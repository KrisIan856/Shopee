<?php
// api/cart.php  —  Cart AJAX API
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in', 'redirect' => SITE_URL . '/login.php']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? ($_GET['action'] ?? '');
$uid    = $_SESSION['user_id'];
$pdo    = getDB();

try {
    switch ($action) {

        // ── ADD ──────────────────────────────────────────────
        case 'add':
            $pid = (int)($body['product_id'] ?? 0);
            $qty = max(1, (int)($body['quantity'] ?? 1));

            $st = $pdo->prepare('SELECT stock FROM products WHERE id=? AND is_active=1');
            $st->execute([$pid]);
            $prod = $st->fetch();
            if (!$prod) { echo json_encode(['error' => 'Product not found']); exit; }

            $pdo->prepare('
                INSERT INTO cart_items (user_id, product_id, quantity)
                VALUES (?,?,?)
                ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + ?, ?)
            ')->execute([$uid, $pid, $qty, $qty, $prod['stock']]);

            unset($_SESSION['cart_count']);
            $count = cartCount();
            echo json_encode(['success' => true, 'cart_count' => $count, 'message' => 'Added to cart!']);
            break;

        // ── UPDATE QTY ──────────────────────────────────────
        case 'update':
            $cartId = (int)($body['cart_id'] ?? 0);
            $qty    = max(1, (int)($body['quantity'] ?? 1));
            $pdo->prepare('UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?')
                ->execute([$qty, $cartId, $uid]);
            unset($_SESSION['cart_count']);
            echo json_encode(['success' => true]);
            break;

        // ── REMOVE ──────────────────────────────────────────
        case 'remove':
            $cartId = (int)($body['cart_id'] ?? 0);
            $pdo->prepare('DELETE FROM cart_items WHERE id=? AND user_id=?')
                ->execute([$cartId, $uid]);
            unset($_SESSION['cart_count']);
            $count = cartCount();
            echo json_encode(['success' => true, 'cart_count' => $count]);
            break;

        // ── VOUCHER ─────────────────────────────────────────
        case 'voucher':
            $code = strtoupper(trim($body['code'] ?? ''));
            $st   = $pdo->prepare('
                SELECT * FROM vouchers
                WHERE code=? AND is_active=1
                  AND (expires_at IS NULL OR expires_at > NOW())
                  AND used_count < max_uses
            ');
            $st->execute([$code]);
            $voucher = $st->fetch();

            if (!$voucher) {
                echo json_encode(['error' => true, 'message' => 'Invalid or expired voucher.']);
                exit;
            }
            // Calculate subtotal
            $st2 = $pdo->prepare('SELECT SUM(ci.quantity * p.price) FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.user_id=?');
            $st2->execute([$uid]);
            $subtotal = (float)$st2->fetchColumn();

            if ($subtotal < $voucher['min_spend']) {
                echo json_encode(['error' => true, 'message' => 'Min. spend ₱' . number_format($voucher['min_spend'], 0) . ' required.']);
                exit;
            }
            $discount = $voucher['discount_type'] === 'percent'
                ? $subtotal * $voucher['discount_value'] / 100
                : (float)$voucher['discount_value'];

            $_SESSION['voucher'] = ['code'=>$code,'discount'=>$discount];
            echo json_encode(['success'=>true, 'discount'=>round($discount), 'message'=>"Voucher applied! You save ₱".number_format($discount,0)]);
            break;

        // ── COUNT ──────────────────────────────────────────
        case 'count':
            echo json_encode(['count' => cartCount()]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
