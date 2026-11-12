<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('staff');
$q = function ($sql) use ($pdo) { return (int)$pdo->query($sql)->fetch()['c']; };
$stats = [
    'pending'   => $q("SELECT COUNT(*) c FROM bookings WHERE status='pending'"),
    'today'     => $q("SELECT COUNT(*) c FROM bookings WHERE DATE(created_at)=CURDATE()"),
    'available' => $q("SELECT COUNT(*) c FROM houses WHERE approval_status='approved' AND status='available'"),
    'awaiting'  => $q("SELECT COUNT(*) c FROM houses WHERE approval_status='pending'"),
];
$recent = $pdo->query("SELECT b.*, h.title, u.full_name AS tenant_name FROM bookings b JOIN houses h ON h.id=b.house_id JOIN users u ON u.id=b.tenant_id
                       WHERE b.status='pending' ORDER BY b.created_at DESC LIMIT 6")->fetchAll();
$page_title = 'Staff Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="mb-1">Good day, <?= sanitize($_SESSION['full_name']) ?> 👋</h3>
  <p class="text-muted mb-4">You work on behalf of our landlords — houses, bookings and walk-in clients. Everything you do is logged.</p>
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= $stats['pending'] ?></div>Pending Bookings</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['today'] ?></div>Booked Today</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['available'] ?></div>Available Houses</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= $stats['awaiting'] ?></div>Awaiting Admin Approval</div></div>
  </div>
  <div class="row g-4">
    <div class="col-lg-8"><div class="sidebar-card"><h5>Pending bookings</h5>
      <?php if (!$recent): ?><p class="text-muted small mb-0">Nothing waiting. 🎉</p><?php else: ?>
      <div class="table-responsive"><table class="table align-middle"><thead><tr><th>House</th><th>Tenant</th><th>Move-in</th></tr></thead><tbody>
        <?php foreach ($recent as $b): ?><tr><td><?= sanitize($b['title']) ?></td><td><?= sanitize($b['tenant_name']) ?></td><td><?= $b['move_in_date'] ? date('d M Y', strtotime($b['move_in_date'])) : '—' ?></td></tr><?php endforeach; ?>
      </tbody></table></div><a href="<?= base_url('admin/bookings.php') ?>" class="btn btn-brand btn-sm">Handle bookings</a><?php endif; ?>
    </div></div>
    <div class="col-lg-4"><div class="sidebar-card"><h5>Quick actions</h5><div class="d-grid gap-2">
      <a class="btn btn-brand" href="<?= base_url('staff/walkin.php') ?>"><i class="bi bi-person-plus"></i> Register &amp; Book a Client</a>
      <a class="btn btn-outline-brand" href="<?= base_url('admin/add_house.php') ?>"><i class="bi bi-plus-circle"></i> Add House for Landlord</a>
      <a class="btn btn-outline-brand" href="<?= base_url('admin/houses.php') ?>"><i class="bi bi-houses"></i> Manage Houses</a>
      <a class="btn btn-outline-brand" href="<?= base_url('admin/tenants.php') ?>"><i class="bi bi-people"></i> Tenants</a>
    </div></div></div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
