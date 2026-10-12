<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';requireAuth();
require_once __DIR__.'/../includes/punishments.php';
// Intentionally select only the public fields; internal staff notes never leave this query.
$rows=punishmentHistory($pdo,(int)userId());
$labels=['warning'=>'Advertência','suspension'=>'Suspensão','ban'=>'Banimento'];$pageTitle='Minhas punições';require __DIR__.'/../components/header.php';
$staffView=false;
?>
<link rel="stylesheet" href="<?= e(assetUrl('/assets/css/competitive.css')) ?>">
<section class="site-container cx-shell cx-workspace" aria-labelledby="punishments-title">
    <div class="cx-heading"><div><p class="cx-eyebrow">Minha conta</p><h1 id="punishments-title">Punições</h1><p>Consulte as decisões da staff e o prazo de cada registro.</p></div><a class="cx-button" href="/support">Falar com o suporte <?= faIcon('arrow-right') ?></a></div>
    <?php if(competitiveRestriction($pdo,(int)userId())): ?><p class="cx-alert">Sua conta possui uma restrição competitiva ativa. A entrada na fila está bloqueada.</p><?php endif; ?>
    <div class="cx-list"><?php foreach($rows as $row): require __DIR__.'/../components/punishment-card.php'; endforeach; ?>
    <?php if(!$rows): ?><div class="cx-empty cx-empty-panel"><span class="cx-empty-icon"><?= faIcon('shield-halved') ?></span><h2>Nenhuma punição registrada</h2><p>Você pode acompanhar aqui qualquer decisão referente à sua conta.</p><a class="cx-button" href="/matchmaking">Ir para o matchmaking</a></div><?php endif; ?></div>
</section>
<?php require __DIR__.'/../components/footer.php'; ?>
