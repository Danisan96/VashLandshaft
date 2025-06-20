<?php
session_start();
require_once 'logger.php';

$host = '84.252.74.178';
$dbname = 'land';
$username = 'landadmin';
$password = 'Danisan';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    Logger::logError("Database connection failed: " . $e->getMessage());
    die("Ошибка подключения к базе данных");
}

// Константы путей
define('AVATAR_UPLOAD_DIR', __DIR__ . '/uploads/avatars/');
define('SERVICE_UPLOAD_DIR', __DIR__ . '/uploads/services/');

// Создаем директории при необходимости
if (!is_dir(AVATAR_UPLOAD_DIR)) mkdir(AVATAR_UPLOAD_DIR, 0777, true);
if (!is_dir(SERVICE_UPLOAD_DIR)) mkdir(SERVICE_UPLOAD_DIR, 0777, true);
?>