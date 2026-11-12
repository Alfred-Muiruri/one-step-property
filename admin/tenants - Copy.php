<?php
require_once __DIR__ . '/../includes/functions.php';
require_roles(['admin', 'staff']);
$admin = is_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    $id = (int)($_POST['user_id'] ?? 0);
    $a = $_POST['action'] ?? '';
    if ($a === 'suspend')   { $pdo->prepare("UPDATE users SET status='suspended' WHERE id=? AND role='tenant'")->execute([$id]); flash('success', 'Tenant suspended.'); }
    if ($a === 'reinstate') { $pdo->prepare("UPDATE users SET status='approved' WHERE id=? AND role='tenant'")->execute([$id]); flash('success', 'Tenant reinstated.'); }
    if ($a === 'delete')    { $pdo->prepare("DELETE FROM users WHERE id=? AND role='tenant'")->execute([$id]); flash('success', 'Tenant account deleted.'); }
    redirect('admin/tenants.php');
}

$type = $_GET['type'] ?? '';
$where = in_array($type, ['student','non_student'], true) ? "AND u.tenant_type='$type'" : '';
$tenants = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM bookings WHERE tenant_id=u.id) AS booking_count FROM users u WHERE u.role='tenant' $where ORDER BY u.created_at DESC")->fetchAll();

$page_title = 'Tenants';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="section-title mb-0">Tenants</h3>
    <a href="<?= base_url('staff/walkin.php') ?>" class="btn btn-brand"><i class="bi bi-person-plus"></i> Register &amp; Book a Client</a>
  </div>
  <div class="mb-3 d-flex gap-2">
    <?php foreach (['' => 'All', 'student' => 'Students', 'non_student' => 'Non-students'] as $k => $l): ?><a class="btn btn-sm <?= $type===$k?'btn-brand':'btn-outline-brand' ?>" href="?type=<?= $k ?>"><?= $l ?></a><?php endforeach; ?>
  </div>
  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Name</th><th>Type</th><th>Contact</th><th>Notify</th><th>Bookings</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($tenants as $t): ?><tr>
      <td><?= sanitize($t['full_name']) ?><br><span class="small text-muted"><?= sanitize($t['organization']) ?></span></td>
      <td><span class="badge <?= $t['tenant_type']==='student'?'badge-student':'badge-non_student' ?>"><?= $t['tenant_type']==='student'?'Student':'Non-student' ?></span></td>
      <td class="small"><?= sanitize($t['phone']) ?><br><?= sanitize($t['email']) ?></td>
      <td class="small"><?= ucfirst($t['notify_pref']) ?></td>
      <td><?= (int)$t['booking_count'] ?></td>
      <td><span class="badge <?= badge_class($t['status']) ?>"><?= ucfirst($t['status']) ?></span></td>
      <td><?php if (!$admin): ?><span class="text-muted small">View only</span><?php else: ?><div class="d-flex gap-1 flex-wrap">
        <?php if ($t['status'] === 'approved'): ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $t['id'] ?>"><input type="hidden" name="action" value="suspend"><button class="btn btn-sm btn-outline-danger">Suspend</button></form>
        <?php else: ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $t['id'] ?>"><input type="hidden" name="action" value="reinstate"><button class="btn btn-sm btn-brand">Reinstate</button></form>
        <?php endif; ?>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $t['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-secondary confirm-action" data-confirm="Delete this tenant account?">Delete</button></form>
      </div><?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$tenants): ?><p class="text-muted">No tenants yet.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
