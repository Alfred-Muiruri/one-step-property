<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $st = $pdo->prepare("SELECT * FROM users WHERE email=? AND status='approved'");
    $st->execute([$email]);
    $user = $st->fetch();
    // Same message whether or not the account exists (prevents email guessing).
    if ($user && seconds_until_resend($user['id'], 'reset') === 0) {
        send_otp_email($user, issue_otp($user['id'], 'reset'), 'reset your password');
    }
    $_SESSION['reset_email'] = $email;
    flash('success', 'If that email has an account, a 6-digit reset code is on its way.');
    redirect('auth/reset_password.php');
}

$page_title = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h3 class="text-center mb-1">Reset Password</h3>
    <p class="text-center text-muted mb-4">Enter your account email and we'll send a reset code.</p>
    <form method="POST">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" required autofocus></div>
      <button type="submit" class="btn btn-brand w-100 py-2">Send Reset Code</button>
    </form>
    <p class="text-center mt-3 mb-0 small"><a href="<?= base_url('auth/login.php') ?>">Back to login</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
