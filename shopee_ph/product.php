<?php
// product.php  —  Product Detail
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';

$id = (int)($_GET['id'] ?? 0);
$product = null; $seller = null; $reviews = []; $related = [];
$canReview = false;
$hasReviewed = false;
$reviewError = '';

try {
    $pdo = getDB();
    $st  = $pdo->prepare('SELECT p.*, u.username AS seller_name FROM products p JOIN users u ON u.id=p.seller_id WHERE p.id=? AND p.is_active=1');
    $st->execute([$id]);
    $product = $st->fetch();

    if ($product) {
        $pageTitle = $product['name'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && isLoggedIn()) {
            verifyCsrf();
            $rating = (int)($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            if ($rating < 1 || $rating > 5) {
                $reviewError = 'Please choose a rating between 1 and 5 stars.';
            } else {
                $checkReview = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE product_id=? AND user_id=?');
                $checkReview->execute([$id, $_SESSION['user_id']]);
                if ((int)$checkReview->fetchColumn() > 0) {
                    $reviewError = 'You have already reviewed this product.';
                } else {
                    $buyCheck = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=? AND o.buyer_id=? AND o.status IN ("shipped","delivered")');
                    $buyCheck->execute([$id, $_SESSION['user_id']]);
                    if ((int)$buyCheck->fetchColumn() === 0) {
                        $reviewError = 'You can only review products you have purchased and received.';
                    } else {
                        $pdo->prepare('INSERT INTO reviews (product_id,user_id,rating,comment) VALUES (?,?,?,?)')
                            ->execute([$id, $_SESSION['user_id'], $rating, $comment]);

                        $newCount = $product['rating_count'] + 1;
                        $newRating = $newCount > 0 ? round(((float)$product['rating'] * $product['rating_count'] + $rating) / $newCount, 2) : $rating;
                        $pdo->prepare('UPDATE products SET rating=?, rating_count=? WHERE id=?')
                            ->execute([$newRating, $newCount, $id]);

                        setFlash('success', 'Thank you! Your review has been submitted.');
                        header('Location: ' . SITE_URL . '/product.php?id=' . $id);
                        exit;
                    }
                }
            }
        }

        if (isLoggedIn()) {
            $userId = $_SESSION['user_id'];
            $reviewExists = $pdo->prepare('SELECT COUNT(*) FROM reviews WHERE product_id=? AND user_id=?');
            $reviewExists->execute([$id, $userId]);
            $hasReviewed = (int)$reviewExists->fetchColumn() > 0;

            $purchaseCheck = $pdo->prepare('SELECT COUNT(*) FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=? AND o.buyer_id=? AND o.status IN ("shipped","delivered")');
            $purchaseCheck->execute([$id, $userId]);
            $canReview = !$hasReviewed && (int)$purchaseCheck->fetchColumn() > 0;
        }

        // reviews
        $rv = $pdo->prepare('SELECT r.*, u.username FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? ORDER BY r.created_at DESC LIMIT 10');
        $rv->execute([$id]);
        $reviews = $rv->fetchAll();

        // related
        $rel = $pdo->prepare('SELECT * FROM products WHERE category_id=? AND id!=? AND is_active=1 ORDER BY sold_count DESC LIMIT 5');
        $rel->execute([$product['category_id'], $id]);
        $related = $rel->fetchAll();
    }
} catch (Exception $e) { /* ignore */ }

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="main" style="text-align:center;padding:60px"><h2>Product not found.</h2><a href="'.SITE_URL.'/index.php" class="btn-primary">Back to Home</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pct = discountPct((float)$product['original_price'], (float)$product['price']);
require __DIR__ . '/includes/header.php';
?>

<div class="main">
  <div class="breadcrumb">
    <a href="<?= SITE_URL ?>/index.php">Home</a> ›
    <a href="<?= SITE_URL ?>/category.php?cat=<?= $product['category_id'] ?>">Category</a> ›
    <?= sanitize(mb_strimwidth($product['name'], 0, 50, '…')) ?>
  </div>

  <!-- Product Detail Card -->
  <div class="product-detail-card">
    <div class="product-detail-img-col">
      <div class="product-detail-img <?= $product['color_class'] ?>">
        <?php if (!empty($product['image'])): ?>
          <img src="<?= UPLOAD_URL . sanitize($product['image']) ?>" alt="<?= sanitize($product['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
        <?php else: ?>
          <span class="detail-emoji"><?= $product['emoji'] ?></span>
        <?php endif; ?>
      </div>
      <?php if ($pct > 0): ?>
        <div class="detail-discount-badge">-<?= $pct ?>% OFF</div>
      <?php endif; ?>
    </div>

    <div class="product-detail-info">
      <?php if ($product['is_hot']): ?><span class="hot-badge" style="position:static;display:inline-block;margin-bottom:8px">HOT</span><?php endif; ?>
      <h1 class="detail-title"><?= sanitize($product['name']) ?></h1>

      <div class="detail-meta">
        <span class="stars"><?= stars((float)$product['rating']) ?></span>
        <span style="color:#757575;font-size:13px">(<?= number_format($product['rating_count']) ?> ratings)</span>
        <span style="color:#757575;font-size:13px">| <?= soldFormat((int)$product['sold_count']) ?> sold</span>
      </div>

      <?php if (isLoggedIn()): ?>
        <div class="review-callout" style="margin:16px 0;padding:14px 16px;border:1px solid #e0e0e0;border-radius:8px;background:#fff">
          <?php if ($canReview): ?>
            <strong>Already purchased this product?</strong> Share your rating below.
            <?php if ($reviewError): ?><div class="alert alert-error" style="margin-top:8px"><?= sanitize($reviewError) ?></div><?php endif; ?>
            <form method="POST" style="margin-top:12px">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <div class="form-group" style="margin-bottom:8px">
                <label>Rating</label>
                <select name="rating" required style="width:120px">
                  <option value="">Choose stars</option>
                  <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="form-group" style="margin-bottom:8px">
                <label>Comment</label>
                <textarea name="comment" rows="3" placeholder="Write a short review..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:6px"></textarea>
              </div>
              <button type="submit" name="submit_review" class="btn-primary" style="padding:8px 16px">Submit Review</button>
            </form>
          <?php elseif ($hasReviewed): ?>
            <strong>Thanks for reviewing this product.</strong>
          <?php else: ?>
            <strong>Buy and receive this product to leave a review.</strong>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="review-callout" style="margin:16px 0;padding:14px 16px;border:1px solid #e0e0e0;border-radius:8px;background:#fff">
          <a href="<?= SITE_URL ?>/login.php">Log in</a> to review this product after purchase.
        </div>
      <?php endif; ?>

      <div class="detail-price-row">
        <span class="detail-price"><?= peso((float)$product['price']) ?></span>
        <?php if ($product['original_price'] > $product['price']): ?>
          <span class="detail-was"><?= peso((float)$product['original_price']) ?></span>
          <span class="discount-pill">-<?= $pct ?>%</span>
        <?php endif; ?>
      </div>

      <div class="detail-shipping">
        🚚 <strong>Shipping:</strong>
        <?= $product['free_shipping'] ? 'FREE Shipping' : 'Calculated at checkout' ?>
        &nbsp;|&nbsp; 📍 From <?= sanitize($product['location']) ?>
      </div>

      <div class="detail-stock">
        <span>In Stock: <strong><?= number_format($product['stock']) ?></strong> pcs</span>
      </div>

      <div class="detail-qty-row">
        <label>Qty:</label>
        <div class="qty-stepper">
          <button onclick="changeQty(-1)">−</button>
          <input type="number" id="qty" value="1" min="1" max="<?= $product['stock'] ?>">
          <button onclick="changeQty(1)">+</button>
        </div>
      </div>

      <div class="detail-actions">
        <button class="btn-outline" onclick="addToCartDetail(<?= $product['id'] ?>)">
          🛒 Add to Cart
        </button>
        <button class="btn-primary" onclick="buyNow(<?= $product['id'] ?>)">
          Buy Now
        </button>
        <button class="btn-wish" onclick="toggleWishlist(event,<?= $product['id'] ?>)">
          🤍 Wishlist
        </button>
      </div>

      <div class="seller-info-box">
        <div class="seller-avatar">🏪</div>
        <div>
          <div style="font-weight:700"><?= sanitize($product['seller_name']) ?></div>
          <div style="font-size:12px;color:#757575">Official Store</div>
        </div>
        <a href="<?= SITE_URL ?>/search.php?seller=<?= $product['seller_id'] ?>" class="btn-outline" style="font-size:12px;padding:6px 14px">View Shop</a>
      </div>
    </div>
  </div>

  <!-- Description -->
  <div class="detail-section">
    <h2>Product Description</h2>
    <div class="detail-desc">
      <?= $product['description'] ? nl2br(sanitize($product['description'])) : '<em>No description provided.</em>' ?>
    </div>
  </div>

  <!-- Reviews -->
  <div class="detail-section">
    <h2>Customer Reviews (<?= count($reviews) ?>)</h2>
    <?php if (empty($reviews)): ?>
      <p style="color:#757575">No reviews yet. Be the first to review!</p>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div class="review-item">
          <div class="review-header">
            <strong><?= sanitize($r['username']) ?></strong>
            <span class="stars" style="font-size:13px"><?= stars((float)$r['rating']) ?></span>
            <span style="font-size:11px;color:#aaa"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
          </div>
          <p><?= sanitize($r['comment']) ?></p>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Related Products -->
  <?php if (!empty($related)): ?>
  <div class="section">
    <div class="section-header">
      <div class="section-title">🔗 Related Products</div>
    </div>
    <div class="products-section-body">
      <div class="products-grid">
        <?php foreach ($related as $rp): ?>
          <div class="product-card">
            <a href="<?= SITE_URL ?>/product.php?id=<?= $rp['id'] ?>">
              <div class="product-thumb <?= $rp['color_class'] ?>">
                <?php if (!empty($rp['image'])): ?>
                  <img src="<?= UPLOAD_URL . sanitize($rp['image']) ?>" alt="<?= sanitize($rp['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                  <?= $rp['emoji'] ?>
                <?php endif; ?>
              </div>
            </a>
            <div class="product-info">
              <div class="product-name"><?= sanitize($rp['name']) ?></div>
              <div class="product-pricing">
                <span class="product-price"><?= peso((float)$rp['price']) ?></span>
                <span class="product-was"><?= peso((float)$rp['original_price']) ?></span>
              </div>
              <div class="product-meta">
                <span class="stars"><?= stars((float)$rp['rating']) ?></span>
                <span class="sold-count"><?= soldFormat((int)$rp['sold_count']) ?> sold</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
function changeQty(d){
  const i=document.getElementById('qty');
  i.value=Math.max(1,Math.min(<?= $product['stock'] ?>,parseInt(i.value||1)+d));
}
function addToCartDetail(pid){
  const qty=parseInt(document.getElementById('qty').value)||1;
  addToCartQty(pid,qty);
}
function buyNow(pid){
  if(!<?= isLoggedIn()?'true':'false' ?>){ window.location='<?= SITE_URL ?>/login.php'; return; }
  const qty=parseInt(document.getElementById('qty').value)||1;
  fetch('<?= SITE_URL ?>/api/cart.php',{
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'<?= csrfToken() ?>'},
    body:JSON.stringify({action:'add',product_id:pid,quantity:qty})
  }).then(()=>{ window.location='<?= SITE_URL ?>/cart.php'; });
}
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
