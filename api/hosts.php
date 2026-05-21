<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/json.php';

try {
    $stmt = $pdo->query("
        SELECT
            h.id,
            h.hostname,
            h.ip_address,
            h.security_status,
            h.alert_count,
            h.isolated,
            h.last_seen,
            h.created_at,
            COUNT(s.id)                                         AS total_logs,
            COALESCE(MAX(s.risk_score), 0)                     AS max_risk,
            SUM(CASE WHEN s.status IN ('malicious','suspicious')
                     THEN 1 ELSE 0 END)                        AS detected_alerts
        FROM hosts h
        LEFT JOIN sysmon_logs s ON s.host = h.hostname
        GROUP BY
            h.id, h.hostname, h.ip_address,
            h.security_status, h.alert_count,
            h.isolated, h.last_seen, h.created_at
        ORDER BY h.hostname ASC
    ");

    $hosts = [];

    foreach ($stmt->fetchAll() as $r) {
        $detected = (int)$r['detected_alerts'];

        // Dynamic status based on real alert count
        if ($detected > 10)     $status = 'critical';
        elseif ($detected > 0)  $status = 'warning';
        else                    $status = 'normal';

        $hosts[] = [
            'id'              => (int)$r['id'],
            'hostname'        => $r['hostname'],
            // ✅ FIX: return null instead of string 'N/A' so JS can handle it cleanly
            'ip_address'      => $r['ip_address'] ?: null,
            'security_status' => $status,
            'alert_count'     => $detected,
            'isolated'        => (int)$r['isolated'],
            'last_seen'       => $r['last_seen'],
            'created_at'      => $r['created_at'],
            'total_logs'      => (int)$r['total_logs'],
            'max_risk'        => (float)$r['max_risk'],
        ];
    }

    json_out(['ok' => true, 'hosts' => $hosts]);

} catch (Exception $e) {
    json_out(['ok' => false, 'error' => $e->getMessage()], 500);
}