<?php
declare(strict_types=1);
// Text identities of the organizations cited by the existing landing page.
$proTeams = [
    ['name'=>'FaZe Clan','initials'=>'FC','logo'=>null],
    ['name'=>'FURIA','initials'=>'FU','logo'=>null],
    ['name'=>'Team Liquid','initials'=>'TL','logo'=>null],
    ['name'=>'Black Dragons','initials'=>'BD','logo'=>null],
    ['name'=>'W7M','initials'=>'W7','logo'=>null],
    ['name'=>'Team oNe','initials'=>'TO','logo'=>null],
];
?>
<section class="pro-section" aria-labelledby="pro-title" data-pro-carousel>
  <div class="pro-heading"><div><p class="pro-eyebrow">Mentalidade competitiva</p><h2 id="pro-title">JOGUE COMO OS <span>PROS</span></h2><p>Entre no competitivo e dispute cada partida com a mesma mentalidade dos profissionais.</p></div>
    <button type="button" class="pro-motion-button" data-pro-toggle aria-pressed="false" hidden><span data-pro-pause><?= faIcon('pause') ?></span><span data-pro-play hidden><?= faIcon('play') ?></span><span data-pro-label>Pausar movimento</span></button>
  </div>
  <div class="pro-viewport" data-pro-viewport tabindex="0" role="region" aria-label="Organizações de referência. Faixa rolável horizontalmente.">
    <div class="pro-track" data-pro-track><ul class="pro-group" data-pro-group>
      <?php foreach($proTeams as $team): ?><li class="pro-team">
        <?php if($team['logo']): ?><img src="<?= e($team['logo']) ?>" width="40" height="40" alt=""><?php else: ?><span class="pro-logo-placeholder" aria-hidden="true"><?= e($team['initials']) ?></span><?php endif; ?>
        <span><strong><?= e($team['name']) ?></strong><small>Referência competitiva</small></span>
      </li><?php endforeach; ?>
    </ul></div>
  </div>
  <p class="pro-disclaimer">Organizações apresentadas como referência competitiva. Não há vínculo ou parceria com o INVI SIX PRO.</p>
</section>
<script src="/assets/js/pro-carousel.js" defer></script>
