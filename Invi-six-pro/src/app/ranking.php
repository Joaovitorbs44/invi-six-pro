<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';
require_once __DIR__.'/../includes/profile.php';
$season=competitiveSeason($pdo);
if(isset($_GET['season'])&&is_string($_GET['season'])&&ctype_digit($_GET['season'])){
 $requested=$pdo->prepare("SELECT id,name,starts_at,ends_at,status FROM seasons WHERE id=? AND status='finished'");$requested->execute([(int)$_GET['season']]);
 $archived=$requested->fetch();if($archived)$season=$archived;
}
$ranking=competitiveRanking($pdo,$season['id']??null,PHP_INT_MAX);
$historical=($season['status']??'')==='finished';
$search=mb_substr(trim(is_string($_GET['q']??null)?$_GET['q']:''),0,100);
$metrics=$season?seasonMetrics($pdo,(int)$season['id'],!$historical):['players'=>0,'matches'=>0];
$days=$season&&!$historical?max(0,(int)ceil((strtotime($season['ends_at'].' UTC')-time())/86400)):0;
$rankFilter=is_string($_GET['rank']??null)&&in_array($_GET['rank'],matchmakingRanks(),true)?$_GET['rank']:'';
$activityFilter=is_string($_GET['activity']??null)&&in_array($_GET['activity'],['played','unplayed'],true)?$_GET['activity']:'';
$pageTitle='Classificação · Lobby R6';
require __DIR__.'/../components/header.php';
?>
<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/competitive.css')) ?>">
<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/standings.css')) ?>">
<section class="cx-shell cx-workspace standings" aria-labelledby="ranking-title">
    <div class="cx-heading">
        <div><p class="cx-eyebrow">Lobby R6 / Ranking competitivo</p><h1 id="ranking-title">CLASSIFICAÇÃO</h1><p><?= $historical?'Classificação final preservada':'Temporada atual · 30 dias · Pontos iniciais: 0' ?></p></div>
        <div class="standings-season"><strong><?= e($season['name']??'Sem temporada ativa') ?></strong><?php if($season): ?><span class="status18 status18-<?= ($season['status']??'active')==='finished'?'neutral':'success' ?>"><?= ($season['status']??'active')==='finished'?'Classificação final':'Ativa' ?></span><?php endif; ?></div>
    </div>
    <dl class="cx-summary" data-live-competitive>
        <div><dt>Jogadores</dt><dd><?php playerPopover($ranking, 'na temporada'); ?></dd></div>
        <div><dt>Partidas</dt><dd><?= $metrics['matches'] ?></dd></div>
        <div><dt>Tempo restante</dt><dd><?= $historical?'Encerrada':($season?$days.' dias':'--') ?></dd></div>
    </dl>
    <form method="get" class="cx-filters cx-ranking-search" role="search">
        <?php if(($season['status']??'')==='finished'): ?><input type="hidden" name="season" value="<?= (int)$season['id'] ?>"><?php endif; ?>
        
        <div class="ui-field"><label class="ui-field-label" for="ranking-search">Buscar jogador</label><input id="ranking-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Buscar jogador ou Twitch..." maxlength="100"></div>
        <div class="ui-field"><label class="ui-field-label" for="ranking-rank">Filtrar pelo rank R6 cadastrado</label><select id="ranking-rank" name="rank"><option value="">Todos os ranks</option><?php foreach(matchmakingRanks() as $rank): ?><option value="<?= e($rank) ?>" <?= $rankFilter===$rank?'selected':'' ?>><?= e($rank) ?></option><?php endforeach; ?></select></div>
        <div class="ui-field"><label class="ui-field-label" for="ranking-activity">Participação na temporada</label><select id="ranking-activity" name="activity"><option value="">Todos</option><option value="played" <?= $activityFilter==='played'?'selected':'' ?>>Com partidas</option><option value="unplayed" <?= $activityFilter==='unplayed'?'selected':'' ?>>Sem partidas</option></select></div>
        <button class="cx-button" type="submit">Filtrar</button>
        <?php if($search!==''||$rankFilter!==''||$activityFilter!==''): ?><a class="cx-link" href="/ranking<?= $historical?'?season='.(int)$season['id']:'' ?>">Limpar busca</a><?php endif; ?>
    </form>
    <div class="cx-list" aria-label="Classificação de jogadores" data-live-competitive>
    <?php $visible=0;foreach($ranking as $i=>$player):
        if($search!==''&&mb_stripos($player['r6_nickname'].' '.($player['twitch']??''),$search)===false)continue;
        $played=(int)$player['matches_played'];
        if($rankFilter!==''&&$player['r6_rank']!==$rankFilter)continue;
        if(($activityFilter==='played'&&$played===0)||($activityFilter==='unplayed'&&$played!==0))continue;
        $visible++;$winRate=$played?round(100*(int)$player['wins']/$played):0;
    ?>
        <article class="cx-ranking-card">
            <span class="cx-position <?= $i<3?'cx-podium-'.($i+1):'' ?>" aria-label="Posição <?= $i+1 ?>"><?= str_pad((string)($i+1),2,'0',STR_PAD_LEFT) ?></span>
            <div class="cx-ranking-identity">
                <span class="cx-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($player['r6_nickname'],0,1))) ?></span>
                <div><h2><?= e($player['r6_nickname']) ?></h2><?php if($player['twitch']): ?><a class="cx-muted" href="https://www.twitch.tv/<?= rawurlencode($player['twitch']) ?>" target="_blank" rel="noopener noreferrer">@<?= e($player['twitch']) ?> <?= faIcon('arrow-up-right-from-square', 'fa-sm') ?></a><?php endif; ?></div>
            </div>
            <div class="cx-ranking-rank"><img src="<?= e(matchmakingRankImage($player['r6_rank'])) ?>" alt="" width="32" height="32" loading="lazy" decoding="async"><div><span class="cx-caption">Rank R6</span><strong><?= e($player['r6_rank']) ?></strong></div></div>
            <dl class="cx-ranking-stats" aria-label="Estatísticas da temporada"><div><dt><abbr title="Jogos">J</abbr><span class="standings-stat-label">Partidas</span></dt><dd><?= $played ?></dd></div><div><dt><abbr title="Vitórias">V</abbr><span class="standings-stat-label">Vitórias</span></dt><dd><?= (int)$player['wins'] ?></dd></div><div><dt><abbr title="Derrotas">D</abbr><span class="standings-stat-label">Derrotas</span></dt><dd><?= (int)$player['losses'] ?></dd></div><div><dt><abbr title="Win rate: percentual de vitórias">WR</abbr><span class="standings-stat-label">WR</span></dt><dd><?= $winRate ?>%</dd></div></dl>
            <div class="cx-ranking-points"><span>PTS</span><strong><?= (int)$player['points'] ?></strong></div>
        </article>
    <?php endforeach; ?>
    <?php if(!$visible): ?><div class="cx-empty cx-empty-panel"><span class="cx-empty-icon"><?= faIcon('trophy') ?></span><h2><?= $ranking?'Nenhum jogador encontrado':'Nenhum jogador classificado nesta temporada.' ?></h2><p><?= $ranking?'Experimente outro nickname ou ajuste os filtros.':'A classificação aparecerá quando houver uma temporada e jogadores disponíveis.' ?></p><?php if($search!==''||$rankFilter!==''||$activityFilter!==''): ?><a class="cx-button" href="/ranking">Ver todos os jogadores</a><?php endif; ?></div><?php endif; ?>
    </div>
    <p class="cx-footnote">Todos começam com 0 pontos. O rank R6 permanece fixo. Desempate por vitórias, menos partidas e ordem de cadastro.</p>
</section>
<?php require __DIR__.'/../components/footer.php'; ?>
