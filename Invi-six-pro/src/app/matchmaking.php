<?php

declare(strict_types=1);

require_once __DIR__ . '/../components/head.php';
require_once __DIR__ . '/../includes/matchmaking.php';

$isSnapshot = ($_GET['snapshot'] ?? '') === '1';
if ($isSnapshot) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Sessão expirada. Entre novamente.']);
        exit;
    }
}
requireAuth();
if (userRole() === 'staff') {
    if ($isSnapshot) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso não autorizado.']);
        exit;
    }
    redirect('/admin/support');
}

if ($isSnapshot) session_write_close();

try {
    $state = matchmakingState($pdo, (int) userId());
} catch (Throwable $exception) {
    if (!$isSnapshot) {
        http_response_code(503);
        flash('error', 'Não foi possível carregar o matchmaking. Tente novamente.');
        require_once __DIR__ . '/../components/header.php';
        echo '<section class="site-container py-12"><h1>Matchmaking indisponível</h1><p>Tente novamente em alguns instantes.</p></section>';
        require_once __DIR__ . '/../components/footer.php';
        exit;
    }
    http_response_code(503);
    echo json_encode(['error' => 'Não foi possível atualizar a fila.']);
    exit;
}
if (!$state['account'] || $state['account']['status'] !== 'active' ||
    (!$state['player'] && !isAdmin())) {
    http_response_code(403);
    if ($isSnapshot) {
        echo json_encode(['error' => 'Perfil de jogador indisponível.']);
    } else {
        echo 'Perfil de jogador indisponível.';
    }
    exit;
}
if ($isSnapshot) {
    echo json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

if ($state['match']) redirect('/partida/'.(int)$state['match']['id']);
require_once __DIR__ . '/../includes/profile.php';
$profileSummary = competitiveProfile($pdo, (int) userId());
$pageTitle = 'Matchmaking · Lobby R6';
$player = $state['player'];
$season = $state['season'];
$stats = $state['stats'];
$queue = $state['queue'];
$queueCount = $state['queue_count'];
$queueTarget = $state['queue_target'];
$displayPlayers = $state['participants'] ?: $queue;
$displayCount = count($displayPlayers);
$progressCount = $state['match'] ? $queueTarget : $queueCount;
$isInQueue = $state['is_in_queue'];
$match = $state['match'];
$restricted = $state['restricted'];
$nickname = $player['r6_nickname'] ?? $state['account']['r6_nickname'];
$matchLabels = ['ready_check' => 'Aguardando presença', 'captain_vote' => 'Votação de capitães',
    'draft' => 'Escolha de equipes', 'veto' => 'Seleção de mapa', 'side_selection' => 'Escolha de lado',
    'playing' => 'Em andamento', 'pending_confirmation' => 'Aguardando resultado', 'disputed' => 'Em análise'];
$viewState = $match ? ($match['status'] === 'playing' ? 'playing' : 'match') : ($restricted ? 'restricted' : (!$season ? 'no-season' : ($state['queue_busy']?'busy':($isInQueue ? 'queued' : 'idle'))));
$statusTitle = ['busy'=>'Uma partida está em formação', 'idle'=>'Entre no lobby competitivo', 'queued'=>'Buscando jogadores', 'match'=>'Partida encontrada', 'playing'=>'Partida em andamento', 'restricted'=>'Acesso à fila restrito', 'no-season'=>'Aguardando temporada'][$viewState];
$statusDescription = ['busy'=>'Aguarde a formação das equipes e o início da partida.', 'idle'=>'Entre na fila. A partida começa com dez jogadores reais.', 'queued'=>'Sua vaga está reservada. Acompanhe a fila enquanto os jogadores chegam.', 'match'=>'Seu lobby está pronto. Continue na etapa competitiva abaixo.', 'playing'=>'Boa partida. Acompanhe os times e o resultado no painel da partida.', 'restricted'=>'Consulte suas punições ou fale com o suporte para entender a restrição.', 'no-season'=>'A fila será liberada quando a administração abrir uma temporada.'][$viewState];
$myQueued = null;
foreach ($queue as $queuedPlayer) if ($queuedPlayer['is_current']) $myQueued = $queuedPlayer;
require_once __DIR__ . '/../components/header.php';
?>

<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/matchmaking.css')) ?>">
<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/competitive.css')) ?>">

<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/stage16.css')) ?>">
<section class="mm-page stage16" id="matchmaking" data-queue-state="<?= e($viewState) ?>" aria-labelledby="mm-title">
<div class="site-container mm-shell">
<header class="intro16"><div><p class="intro16-badge"><?= faIcon('crosshairs') ?> COMPETITIVO 5V5</p><h1 id="mm-title">Sua próxima partida<br><span>começa aqui.</span></h1><p class="intro16-description">Um lobby completo. Duas equipes. Sua próxima disputa no Rainbow Six Siege.</p></div>
<div class="intro16-meta"><span id="mm-season-state" class="mm-tag"><?= $season?'Temporada ativa':'Sem temporada ativa' ?></span><p><strong id="mm-header-count"><?= $queueCount ?></strong> participantes no lobby</p><a href="/perfil">Histórico de partidas <?= faIcon('arrow-up-right-from-square') ?></a></div></header>
<div class="mm-layout lobby16-layout">
<div class="mm-main">
<section class="mm-card queue16" aria-labelledby="queue16-title"><div class="queue16-heading"><div><span class="queue16-overline">RAINBOW SIX SIEGE / PC</span><h2 id="queue16-title">Fila competitiva</h2></div><span class="mm-tag">BO1 · 5V5</span></div>
<div class="queue16-meter"><div class="queue16-mark" id="mm-emblem" data-searching="<?= $isInQueue?'true':'false' ?>"><?= faIcon('crosshairs') ?></div><div class="queue16-count">          <div class="mm-roster-heading"><h3><?= faIcon('users') ?><span id="mm-roster-title"><?= $match?'Participantes da partida':'Jogadores na fila' ?></span></h3><span id="mm-empty-count" <?= $displayCount?'hidden':' ' ?>>0 / <?= $queueTarget ?></span><details class="player-popover mm-player-popover" id="queue-popover" data-player-popover <?= !$displayCount?'hidden':' ' ?>><summary class="player-count" aria-controls="queue-player-panel"><strong id="mm-count"><?= $displayCount ?></strong><span id="mm-count-target"> / <?= $match?10:$queueTarget ?></span></summary><div class="player-panel" id="queue-player-panel" data-player-panel role="region" aria-label="Jogadores na fila" tabindex="-1"><p class="player-panel-title">Jogadores na fila</p>
          <ol class="mm-roster" id="mm-roster" aria-label="Posições na fila">
            <?php for ($slot = 0; $slot < max($queueTarget,$displayCount); $slot++) { $queued = $displayPlayers[$slot] ?? null; if($queued){$queued['is_current']=isset($queued['is_current'])?$queued['is_current']:($queued['user_id']==userId());$queued['joined_at']=$queued['joined_at']??'';$queued['points']=$queued['points']??0;} ?>
              <li class="mm-slot <?= $queued ? 'mm-slot-filled' : '' ?> <?= $queued && $queued['is_current'] ? 'mm-slot-self' : '' ?>">
                <span class="mm-slot-number"><?= str_pad((string) ($slot + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <span class="mm-slot-avatar"><?= $queued ? e(mb_strtoupper(mb_substr($queued['r6_nickname'], 0, 1))) : faIcon('users') ?></span>
                <div class="mm-slot-copy"><span class="mm-slot-name"><?= e($queued['r6_nickname'] ?? 'Vaga disponível') ?></span><?php if(!empty($queued['is_simulated'])): ?><span class="simulated-badge">SIMULADO</span><?php endif; ?><span class="mm-slot-rank"><?= e($queued['r6_rank'] ?? 'Aguardando jogador') ?><?= $queued?' · '.($match?($queued['ready']?'Pronto':'A confirmar'):'Na fila'):'' ?></span><span class="mm-slot-wait" data-mm-wait-start="<?= e($queued['joined_at']??'') ?>"><?= $queued?'Desde '.brazilDate($queued['joined_at'],'H:i'):'' ?></span></div>
                <?php if ($queued && $queued['is_current']) { ?><span class="mm-self-label">Você</span><?php } ?>
                <?php if ($queued && $queued['rank_image']) { ?><img class="mm-slot-rank-image" src="<?= e($queued['rank_image']) ?>" alt="" width="26" height="26" loading="lazy" decoding="async"><?php } ?>
              </li>
            <?php } ?>
          </ol>
</div></details></div>
</div></div>
<div class="mm-progress" id="mm-progress" role="progressbar" aria-label="Participantes do lobby" aria-valuemin="0" aria-valuemax="10" aria-valuenow="<?= $progressCount ?>"><?php for($slot=0;$slot<10;$slot++): ?><span data-filled="<?= $slot<$progressCount?'true':'false' ?>"></span><?php endfor; ?></div>
<div class="queue16-status"><span class="mm-state-tag" id="mm-state-tag"><?= $match?'Partida encontrada':($state['queue_busy']?'Lobby em andamento':'Aguardando você') ?></span><h3 id="mm-state-title"><?= e($statusTitle) ?></h3><p id="mm-state-description"><?= e($statusDescription) ?></p></div>
<p class="cx-alert" id="mm-restriction" <?= !$restricted?'hidden':'' ?>>Sua conta possui uma restrição competitiva. <a href="/punishments">Consultar punições</a></p><p class="cx-alert" id="mm-no-season" <?= $season?'hidden':'' ?>>Aguardando abertura de temporada.</p>
<div class="queue16-action"><p class="mm-wait-label"><?= faIcon('clock') ?><span id="mm-wait"><?= $match?'Você tem uma partida ativa':'Aguardando dez jogadores reais' ?></span><span id="mm-personal-wait" data-mm-wait-start="<?= e($myQueued['joined_at']??'') ?>"></span></p>
            <div class="mm-queue-actions">
              <form action="/matchmaking/join" method="POST" id="mm-join-form" class="<?= !$player ? 'mm-first-entry' : '' ?>" <?= $isInQueue || $match ? 'hidden' : '' ?>>
                <?= csrfField() ?>
                <?php if (!$player && isAdmin()) { ?>
                  <div><div class="ui-field"><label class="ui-field-label" for="mm-rank">Seu rank atual</label><select id="mm-rank" name="r6_rank" required><option value="">Selecione seu rank</option><?php foreach (matchmakingRanks() as $rank) { ?><option value="<?= e($rank) ?>"><?= e($rank) ?></option><?php } ?></select></div></div>
                <?php } ?>
                <button type="submit" class="mm-button mm-button-primary" id="mm-join" <?= $queueCount >= $queueTarget || $state['queue_busy'] || $match || $restricted || !$season ? 'disabled' : '' ?>><?= faIcon('play') ?><span><?= $queueCount >= $queueTarget ? 'Fila completa' : 'Entrar na fila' ?></span></button>
              </form>
              <form action="/matchmaking/leave" method="POST" id="mm-leave-form" <?= !$isInQueue ? 'hidden' : '' ?>>
                <?= csrfField() ?>
                <button type="submit" class="mm-button mm-button-subtle" id="mm-leave"><?= faIcon('right-from-bracket') ?><span>Sair da fila</span></button>
              </form>
</div>
</div>
</section>

<div class="queue16-tools"><span class="mm-live"><i class="mm-status-dot"></i><span id="mm-live-label">Ao vivo</span></span><button class="mm-text-button" id="mm-refresh" type="button"><?= faIcon('rotate') ?> Atualizar</button></div>
          <div class="mm-card mm-sound">
            <div class="mm-section-label"><?= faIcon('volume-high') ?><h2>Não perca a chamada</h2></div>
            <p>Ative o som para ouvir o aviso quando a fila completar ou uma partida for atribuída a você.</p>
            <div class="mm-sound-actions">
              <button type="button" class="mm-button mm-button-subtle" id="mm-sound-toggle" aria-pressed="false" aria-describedby="mm-sound-status"><span id="mm-sound-icon"><?= faIcon('volume-xmark') ?></span><span id="mm-sound-label">Ativar som</span></button>
              <button type="button" class="mm-text-button" id="mm-sound-test"><?= faIcon('play') ?><span>Testar aviso</span></button>
            </div>
            <span id="mm-sound-status" class="mm-muted">Som desativado</span>
          </div>

</div>
<aside class="mm-secondary season16" aria-label="Temporada e seu desempenho">
<section class="mm-card season16-card"><div class="season16-heading"><?= faIcon('trophy') ?><span>Temporada atual</span><span class="mm-tag" id="mm-season-badge" <?= $season?'':'hidden' ?>>Ativa</span></div><h2 id="mm-season-name"><?= e($season['name']??'Aguardando temporada') ?></h2><p id="mm-season-dates"><?= $season?e(brazilDate($season['starts_at'],'d/m/Y').' — '.brazilDate($season['ends_at'],'d/m/Y')):'Nenhum ciclo ativo' ?></p>
<div class="player16-summary"><div class="mm-avatar"><?= e(mb_strtoupper(mb_substr($nickname,0,1))) ?></div><div><strong><?= e($nickname) ?></strong><p><?= e($player['r6_rank']??'Selecione seu rank na entrada') ?></p><div class="mm-profile-status"><span class="mm-status-dot" data-mm-in-queue="false"></span><span id="mm-personal-status"><?= $match?'Em uma partida':'Fora da fila' ?></span></div></div></div>
<div class="mm-stats stats16"><?php foreach(['points'=>'Pontos','matches_played'=>'Partidas','wins'=>'Vitórias','losses'=>'Derrotas'] as $key=>$label): ?><div><span><?= e($label) ?></span><strong data-mm-stat="<?= e($key) ?>"><?= (int)$stats[$key] ?></strong></div><?php endforeach; ?></div><p class="season16-note"><?= faIcon('shield') ?> Simulações não alteram seus pontos.</p></section>
<section class="mm-card recent16"><div class="section16-heading"><h2>Últimas partidas</h2><a href="/perfil">Ver todas <?= faIcon('arrow-right') ?></a></div><?php $recentMatches=array_slice($profileSummary['history'],0,3);foreach($recentMatches as $recentMatch): ?><a class="recent16-row" href="/partida/<?= (int)$recentMatch['id'] ?>"><span><?= e($recentMatch['match_code']) ?><small><?= e($recentMatch['map_name']??'Mapa a definir') ?></small></span><span><?= e(matchStatusLabels()[$recentMatch['status']]??'Status não disponível') ?></span></a><?php endforeach; ?><?php if(!$recentMatches): ?><p class="recent16-empty">Seu histórico começa na próxima partida.</p><?php endif; ?></section>
<section class="rules16"><p>COMO FUNCIONA</p><ol><li><span>01</span><div><strong>Complete o lobby</strong><small><?= 'Dez jogadores reais completam o lobby.' ?></small></div></li><li><span>02</span><div><strong>Confirme e vote</strong><small>Sua confirmação e seu voto são manuais.</small></div></li><li><span>03</span><div><strong>Defina a disputa</strong><small>Draft 5v5, veto BO1 e escolha de lados.</small></div></li></ol></section>
</aside></div>

<p class="mm-update-note" id="mm-update-note">Estado sincronizado a cada 4 segundos.</p><p class="sr-only" id="mm-announcement" role="status" aria-live="polite"></p>
</div></section>
<template id="mm-empty-slot"><li class="mm-slot"><span class="mm-slot-number"></span><span class="mm-slot-avatar"><?= faIcon('users') ?></span><div class="mm-slot-copy"><span class="mm-slot-name">Vaga disponível</span><span class="mm-slot-rank">Aguardando jogador</span><span class="mm-slot-wait" data-mm-wait-start=""></span></div><span class="mm-self-label" hidden>Você</span><img class="mm-slot-rank-image" alt="" width="26" height="26" hidden loading="lazy" decoding="async"></li></template>
<template id="mm-volume-on"><?= faIcon('volume-high') ?></template>
<template id="mm-volume-off"><?= faIcon('volume-xmark') ?></template>
<script type="application/json" id="mm-initial-state"><?= json_encode($state, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<script src="<?= e(assetUrl('/assets/js/matchmaking.js')) ?>" defer></script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
