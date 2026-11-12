<?php
// Sidebar layout for logged-in portal pages (admin / landlord / tenant).
$role = current_role();
$me   = current_user();
$self = basename($_SERVER['SCRIPT_NAME']);

$count = function ($sql, $params = []) {
    $s = db()->prepare($sql); $s->execute($params); return (int)$s->fetch()['c'];
};

if ($role === 'admin') {
    $roleLabel = !empty($me['is_super']) ? 'Super Admin' : 'Administrator';
    $menu = [
        ['Dashboard',  'admin/dashboard.php',     'bi-speedometer2', 0],
        ['Landlords',  'admin/landlords.php',     'bi-person-badge', 0],
        ['Houses',     'admin/houses.php',        'bi-houses',       $count("SELECT COUNT(*) c FROM houses WHERE approval_status='pending'")],
        ['Add House',  'admin/add_house.php',     'bi-plus-circle',  0],
        ['Tenants',    'admin/tenants.php',       'bi-people',       0],
        ['Bookings',   'admin/bookings.php',      'bi-calendar-check', $count("SELECT COUNT(*) c FROM bookings WHERE status='pending'")],
        ['Team (Staff & Agents)', 'admin/team.php', 'bi-person-gear', 0],
        ['Commissions','admin/commissions.php',   'bi-cash-coin',    $count("SELECT COUNT(*) c FROM commissions WHERE status='pending'")],
        ['Messages',   'admin/notifications.php', 'bi-chat-dots',    0],
        ['Activity Log','admin/activity.php',     'bi-clipboard-data', 0],
    ];
} elseif ($role === 'staff') {
    $roleLabel = 'Office Staff';
    $menu = [
        ['Dashboard',   'staff/dashboard.php',  'bi-speedometer2', 0],
        ['Houses',      'admin/houses.php',     'bi-houses',       0],
        ['Add House',   'admin/add_house.php',  'bi-plus-circle',  0],
        ['Bookings',    'admin/bookings.php',   'bi-calendar-check', $count("SELECT COUNT(*) c FROM bookings WHERE status='pending'")],
        ['Register & Book Client', 'staff/walkin.php', 'bi-person-plus', 0],
        ['Tenants',     'admin/tenants.php',    'bi-people',       0],
        ['Landlords',   'admin/landlords.php',  'bi-person-badge', 0],
        ['Profile',     'staff/profile.php',    'bi-person-circle', 0],
    ];
} elseif ($role === 'agent') {
    $roleLabel = 'Agent';
    $menu = [
        ['Dashboard',   'agent/dashboard.php',   'bi-speedometer2', 0],
        ['My Houses',   'agent/my_houses.php',   'bi-houses',       0],
        ['Add House',   'admin/add_house.php',   'bi-plus-circle',  0],
        ['Book for Client', 'agent/book_for_client.php', 'bi-person-plus', 0],
        ['My Bookings', 'agent/bookings.php',    'bi-calendar-check', 0],
        ['Commissions', 'agent/commissions.php', 'bi-cash-coin',    $count("SELECT COUNT(*) c FROM commissions WHERE agent_id=? AND status='pending'", [$me['id']])],
        ['Profile',     'agent/profile.php',     'bi-person-circle', 0],
    ];
} elseif ($role === 'landlord') {
    $roleLabel = 'Landlord';
    $menu = [
        ['Dashboard',  'landlord/dashboard.php', 'bi-speedometer2', 0],
        ['My Houses',  'landlord/my_houses.php', 'bi-houses',       0],
        ['Add House',  'landlord/add_house.php', 'bi-plus-circle',  0],
        ['Bookings',   'landlord/bookings.php',  'bi-calendar-check', $count("SELECT COUNT(*) c FROM bookings b JOIN houses h ON h.id=b.house_id WHERE h.landlord_id=? AND b.status='pending'", [$me['id']])],
        ['Profile',    'landlord/profile.php',   'bi-person-circle', 0],
    ];
} else {
    $roleLabel = $me['tenant_type'] === 'student' ? 'Student Tenant' : 'Tenant';
    $menu = [
        ['Dashboard',  'tenant/dashboard.php',   'bi-speedometer2', 0],
        ['Browse Houses', 'houses.php',          'bi-search',       0],
        ['My Bookings','tenant/my_bookings.php', 'bi-calendar-check', 0],
        ['Wishlist',   'tenant/wishlist.php',    'bi-heart',        0],
        ['Profile',    'tenant/profile.php',     'bi-person-circle', 0],
    ];
}
if ($role === 'admin' && !empty($me['is_super'])) {
    $menu[] = ['Admins',   'admin/admins.php',   'bi-shield-lock', 0];
    $menu[] = ['Settings', 'admin/settings.php', 'bi-gear',        0];
}
$menu[] = ['Home page',       'index.php',                'bi-house-door', 0];
$menu[] = ['Change Password', 'auth/change_password.php', 'bi-key',        0];
?>
<div class="app-shell role-<?= sanitize($role) ?>">
  <div class="sb-overlay" id="sbOverlay"></div>
  <aside class="app-sidebar" id="appSidebar">
    <a class="sb-brand" href="<?= base_url($menu[0][1]) ?>"><?php if (COMPANY_LOGO !== ''): ?><img src="<?= base_url(COMPANY_LOGO) ?>" alt="" class="sb-logo"><?php else: ?><i class="bi bi-houses-fill"></i><?php endif; ?> <span><?= sanitize(SITE_NAME) ?></span></a>
    <nav class="sb-nav">
      <?php foreach ($menu as [$label, $path, $icon, $badge]):
            $active = ($self === basename($path) && strpos($_SERVER['SCRIPT_NAME'], dirname($path) === '.' ? '' : dirname($path)) !== false); ?>
        <a href="<?= base_url($path) ?>" class="sb-link <?= $active ? 'active' : '' ?>">
          <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
          <?php if ($badge > 0): ?><em class="sb-badge"><?= $badge ?></em><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="sb-user">
      <div class="sb-avatar"><?= sanitize(strtoupper(mb_substr($me['full_name'], 0, 1))) ?></div>
      <div class="sb-user-info">
        <strong><?= sanitize($me['full_name']) ?></strong>
        <small><?= $roleLabel ?></small>
      </div>
      <a href="<?= base_url('auth/logout.php') ?>" class="sb-logout" title="Log out"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </aside>
  <div class="app-body">
    <div class="app-topbar">
      <button class="btn btn-sm sb-toggle" id="sbToggle" type="button" aria-label="Menu"><i class="bi bi-list"></i></button>
      <span class="fw-semibold"><?= sanitize($page_title) ?></span>
      <span class="ms-auto small text-muted d-none d-sm-inline"><?= date('D, d M Y') ?></span>
    </div>
    <main class="app-main">
