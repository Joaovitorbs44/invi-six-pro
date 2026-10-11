(() => {
    const root=document.querySelector('[data-support]');if(!root)return;
    const modal=root.querySelector('[data-support-modal]');
    const openButton=root.querySelector('[data-open-support]');
    let returnFocus=openButton;
    const close=()=>window.LobbyUI.closeModal(modal);
    const open=()=>{returnFocus=document.activeElement;window.LobbyUI.openModal(modal,{focus:modal.querySelector('select'),trigger:returnFocus});};
    if(!modal.hidden)window.LobbyUI.openModal(modal,{focus:modal.querySelector('select'),trigger:openButton});
    openButton.addEventListener('click',open);
    root.querySelector('[data-close-support]').addEventListener('click',close);
    modal.addEventListener('click',event=>{if(event.target===modal)close();});
    document.addEventListener('keydown',event=>{
        if(modal.hidden)return;
        if(event.key==='Escape')close();
        if(event.key==='Tab'){
            const fields=[...modal.querySelectorAll('button,input:not([type="hidden"]),textarea,select,a[href]')].filter(el=>!el.disabled&&el.getClientRects().length);
            if(event.shiftKey&&document.activeElement===fields[0]){event.preventDefault();fields.at(-1).focus();}
            else if(!event.shiftKey&&document.activeElement===fields.at(-1)){event.preventDefault();fields[0].focus();}
        }
    });
    const category=root.querySelector('[data-support-category]');
    const changeCategory=()=>{root.querySelector('[data-related-match]').hidden=category.value!=='match';root.querySelector('[data-related-player]').hidden=category.value!=='report';};
    category.addEventListener('change',changeCategory);changeCategory();
    let last='',listSignature='',busy=false,submitting=false,stopped=false,timer;
    const endpoint=new URL('/support/snapshot',location.origin);
    const filters=new URLSearchParams(location.search);
    for(const key of ['q','status','category'])if(filters.has(key))endpoint.searchParams.set(key,filters.get(key));
    endpoint.searchParams.set('ticket',root.dataset.ticket);
    endpoint.searchParams.set('view',root.dataset.staff==='1'?'staff':'player');
    const element=(tag,text,className)=>{const node=document.createElement(tag);node.textContent=text;if(className)node.className=className;return node;};
    function renderList(data){
        const signature=JSON.stringify(data.tickets);if(signature===listSignature)return;listSignature=signature;
        const sidebar=root.querySelector('.cx-ticket-list');
        const focusId=document.activeElement?.dataset.ticketId;
        sidebar.querySelectorAll('.cx-ticket,.cx-ticket-empty').forEach(el=>el.remove());
        for(const item of data.tickets){
            const link=document.createElement('a');const params=new URLSearchParams();
            for(const key of ['q','status','category'])if(filters.has(key))params.set(key,filters.get(key));params.set('ticket',item.id);
            link.href=(root.dataset.staff==='1'?'/admin/support':'/support')+'?'+params;
            link.dataset.ticketId=item.id;link.className='cx-ticket'+(String(item.id)===root.dataset.ticket?' cx-selected':'');
            if(String(item.id)===root.dataset.ticket)link.setAttribute('aria-current','true');
            const title=document.createElement('div');title.append(element('strong',item.subject),element('span',data.statuses[item.status],'cx-badge'));
            const meta=element('small','#'+item.id+' '+item.owner_name);const unread=element('span',item.unread?'● '+item.unread:'');unread.dataset.ticketUnread=item.id;meta.append(unread);
            link.append(title,meta,element('p',data.categories[item.category],'cx-ticket-category'),element('p',item.last_message),element('small',window.LobbyDates.format(item.updated_at)));sidebar.append(link);
        }
        if(!data.tickets.length){const empty=element('div','','cx-empty cx-ticket-empty');empty.append(element('h3','Nenhuma conversa por aqui.'),element('p',filters.get('q')||filters.get('category')||filters.get('status')?'Tente ajustar os filtros.':'Quando você iniciar um atendimento, ele aparecerá aqui.'));sidebar.append(empty);}
        if(focusId)sidebar.querySelector('[data-ticket-id="'+Number(focusId)+'"]')?.focus();
    }
    function replyState(status){
        const reply=root.querySelector('.cx-reply');if(!reply)return;
        const closed=['resolved','closed'].includes(status);
        reply.querySelector('textarea').disabled=closed;reply.querySelector('button').disabled=closed||submitting;
        reply.querySelector('textarea').placeholder=closed?'Conversa encerrada. A staff pode reabrir este ticket.':'Digite sua mensagem...';
    }
    async function refresh(){
        if(busy||document.hidden||stopped)return;busy=true;
        try{
            const response=await fetch(endpoint,{credentials:'same-origin',cache:'no-store',signal:AbortSignal.timeout(8000)});
            if(response.status===401){stopped=true;window.showToast?.('warning','Sua sessão expirou. Entre novamente.');return;}
            if(!response.ok)return;const data=await response.json();
            root.querySelector('[data-unread-count]').textContent=data.unread+' não lidas';root.querySelector('[data-pending-count]').textContent=data.pending;
            renderList(data);
            const history=root.querySelector('[data-messages]');if(!history)return;
            const signature=JSON.stringify(data.messages);
            if(signature!==last){
                const previousTop=history.scrollTop;
                const nearBottom=history.scrollHeight-history.scrollTop-history.clientHeight<100;
                history.replaceChildren();
                for(const message of data.messages){
                    const article=element('article','','cx-message'+(message.sender_type==='player'?' cx-message-player':''));
                    const heading=document.createElement('p');heading.append(element('strong',message.sender_name));if(message.sender_type==='staff')heading.append(element('span','Staff','cx-badge'));
                    article.append(heading,element('p',message.message,'cx-message-text'),element('small',window.LobbyDates.format(message.created_at)));history.append(article);
                }
                history.scrollTop=nearBottom||!last?history.scrollHeight:previousTop;
                if(last)window.showToast?.('info','Novas mensagens na conversa.');last=signature;
            }
            root.querySelector('[data-ticket-status]').textContent=data.statuses[data.status]||'';
            root.querySelector('[data-ticket-staff]').textContent=data.staff_name||'Staff ainda não atribuída';
            replyState(data.status);
        }catch{}finally{busy=false;}
    }
    root.querySelectorAll('[data-support-action],[data-support-create]').forEach(form=>form.addEventListener('submit',async event=>{
        event.preventDefault();if(submitting)return;
        const payload=new FormData(form);submitting=true;
        const button=form.querySelector('button:not([type="button"])');
        const buttonLabel=button?.textContent;
        form.setAttribute('aria-busy','true');
        if(button){button.disabled=true;button.textContent='Enviando…';}
        try{
            const response=await fetch(form.getAttribute('action'),{method:'POST',body:payload,headers:{Accept:'application/json'},credentials:'same-origin',signal:AbortSignal.timeout(10000)});
            if(response.status===419||response.status===401)throw Error('Sua sessão expirou. Atualize a página e tente novamente.');
            const data=await response.json();if(!response.ok)throw Error(data.error||'Não foi possível enviar.');
            window.showToast?.('success',data.message);
            if(form.hasAttribute('data-support-create')){location.href=(root.dataset.staff==='1'?'/admin/support':'/support')+'?ticket='+data.ticket_id;return;}
            const text=form.querySelector('textarea');if(text)text.value='';
        }catch(error){window.showToast?.('error',error.message);}finally{submitting=false;form.removeAttribute('aria-busy');if(button){button.disabled=false;button.textContent=buttonLabel;}await refresh();}
    }));
    async function poll(){await refresh();if(!stopped)timer=setTimeout(poll,5000);}
    window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});
    window.addEventListener('pageshow',event=>{if(event.persisted){stopped=false;void poll();}});
    void poll();
})();
