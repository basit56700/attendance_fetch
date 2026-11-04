/**
 * Enterprise Light / Dark Mode Theme Manager
 * Follows Modern Web Guidance: handles FOUC prevention, meta color-scheme sync, and cross-tab storage events.
 */

(function () {
  'use strict';

  function getActiveTheme() {
    try {
      const saved = localStorage.getItem('theme');
      if (saved === 'light' || saved === 'dark') {
        return saved;
      }
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    } catch (e) {
      return 'dark';
    }
  }

  function applyTheme(theme) {
    const isDark = theme === 'dark';
    const docEl = document.documentElement;

    docEl.classList.toggle('dark', isDark);
    docEl.classList.toggle('light', !isDark);

    // Sync meta color-scheme tag
    const meta = document.querySelector('meta[name="color-scheme"]');
    if (meta) {
      meta.content = isDark ? 'dark' : 'light';
    }

    // Update all toggle buttons in DOM (header, login, settings)
    document.querySelectorAll('.theme-toggle-btn').forEach((btn) => {
      const sunIcon = btn.querySelector('.theme-sun-icon');
      const moonIcon = btn.querySelector('.theme-moon-icon');
      const label = btn.querySelector('.theme-label');

      if (isDark) {
        if (sunIcon) sunIcon.classList.remove('hidden');
        if (moonIcon) moonIcon.classList.add('hidden');
        if (label) label.textContent = 'Light Mode';
        btn.setAttribute('title', 'Switch to Light Mode');
      } else {
        if (sunIcon) sunIcon.classList.add('hidden');
        if (moonIcon) moonIcon.classList.remove('hidden');
        if (label) label.textContent = 'Dark Mode';
        btn.setAttribute('title', 'Switch to Dark Mode');
      }
    });
  }

  window.toggleTheme = function () {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    const target = current === 'dark' ? 'light' : 'dark';

    try {
      localStorage.setItem('theme', target);
    } catch (e) {}

    applyTheme(target);
  };

  // Sync on DOMContentLoaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      applyTheme(getActiveTheme());
    });
  } else {
    applyTheme(getActiveTheme());
  }

  // Cross-tab synchronization
  window.addEventListener('storage', function (e) {
    if (e.key === 'theme' && e.newValue) {
      applyTheme(e.newValue);
    }
  });

  // Listen to OS preference changes if no manual override is saved
  try {
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', function (e) {
        if (!localStorage.getItem('theme')) {
          applyTheme(e.matches ? 'dark' : 'light');
        }
      });
    }
  } catch (e) {}
})();
