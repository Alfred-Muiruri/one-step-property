<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('tenant');
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare('DELETE FROM wishlist WHERE tenant_id=? AND house_id=?')->execute([$tid, (int)($_POST['house_id'] ?? 0)]);
    flash('success', 'Removed from wishlist.');
    redirect('tenant/wishlist.php');
}

$items = $pdo->prepare("SELECT h.*, (SELECT image_path FROM house_images WHERE house_id=h.id LIMIT 1) AS thumb
    FROM wishlist w JOIN houses h ON h.id=w.house_id WHERE w.tenant_id=? AND h.approval_status='approved' ORDER BY w.created_at DESC");
$items->execute([$tid]); $items = $items->fetchAll();

$page_title = 'Wishlist';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">My Wishlist</h3>
  <div class="row g-4">
    <?php foreach ($items as $h): ?>
      <div class="col-md-6 col-lg-4"><div class="card house-card">
        <img src="<?= $h['thumb'] ? base_url($h['thumb']) : base_url('assets/img/placeholder.svg') ?>" class="card-img-top" alt="">
        <div class="card-body">
          <span class="badge <?= badge_class($h['status']) ?> mb-2"><?= ucfirst($h['status']) ?></span>
          <h5 class="card-title mb-1"><?= sanitize($h['title']) ?></h5>
          <p class="text-muted small mb-1"><?= sanitize($h['location']) ?></p>
          <p class="price-tag mb-2">KES <?= number_format($h['price']) ?></p>
          <div class="d-flex gap-2">
            <a href="<?= base_url('house_details.php?id=' . $h['id']) ?>" class="btn btn-sm btn-brand flex-fill">View</a>
            <form method="POST" class="flex-fill"><?= csrf_field() ?><input type="hidden" name="house_id" value="<?= $h['id'] ?>"><button class="btn btn-sm btn-outline-danger w-100">Remove</button></form>
          </div>
        </div>
      </div></div>
    <?php endforeach; ?>
    <?php if (!$items): ?><p class="text-muted">Your wishlist is empty. <a href="<?= base_url('houses.php') ?>">Find houses you like.</a></p><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
