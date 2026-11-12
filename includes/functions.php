<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Base URL auto-detect (works in any folder under htdocs, or at a domain root)
$__root    = str_replace('\\', '/', dirname(__DIR__));
$__docroot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));
define('BASE_URL', str_replace($__docroot, '', $__root));

$pdo = db();
require_once __DIR__ . '/settings.php';
osp_load_settings();

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Idle timeout
if (isset($_SESSION['user_id'])) {
    if (time() - ($_SESSION['last_active'] ?? time()) > SESSION_IDLE_SECONDS) {
        $csrf = $_SESSION['csrf'];
        session_unset();
        $_SESSION['csrf'] = $csrf;
        $_SESSION['flash'][] = ['type' => 'warning', 'message' => 'You were logged out because of inactivity. Please log in again.'];
    } else {
        $_SESSION['last_active'] = time();
    }
}

// ---- small helpers ------------------------------------------------
function base_url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }

function redirect($path) { header('Location: ' . base_url($path)); exit; }

function sanitize($str) { return htmlspecialchars(trim((string)($str ?? '')), ENT_QUOTES, 'UTF-8'); }

function csrf_field() { return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">'; }

function flash($type, $message) { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }

function get_flashes() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function is_logged_in() { return isset($_SESSION['user_id']); }
function current_role() { return $_SESSION['role'] ?? null; }

function current_user() {
    static $u = null;
    if ($u === null && is_logged_in()) {
        $s = db()->prepare('SELECT * FROM users WHERE id=?');
        $s->execute([$_SESSION['user_id']]);
        $u = $s->fetch() ?: false;
    }
    return $u;
}

function require_login() {
    if (!is_logged_in()) { redirect('auth/login.php'); }
    $u = current_user();
    if (!$u || $u['status'] !== 'approved') {
        session_unset();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        flash('danger', 'Your account is not active. Please contact One Step Solution Property.');
        redirect('auth/login.php');
    }
    $self = basename($_SERVER['SCRIPT_NAME']);
    if ($u['must_change_password'] && $self !== 'change_password.php' && $self !== 'logout.php') {
        flash('warning', 'For your security, please set a new password before continuing.');
        redirect('auth/change_password.php');
    }
}

function require_role($role) {
    require_login();
    if (current_role() !== $role) { redirect('index.php'); }
}

function dashboard_path() {
    switch (current_role()) {
        case 'admin':    return 'admin/dashboard.php';
        case 'staff':    return 'staff/dashboard.php';
        case 'agent':    return 'agent/dashboard.php';
        case 'landlord': return 'landlord/dashboard.php';
        case 'tenant':   return 'tenant/dashboard.php';
    }
    return 'index.php';
}

// CSRF check for every POST (scripts may define SKIP_CSRF before including this file)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !defined('SKIP_CSRF')) {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        die('Your session expired or the form was invalid. Go back, refresh the page and try again.');
    }
}

// ---- labels -------------------------------------------------------
function room_types() {
    return [
        'bedsitter' => 'Bedsitter', 'single' => 'Single Room', 'studio' => 'Studio',
        'one_bedroom' => 'One Bedroom', 'two_bedroom' => 'Two Bedroom', 'three_bedroom' => 'Three Bedroom',
        'maisonette' => 'Maisonette', 'bungalow' => 'Bungalow', 'hostel' => 'Hostel Room',
    ];
}
function room_type_label($t) { return room_types()[$t] ?? $t; }

function suitable_label($s) {
    return ['anyone' => 'Anyone', 'students' => 'Students', 'non_students' => 'Working / non-students'][$s] ?? $s;
}

function star_rating($rating) {
    $rating = (int)round($rating);
    return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
}

function badge_class($status) {
    $map = ['confirmed' => 'approved', 'approved' => 'approved', 'sent' => 'approved', 'available' => 'status-available',
            'booked' => 'status-booked', 'pending' => 'pending', 'rejected' => 'rejected', 'cancelled' => 'rejected',
            'failed' => 'rejected', 'suspended' => 'rejected'];
    return 'badge-' . ($map[$status] ?? 'pending');
}

// ---- phone / validation ------------------------------------------
function normalize_phone($p) {
    // Exactly 10 digits, nothing else (e.g. 0712345678). Stored internationally as +254712345678.
    $p = trim((string)$p);
    if (preg_match('/^0[17]\d{8}$/', $p)) return '+254' . substr($p, 1);
    return null;
}

/** True if another account already uses this (normalised) phone number. */
function phone_in_use($normPhone, $excludeUserId = 0) {
    $s = db()->prepare('SELECT id FROM users WHERE phone = ? AND id <> ? LIMIT 1');
    $s->execute([$normPhone, (int)$excludeUserId]);
    return (bool)$s->fetch();
}
const PHONE_TAKEN_MSG = 'That phone number is already in use by another account. Please use a different number.';

/** True if another account already uses this ID number (national or student ID). */
function id_in_use($idNo, $excludeUserId = 0) {
    $s = db()->prepare('SELECT id FROM users WHERE id_number = ? AND id <> ? LIMIT 1');
    $s->execute([$idNo, (int)$excludeUserId]);
    return (bool)$s->fetch();
}

/** True if this management agreement reference is already assigned to a landlord. */
function agreement_ref_in_use($ref) {
    $s = db()->prepare('SELECT id FROM landlord_profiles WHERE UPPER(agreement_ref) = UPPER(?) LIMIT 1');
    $s->execute([$ref]);
    return (bool)$s->fetch();
}

/** Agreement reference: letters, digits, / - _ ; 5-40 characters, must contain at least one digit. */
function valid_agreement_ref($r) {
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9\/\-_]{4,39}$/', (string)$r) === 1 && preg_match('/\d/', (string)$r) === 1;
}

/** University / employer / occupation: must contain letters; letters, digits, spaces and & . , ' ( ) - only. */
function valid_org($o) {
    $o = trim((string)$o);
    return mb_strlen($o) >= 2 && mb_strlen($o) <= 100 && preg_match('/\p{L}{2}/u', $o) === 1 && preg_match("/^[\p{L}\d &.,'()\-]+$/u", $o) === 1;
}

/** Names: letters only (plus spaces, hyphen, apostrophe, full stop). No digits. */
function valid_name($n) {
    $n = trim((string)$n);
    return mb_strlen($n) >= 2 && mb_strlen($n) <= 100 && preg_match("/^\p{L}+(?:[ .'\-]+\p{L}+)*\.?$/u", $n) === 1;
}

/** National ID: exactly 8 digits. */
function valid_national_id($id) { return preg_match('/^\d{8}$/', (string)$id) === 1; }

/** School / student ID: letters, digits and the separators / - (e.g. SC211/1234/2022). */
function valid_school_id($id) { return preg_match('/^[A-Za-z0-9][A-Za-z0-9\/\-]{2,29}$/', (string)$id) === 1; }

function valid_password($p) {
    return strlen($p) >= 8 && preg_match('/[A-Za-z]/', $p) && preg_match('/\d/', $p);
}

// ---- uploads ------------------------------------------------------
function handle_image_upload($fileInputName, $subfolder) {
    if (empty($_FILES[$fileInputName]['name']) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $file = $_FILES[$fileInputName];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) return null;

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) return null;

    $destDir = __DIR__ . '/../assets/uploads/' . $subfolder;
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $filename = $subfolder . '_' . bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) return null;
    return 'assets/uploads/' . $subfolder . '/' . $filename;
}

/** Save every file in a multi-file input (name="photos[]") for a house. */
function save_house_photos($houseId, $field = 'photos') {
    if (empty($_FILES[$field]['name'][0])) return 0;
    $n = 0;
    foreach ($_FILES[$field]['name'] as $i => $name) {
        if ($name === '') continue;
        $_FILES['__single'] = [
            'name' => $_FILES[$field]['name'][$i], 'type' => $_FILES[$field]['type'][$i],
            'tmp_name' => $_FILES[$field]['tmp_name'][$i], 'error' => $_FILES[$field]['error'][$i],
            'size' => $_FILES[$field]['size'][$i],
        ];
        $path = handle_image_upload('__single', 'houses');
        if ($path) {
            db()->prepare('INSERT INTO house_images (house_id, image_path) VALUES (?,?)')->execute([$houseId, $path]);
            $n++;
        }
    }
    return $n;
}


/** True if any house still references this image file. */
function image_in_use($path) {
    $s = db()->prepare('SELECT 1 FROM house_images WHERE image_path = ? LIMIT 1'); $s->execute([$path]);
    return (bool)$s->fetch();
}

// ---- OTP ------------------------------------------------------------
function issue_otp($userId, $purpose) {
    $code = (string)random_int(100000, 999999);
    db()->prepare('UPDATE login_otps SET used=1 WHERE user_id=? AND purpose=? AND used=0')->execute([$userId, $purpose]);
    db()->prepare('INSERT INTO login_otps (user_id, purpose, code_hash, sent_at, expires_at, ip) VALUES (?,?,?,?,?,?)')
        ->execute([
            $userId, $purpose, password_hash($code, PASSWORD_DEFAULT), date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', time() + OTP_TTL_MINUTES * 60), $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    return $code;
}

/** Returns 'ok' | 'invalid' | 'expired' | 'locked' */
function check_otp($userId, $purpose, $code) {
    $s = db()->prepare('SELECT * FROM login_otps WHERE user_id=? AND purpose=? AND used=0 ORDER BY id DESC LIMIT 1');
    $s->execute([$userId, $purpose]);
    $row = $s->fetch();
    if (!$row) return 'expired';
    if (strtotime($row['expires_at']) < time()) return 'expired';
    if ($row['attempts'] >= OTP_MAX_ATTEMPTS) return 'locked';
    db()->prepare('UPDATE login_otps SET attempts = attempts + 1 WHERE id=?')->execute([$row['id']]);
    if (!password_verify(trim($code), $row['code_hash'])) {
        return ($row['attempts'] + 1 >= OTP_MAX_ATTEMPTS) ? 'locked' : 'invalid';
    }
    db()->prepare('UPDATE login_otps SET used=1 WHERE id=?')->execute([$row['id']]);
    return 'ok';
}

function seconds_until_resend($userId, $purpose) {
    $s = db()->prepare('SELECT sent_at FROM login_otps WHERE user_id=? AND purpose=? ORDER BY id DESC LIMIT 1');
    $s->execute([$userId, $purpose]);
    $r = $s->fetch();
    if (!$r) return 0;
    return max(0, OTP_RESEND_SECONDS - (time() - strtotime($r['sent_at'])));
}

function mask_email($e) {
    [$n, $d] = explode('@', $e) + [1 => ''];
    return substr($n, 0, 2) . str_repeat('*', max(2, strlen($n) - 2)) . '@' . $d;
}

require_once __DIR__ . '/notify.php';
require_once __DIR__ . '/ops.php';
require_once __DIR__ . '/bookings.php';
