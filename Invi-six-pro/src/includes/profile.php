<?php
declare(strict_types=1);
require_once __DIR__.'/matchmaking.php';
require_once __DIR__.'/seasons.php';

function competitiveSeason(PDO $pdo): ?array
{
    return $pdo->query("SELECT id,name,starts_at,ends_at FROM seasons WHERE status='active' AND starts_at<=CURRENT_TIMESTAMP AND ends_at>CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: null;
}

function competitiveRanking(PDO $pdo, ?int $season, int $limit=100): array
{
    if (!$season) return [];
    seasonSchema($pdo);
    $closed=$pdo->prepare('SELECT 1 FROM season_closures WHERE season_id=?');$closed->execute([$season]);
    if($closed->fetchColumn()){
        $q=$pdo->prepare('SELECT player_id id,NULL user_id,nickname r6_nickname,r6_rank,NULL twitch,points,wins,losses,matches_played,position FROM season_final_standings WHERE season_id=? ORDER BY position LIMIT ?');
        $q->bindValue(1,$season,PDO::PARAM_INT);$q->bindValue(2,max(1,$limit),PDO::PARAM_INT);$q->execute();return $q->fetchAll(PDO::FETCH_ASSOC);
    }
    $profiles = accountsDatabase($pdo)->query("SELECT p.id,p.user_id,p.r6_nickname,p.r6_rank,u.twitch FROM players p JOIN users u ON u.id=p.user_id WHERE u.status='active' AND u.role IN('player','admin')")->fetchAll(PDO::FETCH_ASSOC);
    $q = $pdo->prepare('SELECT player_id,points,wins,losses,matches_played FROM player_stats WHERE season_id=?');
    $q->execute([$season]);
    $stats = [];
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) $stats[(int)$row['player_id']] = $row;
    foreach ($profiles as &$profile) $profile += $stats[(int)$profile['id']] ?? ['points'=>0,'wins'=>0,'losses'=>0,'matches_played'=>0];
    unset($profile);
    usort($profiles, static fn($a,$b) => ((int)$b['points'] <=> (int)$a['points']) ?: ((int)$b['wins'] <=> (int)$a['wins']) ?: ((int)$a['matches_played'] <=> (int)$b['matches_played']) ?: ((int)$a['id'] <=> (int)$b['id']));
    return array_slice($profiles,0,max(1,$limit));
}

function competitiveProfile(PDO $pdo, int $user): array
{
    $player = playerForUser($pdo,$user);
    $season = competitiveSeason($pdo);
    $stats = ['points'=>0,'wins'=>0,'losses'=>0,'matches_played'=>0];
    $position = null;
    $history = [];
    if ($player) {
        if ($season) {
            $q = $pdo->prepare('SELECT points,wins,losses,matches_played FROM player_stats WHERE player_id=? AND season_id=?');
            $q->execute([$player['id'],$season['id']]);
            $stats = $q->fetch(PDO::FETCH_ASSOC) ?: $stats;
            foreach (competitiveRanking($pdo,(int)$season['id'],PHP_INT_MAX) as $i=>$ranked) {
                if ((int)$ranked['id'] === (int)$player['id']) { $position=$i+1; break; }
            }
        }
        $q=$pdo->prepare("SELECT m.id,m.match_code,m.map_name,m.status,m.finished_at,m.created_at,m.winner_team,m.points_applied,m.is_simulation,mp.team,s.name season_name FROM match_players mp JOIN matches m ON m.id=mp.match_id JOIN seasons s ON s.id=m.season_id WHERE mp.player_id=? ORDER BY m.id DESC LIMIT 20");
        $q->execute([$player['id']]);
        $history=$q->fetchAll(PDO::FETCH_ASSOC);
    }
    return ['player'=>$player,'season'=>$season,'stats'=>$stats,'position'=>$position,'history'=>$history];
}

function seasonMetrics(PDO $pdo, int $id, bool $active=false): array
{
    $q=$pdo->prepare('SELECT COUNT(DISTINCT player_id) FROM (SELECT player_id FROM player_stats WHERE season_id=? UNION SELECT mp.player_id FROM match_players mp JOIN matches m ON m.id=mp.match_id WHERE m.season_id=?) participants');
    $q->execute([$id,$id]);
    $players=(int)$q->fetchColumn();
    if ($active) $players=(int)accountsDatabase($pdo)->query("SELECT COUNT(*) FROM players p JOIN users u ON u.id=p.user_id WHERE u.status='active' AND u.role IN('player','admin')")->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*) FROM matches WHERE season_id=?');
    $q->execute([$id]);
    return ['players'=>$players,'matches'=>(int)$q->fetchColumn()];
}
