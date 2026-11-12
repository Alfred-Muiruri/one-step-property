<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect(dashboard_path());

$error = '';
$email = '';
$_SESSION['login_fails'] = $_SESSION['login_fails'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (($_SESSION['lock_until'] ?? 0) > time()) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['login_fails']++;
            if ($_SESSION['login_fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['login_fails'] = 0; }
            $error = 'Incorrect email or password.';
        } elseif ($user['status'] !== 'approved') {
            $error = 'Your account is suspended. Please contact ' . SITE_NAME . '.';
        } else {
            $_SESSION['login_fails'] = 0;
            $code = issue_otp($user['id'], 'login');
            [$ok, $err] = send_otp_email($user, $code, 'log in');
            if (!$ok) {
                $error = 'We could not send your verification code by email right now. Please try again shortly or contact support.';
            } else {
                session_regenerate_id(true);
                $_SESSION['pending_user_id'] = $user['id'];
                $_SESSION['pending_at'] = time();
                redirect('auth/verify_otp.php');
            }
        }
    }
}

$page_title = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <h3 class="text-center mb-1">Welcome Back</h3>
    <p class="text-center text-muted mb-4">Log in to <?= SITE_NAME ?></p>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= sanitize($error) ?></div><?php endif; ?>
    <form method="POST" autocomplete="on">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= sanitize($email) ?>" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-brand w-100 py-2">Continue</button>
      <p class="text-center text-muted small mt-3 mb-0"><i class="bi bi-shield-lock"></i> Next, we'll email you a 6-digit code to confirm it's really you.</p>
    </form>
    <p class="text-center mt-3 mb-1 small"><a href="<?= base_url('auth/forgot_password.php') ?>">Forgot password?</a></p>
    <p class="text-center small mb-0">New tenant? <a href="<?= base_url('auth/register.php') ?>">Create an account</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
