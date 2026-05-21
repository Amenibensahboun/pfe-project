<?php
declare(strict_types=1);

/** Liste simple des hôtes pour les filtres (logs, etc.) */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/json.php';

$stmt = $pdo->query('SELECT id, hostname FROM hosts ORDER BY hostname');
json_out(['ok' => true, 'hosts' => $stmt->fetchAll()]);
?>