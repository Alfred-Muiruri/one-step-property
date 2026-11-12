<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('agent');
$aid = (int)$_SESSION['user_id'];
$s = $pdo->prepare("SELECT h.*, u.full_name AS landlord_name, (SELECT image_path FROM house_images WHERE house_id=h.id LIMIT 1) AS thumb
    FROM houses h JOIN users u ON u.id=h.landlord_id WHERE h.agent_id=? OR h.added_by=? ORDER BY h.created_at DESC");
$s->execute([$aid, $aid]); $houses = $s->fetchAll();
$page_title = 'My Houses';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="section-title mb-0">My Houses</h3>
    <a href="<?= base_url('admin/add_house.php') ?>" class="btn btn-brand"><i class="bi bi-plus-circle"></i> Add House</a>
  </div>
  <p class="text-muted small">Houses you sourced or that the office assigned to you. Bookings on these earn you commission. To change a listing, ask office staff.</p>
  <div class="row g-4">
    <?php foreach ($houses as $h): ?>
      <div class="col-md-6 col-lg-4"><div class="card house-card">
        <img src="<?= $h['thumb'] ? base_url($h['thumb']) : base_url('assets/img/placeholder.svg') ?>" class="card-img-top" alt="">
        <div class="card-body">
          <div class="d-flex gap-2 mb-2"><span class="badge <?= badge_class($h['approval_status']) ?>"><?= ucfirst($h['approval_status']) ?></span>
            <?php if ($h['approval_status']==='approved'): ?><span class="badge <?= badge_class($h['status']) ?>"><?= ucfirst($h['status']) ?></span><?php endif; ?></div>
          <h5 class="card-title mb-1"><?= sanitize($h['title']) ?></h5>
          <p class="text-muted small mb-1"><?= sanitize($h['location']) ?> · Landlord: <?= sanitize($h['landlord_name']) ?></p>
          <p class="price-tag mb-2">KES <?= number_format($h['price']) ?></p>
          <div class="d-grid gap-2">
            <a href="<?= base_url('house_details.php?id=' . $h['id']) ?>" class="btn btn-sm btn-outline-brand">Preview</a>
            <?php if ($h['approval_status']==='approved' && $h['status']==='available'): ?><a href="<?= base_url('agent/book_for_client.php?house=' . $h['id']) ?>" class="btn btn-sm btn-brand">Book for a client</a><?php endif; ?>
          </div>
        </div>
      </div></div>
    <?php endforeach; ?>
    <?php if (!$houses): ?><p class="text-muted">No houses yet. Add one you have sourced, or ask the office to assign you some.</p><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
