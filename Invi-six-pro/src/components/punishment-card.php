<?php
// Both views use the same public fields. Internal notes are rendered only in staff mode.
$status=punishmentStatus($row);
$duration=$row['type']==='warning'?'Sem restrição de acesso':($row['expires_at']?round((strtotime($row['expires_at'].' UTC')-strtotime($row['created_at'].' UTC'))/3600,1).' horas':'Permanente');
?>
<article class="cx-punishment-card">
    <div class="cx-record-heading"><div><p class="cx-caption"><?= $staffView?e($row['nickname']).' · #'.(int)($row['user_id']??0):'Registro #'.(int)$row['id'] ?></p><h3><?= e($labels[$row['type']]) ?></h3></div><span class="cx-badge <?= $status!=='Encerrada'&&$row['type']!=='warning'?'cx-badge-danger':'' ?>"><?= e($status) ?></span></div>
    <p class="cx-message-text"><?= e($row['reason']) ?></p>
    <p class="cx-muted">Por <?= e($row['staff_name']) ?> · <?= e(brazilDate($row['created_at'])) ?> (São Paulo)</p>
    <p class="cx-muted"><?= e($duration) ?><?= $row['expires_at']?' · Término: '.e(brazilDate($row['expires_at'])).' (São Paulo)':'' ?></p>
    <?php if($staffView&&$row['internal_notes']): ?><details class="cx-internal-note"><summary>Observação interna · Somente staff</summary><p class="cx-message-text"><?= e($row['internal_notes']) ?></p></details><?php endif; ?>
    <?php if($staffView&&!$row['revoked_at']): ?><form method="post" class="cx-record-action"><?= csrfField() ?><input type="hidden" name="action" value="revoke"><input type="hidden" name="punishment_id" value="<?= (int)$row['id'] ?>"><button class="cx-button" type="submit">Revogar punição</button></form><?php endif; ?>
</article>
