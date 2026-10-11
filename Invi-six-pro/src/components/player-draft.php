<?php
$turn=$matchData['turn'];$owner=$me&&$me['is_captain']&&$me['team']===$turn;
?>
<div class="draft18-intro"><div><h3>Escolha sua equipe</h3><p>Os capitães escolhem os jogadores disponíveis para formar suas equipes.</p><p class="cx-turn">Vez de <?= e($captains[$turn]['r6_nickname']??'Capitão') ?> · Equipe <?= e($turn) ?></p></div><span class="draft18-count"><?= (int)$matchData['picks'] ?>/8 escolhas</span></div>
<p class="arena-draft-note">Formação 1–2–2–2–2–1, incluindo os capitães. Próximas escolhas: B → A → A → B → B → A → A → B. Os dois capitães já ocupam a primeira vaga de suas equipes.</p>
<div class="draft-layout">
 <section class="draft-team arena-team-a<?= $turn==='A'?' is-turn':'' ?>" aria-label="Equipe A">
  <div class="draft-team-heading"><span>Equipe A<?= $turn==='A'?' · Escolhendo':'' ?></span><strong><?= e($captains['A']['r6_nickname']??'Capitão a definir') ?></strong><small><?= count($teams['A']) ?>/5 jogadores</small></div>
  <div class="draft-team-players"><?php foreach($teams['A'] as $rosterIndex=>$rosterPlayer):require __DIR__.'/player-card.php';endforeach; ?><?php for($vacancy=count($teams['A']);$vacancy<5;$vacancy++): ?><div class="draft-vacancy">Vaga <?= $vacancy+1 ?> · Aguardando escolha</div><?php endfor; ?></div>
 </section>
 <section class="draft-pool" aria-labelledby="draft-available-title">
  <h3 class="arena-draft-title" id="draft-available-title">Jogadores disponíveis</h3>
  <div class="arena-available"><?php foreach($players as $player):if($player['team'])continue;$playerCanPick=false; ?>
   <div class="arena-draft-option">
    <?php $rosterPlayer=$player;$rosterIndex=0;require __DIR__.'/player-card.php'; ?>
    <?php if($owner)$form('pick',['player_id'=>$player['player_id']],'Selecionar para Equipe '.$turn,'cx-button arena-draft-action'); ?>
   </div>
  <?php endforeach; ?></div>
 </section>
 <section class="draft-team arena-team-b<?= $turn==='B'?' is-turn':'' ?>" aria-label="Equipe B">
  <div class="draft-team-heading"><span>Equipe B<?= $turn==='B'?' · Escolhendo':'' ?></span><strong><?= e($captains['B']['r6_nickname']??'Capitão a definir') ?></strong><small><?= count($teams['B']) ?>/5 jogadores</small></div>
  <div class="draft-team-players"><?php foreach($teams['B'] as $rosterIndex=>$rosterPlayer):require __DIR__.'/player-card.php';endforeach; ?><?php for($vacancy=count($teams['B']);$vacancy<5;$vacancy++): ?><div class="draft-vacancy">Vaga <?= $vacancy+1 ?> · Aguardando escolha</div><?php endfor; ?></div>
 </section>
</div>
<?php if($matchData['draft_history']): ?><ol class="cx-timeline draft18-history" aria-label="Histórico de escolhas"><?php foreach($matchData['draft_history'] as $pick): ?><li>Escolha <?= (int)$pick['pick_number'] ?> · Equipe <?= e($pick['team']) ?> · <?= e($pick['r6_nickname']) ?></li><?php endforeach; ?></ol><?php endif; ?>
