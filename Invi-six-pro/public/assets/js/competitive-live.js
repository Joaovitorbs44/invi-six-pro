"use strict";
// Refresh only server-rendered competitive data. Forms and focused controls stay intact.
document.addEventListener("DOMContentLoaded", () => {
  const targets = () => [...document.querySelectorAll("[data-live-competitive]")];
  if (!targets().length) return;
  let busy = false;
  async function refresh() {
    if (busy || document.hidden) return;
    busy = true;
    try {
      const response = await fetch(window.location.href, {credentials: "same-origin", cache: "no-store", headers: {Accept: "text/html"}, signal: AbortSignal.timeout(10000)});
      if (!response.ok || response.redirected) return;
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
    } catch { /* The last valid data stays visible; the next poll retries. */ }
    finally { busy = false; }
  }
  window.addEventListener("storage", event => {if (event.key === "invisix.result.updated") void refresh();});
  window.addEventListener("focus", refresh);
  window.addEventListener("pageshow", refresh);
  document.addEventListener("visibilitychange", () => {if (!document.hidden) void refresh();});
  window.setInterval(refresh, 15000);
});
