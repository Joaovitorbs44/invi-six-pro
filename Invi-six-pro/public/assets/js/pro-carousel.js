'use strict';
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-pro-carousel]').forEach(root => {
    const viewport=root.querySelector('[data-pro-viewport]'),track=root.querySelector('[data-pro-track]'),group=root.querySelector('[data-pro-group]'),button=root.querySelector('[data-pro-toggle]');
    const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
    let clone,paused=false;
    function sync(){
      root.dataset.proPaused=String(paused);
      button.setAttribute('aria-pressed',String(paused));
      root.querySelector('[data-pro-label]').textContent=paused?'Retomar movimento':'Pausar movimento';
      root.querySelector('[data-pro-pause]').hidden=paused;
      root.querySelector('[data-pro-play]').hidden=!paused;
    }
    function build(){
      if(reduced.matches){root.dataset.proEnhanced='false';clone?.remove();clone=null;button.hidden=true;group.style.minWidth='';return;}
      if(!clone){clone=group.cloneNode(true);clone.removeAttribute('data-pro-group');clone.setAttribute('aria-hidden','true');clone.inert=true;track.appendChild(clone);}
      const motion=track.getAnimations()[0];
      const duration=motion?.effect.getTiming().duration;
      const progress=motion && typeof duration==='number' && duration>0 ? Number(motion.currentTime)/duration : 0;
      group.style.minWidth=viewport.clientWidth+'px';clone.style.minWidth=group.style.minWidth;
      const distance=group.getBoundingClientRect().width;
      track.style.setProperty('--pro-distance',distance+'px');track.style.setProperty('--pro-duration',distance/22+'s');
      root.dataset.proEnhanced='true';button.hidden=false;
      if(motion)motion.currentTime=progress*(distance/22*1000);
      sync();
    }
    button.addEventListener('click',()=>{paused=!paused;if(!paused)viewport.scrollLeft=0;sync();});
    viewport.addEventListener('pointerdown',event=>{if(event.pointerType==='touch'||event.pointerType==='pen'){paused=true;sync();}});
    viewport.addEventListener('keydown',event=>{if(event.key==='ArrowRight'||event.key==='ArrowLeft'){event.preventDefault();paused=true;sync();viewport.scrollBy({left:event.key==='ArrowRight'?220:-220,behavior:'instant'});}});
    reduced.addEventListener('change',build);
    if(window.ResizeObserver)new ResizeObserver(build).observe(viewport);else window.addEventListener('resize',build);
    document.fonts?.ready.then(build);
    build();
  });
});
