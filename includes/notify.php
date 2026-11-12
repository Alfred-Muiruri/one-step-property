<?php
require_once __DIR__ . '/mailer.php';

function site_url_abs($path = '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_url($path);
}

function email_template($title, $bodyHtml, $buttonText = null, $buttonUrl = null) {
    $btn = $buttonUrl
        ? '<p style="margin:24px 0"><a href="' . htmlspecialchars($buttonUrl) . '" style="background:#ff8c42;color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:600;display:inline-block">' . htmlspecialchars($buttonText) . '</a></p>'
        : '';
    return '<!DOCTYPE html><html><body style="margin:0;background:#f6f9f8;font-family:Arial,Helvetica,sans-serif;color:#1c2b2b">'
         . '<div style="max-width:560px;margin:0 auto;padding:24px">'
         . '<div style="background:#0f3d3e;color:#fff;padding:18px 24px;border-radius:12px 12px 0 0;font-size:18px;font-weight:700">' . htmlspecialchars(SITE_NAME) . '</div>'
         . '<div style="background:#fff;padding:28px 24px;border-radius:0 0 12px 12px;box-shadow:0 4px 14px rgba(15,61,62,.08)">'
         . '<h2 style="margin-top:0;color:#0f3d3e">' . htmlspecialchars($title) . '</h2>'
         . '<div style="line-height:1.6;font-size:15px">' . $bodyHtml . '</div>' . $btn
         . '</div><p style="text-align:center;color:#8a9b9b;font-size:12px;margin-top:16px">'
         . htmlspecialchars(SITE_NAME) . ' · ' . htmlspecialchars(COMPANY_PHONE) . ' · ' . htmlspecialchars(COMPANY_EMAIL) . '</p>'
         . '</div></body></html>';
}

function log_notification($userId, $channel, $recipient, $subject, $message, $ok, $err = '') {
    try {
        db()->prepare('INSERT INTO notifications (user_id, channel, recipient, subject, message, status, error) VALUES (?,?,?,?,?,?,?)')
            ->execute([$userId, $channel, $recipient, mb_substr($subject, 0, 190), $message, $ok ? 'sent' : 'failed', $err ? mb_substr($err, 0, 250) : null]);
    } catch (Throwable $e) { error_log($e->getMessage()); }
}

/** Send an email. Returns [ok, error]. */
function send_email($to, $subject, $title, $bodyHtml, $buttonText = null, $buttonUrl = null) {
    return (new SmtpMailer())->send($to, $subject, email_template($title, $bodyHtml, $buttonText, $buttonUrl));
}

/** Send an SMS through Africa's Talking. Returns [ok, error]. */
function send_sms($phone, $message) {
    if (AT_API_KEY === '') return [false, 'SMS is not configured'];
    $to = normalize_phone($phone);
    if (!$to) return [false, 'Invalid phone number'];
    $url = AT_USERNAME === 'sandbox'
        ? 'https://api.sandbox.africastalking.com/version1/messaging'
        : 'https://api.africastalking.com/version1/messaging';
    $fields = ['username' => AT_USERNAME, 'to' => $to, 'message' => $message];
    if (AT_SENDER_ID !== '') $fields['from'] = AT_SENDER_ID;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($fields),
        CURLOPT_HTTPHEADER => ['apiKey: ' . AT_API_KEY, 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($resp === false) return [false, 'SMS gateway unreachable: ' . $err];
    $data = json_decode($resp, true);
    $status = $data['SMSMessageData']['Recipients'][0]['status'] ?? '';
    if ($http >= 200 && $http < 300 && $status === 'Success') return [true, ''];
    return [false, 'SMS failed: ' . ($status ?: "HTTP $http")];
}

/**
 * Notify one user on the channel(s) they chose (email / sms / both).
 * If they chose SMS but SMS fails or isn't set up, we fall back to email so nobody misses a message.
 * $user needs: id, email, phone, notify_pref, full_name.
 */
function notify_user(array $user, $subject, $message, $buttonText = null, $buttonPath = null) {
    $pref = $user['notify_pref'] ?? 'email';
    $url  = $buttonPath ? site_url_abs($buttonPath) : null;
    $html = '<p>Hello ' . htmlspecialchars($user['full_name']) . ',</p><p>' . nl2br(htmlspecialchars($message)) . '</p>';
    $smsOk = false;

    if ($pref === 'sms' || $pref === 'both') {
        $text = SITE_NAME . ': ' . $message;
        [$smsOk, $err] = send_sms($user['phone'], mb_substr($text, 0, 300));
        log_notification($user['id'] ?? null, 'sms', $user['phone'], $subject, $message, $smsOk, $err);
    }
    if ($pref === 'email' || $pref === 'both' || !$smsOk) {
        [$ok, $err] = send_email($user['email'], $subject, $subject, $html, $buttonText, $url);
        log_notification($user['id'] ?? null, 'email', $user['email'], $subject, $message, $ok, $err);
    }
}

function notify_admins($subject, $message, $buttonText = null, $buttonPath = null) {
    $admins = db()->query("SELECT * FROM users WHERE role='admin' AND status='approved'")->fetchAll();
    foreach ($admins as $a) { notify_user($a, $subject, $message, $buttonText, $buttonPath); }
}

/** OTP emails are never logged with their code. Returns [ok, error]. */
function send_otp_email(array $user, $code, $purposeLabel) {
    $body = '<p>Hello ' . htmlspecialchars($user['full_name']) . ',</p>'
          . '<p>Your one-time code to ' . htmlspecialchars($purposeLabel) . ' is:</p>'
          . '<p style="font-size:34px;letter-spacing:8px;font-weight:700;color:#146c68;margin:18px 0">' . $code . '</p>'
          . '<p>It expires in ' . OTP_TTL_MINUTES . ' minutes. If you did not try to do this, ignore this email and consider changing your password &mdash; <strong>never share this code with anyone</strong>, including our staff.</p>';
    $res = send_email($user['email'], "$code is your " . SITE_NAME . ' code', 'Your verification code', $body);
    log_notification($user['id'], 'email', $user['email'], 'Verification code (' . $purposeLabel . ')', '[code hidden]', $res[0], $res[1]);
    return $res;
}
