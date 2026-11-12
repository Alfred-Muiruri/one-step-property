<?php
require_once __DIR__ . '/../includes/functions.php';
if (is_logged_in()) redirect(dashboard_path());

$errors = [];
$old = ['tenant_type' => 'student', 'full_name' => '', 'email' => '', 'phone' => '', 'id_number' => '', 'organization' => '', 'notify_pref' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['full_name', 'email', 'phone', 'id_number', 'organization'] as $k) $old[$k] = trim($_POST[$k] ?? '');
    $old['tenant_type'] = ($_POST['tenant_type'] ?? '') === 'non_student' ? 'non_student' : 'student';
    $old['notify_pref'] = in_array($_POST['notify_pref'] ?? '', ['email', 'sms', 'both'], true) ? $_POST['notify_pref'] : '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!valid_name($old['full_name'])) $errors[] = 'Full name must contain letters only (no digits or symbols).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email. You will receive your login codes there.';
    $phone = normalize_phone($old['phone']);
    if (!$phone) $errors[] = 'Phone number must be exactly 10 digits, e.g. 0712345678.';
    if ($old['id_number'] !== '') {
        if ($old['tenant_type'] === 'student') { if (!valid_school_id($old['id_number'])) $errors[] = 'Student ID may contain letters, digits, / or - only (3–30 characters).'; }
        elseif (!valid_national_id($old['id_number'])) $errors[] = 'National ID must be exactly 8 digits.';
    }
    if ($old['notify_pref'] === '') $errors[] = 'Please choose how you want to receive booking updates.';
    if ($old['organization'] !== '' && !valid_org($old['organization'])) $errors[] = 'University / employer name must contain letters (no random numbers or symbols).';
    if ($old['tenant_type'] === 'student' && $old['organization'] === '') $errors[] = 'Please enter your university or college.';
    if (!valid_password($password)) $errors[] = 'Password must be at least 8 characters and include letters and numbers.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?'); $st->execute([$old['email']]);
        if ($st->fetch()) $errors[] = 'An account with that email already exists.';
        if (phone_in_use($phone)) $errors[] = PHONE_TAKEN_MSG;
        if ($old['id_number'] !== '' && id_in_use($old['id_number'])) $errors[] = 'That ID number is already registered to another account.';
    }

    if (!$errors) {
        $pdo->prepare(
            "INSERT INTO users (role, full_name, email, phone, password, status, tenant_type, organization, id_number, notify_pref)
             VALUES ('tenant',?,?,?,?, 'approved',?,?,?,?)"
        )->execute([
            $old['full_name'], $old['email'], $phone, password_hash($password, PASSWORD_DEFAULT),
            $old['tenant_type'], $old['organization'] ?: null, $old['id_number'] ?: null, $old['notify_pref'],
        ]);
        $new = $pdo->prepare('SELECT * FROM users WHERE id=?'); $new->execute([$pdo->lastInsertId()]);
        notify_user($new->fetch(), 'Welcome to ' . SITE_NAME, 'Your account is ready. Browse available houses, send a booking request and we will keep you updated here.', 'Browse houses', 'houses.php');
        flash('success', 'Account created! Log in — we will email you a code to confirm it is you.');
        redirect('auth/login.php');
    }
}

$page_title = 'Create Account';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card" style="max-width:520px">
    <h3 class="text-center mb-1">Create your account</h3>
    <p class="text-center text-muted mb-4">For tenants — students and non-students alike.</p>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label fw-semibold">I am a</label>
        <div class="btn-group w-100" role="group">
          <input type="radio" class="btn-check" name="tenant_type" id="tS" value="student" <?= $old['tenant_type']==='student'?'checked':'' ?>>
          <label class="btn btn-outline-brand" for="tS"><i class="bi bi-mortarboard"></i> Student</label>
          <input type="radio" class="btn-check" name="tenant_type" id="tN" value="non_student" <?= $old['tenant_type']==='non_student'?'checked':'' ?>>
          <label class="btn btn-outline-brand" for="tN"><i class="bi bi-briefcase"></i> Non-student</label>
        </div>
      </div>
      <div class="mb-3"><label class="form-label">Full Name</label>
        <input data-v="name" maxlength="100" pattern="[A-Za-z .'\-]+" title="Letters only — no digits or symbols" type="text" name="full_name" class="form-control" value="<?= sanitize($old['full_name']) ?>" required></div>
      <div class="mb-3"><label class="form-label">Email Address <span class="text-muted small">(login codes are sent here)</span></label>
        <input type="email" name="email" class="form-control" value="<?= sanitize($old['email']) ?>" required></div>
      <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Phone Number</label>
          <input data-v="phone" maxlength="10" minlength="10" inputmode="numeric" pattern="0[17][0-9]{8}" title="Exactly 10 digits, e.g. 0712345678" type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= sanitize($old['phone']) ?>" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">National ID / Student ID <span class="text-muted small">(optional)</span></label>
          <input data-v="idauto" maxlength="30" title="National ID: 8 digits. Student ID: letters and digits" type="text" name="id_number" class="form-control" value="<?= sanitize($old['id_number']) ?>"></div>
      </div>
      <div class="mb-3"><label class="form-label" id="orgLabel">University / College</label>
        <input type="text" name="organization" class="form-control" value="<?= sanitize($old['organization']) ?>"></div>
      <div class="mb-3">
        <label class="form-label">Send me booking updates by</label>
        <select name="notify_pref" class="form-select" required><option value="">Select how to notify…</option>
          <option value="email" <?= $old['notify_pref']==='email'?'selected':'' ?>>Email</option>
          <option value="sms"   <?= $old['notify_pref']==='sms'?'selected':'' ?>>SMS</option>
          <option value="both"  <?= $old['notify_pref']==='both'?'selected':'' ?>>Email and SMS</option>
        </select>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
      </div>
      <button type="submit" class="btn btn-brand w-100 py-2">Create Account</button>
    </form>
    <p class="text-center mt-3 mb-1 small">Already have an account? <a href="<?= base_url('auth/login.php') ?>">Log in</a></p>
    <p class="text-center text-muted small mb-0">Landlords: accounts are created by <?= SITE_NAME ?> after your management agreement.</p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
