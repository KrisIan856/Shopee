<?php
// admin/products.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
requireRole('admin');

$pdo = getDB();

// Toggle active
if (isset($_GET['toggle'])) {
    $pdo->prepare('UPDATE products SET is_active = NOT is_active WHERE id=?')->execute([(int)$_GET['toggle']]);
    header('Location: ' . SITE_URL . '/admin/products.php'); exit;
}
// Hard delete
if (isset($_GET['delete'])) {
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_GET['delete']]);
    setFlash('success','Product deleted.');
    header('Location: ' . SITE_URL . '/admin/products.php'); exit;
}

$q     = trim($_GET['q'] ?? '');
$page  = max(1,(int)($_GET['page']??1));
$limit = 20; $offset = ($page-1)*$limit;
$where = $q ? 'WHERE p.name LIKE ?' : '';
$params= $q ? ["%$q%"] : [];

$total    = $pdo->prepare("SELECT COUNT(*) FROM products p $where"); $total->execute($params); $total=(int)$total->fetchColumn();
$products = $pdo->prepare("SELECT p.*,u.username AS seller FROM products p JOIN users u ON u.id=p.seller_id $where ORDER BY p.id DESC LIMIT $limit OFFSET $offset");
$products->execute($params); $products=$products->fetchAll();

$pageTitle = 'Manage Products';
require __DIR__ . '/../includes/header.php';
?>
<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h1 class="page-title">📦 Manage Products</h1>
    <a href="<?= SITE_URL ?>/seller/add-product.php" class="btn-primary">+ Add Product</a>
  </div>

  <form method="GET" style="display:flex;gap:8px;margin-bottom:16px">
    <input type="text" name="q" placeholder="Search products..." value="<?= sanitize($q) ?>" style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:6px">
    <button type="submit" class="btn-primary">Search</button>
    <?php if($q): ?><a href="<?= SITE_URL ?>/admin/products.php" class="btn-outline">Clear</a><?php endif; ?>
  </form>

  <div style="background:#fff;border-radius:8px;overflow:hidden">
    <table class="data-table">
      <thead><tr><th>Product</th><th>Seller</th><th>Price</th><th>Stock</th><th>Sold</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <?php if (!empty($p['image'])): ?>
                <img src="<?= UPLOAD_URL . htmlspecialchars($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:4px">
              <?php else: ?>
                <div class="table-thumb <?= $p['color_class'] ?>"><?= $p['emoji'] ?></div>
              <?php endif; ?>
              <div>
                <div style="font-weight:600;font-size:13px"><?= sanitize(mb_strimwidth($p['name'],0,45,'…')) ?></div>
                <div style="font-size:11px;color:#aaa">ID: <?= $p['id'] ?></div>
              </div>
            </div>
          </td>
          <td style="font-size:12px"><?= sanitize($p['seller']) ?></td>
          <td><?= peso((float)$p['price']) ?></td>
          <td><?= number_format($p['stock']) ?></td>
          <td><?= soldFormat((int)$p['sold_count']) ?></td>
          <td><span class="stars" style="font-size:11px"><?= stars((float)$p['rating']) ?></span></td>
          <td><span class="status-pill <?= $p['is_active']?'active':'inactive' ?>"><?= $p['is_active']?'Active':'Hidden' ?></span></td>
          <td style="white-space:nowrap">
            <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>" class="btn-link">View</a> |
            <a href="<?= SITE_URL ?>/seller/add-product.php?edit=<?= $p['id'] ?>" class="btn-link">Edit</a> |
            <a href="?toggle=<?= $p['id'] ?>" class="btn-link"><?= $p['is_active']?'Hide':'Show' ?></a> |
            <a href="?delete=<?= $p['id'] ?>" class="btn-link" style="color:#EE4D2D" onclick="return confirm('Permanently delete?')">Del</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php $pages=max(1,(int)ceil($total/$limit)); if($pages>1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
