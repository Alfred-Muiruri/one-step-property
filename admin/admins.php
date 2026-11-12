<?php
require_once __DIR__ . '/../includes/functions.php';
require_super();
$me = current_user();

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'id_number' => '', 'notify_pref' => ''];
$created = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        foreach ($old as $k => $v) $old[$k] = trim($_POST[$k] ?? '');
        $makeSuper = !empty($_POST['is_super']);
        [$errors, $newId, $ok, $tmp, $err] = create_managed_user('admin', $old);
        if (!$errors) {
            if ($makeSuper) $pdo->prepare('UPDATE users SET is_super=1 WHERE id=?')->execute([$newId]);
            log_activity('Admin account created', $old['full_name'] . ($makeSuper ? ' (super admin)' : ''));
            $created = ['name' => $old['full_name'], 'email' => $old['email'], 'emailed' => $ok, 'tmp' => $tmp, 'err' => $err];
            $old = ['full_name' => '', 'email' => '', 'phone' => '', 'id_number' => '', 'notify_pref' => ''];
        }
    } else {
        $id = (int)($_POST['user_id'] ?? 0);
        $t = $pdo->prepare("SELECT * FROM users WHERE id=? AND role='admin'"); $t->execute([$id]); $target = $t->fetch();
        if (!$target || $id === (int)$me['id']) {
            flash('danger', 'You cannot change your own account here, and the account must be an admin.');
        } elseif ($action === 'suspend')   { $pdo->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$id]); log_activity('Admin suspended', $target['full_name']); flash('success', 'Admin suspended — they can no longer log in.'); }
        elseif ($action === 'reinstate')   { $pdo->prepare("UPDATE users SET status='approved' WHERE id=?")->execute([$id]); log_activity('Admin reinstated', $target['full_name']); flash('success', 'Admin reinstated.'); }
        elseif ($action === 'delete')      { $pdo->prepare("DELETE FROM users WHERE id=? AND role='admin'")->execute([$id]); log_activity('Admin deleted', $target['full_name']); flash('success', 'Admin account deleted.'); }
        elseif ($action === 'grant_super') { $pdo->prepare('UPDATE users SET is_super=1 WHERE id=?')->execute([$id]); log_activity('Super admin granted', $target['full_name']); notify_user($target, 'You are now a super admin', 'You can now manage other admins and the system settings.', 'Open settings', 'admin/settings.php'); flash('success', $target['full_name'] . ' is now a super admin.'); }
        elseif ($action === 'revoke_super'){ $pdo->prepare('UPDATE users SET is_super=0 WHERE id=?')->execute([$id]); log_activity('Super admin revoked', $target['full_name']); flash('success', 'Super admin rights removed.'); }
        redirect('admin/admins.php');
    }
}

$admins = $pdo->query("SELECT * FROM users WHERE role='admin' ORDER BY is_super DESC, created_at ASC")->fetchAll();

$page_title = 'Admins';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Administrators</h3>
  <p class="text-muted small"><strong>Admins</strong> have full access to landlords, houses, tenants, bookings, staff, agents, commissions and messages. <strong>Super admins</strong> can also add other admins and change system Settings.</p>

  <?php if ($created): ?>
    <div class="alert alert-<?= $created['emailed'] ? 'success' : 'warning' ?>">
      <?php if ($created['emailed']): ?>Admin account created for <strong><?= sanitize($created['name']) ?></strong>. Login details were emailed to <?= sanitize($created['email']) ?>.
      <?php else: ?>Admin account created for <strong><?= sanitize($created['name']) ?></strong>, but the email could not be sent (<?= sanitize($created['err']) ?>). Give them this temporary password securely — shown once:
        <span class="fw-bold font-monospace fs-5"><?= sanitize($created['tmp']) ?></span><?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="sidebar-card mb-4">
    <h5><i class="bi bi-person-plus"></i> Add an admin</h5>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Full Name</label><input data-v="name" maxlength="100" pattern="[A-Za-z .'\-]+" title="Letters only — no digits or symbols" type="text" name="full_name" class="form-control" value="<?= sanitize($old['full_name']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Email <span class="text-muted small">(their login codes go here)</span></label><input type="email" name="email" class="form-control" value="<?= sanitize($old['email']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input data-v="phone" maxlength="10" minlength="10" inputmode="numeric" pattern="0[17][0-9]{8}" title="Exactly 10 digits, e.g. 0712345678" type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= sanitize($old['phone']) ?>" required></div>
        <div class="col-md-3"><label class="form-label">National ID <span class="text-muted small">optional</span></label><input data-v="nid" maxlength="8" inputmode="numeric" pattern="[0-9]{8}" title="Exactly 8 digits" type="text" name="id_number" class="form-control" value="<?= sanitize($old['id_number']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Notify by</label>
          <select name="notify_pref" class="form-select" required><option value="">Select how to notify…</option><?php foreach (['email'=>'Email','sms'=>'SMS','both'=>'Email and SMS'] as $k=>$l): ?><option value="<?= $k ?>" <?= $old['notify_pref']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_super" id="sup" value="1"><label class="form-check-label" for="sup">Also make them a <strong>super admin</strong> (can add admins and change Settings)</label></div></div>
      </div>
      <button class="btn btn-brand mt-3 px-4">Create &amp; Email Login Details</button>
    </form>
  </div>

  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Name</th><th>Contact</th><th>Level</th><th>Last login</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($admins as $a): $self = (int)$a['id'] === (int)$me['id']; ?><tr>
      <td><?= sanitize($a['full_name']) ?><?= $self ? ' <span class="badge bg-light text-dark border">You</span>' : '' ?></td>
      <td class="small"><?= sanitize($a['phone']) ?><br><?= sanitize($a['email']) ?></td>
      <td><span class="badge <?= $a['is_super'] ? 'badge-approved' : 'badge-type' ?>"><?= $a['is_super'] ? 'Super admin' : 'Admin' ?></span></td>
      <td class="small"><?= $a['last_login_at'] ? date('d M Y H:i', strtotime($a['last_login_at'])) : '—' ?></td>
      <td><span class="badge <?= badge_class($a['status']) ?>"><?= ucfirst($a['status']) ?></span></td>
      <td><?php if ($self): ?><span class="text-muted small">—</span><?php else: ?><div class="d-flex gap-1 flex-wrap">
        <?php if ($a['status'] === 'approved'): ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $a['id'] ?>"><input type="hidden" name="action" value="suspend"><button class="btn btn-sm btn-outline-danger">Suspend</button></form>
        <?php else: ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $a['id'] ?>"><input type="hidden" name="action" value="reinstate"><button class="btn btn-sm btn-brand">Reinstate</button></form>
        <?php endif; ?>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $a['id'] ?>"><input type="hidden" name="action" value="<?= $a['is_super'] ? 'revoke_super' : 'grant_super' ?>">
          <button class="btn btn-sm btn-outline-brand confirm-action" data-confirm="<?= $a['is_super'] ? 'Remove super admin rights from this person?' : 'Give this person super admin rights?' ?>"><?= $a['is_super'] ? 'Remove super' : 'Make super' ?></button></form>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $a['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-secondary confirm-action" data-confirm="Delete this admin account permanently?">Delete</button></form>
      </div><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
