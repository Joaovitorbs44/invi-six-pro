<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-6" aria-hidden="true">
    <div role="dialog" aria-modal="true" aria-labelledby="delete-title" class="w-full max-w-[420px] rounded-lg border border-border bg-surface p-6">
        <p id="delete-label" class="text-[10px] font-medium uppercase tracking-wider text-[#FF3D2C]"></p>
        <h2 id="delete-title" class="mt-3 text-xl font-semibold tracking-tight text-foreground"></h2>
        <p class="mt-4 text-xs leading-5 text-muted">Tem certeza que deseja excluir <strong id="delete-player" class="font-medium text-foreground"></strong>?</p>
        <p id="delete-description" class="mt-3 text-xs leading-5 text-muted"></p>
        <form id="delete-form" method="POST" class="mt-6">
            <?= csrfField() ?>
            <input id="delete-id" type="hidden">
            <div class="grid grid-cols-2 gap-2">
                <button type="button" data-close-delete class="h-11 rounded-[4px] border border-border text-[10px] font-medium text-foreground hover:border-border">Cancelar</button>
                <button id="delete-submit" type="submit" class="h-11 rounded-[4px] bg-red-600 text-[10px] font-medium text-white hover:bg-red-700"></button>
            </div>
        </form>
    </div>
</div>
<script src="<?= e(assetUrl('/assets/js/admin-delete.js')) ?>" defer></script>
