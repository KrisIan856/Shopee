<?php
// seller/add-product.php  —  Add / Edit Product
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';
requireLogin();

$user = currentUser();
if (!in_array($user['role'], ['seller','admin'])) {
    header('Location: ' . SITE_URL . '/seller/index.php'); exit;
}

$uid        = $_SESSION['user_id'];
$pdo        = getDB();
$editId     = (int)($_GET['edit'] ?? 0);
$product    = null;
$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order')->fetchAll();
$error = $success = '';

// Load existing product for edit
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM products WHERE id=? AND seller_id=?');
    $st->execute([$editId, $uid]);
    $product = $st->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $description = trim($_POST['description'] ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $orig        = (float)($_POST['original_price'] ?? 0);
    $stock       = (int)($_POST['stock']      ?? 0);
    $catId       = (int)($_POST['category_id']?? 0);
    $colorClass  = trim($_POST['color_class'] ?? 'emj-bg-1');
    $location    = trim($_POST['location']    ?? '');
    $freeShip    = isset($_POST['free_shipping']) ? 1 : 0;
    $isHot       = isset($_POST['is_hot']) ? 1 : 0;
    $isNew       = isset($_POST['is_new']) ? 1 : 0;
    $image       = $product['image'] ?? '';

    if (!$name || $price <= 0 || !$catId) {
        $error = 'Name, price and category are required.';
    } else {
        // Handle single image upload
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExt, array_keys($allowed)) || !in_array($file['type'], $allowed)) {
                $error = 'Only JPG, PNG, GIF, and WebP images are allowed.';
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB max
                $error = 'File size must not exceed 5MB.';
            } else {
                // Generate unique filename
                $newFileName = uniqid('product_') . '.' . $fileExt;
                $uploadPath = UPLOAD_DIR . $newFileName;
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Delete old image if editing
                    if ($editId && $product && !empty($product['image'])) {
                        $oldPath = UPLOAD_DIR . $product['image'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $image = $newFileName;
                } else {
                    $error = 'Failed to upload image. Please try again.';
                }
            }
        }

        if (!$error) {
            if ($editId && $product) {
                $pdo->prepare('
                    UPDATE products SET name=?,description=?,price=?,original_price=?,stock=?,
                    category_id=?,color_class=?,location=?,free_shipping=?,is_hot=?,is_new=?,image=?
                    WHERE id=? AND seller_id=?
                ')->execute([$name,$description,$price,$orig,$stock,$catId,$colorClass,$location,$freeShip,$isHot,$isNew,$image,$editId,$uid]);
                setFlash('success','Product updated!');
            } else {
                $pdo->prepare('
                    INSERT INTO products (seller_id,category_id,name,description,price,original_price,
                    stock,color_class,location,free_shipping,is_hot,is_new,image)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
                ')->execute([$uid,$catId,$name,$description,$price,$orig,$stock,$colorClass,$location,$freeShip,$isHot,$isNew,$image]);
                setFlash('success','Product added successfully!');
            }
            header('Location: ' . SITE_URL . '/seller/index.php');
            exit;
        }
    }
}

$p         = $product ?? [];
$pageTitle = $editId ? 'Edit Product' : 'Add Product';
require __DIR__ . '/../includes/header.php';
?>

<div class="main">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <a href="<?= SITE_URL ?>/seller/index.php" class="btn-outline">← Back</a>
    <h1 class="page-title" style="margin:0"><?= $editId ? '✏️ Edit Product' : '➕ Add New Product' ?></h1>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

  <div class="form-card">
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-grid-2">
        <div class="form-group">
          <label>Product Name *</label>
          <input type="text" name="name" value="<?= sanitize($p['name'] ?? '') ?>" required placeholder="Full product name">
        </div>
        <div class="form-group">
          <label>Category *</label>
          <select name="category_id" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($p['category_id'] ?? 0)==$c['id']?'selected':'' ?>>
                <?= $c['emoji'] ?> <?= sanitize($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Sale Price (₱) *</label>
          <input type="number" name="price" value="<?= $p['price'] ?? '' ?>" min="1" step="0.01" required>
        </div>
        <div class="form-group">
          <label>Original Price (₱)</label>
          <input type="number" name="original_price" value="<?= $p['original_price'] ?? '' ?>" min="0" step="0.01">
        </div>
        <div class="form-group">
          <label>Stock Quantity *</label>
          <input type="number" name="stock" value="<?= $p['stock'] ?? '' ?>" min="0" required>
        </div>
        <div class="form-group">
          <label>Location (City)</label>
          <input type="text" name="location" value="<?= sanitize($p['location'] ?? '') ?>" placeholder="e.g. Makati City">
        </div>
        <div class="form-group">
          <label>Card Color Theme</label>
          <select name="color_class">
            <?php
            $colors = ['emj-bg-1'=>'Orange/Red','emj-bg-2'=>'Blue','emj-bg-3'=>'Green','emj-bg-4'=>'Yellow','emj-bg-5'=>'Pink','emj-bg-6'=>'Purple','emj-bg-7'=>'Teal','emj-bg-8'=>'Peach'];
            foreach ($colors as $v=>$label): ?>
              <option value="<?= $v ?>" <?= ($p['color_class']??'emj-bg-1')===$v?'selected':'' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" rows="4" placeholder="Describe your product..."><?= sanitize($p['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label>Product Image</label>
        <?php if (!empty($p['image'])): ?>
          <div style="margin-bottom: 12px;">
            <img src="<?= UPLOAD_URL . sanitize($p['image']) ?>" alt="Product Image" style="max-width: 200px; height: auto; border-radius: 8px;">
            <p style="font-size: 12px; color: #666; margin-top: 8px;">Current image. Upload a new image to replace it.</p>
          </div>
        <?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" placeholder="Select product image">
        <small style="color: #999;">Supported formats: JPG, PNG, GIF, WebP. Max size: 5MB</small>
      </div>

      <div class="checkbox-row">
        <label><input type="checkbox" name="free_shipping" <?= ($p['free_shipping'] ?? 1) ? 'checked' : '' ?>> Free Shipping</label>
        <label><input type="checkbox" name="is_hot" <?= ($p['is_hot'] ?? 0) ? 'checked' : '' ?>> Mark as HOT</label>
        <label><input type="checkbox" name="is_new" <?= ($p['is_new'] ?? 0) ? 'checked' : '' ?>> Mark as NEW</label>
      </div>

      <div class="form-actions">
        <a href="<?= SITE_URL ?>/seller/index.php" class="btn-outline">Cancel</a>
        <button type="submit" class="btn-primary"><?= $editId ? 'Update Product' : 'Add Product' ?></button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
