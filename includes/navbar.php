<nav class="navbar navbar-expand-lg navbar-dark sticky-top brand-nav">
  <div class="container">
    <a class="navbar-brand" href="<?= base_url('index.php') ?>"><?php if (COMPANY_LOGO !== ''): ?><img src="<?= base_url(COMPANY_LOGO) ?>" alt="" class="nav-logo"><?php else: ?><i class="bi bi-houses-fill"></i><?php endif; ?> <?= sanitize(SITE_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('index.php') ?>">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('houses.php') ?>">Browse Houses</a></li>
        <?php if (!is_logged_in()): ?>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('auth/login.php') ?>">Login</a></li>
          <li class="nav-item ms-lg-2"><a class="btn btn-warm btn-sm" href="<?= base_url('auth/register.php') ?>">Create Account</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= base_url(dashboard_path()) ?>"><i class="bi bi-grid"></i> My Dashboard</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= base_url('auth/logout.php') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
