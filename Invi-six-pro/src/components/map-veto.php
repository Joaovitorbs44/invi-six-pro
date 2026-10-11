<section class="arena-veto" id="match-veto" aria-labelledby="veto-title">
 <header class="arena-veto-heading"><div><p class="arena-kicker">MAP VETO / BO1</p><h2 id="veto-title">Veto de mapas</h2><p>Os capitães alternam bans até definir o mapa da partida.</p></div><div class="arena-veto-captains"><?php foreach(['A','B'] as $vetoTeam): ?><div class="arena-team-<?= strtolower($vetoTeam) ?>"><span class="arena-team-symbol" aria-hidden="true"></span><div><span>Time <?= $vetoTeam ?></span><strong><?= e($captains[$vetoTeam]['r6_nickname']??'A definir') ?></strong><small>Capitão</small></div></div><?php endforeach; ?></div></header>
 <div class="arena-turn"><div><span><?= $m['status']==='veto'?'Vez de':'Mapa definido' ?></span><strong><?= $m['status']==='veto'?e($captains[$matchData['turn']]['r6_nickname']??'A definir').' · Time '.e($matchData['turn']):e($m['map_name']?matchMapLabel($m['map_name']):'A definir') ?></strong></div><div><span>Ação</span><strong><?= $m['status']==='veto'?'Banir mapa':'Veto concluído' ?></strong></div><?php if($m['status']==='veto'): ?><small>Ban <?= $matchData['bans']+1 ?> de <?= max(0,count($matchData['maps'])-1) ?></small><?php endif; ?></div>
 <?php if($matchData['maps']): ?>
  <?php
   $canBan=$m['status']==='veto'&&$me&&$me['is_captain']&&$me['team']===$matchData['turn'];
   $displayMaps=$matchData['maps'];$catalog=matchMapCatalog();
   usort($displayMaps,static fn($a,$b)=>(($catalog[$a['name']]['order']??100)<=>($catalog[$b['name']]['order']??100))?:((int)$a['sort_order']<=>(int)$b['sort_order']));
  ?>
  <p class="arena-instruction"><?= $canBan?'Selecione um mapa para revisar e confirmar o ban.':($m['status']==='veto'?'Acompanhe a escolha do capitão.':'O mapa decisivo está confirmado.') ?></p>
  <div class="arena-map-track" role="region" aria-label="Mapas do veto">
   <?php foreach($displayMaps as $map): $mapState=$map['banned_team']?'banned':($m['map_name']===$map['name']?'decider':'available');require __DIR__.'/map-card.php';endforeach; ?>
  </div>
  <section class="arena-veto-order" aria-labelledby="veto-order-title"><h3 id="veto-order-title">Ordem do veto</h3><ol>
   <?php foreach($matchData['veto_history'] as $veto): ?><li><span><?= str_pad((string)$veto['veto_order'],2,'0',STR_PAD_LEFT) ?></span><small>Time <?= e($veto['team']) ?></small><strong class="arena-ban-label">BAN</strong><p><?= e(matchMapLabel($veto['name'])) ?></p></li><?php endforeach; ?>
   <?php if($m['map_name']): ?><li><span><?= str_pad((string)($matchData['bans']+1),2,'0',STR_PAD_LEFT) ?></span><small>Mapa final</small><strong>DECIDER</strong><p><?= e(matchMapLabel($m['map_name'])) ?></p></li><?php endif; ?>
  </ol><?php if(!$matchData['veto_history']&&!$m['map_name']): ?><p class="arena-empty">O veto ainda não começou.</p><?php endif; ?></section>
 <?php else: ?><p class="arena-empty">O veto ainda não começou.</p><?php endif; ?>
</section>
