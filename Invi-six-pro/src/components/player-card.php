<?php
// $rosterPlayer is an actual match participant. Optional local artwork replaces the initial.
$playerNickname=$rosterPlayer['r6_nickname']??'Conta removida';
$playerArtwork=null;
if(!empty($rosterPlayer['player_id'])) {
 foreach(['webp','png','jpg'] as $extension) {
  $candidate='/assets/images/players/'.(int)$rosterPlayer['player_id'].'.'.$extension;
  if(is_file(dirname(__DIR__, 2).'/public'.$candidate)) { $playerArtwork=$candidate;break; }
 }
}
?>
<article class="arena-player<?= !empty($playerCanPick)?' is-selectable arena-team-'.strtolower($matchData['turn']):'' ?>" data-player-id="<?= (int)$rosterPlayer['player_id'] ?>">
 <div class="arena-player-art">
 <?php if($playerArtwork): ?><img src="<?= e($playerArtwork) ?>" alt="" loading="lazy"><?php else: ?><span class="arena-player-initial" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($playerNickname,0,1))) ?></span><?php endif; ?>
 </div>
 <div class="arena-player-info"><h3 title="<?= e($playerNickname) ?>"><?= e($playerNickname) ?></h3><p><?= e($rosterPlayer['r6_rank']??'Rank indisponível') ?></p><div class="arena-player-badges"><?php if($rosterPlayer['is_captain']): ?><span class="arena-captain-badge"><?= faIcon('chess-rook') ?> Capitão</span><?php endif; ?><?php if(!empty($rosterPlayer['is_simulated'])): ?><span class="simulated-badge">SIMULADO</span><?php endif; ?><?php if(($matchData['match']['status']??'')==='draft'&&!empty($rosterPlayer['team'])): ?><span class="status18 status18-info">Equipe <?= e($rosterPlayer['team']) ?></span><?php endif; ?><?php if($rosterPlayer['ready']): ?><span class="arena-ready"><?= faIcon('check') ?> Pronto</span><?php endif; ?></div></div>
 <?php if(!empty($playerCanPick))$form('pick',['player_id'=>$rosterPlayer['player_id']],'Escolher '.$playerNickname,'cx-button arena-player-action'); ?>
</article>
