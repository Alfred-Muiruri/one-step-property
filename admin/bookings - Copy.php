<?php
require_once __DIR__ . '/../includes/functions.php';
require_roles(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bid = (int)($_POST['booking_id'] ?? 0);
    $a = $_POST['action'] ?? '';
    if ($a === 'delete' && is_admin()) {
        $pdo->prepare('DELETE FROM bookings WHERE id=?')->execute([$bid]);
        log_activity('Booking deleted', '#' . $bid);
        flash('success', 'Booking record removed.');
    } elseif (in_array($a, ['confirm', 'reject'], true)) {
        if (set_booking_status($bid, $a === 'confirm' ? 'confirmed' : 'rejected')) flash('success', 'Booking ' . ($a === 'confirm' ? 'confirmed' : 'rejected') . ' and the tenant notified.');
        else flash('warning', 'That booking could not be updated.');
    }
    redirect('admin/bookings.php');
}

$bookings = $pdo->query(
    "SELECT b.*, h.title, h.location, u.full_name AS tenant_name, u.phone AS tenant_phone, u.tenant_type, ul.full_name AS landlord_name, ag.full_name AS agent_name
     FROM bookings b JOIN houses h ON h.id=b.house_id JOIN users u ON u.id=b.tenant_id JOIN users ul ON ul.id=h.landlord_id LEFT JOIN users ag ON ag.id=b.agent_id
     ORDER BY b.status='pending' DESC, b.created_at DESC"
)->fetchAll();

$page_title = 'All Bookings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="section-title mb-0">All Bookings</h3>
    <a href="<?= base_url('staff/walkin.php') ?>" class="btn btn-brand"><i class="bi bi-person-plus"></i> Register &amp; Book a Client</a>
  </div>
  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>House</th><th>Tenant</th><th>Landlord</th><th>Move-in</th><th>Source</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($bookings as $b): ?><tr>
      <td><a href="<?= base_url('house_details.php?id=' . $b['house_id']) ?>"><?= sanitize($b['title']) ?></a><br><span class="small text-muted"><?= sanitize($b['location']) ?></span></td>
      <td><?= sanitize($b['tenant_name']) ?> <span class="badge <?= $b['tenant_type']==='student'?'badge-student':'badge-non_student' ?>"><?= $b['tenant_type']==='student'?'Student':'Non-student' ?></span><br><span class="small text-muted"><?= sanitize($b['tenant_phone']) ?></span></td>
      <td><?= sanitize($b['landlord_name']) ?></td>
      <td class="small"><?= $b['move_in_date'] ? date('d M Y', strtotime($b['move_in_date'])) : '—' ?></td>
      <td><span class="badge badge-source-<?= $b['source'] ?>"><?= ucfirst($b['source']) ?></span><?= $b['agent_name'] ? '<br><span class="small text-muted">Agent: ' . sanitize($b['agent_name']) . '</span>' : '' ?></td>
      <td><span class="badge <?= badge_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
      <td><div class="d-flex gap-1 flex-wrap">
        <?php if ($b['status'] === 'pending'): ?>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="confirm"><button class="btn btn-sm btn-brand">Confirm</button></form>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-outline-danger confirm-action" data-confirm="Reject this booking?">Reject</button></form>
        <?php endif; ?>
        <?php if (is_admin()): ?>
        <form method="POST"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-secondary confirm-action" data-confirm="Remove this booking record?">Delete</button></form>
        <?php endif; ?>
      </div></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$bookings): ?><p class="text-muted">No bookings yet.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
