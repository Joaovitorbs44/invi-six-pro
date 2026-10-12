<?php
$adminLinks=['/admin'=>'Solicitações e usuários','/admin/support'=>'Suporte','/admin/matches'=>'Partidas e disputas','/admin/punishments'=>'Punições'];
if(isAdmin())$adminLinks['/admin/seasons']='Temporadas';
?>
<nav class="admin19-nav site-container" aria-label="Áreas administrativas">
<?php foreach($adminLinks as $href=>$label): ?><a href="<?= e($href) ?>" <?= $headerPath===$href?'aria-current="page"':'' ?>><?= e($label) ?></a><?php endforeach; ?>
</nav>
