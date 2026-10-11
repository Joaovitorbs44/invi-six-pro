<?php

declare(strict_types=1);

$toastSuccess = flash('success');
$toastError = flash('error');
$toastWarning = flash('warning');
$toastInfo = flash('info');

?>

  <?php
  $toastTypes = [
    'success' => ['message' => $toastSuccess, 'title' => 'Sucesso', 'color' => 'bg-green-50 text-green-600', 'bar' => 'bg-green-500', 'duration' => 4500],
    'error' => ['message' => $toastError, 'title' => 'Atenção', 'color' => 'bg-red-50 text-red-600', 'bar' => 'bg-red-500', 'duration' => 5500],
    'warning' => ['message' => $toastWarning, 'title' => 'Aviso', 'color' => 'bg-amber-50 text-amber-600', 'bar' => 'bg-amber-500', 'duration' => 5000],
    'info' => ['message' => $toastInfo, 'title' => 'Informação', 'color' => 'bg-blue-50 text-blue-600', 'bar' => 'bg-blue-500', 'duration' => 4500],
  ];
  ?>

  <div id="toast-container"
    class="pointer-events-none fixed right-5 top-5 z-[9999] flex w-[360px] max-w-[calc(100vw-40px)] flex-col gap-3"
    aria-live="polite" aria-relevant="additions">
    <?php foreach ($toastTypes as $type => $toast) { ?>
      <?php ob_start(); ?>
      <div data-toast data-toast-type="<?= e($type) ?>" data-toast-duration="<?= $toast['duration'] ?>"
        class="pointer-events-auto translate-x-5 rounded-lg border border-neutral-200 bg-white p-4 opacity-0 shadow-sm transition-all duration-300 motion-reduce:transition-none"
        role="<?= $type === 'error' ? 'alert' : 'status' ?>" aria-atomic="true">
        <div class="flex items-start gap-3">
          <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full <?= $toast['color'] ?>">
            <?= faIcon(match ($type) {'success'=>'check','error','warning'=>'triangle-exclamation',default=>'circle-info'}) ?>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-neutral-950"><?= e($toast['title']) ?></p>
            <p data-toast-message class="mt-1 break-words text-xs leading-5 text-neutral-500"><?= e($toast['message']) ?></p>
          </div>
          <button type="button" data-toast-close
            class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-neutral-400 transition hover:bg-neutral-100 hover:text-black"
            aria-label="Fechar notificação"><?= faIcon('xmark', 'fa-sm') ?></button>
        </div>
        <div class="mt-3 h-[2px] overflow-hidden rounded-full bg-neutral-100" aria-hidden="true">
          <div data-toast-progress class="h-full w-full origin-left <?= $toast['bar'] ?>"></div>
        </div>
      </div>
      <?php $toastMarkup = ob_get_clean(); ?>
      <template data-toast-template="<?= e($type) ?>"><?= $toastMarkup ?></template>
      <?php if ($toast['message'] !== null) { echo $toastMarkup; } ?>
    <?php } ?>
  </div>

  <script nonce="<?= cspNonce() ?>">
    document.addEventListener('DOMContentLoaded', () => {
      const container = document.getElementById('toast-container');
      if (!container) return;
      if (container.parentElement !== document.body) document.body.appendChild(container);
      const closers = new WeakMap();

      const initializeToast = (toast, index = 0) => {
        const duration = Number(toast.dataset.toastDuration) || 5000;
        const progress = toast.querySelector('[data-toast-progress]');
        const closeButton = toast.querySelector('[data-toast-close]');
        let closed = false;
        let timeout;
        let entryTimeout;
        let removalTimeout;
        toast.dataset.motion = 'visible';

        const close = (immediate = false) => {
          if (immediate === true) {
            closed = true;
            window.clearTimeout(timeout); window.clearTimeout(entryTimeout); window.clearTimeout(removalTimeout);
            toast.remove(); return;
          }
          if (closed) return;
          closed = true;
          window.clearTimeout(timeout);
          window.clearTimeout(entryTimeout);
          toast.dataset.motion = 'closing';
          removalTimeout = window.setTimeout(() => toast.remove(), window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 160);
        };

        closers.set(toast, close);
        closeButton?.addEventListener('click', () => close());
        entryTimeout = window.setTimeout(() => {
          if (closed) return;
          toast.dataset.motion = 'visible';
          if (progress) {
            progress.style.transition = 'transform ' + duration + 'ms linear';
            window.requestAnimationFrame(() => {
              window.requestAnimationFrame(() => {
                if (!closed) progress.style.transform = 'scaleX(0)';
              });
            });
          }
          timeout = window.setTimeout(close, duration);
        }, 60 + index * 80);
      };

      const initial = [...container.querySelectorAll('[data-toast]')];
      initial.slice(0,-3).forEach(toast => toast.remove());
      initial.slice(-3).forEach(initializeToast);

      // Validações no navegador usam os mesmos cards das mensagens PHP.
      window.showToast = (type, message) => {
        if ([...container.querySelectorAll('[data-toast]')].some(item => item.dataset.motion !== 'closing' && item.dataset.toastType === type && item.querySelector('[data-toast-message]')?.textContent === String(message))) return;
        const template = Array.from(container.querySelectorAll('[data-toast-template]'))
          .find((item) => item.dataset.toastTemplate === type);
        if (!template) return;
        while (container.querySelectorAll('[data-toast]').length >= 3) {
          const oldest = container.querySelector('[data-toast]');
          const close = closers.get(oldest);
          if (close) close(true); else oldest.remove();
        }
        const toast = template.content.firstElementChild.cloneNode(true);
        toast.querySelector('[data-toast-message]').textContent = String(message);
        container.appendChild(toast);
        initializeToast(toast);
      };

      let validationPending = false;
      document.addEventListener('invalid', (event) => {
        if (!(event.target instanceof HTMLInputElement ||
              event.target instanceof HTMLSelectElement ||
              event.target instanceof HTMLTextAreaElement)) return;
        event.preventDefault();
        if (validationPending) return;
        validationPending = true;
        window.showToast('warning', event.target.validationMessage);
        if (event.target.getClientRects().length) event.target.focus();
        window.setTimeout(() => { validationPending = false; }, 0);
      }, true);
    });
  </script>

