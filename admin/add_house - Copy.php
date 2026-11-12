<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/house_form.php';
require_roles(['admin', 'staff', 'agent']);
$role = current_role();
$me = current_user();

$errors = [];
$old = house_empty();
$landlordId = (int)($_POST['landlord_id'] ?? 0);
$agentId = (int)($_POST['agent_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = house_input_from_post();
    $errors = house_input_errors($old);
    $chk = $pdo->prepare("SELECT id FROM users WHERE id=? AND role='landlord' AND status='approved'"); $chk->execute([$landlordId]);
    if (!$chk->fetch()) $errors[] = 'Select a landlord.';
    if ($role === 'agent') $agentId = (int)$me['id'];
    if (!$errors) {
        $approved = $role === 'admin' || ($role === 'staff' && !STAFF_HOUSES_NEED_APPROVAL);
        $pdo->prepare("INSERT INTO houses (landlord_id,added_by,agent_id,title,room_type,suitable_for,price,deposit,location,nearest_landmark,distance_notes,description,amenities,latitude,longitude,approval_status)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$landlordId, $me['id'], $agentId ?: null, $old['title'], $old['room_type'], $old['suitable_for'], $old['price'], $old['deposit'] === '' ? null : $old['deposit'],
                       $old['location'], $old['nearest_landmark'] ?: null, $old['distance_notes'] ?: null, $old['description'] ?: null, $old['amenities'] ?: null,
                       $old['latitude'], $old['longitude'], $approved ? 'approved' : 'pending']);
        $hid = (int)$pdo->lastInsertId();
        save_house_photos($hid);
        log_activity('House added for landlord', $old['title'] . ' (#' . $hid . ')');
        if (!$approved) {
            notify_admins('House awaiting approval: ' . $old['title'], $me['full_name'] . ' (' . role_label($role) . ') added "' . $old['title'] . '" for a landlord.', 'Review houses', 'admin/houses.php');
            flash('success', 'House submitted. It goes live once the admin approves it.');
        } else {
            flash('success', 'House added and published.');
        }
        redirect($role === 'agent' ? 'agent/my_houses.php' : 'admin/houses.php');
    }
}

$page_title = 'Add House';
$needs_map = true;
$page_scripts = pin_picker_script();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Add a House for a Landlord</h3>
  <?php if ($role !== 'admin' && ($role === 'agent' || STAFF_HOUSES_NEED_APPROVAL)): ?>
    <div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> Houses you add are reviewed by the admin before going live.</div>
  <?php endif; ?>
  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" enctype="multipart/form-data"><?= csrf_field() ?>
      <?php render_owner_selects($landlordId, $agentId, $role !== 'agent'); ?>
      <?php render_house_fields($old); ?>
      <div class="mt-3"><label class="form-label">Photos</label><input type="file" name="photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>
      <button class="btn btn-brand mt-4 px-4 py-2">Add House</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
