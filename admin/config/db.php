<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('資料庫連線失敗，請確認 XAMPP 的 MySQL 服務已啟動，且已匯入 admin/sql/schema.sql 建立 ' . DB_NAME . ' 資料庫。<br>錯誤訊息：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
