<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('landlord');
$lid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $map = ['confirm' => 'confirmed', 'reject' => 'rejected'];
    $new = $map[$_POST['action'] ?? ''] ?? null;
    if ($new && set_booking_status((int)($_POST['booking_id'] ?? 0), $new, $lid)) {
        flash('success', $new === 'confirmed' ? 'Booking confirmed — the tenant has been notified and the house marked as booked.' : 'Booking rejected — the tenant has been notified.');
    } else {
        flash('warning', 'That booking could not be updated (it may have already been handled).');
    }
    redirect('landlord/bookings.php');
}

$bookings = $pdo->prepare("SELECT b.*, h.title, h.location, h.price, u.full_name AS tenant_name, u.phone AS tenant_phone, u.email AS tenant_email, u.tenant_type, u.organization
    FROM bookings b JOIN houses h ON h.id=b.house_id JOIN users u ON u.id=b.tenant_id
    WHERE h.landlord_id=? ORDER BY b.status='pending' DESC, b.created_at DESC");
$bookings->execute([$lid]); $bookings = $bookings->fetchAll();

$page_title = 'Booking Requests';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Booking Requests</h3>
  <p class="text-muted small">One Step may also handle bookings on your behalf. Tenants are notified automatically on every decision.</p>
  <?php foreach ($bookings as $b): ?>
    <div class="sidebar-card mb-3"><div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h6 class="mb-1"><?= sanitize($b['title']) ?> <span class="badge <?= badge_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></h6>
        <p class="mb-1 small text-muted"><?= sanitize($b['location']) ?> · KES <?= number_format($b['price']) ?></p>
        <p class="mb-1 small"><strong>Tenant:</strong> <?= sanitize($b['tenant_name']) ?> <span class="badge <?= $b['tenant_type']==='student'?'badge-student':'badge-non_student' ?>"><?= $b['tenant_type']==='student'?'Student':'Non-student' ?></span>
          · <?= sanitize($b['tenant_phone']) ?> · <?= sanitize($b['tenant_email']) ?><?= $b['organization'] ? ' · ' . sanitize($b['organization']) : '' ?></p>
        <p class="mb-1 small"><strong>Move-in:</strong> <?= $b['move_in_date'] ? date('d M Y', strtotime($b['move_in_date'])) : '—' ?></p>
        <?php if ($b['message']): ?><p class="mb-0 small text-muted"><em>"<?= sanitize($b['message']) ?>"</em></p><?php endif; ?>
      </div>
      <?php if ($b['status'] === 'pending'): ?>
        <div class="d-flex gap-2">
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="confirm"><button class="btn btn-sm btn-brand">Confirm</button></form>
          <form method="POST"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-outline-danger confirm-action" data-confirm="Reject this booking?">Reject</button></form>
        </div>
      <?php endif; ?>
    </div></div>
  <?php endforeach; ?>
  <?php if (!$bookings): ?><p class="text-muted">No booking requests yet.</p><?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
