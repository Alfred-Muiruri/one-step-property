<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('landlord');
$u = current_user();
$lp = $pdo->prepare('SELECT * FROM landlord_profiles WHERE user_id=?'); $lp->execute([$u['id']]); $lp = $lp->fetch() ?: [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = normalize_phone($_POST['phone'] ?? '');
    $pref  = in_array($_POST['notify_pref'] ?? '', ['email','sms','both'], true) ? $_POST['notify_pref'] : '';
    if (!$phone) $errors[] = 'Phone number must be exactly 10 digits, e.g. 0712345678.';
    if ($pref === '') $errors[] = 'Please choose how you want to receive updates.';
    if ($phone && phone_in_use($phone, $u['id'])) $errors[] = PHONE_TAKEN_MSG;
    if (!$errors) {
        $pdo->prepare('UPDATE users SET phone=?, notify_pref=? WHERE id=?')->execute([$phone, $pref, $u['id']]);
        flash('success', 'Profile updated.');
        redirect('landlord/profile.php');
    }
}

$page_title = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4" style="max-width:720px">
  <h3 class="section-title">My Profile</h3>
  <div class="sidebar-card mb-3">
    <h6 class="fw-bold"><i class="bi bi-file-earmark-check"></i> Management Agreement</h6>
    <p class="mb-1 small">Reference: <strong><?= sanitize($lp['agreement_ref'] ?? '—') ?></strong></p>
    <p class="mb-0 small">Signed: <strong><?= !empty($lp['agreement_date']) ? date('d M Y', strtotime($lp['agreement_date'])) : '—' ?></strong></p>
  </div>
  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST"><?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Full Name</label><input class="form-control" value="<?= sanitize($u['full_name']) ?>" disabled></div>
        <div class="col-md-6"><label class="form-label">Email (login codes go here)</label><input class="form-control" value="<?= sanitize($u['email']) ?>" disabled></div>
        <div class="col-md-6"><label class="form-label">Phone</label><input data-v="phone" maxlength="10" minlength="10" inputmode="numeric" pattern="0[17][0-9]{8}" title="Exactly 10 digits, e.g. 0712345678" type="text" name="phone" class="form-control" value="<?= sanitize($u['phone']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Send me updates by</label>
          <select name="notify_pref" class="form-select">
            <?php foreach (['email'=>'Email','sms'=>'SMS','both'=>'Email and SMS'] as $k => $l): ?><option value="<?= $k ?>" <?= $u['notify_pref']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
          </select></div>
      </div>
      <p class="small text-muted mt-3 mb-0">To change your name or email, please contact <?= SITE_NAME ?>.</p>
      <button class="btn btn-brand mt-3 px-4">Save</button>
      <a href="<?= base_url('auth/change_password.php') ?>" class="btn btn-outline-brand mt-3">Change Password</a>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
