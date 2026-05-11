<?php
// profile.php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
requireLogin();

$tab  = $_GET['tab'] ?? 'profile';
// Redirect seller and rider tabs before any output
if ($tab === 'seller') {
  header('Location: ' . SITE_URL . '/seller/index.php');
  exit;
}
if ($tab === 'rider') {
  header('Location: ' . SITE_URL . '/rider_dashboard.php');
  exit;
}
$uid  = $_SESSION['user_id'];
$pdo  = getDB();
$user = currentUser();
$error = $success = '';

// Update profile
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
    $full  = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone']     ?? '');
    $addr  = trim($_POST['address']   ?? '');
    $avatar = $user['avatar'] ?? '';

    // Handle profile picture upload
    if (!empty($_FILES['avatar']['name'])) {
        $file = $_FILES['avatar'];
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, array_keys($allowed)) || !in_array($file['type'], $allowed)) {
            $error = 'Only JPG, PNG, GIF, and WebP images are allowed for profile picture.';
        } elseif ($file['size'] > 2 * 1024 * 1024) { // 2MB max
            $error = 'Profile picture must not exceed 2MB.';
        } else {
            // Generate unique filename
            $newFileName = uniqid('avatar_') . '.' . $fileExt;
            $uploadPath = UPLOAD_DIR . $newFileName;
            
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Delete old avatar if exists
                if (!empty($user['avatar'])) {
                    $oldPath = UPLOAD_DIR . $user['avatar'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $avatar = $newFileName;
            } else {
                $error = 'Failed to upload profile picture. Please try again.';
            }
        }
    }

    if (!$error) {
        $pdo->prepare('UPDATE users SET full_name=?,phone=?,address=?,avatar=? WHERE id=?')->execute([$full,$phone,$addr,$avatar,$uid]);
        $_SESSION['user']['full_name'] = $full;
        $_SESSION['user']['phone']     = $phone;
        $_SESSION['user']['address']   = $addr;
        $_SESSION['user']['avatar']    = $avatar;
        setFlash('success','Profile updated!');
        header('Location: profile.php?tab=profile'); exit;
    }
}
// Change password
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_pw'])) {
    $curr = $_POST['current_password'] ?? '';
    $new1 = $_POST['new_password']     ?? '';
    $new2 = $_POST['confirm_password'] ?? '';
    if (!password_verify($curr, $user['password'])) { $error='Current password is incorrect.'; }
    elseif (strlen($new1)<6)                         { $error='New password must be at least 6 chars.'; }
    elseif ($new1!==$new2)                           { $error='Passwords do not match.'; }
    else {
        $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new1,PASSWORD_DEFAULT),$uid]);
        setFlash('success','Password changed!');
        header('Location: profile.php?tab=security'); exit;
    }
}

// Fetch orders
$orders = $pdo->prepare('SELECT o.*,(SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS item_count FROM orders o WHERE o.buyer_id=? ORDER BY o.created_at DESC');
$orders->execute([$uid]); $orders=$orders->fetchAll();

// Fetch wishlist
$wishlist = $pdo->prepare('SELECT p.* FROM wishlists w JOIN products p ON p.id=w.product_id WHERE w.user_id=?');
$wishlist->execute([$uid]); $wishlist=$wishlist->fetchAll();

$pageTitle = 'My Profile';
require __DIR__ . '/includes/header.php';
?>
<div class="main">
  <h1 class="page-title">👤 My Account</h1>
  <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

  <div class="profile-layout">
    <!-- Sidebar Tabs -->
    <div class="profile-sidebar">
      <div class="profile-avatar-block">
        <?php if (!empty($user['avatar'])): ?>
          <img src="<?= UPLOAD_URL . htmlspecialchars($user['avatar']) ?>" alt="Profile Picture" class="avatar-circle" style="object-fit: cover;">
        <?php else: ?>
          <div class="avatar-circle">👤</div>
        <?php endif; ?>
        <div><strong><?= sanitize($user['username']) ?></strong></div>
        <div style="font-size:12px;color:#aaa"><?= sanitize($user['role']) ?></div>
      </div>
      <?php
      $tabs = ['profile'=>'👤 My Profile','orders'=>'📦 My Orders','wishlist'=>'❤️ Wishlist','security'=>'🔒 Password'];
      if (in_array($user['role'],['seller','admin'])) {
          $tabs['seller'] = '🏪 My Shop';
      } elseif ($user['role'] === 'rider') {
          $tabs['rider'] = '🚚 Rider Dashboard';
      }
      foreach ($tabs as $k=>$label): ?>
        <a href="?tab=<?= $k ?>" class="sidebar-tab <?= $tab===$k?'active':'' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>

    <!-- Tab Content -->
    <div class="profile-content">

      <?php if ($tab==='profile'): ?>
      <div class="form-card">
        <h3>My Profile</h3>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          
          <div class="form-group">
            <label>Profile Picture</label>
            <?php if (!empty($user['avatar'])): ?>
              <div style="margin-bottom: 12px;">
                <img src="<?= UPLOAD_URL . sanitize($user['avatar']) ?>" alt="Profile Picture" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px;">
                <p style="font-size: 12px; color: #666; margin-top: 8px;">Current profile picture. Upload a new image to replace it.</p>
              </div>
            <?php endif; ?>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">
            <small style="color: #999;">Supported formats: JPG, PNG, GIF, WebP. Max size: 2MB</small>
          </div>
          
          <div class="form-grid-2">
            <div class="form-group">
              <label>Username</label>
              <input type="text" value="<?= sanitize($user['username']) ?>" disabled style="background:#f5f5f5">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" value="<?= sanitize($user['email']) ?>" disabled style="background:#f5f5f5">
            </div>
            <div class="form-group">
              <label>Full Name</label>
              <input type="text" name="full_name" value="<?= sanitize($user['full_name']??'') ?>" placeholder="Your full name">
            </div>
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" value="<?= sanitize($user['phone']??'') ?>" placeholder="09XXXXXXXXX">
            </div>
          </div>
          <div class="form-group">
            <label>Default Address</label>
            <textarea name="address" rows="2"><?= sanitize($user['address']??'') ?></textarea>
          </div>
          <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
        </form>
      </div>

      <?php elseif ($tab==='orders'): ?>
      <div class="form-card">
        <h3>My Orders (<?= count($orders) ?>)</h3>
        <?php if (empty($orders)): ?>
          <div class="empty-state" style="padding:30px"><div class="empty-icon">📦</div><p>No orders yet.</p><a href="<?= SITE_URL ?>/index.php" class="btn-primary">Start Shopping</a></div>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
          <div style="border:1px solid #f0f0f0;border-radius:8px;padding:14px;margin-bottom:10px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
              <strong>Order #<?= $o['id'] ?></strong>
              <span class="status-pill <?= $o['status'] ?>"><?= ucfirst(str_replace('_',' ',$o['status'])) ?></span>
            </div>
            <div style="font-size:13px;color:#757575">
              <?= $o['item_count'] ?> item(s) · <?= $o['payment_method'] ?> · <?= date('M d, Y', strtotime($o['created_at'])) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;gap:10px">
              <div style="font-weight:700;color:#EE4D2D"><?= peso((float)$o['total_amount']) ?></div>
              <a href="<?= SITE_URL ?>/order.php?id=<?= $o['id'] ?>" class="btn-link">View Order</a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php elseif ($tab==='wishlist'): ?>
      <div class="form-card">
        <h3>My Wishlist (<?= count($wishlist) ?>)</h3>
        <?php if (empty($wishlist)): ?>
          <div class="empty-state" style="padding:30px"><div class="empty-icon">❤️</div><p>Your wishlist is empty.</p></div>
        <?php else: ?>
          <div class="products-grid" style="grid-template-columns:repeat(3,1fr)">
            <?php foreach ($wishlist as $p): ?>
            <div class="product-card">
              <a href="<?= SITE_URL ?>/product.php?id=<?= $p['id'] ?>">
                <div class="product-thumb <?= $p['color_class'] ?>"><?= $p['emoji'] ?></div>
              </a>
              <div class="product-info">
                <div class="product-name"><?= sanitize($p['name']) ?></div>
                <div class="product-pricing"><span class="product-price"><?= peso((float)$p['price']) ?></span></div>
                <button class="add-cart-btn" onclick="addToCart(<?= $p['id'] ?>)">Add to Cart</button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php elseif ($tab==='security'): ?>
      <div class="form-card">
        <h3>Change Password</h3>
        <form method="POST" style="max-width:400px">
          <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
          <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
          <div class="form-group"><label>New Password</label><input type="password" name="new_password" required></div>
          <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
          <button type="submit" name="change_pw" class="btn-primary">Change Password</button>
        </form>
      </div>

      <?php endif; ?>

    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
