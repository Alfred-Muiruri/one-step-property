<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/charts.php';
require_role('landlord');
$lid = $_SESSION['user_id'];
$user = current_user();

$hs = $pdo->prepare("SELECT COUNT(*) total, COALESCE(SUM(approval_status='pending'),0) pending_approval, COALESCE(SUM(approval_status='rejected'),0) rejected,
    COALESCE(SUM(approval_status='approved' AND status='available'),0) available FROM houses WHERE landlord_id=?");
$hs->execute([$lid]); $hs = $hs->fetch();
$pb = $pdo->prepare("SELECT COUNT(*) c FROM bookings b JOIN houses h ON h.id=b.house_id WHERE h.landlord_id=? AND b.status='pending'");
$pb->execute([$lid]); $pb = $pb->fetch()['c'];


// ---- chart data (live, only this landlord's houses) ----
$months = last_months(6);
$bm = []; foreach (['confirmed','pending','rejected','cancelled'] as $s) $bm[$s] = array_fill_keys(array_keys($months), 0);
$st = $pdo->prepare("SELECT DATE_FORMAT(b.created_at,'%Y-%m') m, b.status, COUNT(*) c FROM bookings b JOIN houses h ON h.id=b.house_id
    WHERE h.landlord_id=? AND b.created_at >= DATE_FORMAT(NOW() - INTERVAL 5 MONTH,'%Y-%m-01') GROUP BY m, b.status");
$st->execute([$lid]);
foreach ($st as $r) if (isset($bm[$r['status']][$r['m']])) $bm[$r['status']][$r['m']] = (int)$r['c'];
$ph = $pdo->prepare("SELECT h.title, SUM(b.status='confirmed') conf, SUM(b.status='pending') pend, SUM(b.status IN ('rejected','cancelled')) lost
    FROM houses h LEFT JOIN bookings b ON b.house_id=h.id WHERE h.landlord_id=? GROUP BY h.id, h.title ORDER BY (COUNT(b.id)) DESC LIMIT 8");
$ph->execute([$lid]); $ph = $ph->fetchAll();
$page_title = 'Landlord Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="mb-1">Welcome, <?= sanitize($user['full_name']) ?> 👋</h3>
  <p class="text-muted">Your houses are managed in partnership with <?= SITE_NAME ?>. New listings go live once our team approves them.</p>
  <?php if ($hs['rejected'] > 0): ?><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= (int)$hs['rejected'] ?> listing(s) were not approved. Open <a href="<?= base_url('landlord/my_houses.php') ?>">My Houses</a> to see why and resubmit.</div><?php endif; ?>
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= (int)$hs['total'] ?></div>My Houses</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= (int)$hs['pending_approval'] ?></div>Awaiting Approval</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= (int)$hs['available'] ?></div>Live &amp; Available</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= (int)$pb ?></div>Pending Bookings</div></div>
  </div>
  <div class="sidebar-card"><h5>Quick Links</h5><div class="d-flex flex-wrap gap-2">
    <a class="btn btn-brand" href="<?= base_url('landlord/add_house.php') ?>"><i class="bi bi-plus-circle"></i> Add a House</a>
    <a class="btn btn-outline-brand" href="<?= base_url('landlord/my_houses.php') ?>"><i class="bi bi-houses"></i> My Houses</a>
    <a class="btn btn-outline-brand" href="<?= base_url('landlord/bookings.php') ?>"><i class="bi bi-calendar-check"></i> Booking Requests</a>
    <a class="btn btn-outline-brand" href="<?= base_url('landlord/profile.php') ?>"><i class="bi bi-person"></i> Profile</a>
  </div></div>
  <div class="row g-3 mt-1">
    <div class="col-lg-6"><div class="sidebar-card h-100"><h5><i class="bi bi-bar-chart-line"></i> Booking requests per month <span class="text-muted small fw-normal">(last 6 months)</span></h5>
      <div style="height:300px"><canvas id="chLBookings"></canvas></div></div></div>
    <div class="col-lg-6"><div class="sidebar-card h-100"><h5><i class="bi bi-houses"></i> Performance of each house <span class="text-muted small fw-normal">(which ones attract tenants)</span></h5>
      <div style="height:300px"><canvas id="chLHouses"></canvas></div></div></div>
  </div>
<?php
render_bar_chart('chLBookings', $months, [
  ['label'=>'Confirmed','data'=>array_values($bm['confirmed']),'backgroundColor'=>'#0f766e'],
  ['label'=>'Pending','data'=>array_values($bm['pending']),'backgroundColor'=>'#f97316'],
  ['label'=>'Rejected / cancelled','data'=>array_map(function($a,$b){return $a+$b;}, array_values($bm['rejected']), array_values($bm['cancelled'])),'backgroundColor'=>'#94a3b8'],
], true);
render_bar_chart('chLHouses', array_map(function($r){ return mb_strimwidth($r['title'], 0, 22, '…'); }, $ph), [
  ['label'=>'Confirmed','data'=>array_map(function($r){return (int)$r['conf'];}, $ph),'backgroundColor'=>'#0f766e'],
  ['label'=>'Pending','data'=>array_map(function($r){return (int)$r['pend'];}, $ph),'backgroundColor'=>'#f97316'],
  ['label'=>'Rejected / cancelled','data'=>array_map(function($r){return (int)$r['lost'];}, $ph),'backgroundColor'=>'#94a3b8'],
], true);
?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
