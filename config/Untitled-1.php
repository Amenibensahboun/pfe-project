<?php

require "db.php";

$stmt = $pdo->query("SELECT COUNT(*) as total FROM sysmon_logs");

$row = $stmt->fetch();

echo "Nombre de logs : ".$row['total'];