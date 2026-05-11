<?php
// login.php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$login || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $pdo = getDB();
            $st  = $pdo->prepare('SELECT * FROM users WHERE (email=? OR username=?) AND is_active=1 LIMIT 1');
            $st->execute([$login, $login]);
            $user = $st->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user']    = $user;
                unset($_SESSION['cart_count']);
                setFlash('success', 'Welcome back, ' . $user['username'] . '!');
                $redirect = $_GET['redirect'] ?? SITE_URL . '/index.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = 'Invalid email/username or password.';
            }
        } catch (Exception $e) {
            $error = 'Database error. Please try again.';
        }
    }
}

$pageTitle = 'Log In';
require __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
  <div class="auth-card">
    <div class="auth-hero">
      <div class="auth-logo">Shopee <span class="logo-badge">PH</span></div>
      <p>Log in to your Shopee account</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="auth-form">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label>Email or Username</label>
        <input type="text" name="login" placeholder="Enter email or username"
               value="<?= sanitize($_POST['login'] ?? '') ?>" required autocomplete="username">
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <input type="password" name="password" id="pw" placeholder="Enter your password" required>
          <button type="button" class="toggle-pw" onclick="togglePw()">👁</button>
        </div>
      </div>

      <button type="submit" class="btn-primary btn-full">Log In</button>
    </form>

    <div class="auth-divider"><span>or</span></div>
    <p class="auth-switch">Don't have an account? <a href="<?= SITE_URL ?>/register.php">Sign Up</a></p>

  </div>
</div>

<script>
function fillDemo(u,p){
  document.querySelector('[name=login]').value=u;
  document.querySelector('[name=password]').value=p;
}
function togglePw(){
  const i=document.getElementById('pw');
  i.type = i.type==='password'?'text':'password';
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
