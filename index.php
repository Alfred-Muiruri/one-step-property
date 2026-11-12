<?php
require_once __DIR__ . '/includes/functions.php';

$featured = $pdo->query(
    "SELECT h.*, (SELECT image_path FROM house_images WHERE house_id=h.id LIMIT 1) AS thumb
     FROM houses h WHERE h.approval_status='approved' AND h.status='available'
     ORDER BY h.created_at DESC LIMIT 6"
)->fetchAll();

$totalHouses    = $pdo->query("SELECT COUNT(*) c FROM houses WHERE approval_status='approved'")->fetch()['c'];
$availableNow   = $pdo->query("SELECT COUNT(*) c FROM houses WHERE approval_status='approved' AND status='available'")->fetch()['c'];
$totalTenants   = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='tenant'")->fetch()['c'];

$page_title = 'Find Your Next Home';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <div class="row"><div class="col-lg-8">
      <span class="hero-badge"><i class="bi bi-patch-check-fill"></i> Professionally managed homes</span>
      <h1 class="fw-bold">Your next home is just<br>one step away.</h1>
      <p class="lead"><?= SITE_NAME ?> manages quality houses for students, working professionals and families. Browse, see the exact location on the map, and book online — we handle the rest.</p>
      <div class="d-flex gap-3 flex-wrap mt-3">
        <div class="stat-pill"><strong><?= (int)$availableNow ?></strong> Available now</div>
        <div class="stat-pill"><strong><?= (int)$totalHouses ?></strong> Managed houses</div>
        <div class="stat-pill"><strong><?= (int)$totalTenants ?></strong> Happy tenants</div>
      </div>
    </div></div>
  </div>
</section>

<div class="container">
  <div class="search-card">
    <form method="GET" action="<?= base_url('houses.php') ?>" class="row g-3 align-items-end">
      <div class="col-md-3"><label class="form-label small fw-semibold">Location / Area</label>
        <input type="text" name="location" class="form-control" placeholder="e.g. Juja, Thika, Kahawa…"></div>
      <div class="col-md-3"><label class="form-label small fw-semibold">House Type</label>
        <select name="room_type" class="form-select"><option value="">Any type</option>
          <?php foreach (room_types() as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><label class="form-label small fw-semibold">Max Rent (KES)</label>
        <input type="number" name="max_price" class="form-control" placeholder="e.g. 15000"></div>
      <div class="col-md-2"><label class="form-label small fw-semibold">I am a</label>
        <select name="for" class="form-select"><option value="">Anyone</option><option value="students">Student</option><option value="non_students">Non-student</option></select></div>
      <div class="col-md-2"><button class="btn btn-warm w-100 py-2" type="submit"><i class="bi bi-search"></i> Search</button></div>
    </form>
  </div>
</div>

<div class="container my-5">
  <h3 class="section-title">Latest Available Houses</h3>
  <div class="row g-4">
    <?php if (!$featured): ?><p class="text-muted">New houses are being added — check back soon, or call us on <?= sanitize(COMPANY_PHONE) ?>.</p><?php endif; ?>
    <?php foreach ($featured as $h): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card house-card">
          <img src="<?= $h['thumb'] ? base_url($h['thumb']) : base_url('assets/img/placeholder.svg') ?>" class="card-img-top" alt="<?= sanitize($h['title']) ?>">
          <div class="card-body">
            <span class="badge badge-type mb-2"><?= room_type_label($h['room_type']) ?></span>
            <?php if ($h['latitude']): ?><span class="badge bg-light text-dark border mb-2"><i class="bi bi-geo-alt-fill text-danger"></i> Pinned</span><?php endif; ?>
            <h5 class="card-title mb-1"><?= sanitize($h['title']) ?></h5>
            <p class="text-muted small mb-1"><i class="bi bi-geo-alt"></i> <?= sanitize($h['location']) ?></p>
            <p class="price-tag mb-2">KES <?= number_format($h['price']) ?> / month</p>
            <a href="<?= base_url('house_details.php?id=' . $h['id']) ?>" class="btn btn-outline-brand btn-sm w-100">View Details</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($featured): ?><div class="text-center mt-4"><a href="<?= base_url('houses.php') ?>" class="btn btn-brand px-4">Browse All Houses</a></div><?php endif; ?>
</div>

<div class="container my-5">
  <div class="row g-4">
    <?php foreach ([['bi-search','1','Search','Filter by area, house type and rent. Open any listing to see its exact pin on the map.'],
                    ['bi-calendar-check','2','Book','Send a booking request with your move-in date. We confirm and keep you updated by email or SMS.'],
                    ['bi-house-heart','3','Move in','Pay through our official channels, get your keys and settle in — we manage the house for you.']] as $s): ?>
      <div class="col-md-4"><div class="sidebar-card text-center h-100">
        <i class="bi <?= $s[0] ?> fs-1" style="color:var(--brand)"></i>
        <h5 class="mt-2"><span class="step-num me-1"><?= $s[1] ?></span> <?= $s[2] ?></h5>
        <p class="text-muted small mb-0"><?= $s[3] ?></p>
      </div></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="container my-5">
  <div class="sidebar-card d-md-flex justify-content-between align-items-center gap-3">
    <div><h5 class="mb-1">Own a house? Let us manage it.</h5>
      <p class="text-muted mb-0 small">We list, market and manage your property. After a management agreement, you get a landlord account — post your houses at no listing fee.</p></div>
    <a href="mailto:<?= sanitize(COMPANY_EMAIL) ?>" class="btn btn-warm mt-3 mt-md-0 text-nowrap"><i class="bi bi-envelope"></i> Talk to us</a>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
