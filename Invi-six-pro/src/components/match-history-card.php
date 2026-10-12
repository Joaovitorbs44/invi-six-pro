<?php
$historyStatus=(string)$historyMatch['status'];
$historyMap=empty($historyMatch['map_name'])?'Mapa não definido':matchMapLabel($historyMatch['map_name']);
?>
<a class="history18-card" href="/partida/<?= (int)$historyMatch['id'] ?><?= isset($historyReturn)?'?return='.e(rawurlencode($historyReturn)):'' ?>" aria-label="Ver detalhes da partida <?= (int)$historyMatch['id'] ?>">
 <div class="history18-identity"><div><strong class="history18-code">MATCH #<?= (int)$historyMatch['id'] ?></strong><span class="status18 status18-<?= e(matchStatusTone($historyStatus)) ?>"><?= e(matchStatusLabels()[$historyStatus]??'Status não disponível') ?></span></div><p class="history18-season"><?= e($historyMatch['season_name']) ?></p><?php if(!empty($historyMatch['match_code'])): ?><p class="history18-season"><?= e($historyMatch['match_code']) ?></p><?php endif; ?></div>
 <div><h3 class="history18-map"><?= e($historyMap) ?></h3><p class="history18-result"><?= e(matchHistoryResult($historyMatch)) ?></p><?php if(!empty($historyMatch['team_a_count'])||!empty($historyMatch['team_b_count'])): ?><p class="history18-teams">Equipe A · <?= (int)$historyMatch['team_a_count'] ?> × Equipe B · <?= (int)$historyMatch['team_b_count'] ?></p><?php endif; ?></div>
 <div class="history18-details"><time><?= e(brazilDate($historyMatch['finished_at']??$historyMatch['created_at'],'d/m/Y · H:i')) ?></time><span>Ver detalhes <?= faIcon('arrow-right') ?></span></div>
</a>
