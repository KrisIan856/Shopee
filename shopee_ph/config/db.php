<?php
// ─────────────────────────────────────────────
//  config/db.php  —  Database connection (PDO)
//  Adjust HOST / USER / PASS to match your XAMPP
// ─────────────────────────────────────────────

define('DB_HOST', 'localhost');
define('DB_NAME', 'shopee_ph');
define('DB_USER', 'root');
define('DB_PASS', '');           // default XAMPP has no root password
define('DB_CHARSET', 'utf8mb4');

define('SITE_URL',  'http://localhost/shopee_ph');
define('SITE_NAME', 'Shopee PH');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Show friendly error instead of crashing
            die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
