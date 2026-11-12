<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('tenant');
$u = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['full_name'] ?? '');
    $phone = normalize_phone($_POST['phone'] ?? '');
    $org   = trim($_POST['organization'] ?? '');
    $type  = ($_POST['tenant_type'] ?? '') === 'non_student' ? 'non_student' : 'student';
    $pref  = in_array($_POST['notify_pref'] ?? '', ['email','sms','both'], true) ? $_POST['notify_pref'] : '';
    if (!valid_name($name)) $errors[] = 'Full name must contain letters only (no digits or symbols).';
    if (!$phone) $errors[] = 'Phone number must be exactly 10 digits, e.g. 0712345678.';
    if ($pref === '') $errors[] = 'Please choose how you want to receive updates.';
    if ($org !== '' && !valid_org($org)) $errors[] = 'University / employer name must contain letters (no random numbers or symbols).';
    if ($phone && phone_in_use($phone, $u['id'])) $errors[] = PHONE_TAKEN_MSG;
    if ($type === 'student' && $org === '') $errors[] = 'Please enter your university or college.';
    if (!$errors) {
        $pdo->prepare('UPDATE users SET full_name=?, phone=?, tenant_type=?, organization=?, notify_pref=? WHERE id=?')
            ->execute([$name, $phone, $type, $org ?: null, $pref, $u['id']]);
        $_SESSION['full_name'] = $name;
        flash('success', 'Profile updated.');
        redirect('tenant/profile.php');
    }
    $u = array_merge($u, ['full_name' => $name, 'phone' => $_POST['phone'] ?? '', 'tenant_type' => $type, 'organization' => $org, 'notify_pref' => $pref]);
}

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width:720px">
  <h3 class="section-title">My Profile</h3>
  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST"><?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name</label><input data-v="name" maxlength="100" pattern="[A-Za-z .'\-]+" title="Letters only — no digits or symbols" type="text" name="full_name" class="form-control" value="<?= sanitize($u['full_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email <span class="text-muted small">(login codes go here)</span></label><input type="email" class="form-control" value="<?= sanitize($u['email']) ?>" disabled></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input data-v="phone" maxlength="10" minlength="10" inputmode="numeric" pattern="0[17][0-9]{8}" title="Exactly 10 digits, e.g. 0712345678" type="text" name="phone" class="form-control" value="<?= sanitize($u['phone']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">I am a</label>
          <div class="btn-group w-100">
            <input type="radio" class="btn-check" name="tenant_type" id="tS" value="student" <?= $u['tenant_type']==='student'?'checked':'' ?>><label class="btn btn-outline-brand" for="tS">Student</label>
            <input type="radio" class="btn-check" name="tenant_type" id="tN" value="non_student" <?= $u['tenant_type']==='non_student'?'checked':'' ?>><label class="btn btn-outline-brand" for="tN">Non-student</label>
          </div></div>
        <div class="col-md-6"><label class="form-label" id="orgLabel">University / College</label><input type="text" name="organization" class="form-control" value="<?= sanitize($u['organization']) ?>"></div>
        <div class="col-md-6"><label class="form-label">Send me updates by</label>
          <select name="notify_pref" class="form-select">
            <?php foreach (['email'=>'Email','sms'=>'SMS','both'=>'Email and SMS'] as $k => $l): ?><option value="<?= $k ?>" <?= $u['notify_pref']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
          </select></div>
      </div>
      <button class="btn btn-brand mt-4 px-4">Save Changes</button>
      <a href="<?= base_url('auth/change_password.php') ?>" class="btn btn-outline-brand mt-4">Change Password</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
