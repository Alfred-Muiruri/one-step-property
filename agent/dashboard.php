<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('agent');
$aid = (int)$_SESSION['user_id'];
$one = function ($sql, $params) use ($pdo) { $s = $pdo->prepare($sql); $s->execute($params); return $s->fetch(); };
$houses    = (int)$one('SELECT COUNT(*) c FROM houses WHERE agent_id=? OR added_by=?', [$aid, $aid])['c'];
$pending   = (int)$one("SELECT COUNT(*) c FROM bookings WHERE agent_id=? AND status='pending'", [$aid])['c'];
$confirmed = (int)$one("SELECT COUNT(*) c FROM bookings WHERE agent_id=? AND status='confirmed'", [$aid])['c'];
$com       = $one("SELECT COALESCE(SUM(CASE WHEN status='pending' THEN amount END),0) p, COALESCE(SUM(CASE WHEN status='paid' THEN amount END),0) d FROM commissions WHERE agent_id=?", [$aid]);
$page_title = 'Agent Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="mb-1">Hello, <?= sanitize($_SESSION['full_name']) ?> 👋</h3>
  <p class="text-muted mb-4">Source houses, book clients and earn commission on every confirmed booking.</p>
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $houses ?></div>My Houses</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num"><?= $pending ?></div>Pending Bookings</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat"><div class="num"><?= $confirmed ?></div>Confirmed Bookings</div></div>
    <div class="col-sm-6 col-lg-3"><div class="dash-stat accent"><div class="num" style="font-size:1.5rem">KES <?= number_format($com['p']) ?></div>Commission Owed</div></div>
  </div>
  <div class="sidebar-card"><h5>Quick actions</h5><div class="d-flex flex-wrap gap-2">
    <a class="btn btn-brand" href="<?= base_url('agent/book_for_client.php') ?>"><i class="bi bi-person-plus"></i> Book for a Client</a>
    <a class="btn btn-outline-brand" href="<?= base_url('admin/add_house.php') ?>"><i class="bi bi-plus-circle"></i> Add a House I Sourced</a>
    <a class="btn btn-outline-brand" href="<?= base_url('agent/commissions.php') ?>"><i class="bi bi-cash-coin"></i> My Commissions</a>
    <a class="btn btn-outline-brand" href="<?= base_url('houses.php') ?>"><i class="bi bi-search"></i> Browse All Houses</a>
  </div>
  <p class="small text-muted mt-3 mb-0">Total paid out to you so far: <strong>KES <?= number_format($com['d']) ?></strong></p></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
