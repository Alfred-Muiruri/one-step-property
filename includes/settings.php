<?php
// Settings engine: defaults (config.php) overridden by the `settings` table, exposed as constants.

const OSP_SECRET_KEYS = ['SMTP_PASS', 'AT_API_KEY'];

function osp_key() {
    static $k = null;
    if ($k !== null) return $k;
    $f = __DIR__ . '/../storage/.secret_key';
    if (!is_file($f)) @file_put_contents($f, bin2hex(random_bytes(32)));
    $hex = is_file($f) ? trim((string)@file_get_contents($f)) : '';
    $k = ctype_xdigit($hex) && strlen($hex) === 64 ? hex2bin($hex) : hash('sha256', DB_PASS . DB_NAME, true);
    return $k;
}
function osp_encrypt($plain) {
    if ($plain === '' || !function_exists('openssl_encrypt')) return $plain;
    $iv = random_bytes(16);
    return 'enc:' . base64_encode($iv . openssl_encrypt($plain, 'aes-256-cbc', osp_key(), OPENSSL_RAW_DATA, $iv));
}
function osp_decrypt($v) {
    if (strpos((string)$v, 'enc:') !== 0) return (string)$v;
    $raw = base64_decode(substr($v, 4));
    $d = function_exists('openssl_decrypt') ? openssl_decrypt(substr($raw, 16), 'aes-256-cbc', osp_key(), OPENSSL_RAW_DATA, substr($raw, 0, 16)) : false;
    return $d === false ? '' : $d;
}

function osp_load_settings() {
    $defaults = osp_defaults();
    $stored = [];
    try {
        foreach (db()->query('SELECT skey, svalue FROM settings') as $r) $stored[$r['skey']] = (string)$r['svalue'];
    } catch (Throwable $e) { /* table not created yet: use defaults */ }

    $v = [];
    foreach ($defaults as $k => $d) {
        $raw = array_key_exists($k, $stored) ? $stored[$k] : $d;
        if (in_array($k, OSP_SECRET_KEYS, true) && is_string($raw)) $raw = osp_decrypt($raw);
        if (is_bool($d))       $v[$k] = in_array(strtolower((string)$raw), ['1', 'true', 'on', 'yes'], true);
        elseif (is_int($d))    $v[$k] = (int)$raw;
        elseif (is_float($d))  $v[$k] = (float)$raw;
        else                   $v[$k] = (string)$raw;
    }
    if ($v['SMTP_FROM_EMAIL'] === '') $v['SMTP_FROM_EMAIL'] = $v['SMTP_USER'] !== '' ? $v['SMTP_USER'] : 'no-reply@localhost';
    if ($v['SMTP_FROM_NAME'] === '')  $v['SMTP_FROM_NAME'] = $v['SITE_NAME'];

    $p = [];
    if ($v['PAY_TEXT'] !== '') $p[] = $v['PAY_TEXT'];
    if ($v['PAY_PAYBILL'] !== '') $p[] = 'M-Pesa Paybill ' . $v['PAY_PAYBILL'] . ($v['PAY_ACCOUNT_NOTE'] !== '' ? ', Account: ' . $v['PAY_ACCOUNT_NOTE'] : '') . '.';
    if ($v['PAY_TILL'] !== '') $p[] = 'M-Pesa Till ' . $v['PAY_TILL'] . '.';
    if ($v['PAY_BANK_ACCOUNT'] !== '') $p[] = 'Bank: ' . ($v['PAY_BANK_NAME'] !== '' ? $v['PAY_BANK_NAME'] . ', ' : '') . 'Account ' . $v['PAY_BANK_ACCOUNT'] . ($v['PAY_BANK_ACCOUNT_NAME'] !== '' ? ' (' . $v['PAY_BANK_ACCOUNT_NAME'] . ')' : '') . '.';
    $v['PAY_INSTRUCTIONS'] = implode(' ', $p);

    foreach ($v as $k => $val) { if (!defined($k)) define($k, $val); }
    $GLOBALS['OSP_SETTINGS'] = $v;
}

function osp_save_setting($key, $value) {
    if (in_array($key, OSP_SECRET_KEYS, true)) $value = osp_encrypt((string)$value);
    db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)')->execute([$key, (string)$value]);
}
