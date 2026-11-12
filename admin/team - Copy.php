<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$errors = [];
$old = ['kind' => 'staff', 'full_name' => '', 'email' => '', 'phone' => '', 'id_number' => '', 'position' => 'Receptionist', 'territory' => '', 'commission_percent' => '10', 'notify_pref' => 'email'];
$created = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        foreach ($old as $k => $v) $old[$k] = trim($_POST[$k] ?? '');
        $kind = $old['kind'] === 'agent' ? 'agent' : 'staff';
        $old['kind'] = $kind;
        if ($kind === 'agent' && (!is_numeric($old['commission_percent']) || $old['commission_percent'] < 0 || $old['commission_percent'] > 100)) $errors[] = 'Commission must be between 0 and 100.';
        if (!$errors) {
            [$uerr, $newId, $ok, $tmp, $err] = create_managed_user($kind, $old);
            $errors = $uerr;
            if (!$errors) {
                $pdo->prepare('INSERT INTO team_profiles (user_id, position, territory, commission_percent) VALUES (?,?,?,?)')
                    ->execute([$newId, $kind === 'staff' ? ($old['position'] ?: null) : null, $kind === 'agent' ? ($old['territory'] ?: null) : null, $kind === 'agent' ? $old['commission_percent'] : null]);
                log_activity(ucfirst($kind) . ' account created', $old['full_name']);
                $created = ['name' => $old['full_name'], 'email' => $old['email'], 'emailed' => $ok, 'tmp' => $tmp, 'err' => $err];
                $old = array_merge($old, ['full_name' => '', 'email' => '', 'phone' => '', 'id_number' => '', 'territory' => '']);
            }
        }
    } else {
        $id = (int)($_POST['user_id'] ?? 0);
        $q = "id=? AND role IN ('staff','agent')";
        if ($action === 'suspend')   { $pdo->prepare("UPDATE users SET status='suspended' WHERE $q")->execute([$id]); flash('success', 'Account suspended.'); log_activity('Team member suspended', '#' . $id); }
        if ($action === 'reinstate') { $pdo->prepare("UPDATE users SET status='approved' WHERE $q")->execute([$id]); flash('success', 'Account reinstated.'); log_activity('Team member reinstated', '#' . $id); }
        if ($action === 'delete')    { $pdo->prepare("DELETE FROM users WHERE $q")->execute([$id]); flash('success', 'Account deleted.'); log_activity('Team member deleted', '#' . $id); }
        if ($action === 'commission') {
            $pct = $_POST['commission_percent'] ?? '';
            if (is_numeric($pct) && $pct >= 0 && $pct <= 100) {
                $pdo->prepare('UPDATE team_profiles SET commission_percent=? WHERE user_id=?')->execute([$pct, $id]);
                flash('success', 'Commission rate updated (applies to future confirmed bookings).');
                log_activity('Commission rate changed', '#' . $id . ' → ' . $pct . '%');
            } else flash('danger', 'Commission must be between 0 and 100.');
        }
        redirect('admin/team.php');
    }
}

$team = $pdo->query("SELECT u.*, tp.position, tp.territory, tp.commission_percent FROM users u LEFT JOIN team_profiles tp ON tp.user_id=u.id
                     WHERE u.role IN ('staff','agent') ORDER BY u.role, u.created_at DESC")->fetchAll();

$page_title = 'Team';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Team — Staff &amp; Agents</h3>

  <?php if ($created): ?>
    <div class="alert alert-<?= $created['emailed'] ? 'success' : 'warning' ?>">
      <?php if ($created['emailed']): ?>Account created for <strong><?= sanitize($created['name']) ?></strong>. Login details were emailed to <?= sanitize($created['email']) ?>.
      <?php else: ?>Account created for <strong><?= sanitize($created['name']) ?></strong>, but the email could not be sent (<?= sanitize($created['err']) ?>). Give them this temporary password securely — shown once:
        <span class="fw-bold font-monospace fs-5"><?= sanitize($created['tmp']) ?></span><?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="sidebar-card mb-4">
    <h5><i class="bi bi-person-plus"></i> Add a team member</h5>
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Role</label>
          <select name="kind" id="kindSel" class="form-select">
            <option value="staff" <?= $old['kind']==='staff'?'selected':'' ?>>Office staff (receptionist, etc.)</option>
            <option value="agent" <?= $old['kind']==='agent'?'selected':'' ?>>Agent</option></select></div>
        <div class="col-md-5"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" value="<?= sanitize($old['full_name']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?= sanitize($old['email']) ?>" required></div>
        <div class="col-md-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= sanitize($old['phone']) ?>" required></div>
        <div class="col-md-3"><label class="form-label">National ID <span class="text-muted small">optional</span></label><input type="text" name="id_number" class="form-control" value="<?= sanitize($old['id_number']) ?>"></div>
        <div class="col-md-3 kind-staff"><label class="form-label">Job title</label><input type="text" name="position" class="form-control" value="<?= sanitize($old['position']) ?>"></div>
        <div class="col-md-3 kind-agent"><label class="form-label">Territory / area</label><input type="text" name="territory" class="form-control" placeholder="e.g. Juja & Thika" value="<?= sanitize($old['territory']) ?>"></div>
        <div class="col-md-3 kind-agent"><label class="form-label">Commission (% of first month's rent)</label><input type="number" step="0.01" min="0" max="100" name="commission_percent" class="form-control" value="<?= sanitize($old['commission_percent']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Notify by</label>
          <select name="notify_pref" class="form-select"><?php foreach (['email'=>'Email','sms'=>'SMS','both'=>'Email and SMS'] as $k=>$l): ?><option value="<?= $k ?>" <?= $old['notify_pref']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      </div>
      <button class="btn btn-brand mt-3 px-4">Create &amp; Email Login Details</button>
    </form>
  </div>

  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Name</th><th>Role</th><th>Contact</th><th>Details</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($team as $m): ?><tr>
      <td><?= sanitize($m['full_name']) ?></td>
      <td><span class="badge badge-<?= $m['role'] ?>"><?= $m['role']==='staff' ? 'Staff' : 'Agent' ?></span></td>
      <td class="small"><?= sanitize($m['phone']) ?><br><?= sanitize($m['email']) ?></td>
      <td class="small">
        <?php if ($m['role'] === 'staff'): ?><?= sanitize($m['position'] ?: '—') ?>
        <?php else: ?><?= sanitize($m['territory'] ?: '—') ?><br>
          <form method="POST" class="input-group input-group-sm mt-1" style="max-width:150px"><?= csrf_field() ?><input type="hidden" name="action" value="commission"><input type="hidden" name="user_id" value="<?= $m['id'] ?>">
            <input type="number" step="0.01" min="0" max="100" name="commission_percent" class="form-control" value="<?= sanitize($m['commission_percent']) ?>"><span class="input-group-text">%</span><button class="btn btn-outline-brand" title="Save rate"><i class="bi bi-check2"></i></button></form>
        <?php endif; ?></td>
      <td><span class="badge <?= badge_class($m['status']) ?>"><?= ucfirst($m['status']) ?></span></td>
      <td><div class="d-flex gap-1 flex-wrap">
        <?php if ($m['status'] === 'approved'): ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="suspend"><button class="btn btn-sm btn-outline-danger">Suspend</button></form>
        <?php else: ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="reinstate"><button class="btn btn-sm btn-brand">Reinstate</button></form>
        <?php endif; ?>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-secondary confirm-action" data-confirm="Delete this account?">Delete</button></form>
      </div></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$team): ?><p class="text-muted">No staff or agents yet.</p><?php endif; ?>
  </div></div>
</div>
<script>
(function(){var s=document.getElementById('kindSel');function f(){document.querySelectorAll('.kind-staff').forEach(function(e){e.style.display=s.value==='staff'?'':'none'});document.querySelectorAll('.kind-agent').forEach(function(e){e.style.display=s.value==='agent'?'':'none'});}s.addEventListener('change',f);f();})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
