<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/charts.php';
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

// ---- chart data (live) ----
$months = last_months(6);
$bm = []; foreach (['confirmed','pending','rejected','cancelled'] as $s) $bm[$s] = array_fill_keys(array_keys($months), 0);
foreach ($pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m, status, COUNT(*) c FROM bookings WHERE created_at >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH,'%Y-%m-01') GROUP BY m, status") as $r)
    if (isset($bm[$r['status']][$r['m']])) $bm[$r['status']][$r['m']] = (int)$r['c'];
$hq = $pdo->query("SELECT COALESCE(SUM(approval_status='approved' AND status='available'),0) a, COALESCE(SUM(approval_status='approved' AND status='booked'),0) b,
                          COALESCE(SUM(approval_status='pending'),0) p, COALESCE(SUM(approval_status='rejected'),0) r FROM houses")->fetch();
$locs = $pdo->query("SELECT h.location, COUNT(b.id) c FROM bookings b JOIN houses h ON h.id=b.house_id GROUP BY h.location ORDER BY c DESC LIMIT 6")->fetchAll();
$tenantMix = [$stats['students'], $stats['tenants'] - $stats['students']];
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
  <div class="row g-3 mt-1">
    <div class="col-lg-8"><div class="sidebar-card h-100"><h5><i class="bi bi-bar-chart-line"></i> Bookings per month <span class="text-muted small fw-normal">(last 6 months, by outcome)</span></h5>
      <div style="height:300px"><canvas id="chBookings"></canvas></div></div></div>
    <div class="col-lg-4"><div class="sidebar-card h-100"><h5><i class="bi bi-houses"></i> Houses by status</h5>
      <div style="height:300px"><canvas id="chHouses"></canvas></div></div></div>
    <div class="col-lg-8"><div class="sidebar-card h-100"><h5><i class="bi bi-geo-alt"></i> Most-demanded locations <span class="text-muted small fw-normal">(total bookings)</span></h5>
      <div style="height:280px"><canvas id="chLocations"></canvas></div></div></div>
    <div class="col-lg-4"><div class="sidebar-card h-100"><h5><i class="bi bi-people"></i> Tenant mix</h5>
      <div style="height:280px"><canvas id="chTenants"></canvas></div></div></div>
  </div>
<?php
render_bar_chart('chBookings', $months, [
  ['label'=>'Confirmed','data'=>array_values($bm['confirmed']),'backgroundColor'=>'#0f766e'],
  ['label'=>'Pending','data'=>array_values($bm['pending']),'backgroundColor'=>'#f97316'],
  ['label'=>'Rejected','data'=>array_values($bm['rejected']),'backgroundColor'=>'#b91c1c'],
  ['label'=>'Cancelled','data'=>array_values($bm['cancelled']),'backgroundColor'=>'#94a3b8'],
], true);
render_bar_chart('chHouses', ['Available','Booked','Awaiting approval','Rejected'], [
  ['label'=>'Houses','data'=>[(int)$hq['a'],(int)$hq['b'],(int)$hq['p'],(int)$hq['r']],'backgroundColor'=>['#0f766e','#1d4ed8','#f97316','#b91c1c']],
]);
render_bar_chart('chLocations', array_column($locs, 'location'), [
  ['label'=>'Bookings','data'=>array_map('intval', array_column($locs, 'c')),'backgroundColor'=>'#0f766e'],
], false, true);
render_bar_chart('chTenants', ['Students','Non-students'], [
  ['label'=>'Tenants','data'=>$tenantMix,'backgroundColor'=>['#0f766e','#f97316']],
]);
?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
