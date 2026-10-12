"use strict";
// Refresh only server-rendered competitive data. Forms and focused controls stay intact.
document.addEventListener("DOMContentLoaded", () => {
  const targets = () => [...document.querySelectorAll("[data-live-competitive]")];
  if (!targets().length || document.getElementById('matchmaking')) return;
  let busy = false;
  let lastRefresh = performance.now();
  function refreshNotice(message) {
    const root = targets()[0];
    if (!root) return;
    let notice = root.querySelector('[data-refresh-notice]');
    if (!message) { notice?.remove(); return; }
    if (!notice) {
      notice = document.createElement('p');
      notice.dataset.refreshNotice = 'true';
      notice.className = 'cx-alert';
      notice.setAttribute('role', 'status');
      root.append(notice);
    }
    notice.textContent = message;
  }
  async function refresh() {
    if (busy || document.hidden || performance.now() - lastRefresh < 1000) return;
    busy = true;
    lastRefresh = performance.now();
    try {
      const response = await fetch(window.location.href, {credentials: "same-origin", cache: "no-store", headers: {Accept: "text/html"}, signal: AbortSignal.timeout(10000)});
      if (response.redirected) { refreshNotice('Entre novamente para atualizar os dados.'); return; }
      if (!response.ok) throw new Error('Refresh unavailable');
      refreshNotice('');
      const page = new DOMParser().parseFromString(await response.text(), "text/html");
      const next = [...page.querySelectorAll("[data-live-competitive]")];
      for (const [index, current] of targets().entries()) {
        if (!next[index] || current.contains(document.activeElement)) continue;
        // History filters are interactive: replace only the list and pagination.
        if (current.matches(".history18")) {
          for (const selector of [".match-history18", 'nav[aria-label="Paginação de partidas"]']) {
            const old = current.querySelector(selector), fresh = next[index].querySelector(selector);
            if (old && fresh && old.innerHTML !== fresh.innerHTML) old.replaceWith(fresh);
          }
        } else if (current.innerHTML !== next[index].innerHTML) current.replaceWith(next[index]);
      }
    } catch { refreshNotice('Não foi possível atualizar os dados. Tentaremos novamente automaticamente.'); }
    finally { busy = false; }
  }
  window.addEventListener("storage", event => {if (event.key === "invisix.result.updated") void refresh();});
  window.addEventListener("focus", refresh);
  window.addEventListener("pageshow", event => {if (event.persisted) void refresh();});
  document.addEventListener("visibilitychange", () => {if (!document.hidden) void refresh();});
  window.setInterval(refresh, 30000);
});
