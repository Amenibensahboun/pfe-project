(function () {
  'use strict';

  // ─────────────────────────────────────────────
  // API
  // ─────────────────────────────────────────────

  const API = {
    stats:    'api/stats.php',
    logs:     'api/logs.php',
    alerts:   'api/alerts.php',
    hosts:    'api/hosts.php',
    ai:       'api/ai.php',
    hostList: 'api/hosts_list.php',
  };

  // ─────────────────────────────────────────────
  // EVENT NAMES
  // ─────────────────────────────────────────────

  const eventNames = {
    1:  'Process Create',
    3:  'Network Connection',
    7:  'Image Loaded',
    11: 'File Created',
    12: 'Registry Create',
    13: 'Registry Set',
  };

  // ─────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────

  function $(sel) {
    return document.querySelector(sel);
  }

  async function fetchJson(url) {

    const r = await fetch(url);

    if (!r.ok) {
      throw new Error('HTTP ' + r.status + ' — ' + url);
    }

    const json = await r.json();

    if (json.ok === false) {
      throw new Error(json.error || 'API error');
    }

    return json;
  }

  function formatDateTime(s) {

    if (!s) return '—';

    return new Date(
      s.replace(' ', 'T')
    ).toLocaleString('fr-FR');
  }

  // ─────────────────────────────────────────────
  // BADGES
  // ─────────────────────────────────────────────

  function badgeStatus(status) {

    const map = {
      malicious:  'badge--danger',
      suspicious: 'badge--warning',
      warning:    'badge--warning',
      normal:     'badge--success',
      unknown:    'badge--muted',
    };

    const cls = map[status] || 'badge--muted';

    return `
      <span class="badge ${cls}">
        ${status || '—'}
      </span>
    `;
  }

  function badgeLevel(level) {

    const map = {
      critical: 'badge--danger',
      high:     'badge--danger',
      medium:   'badge--warning',
      low:      'badge--success'
    };

    return `
      <span class="badge ${map[level] || 'badge--muted'}">
        ${level || '—'}
      </span>
    `;
  }

  // ─────────────────────────────────────────────
  // CLOCK
  // ─────────────────────────────────────────────

  function startClock() {

    function tick() {

      const el = document.getElementById('clock-utc');

      if (!el) return;

      const now = new Date();

      const formatted =
        now.toLocaleDateString('fr-FR', {
          weekday: 'short',
          day: '2-digit',
          month: 'short',
          year: 'numeric'
        }) +
        ' ' +
        now.toLocaleTimeString('fr-FR');

      el.textContent = formatted;
    }

    tick();

    setInterval(tick, 1000);
  }

  // ─────────────────────────────────────────────
  // CHARTS
  // ─────────────────────────────────────────────

  let chartEvents = null;
  let chartRisk = null;
  let chartTimeline = null;

  function buildCharts(charts) {

    const COLORS = [
      '#6366f1',
      '#22d3ee',
      '#f59e0b',
      '#10b981',
      '#ef4444',
      '#a78bfa'
    ];

    // EVENT DISTRIBUTION

    const evCtx = $('#chart-events');

    if (evCtx && charts.event_distribution?.length) {

      if (chartEvents) chartEvents.destroy();

      chartEvents = new Chart(evCtx, {

        type: 'doughnut',

        data: {

          labels: charts.event_distribution.map(
            r => eventNames[r.event_id] || 'Event ' + r.event_id
          ),

          datasets: [{
            data: charts.event_distribution.map(r => r.cnt),
            backgroundColor: COLORS
          }]
        },

        options: {
          plugins: {
            legend: {
              position: 'bottom'
            }
          },
          maintainAspectRatio: false
        }
      });
    }

    // RISK DISTRIBUTION

    const riskCtx = $('#chart-risk');

    if (riskCtx && charts.risk_distribution) {

      if (chartRisk) chartRisk.destroy();

      chartRisk = new Chart(riskCtx, {

        type: 'bar',

        data: {

          labels: [
            'Faible',
            'Moyen',
            'Élevé',
            'Critique'
          ],

          datasets: [{
            label: 'Logs',
            data: charts.risk_distribution,
            backgroundColor: [
              '#10b981',
              '#f59e0b',
              '#f97316',
              '#ef4444'
            ]
          }]
        },

        options: {
          plugins: {
            legend: {
              display: false
            }
          },
          maintainAspectRatio: false
        }
      });
    }

    // TIMELINE

    const tlCtx = $('#chart-timeline');

    if (tlCtx && charts.alert_timeline?.length) {

      if (chartTimeline) chartTimeline.destroy();

      chartTimeline = new Chart(tlCtx, {

        type: 'line',

        data: {

          labels: charts.alert_timeline.map(r => r.d),

          datasets: [{
            label: 'Alertes',
            data: charts.alert_timeline.map(r => r.cnt),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.15)',
            fill: true,
            tension: 0.4
          }]
        },

        options: {
          plugins: {
            legend: {
              display: false
            }
          },
          maintainAspectRatio: false
        }
      });
    }
  }

  // ─────────────────────────────────────────────
  // DASHBOARD
  // ─────────────────────────────────────────────

  async function loadDashboard() {

    try {

      const data = await fetchJson(API.stats);

      const s = data.stats || {};

      $('#stat-logs').textContent =
        (s.total_logs ?? 0).toLocaleString();

      $('#stat-alerts').textContent =
        (s.alerts_detected ?? 0).toLocaleString();

      $('#stat-hosts').textContent =
        (s.monitored_hosts ?? 0).toLocaleString();

      $('#stat-ai').textContent =
        s.ai_status === 'active'
          ? '✅ Actif'
          : '⚠ Inactif';

      const aiSub = $('#stat-ai-sub');

      if (aiSub) {
        aiSub.textContent = s.ai_model_name || '';
      }

      const alertSub = $('#stat-alerts-sub');

      if (alertSub) {
        alertSub.textContent =
          `${s.alerts_open ?? 0} ouverte(s)`;
      }

      const lu = $('#last-updated');

      if (lu) {
        lu.textContent =
          'Mis à jour : ' +
          new Date().toLocaleString('fr-FR');
      }

      if (data.charts) {
        buildCharts(data.charts);
      }

    } catch (e) {

      console.error('Dashboard error:', e);
    }
  }

  // ─────────────────────────────────────────────
  // LOGS
  // ─────────────────────────────────────────────

  let allLogs = [];

  async function loadLogs() {

    const tbody = $('#logs-tbody');

    if (!tbody) return;

    tbody.innerHTML =
      '<tr><td colspan="6" class="empty-state">Chargement…</td></tr>';

    try {

      const data = await fetchJson(API.logs);

      allLogs = Array.isArray(data)
        ? data
        : (data.logs || []);

      renderLogs(allLogs);

      populateHostFilter(allLogs);

      const banner = $('#logs-banner');

      if (banner) {

        banner.style.display =
          allLogs.some(
            l => Number(l.risk_score || 0) > 90
          )
            ? ''
            : 'none';
      }

    } catch (e) {

      tbody.innerHTML =
        '<tr><td colspan="6" class="empty-state">Erreur de chargement des logs.</td></tr>';

      console.error('Logs error:', e);
    }
  }

  function renderLogs(logs) {

    const tbody = $('#logs-tbody');

    if (!tbody) return;

    if (!logs.length) {

      tbody.innerHTML =
        '<tr><td colspan="6" class="empty-state">Aucun log trouvé.</td></tr>';

      return;
    }

    tbody.innerHTML = logs.map(l => `

      <tr>
        <td>${formatDateTime(l.logged_at || l.timestamp)}</td>

        <td>${l.hostname || l.host || '—'}</td>

        <td title="${eventNames[l.event_id] || ''}">
          ${eventNames[l.event_id] || l.event_id}
        </td>

        <td>${l.process_name || '—'}</td>

        <td>${Number(l.risk_score || 0).toFixed(1)}%</td>

        <td>${badgeStatus(l.status)}</td>
      </tr>

    `).join('');
  }

  function populateHostFilter(logs) {

    const sel = $('#filter-host');

    if (!sel) return;

    const hosts = [
      ...new Set(
        logs.map(
          l => l.hostname || l.host
        ).filter(Boolean)
      )
    ].sort();

    sel.innerHTML =
      '<option value="">Tous</option>' +
      hosts.map(
        h => `<option value="${h}">${h}</option>`
      ).join('');
  }

  // ─────────────────────────────────────────────
  // ALERTS
  // ─────────────────────────────────────────────

  async function loadAlerts() {

    const tbody = $('#alerts-tbody');

    if (!tbody) return;

    tbody.innerHTML =
      '<tr><td colspan="7" class="empty-state">Chargement…</td></tr>';

    try {

      const data = await fetchJson(API.alerts);

      const alerts = data.alerts || [];

      const active =
        alerts.filter(a =>
          a.alert_level === 'high' ||
          a.alert_level === 'critical'
        ).length;

      const review =
        alerts.filter(a =>
          a.alert_level === 'medium'
        ).length;

      const resolved =
        alerts.filter(a =>
          a.alert_level === 'low'
        ).length;

      $('#alerts-kpi-active').textContent = active;
      $('#alerts-kpi-review').textContent = review;
      $('#alerts-kpi-resolved').textContent = resolved;

      if (!alerts.length) {

        tbody.innerHTML =
          '<tr><td colspan="7" class="empty-state">Aucune alerte.</td></tr>';

        return;
      }

      tbody.innerHTML = alerts.map(a => `

        <tr class="${a.critical ? 'row--critical' : ''}">

          <td>${formatDateTime(a.created_at)}</td>

          <td>${a.host || '—'}</td>

          <td>${a.attack_type || '—'}</td>

          <td>${a.details || a.analysis || '—'}</td>

          <td>${Number(a.risk_score || 0).toFixed(1)}%</td>

          <td>${badgeLevel(a.alert_level)}</td>

          <td>
            <button
              class="btn btn--ghost btn--sm"
              onclick="openModal(${JSON.stringify(JSON.stringify(a))})"
            >
              Détail
            </button>
          </td>

        </tr>

      `).join('');

    } catch (e) {

      tbody.innerHTML =
        '<tr><td colspan="7" class="empty-state">Erreur de chargement des alertes.</td></tr>';

      console.error('Alerts error:', e);
    }
  }

  // ─────────────────────────────────────────────
  // HOSTS
  // ─────────────────────────────────────────────

  async function loadHosts() {

    const tbody = $('#hosts-tbody');

    if (!tbody) return;

    tbody.innerHTML =
      '<tr><td colspan="5" class="empty-state">Chargement…</td></tr>';

    try {

      const data = await fetchJson(API.hosts);

      const hosts = data.hosts || [];

      if (!hosts.length) {

        tbody.innerHTML =
          '<tr><td colspan="5" class="empty-state">Aucun hôte.</td></tr>';

        return;
      }

      tbody.innerHTML = hosts.map(h => {

        const statusMap = {
          critical: 'badge--danger',
          warning:  'badge--warning',
          normal:   'badge--success'
        };

        const statusBadge = `
          <span class="badge ${statusMap[h.security_status] || 'badge--muted'}">
            ${h.security_status}
          </span>
        `;

        return `

          <tr>

            <td>${h.hostname}</td>

            <td>${h.ip_address || '—'}</td>

            <td>${statusBadge}</td>

            <td>${h.alert_count ?? 0}</td>

            <td>
              <button
                class="btn btn--ghost btn--sm"
                onclick="openModal(${JSON.stringify(JSON.stringify(h))})"
              >
                Détail
              </button>
            </td>

          </tr>
        `;
      }).join('');

    } catch (e) {

      tbody.innerHTML =
        '<tr><td colspan="5" class="empty-state">Erreur de chargement des hôtes.</td></tr>';

      console.error('Hosts error:', e);
    }
  }

  // ─────────────────────────────────────────────
  // AI PANEL
  // ─────────────────────────────────────────────

  async function loadAI() {

    const container = $('#ai-content');

    if (!container) return;

    try {

      const data = await fetchJson(API.ai);

      const m = data.model || {};

      container.innerHTML = `

        <div class="cards" style="margin-bottom:1.5rem">

          <div class="card">
            <div class="card__label">Modèle</div>
            <p class="card__value" style="font-size:1rem">
              ${m.name || '—'}
            </p>
          </div>

          <div class="card">
            <div class="card__label">Score moyen</div>
            <p class="card__value">
              ${Number(m.detection_score || 0).toFixed(2)}%
            </p>
          </div>

          <div class="card">
            <div class="card__label">Événements suspects</div>
            <p class="card__value">
              ${(m.suspicious_events ?? 0).toLocaleString()}
            </p>
          </div>

          <div class="card">
            <div class="card__label">Logs analysés</div>
            <p class="card__value">
              ${(m.total_logs ?? 0).toLocaleString()}
            </p>
          </div>

        </div>

        <p class="settings-note">
          Dernière mise à jour :
          ${m.updated_at || '—'}
        </p>
      `;

    } catch (e) {

      container.innerHTML =
        '<p class="empty-state">Erreur de chargement.</p>';

      console.error('AI error:', e);
    }
  }

  // ─────────────────────────────────────────────
  // MODAL
  // ─────────────────────────────────────────────

  window.openModal = function (jsonStr) {

    const backdrop = $('#modal-backdrop');
    const body = $('#modal-body');

    if (!backdrop || !body) return;

    try {

      body.textContent =
        JSON.stringify(
          JSON.parse(jsonStr),
          null,
          2
        );

    } catch {

      body.textContent = jsonStr;
    }

    backdrop.classList.add('active');
  };

  function initModal() {

    const backdrop = $('#modal-backdrop');
    const btnClose = $('#modal-close');

    if (btnClose) {

      btnClose.addEventListener('click', () => {
        backdrop.classList.remove('active');
      });
    }

    if (backdrop) {

      backdrop.addEventListener('click', e => {

        if (e.target === backdrop) {
          backdrop.classList.remove('active');
        }
      });
    }
  }

  // ─────────────────────────────────────────────
  // METERS
  // ─────────────────────────────────────────────

  function updateMeters() {

    function rand(min, max) {
      return Math.floor(
        Math.random() * (max - min + 1)
      ) + min;
    }

    const cpu = rand(20, 85);
    const mem = rand(40, 75);
    const disk = rand(30, 60);

    function setMeter(id, valId, value) {

      const bar = $(id);
      const label = $(valId);

      if (bar) {
        bar.style.width = value + '%';
      }

      if (label) {
        label.textContent = value + '%';
      }
    }

    setMeter('#meter-cpu', '#meter-cpu-val', cpu);
    setMeter('#meter-mem', '#meter-mem-val', mem);
    setMeter('#meter-disk', '#meter-disk-val', disk);
  }

  // ─────────────────────────────────────────────
  // SETTINGS
  // ─────────────────────────────────────────────

  function initSettings() {

    const btnSave = $('#btn-settings-save');
    const btnReset = $('#btn-settings-reset');
    const toast = $('#settings-toast');

    function showToast(msg, ok = true) {

      if (!toast) return;

      toast.textContent = msg;

      toast.className =
        'settings-toast ' +
        (ok
          ? 'settings-toast--ok'
          : 'settings-toast--err');

      setTimeout(() => {

        toast.textContent = '';

        toast.className = 'settings-toast';

      }, 3000);
    }

    if (btnSave) {

      btnSave.addEventListener('click', () => {

        localStorage.setItem(
          'siem_refresh',
          $('#set-refresh-interval')?.value || '30'
        );

        localStorage.setItem(
          'siem_threshold',
          $('#set-ai-threshold')?.value || '0.75'
        );

        showToast('✅ Paramètres enregistrés.');
      });
    }

    if (btnReset) {

      btnReset.addEventListener('click', () => {

        if ($('#set-refresh-interval')) {
          $('#set-refresh-interval').value = '30';
        }

        if ($('#set-ai-threshold')) {
          $('#set-ai-threshold').value = '0.75';
        }

        localStorage.removeItem('siem_refresh');
        localStorage.removeItem('siem_threshold');

        showToast('↺ Réinitialisé.', false);
      });
    }
  }

  // ─────────────────────────────────────────────
  // AUTO REFRESH
  // ─────────────────────────────────────────────

  let refreshTimer = null;

  function scheduleRefresh(page) {

    clearInterval(refreshTimer);

    const toggle = $('#set-auto-refresh');

    if (!toggle?.checked) return;

    const secs =
      parseInt(
        $('#set-refresh-interval')?.value || '30'
      ) * 1000;

    refreshTimer = setInterval(() => {
      loadPage(page);
    }, secs);
  }

  // ─────────────────────────────────────────────
  // PAGE LOADER
  // ─────────────────────────────────────────────

  const PAGE_TITLES = {
    dashboard: 'Vue d’ensemble',
    logs:      'Journaux Sysmon',
    alerts:    'Alertes',
    hosts:     'Hôtes surveillés',
    ai:        'Analyse IA',
    settings:  'Paramètres',
  };

  function loadPage(page) {

    switch (page) {

      case 'dashboard':
        loadDashboard();
        break;

      case 'logs':
        loadLogs();
        break;

      case 'alerts':
        loadAlerts();
        break;

      case 'hosts':
        loadHosts();
        break;

      case 'ai':
        loadAI();
        break;
    }
  }

  // ─────────────────────────────────────────────
  // NAVIGATION
  // ─────────────────────────────────────────────

  function showPage(page) {

    document.querySelectorAll('.page')
      .forEach(p => p.classList.remove('active'));

    const target =
      document.getElementById('page-' + page);

    if (!target) return;

    target.classList.add('active');

    const titleEl = $('#content-title');

    if (titleEl) {
      titleEl.textContent =
        PAGE_TITLES[page] || page;
    }

    document.querySelectorAll('[data-page]')
      .forEach(l => {

        l.classList.toggle(
          'active',
          l.getAttribute('data-page') === page
        );
      });

    loadPage(page);

    scheduleRefresh(page);
  }

  function initNavigation() {

    document.querySelectorAll('[data-page]')
      .forEach(link => {

        link.addEventListener('click', function (e) {

          e.preventDefault();

          showPage(
            this.getAttribute('data-page')
          );
        });
      });

    const btnFilter = $('#btn-apply-filters');

    if (btnFilter) {

      btnFilter.addEventListener(
        'click',
        applyLogFilters
      );
    }
  }

  // ─────────────────────────────────────────────
  // LOG FILTERS
  // ─────────────────────────────────────────────

  function applyLogFilters() {

    const host =
      ($('#filter-host')?.value || '')
      .toLowerCase();

    const eventId =
      parseInt($('#filter-event')?.value || '');

    const dateFrom =
      $('#filter-date-from')?.value;

    const dateTo =
      $('#filter-date-to')?.value;

    const filtered = allLogs.filter(l => {

      const lHost =
        (l.hostname || l.host || '')
        .toLowerCase();

      if (host && lHost !== host) {
        return false;
      }

      if (
        !isNaN(eventId) &&
        eventId &&
        l.event_id !== eventId
      ) {
        return false;
      }

      if (dateFrom) {

        const d = new Date(
          (l.logged_at || l.timestamp || '')
          .replace(' ', 'T')
        );

        if (d < new Date(dateFrom)) {
          return false;
        }
      }

      if (dateTo) {

        const d = new Date(
          (l.logged_at || l.timestamp || '')
          .replace(' ', 'T')
        );

        if (
          d >
          new Date(dateTo + 'T23:59:59')
        ) {
          return false;
        }
      }

      return true;
    });

    renderLogs(filtered);
  }

  // ─────────────────────────────────────────────
  // BOOTSTRAP
  // ─────────────────────────────────────────────

  document.addEventListener(
    'DOMContentLoaded',
    function () {

      startClock();

      initNavigation();

      initModal();

      initSettings();

      updateMeters();

      setInterval(updateMeters, 5000);

      showPage('dashboard');
    }
  );

})();