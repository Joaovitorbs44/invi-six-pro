<?php
declare(strict_types=1);
// Presentation-only reads; do not infer online presence from registered accounts.
require_once __DIR__ . '/player-popover.php';
$communityMembers = accountsDatabase($pdo)->query("SELECT p.r6_nickname,p.r6_rank FROM players p JOIN users u ON u.id=p.user_id WHERE u.status='active' AND u.role IN('player','admin') ORDER BY p.r6_nickname")->fetchAll();
$communitySeason = $pdo->query("SELECT name FROM seasons WHERE status='active' AND starts_at<=CURRENT_TIMESTAMP AND ends_at>CURRENT_TIMESTAMP ORDER BY id DESC LIMIT 1")->fetch();
$communityLinks = [];
if (!$headerLoggedIn || $headerRole !== 'staff') $communityLinks['/matchmaking'] = 'Matchmaking';
$communityLinks['/ranking'] = 'Ranking';
?>
<div class="community-shell site-container">
  <section class="community-heading" aria-labelledby="community-name">
    <img class="community-logo" src="/assets/images/logo-invisix.svg" alt="" decoding="async">
    <div class="community-copy"><p class="community-eyebrow">Comunidade competitiva · Rainbow Six Siege</p><h2 id="community-name">INVI SIX PRO</h2><p>Seu espaço para competir, evoluir e encontrar o próximo lobby.</p></div>
    <div class="community-meta"><?php playerPopover($communityMembers, 'jogadores cadastrados'); ?><span><?= e($communitySeason['name'] ?? 'Sem temporada ativa') ?></span><span class="community-status"><?= $communitySeason ? 'Temporada ativa' : 'Aguardando temporada' ?></span></div>
  </section>
  <nav class="community-nav" aria-label="Navegação competitiva">
    <?php foreach ($communityLinks as $communityHref => $communityLabel): ?><a href="<?= e($communityHref) ?>" <?= $headerPath === $communityHref ? 'aria-current="page"' : '' ?>><?= e($communityLabel) ?></a><?php endforeach; ?>
  </nav>
</div>
