<?php
require_once __DIR__ . '/includes/functions.php';

$location = trim($_GET['location'] ?? '');
$roomType = trim($_GET['room_type'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$keyword  = trim($_GET['keyword'] ?? '');
$for      = trim($_GET['for'] ?? '');

$sql = "SELECT h.*, (SELECT image_path FROM house_images WHERE house_id=h.id LIMIT 1) AS thumb,
               (SELECT ROUND(AVG(rating),1) FROM reviews WHERE house_id=h.id) AS avg_rating
        FROM houses h WHERE h.approval_status='approved'";
$params = [];
if ($location !== '') { $sql .= ' AND (h.location LIKE ? OR h.nearest_landmark LIKE ?)'; $params[] = "%$location%"; $params[] = "%$location%"; }
if ($roomType !== '' && isset(room_types()[$roomType])) { $sql .= ' AND h.room_type=?'; $params[] = $roomType; }
if ($maxPrice !== '' && is_numeric($maxPrice)) { $sql .= ' AND h.price<=?'; $params[] = $maxPrice; }
if ($keyword !== '') { $sql .= ' AND (h.title LIKE ? OR h.description LIKE ?)'; $params[] = "%$keyword%"; $params[] = "%$keyword%"; }
if ($for === 'students')     { $sql .= " AND h.suitable_for IN ('anyone','students')"; }
if ($for === 'non_students') { $sql .= " AND h.suitable_for IN ('anyone','non_students')"; }
$sql .= " ORDER BY h.status='available' DESC, h.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$houses = $stmt->fetchAll();

$pins = [];
foreach ($houses as $h) {
    if ($h['latitude'] !== null) {
        $pins[] = ['id' => (int)$h['id'], 't' => $h['title'], 'p' => number_format($h['price']), 'lat' => (float)$h['latitude'], 'lng' => (float)$h['longitude'], 'a' => $h['status'] === 'available'];
    }
}

$page_title = 'Browse Houses';
$needs_map = (bool)$pins;
$page_scripts = '';
if ($pins) {
    $detail = base_url('house_details.php?id=');
    $page_scripts = '<script>(function(){var pins=' . json_encode($pins, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        . ';var map=osMap("resultsMap",' . (float)MAP_DEFAULT_LAT . ',' . (float)MAP_DEFAULT_LNG . ',' . (int)MAP_DEFAULT_ZOOM . ');var b=[];'
        . 'pins.forEach(function(p){var m=L.marker([p.lat,p.lng]).addTo(map);var d=document.createElement("div");'
        . 'var s=document.createElement("strong");s.textContent=p.t;d.appendChild(s);d.appendChild(document.createElement("br"));'
        . 'd.appendChild(document.createTextNode("KES "+p.p+(p.a?"":" (booked)")));d.appendChild(document.createElement("br"));'
        . 'var a=document.createElement("a");a.href=' . json_encode($detail) . '+p.id;a.textContent="View details";d.appendChild(a);m.bindPopup(d);b.push([p.lat,p.lng]);});'
        . 'map.fitBounds(b,{padding:[40,40],maxZoom:15});})();</script>';
}
require_once __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Browse Houses</h3>

  <form method="GET" class="row g-2 mb-4 sidebar-card">
    <div class="col-md-3"><input type="text" name="location" class="form-control" placeholder="Location / area" value="<?= sanitize($location) ?>"></div>
    <div class="col-md-2"><select name="room_type" class="form-select"><option value="">Any type</option>
      <?php foreach (room_types() as $k => $l): ?><option value="<?= $k ?>" <?= $roomType===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><input type="number" name="max_price" class="form-control" placeholder="Max rent" value="<?= sanitize($maxPrice) ?>"></div>
    <div class="col-md-2"><select name="for" class="form-select">
      <option value="">Anyone</option><option value="students" <?= $for==='students'?'selected':'' ?>>Student</option><option value="non_students" <?= $for==='non_students'?'selected':'' ?>>Non-student</option></select></div>
    <div class="col-md-2"><input type="text" name="keyword" class="form-control" placeholder="Keyword" value="<?= sanitize($keyword) ?>"></div>
    <div class="col-md-1"><button class="btn btn-brand w-100" type="submit">Go</button></div>
  </form>

  <p class="text-muted"><?= count($houses) ?> house(s) found<?= $pins ? ' · ' . count($pins) . ' on the map' : '' ?></p>

  <?php if ($pins): ?><div id="resultsMap" class="map-box big mb-4"></div><?php endif; ?>

  <div class="row g-4">
    <?php foreach ($houses as $h): ?>
      <div class="col-sm-6 col-lg-4">
        <div class="card house-card">
          <img src="<?= $h['thumb'] ? base_url($h['thumb']) : base_url('assets/img/placeholder.svg') ?>" class="card-img-top" alt="<?= sanitize($h['title']) ?>">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <span class="badge badge-type"><?= room_type_label($h['room_type']) ?></span>
              <span class="badge <?= badge_class($h['status']) ?>"><?= ucfirst($h['status']) ?></span>
            </div>
            <h5 class="card-title mb-1"><?= sanitize($h['title']) ?></h5>
            <p class="text-muted small mb-1"><i class="bi bi-geo-alt"></i> <?= sanitize($h['location']) ?></p>
            <?php if ($h['suitable_for'] !== 'anyone'): ?><p class="small mb-1"><span class="badge <?= $h['suitable_for']==='students'?'badge-student':'badge-non_student' ?>"><?= suitable_label($h['suitable_for']) ?> only</span></p><?php endif; ?>
            <?php if ($h['avg_rating']): ?><p class="small mb-1 text-warning"><?= star_rating($h['avg_rating']) ?> <span class="text-muted">(<?= $h['avg_rating'] ?>)</span></p><?php endif; ?>
            <p class="price-tag mb-2">KES <?= number_format($h['price']) ?> / month</p>
            <a href="<?= base_url('house_details.php?id=' . $h['id']) ?>" class="btn btn-outline-brand btn-sm w-100">View Details</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$houses): ?><p class="text-muted">No houses matched your search. Try clearing some filters.</p><?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
