<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('tenant');
$tid = $_SESSION['user_id'];

$c = function ($sql) use ($pdo, $tid) { $s = $pdo->prepare($sql); $s->execute([$tid]); return $s->fetch()['c']; };
$stats = [
    'total'     => $c('SELECT COUNT(*) c FROM bookings WHERE tenant_id=?'),
    'pending'   => $c("SELECT COUNT(*) c FROM bookings WHERE tenant_id=? AND status='pending'"),
    'confirmed' => $c("SELECT COUNT(*) c FROM bookings WHERE tenant_id=? AND status='confirmed'"),
    'wishlist'  => $c('SELECT COUNT(*) c FROM wishlist WHERE tenant_id=?'),
];
$recent = $pdo->prepare('SELECT b.*, h.title, h.price, h.location FROM bookings b JOIN houses h ON h.id=b.house_id WHERE b.tenant_id=? ORDER BY b.created_at DESC LIMIT 5');
$recent->execute([$tid]); $recent = $recent->fetchAll();

$page_title = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="mb-1">Hi, <?= sanitize($_SESSION['full_name']) ?> 👋</h3>
  <p class="text-muted mb-4">Here's what's happening with your house search.</p>
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['total'] ?></div>Total Bookings</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= $stats['pending'] ?></div>Pending</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['confirmed'] ?></div>Confirmed</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= $stats['wishlist'] ?></div>Wishlisted</div></div>
  </div>
  <div class="row g-4">
    <div class="col-lg-8"><div class="sidebar-card">
      <h5>Recent Bookings</h5>
      <?php if (!$recent): ?><p class="text-muted small">No bookings yet. <a href="<?= base_url('houses.php') ?>">Browse houses</a> to get started.</p>
      <?php else: ?>
        <div class="table-responsive"><table class="table align-middle">
          <thead><tr><th>House</th><th>Location</th><th>Rent</th><th>Status</th></tr></thead><tbody>
          <?php foreach ($recent as $b): ?><tr>
            <td><a href="<?= base_url('house_details.php?id=' . $b['house_id']) ?>"><?= sanitize($b['title']) ?></a></td>
            <td><?= sanitize($b['location']) ?></td><td>KES <?= number_format($b['price']) ?></td>
            <td><span class="badge <?= badge_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td></tr>
          <?php endforeach; ?></tbody></table></div>
      <?php endif; ?>
    </div></div>
    <div class="col-lg-4"><div class="sidebar-card"><h5>Quick Links</h5><div class="d-grid gap-2">
      <a class="btn btn-outline-brand" href="<?= base_url('houses.php') ?>"><i class="bi bi-search"></i> Browse Houses</a>
      <a class="btn btn-outline-brand" href="<?= base_url('tenant/my_bookings.php') ?>"><i class="bi bi-calendar-check"></i> My Bookings</a>
      <a class="btn btn-outline-brand" href="<?= base_url('tenant/wishlist.php') ?>"><i class="bi bi-heart"></i> Wishlist</a>
      <a class="btn btn-outline-brand" href="<?= base_url('tenant/profile.php') ?>"><i class="bi bi-person"></i> Profile &amp; Notifications</a>
    </div></div></div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
