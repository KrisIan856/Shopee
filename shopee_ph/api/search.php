<?php
// api/search.php  —  Live search suggestions
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

try {
    $pdo = getDB();
    $st  = $pdo->prepare('SELECT id, name, price, emoji, color_class FROM products WHERE name LIKE ? AND is_active=1 ORDER BY sold_count DESC LIMIT 8');
    $st->execute(["%$q%"]);
    echo json_encode($st->fetchAll());
} catch (Exception $e) {
    echo json_encode([]);
}
