"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const root = document.getElementById("matchmaking");
  if (!root) return;
  const byId = (id) => document.getElementById(id);
  const initial = byId("mm-initial-state");
  let state;
  let matchDestination = null;
  try { state = JSON.parse(initial.textContent); } catch { return; }

  const memory = new Map();
  const storage = {
    get(key, persistent = false) {
      if (memory.has(key)) return memory.get(key);
      try { return (persistent ? localStorage : sessionStorage).getItem(key); } catch { return null; }
    },
    set(key, value, persistent = false) {
      memory.set(key, value);
      try {
        const target = persistent ? localStorage : sessionStorage;
        if (value === null) target.removeItem(key); else target.setItem(key, value);
      } catch { /* Private browsing can disable storage. */ }
    },
  };
  const soundKey = "lobby-r6-matchmaking-sound";
  const queueKey = "lobby-r6-queue-notice-" + state.account.id;
  const matchKey = "lobby-r6-match-notice-" + state.account.id;
  const presentations = {
    busy: ["Lobby em andamento", "Uma simulação está em andamento", "Aguarde o encerramento ou reinicie a partida pelo painel de homologação."],
    idle: ["Disponível", "Pronto para competir?", state.testing ? "Entre na fila quando estiver pronto. Nove simulados aguardam sua entrada. Você ocupa a décima vaga." : "Entre na fila. A partida começa quando dez jogadores reais estiverem disponíveis."],
    queued: ["Na fila", "Buscando jogadores", "Sua vaga está reservada. Acompanhe a fila enquanto os jogadores chegam."],
    match: ["Partida encontrada", "Partida encontrada", "Seu lobby está pronto. Continue na etapa competitiva abaixo."],
    playing: ["Em partida", "Partida em andamento", "Boa partida. Acompanhe os times e o resultado no painel da partida."],
    restricted: ["Restrição competitiva", "Acesso à fila restrito", "Consulte suas punições ou fale com o suporte para entender a restrição."],
    "no-season": ["Fila pausada", "Aguardando temporada", "A fila será liberada quando a administração abrir uma temporada."],
  };
  function waitLabel(joined) {
    if (!joined) return "";
    const start = Date.parse(joined.replace(" ", "T") + "Z");
    if (!Number.isFinite(start)) return "";
    const seconds = Math.max(0, Math.floor((Date.now() - start) / 1000));
    return Math.floor(seconds / 60).toString().padStart(2, "0") + ":" + (seconds % 60).toString().padStart(2, "0") + " na fila";
  }
  function renderWaits() {
    root.querySelectorAll("[data-mm-wait-start]").forEach(element => {
      element.textContent = waitLabel(element.dataset.mmWaitStart);
    });
  }
  let soundEnabled = storage.get(soundKey, true) === "1";
  let audio = null;
  let audioWarningShown = false;
  let pendingSound = false;
  let soundBusy = false;
  const toggle = byId("mm-sound-toggle");

  function toast(type, message) {
    if (typeof window.showToast === "function") window.showToast(type, message);
    byId("mm-announcement").textContent = message;
  }

  function renderSound() {
    const active = soundEnabled && audio?.state === "running";
    toggle.setAttribute("aria-pressed", String(active));
    byId("mm-sound-label").textContent = active ? "Desativar som" : "Ativar som";
    byId("mm-sound-icon").replaceChildren(byId(active ? "mm-volume-on" : "mm-volume-off").content.cloneNode(true));
    byId("mm-sound-status").textContent = active ? "Som ativado nesta aba" :
      (soundEnabled ? "Clique em Ativar som para habilitar o aviso nesta aba." : "Som desativado");
  }

  async function unlockAudio() {
    const Audio = window.AudioContext || window.webkitAudioContext;
    if (!Audio) throw new Error("Audio unsupported");
    if (!audio || audio.state === "closed") audio = new Audio();
    if (audio.state !== "running") {
      await Promise.race([
        audio.resume(),
        new Promise((_, reject) => window.setTimeout(() => reject(new Error("Audio blocked")), 1500)),
      ]);
    }
    if (audio.state !== "running") throw new Error("Audio blocked");
    renderSound();
  }

  function chime() {
    if (!audio || audio.state !== "running") return;
    // A short, soft triad generated locally; no audio download is needed.
    [523.25, 659.25, 783.99, 1046.5].forEach((frequency, index) => {
      const start = audio.currentTime + index * 0.16;
      const oscillator = audio.createOscillator();
      const gain = audio.createGain();
      oscillator.type = "sine";
      oscillator.frequency.value = frequency;
      gain.gain.setValueAtTime(0, start);
      gain.gain.linearRampToValueAtTime(0.09, start + 0.025);
      gain.gain.exponentialRampToValueAtTime(0.001, start + 0.32);
      oscillator.connect(gain);
      gain.connect(audio.destination);
      oscillator.start(start);
      oscillator.stop(start + 0.34);
      oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
    });
  }

  async function signal(message, key, value) {
    if (storage.get(key) === value) return;
    storage.set(key, value);
    toast("success", message);
    if (!soundEnabled) return;
    try {
      await unlockAudio();
      chime();
      pendingSound = false;
    } catch {
      pendingSound = true;
      renderSound();
      if (!audioWarningShown) {
        toast("info", "O navegador precisa de um clique em Ativar som para liberar o aviso sonoro.");
        audioWarningShown = true;
      }
    }
  }

  toggle.addEventListener("click", async () => {
    if (soundBusy) return;
    if (soundEnabled && audio?.state === "running") {
      soundEnabled = false;
      storage.set(soundKey, "0", true);
      renderSound();
      return;
    }
    soundBusy = true;
    try {
      await unlockAudio();
      soundEnabled = true;
      storage.set(soundKey, "1", true);
      audioWarningShown = false;
      renderSound();
      chime();
      pendingSound = false;
    } catch {
      toast("warning", "Não foi possível ativar o áudio. Verifique as permissões de som do navegador.");
    } finally { soundBusy = false; }
  });

  byId("mm-sound-test").addEventListener("click", async () => {
    try { await unlockAudio(); chime(); }
    catch { toast("warning", "Seu navegador não liberou o som. Verifique as permissões de áudio."); }
  });

  // A remembered preference is unlocked by a real gesture after a redirect.
  document.addEventListener("pointerdown", (event) => {
    // Let the sound buttons handle their own gesture, so auto-unlock cannot
    // turn the first click on "Ativar som" into a click on "Desativar som".
    if (event.target.closest?.("#mm-sound-toggle, #mm-sound-test")) return;
    if (!soundEnabled || audio?.state === "running") return;
    unlockAudio().then(() => {
      if (pendingSound) { chime(); pendingSound = false; }
    }).catch(() => {});
  }, { passive: true });

  function renderRoster(next) {
    const fragment = document.createDocumentFragment();
    const players = next.match ? (next.participants || []) : next.queue;
    for (let index = 0; index < Math.max(next.queue_target || 10, players.length); index++) {
      const item = byId("mm-empty-slot").content.firstElementChild.cloneNode(true);
      item.querySelector(".mm-slot-number").textContent = String(index + 1).padStart(2, "0");
      const player = players[index];
      if (player) {
        item.classList.add("mm-slot-filled");
        item.classList.toggle("mm-slot-self", player.is_current || Number(player.user_id) === Number(next.account.id));
        item.querySelector(".mm-slot-avatar").textContent = (Array.from(player.r6_nickname)[0] || "?").toLocaleUpperCase("pt-BR");
        item.querySelector(".mm-slot-name").textContent = player.r6_nickname;
        if (player.is_simulated) { const badge = document.createElement('span'); badge.className = 'simulated-badge'; badge.textContent = 'SIMULADO'; item.querySelector('.mm-slot-copy').append(badge); }
        item.querySelector(".mm-slot-rank").textContent = (player.r6_rank || (player.is_simulated ? "Rank não aplicável" : "Rank não informado")) + " · " + (next.match ? (player.ready ? "Pronto" : "A confirmar") : "Na fila");
        item.querySelector(".mm-slot-wait").dataset.mmWaitStart = player.joined_at || "";
        item.querySelector(".mm-slot-wait").textContent = waitLabel(player.joined_at);
        item.querySelector(".mm-self-label").hidden = !(player.is_current || Number(player.user_id) === Number(next.account.id));
        const rankImage = item.querySelector(".mm-slot-rank-image");
        if (player.rank_image) { rankImage.src = player.rank_image; rankImage.hidden = false; }
      }
      fragment.appendChild(item);
    }
    byId("mm-roster").replaceChildren(fragment);
  }

  function render(next) {
    if(next.match && Number.isSafeInteger(Number(next.match.id)) && Number(next.match.id)>0){
      const destination="/partida/"+Number(next.match.id);
      if(matchDestination!==destination){
        void signal("Partida encontrada! Código " + next.match.match_code + ".",matchKey,String(next.match.id));
        matchDestination=destination;
        try{window.location.assign(destination);}catch(error){matchDestination=null;throw error;}
      }
      return;
    }
    matchDestination=null;
    const changed = JSON.stringify(state.queue) !== JSON.stringify(next.queue) || JSON.stringify(state.participants) !== JSON.stringify(next.participants);
    if (changed) renderRoster(next);
    if (next.queue_count !== state.queue_count) {
      byId("mm-announcement").textContent = next.queue_count + " de " + (next.queue_target || 10) + " jogadores na fila.";
    }
    const displayCount = next.match ? (next.participants?.length || 0) : next.queue_count;
    byId("mm-count").textContent = displayCount;
    const countTarget = byId("mm-count-target"); if (countTarget) countTarget.textContent = " / " + (next.match ? 10 : next.queue_target || 10);
    byId("mm-header-count").textContent = next.queue_count;
    const queuePopover = document.getElementById("queue-popover");
    if (queuePopover) { queuePopover.hidden = !displayCount; if (!displayCount) queuePopover.open = false; }
    const emptyCount = document.getElementById("mm-empty-count");
    if (emptyCount) emptyCount.hidden = !!displayCount;
    const viewState = next.match ? (next.match.status === "playing" ? "playing" : "match") :
      (next.restricted ? "restricted" : (!next.season ? "no-season" : (next.queue_busy ? "busy" : (next.is_in_queue ? "queued" : "idle"))));
    root.dataset.queueState = viewState;
    byId("mm-state-tag").textContent = presentations[viewState][0];
    byId("mm-season-state").textContent = next.season ? "Temporada ativa" : "Sem temporada ativa";
    byId("mm-season-badge").hidden = !next.season;
    byId("mm-season-name").textContent = next.season?.name || "Aguardando temporada";
    byId("mm-season-dates").textContent = next.season ?
      window.LobbyDates.format(next.season.starts_at, "date") + " — " +
      window.LobbyDates.format(next.season.ends_at, "date") : "Nenhum ciclo ativo no momento.";
    const queued = next.queue.find(player => player.is_current);
    byId("mm-personal-wait").dataset.mmWaitStart = queued?.joined_at || "";
    byId("mm-personal-wait").textContent = waitLabel(queued?.joined_at);
    renderWaits();
    const progressCount = next.match ? (next.queue_target || 10) : next.queue_count;
    byId("mm-progress").setAttribute("aria-valuenow", String(progressCount));
    const rosterTitle = byId("mm-roster-title"); if (rosterTitle) rosterTitle.textContent = next.match ? "Participantes da partida" : "Jogadores na fila";
    byId("mm-progress").querySelectorAll("span").forEach((segment, index) => {
      segment.dataset.filled = String(index < progressCount);
    });
    byId("mm-personal-status").textContent = next.match ? "Em uma partida" : (next.is_in_queue ? "Na fila" : "Fora da fila");
    root.querySelector(".mm-profile-status .mm-status-dot").dataset.mmInQueue = String(next.is_in_queue || !!next.match);
    byId("mm-emblem").dataset.searching = String(next.is_in_queue && !next.match);
    byId("mm-state-title").textContent = presentations[viewState][1];
    if(viewState === "queued" && next.queue_count === (next.queue_target || 10)) byId("mm-state-title").textContent = "Jogadores reunidos!";
    byId("mm-state-description").textContent = presentations[viewState][2];
    byId("mm-wait").textContent = next.match ? "Você tem uma partida ativa" : (next.is_in_queue ? "Aguardando outros jogadores" : "Entre quando estiver pronto");
    byId("mm-join-form").hidden = next.is_in_queue || !!next.match;
    byId("mm-leave-form").hidden = !next.is_in_queue;
    byId("mm-join").disabled = next.queue_count >= (next.queue_target || 10) || !!next.match || !!next.queue_busy || !!next.restricted || !next.season;
    byId("mm-restriction").hidden = !next.restricted;
    byId("mm-no-season").hidden = !!next.season;
    byId("mm-join").querySelector("span").textContent = next.queue_count >= (next.queue_target || 10) ? "Fila completa" : "Entrar na fila";
    if (next.is_in_queue && next.queue_count === (next.queue_target || 10)) {
      const signature=next.queue.map(player=>Number(player.player_id)).sort((a,b)=>a-b).join('-');
      void signal('Fila completa! Os dez jogadores estão reunidos.',queueKey,signature);
    } else storage.set(queueKey,null);
    root.querySelectorAll("[data-mm-stat]").forEach((element) => {
      element.textContent = Number(next.stats[element.dataset.mmStat]) || 0;
    });
    state = next;
  }

  let timer;
  let controller;
  let stopped = false;
  let failed = false;
  let queueRequest = false;
  let waitTimer;
  const refresh = byId("mm-refresh");
  const endpoint = new URL("/matchmaking", window.location.origin);
  endpoint.searchParams.set("snapshot", "1");

  async function poll() {
    window.clearTimeout(timer);
    if (controller || stopped || queueRequest) return;
    controller = new AbortController();
    const abort = window.setTimeout(() => controller?.abort(), 8000);
    refresh.setAttribute("aria-busy", "true");
    try {
      const response = await fetch(endpoint, { credentials: "same-origin", cache: "no-store", signal: controller.signal });
      if (response.status === 401 || response.status === 403) {
        stopped = true;
        toast("warning", "Seu acesso expirou ou não está disponível. Entre novamente para continuar.");
        byId("mm-live-label").textContent = "Acesso indisponível";
        byId("mm-join").disabled = true;
        byId("mm-leave").disabled = true;
        return;
      }
      if (!response.ok || !response.headers.get("content-type")?.includes("application/json")) throw new Error("Snapshot unavailable");
      const next = await response.json();
      if (!Array.isArray(next.queue) || !next.stats || !next.account) throw new Error("Invalid snapshot");
      render(next);
      failed = false;
      byId("mm-live-label").textContent = "Ao vivo";
      byId("mm-update-note").textContent = "A fila atualiza automaticamente a cada 4 segundos.";
    } catch (error) {
      if (queueRequest || stopped) return;
      byId("mm-live-label").textContent = "Reconectando";
      byId("mm-update-note").textContent = "Tentando restabelecer a atualização automática.";
      if (!failed) toast("warning", "Não foi possível atualizar a fila. Vamos tentar novamente.");
      failed = true;
    } finally {
      window.clearTimeout(abort);
      controller = null;
      refresh.setAttribute("aria-busy", "false");
      if (!stopped && !queueRequest) timer = window.setTimeout(poll, failed ? 8000 : 4000);
    }
  }

  refresh.addEventListener("click", () => { void poll(); });
  // Server-side validation and POST + CSRF remain authoritative.
  [byId("mm-join-form"), byId("mm-leave-form")].forEach((form) => {
    form.addEventListener("submit", event => {
      if (queueRequest) {event.preventDefault?.(); return;}
      queueRequest = true;
      window.clearTimeout(timer);
      controller?.abort();
      // Keep the submit button enabled so the browser can complete the POST.
      form.setAttribute("aria-busy", "true");
      form.querySelector("button span").textContent = form.id === "mm-leave-form" ? "Saindo…" : "Entrando…";
    });
  });
  window.addEventListener("pagehide", () => { stopped = true; window.clearTimeout(timer); window.clearTimeout(waitTimer); controller?.abort(); });
  window.addEventListener("pageshow", (event) => {
    if (!event.persisted) return;
    stopped = false; queueRequest = false;
    root.querySelectorAll("form[aria-busy]").forEach((form) => form.removeAttribute("aria-busy"));
    void poll();
    void updateWaits();
  });
  function updateWaits() {
    window.clearTimeout(waitTimer);
    if (stopped || queueRequest) return;
    renderWaits();
    waitTimer = window.setTimeout(updateWaits, 1000);
  }

  renderSound();
  render(state);
  updateWaits();
  timer = window.setTimeout(poll, 4000);
});
