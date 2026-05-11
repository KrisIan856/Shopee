<?php
// register.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    $role      = in_array($_POST['role'] ?? 'buyer', ['buyer','seller']) ? $_POST['role'] : 'buyer';

    if (!$username || !$email || !$password) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDB();
            $st  = $pdo->prepare('SELECT id FROM users WHERE email=? OR username=?');
            $st->execute([$email, $username]);
            if ($st->fetch()) {
                $error = 'Email or username already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins  = $pdo->prepare('INSERT INTO users (username,email,password,role) VALUES (?,?,?,?)');
                $ins->execute([$username, $email, $hash, $role]);
                setFlash('success', 'Account created! Please log in.');
                header('Location: ' . SITE_URL . '/login.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Sign Up';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-hero">
      <div class="auth-logo">Shopee <span class="logo-badge">PH</span></div>
      <p>Create your Shopee account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Choose a username"
               value="<?= sanitize($_POST['username'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="Your email address"
               value="<?= sanitize($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="At least 6 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="password2" placeholder="Repeat password" required>
      </div>
      <div class="form-group">
        <label>I want to</label>
        <div class="role-choice">
          <label class="role-option">
            <input type="radio" name="role" value="buyer" <?= ($_POST['role'] ?? 'buyer')==='buyer'?'checked':'' ?>>
            <span>🛒 Buy Products</span>
          </label>
          <label class="role-option">
            <input type="radio" name="role" value="seller" <?= ($_POST['role'] ?? '')==='seller'?'checked':'' ?>>
            <span>🏪 Sell Products</span>
          </label>
        </div>
      </div>

      <button type="submit" class="btn-primary btn-full">Create Account</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="<?= SITE_URL ?>/login.php">Log In</a></p>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
