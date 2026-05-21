<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/json.php';

// ✅ FIX: check if shuffle_status column exists before selecting it
//    to avoid fatal SQL errors on databases that don't have it yet.
$columns = [];
$colStmt = $pdo->query("SHOW COLUMNS FROM sysmon_logs LIKE 'shuffle_status'");
$hasShuffleStatus = $colStmt->rowCount() > 0;

$selectShuffle = $hasShuffleStatus ? ", shuffle_status" : ", NULL AS shuffle_status";

$stmt = $pdo->query("
    SELECT
        id,
        timestamp,
        host,
        event_id,
        process_name,
        risk_score,
        status,
        alert_level,
        analysis
        {$selectShuffle}
    FROM sysmon_logs
    ORDER BY timestamp DESC
    LIMIT 500
");

$rows = $stmt->fetchAll();
$logs = [];

foreach ($rows as $r) {
    $logs[] = [
        'id'             => (int)$r['id'],
        'logged_at'      => $r['timestamp'],
        'hostname'       => $r['host'],
        'event_id'       => (int)$r['event_id'],
        'process_name'   => $r['process_name'] ?? 'N/A',
        'risk_score'     => (float)($r['risk_score'] ?? 0),
        'status'         => $r['status']         ?? 'unknown',
        'alert_level'    => $r['alert_level']    ?? 'low',
        'analysis'       => $r['analysis']       ?? '',
        'shuffle_status' => $r['shuffle_status'] ?? 'none',
    ];
}

json_out([
    'ok'   => true,
    'logs' => $logs,
]);