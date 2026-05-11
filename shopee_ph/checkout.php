<?php
// checkout.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
requireLogin();

$uid  = $_SESSION['user_id'];
$pdo  = getDB();
$user = currentUser();

$selectedIds = [];
if (!empty($_GET['cart_ids'])) {
    $selectedIds = array_filter(array_map('intval', explode(',', $_GET['cart_ids'])), fn($id) => $id > 0);
}

function fetchCartItems(PDO $pdo, int $uid, array $selectedIds): array {
    if (empty($selectedIds)) {
        $st = $pdo->prepare('SELECT ci.*, p.name, p.price, p.emoji, p.color_class, p.free_shipping, p.seller_id, p.location
            FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.user_id=? ORDER BY ci.added_at DESC');
        $st->execute([$uid]);
    } else {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $st = $pdo->prepare("SELECT ci.*, p.name, p.price, p.emoji, p.color_class, p.free_shipping, p.seller_id, p.location
            FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.user_id=? AND ci.id IN ($placeholders) ORDER BY ci.added_at DESC");
        $st->execute(array_merge([$uid], $selectedIds));
    }
    return $st->fetchAll();
}

$cartItems = fetchCartItems($pdo, $uid, $selectedIds);

if (empty($cartItems)) {
    header('Location: ' . SITE_URL . '/cart.php'); exit;
}

$subtotal = array_sum(array_map(fn($i) => $i['price']*$i['quantity'], $cartItems));
$voucher  = $_SESSION['voucher'] ?? null;
$discount = $voucher ? $voucher['discount'] : 0;
$total    = max(0, $subtotal - $discount);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $addr   = trim($_POST['address'] ?? '');
    $method = in_array($_POST['payment']??'', ['COD','GCash','Visa','Online Banking']) ? $_POST['payment'] : 'COD';
    $selectedIds = [];
    if (!empty($_POST['selected_cart_ids'])) {
        $selectedIds = array_filter(array_map('intval', explode(',', $_POST['selected_cart_ids'])), fn($id) => $id > 0);
    }

    if (!$addr) {
        $error = 'Please enter a shipping address.';
    } else {
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare('INSERT INTO orders (buyer_id,total_amount,status,shipping_addr,payment_method,voucher_code,discount_amt) VALUES (?,?,?,?,?,?,?)');
            $ins->execute([$uid, $total, 'pending', $addr, $method, $voucher['code']??null, $discount]);
            $orderId = $pdo->lastInsertId();

            $iins = $pdo->prepare('INSERT INTO order_items (order_id,product_id,seller_id,quantity,unit_price) VALUES (?,?,?,?,?)');
            foreach ($cartItems as $item) {
                $iins->execute([$orderId, $item['product_id'], $item['seller_id'], $item['quantity'], $item['price']]);
                $pdo->prepare('UPDATE products SET stock=stock-?, sold_count=sold_count+? WHERE id=?')
                    ->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }

            if (!empty($selectedIds)) {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND id IN ($placeholders)")
                    ->execute(array_merge([$uid], $selectedIds));
            } else {
                $pdo->prepare('DELETE FROM cart_items WHERE user_id=?')->execute([$uid]);
            }

            if ($voucher) {
                $pdo->prepare('UPDATE vouchers SET used_count=used_count+1 WHERE code=?')->execute([$voucher['code']]);
                unset($_SESSION['voucher']);
            }
            unset($_SESSION['cart_count']);
            $pdo->commit();
            setFlash('success', "🎉 Order #$orderId placed! Thank you for shopping at Shopee PH.");
            header('Location: ' . SITE_URL . '/profile.php?tab=orders');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack(); $error = 'Order failed. Please try again.';
        }
    }
}

$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';
?>
<div class="main">
  <h1 class="page-title">🧾 Checkout</h1>
  <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

  <div class="cart-layout">
    <div class="cart-items-col">
      <div class="form-card">
        <h3 style="margin-bottom:14px">📦 Order Items</h3>
        <?php foreach ($cartItems as $item): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f5f5f5">
          <div class="cart-thumb <?= $item['color_class'] ?>" style="width:50px;height:50px;font-size:22px"><?= $item['emoji'] ?></div>
          <div style="flex:1">
            <div style="font-weight:600;font-size:13px"><?= sanitize($item['name']) ?></div>
            <div style="font-size:12px;color:#757575">Qty: <?= $item['quantity'] ?></div>
          </div>
          <div style="font-weight:700;color:#EE4D2D"><?= peso($item['price']*$item['quantity']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="form-card" style="margin-top:12px">
        <h3 style="margin-bottom:14px">📍 Shipping Address</h3>
        <form method="POST" id="checkout-form">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="form-group">
            <label>Full Address</label>
            <textarea name="address" rows="3" placeholder="House/Unit No., Street, Barangay, City, Province, Zip Code" required><?= sanitize($user['address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Payment Method</label>
            <div class="role-choice" style="flex-wrap:wrap">
              <?php foreach(['COD'=>'💵 Cash on Delivery','GCash'=>'📱 GCash','Visa'=>'💳 Visa/Mastercard','Online Banking'=>'🏦 Online Banking'] as $v=>$l): ?>
                <label class="role-option">
                  <input type="radio" name="payment" value="<?= $v ?>" <?= $v==='COD'?'checked':'' ?>>
                  <span><?= $l ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
          <?php if (!empty($selectedIds)): ?>
            <input type="hidden" name="selected_cart_ids" value="<?= htmlspecialchars(implode(',', $selectedIds)) ?>">
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="cart-summary-col">
      <div class="cart-summary-card">
        <h3>Order Summary</h3>
        <div class="summary-row"><span>Subtotal</span><span><?= peso($subtotal) ?></span></div>
        <div class="summary-row"><span>Shipping</span><span style="color:#26AA99">FREE</span></div>
        <?php if ($discount > 0): ?>
          <div class="summary-row"><span>Voucher (<?= sanitize($voucher['code']) ?>)</span><span style="color:#EE4D2D">-<?= peso($discount) ?></span></div>
        <?php endif; ?>
        <div class="summary-row total-row"><span>Total</span><span><?= peso($total) ?></span></div>
        <button form="checkout-form" type="submit" class="btn-primary btn-full" style="margin-top:16px">
          🛒 Place Order
        </button>
        <a href="<?= SITE_URL ?>/cart.php" class="btn-outline btn-full" style="margin-top:8px;display:block;text-align:center">Back to Cart</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
