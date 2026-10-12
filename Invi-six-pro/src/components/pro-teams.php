<?php
declare(strict_types=1);
// Local copies of confirmed logos. Sources are documented in docs/DESIGN-UPDATE.md.
$proTeams = [
    ['name'=>'Imperial Esports','logo'=>'imperial.png'],
    ['name'=>'INTZ','logo'=>'intz.png'],
    ['name'=>'Juventus','logo'=>'juventus.png'],
    ['name'=>'LOS','logo'=>'los.png'],
    ['name'=>'LOUD','logo'=>'loud.png'],
    ['name'=>'MIBR','logo'=>'mibr.png'],
    ['name'=>'Mystic Esports','logo'=>'mystic.png'],
    ['name'=>'Ninjas in Pyjamas','logo'=>'nip.png'],
    ['name'=>'Razah Company','logo'=>'razah.png'],
];
?>
<section class="pro-section" aria-labelledby="pro-title" data-pro-carousel>
  <div class="site-container">
  <div class="pro-heading"><div><p class="pro-eyebrow">Mentalidade competitiva</p><h2 id="pro-title">JOGUE COM OS <span>PROS</span></h2><p>Inspire-se nas equipes profissionais e dispute cada partida com mentalidade competitiva.</p></div>
    <button type="button" class="pro-motion-button" data-pro-toggle aria-controls="pro-viewport" aria-pressed="false" hidden><span data-pro-pause><?= faIcon('pause') ?></span><span data-pro-play hidden><?= faIcon('play') ?></span><span data-pro-label>Pausar movimento</span></button>
  </div>
  <div id="pro-viewport" class="pro-viewport" data-pro-viewport tabindex="0" role="region" aria-label="Equipes profissionais. Use as setas para percorrer a faixa.">
    <div class="pro-track" data-pro-track><ul class="pro-group" data-pro-group>
      <?php foreach($proTeams as $team): ?><li class="pro-team">
        <img src="<?= e(assetUrl('/assets/images/teams/' . $team['logo'])) ?>" width="48" height="48" alt="" loading="lazy" decoding="async">
        <strong><?= e($team['name']) ?></strong>
      </li><?php endforeach; ?>
    </ul></div>
  </div>
  <p class="pro-disclaimer">Equipes apresentadas como referência competitiva, sem vínculo ou parceria com o INVI-SIX PRO.</p>
  </div>
</section>
<script src="<?= e(assetUrl('/assets/js/pro-carousel.js')) ?>" defer></script>
