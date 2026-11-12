<?php
// ================================================================
//  One Step Solution Property — base configuration
//  Only the DATABASE settings must live here.
//  Everything else (company info, email, SMS, payments, security…)
//  is managed in the app: Admin → Settings (super admin only).
//  The values below are just first-time defaults.
// ================================================================
date_default_timezone_set('Africa/Nairobi');

define('DB_HOST', 'localhost');
define('DB_NAME', 'onestep_property');
define('DB_USER', 'root');
define('DB_PASS', '');

function osp_defaults() {
    return [
        // Company
        'SITE_NAME' => 'One Step Solution Property',
        'COMPANY_LOGO' => '',
        'COMPANY_EMAIL' => 'info@onestepsolution.co.ke',
        'COMPANY_PHONE' => '+254 700 000 000',
        'COMPANY_ALT_PHONE' => '',
        'COMPANY_WHATSAPP' => '',
        'COMPANY_ADDRESS' => 'Kenya',
        'COMPANY_HOURS' => 'Mon–Fri 8:00am–5:00pm, Sat 9:00am–1:00pm',
        'COMPANY_WEBSITE' => '',
        'COMPANY_FACEBOOK' => '',
        'COMPANY_INSTAGRAM' => '',
        'COMPANY_X' => '',
        // Payments (shown to tenants after a booking is confirmed)
        'PAY_TEXT' => '',
        'PAY_PAYBILL' => '',
        'PAY_ACCOUNT_NOTE' => '',
        'PAY_TILL' => '',
        'PAY_BANK_NAME' => '',
        'PAY_BANK_ACCOUNT' => '',
        'PAY_BANK_ACCOUNT_NAME' => '',
        // Email (SMTP)
        'SMTP_HOST' => '',
        'SMTP_PORT' => 587,
        'SMTP_SECURE' => 'tls',
        'SMTP_USER' => '',
        'SMTP_PASS' => '',
        'SMTP_FROM_EMAIL' => '',
        'SMTP_FROM_NAME' => '',
        'SMTP_VERIFY_PEER' => true,
        // SMS (Africa's Talking)
        'AT_USERNAME' => 'sandbox',
        'AT_API_KEY' => '',
        'AT_SENDER_ID' => '',
        // Security & system
        'OTP_TTL_MINUTES' => 10,
        'OTP_MAX_ATTEMPTS' => 5,
        'OTP_RESEND_SECONDS' => 60,
        'SESSION_IDLE_SECONDS' => 7200,
        'DEV_MODE' => true,
        'STAFF_HOUSES_NEED_APPROVAL' => true,
        // Map
        'MAP_DEFAULT_LAT' => -1.2921,
        'MAP_DEFAULT_LNG' => 36.8219,
        'MAP_DEFAULT_ZOOM' => 12,
    ];
}
