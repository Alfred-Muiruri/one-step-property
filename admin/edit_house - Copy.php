<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/house_form.php';
require_roles(['admin', 'staff']);
$role = current_role();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM houses WHERE id=?'); $stmt->execute([$id]); $house = $stmt->fetch();
if (!$house) { flash('danger', 'House not found.'); redirect('admin/houses.php'); }
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['form'] ?? '') === 'delete_image') {
        $img = $pdo->prepare('SELECT image_path FROM house_images WHERE id=? AND house_id=?'); $img->execute([(int)$_POST['image_id'], $id]);
        if ($row = $img->fetch()) {
            $pdo->prepare('DELETE FROM house_images WHERE id=?')->execute([(int)$_POST['image_id']]);
            @unlink(__DIR__ . '/../' . $row['image_path']);
            log_activity('Photo removed', $house['title']);
        }
        flash('success', 'Photo removed.');
        redirect('admin/edit_house.php?id=' . $id);
    }

    $in = house_input_from_post();
    $errors = house_input_errors($in);
    $landlordId = (int)($_POST['landlord_id'] ?? 0);
    $agentId = (int)($_POST['agent_id'] ?? 0);
    $chk = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='landlord'"); $chk->execute([$landlordId]);
    if (!$chk->fetch()) $errors[] = 'Select a landlord.';
    if (!$errors) {
        $approval = ($role === 'staff' && STAFF_HOUSES_NEED_APPROVAL) ? 'pending' : $house['approval_status'];
        $pdo->prepare("UPDATE houses SET landlord_id=?, agent_id=?, title=?, room_type=?, suitable_for=?, price=?, deposit=?, location=?, nearest_landmark=?, distance_notes=?,
                       description=?, amenities=?, latitude=?, longitude=?, approval_status=?, rejection_reason=NULL WHERE id=?")
            ->execute([$landlordId, $agentId ?: null, $in['title'], $in['room_type'], $in['suitable_for'], $in['price'], $in['deposit'] === '' ? null : $in['deposit'], $in['location'],
                       $in['nearest_landmark'] ?: null, $in['distance_notes'] ?: null, $in['description'] ?: null, $in['amenities'] ?: null,
                       $in['latitude'], $in['longitude'], $approval, $id]);
        save_house_photos($id);
        log_activity('House edited on behalf of landlord', $in['title'] . ' (#' . $id . ')');
        if ($approval === 'pending' && $house['approval_status'] !== 'pending') {
            notify_admins('House edited, needs re-approval: ' . $in['title'], $_SESSION['full_name'] . ' edited "' . $in['title'] . '".', 'Review houses', 'admin/houses.php');
        }
        flash('success', $approval === 'pending' ? 'Saved and sent for admin approval.' : 'House updated.');
        redirect('admin/houses.php');
    }
    $house = array_merge($house, $in, ['landlord_id' => $landlordId, 'agent_id' => $agentId]);
}

$images = $pdo->prepare('SELECT * FROM house_images WHERE house_id=?'); $images->execute([$id]); $images = $images->fetchAll();

$page_title = 'Edit House';
$needs_map = true;
$page_scripts = pin_picker_script();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Edit House (on behalf of landlord)</h3>
  <?php if ($role === 'staff' && STAFF_HOUSES_NEED_APPROVAL): ?><div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> After you save, the listing returns to “pending” until the admin approves it.</div><?php endif; ?>
  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <?php render_owner_selects($house['landlord_id'], $house['agent_id'], true); ?>
      <?php render_house_fields($house); ?>
      <div class="mt-3"><label class="form-label">Add More Photos</label><input type="file" name="photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>
      <button class="btn btn-brand mt-4 px-4 py-2">Save Changes</button>
      <a href="<?= base_url('admin/houses.php') ?>" class="btn btn-outline-brand mt-4 px-4 py-2">Back</a>
    </form>
    <?php if ($images): ?><hr><h6>Current Photos</h6><div class="d-flex flex-wrap gap-2">
      <?php foreach ($images as $img): ?><div><img src="<?= base_url($img['image_path']) ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;" alt="">
        <form method="POST" class="mt-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="form" value="delete_image"><input type="hidden" name="image_id" value="<?= $img['id'] ?>">
          <button class="btn btn-sm btn-outline-danger w-100 confirm-action" data-confirm="Remove this photo?">Remove</button></form></div><?php endforeach; ?></div><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
