document.addEventListener('DOMContentLoaded',()=>{
 const dialog=document.querySelector('[data-season-close-dialog]');
 document.querySelector('[data-season-close-open]')?.addEventListener('click',()=>{if(dialog?.showModal)dialog.showModal();});
 dialog?.querySelector('[data-season-close-cancel]')?.addEventListener('click',()=>dialog.close());
 document.querySelectorAll('form[method=get],form[method=GET]').forEach(form=>form.addEventListener('submit',event=>{
  if(!form.matches('.cx-filters,.history18-filter,.admin19-filters'))return;
  if(form.getAttribute('aria-busy')==='true'){event.preventDefault();return;}
  form.setAttribute('aria-busy','true');form.classList.add('filter19-busy');
 }));
 window.addEventListener('pageshow',()=>document.querySelectorAll('.filter19-busy').forEach(form=>{form.removeAttribute('aria-busy');form.classList.remove('filter19-busy');}));
});
