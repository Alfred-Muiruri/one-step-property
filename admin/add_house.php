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
        $ids = create_house_units($old, $landlordId, $me['id'], $agentId, $approved ? 'approved' : 'pending');
        $n = count($ids); $hid = $ids[0];
        $label = $n > 1 ? $n . ' units of ' . $old['title'] : $old['title'] . ' (#' . $hid . ')';
        log_activity('House added for landlord', $label);
        if (!$approved) {
            notify_admins('House awaiting approval: ' . $old['title'], $me['full_name'] . ' (' . role_label($role) . ') added "' . $old['title'] . '" for a landlord.', 'Review houses', 'admin/houses.php');
            flash('success', ($n > 1 ? $n . ' units submitted. They go' : 'House submitted. It goes') . ' live once the admin approves.');
        } else {
            flash('success', $n > 1 ? $n . ' units added and published.' : 'House added and published.');
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
      <?php render_house_fields($old, true); ?>
      <div class="mt-3"><label class="form-label">Photos <span class="text-muted small">(for several units one photo of the whole building is enough)</span></label><input type="file" name="photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>
      <button class="btn btn-brand mt-4 px-4 py-2">Add House</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
