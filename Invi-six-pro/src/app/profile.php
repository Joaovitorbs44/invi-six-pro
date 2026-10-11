<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';requireAuth();
require_once __DIR__.'/../includes/profile.php';
require_once __DIR__.'/../includes/matches.php';
if(isPost()){
 verifyCsrf();$twitch=trim((string)($_POST['twitch']??''));
 if($twitch!==''&&!preg_match('/^[a-zA-Z0-9_]{1,25}$/D',$twitch))flash('warning','Informe apenas o nome do canal Twitch, com até 25 letras, números ou sublinhados.');
 else {
  beginWriteTransaction($usersPdo);
  try {
   validateDatabaseActor($usersPdo,(int)userId());
   $usersPdo->prepare('UPDATE users SET twitch=?,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$twitch?:null,userId()]);
   $usersPdo->exec('COMMIT');flash('success','Perfil atualizado.');
  } catch (Throwable $error) {
   if($usersPdo->inTransaction())$usersPdo->rollBack();
   flash('error','Não foi possível atualizar seu perfil.');
  }
 }
 redirect('/perfil');
}
$profile=competitiveProfile($pdo,(int)userId());$p=$profile['player'];$stats=$profile['stats'];$pageTitle='Meu perfil · InviSixTational';
require __DIR__.'/../components/header.php';
$profileAccountDate=$usersPdo->prepare('SELECT created_at FROM users WHERE id=?');$profileAccountDate->execute([userId()]);$profileJoined=$profileAccountDate->fetchColumn();
?>
<link rel="stylesheet" href="/assets/css/competitive.css">
<p class="profile17-since">Membro desde <?= e(brazilDate($profileJoined?:null)) ?> · São Paulo</p>
<section class="cx-shell profile17"><div class="cx-heading"><div><p class="cx-eyebrow">Meu perfil</p><h1><?= e($p['r6_nickname']??$_SESSION['user_r6_nickname']??'Conta') ?></h1><p><?= e($profile['season']['name']??'Aguardando temporada') ?></p></div><?php if($p): ?><div class="cx-player"><img src="<?= e(matchmakingRankImage($p['r6_rank'])) ?>" alt="<?= e($p['r6_rank']) ?>"><strong><?= e($p['r6_rank']) ?></strong></div><?php endif; ?></div>
<?php if(!$p): ?><p class="cx-alert">Esta conta ainda não possui perfil competitivo.</p><?php endif; ?>
<div class="cx-grid cx-grid-3" data-live-competitive><?php foreach(['Pontos'=>$stats['points'],'Posição'=>$profile['position']?'#'.$profile['position']:'—','Partidas'=>$stats['matches_played'],'Vitórias'=>$stats['wins'],'Derrotas'=>$stats['losses'],'Win rate'=>($stats['matches_played']?round(100*$stats['wins']/$stats['matches_played']):0).'%'] as $label=>$value): ?><div class="cx-card cx-section"><p class="cx-muted"><?= e($label) ?></p><h2><?= e((string)$value) ?></h2></div><?php endforeach; ?></div>
<section class="cx-card cx-section"><h2>Twitch</h2><form method="post" class="cx-filters cx-twitch-form"><?= csrfField() ?><div class="ui-field"><label class="ui-field-label" for="twitch">Nome do canal (opcional)</label><input id="twitch" name="twitch" maxlength="25" value="<?= e($p['twitch']??'') ?>" placeholder="Seu canal"></div><button class="cx-button cx-primary" type="submit">Salvar</button></form></section>
<?php $historyRows=$profile['history']; ?>
<section class="cx-section" data-live-competitive><h2>Partidas recentes</h2><a class="cx-link" href="/partidas">Ver todas as partidas</a><?php require __DIR__.'/../components/match-history-list.php'; ?></section>
<a class="cx-link" href="/punishments">Consultar minhas punições</a></section>
<?php require __DIR__.'/../components/footer.php';
