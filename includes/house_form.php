<?php
/** Read + validate house form input (shared by landlord add/edit and admin add). */
function house_input_from_post() {
    $in = [];
    foreach (['title','location','nearest_landmark','distance_notes','description','amenities'] as $k) $in[$k] = trim($_POST[$k] ?? '');
    $in['room_type']    = array_key_exists($_POST['room_type'] ?? '', room_types()) ? $_POST['room_type'] : 'bedsitter';
    $in['suitable_for'] = in_array($_POST['suitable_for'] ?? '', ['anyone','students','non_students'], true) ? $_POST['suitable_for'] : 'anyone';
    $in['units']        = (int)($_POST['units'] ?? 1);
    $in['price']        = trim($_POST['price'] ?? '');
    $in['deposit']      = trim($_POST['deposit'] ?? '');
    $lat = trim($_POST['latitude'] ?? ''); $lng = trim($_POST['longitude'] ?? '');
    $in['latitude']  = (is_numeric($lat) && $lat >= -90  && $lat <= 90)  ? $lat : '';
    $in['longitude'] = (is_numeric($lng) && $lng >= -180 && $lng <= 180) ? $lng : '';
    return $in;
}

function house_input_errors(array $in) {
    $e = [];
    $units = $in['units'] ?? 1;
    if ($units < 1 || $units > MAX_UNITS_PER_BATCH) $e[] = 'Number of units must be between 1 and ' . MAX_UNITS_PER_BATCH . '.';
    if ($units > 1 && mb_strlen($in['title']) > 130) $e[] = 'With several units the title must be 130 characters or less (the unit number is added to it).';
    if (mb_strlen($in['title']) < 5 || mb_strlen($in['title']) > 150) $e[] = 'Title must be 5–150 characters.';
    elseif (!preg_match('/\p{L}{3}/u', $in['title'])) $e[] = 'Title must describe the house in words (not just numbers).';
    if (!is_numeric($in['price']) || $in['price'] <= 0) $e[] = 'Enter a valid monthly rent.';
    elseif ($in['price'] > 10000000) $e[] = 'Monthly rent looks too high. Please check the amount.';
    if ($in['deposit'] !== '' && (!is_numeric($in['deposit']) || $in['deposit'] < 0)) $e[] = 'Deposit must be a valid amount.';
    elseif ($in['deposit'] !== '' && $in['deposit'] > 10000000) $e[] = 'Deposit looks too high. Please check the amount.';
    if ($in['location'] === '' || mb_strlen($in['location']) > 150) $e[] = 'Location / area is required (max 150 characters).';
    elseif (!preg_match('/\p{L}{3}/u', $in['location'])) $e[] = 'Location must be a place name (letters), e.g. Kahawa West, Nairobi.';
    if (mb_strlen($in['description']) > 2000) $e[] = 'Description is too long (max 2000 characters).';
    if ($in['latitude'] === '' || $in['longitude'] === '') $e[] = 'Please pin the house location on the map.';
    return $e;
}

const MAX_UNITS_PER_BATCH = 200;

/**
 * Insert one house, or $in['units'] identical units (sharing the same uploaded photo(s)), in a single transaction.
 * Returns the list of new house ids. Photos come from the form's photos[] input.
 */
function create_house_units(array $in, $landlordId, $addedBy = null, $agentId = null, $approval = 'pending') {
    $pdo = db();
    $units = max(1, (int)($in['units'] ?? 1));
    $pad = strlen((string)$units);
    $ins = $pdo->prepare('INSERT INTO houses (landlord_id,added_by,agent_id,title,room_type,suitable_for,price,deposit,location,nearest_landmark,distance_notes,description,amenities,latitude,longitude,approval_status)
                          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $ids = [];
    $pdo->beginTransaction();
    try {
        for ($i = 1; $i <= $units; $i++) {
            $title = $units > 1 ? $in['title'] . ' - Unit ' . str_pad((string)$i, max(2, $pad), '0', STR_PAD_LEFT) : $in['title'];
            $ins->execute([$landlordId, $addedBy, $agentId ?: null, $title, $in['room_type'], $in['suitable_for'], $in['price'], $in['deposit'] === '' ? null : $in['deposit'],
                           $in['location'], $in['nearest_landmark'] ?: null, $in['distance_notes'] ?: null, $in['description'] ?: null, $in['amenities'] ?: null,
                           $in['latitude'], $in['longitude'], $approval]);
            $ids[] = (int)$pdo->lastInsertId();
        }
        save_house_photos($ids[0]);                       // upload once…
        $copy = $pdo->prepare('INSERT INTO house_images (house_id, image_path) SELECT ?, image_path FROM house_images WHERE house_id=?');
        for ($k = 1; $k < count($ids); $k++) $copy->execute([$ids[$k], $ids[0]]);   // …and share with every other unit
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return $ids;
}

function house_empty() {
    return ['units'=>1,'title'=>'','room_type'=>'bedsitter','suitable_for'=>'anyone','price'=>'','deposit'=>'','location'=>'','nearest_landmark'=>'',
            'distance_notes'=>'','description'=>'','amenities'=>'','latitude'=>'','longitude'=>''];
}

/** Echo the form body (fields + map picker). Caller supplies <form> tag, csrf and submit button. */
function render_house_fields(array $h, $showUnits = false) {
    $v = function ($k) use ($h) { return sanitize($h[$k] ?? ''); };
    ?>
    <div class="row g-3">
      <?php if ($showUnits): ?>
      <div class="col-12"><div class="p-3 rounded-3 border bg-light">
        <div class="row g-3 align-items-center">
          <div class="col-md-3"><label class="form-label fw-semibold mb-1"><i class="bi bi-buildings"></i> Number of units</label>
            <input type="number" name="units" id="unitsInput" min="1" max="<?= MAX_UNITS_PER_BATCH ?>" step="1" class="form-control" value="<?= (int)($h['units'] ?? 1) ?>" required></div>
          <div class="col-md-9 small text-muted" id="unitsHelp">Listing a single house? Leave this as <strong>1</strong>. Have many identical rooms in a row (e.g. 40 single rooms)?
            Enter the number of units and upload <strong>one photo of the whole building</strong> — we create every unit for you, numbered
            <em>Title - Unit 01, Unit 02 …</em>, all sharing the same details and photo. Each unit is booked separately.</div>
        </div></div></div>
      <?php endif; ?>
      <div class="col-md-8"><label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?= $v('title') ?>" placeholder="e.g. Spacious 2-bedroom near the main road" required></div>
      <div class="col-md-4"><label class="form-label">House Type</label>
        <select name="room_type" class="form-select">
          <?php foreach (room_types() as $k => $label): ?><option value="<?= $k ?>" <?= ($h['room_type'] ?? '')===$k?'selected':'' ?>><?= $label ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">Monthly Rent (KES)</label>
        <input type="number" step="0.01" min="1" name="price" class="form-control" value="<?= $v('price') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Deposit (KES) <span class="text-muted small">optional</span></label>
        <input type="number" step="0.01" min="0" name="deposit" class="form-control" value="<?= $v('deposit') ?>"></div>
      <div class="col-md-4"><label class="form-label">Suitable For</label>
        <select name="suitable_for" class="form-select">
          <option value="anyone" <?= ($h['suitable_for'] ?? '')==='anyone'?'selected':'' ?>>Anyone</option>
          <option value="students" <?= ($h['suitable_for'] ?? '')==='students'?'selected':'' ?>>Students only</option>
          <option value="non_students" <?= ($h['suitable_for'] ?? '')==='non_students'?'selected':'' ?>>Working / non-students only</option>
        </select></div>
      <div class="col-md-4"><label class="form-label">Location / Area</label>
        <input type="text" name="location" class="form-control" value="<?= $v('location') ?>" placeholder="e.g. Kahawa West, Nairobi" required></div>
      <div class="col-md-4"><label class="form-label">Nearest Landmark <span class="text-muted small">(campus, stage, mall…)</span></label>
        <input type="text" name="nearest_landmark" class="form-control" value="<?= $v('nearest_landmark') ?>"></div>
      <div class="col-md-4"><label class="form-label">Distance Notes</label>
        <input type="text" name="distance_notes" class="form-control" placeholder="e.g. 5 min walk to the stage" value="<?= $v('distance_notes') ?>"></div>
      <div class="col-12"><label class="form-label">Description</label>
        <textarea name="description" rows="4" class="form-control"><?= $v('description') ?></textarea></div>
      <div class="col-12"><label class="form-label">Amenities <span class="text-muted small">(comma separated)</span></label>
        <input type="text" name="amenities" class="form-control" placeholder="Wi-Fi, Water included, Security, Parking" value="<?= $v('amenities') ?>"></div>

      <div class="col-12">
        <label class="form-label fw-semibold"><i class="bi bi-geo-alt-fill text-danger"></i> Pin the live location of the house</label>
        <div class="row g-2 mb-2">
          <div class="col-md-6"><div class="input-group">
            <input type="text" id="searchPlace" class="form-control" placeholder="Search an area or building name…">
            <button type="button" id="searchPlaceBtn" class="btn btn-outline-brand"><i class="bi bi-search"></i></button></div></div>
          <div class="col-md-6 d-flex gap-2">
            <button type="button" id="useMyLocation" class="btn btn-brand"><i class="bi bi-crosshair"></i> Use my current location</button>
            <button type="button" id="clearPin" class="btn btn-outline-secondary">Clear</button>
          </div>
        </div>
        <div id="pickerMap" class="map-box big"></div>
        <input type="hidden" name="latitude" id="latitude" value="<?= $v('latitude') ?>">
        <input type="hidden" name="longitude" id="longitude" value="<?= $v('longitude') ?>">
        <div class="mt-2"><span id="pinStatus" class="small text-muted">No pin set yet.</span></div>
        <div class="pin-help">Standing at the house? Tap “Use my current location”. Otherwise search the area, then click the exact spot (you can drag the pin to adjust).</div>
      </div>
    </div>
    <?php
}

/** Script block that boots the pin picker; pass as $page_scripts. */
function pin_picker_script() {
    return '<script>initPinPicker({lat:' . (float)MAP_DEFAULT_LAT . ',lng:' . (float)MAP_DEFAULT_LNG . ',zoom:' . (int)MAP_DEFAULT_ZOOM . '});</script>';
}

/** Landlord (+ optional agent) pickers for pages where office staff/agents act for a landlord. */
function render_owner_selects($landlordId, $agentId, $showAgent) {
    $landlords = db()->query("SELECT id, full_name FROM users WHERE role='landlord' AND status='approved' ORDER BY full_name")->fetchAll();
    ?>
    <div class="row g-3 mb-3">
      <div class="col-md-5"><label class="form-label fw-semibold">Landlord (owner)</label>
        <select name="landlord_id" class="form-select" required><option value="">Select landlord…</option>
          <?php foreach ($landlords as $l): ?><option value="<?= $l['id'] ?>" <?= (int)$landlordId === (int)$l['id'] ? 'selected' : '' ?>><?= sanitize($l['full_name']) ?></option><?php endforeach; ?></select></div>
      <?php if ($showAgent):
        $agents = db()->query("SELECT id, full_name FROM users WHERE role='agent' AND status='approved' ORDER BY full_name")->fetchAll(); ?>
        <div class="col-md-5"><label class="form-label fw-semibold">Assigned agent <span class="text-muted small">(optional)</span></label>
          <select name="agent_id" class="form-select"><option value="">No agent</option>
            <?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>" <?= (int)$agentId === (int)$a['id'] ? 'selected' : '' ?>><?= sanitize($a['full_name']) ?></option><?php endforeach; ?></select></div>
      <?php endif; ?>
    </div>
    <?php
}
