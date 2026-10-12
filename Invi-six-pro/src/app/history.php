<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';
requireAuth();
require_once __DIR__.'/../includes/matches.php';
$labels=matchStatusLabels();
$status=is_string($_GET['status']??null)&&isset($labels[$_GET['status']])?$_GET['status']:'';
$page=max(1,min(999999,(int)(is_scalar($_GET['page']??1)?($_GET['page']??1):1)));
$player=playerForUser($pdo,(int)userId());
$conditions=[];$parameters=[];
if (!isStaff()) {
    $conditions[]='EXISTS (SELECT 1 FROM match_players own WHERE own.match_id=m.id AND own.player_id=?)';
    $parameters[]=(int)($player['id']??0);
}
if ($status!=='') {$conditions[]='m.status=?';$parameters[]=$status;}
$where=$conditions?' WHERE '.implode(' AND ',$conditions):'';
$count=$pdo->prepare('SELECT COUNT(*) FROM matches m'.$where);$count->execute($parameters);
$total=(int)$count->fetchColumn();$pages=max(1,(int)ceil($total/20));$page=min($page,$pages);
$q=$pdo->prepare("SELECT m.*,s.name season_name,(SELECT COUNT(*) FROM match_players WHERE match_id=m.id AND team='A') team_a_count,(SELECT COUNT(*) FROM match_players WHERE match_id=m.id AND team='B') team_b_count FROM matches m JOIN seasons s ON s.id=m.season_id".$where.' ORDER BY m.id DESC LIMIT 20 OFFSET '.(($page-1)*20));
$q->execute($parameters);$historyRows=$q->fetchAll();
$historyReturn='/partidas?status='.rawurlencode($status).'&page='.$page;
$pageTitle='Minhas partidas · INVI SIX PRO';require __DIR__.'/../components/header.php';
?>
<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/competitive.css')) ?>">
<section class="cx-shell history18" data-live-competitive>
<div class="cx-heading"><div><p class="cx-eyebrow">Competitivo</p><h1>Histórico de partidas</h1><p>Resultados e detalhes das partidas disponíveis para sua conta.</p></div><span><?= $total ?> partidas</span></div>
<form method="get" class="history18-filter"><div class="ui-field h-12"><fieldset class="ui-field-outline"><legend><label for="history-status" class="ui-field-label">Status da partida</label></legend><select id="history-status" name="status"><option value="">Todos os status</option><?php foreach($labels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $status===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></fieldset></div><button class="cx-button">Filtrar</button><a href="/partidas">Limpar filtros</a></form>
<?php require __DIR__.'/../components/match-history-list.php'; ?>
<nav aria-label="Paginação de partidas"><?php if($page>1): ?><a href="/partidas?status=<?= e(rawurlencode($status)) ?>&amp;page=<?= $page-1 ?>">Anterior</a><?php endif; ?> <span>Página <?= $page ?> de <?= $pages ?></span> <?php if($page<$pages): ?><a href="/partidas?status=<?= e(rawurlencode($status)) ?>&amp;page=<?= $page+1 ?>">Próxima</a><?php endif; ?></nav>
</section>
<?php require __DIR__.'/../components/footer.php';
