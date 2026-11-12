<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');
$rows = $pdo->query('SELECT a.*, u.full_name FROM activity_log a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.id DESC LIMIT 300')->fetchAll();
$page_title = 'Activity Log';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Activity Log</h3>
  <p class="text-muted small">Everything staff, agents and admins do on behalf of landlords — for accountability.</p>
  <div class="sidebar-card"><div class="table-responsive"><table class="table table-sm align-middle">
    <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr>
      <td class="small text-nowrap"><?= date('d M H:i', strtotime($r['created_at'])) ?></td>
      <td><?= sanitize($r['full_name'] ?? '—') ?> <span class="badge badge-<?= in_array($r['role'], ['staff','agent'], true) ? $r['role'] : 'type' ?>"><?= sanitize($r['role']) ?></span></td>
      <td><?= sanitize($r['action']) ?></td><td class="small text-muted"><?= sanitize($r['details']) ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$rows): ?><p class="text-muted">No activity recorded yet.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
