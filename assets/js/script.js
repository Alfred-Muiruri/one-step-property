document.addEventListener('DOMContentLoaded', function () {
  // Thumbnail gallery on house details
  document.querySelectorAll('.thumb-scroll img').forEach(function (thumb) {
    thumb.addEventListener('click', function () {
      var main = document.getElementById('mainHouseImage');
      if (main) main.src = thumb.dataset.full || thumb.src;
      document.querySelectorAll('.thumb-scroll img').forEach(function (t) { t.classList.remove('active'); });
      thumb.classList.add('active');
    });
  });

  // Confirm before destructive actions
  document.querySelectorAll('.confirm-action').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // Registration: relabel the organisation field by tenant type
  var typeRadios = document.querySelectorAll('input[name="tenant_type"]');
  var orgLabel = document.getElementById('orgLabel');
  function relabel() {
    var v = document.querySelector('input[name="tenant_type"]:checked');
    if (orgLabel && v) orgLabel.textContent = v.value === 'student' ? 'University / College' : 'Employer or occupation (optional)';
  }
  typeRadios.forEach(function (r) { r.addEventListener('change', relabel); });
  relabel();


  // Input rules: names = letters only, phone = 10 digits, National ID = 8 digits,
  // student ID (registration, students only) = letters + digits.
  function clean(el) {
    var v = el.value, k = el.dataset.v;
    if (k === 'idauto') {
      var st = document.querySelector('input[name="tenant_type"]:checked');
      k = (st && st.value === 'non_student') ? 'nid' : 'sid';
    }
    if (k === 'name')       v = v.replace(/[^A-Za-zÀ-ɏ .'\-]/g, '').replace(/^[ .'\-]+/, '');
    else if (k === 'phone') v = v.replace(/\D/g, '').slice(0, 10);
    else if (k === 'nid')   v = v.replace(/\D/g, '').slice(0, 8);
    else if (k === 'sid')   v = v.replace(/[^A-Za-z0-9\/\-]/g, '').slice(0, 30);
    if (v !== el.value) el.value = v;
  }
  var guarded = document.querySelectorAll('input[data-v]');
  guarded.forEach(function (el) {
    el.addEventListener('input', function () { clean(el); });
    el.addEventListener('blur',  function () { clean(el); });
    clean(el);
  });
  document.querySelectorAll('input[name="tenant_type"]').forEach(function (r) {
    r.addEventListener('change', function () { guarded.forEach(clean); relabelId(); });
  });
  function relabelId() {
    var id = document.querySelector('input[data-v="idauto"]');
    var st = document.querySelector('input[name="tenant_type"]:checked');
    if (id && st) { id.placeholder = st.value === 'non_student' ? '8-digit National ID' : 'Student ID (letters/digits)'; id.removeAttribute('pattern'); }
  }
  relabelId();
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      var msg = '';
      f.querySelectorAll('input[data-v]').forEach(function (el) {
        if (msg) return;
        var k = el.dataset.v, v = el.value.trim(), req = el.required;
        if (k === 'idauto') { var st = f.querySelector('input[name="tenant_type"]:checked'); k = (st && st.value === 'non_student') ? 'nid' : 'sid'; }
        if (!v && !req) return;
        if (k === 'name'  && !/^[A-Za-zÀ-ɏ]+([ .'\-]+[A-Za-zÀ-ɏ]+)*\.?$/.test(v)) msg = 'Name must contain letters only (no digits or symbols).';
        if (k === 'phone' && !/^0[17]\d{8}$/.test(v)) msg = 'Phone number must be exactly 10 digits, e.g. 0712345678.';
        if (k === 'nid'   && !/^\d{8}$/.test(v)) msg = 'National ID must be exactly 8 digits.';
        if (k === 'sid'   && !/^[A-Za-z0-9][A-Za-z0-9\/\-]{2,29}$/.test(v)) msg = 'Student ID may contain letters, digits, / or - only.';
        if (msg) { el.focus(); }
      });
      if (msg) { e.preventDefault(); alert(msg); }
    });
  });

  // OTP: numbers only, auto-submit at 6 digits
  var otp = document.getElementById('otpInput');
  if (otp) {
    otp.addEventListener('input', function () {
      otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
      if (otp.value.length === 6) otp.form.submit();
    });
  }

  // Resend countdown
  var cd = document.getElementById('resendCountdown');
  if (cd) {
    var left = parseInt(cd.dataset.seconds, 10) || 0;
    var btn = document.getElementById('resendBtn');
    function tick() {
      if (left > 0) { cd.textContent = ' (' + left + 's)'; btn.disabled = true; left--; setTimeout(tick, 1000); }
      else { cd.textContent = ''; btn.disabled = false; }
    }
    tick();
  }
});

/* ---------- Map helpers (Leaflet + OpenStreetMap, no API key needed) ---------- */
function osMap(el, lat, lng, zoom) {
  var map = L.map(el).setView([lat, lng], zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  return map;
}

// Pin picker used when adding / editing a house
function initPinPicker(cfg) {
  var latIn = document.getElementById('latitude'), lngIn = document.getElementById('longitude');
  var has = latIn.value !== '' && lngIn.value !== '';
  var map = osMap('pickerMap', has ? parseFloat(latIn.value) : cfg.lat, has ? parseFloat(lngIn.value) : cfg.lng, has ? 16 : cfg.zoom);
  var marker = null, status = document.getElementById('pinStatus');

  function setPin(lat, lng, pan) {
    lat = Math.round(lat * 1e7) / 1e7; lng = Math.round(lng * 1e7) / 1e7;
    latIn.value = lat; lngIn.value = lng;
    if (!marker) {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', function () { var p = marker.getLatLng(); setPin(p.lat, p.lng, false); });
    } else { marker.setLatLng([lat, lng]); }
    if (pan) map.setView([lat, lng], Math.max(map.getZoom(), 16));
    status.textContent = 'Pinned: ' + lat + ', ' + lng;
    status.className = 'text-success small fw-semibold';
  }
  if (has) setPin(parseFloat(latIn.value), parseFloat(lngIn.value), false);

  map.on('click', function (e) { setPin(e.latlng.lat, e.latlng.lng, false); });

  document.getElementById('useMyLocation').addEventListener('click', function () {
    if (!navigator.geolocation) { alert('Your browser does not support location.'); return; }
    status.textContent = 'Getting your location…'; status.className = 'small text-muted';
    navigator.geolocation.getCurrentPosition(
      function (pos) { setPin(pos.coords.latitude, pos.coords.longitude, true); },
      function () { status.textContent = 'Could not get your location. Allow location access, or click the map instead.'; status.className = 'small text-danger'; },
      { enableHighAccuracy: true, timeout: 15000 }
    );
  });

  document.getElementById('searchPlaceBtn').addEventListener('click', function () {
    var q = document.getElementById('searchPlace').value.trim();
    if (!q) return;
    status.textContent = 'Searching…'; status.className = 'small text-muted';
    fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ke&q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.length) { status.textContent = 'Place not found. Try a broader name, then fine-tune by clicking the map.'; status.className = 'small text-danger'; return; }
        map.setView([parseFloat(d[0].lat), parseFloat(d[0].lon)], 16);
        status.textContent = 'Found. Now click the exact spot of the house on the map.'; status.className = 'small text-muted';
      })
      .catch(function () { status.textContent = 'Search unavailable. Click the map to pin instead.'; status.className = 'small text-danger'; });
  });
  document.getElementById('searchPlace').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('searchPlaceBtn').click(); }
  });
  document.getElementById('clearPin').addEventListener('click', function () {
    latIn.value = ''; lngIn.value = ''; if (marker) { map.removeLayer(marker); marker = null; }
    status.textContent = 'No pin set yet.'; status.className = 'small text-muted';
  });
}

// Sidebar toggle (mobile)
document.addEventListener('DOMContentLoaded', function () {
  var sb = document.getElementById('appSidebar'), tg = document.getElementById('sbToggle'), ov = document.getElementById('sbOverlay');
  if (!sb || !tg) return;
  function set(open) { sb.classList.toggle('open', open); ov.classList.toggle('show', open); }
  tg.addEventListener('click', function () { set(!sb.classList.contains('open')); });
  ov.addEventListener('click', function () { set(false); });
});


/* ---------- Dashboard bar charts (Chart.js) ---------- */
function osBarChart(id, cfg) {
  var el = document.getElementById(id);
  if (!el || typeof Chart === 'undefined') return;
  var total = 0;
  cfg.datasets.forEach(function (d) { d.data.forEach(function (v) { total += v; }); d.borderRadius = 6; d.maxBarThickness = 46; });
  if (total === 0) {
    el.parentNode.innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-bar-chart fs-1 d-block mb-2"></i>No data yet — this chart fills in automatically as activity happens.</div>';
    return;
  }
  new Chart(el, {
    type: 'bar',
    data: { labels: cfg.labels, datasets: cfg.datasets },
    options: {
      indexAxis: cfg.horizontal ? 'y' : 'x',
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: cfg.datasets.length > 1, position: 'bottom' }, tooltip: { mode: 'index', intersect: false } },
      scales: {
        x: { stacked: cfg.stacked, grid: { display: false }, ticks: cfg.horizontal ? { precision: 0 } : {} },
        y: { stacked: cfg.stacked, beginAtZero: true, ticks: cfg.horizontal ? {} : { precision: 0 } }
      }
    }
  });
}
