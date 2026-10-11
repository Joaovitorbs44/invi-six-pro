<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';requireStaff();
require_once __DIR__.'/../includes/matches.php';
$labels=matchStatusLabels();
$status=is_string($_GET['status']??null)&&isset($labels[$_GET['status']])?$_GET['status']:'';
$q=$pdo->prepare("SELECT m.*,s.name season_name,(SELECT COUNT(*) FROM match_players WHERE match_id=m.id AND team='A') team_a_count,(SELECT COUNT(*) FROM match_players WHERE match_id=m.id AND team='B') team_b_count FROM matches m JOIN seasons s ON s.id=m.season_id ".($status!==''?'WHERE m.status=? ':'').'ORDER BY m.id DESC LIMIT 100');
$q->execute($status!==''?[$status]:[]);$historyRows=$q->fetchAll(PDO::FETCH_ASSOC);$historyReturn='/admin/matches?status='.rawurlencode($status).'&page=1';$pageTitle='Histórico de partidas · INVI SIX PRO';require __DIR__.'/../components/header.php';
?>
<link rel="stylesheet" href="/assets/css/competitive.css">
<section class="cx-shell history18"><div class="cx-heading"><div><p class="cx-eyebrow">Administração / Competitivo</p><h1>Histórico de partidas</h1><p>Consulte partidas, resultados e disputas da temporada.</p></div><span class="status18 status18-info"><?= count($historyRows) ?> partidas</span></div>
<form class="history18-filter" method="get"><div class="ui-field"><label class="ui-field-label" for="field-matches-status-0">Status da partida</label><select id="field-matches-status-0" name="status"><option value="">Todos os status</option><?php foreach($labels as $value=>$label): ?><option value="<?= e($value) ?>" <?= $status===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div><button class="cx-button" type="submit">Filtrar</button><?php if($status!==''): ?><a href="/admin/matches">Limpar filtros</a><?php endif; ?></form>
<?php require __DIR__.'/../components/match-history-list.php'; ?>
</section>
<?php require __DIR__.'/../components/footer.php';
