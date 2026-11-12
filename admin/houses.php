<?php
require_once __DIR__ . '/../includes/functions.php';
require_roles(['admin', 'staff']);
$admin = is_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['house_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $s = $pdo->prepare('SELECT * FROM houses WHERE id=?'); $s->execute([$id]); $h = $s->fetch();
    if ($h) {
        $lq = $pdo->prepare('SELECT * FROM users WHERE id=?'); $lq->execute([$h['landlord_id']]); $landlord = $lq->fetch();
        if ($action === 'toggle_status' && $h['approval_status'] === 'approved') {
            $pdo->prepare("UPDATE houses SET status = IF(status='available','booked','available') WHERE id=?")->execute([$id]);
            log_activity('Availability changed', $h['title'] . ' → ' . ($h['status'] === 'available' ? 'booked' : 'available'));
            flash('success', 'Availability updated.');
        } elseif ($admin && $action === 'approve') {
            $pdo->prepare("UPDATE houses SET approval_status='approved', rejection_reason=NULL WHERE id=?")->execute([$id]);
            if ($landlord) notify_user($landlord, 'House approved: ' . $h['title'], 'Your house "' . $h['title'] . '" is now live on ' . SITE_NAME . '.', 'View listing', 'house_details.php?id=' . $id);
            log_activity('House approved', $h['title']);
            flash('success', 'House approved and landlord notified.');
        } elseif ($admin && $action === 'reject') {
            $reason = mb_substr(trim($_POST['reason'] ?? ''), 0, 250);
            $pdo->prepare("UPDATE houses SET approval_status='rejected', rejection_reason=? WHERE id=?")->execute([$reason ?: 'Please contact One Step for details.', $id]);
            if ($landlord) notify_user($landlord, 'House not approved: ' . $h['title'], 'Your house "' . $h['title'] . '" was not approved. Reason: ' . ($reason ?: 'please contact us for details') . '. You can edit and resubmit it.', 'Edit house', 'landlord/edit_house.php?id=' . $id);
            log_activity('House rejected', $h['title']);
            flash('success', 'House rejected and landlord notified.');
        } elseif ($admin && $action === 'delete') {
            $pdo->prepare('DELETE FROM houses WHERE id=?')->execute([$id]);
            log_activity('House deleted', $h['title']);
            flash('success', 'House deleted.');
        }
    }
    redirect('admin/houses.php');
}

$filter = $_GET['f'] ?? '';
$where = in_array($filter, ['pending','approved','rejected'], true) ? "WHERE h.approval_status='$filter'" : '';
$houses = $pdo->query("SELECT h.*, u.full_name AS landlord_name, ag.full_name AS agent_name FROM houses h JOIN users u ON u.id=h.landlord_id
                       LEFT JOIN users ag ON ag.id=h.agent_id $where ORDER BY h.approval_status='pending' DESC, h.created_at DESC")->fetchAll();

$page_title = 'Manage Houses';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="section-title mb-0">Manage Houses</h3>
    <a href="<?= base_url('admin/add_house.php') ?>" class="btn btn-brand"><i class="bi bi-plus-circle"></i> Add House</a>
  </div>
  <div class="mb-3 d-flex gap-2 flex-wrap">
    <?php foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?>
      <a class="btn btn-sm <?= $filter===$k?'btn-brand':'btn-outline-brand' ?>" href="?f=<?= $k ?>"><?= $l ?></a><?php endforeach; ?>
  </div>
  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>House</th><th>Landlord / Agent</th><th>Rent</th><th>Pin</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($houses as $h): ?><tr>
      <td><a href="<?= base_url('house_details.php?id=' . $h['id']) ?>"><?= sanitize($h['title']) ?></a><br><span class="small text-muted"><?= room_type_label($h['room_type']) ?> · <?= sanitize($h['location']) ?></span></td>
      <td><?= sanitize($h['landlord_name']) ?><?= $h['agent_name'] ? '<br><span class="badge badge-agent">Agent: ' . sanitize($h['agent_name']) . '</span>' : '' ?></td>
      <td>KES <?= number_format($h['price']) ?></td>
      <td><?= $h['latitude'] !== null ? '<a target="_blank" rel="noopener" href="https://www.google.com/maps?q=' . (float)$h['latitude'] . ',' . (float)$h['longitude'] . '"><i class="bi bi-geo-alt-fill text-danger"></i> View</a>' : '<span class="text-muted">—</span>' ?></td>
      <td><span class="badge <?= badge_class($h['approval_status']) ?>"><?= ucfirst($h['approval_status']) ?></span>
          <?php if ($h['approval_status'] === 'approved'): ?><br><span class="badge <?= badge_class($h['status']) ?> mt-1"><?= ucfirst($h['status']) ?></span><?php endif; ?></td>
      <td style="min-width:260px">
        <div class="d-flex gap-1 flex-wrap mb-1">
          <a href="<?= base_url('admin/edit_house.php?id=' . $h['id']) ?>" class="btn btn-sm btn-outline-brand">Edit</a>
          <?php if ($h['approval_status'] === 'approved'): ?>
            <form method="POST"><?= csrf_field() ?><input type="hidden" name="house_id" value="<?= $h['id'] ?>"><input type="hidden" name="action" value="toggle_status">
              <button class="btn btn-sm btn-outline-secondary">Mark <?= $h['status']==='available'?'Booked':'Available' ?></button></form>
          <?php endif; ?>
          <?php if ($admin && $h['approval_status'] !== 'approved'): ?>
            <form method="POST"><?= csrf_field() ?><input type="hidden" name="house_id" value="<?= $h['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-brand">Approve</button></form>
          <?php endif; ?>
          <?php if ($admin): ?>
            <form method="POST"><?= csrf_field() ?><input type="hidden" name="house_id" value="<?= $h['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-secondary confirm-action" data-confirm="Delete this listing?">Delete</button></form>
          <?php endif; ?>
        </div>
        <?php if ($admin && $h['approval_status'] !== 'rejected'): ?>
          <form method="POST" class="input-group input-group-sm"><?= csrf_field() ?><input type="hidden" name="house_id" value="<?= $h['id'] ?>"><input type="hidden" name="action" value="reject">
            <input type="text" name="reason" class="form-control" placeholder="Reason for rejecting"><button class="btn btn-outline-danger">Reject</button></form>
        <?php endif; ?>
      </td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$houses): ?><p class="text-muted">No houses here.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
