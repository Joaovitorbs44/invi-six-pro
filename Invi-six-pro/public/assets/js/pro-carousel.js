'use strict';
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-pro-carousel]').forEach(root => {
    const viewport=root.querySelector('[data-pro-viewport]'),track=root.querySelector('[data-pro-track]'),group=root.querySelector('[data-pro-group]'),button=root.querySelector('[data-pro-toggle]');
    const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
    let motion,clone,hover=false,focus=false,paused=false,stopped=false;
    function sync() {
      if(!motion)return;
      if(reduced.matches||hover||focus||paused||stopped)motion.pause();else motion.play();
      button.setAttribute('aria-pressed',String(paused));
      root.querySelector('[data-pro-label]').textContent=paused?'Retomar movimento':'Pausar movimento';
      root.querySelector('[data-pro-pause]').hidden=paused;
      root.querySelector('[data-pro-play]').hidden=!paused;
    }
    function build() {
      const oldProgress=motion && Number.isFinite(motion.currentTime) ? motion.currentTime / Number(motion.effect.getTiming().duration) % 1 : 0;
      motion?.cancel();motion=null;
      if(reduced.matches||!track.animate) {clone?.remove();clone=null;button.hidden=true;return;}
      if(!clone){clone=group.cloneNode(true);clone.removeAttribute('data-pro-group');clone.setAttribute('aria-hidden','true');clone.inert=true;track.appendChild(clone);}
      group.style.minWidth=viewport.getBoundingClientRect().width+'px';
      clone.style.minWidth=group.style.minWidth;
      const distance=group.getBoundingClientRect().width;
      if(!distance)return;
      const duration=distance/22*1000;
      motion=track.animate([{transform:'translateX(0)'},{transform:'translateX(-'+distance+'px)'}],{duration,iterations:Infinity,easing:'linear'});
      motion.currentTime=oldProgress*duration;button.hidden=false;sync();
    }
    viewport.addEventListener('pointerenter',event=>{if(event.pointerType==='mouse'){hover=true;sync();}});
    viewport.addEventListener('pointerleave',()=>{hover=false;sync();});
    root.addEventListener('focusin',()=>{focus=true;sync();});
    root.addEventListener('focusout',event=>{focus=root.contains(event.relatedTarget);sync();});
    viewport.addEventListener('pointerdown',event=>{if(event.pointerType==='touch'||event.pointerType==='pen'){paused=true;sync();}});
    button.addEventListener('click',()=>{paused=!paused;sync();});
    reduced.addEventListener('change',build);
    window.addEventListener('resize',build);
    window.addEventListener('pagehide',()=>{stopped=true;sync();});
    window.addEventListener('pageshow',event=>{if(event.persisted){stopped=false;hover=false;focus=root.contains(document.activeElement);sync();}});
    document.fonts?.ready.then(build);
    build();
  });
});
