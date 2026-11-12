<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('agent');
$aid = (int)$_SESSION['user_id'];
$s = $pdo->prepare('SELECT * FROM commissions WHERE agent_id=? ORDER BY created_at DESC'); $s->execute([$aid]); $rows = $s->fetchAll();
$t = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN status='pending' THEN amount END),0) p, COALESCE(SUM(CASE WHEN status='paid' THEN amount END),0) d FROM commissions WHERE agent_id=?"); $t->execute([$aid]); $t = $t->fetch();
$page_title = 'My Commissions';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">My Commissions</h3>
  <div class="row g-3 mb-4">
    <div class="col-sm-6"><div class="dash-stat accent"><div class="num">KES <?= number_format($t['p']) ?></div>Owed to you</div></div>
    <div class="col-sm-6"><div class="dash-stat"><div class="num">KES <?= number_format($t['d']) ?></div>Paid to you</div></div>
  </div>
  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Date</th><th>House</th><th>Rent</th><th>Rate</th><th>Commission</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <td class="small"><?= date('d M Y', strtotime($r['created_at'])) ?></td><td><?= sanitize($r['house_title']) ?></td>
      <td>KES <?= number_format($r['rent']) ?></td><td><?= rtrim(rtrim($r['percent'], '0'), '.') ?>%</td><td class="fw-bold">KES <?= number_format($r['amount']) ?></td>
      <td><span class="badge <?= $r['status']==='paid'?'badge-approved':'badge-pending' ?>"><?= ucfirst($r['status']) ?></span><?= $r['reference'] ? '<br><span class="small text-muted">Ref: ' . sanitize($r['reference']) . '</span>' : '' ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$rows): ?><p class="text-muted">No commissions yet. They appear automatically when a booking you brought in is confirmed.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
