<?php
require 'config.php';

try {
    $stmt = $pdo->query("SELECT 1 AS test");
    $result = $stmt->fetch();
    echo "Database connection successful! Test value: " . $result['test'];
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>