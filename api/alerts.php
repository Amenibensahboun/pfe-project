<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/json.php';

$stmt = $pdo->query("
    SELECT
        id,
        timestamp AS created_at,
        host,
        event_id,
        status AS attack_type,
        risk_score,
        alert_level,
        analysis
    FROM sysmon_logs
    WHERE status IN ('warning', 'suspicious', 'malicious')
    ORDER BY timestamp DESC
    LIMIT 200
");

$rows = $stmt->fetchAll();

foreach ($rows as &$r) {

    // =========================
    // FORMAT SCORE
    // =========================

    $r['risk_score'] = (float)$r['risk_score'];

    // =========================
    // DETAILS
    // =========================

    $r['details'] = !empty($r['analysis'])
        ? $r['analysis']
        : 'Détection IA';

    // =========================
    // CRITICAL FLAG
    // =========================

    $r['critical'] = in_array(
        $r['alert_level'],
        ['high', 'critical']
    );

    // =========================
    // ALERT LEVEL DEFAULT
    // =========================

    if (empty($r['alert_level'])) {
        $r['alert_level'] = 'low';
    }

    // =========================
    // ATTACK TYPE DEFAULT
    // =========================

    if (empty($r['attack_type'])) {
        $r['attack_type'] = 'normal';
    }
}

unset($r);

// =========================
// JSON RESPONSE
// =========================

json_out([
    'ok' => true,
    'alerts' => $rows,
]);