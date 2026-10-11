<?php

declare(strict_types=1);
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/punishments.php';
require_once __DIR__ . '/lobby.php';

function matchmakingRanks(): array
{
    return ['Cobre', 'Bronze', 'Prata', 'Ouro', 'Platina', 'Esmeralda', 'Diamante', 'Campeão'];
}

function matchmakingRankImage(?string $rank): ?string
{
    return match ($rank) {
        'Cobre' => '/assets/images/cobre.png',
        'Bronze' => '/assets/images/bronze.png',
        'Prata' => '/assets/images/prata.png',
        'Ouro' => '/assets/images/ouro.png',
        'Platina' => '/assets/images/platina.png',
        'Esmeralda' => '/assets/images/esmeralda.png',
        'Diamante' => '/assets/images/diamante.png',
        'Campeão' => '/assets/images/champion.png',
        default => null,
    };
}

/** Read-only snapshot shared by the page and its live updates. */
function matchmakingState(PDO $pdo, int $userId): array
{
    $statement = accountsDatabase($pdo)->prepare('SELECT id, r6_nickname, role, status FROM users WHERE id = :id');
    $statement->execute([':id' => $userId]);
    $account = $statement->fetch();

    $statement = accountsDatabase($pdo)->prepare('SELECT id, r6_nickname, r6_rank, r6_tracker_url FROM players WHERE user_id = :id');
    $statement->execute([':id' => $userId]);
    $player = $statement->fetch() ?: null;
    if ($player) {
        $player['rank_image'] = matchmakingRankImage($player['r6_rank']);
    }

    $season = $pdo->query("SELECT id, name, starts_at, ends_at FROM seasons WHERE status = 'active' AND starts_at<=CURRENT_TIMESTAMP AND ends_at>CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1")->fetch() ?: null;
    $stats = ['points' => 0, 'wins' => 0, 'losses' => 0, 'matches_played' => 0];
    if ($player && $season) {
        $statement = $pdo->prepare('SELECT points, wins, losses, matches_played FROM player_stats WHERE player_id = :player AND season_id = :season');
        $statement->execute([':player' => $player['id'], ':season' => $season['id']]);
        $stats = $statement->fetch() ?: $stats;
    }

    $queue = competitiveQueue($pdo, (int)($season['id'] ?? 0));
    $isInQueue = false;
    foreach ($queue as &$queuedPlayer) {
        $queuedPlayer['rank_image'] = matchmakingRankImage($queuedPlayer['r6_rank']);
        $queuedPlayer['is_current'] = $player && (int) $queuedPlayer['player_id'] === (int) $player['id'];
        $isInQueue = $isInQueue || $queuedPlayer['is_current'];
    }
    unset($queuedPlayer);

    $match = null;
    if ($player) {
        $statement = $pdo->prepare("SELECT m.id, m.match_code, m.status, m.map_name, mp.ready
            FROM matches m INNER JOIN match_players mp ON mp.match_id = m.id
            WHERE mp.player_id = :player AND m.status NOT IN ('finished', 'cancelled')
            ORDER BY m.id DESC LIMIT 1");
        $statement->execute([':player' => $player['id']]);
        $match = $statement->fetch() ?: null;
    }

    $participants=[];
    if($match){
        require_once __DIR__.'/matches.php';
        $participants=matchDetails($pdo,(int)$match['id'],$userId)['players'];
    }

    return ['account' => $account, 'player' => $player, 'season' => $season,
        'restricted' => competitiveRestriction($pdo, $userId),
        'stats' => $stats, 'queue' => $queue, 'queue_count' => $match?10:count($queue),
        'is_in_queue' => $isInQueue, 'match' => $match, 'participants'=>$participants,
        'queue_busy'=>lobbyFormationBusy($pdo), 'queue_target'=>competitiveQueueTarget(), 'testing'=>false];
}

function competitiveQueue(PDO $pdo, int $season): array
{
    $q = $pdo->prepare('SELECT q.player_id,q.joined_at,COALESCE(ps.points,0) points FROM matchmaking_queue q LEFT JOIN player_stats ps ON ps.player_id=q.player_id AND ps.season_id=? ORDER BY q.joined_at,q.id');
    $q->execute([$season]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    $identities = databaseIdentities($pdo, array_column($rows, 'player_id'), true);
    $queue = [];
    foreach ($rows as $row) {
        $profile = $identities[(int)$row['player_id']] ?? null;
        if (!$profile || $profile['status'] !== 'active') continue;
        $queue[] = $row + ['is_simulated'=>0, 'user_id'=>$profile['user_id'], 'r6_nickname'=>$profile['r6_nickname'], 'r6_rank'=>$profile['r6_rank']];
        if (count($queue) === 10) break;
    }
    return $queue;
}
