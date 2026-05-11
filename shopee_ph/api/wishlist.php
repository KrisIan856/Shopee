<?php
// api/wishlist.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in', 'redirect' => SITE_URL . '/login.php']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? 'toggle';
$pid    = (int)($body['product_id'] ?? 0);
$uid    = $_SESSION['user_id'];
$pdo    = getDB();

try {
    $st = $pdo->prepare('SELECT id FROM wishlists WHERE user_id=? AND product_id=?');
    $st->execute([$uid, $pid]);
    $exists = $st->fetchColumn();

    if ($exists) {
        $pdo->prepare('DELETE FROM wishlists WHERE user_id=? AND product_id=?')->execute([$uid,$pid]);
        echo json_encode(['success'=>true,'wishlisted'=>false,'message'=>'Removed from wishlist']);
    } else {
        $pdo->prepare('INSERT IGNORE INTO wishlists (user_id,product_id) VALUES (?,?)')->execute([$uid,$pid]);
        echo json_encode(['success'=>true,'wishlisted'=>true,'message'=>'Added to wishlist ❤️']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
