<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT h.*, u.full_name AS landlord_name FROM houses h JOIN users u ON u.id=h.landlord_id WHERE h.id=?');
$stmt->execute([$id]);
$house = $stmt->fetch();

$isOwnerOrAdmin = $house && is_logged_in() && (current_role() === 'admin' || (current_role() === 'landlord' && (int)$house['landlord_id'] === (int)$_SESSION['user_id']));
if (!$house || ($house['approval_status'] !== 'approved' && !$isOwnerOrAdmin)) {
    flash('danger', 'That listing could not be found.');
    redirect('houses.php');
}

$images = $pdo->prepare('SELECT * FROM house_images WHERE house_id=?'); $images->execute([$id]); $images = $images->fetchAll();

$tenant = (is_logged_in() && current_role() === 'tenant') ? current_user() : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!$tenant) { flash('warning', 'Please log in with a tenant account to do that.'); redirect('auth/login.php'); }
    $tid = (int)$tenant['id'];

    if ($action === 'book') {
        [$ok, $msgText] = create_booking($tid, $id, trim($_POST['move_in_date'] ?? ''), trim($_POST['message'] ?? ''), 'online', $tid, null);
        flash($ok ? 'success' : 'danger', $ok ? 'Booking request sent! We will notify you once it is confirmed.' : $msgText);
        redirect('house_details.php?id=' . $id);
    }

    if ($action === 'wishlist') {
        $ex = $pdo->prepare('SELECT id FROM wishlist WHERE tenant_id=? AND house_id=?'); $ex->execute([$tid, $id]);
        if ($ex->fetch()) { $pdo->prepare('DELETE FROM wishlist WHERE tenant_id=? AND house_id=?')->execute([$tid, $id]); flash('success', 'Removed from wishlist.'); }
        else { $pdo->prepare('INSERT INTO wishlist (tenant_id, house_id) VALUES (?,?)')->execute([$tid, $id]); flash('success', 'Added to wishlist.'); }
        redirect('house_details.php?id=' . $id);
    }

    if ($action === 'review') {
        $ok = $pdo->prepare("SELECT id FROM bookings WHERE tenant_id=? AND house_id=? AND status='confirmed'"); $ok->execute([$tid, $id]);
        $had = $pdo->prepare('SELECT id FROM reviews WHERE tenant_id=? AND house_id=?'); $had->execute([$tid, $id]);
        if (!$ok->fetch()) flash('warning', 'Only tenants with a confirmed booking can review this house.');
        elseif ($had->fetch()) flash('info', 'You have already reviewed this house.');
        else {
            $pdo->prepare('INSERT INTO reviews (house_id, tenant_id, rating, comment) VALUES (?,?,?,?)')
                ->execute([$id, $tid, max(1, min(5, (int)($_POST['rating'] ?? 5))), mb_substr(trim($_POST['comment'] ?? ''), 0, 500)]);
            flash('success', 'Thanks for your review!');
        }
        redirect('house_details.php?id=' . $id);
    }
}

$reviews = $pdo->prepare('SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id=r.tenant_id WHERE r.house_id=? ORDER BY r.created_at DESC');
$reviews->execute([$id]); $reviews = $reviews->fetchAll();
$avgRating = $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : null;

$isWishlisted = false; $myBooking = null;
if ($tenant) {
    $w = $pdo->prepare('SELECT id FROM wishlist WHERE tenant_id=? AND house_id=?'); $w->execute([$tenant['id'], $id]); $isWishlisted = (bool)$w->fetch();
    $mb = $pdo->prepare("SELECT * FROM bookings WHERE tenant_id=? AND house_id=? AND status IN ('pending','confirmed') ORDER BY id DESC LIMIT 1");
    $mb->execute([$tenant['id'], $id]); $myBooking = $mb->fetch();
}

$hasPin = $house['latitude'] !== null && $house['longitude'] !== null;
$page_title = $house['title'];
$needs_map = $hasPin;
$page_scripts = $hasPin
    ? '<script>(function(){var lat=' . (float)$house['latitude'] . ',lng=' . (float)$house['longitude'] . ';var m=osMap("houseMap",lat,lng,16);L.marker([lat,lng]).addTo(m);})();</script>'
    : '';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <?php if ($house['approval_status'] !== 'approved'): ?>
    <div class="alert alert-warning"><i class="bi bi-eye"></i> Preview — this listing is <strong><?= sanitize($house['approval_status']) ?></strong> and not visible to the public yet.</div>
  <?php endif; ?>
  <div class="row g-4">
    <div class="col-lg-7">
      <img id="mainHouseImage" src="<?= $images ? base_url($images[0]['image_path']) : base_url('assets/img/placeholder.svg') ?>" class="img-fluid rounded-4 mb-2 w-100" style="max-height:420px;object-fit:cover;" alt="<?= sanitize($house['title']) ?>">
      <?php if (count($images) > 1): ?>
        <div class="thumb-scroll mb-3">
          <?php foreach ($images as $i => $img): ?><img src="<?= base_url($img['image_path']) ?>" data-full="<?= base_url($img['image_path']) ?>" class="<?= $i===0?'active':'' ?>" alt=""><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
        <span class="badge badge-type"><?= room_type_label($house['room_type']) ?></span>
        <span class="badge <?= badge_class($house['status']) ?>"><?= ucfirst($house['status']) ?></span>
        <?php if ($house['suitable_for'] !== 'anyone'): ?><span class="badge <?= $house['suitable_for']==='students'?'badge-student':'badge-non_student' ?>"><?= suitable_label($house['suitable_for']) ?> only</span><?php endif; ?>
        <?php if ($avgRating): ?><span class="text-warning small"><?= star_rating($avgRating) ?> (<?= $avgRating ?>)</span><?php endif; ?>
      </div>
      <h2><?= sanitize($house['title']) ?></h2>
      <p class="text-muted"><i class="bi bi-geo-alt"></i> <?= sanitize($house['location']) ?>
        <?php if ($house['nearest_landmark']): ?> &middot; near <?= sanitize($house['nearest_landmark']) ?><?php endif; ?>
        <?php if ($house['distance_notes']): ?> &middot; <?= sanitize($house['distance_notes']) ?><?php endif; ?></p>
      <p class="price-tag fs-4 mb-1">KES <?= number_format($house['price']) ?> / month</p>
      <?php if ($house['deposit'] !== null): ?><p class="text-muted small">Deposit: KES <?= number_format($house['deposit']) ?></p><?php endif; ?>
      <p><?= nl2br(sanitize($house['description'])) ?></p>
      <?php if ($house['amenities']): ?>
        <p><strong>Amenities:</strong>
          <?php foreach (array_filter(array_map('trim', explode(',', $house['amenities']))) as $a): ?><span class="badge bg-light text-dark border"><?= sanitize($a) ?></span> <?php endforeach; ?></p>
      <?php endif; ?>

      <?php if ($hasPin): ?>
        <h5 class="mt-4"><i class="bi bi-geo-alt-fill text-danger"></i> Location</h5>
        <div id="houseMap" class="map-box mb-2"></div>
        <a class="btn btn-outline-brand btn-sm" target="_blank" rel="noopener"
           href="https://www.google.com/maps/dir/?api=1&destination=<?= (float)$house['latitude'] ?>,<?= (float)$house['longitude'] ?>"><i class="bi bi-sign-turn-right"></i> Get directions</a>
      <?php endif; ?>

      <hr>
      <h5>Reviews <?= $reviews ? '(' . count($reviews) . ')' : '' ?></h5>
      <?php foreach ($reviews as $r): ?>
        <div class="mb-3"><strong><?= sanitize($r['full_name']) ?></strong> <span class="text-warning"><?= star_rating($r['rating']) ?></span>
          <p class="mb-0 small text-muted"><?= sanitize($r['comment']) ?></p></div>
      <?php endforeach; ?>
      <?php if (!$reviews): ?><p class="text-muted small">No reviews yet.</p><?php endif; ?>

      <?php if ($tenant && $myBooking && $myBooking['status'] === 'confirmed'): ?>
        <form method="POST" class="sidebar-card mt-3">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="review">
          <label class="form-label fw-semibold">Leave a review</label>
          <select name="rating" class="form-select mb-2" style="max-width:160px;"><?php for ($s = 5; $s >= 1; $s--): ?><option value="<?= $s ?>"><?= $s ?> Star<?= $s>1?'s':'' ?></option><?php endfor; ?></select>
          <textarea name="comment" class="form-control mb-2" rows="2" placeholder="Share your experience..."></textarea>
          <button class="btn btn-outline-brand btn-sm">Submit Review</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="col-lg-5">
      <div class="sidebar-card mb-3">
        <h6 class="fw-bold"><i class="bi bi-building"></i> Managed by <?= SITE_NAME ?></h6>
        <p class="mb-1 small text-muted"><i class="bi bi-telephone"></i> <?= sanitize(COMPANY_PHONE) ?></p>
        <p class="mb-1 small text-muted"><i class="bi bi-envelope"></i> <?= sanitize(COMPANY_EMAIL) ?></p>
        <?php if ($wa = normalize_phone(COMPANY_WHATSAPP)): ?><a class="btn btn-sm btn-outline-success mt-1" target="_blank" rel="noopener" href="https://wa.me/<?= ltrim($wa, '+') ?>?text=<?= rawurlencode('Hello, I am interested in: ' . $house['title']) ?>"><i class="bi bi-whatsapp"></i> WhatsApp us about this house</a><?php endif; ?>
      </div>

      <?php if ($myBooking && $myBooking['status'] === 'confirmed' && PAY_INSTRUCTIONS !== ''): ?>
        <div class="pay-info mb-3"><h6 class="fw-bold"><i class="bi bi-cash-coin"></i> How to pay</h6><p class="mb-0 small"><?= sanitize(PAY_INSTRUCTIONS) ?></p></div>
      <?php endif; ?>

      <div class="sidebar-card">
        <?php if ($myBooking): ?>
          <div class="alert alert-<?= $myBooking['status']==='confirmed'?'success':'info' ?> py-2">
            Your booking is <strong><?= $myBooking['status'] ?></strong>.
            <a href="<?= base_url('tenant/my_bookings.php') ?>">View my bookings</a></div>
        <?php elseif ($house['status'] === 'available' && $house['approval_status'] === 'approved'): ?>
          <?php if (!is_logged_in()): ?>
            <p class="small text-muted">Log in or create a free tenant account to book this house.</p>
            <a href="<?= base_url('auth/login.php') ?>" class="btn btn-brand w-100 mb-2">Log in to book</a>
            <a href="<?= base_url('auth/register.php') ?>" class="btn btn-outline-brand w-100">Create account</a>
          <?php elseif ($tenant): ?>
            <form method="POST">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="book">
              <label class="form-label small fw-semibold">Preferred move-in date</label>
              <input type="date" name="move_in_date" class="form-control mb-2" min="<?= date('Y-m-d') ?>" required>
              <label class="form-label small fw-semibold">Message (optional)</label>
              <textarea name="message" class="form-control mb-2" rows="2" maxlength="250" placeholder="Anything we should know?"></textarea>
              <button type="submit" class="btn btn-brand w-100 py-2 mb-2"><i class="bi bi-calendar-check"></i> Request Booking</button>
            </form>
          <?php else: ?>
            <p class="small text-muted mb-0">Bookings are made from tenant accounts.</p>
          <?php endif; ?>
        <?php else: ?>
          <button class="btn btn-secondary w-100 py-2 mb-2" disabled>Not available</button>
        <?php endif; ?>
        <?php if ($tenant): ?>
          <form method="POST" class="mt-2"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="wishlist">
            <button type="submit" class="btn btn-outline-brand w-100"><i class="bi bi-heart<?= $isWishlisted?'-fill':'' ?>"></i> <?= $isWishlisted?'Remove from Wishlist':'Save to Wishlist' ?></button></form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
