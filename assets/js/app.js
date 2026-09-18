/**
 * SIWES Tracker — front-end behaviour
 * (theme toggle, mobile sidebar, password visibility)
 */
(function () {
  'use strict';

  var d = document;

  /* ---- Dark / light theme ------------------------------------ */
  var root = d.documentElement;
  var toggle = d.getElementById('themeToggle');
  var stored = null;
  try { stored = localStorage.getItem('siwes-theme'); } catch (e) {}

  function applyTheme(theme, btn) {
    root.setAttribute('data-theme', theme);
    if (btn) btn.innerHTML = theme === 'dark'
      ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/></svg>'
      : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/></svg>';
  }

  if (stored === 'dark' || stored === 'light') {
    applyTheme(stored, toggle);
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      applyTheme(next, toggle);
      try { localStorage.setItem('siwes-theme', next); } catch (e) {}
    });
  }

  /* ---- Mobile sidebar ----------------------------------------- */
  var sidebar = d.getElementById('sidebar');
  var openBtn = d.getElementById('sidebarToggle');
  var backdrop = d.getElementById('sidebarBackdrop');

  function setSidebar(open) {
    d.body.classList.toggle('sidebar-open', open);
    if (openBtn) openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (backdrop) backdrop.hidden = !open;
  }

  if (openBtn && sidebar) {
    openBtn.addEventListener('click', function () {
      setSidebar(!d.body.classList.contains('sidebar-open'));
    });
  }
  if (backdrop) {
    backdrop.addEventListener('click', function () { setSidebar(false); });
  }
  d.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') setSidebar(false);
  });

  /* ---- Show / hide password ----------------------------------- */
  d.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = d.getElementById(btn.getAttribute('data-toggle-password'));
      if (!input) return;
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      btn.innerHTML = showing
        ? '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m2 2 20 20"/><path d="M6.7 6.7A10.5 10.5 0 0 0 2 12s3.5 7 10 7c1.9 0 3.6-.5 5-1.3"/><path d="M9.9 5.2A10.5 10.5 0 0 1 22 12s-1.3 2.6-3.8 4.5"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
      btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });
  });
})();
