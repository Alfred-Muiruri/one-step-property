<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/house_form.php';
require_role('landlord');
$lid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM houses WHERE id=? AND landlord_id=?');
$stmt->execute([$id, $lid]);
$house = $stmt->fetch();
if (!$house) { flash('danger', 'House not found.'); redirect('landlord/my_houses.php'); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['form'] ?? '') === 'delete_image') {
        $img = $pdo->prepare('SELECT image_path FROM house_images WHERE id=? AND house_id=?');
        $img->execute([(int)$_POST['image_id'], $id]);
        if ($row = $img->fetch()) {
            $pdo->prepare('DELETE FROM house_images WHERE id=?')->execute([(int)$_POST['image_id']]);
            if (!image_in_use($row['image_path'])) @unlink(__DIR__ . '/../' . $row['image_path']);   // shared by other units?
        }
        flash('success', 'Photo removed.');
        redirect('landlord/edit_house.php?id=' . $id);
    }

    $in = house_input_from_post();
    $errors = house_input_errors($in);
    if (!$errors) {
        // Any edit sends the listing back for approval so the public never sees unchecked changes.
        $pdo->prepare("UPDATE houses SET title=?, room_type=?, suitable_for=?, price=?, deposit=?, location=?, nearest_landmark=?, distance_notes=?,
                       description=?, amenities=?, latitude=?, longitude=?, approval_status='pending', rejection_reason=NULL WHERE id=?")
            ->execute([$in['title'], $in['room_type'], $in['suitable_for'], $in['price'], $in['deposit'] === '' ? null : $in['deposit'], $in['location'],
                       $in['nearest_landmark'] ?: null, $in['distance_notes'] ?: null, $in['description'] ?: null, $in['amenities'] ?: null,
                       $in['latitude'], $in['longitude'], $id]);
        save_house_photos($id);
        notify_admins('House updated, needs re-approval: ' . $in['title'], $_SESSION['full_name'] . ' edited "' . $in['title'] . '". Please review it.', 'Review houses', 'admin/houses.php');
        flash('success', 'Changes saved and sent for re-approval.');
        redirect('landlord/my_houses.php');
    }
    $house = array_merge($house, $in);
}

$images = $pdo->prepare('SELECT * FROM house_images WHERE house_id=?'); $images->execute([$id]); $images = $images->fetchAll();

$page_title = 'Edit House';
$needs_map = true;
$page_scripts = pin_picker_script();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Edit House</h3>
  <?php if ($house['approval_status'] === 'rejected' && $house['rejection_reason']): ?>
    <div class="alert alert-danger"><strong>Why it was not approved:</strong> <?= sanitize($house['rejection_reason']) ?></div>
  <?php endif; ?>
  <div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> After you save, the listing returns to “pending” until One Step approves the changes.</div>
  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <?php render_house_fields($house); ?>
      <div class="mt-3"><label class="form-label">Add More Photos</label>
        <input type="file" name="photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>
      <button class="btn btn-brand mt-4 px-4 py-2">Save &amp; Resubmit</button>
      <a href="<?= base_url('landlord/my_houses.php') ?>" class="btn btn-outline-brand mt-4 px-4 py-2">Back</a>
    </form>

    <?php if ($images): ?>
      <hr><h6>Current Photos</h6>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($images as $img): ?>
          <div><img src="<?= base_url($img['image_path']) ?>" style="width:120px;height:90px;object-fit:cover;border-radius:8px;" alt="">
            <form method="POST" class="mt-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="form" value="delete_image"><input type="hidden" name="image_id" value="<?= $img['id'] ?>">
              <button class="btn btn-sm btn-outline-danger w-100 confirm-action" data-confirm="Remove this photo?">Remove</button></form></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
