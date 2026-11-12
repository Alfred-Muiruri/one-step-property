<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('agent');
$aid = (int)$_SESSION['user_id'];
$s = $pdo->prepare("SELECT b.*, h.title, h.location, h.price, u.full_name AS tenant_name, u.phone AS tenant_phone, c.amount AS commission, c.status AS commission_status
    FROM bookings b JOIN houses h ON h.id=b.house_id JOIN users u ON u.id=b.tenant_id LEFT JOIN commissions c ON c.booking_id=b.id
    WHERE b.agent_id=? ORDER BY b.created_at DESC");
$s->execute([$aid]); $rows = $s->fetchAll();
$page_title = 'My Bookings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="section-title mb-0">My Bookings</h3>
    <a href="<?= base_url('agent/book_for_client.php') ?>" class="btn btn-brand"><i class="bi bi-person-plus"></i> Book for a Client</a>
  </div>
  <div class="sidebar-card"><div class="table-responsive"><table class="table align-middle">
    <thead><tr><th>House</th><th>Client</th><th>Move-in</th><th>Status</th><th>Commission</th></tr></thead><tbody>
    <?php foreach ($rows as $b): ?><tr>
      <td><?= sanitize($b['title']) ?><br><span class="small text-muted"><?= sanitize($b['location']) ?> · KES <?= number_format($b['price']) ?></span></td>
      <td><?= sanitize($b['tenant_name']) ?><br><span class="small text-muted"><?= sanitize($b['tenant_phone']) ?></span></td>
      <td class="small"><?= $b['move_in_date'] ? date('d M Y', strtotime($b['move_in_date'])) : '—' ?></td>
      <td><span class="badge <?= badge_class($b['status']) ?>"><?= ucfirst($b['status']) ?></span></td>
      <td><?= $b['commission'] !== null ? 'KES ' . number_format($b['commission']) . ' <span class="badge ' . ($b['commission_status']==='paid'?'badge-approved':'badge-pending') . '">' . ucfirst($b['commission_status']) . '</span>' : '<span class="text-muted small">—</span>' ?></td></tr>
    <?php endforeach; ?></tbody></table>
    <?php if (!$rows): ?><p class="text-muted">No bookings yet.</p><?php endif; ?>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
