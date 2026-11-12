<?php
require_once __DIR__ . '/../includes/functions.php';
require_super();
$cur = $GLOBALS['OSP_SETTINGS'];

$tabs = [
    'company'  => ['Company', 'bi-building'],
    'contact'  => ['Contact & Social', 'bi-telephone'],
    'payments' => ['Payments', 'bi-cash-coin'],
    'email'    => ['Email (SMTP)', 'bi-envelope-at'],
    'sms'      => ['SMS', 'bi-chat-text'],
    'system'   => ['Security & System', 'bi-shield-lock'],
];

// type: text|email|url|number|password|checkbox|select|textarea
$spec = [
    'company' => [
        'SITE_NAME' => ['label' => 'Company name', 'type' => 'text', 'required' => true, 'max' => 80, 'hint' => 'Shown in the website header, emails and SMS.'],
    ],
    'contact' => [
        'COMPANY_EMAIL'     => ['label' => 'Official company email', 'type' => 'email', 'required' => true, 'hint' => 'Shown to the public and used as the reply contact.'],
        'COMPANY_PHONE'     => ['label' => 'Official phone', 'type' => 'text', 'required' => true, 'max' => 30],
        'COMPANY_ALT_PHONE' => ['label' => 'Alternative phone', 'type' => 'text', 'max' => 30],
        'COMPANY_WHATSAPP'  => ['label' => 'WhatsApp number', 'type' => 'text', 'max' => 30, 'hint' => 'e.g. 0712345678 — adds a “WhatsApp us” button on house pages.'],
        'COMPANY_ADDRESS'   => ['label' => 'Office address', 'type' => 'text', 'max' => 190],
        'COMPANY_HOURS'     => ['label' => 'Office hours', 'type' => 'text', 'max' => 190],
        'COMPANY_WEBSITE'   => ['label' => 'Website', 'type' => 'url'],
        'COMPANY_FACEBOOK'  => ['label' => 'Facebook page link', 'type' => 'url'],
        'COMPANY_INSTAGRAM' => ['label' => 'Instagram link', 'type' => 'url'],
        'COMPANY_X'         => ['label' => 'X (Twitter) link', 'type' => 'url'],
    ],
    'payments' => [
        'PAY_PAYBILL'           => ['label' => 'M-Pesa Paybill number', 'type' => 'text', 'max' => 20],
        'PAY_ACCOUNT_NOTE'      => ['label' => 'Paybill account instruction', 'type' => 'text', 'max' => 100, 'hint' => 'e.g. “your full name” or “house code”.'],
        'PAY_TILL'              => ['label' => 'M-Pesa Till number', 'type' => 'text', 'max' => 20],
        'PAY_BANK_NAME'         => ['label' => 'Bank name', 'type' => 'text', 'max' => 80],
        'PAY_BANK_ACCOUNT'      => ['label' => 'Bank account number', 'type' => 'text', 'max' => 40],
        'PAY_BANK_ACCOUNT_NAME' => ['label' => 'Bank account name', 'type' => 'text', 'max' => 100],
        'PAY_TEXT'              => ['label' => 'Extra payment instructions', 'type' => 'textarea', 'max' => 400, 'hint' => 'Optional. Everything here is shown to tenants after their booking is confirmed.'],
    ],
    'email' => [
        'SMTP_HOST'        => ['label' => 'SMTP host', 'type' => 'text', 'max' => 120, 'hint' => 'Gmail: smtp.gmail.com'],
        'SMTP_PORT'        => ['label' => 'Port', 'type' => 'number', 'min' => 1, 'max' => 65535, 'hint' => '587 with TLS, 465 with SSL'],
        'SMTP_SECURE'      => ['label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL', '' => 'None']],
        'SMTP_USER'        => ['label' => 'SMTP username (the sending email)', 'type' => 'text', 'max' => 150],
        'SMTP_PASS'        => ['label' => 'SMTP password / App Password', 'type' => 'password', 'hint' => 'Stored encrypted. Leave blank to keep the saved one. For Gmail use a Google App Password.'],
        'SMTP_FROM_EMAIL'  => ['label' => 'Send-from email', 'type' => 'email', 'hint' => 'Leave blank to use the SMTP username.'],
        'SMTP_FROM_NAME'   => ['label' => 'Send-from name', 'type' => 'text', 'max' => 80, 'hint' => 'Leave blank to use the company name.'],
        'SMTP_VERIFY_PEER' => ['label' => 'Verify the mail server’s SSL certificate', 'type' => 'checkbox', 'hint' => 'Turn off only if a local XAMPP on Windows fails SSL verification.'],
    ],
    'sms' => [
        'AT_USERNAME'  => ['label' => 'Africa’s Talking username', 'type' => 'text', 'max' => 60, 'hint' => 'Use “sandbox” while testing.'],
        'AT_API_KEY'   => ['label' => 'API key', 'type' => 'password', 'hint' => 'Stored encrypted. Leave blank to keep the saved one. SMS stays off until a key is saved.'],
        'AT_SENDER_ID' => ['label' => 'Sender ID', 'type' => 'text', 'max' => 11, 'hint' => 'Optional, must be approved by Africa’s Talking.'],
    ],
    'system' => [
        'OTP_TTL_MINUTES'            => ['label' => 'OTP code lifetime (minutes)', 'type' => 'number', 'min' => 3, 'max' => 60],
        'OTP_MAX_ATTEMPTS'           => ['label' => 'Wrong OTP attempts allowed', 'type' => 'number', 'min' => 3, 'max' => 10],
        'OTP_RESEND_SECONDS'         => ['label' => 'Seconds before a code can be re-sent', 'type' => 'number', 'min' => 30, 'max' => 300],
        'SESSION_IDLE_SECONDS'       => ['label' => 'Auto-logout after inactivity (minutes)', 'type' => 'number', 'min' => 10, 'max' => 1440, 'scale' => 60],
        'STAFF_HOUSES_NEED_APPROVAL' => ['label' => 'Houses added/edited by office staff need admin approval', 'type' => 'checkbox'],
        'DEV_MODE'                   => ['label' => 'Developer mode (if email is not set up, write emails to a log file instead of failing)', 'type' => 'checkbox', 'hint' => 'Turn OFF on the live server.'],
        'MAP_DEFAULT_LAT'            => ['label' => 'Default map latitude', 'type' => 'number', 'min' => -90, 'max' => 90, 'step' => 'any', 'hint' => 'Where the location-pin map first opens.'],
        'MAP_DEFAULT_LNG'            => ['label' => 'Default map longitude', 'type' => 'number', 'min' => -180, 'max' => 180, 'step' => 'any'],
        'MAP_DEFAULT_ZOOM'           => ['label' => 'Default map zoom (1–18)', 'type' => 'number', 'min' => 1, 'max' => 18],
    ],
];

$tab = $_GET['tab'] ?? 'company';
if (!isset($tabs[$tab])) $tab = 'company';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    $tab = isset($tabs[$_POST['tab'] ?? '']) ? $_POST['tab'] : 'company';
    $me = current_user();

    if ($action === 'test_email') {
        [$ok, $err] = send_email($me['email'], 'Test email from ' . SITE_NAME, 'Email is working', '<p>This is a test message sent from <strong>Admin → Settings</strong>. If you can read it, your email settings are correct.</p>');
        if (SMTP_HOST === '') flash('warning', 'SMTP host is not set — the test email was only written to storage/mail.log (developer mode).');
        else flash($ok ? 'success' : 'danger', $ok ? 'Test email sent to ' . $me['email'] . '. Check your inbox (and spam).' : 'Test email failed: ' . $err);
        redirect('admin/settings.php?tab=email');
    }
    if ($action === 'test_sms') {
        [$ok, $err] = send_sms($me['phone'], SITE_NAME . ': this is a test SMS from Settings.');
        flash($ok ? 'success' : 'danger', $ok ? 'Test SMS sent to ' . $me['phone'] . '.' : 'Test SMS failed: ' . $err);
        redirect('admin/settings.php?tab=sms');
    }

    // ---- save a tab ----
    $toSave = [];
    foreach ($spec[$tab] as $key => $f) {
        $raw = $_POST[$key] ?? '';
        $raw = is_string($raw) ? trim($raw) : '';
        $label = $f['label'];
        switch ($f['type']) {
            case 'checkbox': $toSave[$key] = isset($_POST[$key]) ? '1' : '0'; break;
            case 'password': if ($raw !== '') $toSave[$key] = $raw; break;
            case 'select':   if (array_key_exists($raw, $f['options'])) $toSave[$key] = $raw; else $errors[] = "$label: invalid choice."; break;
            case 'email':
                if ($raw === '' && !empty($f['required'])) $errors[] = "$label is required.";
                elseif ($raw !== '' && !filter_var($raw, FILTER_VALIDATE_EMAIL)) $errors[] = "$label is not a valid email.";
                else $toSave[$key] = $raw; break;
            case 'url':
                if ($raw !== '' && !preg_match('#^https?://#i', $raw)) $errors[] = "$label must start with http:// or https://";
                else $toSave[$key] = mb_substr($raw, 0, 190); break;
            case 'number':
                if (!is_numeric($raw)) { $errors[] = "$label must be a number."; break; }
                $n = (float)$raw;
                if ($n < $f['min'] || $n > $f['max']) { $errors[] = "$label must be between {$f['min']} and {$f['max']}."; break; }
                $toSave[$key] = (string)($n * ($f['scale'] ?? 1)); break;
            default:
                if ($raw === '' && !empty($f['required'])) { $errors[] = "$label is required."; break; }
                $toSave[$key] = mb_substr($raw, 0, $f['max'] ?? 255);
        }
    }

    // company tab: logo
    if ($tab === 'company' && !$errors) {
        if (!empty($_POST['remove_logo']) && $cur['COMPANY_LOGO'] !== '') {
            @unlink(__DIR__ . '/../' . $cur['COMPANY_LOGO']);
            $toSave['COMPANY_LOGO'] = '';
        }
        if (!empty($_FILES['logo']['name'])) {
            $path = handle_image_upload('logo', 'branding');
            if ($path) {
                if ($cur['COMPANY_LOGO'] !== '') @unlink(__DIR__ . '/../' . $cur['COMPANY_LOGO']);
                $toSave['COMPANY_LOGO'] = $path;
            } else $errors[] = 'Logo upload failed. Use a JPG, PNG or WEBP under 5MB.';
        }
    }

    if (!$errors) {
        foreach ($toSave as $k => $v) osp_save_setting($k, $v);
        log_activity('Settings updated', $tabs[$tab][0]);
        flash('success', $tabs[$tab][0] . ' settings saved.');
        redirect('admin/settings.php?tab=' . $tab);
    }
    // on error fall through and redisplay the posted values
    foreach ($spec[$tab] as $key => $f) if ($f['type'] !== 'password' && isset($_POST[$key])) $cur[$key] = $f['type'] === 'checkbox' ? true : $_POST[$key];
}

$page_title = 'Settings';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
  <h3 class="section-title">Settings</h3>
  <ul class="nav nav-tabs mb-3">
    <?php foreach ($tabs as $k => [$label, $icon]): ?>
      <li class="nav-item"><a class="nav-link <?= $tab === $k ? 'active' : '' ?>" href="?tab=<?= $k ?>"><i class="bi <?= $icon ?>"></i> <?= $label ?></a></li>
    <?php endforeach; ?>
  </ul>

  <div class="sidebar-card">
    <?php foreach ($errors as $e): ?><div class="alert alert-danger py-2"><?= sanitize($e) ?></div><?php endforeach; ?>
    <form method="POST" enctype="multipart/form-data"><?= csrf_field() ?>
      <input type="hidden" name="tab" value="<?= $tab ?>"><input type="hidden" name="action" value="save">
      <div class="row g-3">
        <?php foreach ($spec[$tab] as $key => $f):
              $val = $cur[$key] ?? ''; $wide = in_array($f['type'], ['textarea'], true) || in_array($key, ['COMPANY_ADDRESS', 'COMPANY_HOURS'], true); ?>
          <?php if ($f['type'] === 'checkbox'): ?>
            <div class="col-12"><div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" name="<?= $key ?>" id="f_<?= $key ?>" <?= $val ? 'checked' : '' ?>>
              <label class="form-check-label" for="f_<?= $key ?>"><?= sanitize($f['label']) ?></label>
              <?php if (!empty($f['hint'])): ?><div class="form-text"><?= sanitize($f['hint']) ?></div><?php endif; ?></div></div>
          <?php else: ?>
            <div class="<?= $wide ? 'col-12' : 'col-md-6' ?>">
              <label class="form-label fw-semibold" for="f_<?= $key ?>"><?= sanitize($f['label']) ?><?= !empty($f['required']) ? ' <span class="text-danger">*</span>' : '' ?></label>
              <?php if ($f['type'] === 'textarea'): ?>
                <textarea class="form-control" rows="3" name="<?= $key ?>" id="f_<?= $key ?>"><?= sanitize($val) ?></textarea>
              <?php elseif ($f['type'] === 'select'): ?>
                <select class="form-select" name="<?= $key ?>" id="f_<?= $key ?>"><?php foreach ($f['options'] as $ov => $ol): ?><option value="<?= sanitize($ov) ?>" <?= (string)$val === (string)$ov ? 'selected' : '' ?>><?= sanitize($ol) ?></option><?php endforeach; ?></select>
              <?php elseif ($f['type'] === 'password'): ?>
                <input type="password" class="form-control" name="<?= $key ?>" id="f_<?= $key ?>" autocomplete="new-password" placeholder="<?= $val !== '' ? '•••••••• saved — leave blank to keep' : 'Not set' ?>">
              <?php elseif ($f['type'] === 'number'): ?>
                <input type="number" class="form-control" name="<?= $key ?>" id="f_<?= $key ?>" step="<?= $f['step'] ?? 1 ?>" min="<?= $f['min'] ?>" max="<?= $f['max'] ?>" value="<?= sanitize(isset($f['scale']) ? round($val / $f['scale']) : $val) ?>">
              <?php else: ?>
                <input type="<?= $f['type'] === 'text' ? 'text' : $f['type'] ?>" class="form-control" name="<?= $key ?>" id="f_<?= $key ?>" value="<?= sanitize($val) ?>">
              <?php endif; ?>
              <?php if (!empty($f['hint'])): ?><div class="form-text"><?= sanitize($f['hint']) ?></div><?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($tab === 'company'): ?>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Company logo</label>
            <?php if ($cur['COMPANY_LOGO'] !== ''): ?>
              <div class="mb-2 d-flex align-items-center gap-3"><img src="<?= base_url($cur['COMPANY_LOGO']) ?>" alt="logo" style="height:56px;background:#fff;border:1px solid #d6e4e2;border-radius:8px;padding:4px;">
                <div class="form-check"><input class="form-check-input" type="checkbox" name="remove_logo" id="rl" value="1"><label class="form-check-label" for="rl">Remove logo</label></div></div>
            <?php endif; ?>
            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg,image/webp">
            <div class="form-text">Square or wide PNG with a transparent background works best. Shown in the header and sidebar.</div>
          </div>
        <?php endif; ?>
      </div>
      <button class="btn btn-brand mt-4 px-4"><i class="bi bi-check2"></i> Save <?= sanitize($tabs[$tab][0]) ?> Settings</button>
    </form>

    <?php if ($tab === 'email'): ?>
      <hr>
      <form method="POST" class="d-flex flex-wrap align-items-center gap-3"><?= csrf_field() ?><input type="hidden" name="tab" value="email"><input type="hidden" name="action" value="test_email">
        <button class="btn btn-outline-brand"><i class="bi bi-send"></i> Send a test email to me</button>
        <span class="text-muted small">Save your settings first, then test. The message goes to <?= sanitize(current_user()['email']) ?>.</span></form>
    <?php elseif ($tab === 'sms'): ?>
      <hr>
      <form method="POST" class="d-flex flex-wrap align-items-center gap-3"><?= csrf_field() ?><input type="hidden" name="tab" value="sms"><input type="hidden" name="action" value="test_sms">
        <button class="btn btn-outline-brand"><i class="bi bi-send"></i> Send a test SMS to me</button>
        <span class="text-muted small">Goes to <?= sanitize(current_user()['phone']) ?>.</span></form>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
