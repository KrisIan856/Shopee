<?php
// seller/orders.php — Seller Order Management
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin();

$user = currentUser();
if (!in_array($user['role'], ['seller','admin'])) {
    header('Location: ' . SITE_URL . '/seller/index.php');
    exit;
}

$uid = $_SESSION['user_id'];
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'], $_POST['order_id'], $_POST['status'])) {
    $status = in_array($_POST['status'], ['pending','to_ship','shipped','delivered','cancelled']) ? $_POST['status'] : null;
    $orderId = (int)$_POST['order_id'];
    if ($status) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE order_id=? AND seller_id=?');
        $check->execute([$orderId, $uid]);
        if ((int)$check->fetchColumn() > 0) {
            $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $orderId]);
            setFlash('success', 'Order status updated.');
        }
    }
    header('Location: ' . SITE_URL . '/seller/orders.php');
    exit;
}

$orders = $pdo->prepare('SELECT o.id,o.status,o.total_amount,o.created_at,u.username AS buyer_name,
    COUNT(oi.id) AS item_count, SUM(oi.quantity*oi.unit_price) AS order_total
    FROM orders o
    JOIN order_items oi ON oi.order_id=o.id
    JOIN users u ON u.id=o.buyer_id
    WHERE oi.seller_id=?
    GROUP BY o.id
    ORDER BY o.created_at DESC
');
$orders->execute([$uid]);
$orders = $orders->fetchAll();

$pageTitle = 'Seller Orders';
require __DIR__ . '/../includes/header.php';
?>
<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <h1 class="page-title">📋 Seller Orders</h1>
    <a href="<?= SITE_URL ?>/seller/index.php" class="btn-outline">Back to Dashboard</a>
  </div>

  <div class="section">
    <div style="background:#fff;border-radius:8px;overflow:hidden">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Buyer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:#aaa">No orders found for your products yet.</td></tr>
          <?php else: ?>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td>#<?= $o['id'] ?><br><span style="font-size:11px;color:#777"><?= date('M d, Y', strtotime($o['created_at'])) ?></span></td>
                <td><?= sanitize($o['buyer_name']) ?></td>
                <td><?= number_format($o['item_count']) ?></td>
                <td><?= peso((float)$o['order_total']) ?></td>
                <td><span class="status-pill <?= sanitize($o['status']) ?>"><?= ucfirst(str_replace('_',' ',$o['status'])) ?></span></td>
                <td>
                  <form method="POST" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="status" style="font-size:11px;padding:3px 6px;border-radius:4px;border:1px solid #ddd">
                      <?php foreach(['pending','to_ship','shipped','delivered','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $o['status']===$s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" class="btn-primary" style="font-size:11px;padding:3px 8px">Save</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
