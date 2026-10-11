(() => {
 const root = document.querySelector('[data-match-board]');
 if (!root) return;
 let id = Number(root.dataset.matchId || 0);
 let version = Number(root.dataset.version ?? -1);
 let mapsView = new URLSearchParams(location.search).get('view') === 'maps';
 let busy = false, loading = false, last = null, stopped = false;
 let actionGeneration = 0;
 let pendingVeto = null, approvedVeto = null;
 let lastError = '', lastErrorAt = 0;
 const toast = (type, message) => {
  if (type !== 'success' && message === lastError && Date.now() - lastErrorAt < 5000) return;
  lastError = type === 'success' ? '' : message;
  lastErrorAt = Date.now();
  window.showToast?.(type, message);
 };
 const initialSkeleton = () => {
  if (!root.children.length) root.innerHTML = '<div class="arena-loading" role="status">Carregando partida…<span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span></div>';
 };
 function showError(message, sessionExpired = false) {
  root.querySelector('.arena-loading')?.remove();
  let notice = root.querySelector('.arena-refresh-error');
  if (!notice) {
   notice = document.createElement('p'); notice.className = 'arena-refresh-error'; notice.setAttribute('role', 'alert'); root.append(notice);
  }
  notice.replaceChildren(document.createTextNode(message + ' '));
  const link = document.createElement('a');
  link.href = sessionExpired ? '/login?next=' + encodeURIComponent('/partida/' + id + (mapsView ? '?view=maps' : '')) : '/partida/' + id + (mapsView ? '?view=maps' : '');
  link.textContent = sessionExpired ? 'Entrar novamente' : 'Recarregar partida'; notice.append(link);
 }
 async function refresh(force = false) {
  if (!id || loading || busy || stopped || document.hidden || root.querySelector('[data-veto-dialog]')?.open) return false;
  loading = true; root.setAttribute('aria-busy', 'true'); initialSkeleton();
  const requestedId = id;
  const requestedGeneration = actionGeneration;
  try {
   const response = await fetch('/match/snapshot?match_id=' + id + (mapsView ? '&view=maps' : ''), {headers: {Accept: 'application/json'}, signal: AbortSignal.timeout(10000)});
   const data = await response.json();
   if (requestedId !== id || requestedGeneration !== actionGeneration) return false;
   if (!response.ok) {
    if ([401, 404].includes(response.status)) stopped = true;
    showError(data.message || 'Não foi possível atualizar a partida.', response.status === 401);
    return false;
   }
   root.querySelector('.arena-refresh-error')?.remove();
   if (force || data.version !== version) {
    const active = document.activeElement;
    const focusedForm = root.contains(active) ? active.closest('[data-match-action]') : null;
    const action = focusedForm?.querySelector('[name="action"]')?.value;
    const target = focusedForm?.querySelector('[name="map_id"], [name="player_id"]')?.value;
    const scrollPositions = [...root.querySelectorAll('.arena-map-track, .arena-steps, .arena-veto-order ol')].map(el => el.scrollLeft);
    const oldStatus = root.querySelector('[data-status]')?.dataset.status;
    const openDetails = ['.match19-veto-history','.timeline19-more'].filter(selector => root.querySelector(selector)?.open);
    const positions = new Map([...root.querySelectorAll('[data-player-id]')].map(card => [card.dataset.playerId, {bounds:card.getBoundingClientRect(),team:card.closest(".draft-team")?.className || "pool"}]));
    root.innerHTML = data.html; version = data.version;
    openDetails.forEach(selector => {const details=root.querySelector(selector);if(details)details.open=true;});
    const reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    const board = root.querySelector('[data-status]');
    if (!reduced && board?.animate) {
     if (oldStatus !== data.status) board.animate([{opacity:0,transform:'translateY(12px)'},{opacity:1,transform:'translateY(0)'}],{duration:350,easing:'cubic-bezier(0.22, 1, 0.36, 1)'});
     else root.querySelectorAll('[data-player-id]').forEach(card => {
      const saved = positions.get(card.dataset.playerId), previous = saved?.bounds, current = card.getBoundingClientRect();
      if (previous && saved.team !== (card.closest(".draft-team")?.className || "pool") && (Math.abs(previous.x-current.x)>1 || Math.abs(previous.y-current.y)>1)) card.animate([{transform:'translate('+(previous.x-current.x)+'px,'+(previous.y-current.y)+'px)',opacity:0.6},{transform:'translate(0,0)',opacity:1}],{duration:300,easing:'cubic-bezier(0.22, 1, 0.36, 1)'});
     });
    }
    root.dataset.version = String(version);
    root.querySelectorAll('.arena-map-track, .arena-steps, .arena-veto-order ol').forEach((el, index) => { el.scrollLeft = scrollPositions[index] || 0; });
    if (action) {
     const replacement = [...root.querySelectorAll('[data-match-action]')].find(form => form.querySelector('[name="action"]')?.value === action && form.querySelector('[name="map_id"], [name="player_id"]')?.value === target);
     if (replacement) replacement.querySelector('button')?.focus({preventScroll: true});
     else { const title = root.querySelector('.arena-veto-heading h2, .arena-heading h1'); if (title) {title.tabIndex = -1; title.focus({preventScroll: true});} }
    }
    if (last) {
     if (data.captain && !last.captain) toast('success', 'Você foi definido como capitão.');
     else if (data.turn && data.team === data.turn && (last.turn !== data.turn || last.version !== data.version)) toast('info', data.status === 'draft' ? 'É a sua vez de escolher.' : 'É a sua vez de banir.');
     else if (data.event && data.event !== last.event) toast('info', data.event);
    }
   }
   if(window.dispatchEvent) {
    const teams={};
    for(const team of ['a','b']) {
     const roster=root.querySelector('.arena-roster.arena-team-'+team);
     if (roster) teams[team]={captain:roster.querySelector('.arena-roster-heading strong')?.textContent||null,count:roster.querySelector('.arena-roster-count')?.textContent||'Aguardando draft'};
    }
    window.dispatchEvent(new CustomEvent('lobby:match-rendered',{detail:{id,teams}}));
   }
   last = data; return true;
  } catch (error) {
   if (requestedId === id && requestedGeneration === actionGeneration) showError('Não foi possível atualizar a partida. Seus dados continuam preservados.');
   if (force && requestedId === id && requestedGeneration === actionGeneration) toast('warning', error.message || 'Não foi possível atualizar a partida.');
   return false;
  } finally {
   loading = false; root.setAttribute('aria-busy', 'false');
   if ((requestedGeneration !== actionGeneration || requestedId !== id) && !busy && !stopped) refresh(true);
  }
 }
 root.addEventListener('submit', async event => {
  const form = event.target.closest('[data-match-action]');
  if (!form) return;
  event.preventDefault(); if (busy || stopped) return;
  if (form.dataset?.vetoAction && approvedVeto !== form) {
   const dialog = root.querySelector('[data-veto-dialog]');
   const verb = form.dataset.vetoAction === 'pick' ? 'Escolher' : 'Banir';
   if (dialog?.showModal) {
    pendingVeto = form;
    dialog.querySelector('[data-veto-title]').textContent = verb + ' ' + form.dataset.mapLabel + '?';
    dialog.querySelector('[data-veto-apply]').textContent = verb + ' mapa';
    const description = dialog.querySelector('[data-confirm-description]'); if (description) description.textContent = form.dataset.confirmDescription || 'Esta ação não poderá ser desfeita.';
    dialog.addEventListener('close', () => {pendingVeto = null; form.querySelector('button')?.focus({preventScroll: true});}, {once: true});
    if(window.LobbyUI)window.LobbyUI.openModal(dialog,{focus:dialog.querySelector('[data-veto-cancel]')});
    else {dialog.showModal(); dialog.querySelector('[data-veto-cancel]').focus();} return;
   }
   if (!window.confirm(verb + ' ' + form.dataset.mapLabel + '? ' + (form.dataset.confirmDescription || 'Esta ação não poderá ser desfeita.'))) return;
  }
  approvedVeto = null;
  const payload = new FormData(form), button = form.querySelector('button');
  const originalLabel = button.innerHTML ?? button.textContent;
  const buttons = [...root.querySelectorAll('[data-match-action] button')];
  const disabled = buttons.map(item => item.disabled);
  actionGeneration++; busy = true; buttons.forEach(item => {item.disabled = true;}); button.disabled = true; button.textContent = 'Processando…'; form.setAttribute?.('aria-busy', 'true');
  try {
   // The named input "action" shadows HTMLFormElement.action; read the attribute.
   const response = await fetch(form.getAttribute('action'), {method: 'POST', credentials: 'same-origin', body: payload, headers: {Accept: 'application/json'}, signal: AbortSignal.timeout(10000)});
   const fallback = {401:'Sua sessão expirou. Entre novamente.',403:'Você não tem permissão para esta ação.',404:'A ação não foi encontrada. Recarregue a partida.',405:'Método de envio inválido. Recarregue a página.',409:'A partida mudou ou esta ação não está mais disponível.',419:'O token de segurança expirou. Atualize a página e tente novamente.'};
   let data;
   try {data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;} catch {data = null;}
   if (response.status === 401) {stopped = true; showError(fallback[401], true);}
   if (!response.ok || !data?.success) throw Error(data?.message || fallback[response.status] || 'Resposta inválida do servidor. Tente novamente.');
   toast('success', data.message);
   try { localStorage.setItem('invisix.result.updated', String(Date.now())); } catch { /* Polling works without storage. */ }
  } catch (error) { toast('warning', ['TypeError','TimeoutError','AbortError'].includes(error.name) ? 'Erro de conexão. Confira sua rede e tente novamente.' : error.message || 'Não foi possível enviar a ação.'); }
  finally {
   busy = false; buttons.forEach((item,index) => {item.disabled = disabled[index];}); button.disabled = false;
   if ('innerHTML' in button) button.innerHTML = originalLabel; else button.textContent = originalLabel;
   form.removeAttribute?.('aria-busy'); await refresh(true);
  }
 });
 root.addEventListener('click', async event => {
  if (event.target.closest('[data-veto-cancel]')) {const dialog=root.querySelector('[data-veto-dialog]');if(window.LobbyUI)await window.LobbyUI.closeModal(dialog);else dialog?.close(); return;}
  if (event.target.closest('[data-veto-apply]')) {
   const form = pendingVeto; if (!form || busy) return;
   approvedVeto = form;const dialog=root.querySelector('[data-veto-dialog]');if(window.LobbyUI)await window.LobbyUI.closeModal(dialog);else dialog?.close(); form.requestSubmit(); return;
  }
  const advance = event.target.closest('[data-open-veto]');
  if (advance) {
   event.preventDefault(); if (busy || loading) return;
   mapsView = true;
   const updated = await refresh(true);
   if (updated) {
    if (/^\/(?:match|partida)\/\d+$/.test(location.pathname)) history.replaceState(null, '', '/partida/' + id + '?view=maps#match-veto');
    const title = root.querySelector('#veto-title'); if (title) {title.tabIndex = -1; title.focus({preventScroll: true});}
   }
   return;
  }
  const button = event.target.closest('[data-copy-match]');
  if (!button) return;
  try {await navigator.clipboard.writeText(button.dataset.copyMatch); toast('success', 'Informações copiadas.');}
  catch {toast('warning', 'Não foi possível copiar. Permita o acesso à área de transferência.');}
 });
 window.updateMatchBoard = match => {
  if (!match) {id = 0; version = -1; last = null; stopped = false; root.dataset.matchId = '0'; root.replaceChildren(); return;}
  if (id !== Number(match.id)) {
   id = Number(match.id); version = -1; last = null; mapsView = false; stopped = false;
   root.dataset.matchId = String(id); toast('success', 'Lobby completo.'); refresh(true);
  }
 };
 document.addEventListener('visibilitychange', () => {if (!document.hidden) refresh();});
 function readyClock() {
  root.querySelectorAll('[data-ready-deadline]').forEach(clock => {
   const deadline = Date.parse(clock.dataset.readyDeadline.replace(' ', 'T') + 'Z');
   if (!Number.isFinite(deadline)) return;
   const seconds = Math.max(0, Math.ceil((deadline-Date.now())/1000));
   clock.textContent = Math.floor(seconds/60) + ':' + String(seconds%60).padStart(2,'0');
  });
 }
 setInterval(() => refresh(), 4000);
 setInterval(readyClock,1000); readyClock(); if (!root.children.length) refresh();
})();
