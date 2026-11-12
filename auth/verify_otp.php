<?php
require_once __DIR__ . '/../includes/functions.php';

$uid = (int)($_SESSION['pending_user_id'] ?? 0);
if (!$uid || (time() - ($_SESSION['pending_at'] ?? 0)) > 1800) {
    unset($_SESSION['pending_user_id'], $_SESSION['pending_at']);
    redirect('auth/login.php');
}
$s = $pdo->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$uid]); $user = $s->fetch();
if (!$user || $user['status'] !== 'approved') redirect('auth/login.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['do'] ?? '') === 'resend') {
        if (seconds_until_resend($uid, 'login') > 0) {
            $error = 'Please wait a moment before requesting another code.';
        } else {
            [$ok] = send_otp_email($user, issue_otp($uid, 'login'), 'log in');
            if ($ok) flash('success', 'A new code has been sent to your email.');
            else $error = 'Could not send the code. Please try again.';
            if ($ok) redirect('auth/verify_otp.php');
        }
    } else {
        $res = check_otp($uid, 'login', $_POST['otp'] ?? '');
        if ($res === 'ok') {
            session_regenerate_id(true);
            unset($_SESSION['pending_user_id'], $_SESSION['pending_at']);
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['full_name']   = $user['full_name'];
            $_SESSION['last_active'] = time();
            $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$uid]);
            flash('success', 'Welcome back, ' . $user['full_name'] . '!');
            redirect($user['must_change_password'] ? 'auth/change_password.php' : dashboard_path());
        }
        $error = [
            'invalid' => 'That code is not correct. Please check your email and try again.',
            'expired' => 'That code has expired. Request a new one below.',
            'locked'  => 'Too many wrong attempts. Request a new code below.',
        ][$res];
    }
}

$wait = seconds_until_resend($uid, 'login');
$page_title = 'Verify Code';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card text-center">
    <div class="fs-1 text-brand"><i class="bi bi-envelope-check"></i></div>
    <h3 class="mb-1">Check your email</h3>
    <p class="text-muted mb-4">We sent a 6-digit code to <strong><?= sanitize(mask_email($user['email'])) ?></strong>. It expires in <?= OTP_TTL_MINUTES ?> minutes.</p>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= sanitize($error) ?></div><?php endif; ?>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="text" name="otp" id="otpInput" class="form-control otp-input mb-3" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="••••••" required autofocus>
      <button class="btn btn-brand w-100 py-2" type="submit">Verify &amp; Log In</button>
    </form>
    <form method="POST" class="mt-3">
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="resend">
      <button id="resendBtn" class="btn btn-link btn-sm" type="submit">Resend code<span id="resendCountdown" data-seconds="<?= (int)$wait ?>"></span></button>
    </form>
    <p class="small mb-0"><a href="<?= base_url('auth/login.php') ?>">Use a different account</a></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
