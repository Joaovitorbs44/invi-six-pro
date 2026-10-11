<?php
declare(strict_types=1);
require_once __DIR__ . '/account-setup.php';

function deleteAccessRequest(PDO $pdo, int $requestId, int $adminId): void
{
    $pdo = accountsDatabase($pdo);
    beginWriteTransaction($pdo);
    try {
        validateDatabaseAdmin($pdo,$adminId);
        $statement = $pdo->prepare('SELECT * FROM access_requests WHERE id = ?');
        $statement->execute([$requestId]);
        $request = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$request || !in_array($request['status'], ['pending', 'approved'], true)
            || $request['activated_at'] !== null || setupAccountExists($pdo, $request)) {
            throw new RuntimeException('Esta solicitação não pode ser excluída.');
        }
        setupLog($pdo, 'access_request_deleted', $requestId, null, $adminId);
        $pdo->prepare('DELETE FROM access_requests WHERE id = ?')->execute([$requestId]);
        $pdo->exec('COMMIT');
    } catch (Throwable $exception) {
        $pdo->exec('ROLLBACK');
        throw $exception;
    }
}

function deletePlayerAccount(PDO $pdo, int $targetId, int $adminId): void
{
    if ($targetId === $adminId) throw new RuntimeException('Você não pode remover sua própria conta.');
    $accounts = accountsDatabase($pdo);
    $accountPrefix = "";
    $started = false;
    try {
        beginWriteTransaction($pdo);
        $started = true;
        validateDatabaseAdmin($pdo,$adminId);
        $statement = $accounts->prepare('SELECT * FROM users WHERE id=?');
        $statement->execute([$targetId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['role'] !== 'player') throw new RuntimeException('Somente contas de jogadores podem ser removidas.');
        $profile = playerForUser($pdo,$targetId);
        if ($profile) {
            $playerId=(int)$profile['id'];
            $statement=$pdo->prepare("SELECT 1 FROM matches m WHERE m.status NOT IN('finished','cancelled') AND (
                EXISTS(SELECT 1 FROM match_players WHERE match_id=m.id AND player_id=:player)
                OR EXISTS(SELECT 1 FROM captain_votes WHERE match_id=m.id AND (voter_player_id=:player OR voted_player_id=:player))
                OR EXISTS(SELECT 1 FROM draft_picks WHERE match_id=m.id AND (captain_player_id=:player OR selected_player_id=:player))
                OR EXISTS(SELECT 1 FROM map_veto WHERE match_id=m.id AND banned_by_player_id=:player)
                OR m.result_reported_by=:player OR m.result_confirmed_by=:player) LIMIT 1");
            $statement->execute([':player'=>$playerId]);
            if ($statement->fetchColumn()) throw new RuntimeException('Este jogador está participando de uma partida ativa. Finalize ou cancele a partida antes de removê-lo.');
            foreach (['matchmaking_queue','player_stats'] as $table) $pdo->prepare("DELETE FROM $table WHERE player_id=?")->execute([$playerId]);
            foreach (['match_players'=>['player_id'],'captain_votes'=>['voter_player_id','voted_player_id'],'draft_picks'=>['captain_player_id','selected_player_id'],'map_veto'=>['banned_by_player_id'],'matches'=>['result_reported_by','result_confirmed_by']] as $table=>$columns) {
                foreach ($columns as $column) $pdo->prepare("UPDATE $table SET $column=NULL WHERE $column=?")->execute([$playerId]);
            }
        }
        foreach (['logs'=>['user_id','staff_user_id'],'support_tickets'=>['user_id','assigned_staff_id','related_user_id'],'support_messages'=>['sender_id'],'punishments'=>['user_id','staff_user_id','revoked_by']] as $table=>$columns) {
            foreach ($columns as $column) $pdo->prepare("UPDATE $table SET $column=NULL WHERE $column=?")->execute([$targetId]);
        }
        $pdo->prepare('DELETE FROM '.$accountPrefix.'access_requests WHERE r6_nickname=?')->execute([$user['r6_nickname']]);
        $pdo->prepare("DELETE FROM ".$accountPrefix."users WHERE id=? AND role='player'")->execute([$targetId]);
        $pdo->prepare('INSERT INTO logs(type,description,staff_user_id,additional_data) VALUES(?,?,?,?)')->execute(['player_deleted','Conta de jogador removida.',$adminId,json_encode(['removed_user_id'=>$targetId])]);
        $pdo->exec('COMMIT');
        $started=false;
    } catch (Throwable $exception) {
        if ($started) $pdo->exec('ROLLBACK');
        throw $exception;
    } finally {
        $statement=null;
    }
}
