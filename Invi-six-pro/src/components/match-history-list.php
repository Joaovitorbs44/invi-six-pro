<div class="match-history18" aria-label="Histórico de partidas">
 <?php foreach($historyRows as $historyMatch):require __DIR__.'/match-history-card.php';endforeach; ?>
 <?php if(!$historyRows): ?><p class="match-history18-empty">Nenhuma partida encontrada<?= !empty($status)?' com este filtro':'' ?>.</p><?php endif; ?>
</div>
