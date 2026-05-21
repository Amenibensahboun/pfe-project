<?php
declare(strict_types=1);
/** Point d’entrée — tableau de bord SIEM (HTML + CSS + JS, données via API PHP) */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SIEM — Détection ransomware</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
  <script src="assets/js/app.js" defer></script>
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <div class="sidebar__brand">
        <h1><span class="icon" aria-hidden="true">🛡</span> SIEM Dashboard</h1>
        <p class="sidebar__tagline">Plateforme de renseignement sur la sécurité</p>
      </div>
      <ul class="sidebar__nav">
        <li><a href="#dashboard" data-page="dashboard" class="active"><span class="icon">▣</span> Vue d’ensemble</a></li>
        <li><a href="#logs" data-page="logs"><span class="icon">≡</span> Journaux Sysmon</a></li>
        <li><a href="#alerts" data-page="alerts"><span class="icon">!</span> Alertes</a></li>
        <li><a href="#hosts" data-page="hosts"><span class="icon">◫</span> Hôtes</a></li>
        <li><a href="#ai" data-page="ai"><span class="icon">◇</span> Analyse IA</a></li>
        <li><a href="#settings" data-page="settings"><span class="icon">⚙</span> Paramètres</a></li>
      </ul>
      <div class="sidebar__health">
        <h3 class="sidebar__health-title">Santé système</h3>
        <div class="meter">
          <div class="meter__label"><span>CPU</span><span id="meter-cpu-val">—</span></div>
          <div class="meter__bar"><div class="meter__fill meter__fill--cpu" id="meter-cpu" style="width:0%"></div></div>
        </div>
        <div class="meter">
          <div class="meter__label"><span>Mémoire</span><span id="meter-mem-val">—</span></div>
          <div class="meter__bar"><div class="meter__fill meter__fill--mem" id="meter-mem" style="width:0%"></div></div>
        </div>
        <div class="meter">
          <div class="meter__label"><span>Stockage</span><span id="meter-disk-val">—</span></div>
          <div class="meter__bar"><div class="meter__fill meter__fill--disk" id="meter-disk" style="width:0%"></div></div>
        </div>
      </div>
    </aside>

    <div class="main">
      <header class="topbar">
        <div class="topbar__brand">
          <span class="topbar__logo" aria-hidden="true">🛡</span>
          <div class="topbar__brand-text">
            <strong class="topbar__name">SIEM Dashboard</strong>
            <span class="topbar__tagline">Security Intelligence Platform</span>
          </div>
        </div>
        <nav class="topbar__nav" aria-label="Navigation principale">
          <a href="#dashboard" data-page="dashboard" class="active">Vue d’ensemble</a>
          <a href="#logs" data-page="logs">Journaux Sysmon</a>
          <a href="#alerts" data-page="alerts">Alertes</a>
          <a href="#hosts" data-page="hosts">Hôtes</a>
          <a href="#ai" data-page="ai">Analyse IA</a>
          <a href="#settings" data-page="settings">Paramètres</a>
        </nav>
        <div class="topbar__meta">
          <span class="badge badge--live badge--pulse" title="Collecte active"><span class="badge__dot" aria-hidden="true"></span> Système actif</span>
          <span class="topbar__clock" id="clock-utc">—</span>
        </div>
      </header>

      <main class="content">
        <h1 class="content__title" id="content-title">Vue d’ensemble</h1>
        <p class="content__subtitle" id="content-subtitle" hidden></p>
        <p id="last-updated" class="settings-note content__meta-line">—</p>

        <!-- Dashboard -->
        <section id="page-dashboard" class="page active">
          <div class="cards">
            <div class="card">
              <div class="card__label">Journaux analysés</div>
              <p class="card__value" id="stat-logs">—</p>
              <div class="card__trend trend--up">Temps réel (Sysmon)</div>
            </div>
            <div class="card">
              <div class="card__label">Alertes détectées</div>
              <p class="card__value" id="stat-alerts">—</p>
              <div class="card__trend" id="stat-alerts-sub" style="color:var(--muted)">—</div>
            </div>
            <div class="card">
              <div class="card__label">Hôtes surveillés</div>
              <p class="card__value" id="stat-hosts">—</p>
            </div>
            <div class="card">
              <div class="card__label">État du modèle IA</div>
              <p class="card__value" id="stat-ai">—</p>
              <div class="card__trend" id="stat-ai-sub" style="color:var(--muted);text-transform:none;letter-spacing:0">—</div>
            </div>
          </div>
          <div class="charts-row">
            <div class="chart-card">
              <h2>Répartition des Event ID Sysmon <span class="badge badge--live" style="font-size:0.65rem;margin-left:0.5rem">Live</span></h2>
              <div class="chart-wrap"><canvas id="chart-events"></canvas></div>
            </div>
            <div class="chart-card">
              <h2>Menaces / scores (aperçu)</h2>
              <div class="chart-wrap"><canvas id="chart-risk"></canvas></div>
            </div>
          </div>
          <div class="charts-row charts-row--triple">
            <div class="chart-card" style="grid-column: 1 / -1; max-width: 100%;">
              <h2>Chronologie des alertes (7 jours)</h2>
              <div class="chart-wrap"><canvas id="chart-timeline"></canvas></div>
            </div>
          </div>
        </section>

        <!-- Logs -->
        <section id="page-logs" class="page">
          <div id="logs-banner" class="banner-critical">
            Au moins un journal a un score de risque &gt; 90&nbsp;% — considérez l’<strong>isolation de l’hôte</strong> et une analyse approfondie.
          </div>
          <div class="panel">
            <div class="panel__head">
              <h2>Journaux Sysmon</h2>
              <div class="filters">
                <label>Hôte <select id="filter-host"><option value="">Tous</option></select></label>
                <label>Event ID <input type="number" id="filter-event" placeholder="ex. 1" min="0"></label>
                <label>Du <input type="date" id="filter-date-from"></label>
                <label>Au <input type="date" id="filter-date-to"></label>
                <button type="button" class="btn btn--primary" id="btn-apply-filters">Filtrer</button>
              </div>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Horodatage</th>
                    <th>Hôte</th>
                    <th>Event ID</th>
                    <th>Processus</th>
                    <th>Score risque</th>
                    <th>Statut</th>
                  </tr>
                </thead>
                <tbody id="logs-tbody"><tr><td colspan="6" class="empty-state">Chargement…</td></tr></tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Alerts -->
        <section id="page-alerts" class="page">
          <div class="alerts-kpis" aria-label="Synthèse des alertes">
            <article class="alert-kpi alert-kpi--danger">
              <span class="alert-kpi__label">Alertes actives</span>
              <span class="alert-kpi__value" id="alerts-kpi-active">—</span>
            </article>
            <article class="alert-kpi alert-kpi--warning">
              <span class="alert-kpi__label">En analyse</span>
              <span class="alert-kpi__value" id="alerts-kpi-review">—</span>
            </article>
            <article class="alert-kpi alert-kpi--success">
              <span class="alert-kpi__label">Résolues</span>
              <span class="alert-kpi__value" id="alerts-kpi-resolved">—</span>
            </article>
          </div>
          <div class="siem-panel siem-panel--flush">
            <div class="table-wrap table-wrap--siem">
              <table class="data-table data-table--alerts">
                <thead>
                  <tr>
                    <th>Horodatage</th>
                    <th>Hôte</th>
                    <th>Type d’attaque</th>
                    <th>Description</th>
                    <th>Score menace</th>
                    <th>Statut</th>
                    <th class="data-table__actions-col">Actions</th>
                  </tr>
                </thead>
                <tbody id="alerts-tbody"><tr><td colspan="7" class="empty-state">Chargement…</td></tr></tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- Hosts -->
        <section id="page-hosts" class="page">
          <div class="panel">
            <div class="panel__head">
              <h2>Hôtes surveillés</h2>
              <p class="settings-note" style="margin:0">Score &gt; 90&nbsp;%&nbsp;: statut <strong>critique</strong> et isolation recommandée.</p>
            </div>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nom d’hôte</th>
                    <th>Adresse IP</th>
                    <th>Statut sécurité</th>
                    <th>Nombre d’alertes</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="hosts-tbody"><tr><td colspan="5" class="empty-state">Chargement…</td></tr></tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- AI -->
        <section id="page-ai" class="page">
          <div class="panel">
            <div class="panel__head"><h2>Analyse IA</h2></div>
            <div id="ai-content"><p class="empty-state">Chargement…</p></div>
          </div>
        </section>

        <!-- Settings -->
        <section id="page-settings" class="page">
          <div class="settings-stack">
            <div class="settings-card">
              <div class="settings-card__head">
                <span class="settings-card__icon settings-card__icon--blue" aria-hidden="true">🗄</span>
                <h2>Collecte des données</h2>
              </div>
              <div class="settings-block">
                <div class="settings-row">
                  <div class="settings-row__text">
                    <strong>Actualisation auto du tableau de bord</strong>
                    <span class="settings-row__hint">Actualiser automatiquement les données</span>
                  </div>
                  <label class="toggle">
                    <input type="checkbox" id="set-auto-refresh" checked aria-label="Actualisation automatique">
                    <span class="toggle__ui" aria-hidden="true"></span>
                  </label>
                </div>
                <div class="settings-field">
                  <label for="set-refresh-interval">Intervalle d’actualisation (secondes)</label>
                  <input type="number" id="set-refresh-interval" class="settings-input settings-input--narrow" min="5" max="3600" value="30">
                </div>
                <div class="settings-field">
                  <label for="set-sysmon-filter">Filtre d’événements Sysmon</label>
                  <select id="set-sysmon-filter" class="settings-select">
                    <option value="all">Tous les événements</option>
                    <option value="process">Événements processus</option>
                    <option value="network">Événements réseau</option>
                    <option value="file">Événements fichier</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="settings-card">
              <div class="settings-card__head">
                <span class="settings-card__icon settings-card__icon--purple" aria-hidden="true">🛡</span>
                <h2>Détection IA</h2>
              </div>
              <div class="settings-block">
                <div class="settings-field">
                  <label for="set-ai-threshold">Seuil d’alerte (0,0 – 1,0)</label>
                  <input type="number" id="set-ai-threshold" class="settings-input settings-input--narrow" min="0" max="1" step="0.01" value="0.75">
                  <p class="settings-field__hint">Événements avec un score supérieur à ce seuil génèreront une alerte.</p>
                </div>
                <div class="settings-field">
                  <label for="set-model-frequency">Fréquence de mise à jour du modèle</label>
                  <select id="set-model-frequency" class="settings-select">
                    <option value="realtime">Temps réel</option>
                    <option value="hourly">Horaire</option>
                    <option value="daily" selected>Quotidien</option>
                    <option value="weekly">Hebdomadaire</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="settings-card">
              <div class="settings-card__head">
                <span class="settings-card__icon settings-card__icon--bell" aria-hidden="true">🔔</span>
                <h2>Notifications d’alerte</h2>
              </div>

              <div class="settings-block">
                <div class="settings-row">
                  <div class="settings-row__text">
                    <strong>Alertes e-mail</strong>
                    <span class="settings-row__hint">Recevoir des alertes par e-mail</span>
                  </div>
                  <label class="toggle">
                    <input type="checkbox" id="set-email-enabled" checked aria-label="Activer les alertes e-mail">
                    <span class="toggle__ui" aria-hidden="true"></span>
                  </label>
                </div>
                <div class="settings-field">
                  <label for="set-email">Adresse e-mail</label>
                  <inpsut type="email" id="set-email" class="settings-input" placeholder="admin@company.com" autocomplete="email">
                </div>
              </div>

              <div class="settings-block settings-block--divider">
                <div class="settings-row">
                  <div class="settings-row__text">
                    <strong>Alertes Slack</strong>
                    <span class="settings-row__hint">Envoyer des alertes sur Slack</span>
                  </div>
                  <label class="toggle">
                    <input type="checkbox" id="set-slack-enabled" aria-label="Activer les alertes Slack">
                    <span class="toggle__ui" aria-hidden="true"></span>
                  </label>
                </div>
                <div class="settings-field">
                  <label for="set-slack-url">URL du webhook Slack</label>
                  <input type="url" id="set-slack-url" class="settings-input" placeholder="https://hooks.slack.com/services/…">
                </div>
              </div>

              <div class="settings-actions">
                <button type="button" class="btn btn--settings-save" id="btn-settings-save">
                  <span class="btn__icon" aria-hidden="true">💾</span>
                  Enregistrer les paramètres
                </button>
                <button type="button" class="btn btn--settings-reset" id="btn-settings-reset">
                  <span class="btn__icon" aria-hidden="true">↺</span>
                  Réinitialiser
                </button>
              </div>
            </div>

            <p class="settings-page__devnote settings-note">
              Connexion base de données&nbsp;: variables <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code> ou <code>config/db.php</code> — schéma <code>sql/schema.sql</code>.
            </p>
            <p id="settings-toast" class="settings-toast" role="status" aria-live="polite"></p>
          </div>
        </section>
      </main>
    </div>
  </div>

  <div class="modal-backdrop" id="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal">
      <h3 id="modal-title">Détail</h3>
      <pre id="modal-body"></pre>
      <div class="modal__actions">
        <button type="button" class="btn btn--ghost" id="modal-close">Fermer</button>
      </div>
    </div>
  </div>
</body>
</html>
