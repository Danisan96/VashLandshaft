<?php

require_once 'logger.php';
Logger::init();

$host = '84.252.74.178';
$dbname = 'land';
$username = 'root';
$password = '1scxdL5yUUcp';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Логирование успешного подключения
    Logger::logDb("Connected to database: $dbname");
} catch (PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
    Logger::logError($error);
    die($error);
}
?>