<?php if (!empty($use_sidebar)): ?>
    </main>
  </div>
</div>
<?php else: ?>
<footer class="site-footer mt-5">
  <div class="container py-4">
    <div class="row gy-3">
      <div class="col-md-4">
        <h5 class="text-white"><i class="bi bi-houses-fill"></i> <?= sanitize(SITE_NAME) ?></h5>
        <p class="text-white-50 small mb-0">Professionally managed homes for students, working professionals and families — one step from finding a place to move in.</p>
        <ul class="list-unstyled small text-white-50 mt-2 mb-2">
          <?php if (COMPANY_ADDRESS !== ''): ?><li><i class="bi bi-geo-alt"></i> <?= sanitize(COMPANY_ADDRESS) ?></li><?php endif; ?>
          <?php if (COMPANY_HOURS !== ''): ?><li><i class="bi bi-clock"></i> <?= sanitize(COMPANY_HOURS) ?></li><?php endif; ?>
          <?php if (COMPANY_ALT_PHONE !== ''): ?><li><i class="bi bi-telephone"></i> <?= sanitize(COMPANY_ALT_PHONE) ?></li><?php endif; ?>
        </ul>
        <div class="d-flex gap-3 fs-5">
          <?php if ($wa = normalize_phone(COMPANY_WHATSAPP)): ?><a href="https://wa.me/<?= ltrim($wa, '+') ?>" target="_blank" rel="noopener" title="WhatsApp"><i class="bi bi-whatsapp"></i></a><?php endif; ?>
          <?php if (COMPANY_FACEBOOK !== ''): ?><a href="<?= sanitize(COMPANY_FACEBOOK) ?>" target="_blank" rel="noopener" title="Facebook"><i class="bi bi-facebook"></i></a><?php endif; ?>
          <?php if (COMPANY_INSTAGRAM !== ''): ?><a href="<?= sanitize(COMPANY_INSTAGRAM) ?>" target="_blank" rel="noopener" title="Instagram"><i class="bi bi-instagram"></i></a><?php endif; ?>
          <?php if (COMPANY_X !== ''): ?><a href="<?= sanitize(COMPANY_X) ?>" target="_blank" rel="noopener" title="X"><i class="bi bi-twitter-x"></i></a><?php endif; ?>
          <?php if (COMPANY_WEBSITE !== ''): ?><a href="<?= sanitize(COMPANY_WEBSITE) ?>" target="_blank" rel="noopener" title="Website"><i class="bi bi-globe"></i></a><?php endif; ?>
        </div>
      </div>
      <div class="col-md-4">
        <h6 class="text-white">Quick Links</h6>
        <ul class="list-unstyled small">
          <li><a href="<?= base_url('houses.php') ?>">Browse Houses</a></li>
          <li><a href="<?= base_url('auth/register.php') ?>">Create an Account</a></li>
          <li><a href="<?= base_url('auth/login.php') ?>">Login</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 class="text-white">For Property Owners</h6>
        <p class="text-white-50 small mb-1">Want us to manage your houses? Talk to us — once we sign a management agreement we set up your landlord account at no listing cost.</p>
        <p class="small mb-0"><a href="tel:<?= preg_replace('/\s+/', '', COMPANY_PHONE) ?>"><i class="bi bi-telephone"></i> <?= sanitize(COMPANY_PHONE) ?></a> · <a href="mailto:<?= sanitize(COMPANY_EMAIL) ?>"><?= sanitize(COMPANY_EMAIL) ?></a></p>
      </div>
    </div>
    <hr class="border-secondary">
    <p class="text-white-50 small mb-0 text-center">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
  </div>
</footer>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!empty($needs_map)): ?>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<script src="<?= base_url('assets/js/script.js') ?>"></script>
<?php if (!empty($page_scripts)) echo $page_scripts; ?>
</body>
</html>
