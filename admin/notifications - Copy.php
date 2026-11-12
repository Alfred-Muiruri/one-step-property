<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aud = $_POST['audience'] ?? '';
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $where = ['tenants' => "role='tenant'", 'students' => "role='tenant' AND tenant_type='student'",
              'non_students' => "role='tenant' AND tenant_type='non_student'", 'landlords' => "role='landlord'"][$aud] ?? null;
    if (!$where || $subject === '' || $message === '') {
        flash('danger', 'Choose an audience and fill in both the subject and message.');
    } else {
        $users = $pdo->query("SELECT * FROM users WHERE status='approved' AND $where")->fetchAll();
        set_time_limit(0);
        foreach ($users as $u) notify_user($u, $subject, $message);
        flash('success', 'Announcement sent to ' . count($users) . ' user(s) on their preferred channel.');
    }
    redirect('admin/notifications.php');
}

$log = $pdo->query('SELECT n.*, u.full_name FROM notifications n LEFT JOIN users u ON u.id=n.user_id ORDER BY n.id DESC LIMIT 150')->fetchAll();

$page_title = 'Messages';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Messages &amp; Announcements</h3>
  <div class="sidebar-card mb-4">
    <h5><i class="bi bi-megaphone"></i> Send an announcement</h5>
    <p class="text-muted small">Each person receives it by the channel they chose (email, SMS or both).</p>
    <form method="POST"><?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Send to</label>
          <select name="audience" class="form-select" required>
            <option value="tenants">All tenants</option><option value="students">Student tenants</option><option value="non_students">Non-student tenants</option><option value="landlords">All landlords</option></select></div>
        <div class="col-md-9"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" maxlength="150" required></div>
        <div class="col-12"><label class="form-label">Message</label><textarea name="message" rows="3" class="form-control" maxlength="600" required></textarea></div>
      </div>
      <button class="btn btn-brand mt-3 px-4 confirm-action" data-confirm="Send this announcement now?">Send</button>
    </form>
  </div>

  <div class="sidebar-card"><h5>Recent messages</h5>
    <div class="table-responsive"><table class="table table-sm align-middle">
      <thead><tr><th>When</th><th>To</th><th>Channel</th><th>Subject</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($log as $n): ?><tr>
        <td class="small"><?= date('d M H:i', strtotime($n['created_at'])) ?></td>
        <td class="small"><?= sanitize($n['full_name'] ?? $n['recipient']) ?><br><span class="text-muted"><?= sanitize($n['recipient']) ?></span></td>
        <td><i class="bi bi-<?= $n['channel']==='sms'?'chat-text':'envelope' ?>"></i> <?= $n['channel'] ?></td>
        <td class="small"><?= sanitize($n['subject']) ?></td>
        <td><span class="badge <?= badge_class($n['status']) ?>"><?= $n['status'] ?></span><?= $n['error'] ? '<div class="small text-danger">' . sanitize($n['error']) . '</div>' : '' ?></td></tr>
      <?php endforeach; ?></tbody></table>
      <?php if (!$log): ?><p class="text-muted">Nothing sent yet.</p><?php endif; ?>
    </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
