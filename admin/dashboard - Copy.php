<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$q = function ($sql) use ($pdo) { return (int)$pdo->query($sql)->fetch()['c']; };
$stats = [
    'landlords'      => $q("SELECT COUNT(*) c FROM users WHERE role='landlord'"),
    'tenants'        => $q("SELECT COUNT(*) c FROM users WHERE role='tenant'"),
    'students'       => $q("SELECT COUNT(*) c FROM users WHERE role='tenant' AND tenant_type='student'"),
    'houses'         => $q('SELECT COUNT(*) c FROM houses'),
    'pending_houses' => $q("SELECT COUNT(*) c FROM houses WHERE approval_status='pending'"),
    'pending_book'   => $q("SELECT COUNT(*) c FROM bookings WHERE status='pending'"),
    'available'      => $q("SELECT COUNT(*) c FROM houses WHERE approval_status='approved' AND status='available'"),
    'failed_msgs'    => $q("SELECT COUNT(*) c FROM notifications WHERE status='failed' AND created_at > (NOW() - INTERVAL 7 DAY)"),
];
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="mb-1">Admin Control Panel</h3>
  <p class="text-muted mb-4">Manage landlords, houses, tenants, bookings and notifications.</p>
  <?php if ($stats['failed_msgs'] > 0): ?><div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> <?= $stats['failed_msgs'] ?> email/SMS message(s) failed in the last 7 days. <a href="<?= base_url('admin/notifications.php') ?>">Check message log</a> — usually an SMTP/SMS setting in <code>config/config.php</code>.</div><?php endif; ?>
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= $stats['pending_houses'] ?></div>Houses Awaiting Approval</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= $stats['pending_book'] ?></div>Pending Bookings</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['available'] ?></div>Available Houses</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['houses'] ?></div>Total Houses</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['landlords'] ?></div>Landlords</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['tenants'] ?></div>Tenants</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['students'] ?></div>Student Tenants</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $stats['tenants'] - $stats['students'] ?></div>Non-student Tenants</div></div>
  </div>
  <div class="sidebar-card"><h5>Quick Links</h5><div class="d-flex flex-wrap gap-2">
    <a class="btn btn-brand" href="<?= base_url('admin/landlords.php') ?>"><i class="bi bi-person-plus"></i> Create / Manage Landlords</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/houses.php') ?>"><i class="bi bi-houses"></i> Approve Houses</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/add_house.php') ?>"><i class="bi bi-plus-circle"></i> Add a House</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/bookings.php') ?>"><i class="bi bi-calendar-check"></i> Bookings</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/tenants.php') ?>"><i class="bi bi-people"></i> Tenants</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/team.php') ?>"><i class="bi bi-person-gear"></i> Staff &amp; Agents</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/commissions.php') ?>"><i class="bi bi-cash-coin"></i> Commissions</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/notifications.php') ?>"><i class="bi bi-chat-dots"></i> Messages</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/activity.php') ?>"><i class="bi bi-clipboard-data"></i> Activity Log</a>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
