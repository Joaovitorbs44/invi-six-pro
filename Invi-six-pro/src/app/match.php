<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php'; requireAuth();
require_once __DIR__.'/../includes/matches.php';
try { $matchData=matchDetails($pdo,positiveId($_GET['match_id']??0),(int)userId(),isStaff()); }
catch (Throwable $error) { http_response_code(404); exit('Partida indisponível.'); }
$pageTitle='Partida #'.$matchData['match']['id'];
$historyDestination=matchHistoryReturn($_GET['return']??null);
if(!isStaff()&&str_starts_with($historyDestination,'/admin/'))$historyDestination='/partidas';
require __DIR__.'/../components/header.php';
?>
<link rel="stylesheet" href="/assets/css/competitive.css">
<link rel="stylesheet" href="/assets/css/arena.css">
<link rel="stylesheet" href="/assets/css/stage16.css">
<section class="cx-shell arena-shell match19-page"><a class="cx-link" href="<?= e($historyDestination) ?>"><?= faIcon('arrow-left') ?> Voltar para partidas</a>
<div data-match-board data-match-id="<?= (int)$matchData['match']['id'] ?>" data-version="<?= (int)$matchData['match']['version'] ?>"><?php require __DIR__.'/../components/match-board.php'; ?></div></section>
<script src="/assets/js/match.js" defer></script>
<?php require __DIR__.'/../components/footer.php';
