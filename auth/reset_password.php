<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$email = $_SESSION['reset_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $conf  = $_POST['confirm_password'] ?? '';

    $st = $pdo->prepare("SELECT * FROM users WHERE email=? AND status='approved'");
    $st->execute([$email]);
    $user = $st->fetch();

    if (!valid_password($pass)) $error = 'Password must be at least 8 characters and include letters and numbers.';
    elseif ($pass !== $conf)    $error = 'Passwords do not match.';
    elseif (!$user || check_otp($user['id'], 'reset', $code) !== 'ok') $error = 'That code is invalid or has expired. Request a new one.';
    else {
        $pdo->prepare('UPDATE users SET password=?, must_change_password=0 WHERE id=?')
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $user['id']]);
        notify_user($user, 'Your password was changed', 'Your ' . SITE_NAME . ' password was just changed. If this was not you, contact us immediately.');
        unset($_SESSION['reset_email']);
        flash('success', 'Password updated. You can now log in.');
        redirect('auth/login.php');
    }
}

$page_title = 'Set New Password';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h3 class="text-center mb-1">Set a New Password</h3>
    <p class="text-center text-muted mb-4">Enter the code we emailed you.</p>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= sanitize($error) ?></div><?php endif; ?>
    <form method="POST">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= sanitize($email) ?>" required></div>
      <div class="mb-3"><label class="form-label">6-digit Code</label>
        <input type="text" name="code" class="form-control" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required></div>
      <div class="mb-3"><label class="form-label">New Password</label>
        <input type="password" name="password" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" required></div>
      <button type="submit" class="btn btn-brand w-100 py-2">Reset Password</button>
    </form>
    <p class="text-center mt-3 mb-0 small"><a href="<?= base_url('auth/forgot_password.php') ?>">Send a new code</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
