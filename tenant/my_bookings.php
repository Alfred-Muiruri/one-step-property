<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('tenant');
$tid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $up = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND tenant_id=? AND status='pending'");
    $up->execute([(int)$_POST['booking_id'], $tid]);
    if ($up->rowCount()) flash('success', 'Booking cancelled.');
    redirect('tenant/my_bookings.php');
}

$bookings = $pdo->prepare('SELECT b.*, h.title, h.price, h.location, h.room_type FROM bookings b JOIN houses h ON h.id=b.house_id WHERE b.tenant_id=? ORDER BY b.created_at DESC');
$bookings->execute([$tid]); $bookings = $bookings->fetchAll();

$page_title = 'My Bookings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">My Bookings</h3>
  <?php if (!$bookings): ?><p class="text-muted">No bookings yet. <a href="<?= base_url('houses.php') ?>">Browse houses</a> to send your first request.</p><?php endif; ?>
  <?php foreach ($bookings as $b): ?>
    <div class="sidebar-card mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <h6 class="mb-1"><a href="<?= base_url('house_details.php?id=' . $b['house_id']) ?>"><?= sanitize($b['title']) ?></a></h6>
        <p class="mb-1 small text-muted"><i class="bi bi-geo-alt"></i> <?= sanitize($b['location']) ?> · <?= room_type_label($b['room_type']) ?> · KES <?= number_format($b['price']) ?></p>
        <p class="mb-0 small text-muted">Move-in: <?= $b['move_in_date'] ? date('d M Y', strtotime($b['move_in_date'])) : '—' ?> · Requested <?= date('d M Y', strtotime($b['created_at'])) ?></p>
        <?php if ($b['status'] === 'confirmed' && PAY_INSTRUCTIONS !== ''): ?><p class="mb-0 small mt-2 pay-info"><i class="bi bi-cash-coin"></i> <?= sanitize(PAY_INSTRUCTIONS) ?></p><?php endif; ?>
      </div>
      <div class="text-end">
        <span class="badge <?= badge_class($b['status']) ?> mb-2 d-inline-block"><?= ucfirst($b['status']) ?></span><br>
        <?php if ($b['status'] === 'pending'): ?>
          <form method="POST" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
            <button class="btn btn-sm btn-outline-danger confirm-action" data-confirm="Cancel this booking request?">Cancel</button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
