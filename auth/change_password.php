<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = $_POST['current_password'] ?? '';
    $new = $_POST['password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';
    if (!password_verify($cur, $user['password'])) $error = 'Your current password is incorrect.';
    elseif (!valid_password($new)) $error = 'New password must be at least 8 characters and include letters and numbers.';
    elseif ($new !== $conf) $error = 'New passwords do not match.';
    elseif ($new === $cur) $error = 'Choose a password different from the current one.';
    else {
        $pdo->prepare('UPDATE users SET password=?, must_change_password=0 WHERE id=?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        flash('success', 'Password changed successfully.');
        redirect(dashboard_path());
    }
}

$page_title = 'Change Password';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h3 class="text-center mb-1">Change Password</h3>
    <?php if ($user['must_change_password']): ?><p class="text-center text-muted mb-3">Please replace the temporary password you were given.</p><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= sanitize($error) ?></div><?php endif; ?>
    <form method="POST">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required autofocus></div>
      <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="password" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required></div>
      <button class="btn btn-brand w-100 py-2">Update Password</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
