<?php
declare(strict_types=1);
require_once __DIR__.'/../components/head.php';requireStaff();
require_once __DIR__.'/../includes/punishments.php';
if(isPost()){verifyCsrf();try{punishmentAction($pdo,(int)userId(),$_POST);flash('success','Punição atualizada.');}catch(Throwable $error){flash('warning',$error instanceof RuntimeException&&!($error instanceof PDOException)?$error->getMessage():'Não foi possível atualizar a punição.');}redirect('/admin/punishments');}
$search=mb_substr(trim(is_string($_GET['q']??null)?$_GET['q']:''),0,100);$rows=punishmentHistory($pdo,null,$search);
$users=$usersPdo->query("SELECT id,r6_nickname FROM users WHERE role='player' ORDER BY r6_nickname")->fetchAll(PDO::FETCH_ASSOC);$labels=['warning'=>'Advertência','suspension'=>'Suspensão','ban'=>'Banimento'];$pageTitle='Gerenciar punições';require __DIR__.'/../components/header.php';
$staffView=true;
?>
<link rel="stylesheet" href="/assets/css/competitive.css">
<section class="cx-shell cx-workspace" aria-labelledby="punishments-title">
    <div class="cx-heading"><div><p class="cx-eyebrow">Administração</p><h1 id="punishments-title">Punições</h1><p>Proteja a competição. Registre decisões e acompanhe os prazos.</p></div><a class="cx-button" href="/admin/support">Central de atendimento <?= faIcon('arrow-right') ?></a></div>
    <div class="cx-punishment-layout">
        <section class="cx-card cx-punishment-form"><div class="cx-section-heading"><span class="cx-caption">Moderação</span><h2>Aplicar punição</h2><p class="cx-muted">O motivo será exibido ao jogador.</p></div>
            <form method="post" class="cx-form" data-punishment-form>
                <?= csrfField() ?><input type="hidden" name="action" value="create">
                <div class="ui-field"><label class="ui-field-label" for="punishment-player">Jogador</label><select id="punishment-player" name="user_id" required><option value="">Selecione um jogador</option><?php foreach($users as $user): ?><option value="<?= (int)$user['id'] ?>"><?= e($user['r6_nickname']) ?> (#<?= (int)$user['id'] ?>)</option><?php endforeach; ?></select></div>
                <div class="ui-field"><label class="ui-field-label" for="punishment-type">Tipo</label><select id="punishment-type" name="type"><?php foreach($labels as $key=>$label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
                <div class="ui-field"><label class="ui-field-label" for="punishment-reason">Motivo visível ao jogador</label><textarea id="punishment-reason" name="reason" minlength="3" maxlength="1000" required rows="3" placeholder="Descreva o motivo da decisão"></textarea></div>
                <div class="ui-field"><label class="ui-field-label" for="punishment-notes">Observação interna da staff</label><textarea id="punishment-notes" name="internal_notes" maxlength="3000" rows="2" placeholder="Contexto reservado à equipe" aria-describedby="punishment-privacy-note"></textarea><span class="cx-private-label" id="punishment-privacy-note"><?= faIcon('shield-halved') ?> Esta informação não é mostrada ao jogador.</span></div>
                <div data-punishment-duration><div class="ui-field"><label class="ui-field-label" for="punishment-hours">Duração em horas</label><input id="punishment-hours" name="hours" type="number" min="1" max="87600" value="24"></div><label class="cx-checkbox" for="punishment-permanent"><input id="punishment-permanent" name="permanent" type="checkbox" value="1"> Permanente</label></div>
                <p class="cx-muted" data-warning-note>Advertências registram o motivo sem restringir o acesso competitivo.</p>
                <button class="cx-danger cx-button" type="submit">Aplicar punição</button>
            </form>
        </section>
        <section class="cx-punishment-history" aria-labelledby="punishment-history-title"><div class="cx-heading"><div><p class="cx-eyebrow">Histórico</p><h2 id="punishment-history-title">Punições recentes</h2></div><span class="cx-muted"><?= count($rows) ?> registros</span></div>
            <form method="get" class="cx-filters" role="search"><div class="ui-field"><label class="ui-field-label" for="punishment-search">Buscar jogador</label><input id="punishment-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Nickname ou ID do jogador" maxlength="100"></div><button class="cx-button">Buscar</button><?php if($search!==''): ?><a class="cx-link" href="/admin/punishments">Limpar</a><?php endif; ?></form>
            <div class="cx-list"><?php foreach($rows as $row): require __DIR__.'/../components/punishment-card.php'; endforeach; ?>
            <?php if(!$rows): ?><div class="cx-empty cx-empty-panel"><span class="cx-empty-icon"><?= faIcon('shield-halved') ?></span><h3><?= $search!==''?'Nenhuma punição encontrada':'Nenhuma punição registrada' ?></h3><p><?= $search!==''?'Tente outro nickname ou ID.':'As decisões da staff aparecerão neste histórico.' ?></p></div><?php endif; ?></div>
        </section>
    </div>
    <dialog class="cx-confirm-dialog" data-punishment-confirm aria-labelledby="punishment-confirm-title"><p class="cx-eyebrow">Confirmação da staff</p><h2 id="punishment-confirm-title">Confirmar punição</h2><p class="cx-muted" data-punishment-confirm-copy></p><div class="cx-actions"><button class="cx-button" type="button" data-punishment-cancel>Revisar</button><button class="cx-danger cx-button" type="button" data-punishment-apply>Confirmar e aplicar</button></div></dialog>
</section>
<script src="/assets/js/punishments.js" defer></script>
<?php require __DIR__.'/../components/footer.php'; ?>
