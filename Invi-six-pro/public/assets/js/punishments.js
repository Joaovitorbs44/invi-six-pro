(() => {
    const form=document.querySelector('[data-punishment-form]');
    if(!form)return;
    const type=form.elements.type,permanent=form.elements.permanent,hours=form.elements.hours;
    const duration=form.querySelector('[data-punishment-duration]');
    const update=()=>{
        const warning=type.value==='warning';
        duration.hidden=warning;
        permanent.disabled=warning;
        hours.disabled=warning||permanent.checked;
        hours.required=!hours.disabled;
        form.querySelector('[data-warning-note]').hidden=!warning;
    };
    type.addEventListener('change',update);permanent.addEventListener('change',update);update();
    const dialog=document.querySelector('[data-punishment-confirm]');
    let confirmed=false;
    form.addEventListener('submit',event=>{
        if(confirmed||type.value==='warning')return;
        event.preventDefault();
        const player=form.elements.user_id.selectedOptions[0].textContent;
        const label=type.selectedOptions[0].textContent;
        dialog.querySelector('[data-punishment-confirm-copy]').textContent=label+' para '+player+'. '+(permanent.checked?'Restrição permanente.':'Duração: '+hours.value+' horas.')+' O jogador perderá acesso à fila competitiva.';
        window.LobbyUI.openModal(dialog,{focus:dialog.querySelector('[data-punishment-cancel]')});
    });
    dialog.querySelector('[data-punishment-cancel]').addEventListener('click',()=>window.LobbyUI.closeModal(dialog));
    dialog.querySelector('[data-punishment-apply]').addEventListener('click',async()=>{if(confirmed)return;confirmed=true;await window.LobbyUI.closeModal(dialog);form.requestSubmit();});
    dialog.addEventListener('close',()=>form.querySelector('button[type="submit"]').focus());
})();
