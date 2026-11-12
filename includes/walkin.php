<?php
// Register a walk-in client and book a house for them. Used by office staff (staff/walkin.php) and agents (agent/book_for_client.php).
require_once __DIR__ . '/functions.php';
require_roles(['staff', 'agent', 'admin']);
$role = current_role();
$me = current_user();

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'tenant_type' => 'student', 'organization' => '', 'notify_pref' => '', 'house_id' => (int)($_GET['house'] ?? 0), 'move_in_date' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['full_name', 'email', 'phone', 'organization', 'move_in_date', 'message'] as $k) $old[$k] = trim($_POST[$k] ?? '');
    $old['tenant_type'] = ($_POST['tenant_type'] ?? '') === 'non_student' ? 'non_student' : 'student';
    $old['notify_pref'] = in_array($_POST['notify_pref'] ?? '', ['email', 'sms', 'both'], true) ? $_POST['notify_pref'] : '';
    $old['house_id'] = (int)($_POST['house_id'] ?? 0);

    [$errors, $client, $isNew, $emailOk, $tmp] = find_or_create_client($old);
    if (!$errors) {
        $agentId = $role === 'agent' ? (int)$me['id'] : null;
        [$ok, $msg, $bid] = create_booking($client['id'], $old['house_id'], $old['move_in_date'], $old['message'], $role === 'agent' ? 'agent' : 'staff', $me['id'], $agentId);
        if (!$ok) {
            $errors[] = $msg . ($isNew ? ' (The client account was still created.)' : '');
        } else {
            if ($role !== 'agent' && !empty($_POST['confirm_now'])) set_booking_status($bid, 'confirmed');
            log_activity($isNew ? 'Client registered + booked' : 'Booking for existing client', $client['full_name']);
            $note = $isNew ? ($emailOk ? ' Their login details were emailed.' : ' Email failed — give them this temporary password: ' . $tmp) : '';
            flash('success', 'Booking recorded for ' . $client['full_name'] . '.' . (!empty($_POST['confirm_now']) && $role !== 'agent' ? ' It has been confirmed.' : '') . $note);
            redirect($role === 'agent' ? 'agent/bookings.php' : 'admin/bookings.php');
        }
    }
}

$houses = $pdo->query("SELECT id, title, location, price, room_type FROM houses WHERE approval_status='approved' AND status='available' ORDER BY title")->fetchAll();

$page_title = 'Register & Book a Client';
require_once __DIR__ . '/header.php';
?>
<div class="container py-4" style="max-width:860px">
  <h3 class="section-title">Register &amp; Book a Client</h3>
  <p class="text-muted small">For walk-in clients and people you have met. If the email already has a tenant account, we use it; otherwise we create one and email the client their login details.
    <?php if ($role === 'agent'): ?> The booking is credited to you for commission once it is confirmed.<?php endif; ?></p>
  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" novalidate><?= csrf_field() ?>
      <h6 class="fw-bold">1. Client</h6>
      <div class="row g-3 mb-4">
        <div class="col-md-6"><label class="form-label">Full Name</label><input data-v="name" maxlength="100" pattern="[A-Za-z .'\-]+" title="Letters only — no digits or symbols" type="text" name="full_name" class="form-control" value="<?= sanitize($old['full_name']) ?>" required></div>
        <div class="col-md-6"><label class="form-label">Email <span class="text-muted small">(client's real email)</span></label><input type="email" name="email" class="form-control" value="<?= sanitize($old['email']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input data-v="phone" maxlength="10" minlength="10" inputmode="numeric" pattern="0[17][0-9]{8}" title="Exactly 10 digits, e.g. 0712345678" type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?= sanitize($old['phone']) ?>" required></div>
        <div class="col-md-4"><label class="form-label">Client is a</label>
          <div class="btn-group w-100">
            <input type="radio" class="btn-check" name="tenant_type" id="tS" value="student" <?= $old['tenant_type']==='student'?'checked':'' ?>><label class="btn btn-outline-brand" for="tS">Student</label>
            <input type="radio" class="btn-check" name="tenant_type" id="tN" value="non_student" <?= $old['tenant_type']==='non_student'?'checked':'' ?>><label class="btn btn-outline-brand" for="tN">Non-student</label>
          </div></div>
        <div class="col-md-4"><label class="form-label" id="orgLabel">University / College</label><input type="text" name="organization" class="form-control" value="<?= sanitize($old['organization']) ?>"></div>
        <div class="col-md-4"><label class="form-label">Notify client by</label>
          <select name="notify_pref" class="form-select" required><option value="">Select how to notify…</option><?php foreach (['email'=>'Email','sms'=>'SMS','both'=>'Email and SMS'] as $k=>$l): ?><option value="<?= $k ?>" <?= $old['notify_pref']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      </div>
      <h6 class="fw-bold">2. House</h6>
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Available house</label>
          <select name="house_id" class="form-select" required><option value="">Select a house…</option>
            <?php foreach ($houses as $h): ?><option value="<?= $h['id'] ?>" <?= (int)$old['house_id']===(int)$h['id']?'selected':'' ?>><?= sanitize($h['title']) ?> — <?= sanitize($h['location']) ?> — KES <?= number_format($h['price']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">Move-in date</label><input type="date" name="move_in_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= sanitize($old['move_in_date']) ?>" required></div>
        <div class="col-12"><label class="form-label">Note <span class="text-muted small">optional</span></label><input type="text" name="message" maxlength="250" class="form-control" value="<?= sanitize($old['message']) ?>"></div>
        <?php if ($role !== 'agent'): ?>
          <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="confirm_now" id="cn" value="1"><label class="form-check-label" for="cn">Confirm the booking immediately (client has already agreed / paid at the office)</label></div></div>
        <?php endif; ?>
      </div>
      <button class="btn btn-brand mt-4 px-4 py-2"><i class="bi bi-check2-circle"></i> Save Booking</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
