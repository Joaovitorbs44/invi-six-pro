<?php
// $competitiveStep is a presentation index, never a source of match state.
$competitiveSteps=['Ready check','Capitães','Draft','Pick & Ban','Lados','Partida'];
?>
<nav class="arena-steps" aria-label="Etapas competitivas" tabindex="0">
 <ol><?php foreach($competitiveSteps as $index=>$label): ?>
  <li class="<?= $index===$competitiveStep?'is-active':($index<$competitiveStep?'is-completed':'is-pending') ?>" <?= $index===$competitiveStep?'aria-current="step"':'' ?>>
   <span><?= str_pad((string)($index+1),2,'0',STR_PAD_LEFT) ?></span><strong><?= e($label) ?></strong>
  </li>
 <?php endforeach; ?></ol>
</nav>
