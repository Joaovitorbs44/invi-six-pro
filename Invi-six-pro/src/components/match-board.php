<?php
require_once __DIR__.'/icons.php';
$playerCanPick=false;
// Server-rendered board for the individual match page and its authenticated snapshots.
$m=$matchData['match']; $me=$matchData['me']; $players=$matchData['players'];
$labels=['ready_check'=>'Confirmação dos jogadores','captain_vote'=>'Escolhendo capitães','draft'=>'Draft de jogadores','veto'=>'Veto de mapas','side_selection'=>'Partida pronta','playing'=>'Partida em andamento','pending_confirmation'=>'Aguardando resultado','disputed'=>'Resultado em disputa','finished'=>'Finalizada','cancelled'=>'Cancelada'];
$teams=['A'=>[],'B'=>[]]; $captains=[];
foreach($players as $p) { if($p['team'])$teams[$p['team']][]=$p; if($p['is_captain'])$captains[$p['team']]=$p; }
$form=function(string $action,array $fields,string $label,string $class='cx-button',array $attributes=[])use($m){ ?>
<form action="/match/action" method="post" data-match-action <?php foreach($attributes as $attribute=>$value): ?><?= e($attribute) ?>="<?= e((string)$value) ?>" <?php endforeach; ?>><?= csrfField() ?><input type="hidden" name="match_id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="version" value="<?= (int)$m['version'] ?>"><input type="hidden" name="action" value="<?= e($action) ?>"><?php foreach($fields as $key=>$value): ?><input type="hidden" name="<?= e($key) ?>" value="<?= e((string)$value) ?>"><?php endforeach; ?><button class="<?= e($class) ?>" type="submit"><?php if($action==='side')echo faIcon(($fields['side']??'')==='attack'?'crosshairs':'shield'); elseif(in_array($action,['ready','confirm'],true))echo faIcon('check'); elseif($action==='decline')echo faIcon('xmark'); ?><?= e($label) ?></button></form>
<?php };
$copy="InviSixTational\nMatch #".$m['id']."\n".$m['season_name']."\n\nTime A\n".implode("\n",array_column($teams['A'],'r6_nickname'))."\n\nvs\n\nTime B\n".implode("\n",array_column($teams['B'],'r6_nickname'))."\n\nMapa: ".($m['map_name']??'A definir');
?>
<?php

$competitiveStep=['captain_vote'=>1,'draft'=>2,'veto'=>3,'side_selection'=>4,'ready_check'=>0,'playing'=>5,'pending_confirmation'=>5,'disputed'=>5,'finished'=>5,'cancelled'=>5][$m['status']]??0;
if($m['status']==='cancelled')$competitiveStep=!empty($m['started_at'])?5:(!empty($m['team_a_side'])?4:(!empty($m['map_name'])?3:($matchData['picks']||$captains?2:($matchData['votes']?1:0))));
?>
<div class="arena-board" data-status="<?= e($m['status']) ?>" data-arena-view="<?= $m['status']==='veto'?'maps':'match' ?>">
 <header class="arena-heading <?= $m['status']==='veto'?'arena-heading-compact':'' ?>"><div><p class="arena-kicker">Lobby competitivo / <?= e($m['season_name']) ?></p><h1>MATCH #<?= (int)$m['id'] ?></h1><p><?= e($labels[$m['status']]??$m['status']) ?></p></div><dl class="arena-match-meta"><div><dt>Mapa</dt><dd><?= e($m['map_name']?matchMapLabel($m['map_name']):'A definir') ?></dd></div><div><dt>Modo</dt><dd>Bomba</dd></div><div><dt>Lobby</dt><dd><?= e($m['match_code']) ?> · <?= count($players) ?>/10</dd></div></dl></header>
 <?php require __DIR__.'/competitive-steps.php'; ?>
 <?php if(!in_array($m['status'],['veto','draft','ready_check','captain_vote'],true)): require __DIR__.'/competitive-rosters.php';endif; ?>
 <p class="match17-dates">Criada em <?= e(brazilDate($m['created_at'])) ?><?php if(!empty($m['started_at'])): ?> · Início <?= e(brazilDate($m['started_at'])) ?><?php endif; ?><?php if(!empty($m['finished_at'])): ?> · Encerrada <?= e(brazilDate($m['finished_at'])) ?><?php endif; ?> (São Paulo)</p>
 <?php if($m['status']==='veto'): require __DIR__.'/map-veto.php';else: ?>
<section class="cx-card cx-section"><?php if($m['status']!=='ready_check'): ?><h2><?= e($labels[$m['status']]??$m['status']) ?></h2><?php endif; ?>
<?php if($m['status']==='captain_vote'): ?>
 <p class="cx-muted"><?= $matchData['votes'] ?>/10 votos. Cada jogador escolhe um capitão. Os votos definem os dois capitães; empate segue a ordem de entrada. <?php if(!empty($m['is_simulation'])): ?>Cada simulado vota no participante seguinte. Seu voto é manual.<?php endif; ?></p><div class="cx-grid cx-grid-3">
 <?php if($matchData['voted']): ?><p class="cx-muted">Seu voto foi registrado. Aguarde os demais jogadores.</p><?php endif; ?>
 <?php foreach($players as $p): ?><div class="cx-card cx-section cx-player"><img src="<?= e($p['rank_image']) ?>" alt="<?= e($p['r6_rank']??'') ?>" loading="lazy" decoding="async"><div><strong><?= e($p['r6_nickname']??'Conta removida') ?></strong><?php if(!empty($p['is_simulated'])): ?><span class="simulated-badge">SIMULADO</span><?php endif; ?><p class="cx-muted"><?= e($p['r6_rank']??'') ?></p></div><?php if($me&&!$matchData['voted'])$form('vote',['player_id'=>$p['player_id']],'Votar'); ?></div><?php endforeach; ?></div>
<?php elseif($m['status']==='draft'): ?>
 <?php require __DIR__.'/player-draft.php'; ?>
<?php elseif(in_array($m['status'],['side_selection','ready_check'],true)): ?>
 <?php if($m['status']==='ready_check'): ?>
 <div class="ready17">
 <div><p class="ready17-kicker"><?= faIcon('check') ?> Lobby completo · 10/10</p><h2>Partida encontrada</h2><p class="ready17-summary">Sua equipe está esperando. Confirme até <?= e(brazilDate($m['ready_deadline']??null,'H:i')) ?> (São Paulo). Todos os dez jogadores precisam confirmar em 60 segundos. Recusa ou expiração cancela a partida sem pontos.</p></div>
 <div class="ready17-clock"><span>Tempo restante</span><p class="ready-clock" data-ready-deadline="<?= e($m['ready_deadline']??'') ?>" role="timer"></p></div>
 <div class="ready17-progress" role="progressbar" aria-label="Confirmações" aria-valuenow="<?= array_sum(array_column($players,'ready')) ?>" aria-valuemin="0" aria-valuemax="10"><?php foreach($players as $p): ?><span class="<?= $p['ready']?'is-ready':'' ?>"></span><?php endforeach; ?></div>
 <div class="ready17-total"><strong><?= array_sum(array_column($players,'ready')) ?>/10 confirmados</strong><span><?= $me&&!$me['ready']?'Falta sua confirmação':'Aguardando confirmações' ?></span></div>
 <div class="ready-players"><?php foreach($players as $p): ?><article class="ready-slot <?= $p['ready']?'is-ready':'is-pending' ?>"><span class="ready-slot-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($p['r6_nickname']??'?',0,1))) ?></span><strong title="<?= e($p['r6_nickname']) ?>"><?= e($p['r6_nickname']) ?></strong><?php if(!empty($p['is_simulated'])): ?><span class="simulated-badge">SIMULADO</span><?php endif; ?><span class="ready-slot-status"><?= faIcon($p['ready']?'check':'clock') ?> <?= $p['ready']?'Confirmado':'A confirmar' ?></span></article><?php endforeach; ?></div>
 <div class="ready17-actions"><?php if($me&&!$me['ready']): $form('ready',[],'Confirmar','cx-button ready-confirm');$form('decline',[],'Rejeitar','cx-button ready-decline'); elseif($me): ?><p class="cx-muted">Presença confirmada. Aguarde os demais jogadores.</p><?php endif; ?></div>
 </div>
 <?php endif; ?>
 <?php if($m['status']==='side_selection'&&!$m['team_a_side']): ?><p class="cx-muted">O capitão do Time A escolhe o lado inicial.</p><?php if($me&&$me['is_captain']&&$me['team']==='A'): ?><div class="cx-actions"><?php $form('side',['side'=>'attack'],'ATAQUE — Inicie pressionando o objetivo','cx-button side-choice side-attack'); $form('side',['side'=>'defense'],'DEFESA — Proteja o objetivo','cx-button side-choice side-defense'); ?></div><?php endif; ?><?php endif; ?>
 <?php if($m['status']==='side_selection'&&$me&&!$me['ready'])$form('ready',[],'Confirmar presença','cx-button ready-confirm'); ?>
<?php elseif($m['status']==='playing'): ?><p class="cx-muted">Todos prontos. Após a partida, um capitão envia o vencedor e o adversário confirma.</p><?php if($me&&$me['is_captain']): ?><div class="cx-actions"><?php $form('report',['winner'=>'A'],'Time A venceu');$form('report',['winner'=>'B'],'Time B venceu'); ?></div><?php endif; ?>
<?php elseif($m['status']==='pending_confirmation'): ?><p>Resultado enviado: vitória do Time <?= e($m['result_reported_team']) ?>. Aguardando o capitão adversário.</p><?php $reporterTeam=null;foreach($players as $p)if($p['player_id']==$m['result_reported_by'])$reporterTeam=$p['team'];if($me&&$me['is_captain']&&$me['team']!==$reporterTeam): ?><div class="cx-actions"><?php $form('confirm',[],'Confirmar resultado','cx-button cx-primary');$form('dispute',[],'Contestar resultado'); ?></div><?php endif; ?>
<?php elseif($m['status']==='disputed'): ?><p>Resultado contestado. A staff precisa analisar a partida.</p><a class="cx-link" href="/support">Abrir suporte</a><?php if(isStaff()): ?><div class="cx-actions"><?php $form('resolve',['winner'=>'A'],'Definir vitória do Time A');$form('resolve',['winner'=>'B'],'Definir vitória do Time B'); ?></div><?php endif; ?>
<?php elseif($m['status']==='finished'): ?><p class="cx-turn">Time <?= e($m['winner_team']) ?> venceu</p><p class="cx-muted"><?= !empty($m['is_simulation'])?'':'Vitória +5 · Derrota −3 · ' ?><?= !empty($m['is_simulation'])?'Teste encerrado. Nenhum ponto aplicado.':'Resultado registrado na temporada.' ?></p>
<?php else: ?><p class="cx-muted">Partida cancelada. Nenhum ponto aplicado.</p><?php endif; ?>
<?php if($m['map_name']): ?><p><strong>Mapa: <?= e(matchMapLabel($m['map_name'])) ?></strong></p><button type="button" class="cx-button" data-copy-match="<?= e($copy) ?>">Copiar informações</button><?php endif; ?>
</section>
<?php endif; ?>
<?php if($m['map_name']): ?><details class="match19-veto-history"><summary>Histórico do veto</summary><?php require __DIR__.'/map-veto.php'; ?></details><?php endif; ?>
<?php require __DIR__.'/match-timeline.php'; ?>

<dialog class="cx-confirm-dialog arena-confirm-dialog" data-veto-dialog aria-labelledby="veto-confirm-title" aria-describedby="veto-confirm-description">
 <p class="arena-kicker">Confirmar ação</p><h2 id="veto-confirm-title" data-veto-title>Confirmar mapa</h2><p id="veto-confirm-description" data-confirm-description>Esta ação não poderá ser desfeita.</p><div class="cx-actions"><button class="cx-button" type="button" data-veto-cancel>Cancelar</button><button class="cx-button arena-danger" type="button" data-veto-apply>Banir mapa</button></div>
</dialog>
</div>
