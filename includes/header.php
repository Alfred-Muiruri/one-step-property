<?php
require_once __DIR__ . '/functions.php';
$page_title = $page_title ?? SITE_NAME;
$needs_map  = $needs_map ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= sanitize($page_title) ?> · <?= SITE_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<?php if ($needs_map): ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<?php endif; ?>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
<?php
$__sp = $_SERVER['SCRIPT_NAME'];
$use_sidebar = is_logged_in() && current_user() && (
    strpos($__sp, '/admin/') !== false || strpos($__sp, '/landlord/') !== false ||
    strpos($__sp, '/tenant/') !== false || strpos($__sp, '/staff/') !== false || strpos($__sp, '/agent/') !== false || basename($__sp) === 'change_password.php'
);
if ($use_sidebar) {
    include __DIR__ . '/sidebar.php';
} else { ?>
<div class="contact-strip d-none d-md-block">
  <div class="container d-flex justify-content-between">
    <span><i class="bi bi-telephone"></i> <a href="tel:<?= preg_replace('/\s+/', '', COMPANY_PHONE) ?>"><?= sanitize(COMPANY_PHONE) ?></a></span>
    <span><i class="bi bi-envelope"></i> <a href="mailto:<?= sanitize(COMPANY_EMAIL) ?>"><?= sanitize(COMPANY_EMAIL) ?></a></span>
  </div>
</div>
<?php include __DIR__ . '/navbar.php'; } ?>
<?php $flashes = get_flashes(); if ($flashes): ?>
  <div class="container mt-3">
    <?php foreach ($flashes as $f): ?>
      <div class="alert alert-<?= sanitize($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= sanitize($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
