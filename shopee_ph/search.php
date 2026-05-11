<?php
// search.php  —  Search results / Category browse
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';

$q       = trim($_GET['q']    ?? '');
$cat     = trim($_GET['cat']  ?? '');
$sort    = $_GET['sort']   ?? 'popular';
$flash   = isset($_GET['flash']);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$products = []; $total = 0; $catName = '';

try {
    $pdo    = getDB();
    $where  = ['p.is_active=1'];
    $params = [];

    if ($q) {
        $where[]  = 'p.name LIKE ?';
        $params[] = "%$q%";
    }
    if ($cat) {
        $where[]  = 'c.slug=?';
        $params[] = $cat;
        $catRow   = $pdo->prepare('SELECT name FROM categories WHERE slug=?');
        $catRow->execute([$cat]);
        $catName  = $catRow->fetchColumn() ?: $cat;
    }
    if ($flash) {
        $where[] = 'EXISTS (SELECT 1 FROM flash_deals fd WHERE fd.product_id=p.id AND fd.end_time>NOW())';
    }
    if (isset($_GET['seller'])) {
        $where[]  = 'p.seller_id=?';
        $params[] = (int)$_GET['seller'];
    }

    $orderBy = match($sort) {
        'newest'  => 'p.created_at DESC',
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'rating'  => 'p.rating DESC',
        default   => 'p.sold_count DESC'
    };

    $whereStr  = implode(' AND ', $where);
    $countSql  = "SELECT COUNT(*) FROM products p JOIN categories c ON c.id=p.category_id WHERE $whereStr";
    $st        = $pdo->prepare($countSql);
    $st->execute($params);
    $total     = (int)$st->fetchColumn();

    $sql = "SELECT p.* FROM products p JOIN categories c ON c.id=p.category_id
            WHERE $whereStr ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
    $st  = $pdo->prepare($sql);
    $st->execute($params);
    $products = $st->fetchAll();

} catch (Exception $e) { /* ignore */ }

$totalPages = max(1, (int)ceil($total / $perPage));
$pageTitle  = $q ? "Search: $q" : ($catName ?: ($flash ? 'Flash Deals' : 'All Products'));

require __DIR__ . '/includes/header.php';
?>

<div class="main">
  <div class="search-results-header">
    <div>
      <?php if ($q): ?>
        <h1 class="page-title">Search results for "<em><?= sanitize($q) ?></em>"</h1>
      <?php elseif ($catName): ?>
        <h1 class="page-title">📂 <?= sanitize($catName) ?></h1>
      <?php elseif ($flash): ?>
        <h1 class="page-title">⚡ Flash Deals</h1>
      <?php else: ?>
        <h1 class="page-title">🛍 All Products</h1>
      <?php endif; ?>
      <p style="color:#757575;font-size:13px"><?= number_format($total) ?> result<?= $total!==1?'s':'' ?> found</p>
    </div>
    <div class="sort-bar">
      <label>Sort by:</label>
      <select onchange="resort(this.value)">
        <option value="popular"    <?= $sort==='popular'   ?'selected':'' ?>>Most Popular</option>
        <option value="newest"     <?= $sort==='newest'    ?'selected':'' ?>>Newest</option>
        <option value="price_asc"  <?= $sort==='price_asc' ?'selected':'' ?>>Price ↑</option>
        <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price ↓</option>
        <option value="rating"     <?= $sort==='rating'    ?'selected':'' ?>>Top Rated</option>
      </select>
    </div>
  </div>

  <?php if (empty($products)): ?>
    <div class="empty-state">
      <div class="empty-icon">🔍</div>
      <h3>No products found</h3>
      <p>Try a different search term or browse our categories.</p>
      <a href="<?= SITE_URL ?>/index.php" class="btn-primary">Back to Home</a>
    </div>
  <?php else: ?>
    <div class="products-grid search-grid" id="product-grid">
      <?php foreach ($products as $p):
        $pct = discountPct((float)$p['original_price'], (float)$p['price']);
      ?>
      <div class="product-card" data-id="<?= $p['id'] ?>">
        <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>">
          <div class="product-thumb <?= $p['color_class'] ?>">
            <?php if (!empty($p['image'])): ?>
              <img src="<?= UPLOAD_URL . sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
              <?= $p['emoji'] ?>
            <?php endif; ?>
            <?php if ($p['is_hot']): ?><span class="hot-badge">HOT</span><?php endif; ?>
            <?php if ($p['is_new']): ?><span class="hot-badge new-badge">NEW</span><?php endif; ?>
          </div>
        </a>
        <button class="wishlist-btn" onclick="toggleWishlist(event,<?= $p['id'] ?>)">🤍</button>
        <div class="product-info">
          <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>" style="text-decoration:none;color:inherit">
            <div class="product-name"><?= sanitize($p['name']) ?></div>
          </a>
          <div class="product-pricing">
            <span class="product-price"><?= peso((float)$p['price']) ?></span>
            <?php if ($p['original_price'] > $p['price']): ?>
              <span class="product-was"><?= peso((float)$p['original_price']) ?></span>
              <span class="discount-pill" style="margin-left:4px">-<?= $pct ?>%</span>
            <?php endif; ?>
          </div>
          <div class="product-meta">
            <span class="stars"><?= stars((float)$p['rating']) ?></span>
            <span class="sold-count"><?= soldFormat((int)$p['sold_count']) ?> sold</span>
          </div>
          <?php if ($p['free_shipping']): ?>
            <div style="margin-top:5px"><span class="free-ship">FREE Shipping</span></div>
          <?php endif; ?>
          <div class="location">📍 <?= sanitize($p['location']) ?></div>
          <button class="add-cart-btn" onclick="addToCart(<?= $p['id'] ?>)">Add to Cart</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <?php
      $qs = http_build_query(array_merge($_GET, []));
      for ($i = 1; $i <= $totalPages; $i++):
        $link = '?' . http_build_query(array_merge($_GET, ['page'=>$i]));
      ?>
        <a href="<?= $link ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<script>
function resort(val){
  const url=new URL(window.location);
  url.searchParams.set('sort',val);
  url.searchParams.set('page','1');
  window.location=url.toString();
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
