'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('#login-form, #register-form');
  if (!form) return;
  const kind = form.id === 'login-form' ? 'login' : 'register';
  const page = form.closest('.auth-page');
  const steps = [...form.querySelectorAll('[data-' + kind + '-step]')];
  const fields = step => [...step.querySelectorAll('input,select,textarea')];
  const byId = id => document.getElementById(id);
  const nickname = byId('r6_nickname');
  const submit = byId(kind + '-submit');
  const submitLabel = submit.textContent;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  let current = 1, busy = false, motion;
  // Measure both visible steps before progressive enhancement hides either one.
  form.style.minHeight = Math.ceil(Math.max(...steps.map(step => step.getBoundingClientRect().height))) + 'px';
  page.classList.add('auth-enhanced');
  form.noValidate = true;
  function render(number, animate = true, focus = true) {
    const direction = number >= current ? 1 : -1;
    current = number;
    motion?.cancel();
    steps.forEach((step, index) => { step.hidden = index + 1 !== current; });
    const active = steps[current - 1];
    byId(kind + '-step-counter').textContent = current + ' de 2';
    const titles = kind === 'login' ? ['Identifique sua conta', 'Digite sua senha'] : ['Identifique-se', 'Seu perfil no R6'];
    const descriptions = kind === 'login' ? ['Informe seu nick no Rainbow Six Siege.', 'Use a senha que você criou no primeiro acesso.'] : ['Informe seu nome e seu nick no Rainbow Six Siege.', 'Informe seu R6 Tracker e seu rank atual.'];
    byId(kind + '-step-title').textContent = titles[current - 1];
    byId(kind + '-step-description').textContent = descriptions[current - 1];
    byId(kind + '-player-name').textContent = nickname.value.trim();
    for (const type of ['progress', 'label']) {
      document.querySelectorAll('[data-' + kind + '-' + type + ']').forEach((item, index) => {
        const selected = index + 1 <= current;
        const activeClass = type === 'progress' ? 'bg-[#FF3D2C]' : 'text-[#FF3D2C]';
        const inactiveClass = type === 'progress' ? 'bg-border' : 'text-muted';
        item.classList.toggle(activeClass, selected); item.classList.toggle(inactiveClass, !selected);
      });
    }
    if (animate && !reduced.matches && active.animate) {
      try { motion = active.animate([{opacity:0,transform:'translateX(' + direction * 10 + 'px)'},{opacity:1,transform:'translateX(0)'}], {duration:350,easing:'cubic-bezier(.22,1,.36,1)'}); } catch {}
    }
    if (focus) fields(active)[0]?.focus({preventScroll:true});
  }
  function valid(step) {
    for (const field of fields(step)) {
      if (!field.checkValidity()) { field.focus(); field.reportValidity(); return false; }
    }
    return true;
  }
  byId(kind + '-next').addEventListener('click', () => { if (!busy && valid(steps[0])) render(2); });
  byId(kind + '-back').addEventListener('click', () => { if (!busy) render(1); });
  byId(kind === 'login' ? 'change-player' : 'change-register-player').addEventListener('click', () => { if (!busy) render(1); });
  steps[0].addEventListener('keydown', event => {
    if (event.key === 'Enter' && event.target.matches('input') && !event.isComposing) {
      event.preventDefault(); byId(kind + '-next').click();
    }
  });
  const toggle = byId('toggle-password');
  toggle?.addEventListener('click', () => {
    const password = byId('password'), show = password.type === 'password';
    password.type = show ? 'text' : 'password'; toggle.textContent = show ? 'Ocultar' : 'Mostrar';
    toggle.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
    toggle.setAttribute('aria-pressed', String(show));
  });
  form.addEventListener('submit', event => {
    if (busy) { event.preventDefault(); return; }
    for (let index = 0; index < steps.length; index++) {
      const invalid = fields(steps[index]).find(field => !field.validity.valid);
      if (invalid) { event.preventDefault(); render(index + 1, false, false); invalid.focus(); invalid.reportValidity(); return; }
    }
    busy = true; form.setAttribute('aria-busy', 'true'); submit.disabled = true;
    submit.textContent = kind === 'login' ? 'Entrando…' : 'Enviando solicitação…';
  });
  window.addEventListener('pageshow', event => {
    if (!event.persisted) return;
    busy = false; submit.disabled = false; submit.textContent = submitLabel;
    form.removeAttribute('aria-busy'); render(current, false, false);
  });
  reduced.addEventListener('change', () => motion?.cancel());
  render(1, false, false);
});
