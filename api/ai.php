<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/json.php';

$totalLogs = (int)$pdo->query("
    SELECT COUNT(*)
    FROM sysmon_logs
")->fetchColumn();

$suspicious = (int)$pdo->query("
    SELECT COUNT(*)
    FROM sysmon_logs
    WHERE status IN ('malicious', 'suspicious', 'warning')
")->fetchColumn();

$avgScore = (float)$pdo->query("
    SELECT COALESCE(AVG(risk_score), 0)
    FROM sysmon_logs
")->fetchColumn();

$critical = (int)$pdo->query("
    SELECT COUNT(*)
    FROM sysmon_logs
    WHERE risk_score >= 85
")->fetchColumn();

json_out([
    'ok' => true,
    'model' => [
        'name' => 'Isolation Forest + Logistic Regression',
        'detection_score' => round($avgScore, 2),
        'suspicious_events' => $suspicious,
        'critical_events' => $critical,
        'total_logs' => $totalLogs,
        'updated_at' => date('Y-m-d H:i:s'),
    ],
]);