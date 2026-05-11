<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';

requireRole('rider');

$pdo = getDB();
$uid = $_SESSION['user_id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $orderId = (int)$_POST['order_id'];
    $allowed = ['to_ship','shipped','delivered'];
    $status = in_array($_POST['status'] ?? '', $allowed) ? $_POST['status'] : null;

    if ($status) {
        $valid = $pdo->prepare('SELECT id,status FROM orders WHERE id=? AND rider_id=? LIMIT 1');
        $valid->execute([$orderId, $uid]);
        $order = $valid->fetch();
        if ($order) {
            $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $orderId]);
            setFlash('success', 'Order #' . $orderId . ' updated.');
            header('Location: ' . $_SERVER['REQUEST_URI']); exit;
        }
        $error = 'Order not assigned to you or no longer available.';
    } else {
        $error = 'Invalid action selected.';
    }
}

$orders = $pdo->prepare('SELECT o.*, u.username AS buyer_name, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS item_count FROM orders o JOIN users u ON u.id=o.buyer_id WHERE o.rider_id=? ORDER BY o.created_at DESC');
$orders->execute([$uid]);
$orders = $orders->fetchAll();

$ratingStats = $pdo->prepare('SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS count_reviews FROM rider_reviews WHERE rider_id=?');
$ratingStats->execute([$uid]);
$ratingStats = $ratingStats->fetch();
$avgRiderRating = (float)$ratingStats['avg_rating'];
$riderRatingCount = (int)$ratingStats['count_reviews'];

$pageTitle = 'Rider Dashboard';
require __DIR__ . '/includes/header.php';
?>
<div class="main">
  <h1 class="page-title">🚚 Rider Dashboard</h1>
  <p>Welcome, <?= sanitize(currentUser()['full_name'] ?? currentUser()['username']) ?>.</p>

  <div style="display:flex;flex-wrap:wrap;gap:14px;margin-top:18px">
    <div class="stat-card" style="flex:1;min-width:220px">
      <div class="stat-icon">⭐</div>
      <div class="stat-val"><?= number_format($avgRiderRating, 1) ?></div>
      <div class="stat-label">Rider Rating</div>
      <div style="font-size:12px;color:#777;margin-top:4px"><?= $riderRatingCount ?> review<?= $riderRatingCount===1?'':'s' ?></div>
    </div>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

  <div class="section" style="margin-top:20px">
    <div class="section-header"><div class="section-title">Assigned Orders</div></div>
    <?php if (empty($orders)): ?>
      <div class="empty-state" style="padding:30px"><div class="empty-icon">🚚</div><p>No assigned orders yet.</p><p>Ask admin to assign orders to you from the admin panel.</p></div>
    <?php else: ?>
      <div style="background:#fff;border-radius:8px;overflow:hidden">
        <table class="data-table">
          <thead>
            <tr><th>Order</th><th>Buyer</th><th>Items</th><th>Total</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td>#<?= $o['id'] ?><br><span style="font-size:11px;color:#777"><?= date('M d, Y', strtotime($o['created_at'])) ?></span></td>
                <td><?= sanitize($o['buyer_name']) ?></td>
                <td><?= (int)$o['item_count'] ?></td>
                <td><?= peso((float)$o['total_amount']) ?></td>
                <td><span class="status-pill <?= $o['status'] ?>"><?= ucfirst(str_replace('_',' ',$o['status'])) ?></span></td>
                <td>
                  <?php if (in_array($o['status'], ['to_ship','shipped'])): ?>
                    <form method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
                      <input type="hidden" name="update_order" value="1">
                      <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                      <select name="status" style="font-size:11px;padding:4px;border-radius:4px;border:1px solid #ddd">
                        <?php if ($o['status'] === 'to_ship'): ?>
                          <option value="shipped">Mark Shipped</option>
                          <option value="delivered">Mark Delivered</option>
                        <?php elseif ($o['status'] === 'shipped'): ?>
                          <option value="delivered">Mark Delivered</option>
                        <?php endif; ?>
                      </select>
                      <button type="submit" class="btn-primary" style="font-size:11px;padding:4px 10px">Update</button>
                    </form>
                  <?php else: ?>
                    <span style="font-size:12px;color:#555">No actions available</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
