<div class="arena-rosters">
<?php foreach(['A','B'] as $rosterTeam): ?>
 <?php if($rosterTeam==='B'): ?><div class="arena-versus" aria-hidden="true">VS</div><?php endif; ?>
 <section class="arena-roster arena-team-<?= strtolower($rosterTeam) ?>" aria-label="Jogadores do Time <?= $rosterTeam ?>">
  <?php $rosterSide=$rosterTeam==='A'?$m['team_a_side']:$m['team_b_side']; ?>
  <div class="arena-roster-heading"><span class="arena-team-symbol" aria-hidden="true"></span><div><h2>Time <?= $rosterTeam ?></h2><p class="arena-side <?= $rosterSide?'':'is-undecided' ?>"><?= competitiveSideIcon($rosterSide) ?><span><?= $rosterSide==='attack'?'Ataque':($rosterSide==='defense'?'Defesa':'Lado a definir') ?></span></p><p>Capitão <strong><?= e($captains[$rosterTeam]['r6_nickname']??'A definir') ?></strong></p></div><span class="arena-roster-count"><?= count($teams[$rosterTeam]) ?>/5</span></div>
  <div class="arena-player-grid" role="region" aria-label="Roster do Time <?= $rosterTeam ?>"><?php foreach($teams[$rosterTeam] as $rosterIndex=>$rosterPlayer): ?><?php require __DIR__.'/player-card.php'; ?><?php endforeach; ?></div>
  <?php if(count($teams[$rosterTeam])<5): ?><p class="arena-empty">Aguardando conclusão do draft.</p><?php endif; ?>
 </section>
<?php endforeach; ?>
</div>
