<?php
// Supported visual states. Current BO1 rules supply available, banned and decider.
// picked/opponent-picked are reserved for a future backend that records picks.
$mapState=in_array($mapState,['available','banned','picked','opponent-picked','decider'],true)?$mapState:'available';
$mapLabels=['available'=>'Disponível','banned'=>'BANIDO','picked'=>'Escolhido','opponent-picked'=>'Escolhido','decider'=>'MAPA SELECIONADO'];
$mapName=matchMapLabel($map['name']);
?>
<article class="arena-map is-<?= e($mapState) ?>" data-map-state="<?= e($mapState) ?>">
 <div class="arena-map-art">
  <?php if($map['image']): ?><img src="<?= e($map['image']) ?>" alt="Mapa <?= e($mapName) ?>" width="640" height="360" loading="lazy"><?php else: ?>
   <div class="arena-map-grid" aria-hidden="true"></div><span class="arena-map-placeholder">Imagem não fornecida</span>
  <?php endif; ?>
  <?php if($mapState==='banned'): ?><span class="arena-map-mark" aria-hidden="true"><?= faIcon('xmark') ?></span><?php endif; ?>
 </div>
 <div class="arena-map-info"><h3><?= e($mapName) ?></h3><p><strong><?= e($mapLabels[$mapState]) ?></strong><?php if($map['banned_team']): ?><span>· Time <?= e($map['banned_team']) ?></span><?php elseif($mapState==='decider'): ?><span>· Mapa da partida</span><?php elseif(in_array($mapState,['picked','opponent-picked'],true)&&isset($map['picked_team'])): ?><span>· Time <?= e($map['picked_team']) ?></span><?php endif; ?></p></div>
 <?php if($canBan&&$mapState==='available'): ?><?php $form('ban',['map_id'=>$map['id']],'Banir '. $mapName,'cx-button arena-map-action',['data-veto-action'=>'ban','data-map-label'=>$mapName]); ?><?php endif; ?>
</article>
