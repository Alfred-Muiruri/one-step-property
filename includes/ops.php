<?php
// Shared operations used by admin, staff, agents and the public booking page.


function require_roles(array $roles) {
    require_login();
    if (!in_array(current_role(), $roles, true)) { redirect('index.php'); }
}
function is_admin() { return current_role() === 'admin'; }
function is_super() { $u = current_user(); return $u && $u['role'] === 'admin' && !empty($u['is_super']); }
function require_super() {
    require_roles(['admin']);
    if (!is_super()) { flash('danger', 'Only a super admin can open that page.'); redirect('admin/dashboard.php'); }
}

function role_label($role) {
    return ['admin' => 'Administrator', 'staff' => 'Office Staff', 'agent' => 'Agent', 'landlord' => 'Landlord', 'tenant' => 'Tenant'][$role] ?? $role;
}

function log_activity($action, $details = '') {
    try {
        db()->prepare('INSERT INTO activity_log (user_id, role, action, details) VALUES (?,?,?,?)')
            ->execute([$_SESSION['user_id'] ?? null, current_role(), mb_substr($action, 0, 80), mb_substr($details, 0, 255)]);
    } catch (Throwable $e) { error_log($e->getMessage()); }
}

function temp_password() {
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $p = '';
        for ($i = 0; $i < 10; $i++) $p .= $chars[random_int(0, strlen($chars) - 1)];
    } while (!valid_password($p));
    return $p;
}

/**
 * Create a staff / agent / landlord account and email the login details.
 * Returns [errors[], userId|null, emailOk, tempPasswordIfEmailFailed|null, emailError]
 */
function create_managed_user($role, array $d) {
    $errors = [];
    $name = trim($d['full_name'] ?? ''); $email = trim($d['email'] ?? '');
    $phone = normalize_phone($d['phone'] ?? '');
    $pref = in_array($d['notify_pref'] ?? '', ['email','sms','both'], true) ? $d['notify_pref'] : '';
    $idNo = trim($d['id_number'] ?? '');
    if (!valid_name($name)) $errors[] = 'Full name must contain letters only (no digits or symbols).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required (login codes are sent there).';
    if (!$phone) $errors[] = 'Phone number must be exactly 10 digits, e.g. 0712345678.';
    if ($idNo !== '' && !valid_national_id($idNo)) $errors[] = 'National ID must be exactly 8 digits.';
    if ($pref === '') $errors[] = 'Please choose how this person should be notified (Email, SMS or both).';
    if (($pref === 'sms' || $pref === 'both') && !$phone) $errors[] = 'A valid phone number is needed for SMS notifications.';
    if (!$errors) {
        $ex = db()->prepare('SELECT id FROM users WHERE email=?'); $ex->execute([$email]);
        if ($ex->fetch()) $errors[] = 'An account with that email already exists.';
        if (phone_in_use($phone)) $errors[] = PHONE_TAKEN_MSG;
        if ($idNo !== '' && id_in_use($idNo)) $errors[] = 'That ID number is already registered to another account.';
    }
    if ($errors) return [$errors, null, false, null, ''];

    $tmp = temp_password();
    db()->prepare("INSERT INTO users (role, full_name, email, phone, password, status, id_number, notify_pref, must_change_password) VALUES (?,?,?,?,?, 'approved',?,?,1)")
        ->execute([$role, $name, $email, $phone, password_hash($tmp, PASSWORD_DEFAULT), $idNo ?: null, $pref]);
    $id = (int)db()->lastInsertId();

    $intro = ['admin' => 'You have been given administrator access to ' . SITE_NAME . '. You can manage landlords, houses, tenants, bookings, staff, agents and messages.',
              'staff' => 'You have been added to the ' . SITE_NAME . ' office team. You can manage houses, bookings and clients on behalf of our landlords.',
              'agent' => 'You have been registered as a ' . SITE_NAME . ' agent. You can list houses you source, register clients, book on their behalf and track your commission.',
              'tenant' => 'An account has been created for you so you can follow your booking and receive updates. You can also browse and book more houses online.',
              'landlord' => 'Your landlord account is ready following our management agreement. You can add your houses at no cost — each house goes live after our team approves it.'][$role] ?? '';
    $body = '<p>Hello ' . htmlspecialchars($name) . ',</p><p>' . htmlspecialchars($intro) . '</p>'
          . '<p><strong>Email:</strong> ' . htmlspecialchars($email) . '<br><strong>Temporary password:</strong> <span style="font-family:monospace;font-size:16px">' . htmlspecialchars($tmp) . '</span></p>'
          . '<p>When you log in we also email you a 6-digit code, and you will be asked to choose your own password straight away.</p>';
    [$ok, $err] = send_email($email, 'Your ' . SITE_NAME . ' ' . role_label($role) . ' account', 'Welcome aboard', $body, 'Log in', site_url_abs('auth/login.php'));
    log_notification($id, 'email', $email, 'Account created (' . $role . ')', '[credentials hidden]', $ok, $err);
    if (in_array($pref, ['sms', 'both'], true)) {
        [$sok, $serr] = send_sms($phone, SITE_NAME . ': your ' . role_label($role) . ' account is ready. Check your email (' . $email . ') for login details.');
        log_notification($id, 'sms', $phone, 'Account created (' . $role . ')', 'Account ready notice', $sok, $serr);
    }
    return [[], $id, $ok, $ok ? null : $tmp, $err];
}

/**
 * Find a tenant by email, or register a new one (walk-in client). Credentials are emailed.
 * Returns [errors[], tenantRow|null, isNew, emailOk, tempPasswordIfEmailFailed|null]
 */
function find_or_create_client(array $d) {
    $email = trim($d['email'] ?? '');
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $s = db()->prepare('SELECT * FROM users WHERE email=?'); $s->execute([$email]);
        if ($row = $s->fetch()) {
            if ($row['role'] !== 'tenant') return [['That email belongs to a non-tenant account.'], null, false, true, null];
            if ($row['status'] !== 'approved') return [['That client\'s account is suspended.'], null, false, true, null];
            return [[], $row, false, true, null];
        }
    }
    $type = ($d['tenant_type'] ?? '') === 'non_student' ? 'non_student' : 'student';
    $org  = trim($d['organization'] ?? '');
    if ($type === 'student' && $org === '') return [['Enter the client\'s university or college.'], null, false, true, null];

    [$errors, $id, $ok, $tmp, $err] = create_managed_user('tenant', $d);
    if ($errors) return [$errors, null, false, true, null];
    db()->prepare('UPDATE users SET tenant_type=?, organization=? WHERE id=?')->execute([$type, $org ?: null, $id]);
    $s = db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$id]);
    return [[], $s->fetch(), true, $ok, $tmp];
}

/**
 * Create a booking request and notify everyone involved.
 * $source: 'online' | 'staff' | 'agent'. Returns [bool ok, string message, int bookingId].
 */
function create_booking($tenantId, $houseId, $moveIn, $message, $source = 'online', $createdBy = null, $agentId = null) {
    $pdo = db();
    $h = $pdo->prepare('SELECT * FROM houses WHERE id=?'); $h->execute([$houseId]); $house = $h->fetch();
    $t = $pdo->prepare('SELECT * FROM users WHERE id=?');  $t->execute([$tenantId]); $tenant = $t->fetch();
    if (!$house || !$tenant) return [false, 'House or tenant not found.', 0];
    if ($house['status'] !== 'available' || $house['approval_status'] !== 'approved') return [false, 'Sorry, this house is no longer available.', 0];
    if ($house['suitable_for'] === 'students' && $tenant['tenant_type'] !== 'student') return [false, 'This house is reserved for students.', 0];
    if ($house['suitable_for'] === 'non_students' && $tenant['tenant_type'] !== 'non_student') return [false, 'This house is reserved for non-student tenants.', 0];
    $d = DateTime::createFromFormat('Y-m-d', (string)$moveIn);
    if (!$d || $d->format('Y-m-d') !== $moveIn || $moveIn < date('Y-m-d')) return [false, 'Please choose a valid move-in date (today or later).', 0];
    if ($moveIn > date('Y-m-d', strtotime('+1 year'))) return [false, 'The move-in date cannot be more than one year from today.', 0];
    $dup = $pdo->prepare("SELECT id FROM bookings WHERE tenant_id=? AND house_id=? AND status IN ('pending','confirmed')");
    $dup->execute([$tenantId, $houseId]);
    if ($dup->fetch()) return [false, 'This client already has an active booking request for that house.', 0];

    if (!$agentId) $agentId = $house['agent_id'] ?: null;   // the house's assigned agent earns credit on online bookings
    $pdo->prepare('INSERT INTO bookings (tenant_id, house_id, move_in_date, message, source, created_by, agent_id) VALUES (?,?,?,?,?,?,?)')
        ->execute([$tenantId, $houseId, $moveIn, mb_substr((string)$message, 0, 250) ?: null, $source, $createdBy, $agentId]);
    $bid = (int)$pdo->lastInsertId();

    $when = date('d M Y', strtotime($moveIn));
    notify_user($tenant, 'Booking request received: ' . $house['title'],
        'We received a booking request for "' . $house['title'] . '" with move-in on ' . $when . '. We will confirm shortly.', 'My bookings', 'tenant/my_bookings.php');
    $l = $pdo->prepare('SELECT * FROM users WHERE id=?'); $l->execute([$house['landlord_id']]);
    if ($landlord = $l->fetch()) {
        $by = $source === 'online' ? $tenant['full_name'] . ' (online)' : $tenant['full_name'] . ' (booked by One Step ' . $source . ')';
        notify_user($landlord, 'New booking request: ' . $house['title'], $by . ' has requested "' . $house['title'] . '" from ' . $when . '.', 'Review booking', 'landlord/bookings.php');
    }
    notify_admins('New booking request: ' . $house['title'], $tenant['full_name'] . ' requested "' . $house['title'] . '" for ' . $when . ' (' . $source . ').', 'Open bookings', 'admin/bookings.php');
    if ($source !== 'online') log_activity('Booking created', $tenant['full_name'] . ' → ' . $house['title'] . ' (#' . $bid . ')');
    return [true, 'Booking request sent.', $bid];
}
