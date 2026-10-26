/* PasaBuy Admin — Global Confirm Modal + utilities */
(function () {
  'use strict';

  const SELECTOR = 'form[data-confirm], form[data-confirm-title]';

  const ICONS = {
    default: '<path d="M12 9v4"/><path d="M12 17h.01"/><circle cx="12" cy="12" r="10"/>',
    danger: '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/>',
    success: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
    warning: '<path d="M12 9v4"/><path d="M12 17h.01"/><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/>',
  };

  const VARIANTS = {
    default: {
      circle: 'bg-slate-100 text-slate-600',
      confirm: 'bg-slate-900 hover:bg-slate-800 focus:ring-slate-900/20',
      label: 'Confirm',
    },
    danger: {
      circle: 'bg-red-100 text-red-600',
      confirm: 'bg-red-600 hover:bg-red-700 focus:ring-red-500/30',
      label: 'Delete',
    },
    warning: {
      circle: 'bg-amber-100 text-amber-600',
      confirm: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500/30',
      label: 'Continue',
    },
    success: {
      circle: 'bg-brand-100 text-brand-600',
      confirm: 'bg-brand-600 hover:bg-brand-700 focus:ring-brand-500/30',
      label: 'Confirm',
    },
  };

  class ConfirmModal {
    constructor() {
      this.dialog = document.getElementById('confirm-modal');
      if (!this.dialog) return;

      this.iconWrap = this.dialog.querySelector('[data-confirm-icon]');
      this.iconSvg = this.iconWrap?.querySelector('svg');
      this.titleEl = this.dialog.querySelector('[data-confirm-title]');
      this.bodyEl = this.dialog.querySelector('[data-confirm-body]');
      this.btnCancel = this.dialog.querySelector('[data-confirm-cancel]');
      this.btnOk = this.dialog.querySelector('[data-confirm-ok]');
      this.btnLabel = this.btnOk.querySelector('[data-confirm-ok-label]');
      this.spinner = this.btnOk.querySelector('[data-confirm-spinner]');

      this.btnCancel.addEventListener('click', () => this.dialog.close('cancel'));
      this.btnOk.addEventListener('click', () => this._confirm());

      // Click outside the card closes (cancel)
      this.dialog.addEventListener('click', (e) => {
        const rect = this.dialog.querySelector('[data-confirm-card]').getBoundingClientRect();
        const inCard = e.clientX >= rect.left && e.clientX <= rect.right
          && e.clientY >= rect.top && e.clientY <= rect.bottom;
        if (!inCard) this.dialog.close('cancel');
      });

      this._onConfirm = null;
    }

    open({
      title = 'Are you sure?',
      body = '',
      variant = 'default',
      confirmLabel,
      cancelLabel = 'Cancel',
      onConfirm = null,
    } = {}) {
      return new Promise((resolve) => {
        const v = VARIANTS[variant] || VARIANTS.default;

        // Icon
        this.iconWrap.className =
          'mx-auto flex w-11 h-11 items-center justify-center rounded-full ' + v.circle;
        if (this.iconSvg) {
          this.iconSvg.innerHTML = ICONS[variant] || ICONS.default;
        }

        // Text
        this.titleEl.textContent = title;
        this.bodyEl.textContent = body;
        this.bodyEl.classList.toggle('hidden', !body);

        // Buttons
        this.btnLabel.textContent = confirmLabel || v.label;
        this.btnCancel.textContent = cancelLabel;
        this.btnOk.className =
          'inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium ' +
          'text-white transition-colors focus:outline-none focus:ring-4 ' + v.confirm;
        this._setLoading(false);

        this._onConfirm = typeof onConfirm === 'function' ? onConfirm : null;

        // Resolve on close
        const onClose = () => {
          this.dialog.removeEventListener('close', onClose);
          resolve(this.dialog.returnValue === 'ok');
        };
        this.dialog.addEventListener('close', onClose);

        this.dialog.showModal();

        // Autofocus the confirm button
        requestAnimationFrame(() => this.btnOk.focus());
      });
    }

    async _confirm() {
      if (this._onConfirm) {
        this._setLoading(true);
        try {
          await this._onConfirm();
        } catch (err) {
          console.error('[Confirm] onConfirm threw:', err);
          this._setLoading(false);
          return; // keep modal open so user sees failure context
        }
        this._setLoading(false);
      }
      this.dialog.close('ok');
    }

    _setLoading(loading) {
      this.btnOk.disabled = loading;
      this.btnCancel.disabled = loading;
      this.spinner.classList.toggle('hidden', !loading);
      this.btnLabel.classList.toggle('opacity-70', loading);
      this.btnOk.style.pointerEvents = loading ? 'none' : '';
    }
  }

  /* ---- Public API ---- */
  const instance = new ConfirmModal();

  const Confirm = {
    open(opts) { return instance.open(opts); },

    // Shorthand for simple destructive prompts (no form)
    async ask({
      title,
      body = '',
      variant = 'danger',
      confirmLabel,
      cancelLabel = 'Cancel',
      action,        // function returning a Promise
    }) {
      return instance.open({
        title, body, variant, confirmLabel, cancelLabel,
        onConfirm: action,
      });
    },
  };

  window.Confirm = Confirm;

  /* ---- Auto-attach to forms with [data-confirm] ---- */
  document.addEventListener('submit', async (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!form.matches(SELECTOR)) return;

    e.preventDefault();

    const variant = form.dataset.confirmVariant || 'danger';
    const title = form.dataset.confirmTitle || form.dataset.confirm || 'Are you sure?';
    const body = form.dataset.confirmBody || '';
    const okLabel = form.dataset.confirmLabel;
    const cancelLbl = form.dataset.confirmCancel;

    const ok = await Confirm.open({
      title, body, variant,
      confirmLabel: okLabel,
      cancelLabel: cancelLbl,
    });

    if (ok) {
      // form.submit() bypasses this listener (per HTML spec)
      form.submit();
    }
  });

  /* ---- Optional: decorate links with [data-confirm] (GET nav) ---- */
  document.addEventListener('click', async (e) => {
    const a = e.target.closest('a[data-confirm], a[data-confirm-title]');
    if (!a) return;
    if (a.dataset._confirming === '1') return;

    e.preventDefault();
    const ok = await Confirm.open({
      title: a.dataset.confirmTitle || a.dataset.confirm || 'Are you sure?',
      body: a.dataset.confirmBody || '',
      variant: a.dataset.confirmVariant || 'default',
      confirmLabel: a.dataset.confirmLabel,
    });
    if (ok) {
      a.dataset._confirming = '1';
      window.location.href = a.href;
    }
  });
  /* ---- Sidebar drawer (mobile) ---- */
  (function () {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const toggles = document.querySelectorAll('[data-sidebar-toggle]');
    if (!sidebar) return;

    const isOpen = () => document.body.classList.contains('sidebar-open');

    const setOpen = (open) => {
      document.body.classList.toggle('sidebar-open', open);
      toggles.forEach(b => b.setAttribute('aria-expanded', open ? 'true' : 'false'));
    };

    toggles.forEach(btn =>
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        setOpen(!isOpen());
      })
    );

    backdrop?.addEventListener('click', () => setOpen(false));

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && isOpen()) setOpen(false);
    });

    // Close when a nav link is tapped (mobile only)
    sidebar.addEventListener('click', (e) => {
      if (window.innerWidth < 1024 && e.target.closest('a')) setOpen(false);
    });

    // If the viewport grows to desktop while open, reset state
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 1024 && isOpen()) setOpen(false);
    });
  })()


  /* ============================================================
     Admin notifications — poll /pasabuy/notifications/summary
     Updates the bell badge, sidebar badges, and dropdown panel.
     ============================================================ */
  const Notif = (function () {
    const POLL_MS = 15000;
    let timer = null;
    let latestData = null;

    const bell = document.getElementById('notif-bell');
    const badge = document.getElementById('notif-bell-badge');
    const panel = document.getElementById('notif-panel');
    const list = document.getElementById('notif-list');
    const readAll = document.getElementById('notif-read-all');

    if (!bell) return { init: () => { } };

    function fmtTime(iso) {
      if (!iso) return '';
      const d = new Date(iso.replace(' ', 'T'));
      const diff = (Date.now() - d.getTime()) / 1000;
      if (diff < 60) return 'just now';
      if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
      if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
      if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
      return d.toLocaleDateString();
    }

    function updateBadges(data) {
      const total = data.total || 0;

      if (badge) {
        if (total > 0) {
          badge.textContent = total > 99 ? '99+' : String(total);
          badge.classList.remove('hidden');
          badge.classList.add('flex');
        } else {
          badge.classList.add('hidden');
          badge.classList.remove('flex');
        }
      }

      document.querySelectorAll('.notif-sidebar-badge').forEach(el => {
        const cat = el.dataset.category;
        let count = 0;

        if (cat === 'dashboard') {
          count = total;
        } else {
          count = (data.by_category && data.by_category[cat]) || 0;
        }

        if (count > 0) {
          el.textContent = count > 99 ? '99+' : String(count);
          el.classList.remove('hidden');
          el.classList.add('inline-flex');
        } else {
          el.classList.add('hidden');
          el.classList.remove('inline-flex');
        }
      });
    }

    function renderList(items) {
      if (!list) return;
      if (!items || items.length === 0) {
        list.innerHTML =
          '<div class="px-4 py-10 text-center text-sm text-slate-400">' +
          'Nothing new right now' +
          '</div>';
        return;
      }

      list.innerHTML = items.map(it => {
        const unreadDot = it.read
          ? ''
          : '<span class="inline-block w-2 h-2 rounded-full bg-brand-500 shrink-0 mt-1.5"></span>';

        const wrapper = it.link
          ? `<a href="${it.link}" data-notif-id="${it.id}" class="notif-item block px-4 py-3 hover:bg-slate-50 transition-colors">`
          : `<div data-notif-id="${it.id}" class="notif-item block px-4 py-3">`;

        const closer = it.link ? '</a>' : '</div>';

        return wrapper +
          '<div class="flex items-start gap-3">' +
          unreadDot +
          '<div class="flex-1 min-w-0">' +
          `<p class="text-[13px] font-medium text-slate-900 leading-snug">${escapeHtml(it.title)}</p>` +
          (it.body
            ? `<p class="text-xs text-slate-500 mt-0.5 truncate">${escapeHtml(it.body)}</p>`
            : '') +
          `<p class="text-[11px] text-slate-400 mt-1">${fmtTime(it.created_at)}</p>` +
          '</div>' +
          '</div>' +
          closer;
      }).join('');

      // Mark-read on click
      list.querySelectorAll('.notif-item').forEach(el => {
        el.addEventListener('click', async (e) => {
          const id = el.dataset.notifId;
          const href = el.getAttribute('href');

          if (!id) return;

          // Ctrl/Cmd/Shift/middle-click → let the browser open in new tab,
          // but still fire the mark-read so the badge updates.
          const openingNewTab = e.ctrlKey || e.metaKey || e.shiftKey || e.button === 1;

          if (href && !openingNewTab) {
            e.preventDefault();
          }

          try {
            await fetch('/pasabuy/notifications/' + id + '/read', {
              method: 'POST',
              headers: { 'Accept': 'application/json' },
              // keepalive guarantees the request finishes even after navigation
              keepalive: true,
            });
          } catch (_) { }

          if (href && !openingNewTab) {
            window.location.href = href;
          }

          // Refresh the badge immediately without waiting for the poll
          fetchSummary();
        });
      });
    }

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str == null ? '' : String(str);
      return div.innerHTML;
    }

    async function fetchSummary() {
      try {
        const res = await fetch('/pasabuy/notifications/summary', {
          headers: { 'Accept': 'application/json' },
        });
        if (!res.ok) return;
        const json = await res.json();
        if (!json || !json.success) return;

        latestData = json.data;
        updateBadges(latestData);
        renderList(latestData.latest);
      } catch (_) { }
    }

    function togglePanel(e) {
      if (e) e.stopPropagation();
      if (!panel) return;
      const isHidden = panel.classList.contains('hidden');
      if (isHidden) {
        panel.classList.remove('hidden');
        fetchSummary();
      } else {
        panel.classList.add('hidden');
      }
    }

    function attachEvents() {
      bell.addEventListener('click', togglePanel);

      document.addEventListener('click', (e) => {
        if (!panel || panel.classList.contains('hidden')) return;
        if (panel.contains(e.target) || bell.contains(e.target)) return;
        panel.classList.add('hidden');
      });

      if (readAll) {
        readAll.addEventListener('click', async (e) => {
          e.stopPropagation();
          try {
            await fetch('/pasabuy/notifications/read-all', {
              method: 'POST',
              headers: { 'Accept': 'application/json' },
            });
          } catch (_) { }
          fetchSummary();
        });
      }

      // Pause polling when tab is hidden; resume + fetch on focus
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          if (timer) { clearInterval(timer); timer = null; }
        } else {
          fetchSummary();
          startPolling();
        }
      });
    }

    function startPolling() {
      if (timer) return;
      timer = setInterval(fetchSummary, POLL_MS);
    }

    function init() {
      attachEvents();
      fetchSummary();
      startPolling();
    }

    return { init };
  })();

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => Notif.init());
  } else {
    Notif.init();
  }

})();