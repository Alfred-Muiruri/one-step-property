<?php
require_once __DIR__ . '/../includes/functions.php';
require_roles(['admin', 'staff']);
$admin = is_admin();

$errors = [];
$old = ['full_name'=>'','email'=>'','phone'=>'','id_number'=>'','agreement_ref'=>'','agreement_date'=>'','notify_pref'=>'email'];
$created = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        foreach ($old as $k => $v) $old[$k] = trim($_POST[$k] ?? '');
        if ($old['agreement_ref'] === '') $errors[] = 'Enter the management agreement reference.';
        if ($old['agreement_date'] !== '' && !DateTime::createFromFormat('Y-m-d', $old['agreement_date'])) $errors[] = 'Invalid agreement date.';
        if (!$errors) {
            [$uerr, $newId, $ok, $tmp, $err] = create_managed_user('landlord', $old);
            $errors = array_merge($errors, $uerr);
            if (!$errors) {
                $pdo->prepare('INSERT INTO landlord_profiles (user_id, agreement_ref, agreement_date) VALUES (?,?,?)')
                    ->execute([$newId, $old['agreement_ref'], $old['agreement_date'] ?: null]);
                log_activity('Landlord account created', $old['full_name'] . ' (' . $old['agreement_ref'] . ')');
                $created = ['name' => $old['full_name'], 'email' => $old['email'], 'emailed' => $ok, 'tmp' => $tmp, 'err' => $err];
                $old = array_fill_keys(array_keys($old), ''); $old['notify_pref'] = 'email';
            }
        }
    } else {
        $id = (int)($_POST['user_id'] ?? 0);
        if ($action === 'suspend')   { $pdo->prepare("UPDATE users SET status='suspended' WHERE id=? AND role='landlord'")->execute([$id]); flash('success', 'Landlord suspended.'); }
        if ($action === 'reinstate') { $pdo->prepare("UPDATE users SET status='approved' WHERE id=? AND role='landlord'")->execute([$id]); flash('success', 'Landlord reinstated.'); }
        if ($action === 'delete')    { $pdo->prepare("DELETE FROM users WHERE id=? AND role='landlord'")->execute([$id]); flash('success', 'Landlord and their listings deleted.'); }
        redirect('admin/landlords.php');
    }
}

$landlords = $pdo->query(
    "SELECT u.*, lp.agreement_ref, lp.agreement_date, (SELECT COUNT(*) FROM houses WHERE landlord_id=u.id) AS house_count
     FROM users u LEFT JOIN landlord_profiles lp ON lp.user_id=u.id WHERE u.role='landlord' ORDER BY u.created_at DESC"
)->fetchAll();

$page_title = 'Landlords';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Landlords</h3>

  <?php if ($created): ?>
    <div class="alert alert-<?= $created['emailed'] ? 'success' : 'warning' ?>">
      <?php if ($created['emailed']): ?>
        Account created for <strong><?= sanitize($created['name']) ?></strong>. Login details were emailed to <?= sanitize($created['email']) ?>.
      <?php else: ?>
        Account created for <strong><?= sanitize($created['name']) ?></strong>, but the email could not be sent (<?= sanitize($created['err']) ?>).
        Give them this temporary password securely — it is shown only once:
        <span class="fw-bold font-monospace fs-5"><?= sanitize($created['tmp']) ?></span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($admin): ?>
  <div class="sidebar-card mb-4">
    <h5><i class="bi bi-person-plus"></i> Create a landlord account <span class="text-muted small">(after the agreement is signed)</span></h5>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="row g-3">
        <div class="col-md-4"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= sanitize($old['full_name']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($old['email']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= sanitize($old['phone']) ?>" required></div>
        <div class="col-md-3"><label class="form-label">National ID <span class="text-muted small">optional</span></label><input type="text" name="id_number" class="form-control" value="<?= sanitize($old['id_number']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Agreement Ref.</label><input type="text" name="agreement_ref" class="form-control" placeholder="e.g. OSS/AGR/2026/014" value="<?= sanitize($old['agreement_ref']) ?>" required></div>
        <div class="col-md-3"><label class="form-label">Agreement Date</label><input type="date" name="agreement_date" class="form-control" value="<?= sanitize($old['agreement_date']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Notify by</label>
          <select name="notify_pref" class="form-select"><?php foreach (['email'=>'Email','sms'=>'SMS','both'=>'Email and SMS'] as $k=>$l): ?><option value="<?= $k ?>" <?= $old['notify_pref']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      </div>
      <button class="btn btn-brand mt-3 px-4">Create &amp; Email Login Details</button>
    </form>
  </div>
  <?php else: ?><p class="text-muted small">View only — landlord accounts are created by the administrator after the management agreement is signed. Use <a href="<?= base_url('admin/add_house.php') ?>">Add House</a> to list houses on their behalf.</p><?php endif; ?>

  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Name</th><th>Contact</th><th>Agreement</th><th>Houses</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($landlords as $l): ?><tr>
      <td><?= sanitize($l['full_name']) ?></td>
      <td class="small"><?= sanitize($l['phone']) ?><br><?= sanitize($l['email']) ?></td>
      <td class="small"><?= sanitize($l['agreement_ref'] ?? '—') ?><br><span class="text-muted"><?= !empty($l['agreement_date']) ? date('d M Y', strtotime($l['agreement_date'])) : '' ?></span></td>
      <td><?= (int)$l['house_count'] ?></td>
      <td><span class="badge <?= badge_class($l['status']) ?>"><?= ucfirst($l['status']) ?></span></td>
      <td><?php if (!$admin): ?><span class="text-muted small">—</span><?php else: ?><div class="d-flex gap-1 flex-wrap">
        <?php if ($l['status'] === 'approved'): ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $l['id'] ?>"><input type="hidden" name="action" value="suspend"><button class="btn btn-sm btn-outline-danger">Suspend</button></form>
        <?php else: ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $l['id'] ?>"><input type="hidden" name="action" value="reinstate"><button class="btn btn-sm btn-brand">Reinstate</button></form>
        <?php endif; ?>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $l['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-secondary confirm-action" data-confirm="Delete this landlord and ALL their houses?">Delete</button></form>
      </div><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$landlords): ?><p class="text-muted">No landlords yet — create the first one above.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
