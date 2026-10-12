<?php
$additionalEventLabels=['lobby_completed'=>'Partida encontrada','ready_timeout'=>'Prazo de confirmação','ready_completed'=>'Confirmações concluídas','captain_defined'=>'Capitão definido','map_defined'=>'Mapa definido','side_selected'=>'Lados definidos','player_ready'=>'Confirmação','ready_declined'=>'Partida recusada','result_disputed'=>'Resultado contestado','match_cancelled'=>'Cancelamento','match_started'=>'Partida iniciada','draft_selection_migration'=>'Atualização do draft'];
$eventLabels=['match_created'=>'Partida encontrada','ready'=>'Confirmação','ready_confirmed'=>'Confirmação','all_ready'=>'Confirmações concluídas','captains'=>'Capitães','captains_selected'=>'Capitães','vote'=>'Votação','captain_vote'=>'Votação','draft_pick'=>'Escolha de jogador','pick'=>'Escolha de jogador','map_banned'=>'Veto','ban'=>'Veto','side'=>'Lados','result_reported'=>'Resultado enviado','result_confirmed'=>'Resultado confirmado','match_finished'=>'Partida encerrada','simulation_finished'=>'Simulação encerrada','simulated_action'=>'Ação simulada','simulated_result_confirmed'=>'Confirmação simulada','testing_reset'=>'Reinício de teste','cancelled'=>'Cancelamento','staff_resolution'=>'Resolução administrativa'];
$eventLabels+=$additionalEventLabels;
$renderEvents=static function(array $events)use($eventLabels){ ?>
<ol class="timeline19"><?php foreach($events as $event): ?><li><time><?= e(brazilDate($event['created_at'])) ?></time><span class="timeline19-type"><?= e($eventLabels[$event['type']]??'Evento da partida') ?></span><span><?= e($event['description']) ?></span></li><?php endforeach; ?></ol>
<?php };
?>
<section class="cx-section"><h2>Histórico da partida</h2><?php $renderEvents(array_slice($matchData['events'],0,10)); ?>
<?php if(count($matchData['events'])>10): ?><details class="timeline19-more"><summary>Mostrar mais · <?= count($matchData['events'])-10 ?> eventos</summary><?php $renderEvents(array_slice($matchData['events'],10)); ?></details><?php endif; ?>
<?php if(isStaff()&&!in_array($m['status'],['finished','cancelled'],true))$form('cancel',[],'Cancelar partida'); ?>
</section>
