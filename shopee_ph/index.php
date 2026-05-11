<?php
// index.php  —  Shopee PH Homepage
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'Home';

// ── Fetch data from DB ────────────────────────────────────────
$banners     = [];
$categories  = [];
$flashDeals  = [];
$recommended = [];
$flashEnd    = time() + 3 * 3600; // fallback

try {
    $pdo = getDB();

    // Banners
    $banners = $pdo->query("SELECT * FROM banners WHERE is_active=1 ORDER BY sort_order")->fetchAll();

    // Categories
    $categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order LIMIT 10")->fetchAll();

    // Flash deals with product info
    $flashDeals = $pdo->query("
        SELECT fd.*, p.name, p.emoji, p.color_class, p.price AS orig_price, p.image,
               fd.flash_price, fd.stock_limit, fd.sold_count
        FROM flash_deals fd
        JOIN products p ON p.id = fd.product_id
        WHERE fd.end_time > NOW() AND p.is_active=1
        ORDER BY fd.sold_count DESC LIMIT 5
    ")->fetchAll();

    if (!empty($flashDeals)) {
        $flashEnd = strtotime($flashDeals[0]['end_time']);
    }

    // Recommended products
    $recommended = $pdo->query("
        SELECT p.*, ROUND(p.price) as fmt_price
        FROM products p
        WHERE p.is_active=1
        ORDER BY p.sold_count DESC LIMIT 10
    ")->fetchAll();

} catch (Exception $e) {
    // DB not connected — use empty arrays, page still renders
}

// Hero banner helpers
$heroBanner = null; $sideA = null; $sideB = null;
foreach ($banners as $b) {
    if ($b['type']==='hero'   && !$heroBanner) $heroBanner = $b;
    if ($b['type']==='side_a' && !$sideA)      $sideA      = $b;
    if ($b['type']==='side_b' && !$sideB)      $sideB      = $b;
}
// Fallbacks if DB empty
$heroBanner = $heroBanner ?? ['title'=>'Up to 90% OFF Top Brands','subtitle'=>'Shop the biggest sale — limited time only.','label'=>'⚡ Mega Sale','cta_text'=>'Shop Now →','cta_url'=>'search.php','bg_gradient'=>'linear-gradient(135deg,#FF6B35,#EE4D2D,#C84120)'];
$sideA      = $sideA      ?? ['title'=>'No Min. Spend All Day','subtitle'=>'Free delivery today!','label'=>'Free Delivery','cta_text'=>'Get Free Ship','bg_gradient'=>'linear-gradient(135deg,#6C63FF,#4834D4)'];
$sideB      = $sideB      ?? ['title'=>'Fresh Picks Every Day','subtitle'=>'New arrivals from top sellers.','label'=>'New Arrivals','cta_text'=>'Explore Now','bg_gradient'=>'linear-gradient(135deg,#26AA99,#1A7A6E)'];

require __DIR__ . '/includes/header.php';
?>

<div class="main">

  <!-- ── HERO ─────────────────────────────────────────────── -->
  <div class="hero-row">
    <div class="hero-banner" style="background:<?= sanitize($heroBanner['bg_gradient']) ?>">
      <div class="hero-bg-circle"></div>
      <div class="hero-bg-circle2"></div>
      <div class="hero-content">
        <div class="hero-label"><?= sanitize($heroBanner['label']) ?></div>
        <div class="hero-title"><?= sanitize($heroBanner['title']) ?></div>
        <div class="hero-sub"><?= sanitize($heroBanner['subtitle']) ?></div>
        <a href="<?= SITE_URL ?>/<?= sanitize($heroBanner['cta_url'] ?? 'search.php') ?>" class="hero-btn">
          <?= sanitize($heroBanner['cta_text']) ?>
        </a>
        <div class="hero-dots">
          <div class="dot active"></div>
          <div class="dot"></div>
          <div class="dot"></div>
          <div class="dot"></div>
        </div>
      </div>
    </div>
    <div class="side-banners">
      <div class="side-card" style="background:<?= sanitize($sideA['bg_gradient']) ?>">
        <div class="side-card-label"><?= sanitize($sideA['label']) ?></div>
        <div class="side-card-title"><?= sanitize($sideA['title']) ?></div>
      </div>
      <div class="side-card" style="background:<?= sanitize($sideB['bg_gradient']) ?>">
        <div class="side-card-label"><?= sanitize($sideB['label']) ?></div>
        <div class="side-card-title"><?= sanitize($sideB['title']) ?></div>
      </div>
    </div>
  </div>

  <!-- ── CATEGORIES ───────────────────────────────────────── -->
  <div class="categories-grid">
    <?php
    $fallbackCats = [
      ['slug'=>'mobiles-gadgets','emoji'=>'📱','name'=>'Mobiles & Gadgets','color_class'=>'emj-bg-1'],
      ['slug'=>'womens-fashion', 'emoji'=>'👗','name'=>"Women's Fashion",   'color_class'=>'emj-bg-2'],
      ['slug'=>'mens-fashion',   'emoji'=>'👔','name'=>"Men's Fashion",     'color_class'=>'emj-bg-3'],
      ['slug'=>'home-living',    'emoji'=>'🏠','name'=>'Home & Living',     'color_class'=>'emj-bg-4'],
      ['slug'=>'health-beauty',  'emoji'=>'💄','name'=>'Health & Beauty',   'color_class'=>'emj-bg-5'],
      ['slug'=>'sports-outdoors','emoji'=>'⚽','name'=>'Sports & Outdoors', 'color_class'=>'emj-bg-6'],
      ['slug'=>'toys-kids',      'emoji'=>'🧸','name'=>'Toys & Kids',       'color_class'=>'emj-bg-7'],
      ['slug'=>'food-beverages', 'emoji'=>'🍜','name'=>'Food & Beverages',  'color_class'=>'emj-bg-8'],
      ['slug'=>'computers',      'emoji'=>'💻','name'=>'Computers',         'color_class'=>'emj-bg-1'],
      ['slug'=>'tools-home',     'emoji'=>'🔧','name'=>'Tools & Home Impr.','color_class'=>'emj-bg-3'],
    ];
    $displayCats = !empty($categories) ? $categories : $fallbackCats;
    foreach ($displayCats as $cat): ?>
      <a href="<?= SITE_URL ?>/category.php?cat=<?= $cat['slug'] ?>" class="cat-box">
        <div class="cat-emoji <?= $cat['color_class'] ?>"><?= $cat['emoji'] ?></div>
        <div class="cat-name"><?= sanitize($cat['name']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- ── VOUCHER STRIP ────────────────────────────────────── -->
  <div class="voucher-strip">
    <div class="voucher-left">
      <div class="voucher-icon">🎟</div>
      <div class="voucher-text">
        <h3>Exclusive Vouchers — Claim Now!</h3>
        <p>Up to ₱500 OFF on your next purchase. Limited slots available.</p>
      </div>
    </div>
    <button class="voucher-btn" onclick="claimVoucher()">Claim Voucher</button>
  </div>

  <!-- ── FLASH DEALS ──────────────────────────────────────── -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">
        <span class="flash-icon">⚡ FLASH DEALS</span>
        <div class="countdown">
          <span class="count-block" id="ch">--</span>
          <span class="count-sep">:</span>
          <span class="count-block" id="cm">--</span>
          <span class="count-sep">:</span>
          <span class="count-block" id="cs">--</span>
        </div>
      </div>
      <a href="<?= SITE_URL ?>/search.php?flash=1" class="see-all">See All ›</a>
    </div>
    <div class="flash-grid">
      <?php
      $fallbackFlash = [
        ['product_id'=>0,'name'=>'Smartphone Deal','emoji'=>'📱','color_class'=>'emj-bg-1','flash_price'=>4999,'orig_price'=>12990,'sold_count'=>39,'stock_limit'=>50],
        ['product_id'=>0,'name'=>'Running Shoes',  'emoji'=>'👟','color_class'=>'emj-bg-2','flash_price'=>999, 'orig_price'=>2500, 'sold_count'=>33,'stock_limit'=>60],
        ['product_id'=>0,'name'=>'Laptop Deal',    'emoji'=>'💻','color_class'=>'emj-bg-3','flash_price'=>29500,'orig_price'=>49999,'sold_count'=>8,'stock_limit'=>20],
        ['product_id'=>0,'name'=>'Headphones',     'emoji'=>'🎧','color_class'=>'emj-bg-4','flash_price'=>1299,'orig_price'=>3500, 'sold_count'=>27,'stock_limit'=>30],
        ['product_id'=>0,'name'=>'Smart Watch',    'emoji'=>'⌚','color_class'=>'emj-bg-5','flash_price'=>2199,'orig_price'=>5999, 'sold_count'=>52,'stock_limit'=>80],
      ];
      $displayFlash = !empty($flashDeals) ? $flashDeals : $fallbackFlash;
      foreach ($displayFlash as $fd):
        $pct  = discountPct((float)$fd['orig_price'], (float)$fd['flash_price']);
        $sold = $fd['stock_limit'] > 0
                  ? min(100, round($fd['sold_count'] / $fd['stock_limit'] * 100))
                  : 0;
        $url  = $fd['product_id'] ? SITE_URL.'/product.php?id='.$fd['product_id'] : '#';
      ?>
      <a href="<?= $url ?>" class="flash-item">
        <div class="flash-img <?= $fd['color_class'] ?>">
          <?php if (!empty($fd['image'])): ?>
            <img src="<?= UPLOAD_URL . sanitize($fd['image']) ?>" alt="<?= sanitize($fd['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
          <?php else: ?>
            <?= $fd['emoji'] ?>
          <?php endif; ?>
        </div>
        <div class="flash-price"><?= peso((float)$fd['flash_price']) ?></div>
        <div class="flash-original"><?= peso((float)$fd['orig_price']) ?></div>
        <div class="discount-pill">-<?= $pct ?>%</div>
        <div class="sold-bar-wrap">
          <div class="sold-bar" style="width:<?= $sold ?>%"></div>
        </div>
        <div class="sold-label"><?= $sold ?>% sold</div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── RECOMMENDED ──────────────────────────────────────── -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">🛍 Recommended For You</div>
      <a href="<?= SITE_URL ?>/search.php" class="see-all">See All ›</a>
    </div>
    <div class="products-section-body">
      <div class="products-grid" id="product-grid">
        <?php
        $fallbackProducts = [
          ['id'=>0,'name'=>'Nike Air Max 270 Running Shoes Men Women Unisex',          'price'=>1849,'original_price'=>4500, 'emoji'=>'👟','color_class'=>'emj-bg-1','rating'=>5.0,'sold_count'=>2300,'location'=>'Metro Manila',  'free_shipping'=>1,'is_hot'=>1,'is_new'=>0],
          ['id'=>0,'name'=>'MAC Cosmetics Matte Lipstick Long Lasting 24HR Wear',       'price'=>349, 'original_price'=>950,  'emoji'=>'💄','color_class'=>'emj-bg-2','rating'=>4.3,'sold_count'=>5100,'location'=>'Cebu City',      'free_shipping'=>1,'is_hot'=>0,'is_new'=>0],
          ['id'=>0,'name'=>'PlayStation 5 DualSense Wireless Controller Midnight Black','price'=>3299,'original_price'=>4990, 'emoji'=>'🎮','color_class'=>'emj-bg-3','rating'=>5.0,'sold_count'=>890, 'location'=>'Quezon City',    'free_shipping'=>1,'is_hot'=>1,'is_new'=>0],
          ['id'=>0,'name'=>'Nescafé Gold Premium Coffee Blend 200g Rich Aroma',         'price'=>289, 'original_price'=>450,  'emoji'=>'☕','color_class'=>'emj-bg-4','rating'=>4.6,'sold_count'=>12000,'location'=>'Pasig City',    'free_shipping'=>1,'is_hot'=>0,'is_new'=>0],
          ['id'=>0,'name'=>'Vitamineral Collagen + Glutathione Skin Glow Capsules 60s', 'price'=>599, 'original_price'=>1299, 'emoji'=>'🌿','color_class'=>'emj-bg-5','rating'=>4.9,'sold_count'=>3700,'location'=>'Taguig City',   'free_shipping'=>1,'is_hot'=>0,'is_new'=>0],
          ['id'=>0,'name'=>'Samsonite T5 Laptop Backpack Waterproof Anti-theft 15.6"', 'price'=>1199,'original_price'=>2800, 'emoji'=>'🎒','color_class'=>'emj-bg-6','rating'=>4.4,'sold_count'=>4400,'location'=>'Makati City',   'free_shipping'=>1,'is_hot'=>0,'is_new'=>0],
          ['id'=>0,'name'=>'Adjustable Dumbbell Set 2-25kg Home Gym Fitness Training',  'price'=>3599,'original_price'=>7000, 'emoji'=>'🏋','color_class'=>'emj-bg-7','rating'=>5.0,'sold_count'=>620, 'location'=>'Mandaluyong',    'free_shipping'=>1,'is_hot'=>0,'is_new'=>1],
          ['id'=>0,'name'=>'Scented Soy Wax Candle Set Aromatherapy Home Decor Gift',   'price'=>245, 'original_price'=>550,  'emoji'=>'🕯','color_class'=>'emj-bg-8','rating'=>5.0,'sold_count'=>8900,'location'=>'Las Piñas',     'free_shipping'=>1,'is_hot'=>0,'is_new'=>0],
          ['id'=>0,'name'=>'Canon EOS M50 Mark II Mirrorless Camera 24.1MP Kit Set',    'price'=>34999,'original_price'=>49995,'emoji'=>'📷','color_class'=>'emj-bg-1','rating'=>4.7,'sold_count'=>182,'location'=>'Quezon City',    'free_shipping'=>1,'is_hot'=>0,'is_new'=>0],
          ['id'=>0,'name'=>'Cetaphil Moisturizing Cream 550g Sensitive Skin Daily Use', 'price'=>469, 'original_price'=>799,  'emoji'=>'🧴','color_class'=>'emj-bg-3','rating'=>5.0,'sold_count'=>21000,'location'=>'Pasay City',   'free_shipping'=>1,'is_hot'=>0,'is_new'=>0],
        ];
        $displayProds = !empty($recommended) ? $recommended : $fallbackProducts;
        foreach ($displayProds as $p):
          $url = $p['id'] ? SITE_URL.'/product.php?id='.$p['id'] : '#';
          $pct = discountPct((float)$p['original_price'], (float)$p['price']);
        ?>
        <div class="product-card" data-id="<?= $p['id'] ?>">
          <a href="<?= $url ?>">
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
          <button class="wishlist-btn" onclick="toggleWishlist(event,<?= $p['id'] ?>)" title="Add to Wishlist">🤍</button>
          <div class="product-info">
            <a href="<?= $url ?>" style="text-decoration:none;color:inherit">
              <div class="product-name"><?= sanitize($p['name']) ?></div>
            </a>
            <div class="product-pricing">
              <span class="product-price"><?= peso((float)$p['price']) ?></span>
              <?php if ($p['original_price'] > $p['price']): ?>
                <span class="product-was"><?= peso((float)$p['original_price']) ?></span>
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
    </div>
  </div>

</div><!-- /.main -->

<!-- Countdown timer data -->
<script>
  const flashEndTimestamp = <?= $flashEnd ?>;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
