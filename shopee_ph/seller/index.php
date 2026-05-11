<?php
// seller/index.php  —  Seller Dashboard
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin();

// Allow both sellers AND admins to access seller center
$user = currentUser();
if (!in_array($user['role'], ['seller','admin'])) {
    // Upgrade buyer to seller
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['become_seller'])) {
        getDB()->prepare('UPDATE users SET role=? WHERE id=?')->execute(['seller',$_SESSION['user_id']]);
        $_SESSION['user']['role'] = 'seller';
        header('Location: ' . SITE_URL . '/seller/index.php');
        exit;
    }
    $pageTitle = 'Start Selling';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="main auth-page"><div class="auth-card" style="max-width:480px">
          <h2>Become a Seller</h2>
          <p style="margin:12px 0">Start selling on Shopee PH and reach millions of buyers.</p>
          <form method="POST"><button name="become_seller" class="btn-primary btn-full">Start Selling Now</button></form>
          </div></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$uid = $_SESSION['user_id'];
$pdo = getDB();

// Stats
$totalProducts = $pdo->prepare('SELECT COUNT(*) FROM products WHERE seller_id=?');
$totalProducts->execute([$uid]);
$totalProducts = (int)$totalProducts->fetchColumn();

$totalRevenue = $pdo->prepare('SELECT COALESCE(SUM(oi.unit_price*oi.quantity),0) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.seller_id=? AND o.status=?');
$totalRevenue->execute([$uid,'delivered']);
$totalRevenue = (float)$totalRevenue->fetchColumn();

$pendingOrders = $pdo->prepare('SELECT COUNT(DISTINCT o.id) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.seller_id=? AND o.status=?');
$pendingOrders->execute([$uid,'pending']);
$pendingOrders = (int)$pendingOrders->fetchColumn();

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
    header('Location: ' . SITE_URL . '/seller/index.php');
    exit;
}

$sellerOrders = $pdo->prepare('SELECT o.id,o.status,o.total_amount,o.created_at,u.username AS buyer_name,
    COUNT(oi.id) AS item_count, SUM(oi.quantity*oi.unit_price) AS order_total
    FROM orders o
    JOIN order_items oi ON oi.order_id=o.id
    JOIN users u ON u.id=o.buyer_id
    WHERE oi.seller_id=?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 8
');
$sellerOrders->execute([$uid]);
$sellerOrders = $sellerOrders->fetchAll();

// Calculate shop rating from product reviews
$shopRating = $pdo->prepare('SELECT COALESCE(AVG(r.rating),0) FROM reviews r JOIN products p ON p.id=r.product_id WHERE p.seller_id=?');
$shopRating->execute([$uid]);
$shopRating = (float)$shopRating->fetchColumn();

$products = $pdo->prepare('SELECT * FROM products WHERE seller_id=? ORDER BY created_at DESC LIMIT 20');
$products->execute([$uid]);
$products = $products->fetchAll();

$pageTitle = 'Seller Centre';
require __DIR__ . '/../includes/header.php';
?>

<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
    <h1 class="page-title">🏪 Seller Centre</h1>
    <a href="<?= SITE_URL ?>/seller/add-product.php" class="btn-primary">+ Add New Product</a>
  </div>

  <!-- Stats Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon">📦</div>
      <div class="stat-val"><?= number_format($totalProducts) ?></div>
      <div class="stat-label">Total Products</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">💰</div>
      <div class="stat-val"><?= peso($totalRevenue) ?></div>
      <div class="stat-label">Total Revenue</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🛒</div>
      <div class="stat-val"><?= number_format($pendingOrders) ?></div>
      <div class="stat-label">Pending Orders</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">⭐</div>
      <div class="stat-val"><?= number_format($shopRating, 1) ?></div>
      <div class="stat-label">Shop Rating</div>
    </div>
  </div>

  <!-- Seller Orders -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">📦 Orders with your products</div>
      <a href="<?= SITE_URL ?>/seller/orders.php" class="see-all">View All Orders</a>
    </div>
    <div style="background:#fff;border-radius:0 0 8px 8px;overflow:hidden">
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
          <?php if (empty($sellerOrders)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:#aaa">No orders yet. Once customers buy your products, they will appear here.</td></tr>
          <?php else: ?>
            <?php foreach ($sellerOrders as $o): ?>
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

  <!-- My Products -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">📦 My Products</div>
      <a href="<?= SITE_URL ?>/seller/add-product.php" class="see-all">+ Add Product</a>
    </div>
    <div style="background:#fff;border-radius:0 0 8px 8px;overflow:hidden">
      <table class="data-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Sold</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:#aaa">No products yet. <a href="<?= SITE_URL ?>/seller/add-product.php">Add your first product!</a></td></tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="table-thumb <?= $p['color_class'] ?>">
                    <?php if (!empty($p['image'])): ?>
                      <img src="<?= UPLOAD_URL . sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                      <?= $p['emoji'] ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:13px"><?= sanitize(mb_strimwidth($p['name'],0,50,'…')) ?></div>
                    <div style="font-size:11px;color:#aaa">ID: <?= $p['id'] ?></div>
                  </div>
                </div>
              </td>
              <td><?= peso((float)$p['price']) ?></td>
              <td><?= number_format($p['stock']) ?></td>
              <td><?= soldFormat((int)$p['sold_count']) ?></td>
              <td>
                <span class="status-pill <?= $p['is_active'] ? 'active' : 'inactive' ?>">
                  <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </td>
              <td>
                <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>" class="btn-link">View</a> |
                <a href="<?= SITE_URL ?>/seller/add-product.php?edit=<?= $p['id'] ?>" class="btn-link">Edit</a> |
                <a href="?delete=<?= $p['id'] ?>" class="btn-link" style="color:#EE4D2D"
                   onclick="return confirm('Delete this product?')">Del</a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
// Handle delete
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $pdo->prepare('UPDATE products SET is_active=0 WHERE id=? AND seller_id=?')->execute([$did,$uid]);
    header('Location: ' . SITE_URL . '/seller/index.php');
    exit;
}
require __DIR__ . '/../includes/footer.php';
?>
