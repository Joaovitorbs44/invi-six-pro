"use strict";

// One presentation controller for both native dialogs and existing overlay modals.
(() => {
  const records = new WeakMap();
  const active = [];
  const reduced = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  function panel(modal) {
    return modal.matches("dialog") ? modal : modal.querySelector('[role="dialog"]') || modal.firstElementChild;
  }
  function visibleFields(modal) {
    return [...modal.querySelectorAll('button,a[href],input:not([type="hidden"]),select,textarea,[tabindex]')]
      .filter(field => !field.disabled && field.tabIndex !== -1 && field.getClientRects().length);
  }
  function open(modal, options = {}) {
    if (!modal || records.has(modal)) return;
    const record = {trigger: options.trigger || document.activeElement, inert: [], overflow: document.body.style.overflow, closing: null};
    records.set(modal, record);
    modal.dataset.uiModal = "true";
    delete modal.dataset.motion;
    if (modal.matches("dialog")) modal.showModal();
    else {
      modal.hidden = false;
      modal.classList.remove("hidden");
      modal.classList.add("flex");
      modal.setAttribute("aria-hidden", "false");
    }
    // Inert only the siblings along the path, never the modal or its ancestors.
    let branch = modal;
    while (branch.parentElement && branch !== document.body) {
      for (const sibling of branch.parentElement.children) {
        if (sibling === branch || sibling.matches("script,style,link,#toast-container")) continue;
        record.inert.push([sibling, sibling.inert]);
        sibling.inert = true;
      }
      branch = branch.parentElement;
    }
    document.body.style.overflow = "hidden";
    active.push(modal);
    const content = panel(modal);
    if (!reduced() && content?.animate) {
      record.animation = content.animate([
        {opacity: 0, transform: "translateY(12px) scale(.98)"},
        {opacity: 1, transform: "translateY(0) scale(1)"},
      ], {duration: 250, easing: "cubic-bezier(0.22, 1, 0.36, 1)"});
    }
    const field = options.focus || visibleFields(modal)[0] || content;
    if (field && !field.hasAttribute("tabindex") && field === content) field.tabIndex = -1;
    field?.focus({preventScroll: true});
    if (modal.matches("dialog") && !modal.dataset.uiCancelBound) {
      modal.dataset.uiCancelBound = "true";
      modal.addEventListener("cancel", event => {event.preventDefault(); void close(modal);});
    }
  }
  function close(modal) {
    const record = records.get(modal);
    if (!record) return Promise.resolve();
    if (record.closing) return record.closing;
    record.animation?.cancel();
    modal.dataset.motion = "closing";
    record.closing = new Promise(resolve => {
      const finish = () => {
        if (modal.matches("dialog")) modal.close();
        else {
          modal.hidden = true;
          modal.classList.add("hidden");
          modal.classList.remove("flex");
          modal.setAttribute("aria-hidden", "true");
        }
        for (const [sibling, inert] of record.inert) sibling.inert = inert;
        document.body.style.overflow = record.overflow;
        active.splice(active.indexOf(modal), 1);
        records.delete(modal);
        delete modal.dataset.motion;
        record.trigger?.focus({preventScroll: true});
        resolve();
      };
      window.setTimeout(finish, reduced() ? 0 : 250);
    });
    return record.closing;
  }
  function keydown(event) {
    const modal = active.at(-1);
    if (!modal) return false;
    if (event.key === "Escape") {event.preventDefault(); void close(modal); return true;}
    if (event.key === "Tab") {
      const fields = visibleFields(modal);
      if (!fields.length) {event.preventDefault(); return true;}
      if (event.shiftKey && (document.activeElement === fields[0] || !modal.contains(document.activeElement))) {
        event.preventDefault(); fields.at(-1).focus();
      } else if (!event.shiftKey && (document.activeElement === fields.at(-1) || !modal.contains(document.activeElement))) {
        event.preventDefault(); fields[0].focus();
      }
      return true;
    }
    return false;
  }
  window.LobbyUI = {openModal: open, closeModal: close, modalKeydown: keydown};
})();

document.addEventListener("DOMContentLoaded", () => {
  const navigation = document.querySelector("[data-navigation]");
  const profile = document.querySelector("[data-profile-menu]");
  const desktop = window.matchMedia("(min-width: 1280px)");
  const profileTrigger = profile?.querySelector("summary");
  const profilePanel = profile?.querySelector(".site-profile-panel");
  let profileAnimation;
  let profileClosing = false;
  let profileTimer;
  function setProfile(open) {
    if (!profile || !profilePanel) return;
    profileAnimation?.cancel();
    window.clearTimeout(profileTimer);
    profileClosing = !open;
    profileTrigger.setAttribute("aria-expanded", String(open));
    if (open) profile.open = true;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches || !profilePanel.animate) {
      profile.open = open;
      profileClosing = false;
      return;
    }
    profileAnimation = profilePanel.animate(open ? [
      {opacity:0,transform:"translateY(8px)"}, {opacity:1,transform:"translateY(0)"},
    ] : [
      {opacity:1,transform:"translateY(0)"}, {opacity:0,transform:"translateY(8px)"},
    ], {duration:200,easing:"cubic-bezier(.22,1,.36,1)"});
    const finish = () => {
      profile.open = open;
      profileClosing = false;
      profileAnimation?.cancel();
      profileAnimation = null;
    };
    profileTimer = window.setTimeout(finish, 200);
  }
  profileTrigger?.addEventListener("click", event => {
    event.preventDefault();
    setProfile(!profile.open || profileClosing);
  });
  function syncNavigation() {
    if (!navigation) return;
    navigation.open = desktop.matches;
    setProfile(false);
  }
  syncNavigation();
  desktop.addEventListener("change", syncNavigation);
  document.addEventListener("click", (event) => {
    if (profile?.open && !profileClosing && !profile.contains(event.target)) setProfile(false);
    if (navigation?.open && !desktop.matches && !navigation.contains(event.target)) navigation.open = false;
  });
  document.addEventListener("keydown", (event) => {
    if (window.LobbyUI.modalKeydown(event) || event.key !== "Escape") return;
    if (profile?.open) {
      setProfile(false);
      profile.querySelector("summary").focus();
    } else if (navigation?.open && !desktop.matches) {
      navigation.open = false;
      navigation.querySelector("summary").focus();
    }
  });
  navigation?.addEventListener("click", (event) => {
    if (event.target.closest("a[href]") && !desktop.matches) navigation.open = false;
  });
  document.addEventListener("invalid", event => {
    const field = event.target;
    if (!field.closest?.(".ui-field") || !field.id) return;
    event.preventDefault();
    field.setAttribute("aria-invalid", "true");
    let error = document.getElementById(field.id + "-validation");
    if (!error) {
      error = document.createElement("span");
      error.id = field.id + "-validation";
      error.className = "ui-field-error";
      error.setAttribute("role", "alert");
      field.closest(".ui-field").appendChild(error);
      field.dataset.uiDescription = field.getAttribute("aria-describedby") || "";
      field.setAttribute("aria-describedby", (field.dataset.uiDescription + " " + error.id).trim());
    }
    error.textContent = field.validationMessage;
  }, true);
  const clearFieldError = event => {
    const field = event.target;
    if (field.dataset?.uiDescription === undefined || !field.validity?.valid) return;
    document.getElementById(field.id + "-validation")?.remove();
    field.removeAttribute("aria-invalid");
    if (field.dataset.uiDescription) field.setAttribute("aria-describedby", field.dataset.uiDescription);
    else field.removeAttribute("aria-describedby");
    delete field.dataset.uiDescription;
  };
  document.addEventListener("input", clearFieldError);
  document.addEventListener("change", clearFieldError);
});
