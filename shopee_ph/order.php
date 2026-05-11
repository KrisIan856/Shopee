<?php
// order.php — Buyer order details and rider rating
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
requireLogin();

$orderId = (int)($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];
$pdo = getDB();
$error = '';
$success = '';

// Fetch order and buyer-owned check
$orderSt = $pdo->prepare('SELECT o.*, u.username AS buyer_name, r.username AS rider_name, r.id AS rider_id
    FROM orders o
    JOIN users u ON u.id=o.buyer_id
    LEFT JOIN users r ON r.id=o.rider_id
    WHERE o.id=? AND o.buyer_id=?
');
$orderSt->execute([$orderId, $uid]);
$order = $orderSt->fetch();
if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="main" style="text-align:center;padding:60px"><h2>Order not found.</h2><a href="'.SITE_URL.'/profile.php?tab=orders" class="btn-primary">My Orders</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$itemsSt = $pdo->prepare('SELECT oi.*, p.name, p.image, p.price, p.emoji, p.color_class, p.location
    FROM order_items oi
    JOIN products p ON p.id=oi.product_id
    WHERE oi.order_id=?
');
$itemsSt->execute([$orderId]);
$items = $itemsSt->fetchAll();

$productIds = array_map(fn($item)=>$item['product_id'], $items);
$reviewedProducts = [];
if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $pdo->prepare("SELECT product_id FROM reviews WHERE product_id IN ($placeholders) AND user_id=?");
    $stmt->execute(array_merge($productIds, [$uid]));
    $reviewedProducts = array_column($stmt->fetchAll(), 'product_id');
}

$riderRatingExists = false;
if ($order['rider_id']) {
    $checkRider = $pdo->prepare('SELECT COUNT(*) FROM rider_reviews WHERE order_id=? AND buyer_id=?');
    $checkRider->execute([$orderId, $uid]);
    $riderRatingExists = (int)$checkRider->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rider_review'])) {
    verifyCsrf();
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if (!$order['rider_id']) {
        $error = 'No rider is assigned to this order yet.';
    } elseif ($riderRatingExists) {
        $error = 'You already rated the rider for this order.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5.';
    } else {
        $pdo->prepare('INSERT INTO rider_reviews (order_id,rider_id,buyer_id,rating,comment) VALUES (?,?,?,?,?)')
            ->execute([$orderId, $order['rider_id'], $uid, $rating, $comment]);
        setFlash('success', 'Thank you! Your rider rating has been submitted.');
        header('Location: ' . SITE_URL . '/order.php?id=' . $orderId);
        exit;
    }
}

$pageTitle = 'Order #' . $order['id'];
require __DIR__ . '/includes/header.php';
?>
<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <div>
      <h1 class="page-title">Order #<?= $order['id'] ?></h1>
      <div style="color:#757575;font-size:13px">Placed on <?= date('M d, Y', strtotime($order['created_at'])) ?></div>
    </div>
    <a href="<?= SITE_URL ?>/profile.php?tab=orders" class="btn-outline">Back to My Orders</a>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>

  <div class="section" style="margin-bottom:18px">
    <div class="section-header"><div class="section-title">Order Summary</div></div>
    <div class="section-body" style="background:#fff;border-radius:8px;padding:18px">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div><strong>Status</strong><br><span class="status-pill <?= sanitize($order['status']) ?>"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span></div>
        <div><strong>Total</strong><br><?= peso((float)$order['total_amount']) ?></div>
        <div><strong>Buyer</strong><br><?= sanitize($order['buyer_name']) ?></div>
        <div><strong>Shipping Address</strong><br><?= nl2br(sanitize($order['shipping_addr'])) ?></div>
        <div><strong>Payment</strong><br><?= sanitize($order['payment_method']) ?></div>
        <div><strong>Rider</strong><br>
          <?= $order['rider_id'] ? sanitize($order['rider_name']) : '<span style="color:#777">Not assigned</span>' ?></div>
      </div>
    </div>
  </div>

  <div class="section" style="margin-bottom:18px">
    <div class="section-header"><div class="section-title">Items</div></div>
    <div style="background:#fff;border-radius:8px;overflow:hidden">
      <table class="data-table">
        <thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Subtotal</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="table-thumb <?= $item['color_class'] ?>" style="width:50px;height:50px">
                    <?php if (!empty($item['image'])): ?>
                      <img src="<?= UPLOAD_URL . sanitize($item['image']) ?>" alt="<?= sanitize($item['name']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
                    <?php else: ?>
                      <?= $item['emoji'] ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <a href="<?= SITE_URL ?>/product.php?id=<?= $item['product_id'] ?>" style="font-weight:600;color:#222"><?= sanitize($item['name']) ?></a>
                    <?php if (in_array($order['status'], ['shipped','delivered'])): ?>
                      <div style="font-size:11px;color:#777; margin-top:4px">
                        <?= in_array($item['product_id'], $reviewedProducts) ? 'You reviewed this product.' : '<a href="'.SITE_URL.'/product.php?id='.$item['product_id'].'">Review this product</a>' ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td><?= number_format($item['quantity']) ?></td>
              <td><?= peso((float)$item['price']) ?></td>
              <td><?= peso((float)$item['price'] * $item['quantity']) ?></td>
              <td><span class="status-pill <?= sanitize($order['status']) ?>"><?= ucfirst(str_replace('_',' ',$order['status'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($order['rider_id']): ?>
  <div class="section">
    <div class="section-header"><div class="section-title">Rate Your Rider</div></div>
    <div class="section-body" style="background:#fff;border-radius:8px;padding:18px">
      <?php if ($riderRatingExists): ?>
        <p style="color:#555">You have already rated <?= sanitize($order['rider_name']) ?> for this delivery.</p>
      <?php elseif ($order['status'] !== 'delivered'): ?>
        <p style="color:#555">You can rate your rider after delivery is completed.</p>
      <?php else: ?>
        <form method="POST" style="max-width:560px">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="form-group">
            <label>Rating</label>
            <select name="rating" required style="width:120px">
              <option value="">Choose stars</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Comment</label>
            <textarea name="comment" rows="3" placeholder="Share how the delivery went..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px"></textarea>
          </div>
          <button type="submit" name="submit_rider_review" class="btn-primary">Submit Rider Rating</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
