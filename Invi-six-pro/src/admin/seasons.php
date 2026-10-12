<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';requireAdmin();
require_once __DIR__.'/../includes/profile.php';require_once __DIR__.'/../includes/seasons.php';
if(isPost()){
 verifyCsrf();
 try{
  if(($_POST['action']??'start')==='close'){
   closeSeason($pdo,positiveId($_POST['season_id']??0),(int)userId(),($_POST['confirm_close']??'')==='1');
   flash('success','Temporada encerrada. Classificação final e histórico preservados.');
  }elseif(($_POST['action']??'start')==='start'){
   startSeason($pdo,(int)userId(),is_string($_POST['name']??null)?$_POST['name']:'');flash('success','Temporada iniciada por 30 dias. Todos começam com 0 pontos.');
  }else throw new RuntimeException('Ação indisponível.');
 }catch(Throwable $error){flash('warning',$error instanceof RuntimeException&&!($error instanceof PDOException)?$error->getMessage():'Não foi possível atualizar a temporada.');}
 redirect('/admin/seasons');
}
$seasons=$pdo->query('SELECT * FROM seasons ORDER BY id DESC')->fetchAll();
$current=null;foreach($seasons as $season)if($season['status']==='active'){$current=$season;break;}
$history=array_filter($seasons,static fn($s)=>$s['status']==='finished');
$pageTitle='Temporadas · INVI SIX PRO';require __DIR__.'/../components/header.php';
?>
<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/competitive.css')) ?>">
<section class="cx-shell cx-workspace"><div class="cx-heading"><div><p class="cx-eyebrow">Administração</p><h1>Temporadas</h1><p>Ciclos de 30 dias e classificação final preservada.</p></div><a class="cx-button" href="/ranking">Ver ranking <?= faIcon('arrow-right') ?></a></div>
<?php if($current):$metrics=seasonMetrics($pdo,(int)$current['id'],true);$expired=$current['ends_at']<=gmdate('Y-m-d H:i:s'); ?>
<article class="cx-card cx-section cx-season-current"><div class="cx-heading"><div><p class="cx-eyebrow">Temporada atual</p><h2><?= e($current['name']) ?></h2></div><span class="status18 status18-<?= $expired?'warning':'success' ?>"><?= $expired?'Aguardando resolução':'Ativa' ?></span></div>
<dl class="cx-summary"><div><dt>Início</dt><dd><?= e(brazilDate($current['starts_at'])) ?></dd></div><div><dt>Prazo</dt><dd><?= e(brazilDate($current['ends_at'])) ?></dd></div><div><dt>Partidas</dt><dd><?= $metrics['matches'] ?></dd></div></dl>
<?php $pending=$pdo->prepare("SELECT COUNT(*) FROM matches WHERE season_id=? AND status NOT IN ('finished','cancelled')");$pending->execute([$current['id']]);$pendingCount=(int)$pending->fetchColumn(); ?>
<?php if($pendingCount): ?><p class="season19-warning"><?= $pendingCount ?> partidas em aberto. Resolva resultados e disputas antes de encerrar. Nenhuma partida será cancelada automaticamente.</p><?php endif; ?>
<button class="cx-button season19-close" type="button" data-season-close-open>Encerrar temporada</button>
<dialog class="season19-dialog" data-season-close-dialog aria-labelledby="season-close-title"><h2 id="season-close-title">Encerrar temporada?</h2><p>Esta ação encerrará a temporada atual e finalizará sua classificação. O histórico será preservado.</p>
<form method="post"><?= csrfField() ?><input type="hidden" name="action" value="close"><input type="hidden" name="season_id" value="<?= (int)$current['id'] ?>"><label><input type="checkbox" name="confirm_close" value="1" required> Confirmo o encerramento da temporada.</label><div class="cx-actions"><button class="cx-button" type="button" data-season-close-cancel>Cancelar</button><button class="cx-button cx-primary" type="submit">Confirmar encerramento</button></div></form></dialog>
</article>
<?php else: ?>
<section class="cx-section"><h2>Iniciar temporada</h2><p class="cx-muted">Novo ciclo de 30 dias, com zero pontos e os ranks R6 cadastrados preservados.</p><form method="post" class="cx-filters"><?= csrfField() ?><input type="hidden" name="action" value="start"><fieldset class="ui-field"><legend><label class="ui-field-label" for="season-name">Nome da temporada</label></legend><input id="season-name" name="name" minlength="3" maxlength="80" required placeholder="Nome do próximo ciclo"></fieldset><button class="cx-button cx-primary" type="submit">Iniciar temporada</button></form></section>
<?php endif; ?>
<p class="cx-footnote">Encerramento automático no prazo, condicionado à resolução das partidas pendentes. Temporadas agendadas elegíveis são ativadas; se não houver uma, inicie o próximo ciclo acima.</p>
<section class="cx-section"><h2>Histórico de temporadas</h2><div class="cx-list"><?php foreach($history as $season):$metrics=seasonMetrics($pdo,(int)$season['id']);$closure=$pdo->prepare('SELECT reason FROM season_closures WHERE season_id=?');$closure->execute([$season['id']]);$reason=$closure->fetchColumn(); ?>
<article class="cx-season-row"><div><h3><?= e($season['name']) ?></h3><p class="cx-muted"><?= e(brazilDate($season['starts_at'])) ?> — <?= e(brazilDate($season['finished_at']??$season['ends_at'])) ?></p></div><span class="status18 status18-neutral">Encerrada<?= $reason==='manual'?' manualmente':'' ?></span><span><?= $metrics['matches'] ?> partidas</span><a class="cx-link" href="/ranking?season=<?= (int)$season['id'] ?>">Classificação final <?= faIcon('arrow-right') ?></a></article>
<?php endforeach; ?><?php if(!$history): ?><p class="cx-muted">Nenhuma temporada encerrada.</p><?php endif; ?></div></section>
<?php $scheduled=array_filter($seasons,static fn($s)=>$s['status']==='scheduled');if($scheduled): ?><section class="cx-section"><h2>Temporadas agendadas</h2><?php foreach($scheduled as $season): ?><p><?= e($season['name']) ?> · <?= e(brazilDate($season['starts_at'])) ?></p><?php endforeach; ?></section><?php endif; ?>
</section>
<?php require __DIR__.'/../components/footer.php';
