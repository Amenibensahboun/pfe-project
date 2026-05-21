<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/json.php';

// Total logs
$totalLogs = (int)$pdo->query("SELECT COUNT(*) FROM sysmon_logs")->fetchColumn();

// Detected alerts
$totalAlerts = (int)$pdo->query("
    SELECT COUNT(*)
    FROM sysmon_logs
    WHERE status IN ('malicious', 'suspicious')
")->fetchColumn();

// Monitored hosts
$monitoredHosts = (int)$pdo->query("SELECT COUNT(*) FROM hosts")->fetchColumn();

// Event distribution (filtered to known Sysmon IDs)
$eventDist = $pdo->query("
    SELECT event_id, COUNT(*) AS cnt
    FROM sysmon_logs
    WHERE event_id IN (1, 3, 7, 11, 12, 13)
    GROUP BY event_id
    ORDER BY cnt DESC
")->fetchAll();

// Alert timeline — last 7 days
$alertTimeline = $pdo->query("
    SELECT DATE(timestamp) AS d, COUNT(*) AS cnt
    FROM sysmon_logs
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(timestamp)
    ORDER BY d ASC
")->fetchAll();

// Risk score distribution
$riskRow = $pdo->query("
    SELECT
        SUM(CASE WHEN risk_score < 30                    THEN 1 ELSE 0 END) AS low_risk,
        SUM(CASE WHEN risk_score BETWEEN 30 AND 59.99    THEN 1 ELSE 0 END) AS medium_risk,
        SUM(CASE WHEN risk_score BETWEEN 60 AND 79.99    THEN 1 ELSE 0 END) AS high_risk,
        SUM(CASE WHEN risk_score >= 80                   THEN 1 ELSE 0 END) AS critical_risk
    FROM sysmon_logs
")->fetch();

json_out([
    'ok'     => true,
    'stats'  => [
        'total_logs'       => $totalLogs,
        'alerts_detected'  => $totalAlerts,
        'alerts_open'      => $totalAlerts,
        'monitored_hosts'  => $monitoredHosts,
        'ai_status'        => 'active',
        'ai_model_name'    => 'IsolationForest + LogisticRegression',
        'ai_detection_score' => 95,
    ],
    'charts' => [
        'event_distribution' => $eventDist,
        'alert_timeline'     => $alertTimeline,
        'risk_distribution'  => [
            (int)($riskRow['low_risk']      ?? 0),
            (int)($riskRow['medium_risk']   ?? 0),
            (int)($riskRow['high_risk']     ?? 0),
            (int)($riskRow['critical_risk'] ?? 0),
        ],
    ],
]);