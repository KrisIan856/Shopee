<?php
// admin/index.php  —  Admin Dashboard
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
requireRole('admin');

$pdo = getDB();

// Stats
function fetchStat($pdo, $sql, $params=[]) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchColumn();
}

$stats = [
    'users'    => fetchStat($pdo, 'SELECT COUNT(*) FROM users'),
    'products' => fetchStat($pdo, 'SELECT COUNT(*) FROM products WHERE is_active=1'),
    'orders'   => fetchStat($pdo, 'SELECT COUNT(*) FROM orders'),
    'revenue'  => fetchStat($pdo, "SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status='delivered'"),
    'pending'  => fetchStat($pdo, "SELECT COUNT(*) FROM orders WHERE status='pending'"),
    'sellers'  => fetchStat($pdo, "SELECT COUNT(*) FROM users WHERE role='seller'"),
];

// Recent orders
$riders = $pdo->query('SELECT id, username, full_name FROM users WHERE role="rider" AND is_active=1 ORDER BY username')->fetchAll();

$orders = $pdo->query('SELECT o.*, u.username AS buyer_name, r.username AS rider_username FROM orders o JOIN users u ON u.id=o.buyer_id LEFT JOIN users r ON r.id=o.rider_id ORDER BY o.created_at DESC LIMIT 10')->fetchAll();

// Recent users
$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC LIMIT 8')->fetchAll();

// Update order status and rider assignment
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['order_id'])) {
    $status = in_array($_POST['status'], ['pending','to_ship','shipped','delivered','cancelled']) ? $_POST['status'] : 'pending';
    $riderId = null;
    if (isset($_POST['rider_id']) && $_POST['rider_id'] !== '') {
        $candidate = (int)$_POST['rider_id'];
        $check = $pdo->prepare('SELECT id FROM users WHERE id=? AND role="rider" AND is_active=1 LIMIT 1');
        $check->execute([$candidate]);
        if ($check->fetch()) {
            $riderId = $candidate;
        }
    }
    $pdo->prepare('UPDATE orders SET status=?, rider_id=? WHERE id=?')->execute([$status, $riderId, (int)$_POST['order_id']]);
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
}

$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>
<div class="main">
  <h1 class="page-title">🔑 Admin Dashboard</h1>

  <!-- Stats -->
  <div class="stats-grid" style="grid-template-columns:repeat(6,1fr)">
    <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-val"><?= number_format($stats['users']) ?></div><div class="stat-label">Users</div></div>
    <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-val"><?= number_format($stats['products']) ?></div><div class="stat-label">Products</div></div>
    <div class="stat-card"><div class="stat-icon">🛒</div><div class="stat-val"><?= number_format($stats['orders']) ?></div><div class="stat-label">Orders</div></div>
    <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-val"><?= peso((float)$stats['revenue']) ?></div><div class="stat-label">Revenue</div></div>
    <div class="stat-card" style="border-color:#FF9800"><div class="stat-icon">⏳</div><div class="stat-val"><?= number_format($stats['pending']) ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-icon">🏪</div><div class="stat-val"><?= number_format($stats['sellers']) ?></div><div class="stat-label">Sellers</div></div>
  </div>

  <!-- Quick Links -->
  <div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <a href="<?= SITE_URL ?>/admin/products.php" class="btn-outline">📦 Manage Products</a>
    <a href="<?= SITE_URL ?>/admin/users.php" class="btn-outline">👥 Manage Users</a>
    <a href="<?= SITE_URL ?>/admin/index.php" class="btn-outline">🛠️ Manage Orders</a>
    <a href="<?= SITE_URL ?>/seller/add-product.php" class="btn-primary">+ Add Product</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">

    <!-- Recent Orders -->
    <div class="section">
      <div class="section-header"><div class="section-title">📋 Recent Orders</div></div>
      <div style="background:#fff;border-radius:0 0 8px 8px;overflow:hidden">
        <table class="data-table">
          <thead><tr><th>ID</th><th>Buyer</th><th>Rider</th><th>Total</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= $o['id'] ?></td>
              <td><?= sanitize($o['buyer_name']) ?></td>
              <td><?= sanitize($o['rider_username'] ?? '—') ?></td>
              <td><?= peso((float)$o['total_amount']) ?></td>
              <td><span class="status-pill <?= $o['status'] ?>"><?= $o['status'] ?></span></td>
              <td>
                <form method="POST" style="display:flex;gap:4px;flex-wrap:wrap;align-items:center">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <select name="rider_id" style="font-size:11px;padding:2px 4px;border-radius:4px;border:1px solid #ddd">
                    <option value="">No Rider</option>
                    <?php foreach ($riders as $r): ?>
                      <option value="<?= $r['id'] ?>" <?= $o['rider_id']===$r['id']?'selected':'' ?>><?= sanitize($r['username']) ?><?= $r['full_name'] ? ' (' . sanitize($r['full_name']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                  </select>
                  <select name="status" style="font-size:11px;padding:2px 4px;border-radius:4px;border:1px solid #ddd">
                    <?php foreach(['pending','to_ship','shipped','delivered','cancelled'] as $s): ?>
                      <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn-primary" style="font-size:11px;padding:3px 8px">Save</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Users -->
    <div class="section">
      <div class="section-header"><div class="section-title">👥 Recent Users</div></div>
      <div style="background:#fff;border-radius:0 0 8px 8px;overflow:hidden">
        <table class="data-table">
          <thead><tr><th>User</th><th>Role</th><th>Joined</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <strong><?= sanitize($u['username']) ?></strong><br>
                <span style="font-size:11px;color:#aaa"><?= sanitize($u['email']) ?></span>
              </td>
              <td><span class="status-pill <?= $u['role'] ?>"><?= $u['role'] ?></span></td>
              <td style="font-size:11px"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
              <td><span class="status-pill <?= $u['is_active']?'active':'inactive' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
