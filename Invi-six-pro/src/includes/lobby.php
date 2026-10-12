<?php
declare(strict_types=1);

function competitiveQueueTarget(): int { return 10; }

function lobbyFormationBusy(PDO $db): bool
{
    return (bool)$db->query("SELECT 1 FROM matches WHERE status IN ('ready_check','captain_vote','draft','veto','side_selection') LIMIT 1")->fetchColumn();
}

function expireReadyChecksInTransaction(PDO $db): void
{
    $db->exec("DELETE FROM matchmaking_queue q WHERE NOT EXISTS (
        SELECT 1 FROM players p JOIN users u ON u.id=p.user_id
        WHERE p.id=q.player_id AND u.status='active' AND u.role IN ('player','admin')
        AND NOT EXISTS (SELECT 1 FROM punishments WHERE user_id=u.id AND type IN ('suspension','ban')
            AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at>CURRENT_TIMESTAMP)))");
    $ids = $db->query("UPDATE matches SET status='cancelled',finished_at=CURRENT_TIMESTAMP,version=version+1
        WHERE status='ready_check' AND ready_deadline<=CURRENT_TIMESTAMP RETURNING id")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $db->prepare('INSERT INTO logs(match_id,type,description) VALUES(?,?,?)')
            ->execute([$id, 'ready_expired', 'Prazo de 60 segundos expirado. Participantes liberados, sem pontos.']);
    }
}

// Lazy reconciliation works across serverless requests, without a background worker.
function reconcileLobby(PDO $db): void
{
    $pending = $db->query("SELECT EXISTS (
        SELECT 1 FROM matches WHERE status='ready_check' AND ready_deadline<=CURRENT_TIMESTAMP
    ) OR EXISTS (
        SELECT 1 FROM matchmaking_queue q WHERE NOT EXISTS (
            SELECT 1 FROM players p JOIN users u ON u.id=p.user_id
            WHERE p.id=q.player_id AND u.status='active' AND u.role IN ('player','admin')
            AND NOT EXISTS (SELECT 1 FROM punishments WHERE user_id=u.id AND type IN ('suspension','ban')
                AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at>CURRENT_TIMESTAMP))
        )
    )")->fetchColumn();
    if (!$pending) return;
    beginWriteTransaction($db);
    try {
        expireReadyChecksInTransaction($db);
        $db->exec('COMMIT');
    } catch (Throwable $error) {
        $db->exec('ROLLBACK');
        throw $error;
    }
}
