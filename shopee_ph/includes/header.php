<?php
// includes/header.php  —  Navigation + <head>
if (!defined('DB_HOST')) require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/session.php';

$flash    = getFlash();
$cartCnt  = cartCount();
$user     = currentUser();
$searchQ  = sanitize($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
  <meta name="csrf-token" content="<?= csrfToken() ?>">
</head>
<body>

<?php if ($flash): ?>
<div class="flash-banner flash-<?= $flash['type'] ?>">
  <?= sanitize($flash['msg']) ?>
  <button onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-links">
    <a href="<?= SITE_URL ?>/seller/index.php">Seller Centre</a>
    <span class="divider">|</span>
    <a href="<?= SITE_URL ?>/seller/index.php">Start Selling</a>
    <span class="divider">|</span>
    <a href="#">Download App</a>
  </div>
  <div class="topbar-links">
    <?php if ($user): ?>
      <a href="<?= SITE_URL ?>/profile.php">Hi, <?= sanitize($user['username']) ?></a>
      <span class="divider">|</span>
      <?php if ($user['role'] === 'admin'): ?>
        <a href="<?= SITE_URL ?>/admin/index.php">Admin Panel</a>
        <span class="divider">|</span>
      <?php elseif ($user['role'] === 'seller'): ?>
        <a href="<?= SITE_URL ?>/seller/index.php">My Shop</a>
        <span class="divider">|</span>
      <?php elseif ($user['role'] === 'rider'): ?>
        <a href="<?= SITE_URL ?>/rider_dashboard.php">Rider Dashboard</a>
        <span class="divider">|</span>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/logout.php">Log Out</a>
    <?php else: ?>
      <a href="<?= SITE_URL ?>/register.php" style="font-weight:700;color:#fff">Sign Up</a>
      <span class="divider">|</span>
      <a href="<?= SITE_URL ?>/login.php" style="font-weight:700;color:#fff">Log In</a>
    <?php endif; ?>
  </div>
</div>

<!-- NAVBAR -->
<div class="navbar">
  <a href="<?= SITE_URL ?>/index.php" class="logo">
    Shopee <span class="logo-badge">PH</span>
  </a>
  <form class="search-wrap" action="<?= SITE_URL ?>/search.php" method="GET">
    <input type="text" name="q" placeholder="Search for products, brands, and more..."
           value="<?= $searchQ ?>" autocomplete="off" id="search-input">
    <button type="submit" class="search-btn">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
      </svg>
      Search
    </button>
  </form>
  <div class="nav-icons">
    <a href="<?= SITE_URL ?>/cart.php" class="nav-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 0 1-8 0"/>
      </svg>
      <span>Cart</span>
      <?php if ($cartCnt > 0): ?>
        <span class="badge" id="cart-badge"><?= $cartCnt ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= isLoggedIn() ? SITE_URL . '/profile.php' : SITE_URL . '/login.php' ?>" class="nav-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      <span><?= $user ? sanitize($user['username']) : 'Profile' ?></span>
    </a>
  </div>
</div>

<!-- CATEGORY STRIP -->
<div class="cat-strip">
  <?php
  $activeCat = $_GET['cat'] ?? '';
  $cats = [];
  try {
    $pdo  = getDB();
    $cats = $pdo->query('SELECT slug,name FROM categories ORDER BY sort_order')->fetchAll();
  } catch (Exception $e) { /* DB not connected yet */ }
  ?>
  <a href="<?= SITE_URL ?>/index.php" class="cat-item <?= $activeCat==='' ? 'active' : '' ?>">All Categories</a>
  <?php foreach ($cats as $c): ?>
    <a href="<?= SITE_URL ?>/category.php?cat=<?= $c['slug'] ?>"
       class="cat-item <?= $activeCat===$c['slug'] ? 'active' : '' ?>">
      <?= sanitize($c['name']) ?>
    </a>
  <?php endforeach; ?>
</div>
