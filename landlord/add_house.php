<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/house_form.php';
require_role('landlord');
$lid = $_SESSION['user_id'];

$errors = [];
$old = house_empty();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = house_input_from_post();
    $errors = house_input_errors($old);
    if (!$errors) {
        $ids = create_house_units($old, $lid);
        $n = count($ids);
        $label = $n > 1 ? $n . ' units of "' . $old['title'] . '"' : '"' . $old['title'] . '"';
        notify_admins('House awaiting approval: ' . $old['title'], $_SESSION['full_name'] . ' submitted ' . $label . ' (' . $old['location'] . ') for approval.', 'Review houses', 'admin/houses.php');
        flash('success', ($n > 1 ? $n . ' units submitted!' : 'House submitted!') . ' ' . ($n > 1 ? 'They' : 'It') . ' will go live as soon as One Step approves.');
        redirect('landlord/my_houses.php');
    }
}

$page_title = 'Add a House';
$needs_map = true;
$page_scripts = pin_picker_script();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">List a New House</h3>
  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" enctype="multipart/form-data"><?= csrf_field() ?>
      <?php render_house_fields($old, true); ?>
      <div class="mt-3"><label class="form-label">Photos <span class="text-muted small">(JPG/PNG/WEBP up to 5MB each — for several units one photo of the whole building is enough)</span></label>
        <input type="file" name="photos[]" class="form-control" multiple accept="image/jpeg,image/png,image/webp"></div>
      <button class="btn btn-brand mt-4 px-4 py-2">Submit for Approval</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
