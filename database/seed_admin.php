<?php
/**
 * ONE-TIME SETUP — creates the first administrator.
 * Open  http://localhost/one-step-property/database/seed_admin.php  once,
 * use the REAL email address of the admin (login codes are sent there),
 * then DELETE this file.
 */
define('SKIP_CSRF', true);
require_once __DIR__ . '/../includes/functions.php';

$exists = (int)$pdo->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch()['c'];
$msg = ''; $err = '';

if (!$exists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $phone = normalize_phone($_POST['phone'] ?? ''); $pass = $_POST['password'] ?? '';
    if (!valid_name($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$phone) $err = 'Enter your name, a valid email and a 10-digit phone number (letters-only name).';
    elseif (phone_in_use($phone)) $err = PHONE_TAKEN_MSG;
    elseif (!valid_password($pass)) $err = 'Password must be at least 8 characters with letters and numbers.';
    else {
        $pdo->prepare("INSERT INTO users (role, full_name, email, phone, password, status, notify_pref, is_super) VALUES ('admin',?,?,?,?, 'approved','email',1)")
            ->execute([$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT)]);
        $msg = 'Admin created. Now DELETE database/seed_admin.php and log in.';
        $exists = 1;
    }
}
?><!DOCTYPE html><html><head><meta charset="utf-8"><title>Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light"><div class="container py-5" style="max-width:480px">
<h3>One Step — create administrator</h3>
<?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($exists && !$msg): ?><div class="alert alert-warning">An admin already exists. Delete this file now.</div>
<?php elseif (!$exists): ?>
<form method="POST" class="card card-body">
  <label class="form-label">Full name</label><input name="full_name" data-v="name" class="form-control mb-2" required>
  <label class="form-label">Email (codes are sent here)</label><input type="email" name="email" class="form-control mb-2" required>
  <label class="form-label">Phone</label><input name="phone" data-v="phone" maxlength="10" inputmode="numeric" class="form-control mb-2" placeholder="07XXXXXXXX" required>
  <label class="form-label">Password</label><input type="password" name="password" class="form-control mb-3" required>
  <button class="btn btn-success">Create admin</button>
</form><?php endif; ?>
</div></body></html>
