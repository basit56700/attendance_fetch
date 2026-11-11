/**
 * Shared Live Auto-Refresh Controller for Attendance ERP Views
 * Keeps Dashboard, Attendance Logs, and Machines Hub automatically synchronized
 * on a configurable refresh duration (Off, 5s, 10s, 30s, 60s, or Custom).
 */
(function (window) {
  'use strict';

  const STORAGE_KEY = 'gcs_live_refresh_seconds';

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function renderPunchBadge(punchType, typeRemarks, punchLabel) {
    const t = Number(punchType);
    let cls = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
    let defaultLabel = typeRemarks || `Status ${t}`;

    if (t === 0) {
      cls = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
      defaultLabel = 'Check-In (IN)';
    } else if (t === 1) {
      cls = 'bg-red-500/10 text-red-400 border-red-500/20';
      defaultLabel = 'Check-Out (OUT)';
    }

    const label = punchLabel && typeRemarks && !String(punchLabel).includes(typeRemarks)
      ? `${punchLabel} (${typeRemarks})`
      : (punchLabel || defaultLabel);

    return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border ${cls}">${escapeHtml(label)}</span>`;
  }

  function getSavedSeconds(defaultSeconds) {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved !== null && saved !== '' && !Number.isNaN(Number(saved))) {
        const val = Number(saved);
        return val <= 0 ? 0 : Math.max(3, val);
      }
    } catch (e) {}
    const def = Number(defaultSeconds);
    return Number.isNaN(def) ? 5 : (def <= 0 ? 0 : Math.max(3, def));
  }

  function saveSeconds(seconds) {
    try {
      localStorage.setItem(STORAGE_KEY, String(seconds));
    } catch (e) {}
  }

  function ensureOption(selectEl, seconds) {
    const valStr = String(seconds);
    let exists = Array.from(selectEl.options).some((opt) => opt.value === valStr);
    if (!exists && seconds > 0) {
      const customOpt = document.createElement('option');
      customOpt.value = valStr;
      customOpt.textContent = `Live: ${seconds}s`;
      const customTrigger = selectEl.querySelector('option[value="custom"]');
      if (customTrigger) {
        selectEl.insertBefore(customOpt, customTrigger);
      } else {
        selectEl.appendChild(customOpt);
      }
    }
    selectEl.value = valStr;
  }

  function initLiveRefresh(options) {
    const selectEl = document.getElementById(options.selectId);
    const dotEl = document.getElementById(options.dotId);
    const statusEl = document.getElementById(options.statusId);
    if (!selectEl) return null;

    let currentSeconds = getSavedSeconds(options.defaultSeconds ?? 5);
    let timerId = null;
    let inFlight = false;

    function updateStatusUi(text) {
      if (statusEl) {
        statusEl.textContent = text;
      }
      if (dotEl) {
        if (currentSeconds > 0) {
          dotEl.className = 'w-2 h-2 rounded-full bg-emerald-400 animate-pulse inline-block';
        } else {
          dotEl.className = 'w-2 h-2 rounded-full bg-slate-500 inline-block';
        }
      }
    }

    async function runTick() {
      if (inFlight || document.hidden) return;
      inFlight = true;
      try {
        const url = typeof options.url === 'function' ? options.url() : options.url;
        const res = await fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });
        if (!res.ok) return;
        const data = await res.json();
        options.onData(data);
        if (currentSeconds > 0) {
          updateStatusUi(`Synced ${data.server_time || new Date().toLocaleTimeString()}`);
        }
      } catch (e) {
        // Keep silent on transient network hiccup so next tick recovers smoothly
      } finally {
        inFlight = false;
      }
    }

    function restartTimer() {
      if (timerId) {
        clearInterval(timerId);
        timerId = null;
      }
      if (currentSeconds > 0) {
        updateStatusUi(`Live (${currentSeconds}s)`);
        timerId = setInterval(runTick, currentSeconds * 1000);
      } else {
        updateStatusUi('Paused');
      }
    }

    ensureOption(selectEl, currentSeconds);
    restartTimer();

    selectEl.addEventListener('change', function () {
      if (selectEl.value === 'custom') {
        const entered = window.prompt('Enter auto-refresh interval in seconds (minimum 3 seconds, 0 to pause):', String(currentSeconds || 5));
        if (entered === null) {
          ensureOption(selectEl, currentSeconds);
          return;
        }
        const parsed = parseInt(entered, 10);
        if (Number.isNaN(parsed)) {
          ensureOption(selectEl, currentSeconds);
          return;
        }
        currentSeconds = parsed <= 0 ? 0 : Math.max(3, Math.min(3600, parsed));
        saveSeconds(currentSeconds);
        ensureOption(selectEl, currentSeconds);
        restartTimer();
        if (currentSeconds > 0) runTick();
        return;
      }

      const nextVal = parseInt(selectEl.value, 10);
      currentSeconds = Number.isNaN(nextVal) || nextVal <= 0 ? 0 : nextVal;
      saveSeconds(currentSeconds);
      restartTimer();
      if (currentSeconds > 0) runTick();
    });

    document.addEventListener('visibilitychange', function () {
      if (!document.hidden && currentSeconds > 0) {
        runTick();
      }
    });

    return {
      refreshNow: runTick,
      setIntervalSeconds: function (secs) {
        currentSeconds = secs <= 0 ? 0 : Math.max(3, secs);
        saveSeconds(currentSeconds);
        ensureOption(selectEl, currentSeconds);
        restartTimer();
      },
    };
  }

  window.GCSLiveRefresh = {
    escapeHtml,
    renderPunchBadge,
    init: initLiveRefresh,
  };
})(window);
