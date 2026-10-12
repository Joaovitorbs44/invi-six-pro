<?php
declare(strict_types=1);
/** Display only the public player identity fields, never serialize source rows. */
function playerPopover(array $players, string $label): void
{
    static $sequence = 0;
    $id = 'players-popover-' . ++$sequence;
    if (!$players) { echo '<span class="player-count"><strong>0</strong> ' . e($label) . '</span>'; return; }
    ?>
    <details class="player-popover" data-player-popover>
      <summary class="player-count" aria-controls="<?= $id ?>"><strong><?= count($players) ?></strong> <?= e($label) ?></summary>
      <div class="player-panel" id="<?= $id ?>" data-player-panel role="region" aria-label="<?= e($label) ?>" tabindex="-1">
        <p class="player-panel-title"><?= e($label) ?></p>
        <ul class="player-list">
          <?php foreach ($players as $publicPlayer): ?><li class="player-row"><span class="player-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($publicPlayer['r6_nickname'],0,1))) ?></span><div><strong><?= e($publicPlayer['r6_nickname']) ?></strong><?php if (!empty($publicPlayer['r6_rank'])): ?><small><?= e($publicPlayer['r6_rank']) ?></small><?php endif; ?></div></li><?php endforeach; ?>
        </ul>
      </div>
    </details>
    <?php
}
