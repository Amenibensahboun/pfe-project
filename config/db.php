<?php

$dbHost = '192.168.1.241';
$dbName = 'soc_logs';
$dbUser = 'root';
$dbPass = '1234';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$dbCharset";

$options = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];

try {

$pdo = new PDO($dsn,$dbUser,$dbPass,$options);

} catch(PDOException $e){

echo $e->getMessage();

}
?>