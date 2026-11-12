<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $cid = (int)($_POST['commission_id'] ?? 0);
    $ref = mb_substr(trim($_POST['reference'] ?? ''), 0, 60);
    $q = $pdo->prepare("SELECT c.*, u.* , c.id AS cid FROM commissions c JOIN users u ON u.id=c.agent_id WHERE c.id=? AND c.status='pending'");
    $q->execute([$cid]);
    if ($c = $q->fetch()) {
        $pdo->prepare("UPDATE commissions SET status='paid', reference=?, paid_at=NOW() WHERE id=?")->execute([$ref ?: null, $cid]);
        notify_user($c, 'Commission paid: ' . $c['house_title'], 'Your commission of KES ' . number_format($c['amount']) . ' for "' . $c['house_title'] . '" has been paid.' . ($ref ? ' Reference: ' . $ref . '.' : ''), 'My commissions', 'agent/commissions.php');
        log_activity('Commission paid', 'KES ' . number_format($c['amount']) . ' · ' . $c['full_name']);
        flash('success', 'Marked as paid and the agent was notified.');
    }
    redirect('admin/commissions.php');
}

$f = $_GET['f'] ?? 'pending';
$where = in_array($f, ['pending', 'paid'], true) ? "WHERE c.status='$f'" : '';
$rows = $pdo->query("SELECT c.*, u.full_name AS agent_name FROM commissions c JOIN users u ON u.id=c.agent_id $where ORDER BY c.created_at DESC")->fetchAll();
$tot = $pdo->query("SELECT COALESCE(SUM(CASE WHEN status='pending' THEN amount END),0) p, COALESCE(SUM(CASE WHEN status='paid' THEN amount END),0) d FROM commissions")->fetch();

$page_title = 'Commissions';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Agent Commissions</h3>
  <div class="row g-3 mb-4">
    <div class="col-sm-6"><div class="dash-stat accent"><div class="num">KES <?= number_format($tot['p']) ?></div>Owed (pending)</div></div>
    <div class="col-sm-6"><div class="dash-stat"><div class="num">KES <?= number_format($tot['d']) ?></div>Paid out</div></div>
  </div>
  <div class="mb-3 d-flex gap-2"><?php foreach (['pending' => 'Pending', 'paid' => 'Paid', 'all' => 'All'] as $k => $l): ?><a class="btn btn-sm <?= $f===$k?'btn-brand':'btn-outline-brand' ?>" href="?f=<?= $k ?>"><?= $l ?></a><?php endforeach; ?></div>
  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>Agent</th><th>House</th><th>Rent</th><th>Rate</th><th>Commission</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <td><?= sanitize($r['agent_name']) ?></td><td><?= sanitize($r['house_title']) ?><br><span class="small text-muted"><?= date('d M Y', strtotime($r['created_at'])) ?></span></td>
      <td>KES <?= number_format($r['rent']) ?></td><td><?= rtrim(rtrim($r['percent'], '0'), '.') ?>%</td><td class="fw-bold">KES <?= number_format($r['amount']) ?></td>
      <td><span class="badge <?= $r['status']==='paid' ? 'badge-approved' : 'badge-pending' ?>"><?= ucfirst($r['status']) ?></span><?= $r['reference'] ? '<br><span class="small text-muted">' . sanitize($r['reference']) . '</span>' : '' ?></td>
      <td><?php if ($r['status'] === 'pending'): ?>
        <form method="POST" class="input-group input-group-sm" style="min-width:230px"><?= csrf_field() ?><input type="hidden" name="action" value="pay"><input type="hidden" name="commission_id" value="<?= $r['id'] ?>">
          <input type="text" name="reference" class="form-control" placeholder="M-Pesa ref (optional)"><button class="btn btn-brand confirm-action" data-confirm="Mark this commission as paid?">Mark paid</button></form>
      <?php endif; ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$rows): ?><p class="text-muted">Nothing here yet. Commissions appear automatically when a booking brought in by an agent is confirmed.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
