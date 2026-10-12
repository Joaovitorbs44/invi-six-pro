'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const records = [...document.querySelectorAll('[data-player-popover]')].map(details => {
    const trigger = details.querySelector('summary'), panel = details.querySelector('[data-player-panel]');
    panel.tabIndex = 0;
    details.dataset.popoverEnhanced = 'true';
    let timer, finish, origin = trigger;
    const clear = () => {window.clearTimeout(timer);window.clearTimeout(finish);delete panel.dataset.closing;};
    function position() {
      if (!details.open) return;
      const gutter = 12, gap = 8, rect = origin.getBoundingClientRect();
      const width = Math.min(260, Math.max(0, window.innerWidth - gutter * 2));
      panel.style.width = width + 'px';
      panel.style.left = Math.max(gutter, Math.min(rect.right - width, window.innerWidth - width - gutter)) + 'px';
      const below = window.innerHeight - rect.bottom - gap - gutter, above = rect.top - gap - gutter;
      const available = Math.max(0, Math.max(below, above));
      panel.style.maxHeight = Math.min(340, available) + 'px';
      const height = panel.getBoundingClientRect().height;
      const top = below >= height || below >= above ? rect.bottom + gap : rect.top - gap - height;
      panel.style.top = Math.max(gutter, Math.min(top, window.innerHeight - height - gutter)) + 'px';
    }
    function close(immediate = false, restore = false) {
      clear();
      if (!details.open) return;
      const end = () => {details.open = false;trigger.setAttribute('aria-expanded','false');delete panel.dataset.closing;if(restore)origin.focus({preventScroll:true});};
      if (immediate || reduced.matches) end();
      else {panel.dataset.closing = 'true';finish = window.setTimeout(end,120);}
    }
    function open(reference = trigger) {
      if (details.hidden) return;
      origin = reference;
      clear();
      records.forEach(record => {if(record.details !== details)record.close(true);});
      details.open = true; trigger.setAttribute('aria-expanded','true');position();
    }
    function leave(event) {
      if (event.pointerType && event.pointerType !== 'mouse') return;
      timer = window.setTimeout(() => {
        if (!details.contains(document.activeElement)) close();
      },140);
    }
    const enter = event => {if(event.pointerType === 'mouse')open();};
    trigger.addEventListener('pointerenter',enter);details.addEventListener('pointerleave',leave);
    panel.addEventListener('pointerenter',clear);panel.addEventListener('pointerleave',leave);
    trigger.addEventListener('click', () => {origin=trigger;clear();});
    details.addEventListener('toggle', () => {
      trigger.setAttribute('aria-expanded',String(details.open));
      if(details.open) {records.forEach(record => {if(record.details!==details)record.close(true);});position();}
    });
    trigger.addEventListener('keydown', event => {
      if(event.key==='ArrowDown') {event.preventDefault();open();panel.focus({preventScroll:true});}
    });
    details.addEventListener('focusin',clear);
    details.addEventListener('focusout',event => {if(!details.contains(event.relatedTarget))close();});
    trigger.setAttribute('aria-expanded',String(details.open));
    if (details.id) document.querySelectorAll('[data-player-anchor="'+details.id+'"]').forEach(anchor => {
      anchor.addEventListener('pointerenter',event=>{if(event.pointerType==='mouse')open(anchor);});
      anchor.addEventListener('pointerleave',leave);
      anchor.addEventListener('click',event=>{if(!details.hidden){event.preventDefault();open(anchor);panel.focus({preventScroll:true});}});
    });
    return {details,trigger,panel,close,position};
  });
  document.addEventListener('pointerdown',event => {
    records.forEach(record => {if(!record.details.contains(event.target))record.close();});
  });
  document.addEventListener('keydown',event => {
    if(event.key!=='Escape')return;
    const record=records.find(item=>item.details.open);
    if(record) {event.preventDefault();record.close(false,record.details.contains(document.activeElement));}
  });
  window.addEventListener('resize',() => records.forEach(record=>record.position()));
  window.addEventListener('scroll',event => {
    if(records.some(record=>record.panel.contains(event.target)))return;
    records.forEach(record=>record.close(true));
  },true);
  window.addEventListener('pagehide',() => records.forEach(record=>record.close(true)));
  window.addEventListener('pageshow',event => {if(event.persisted)records.forEach(record=>record.close(true));});
});
