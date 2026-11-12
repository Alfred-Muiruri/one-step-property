# One Step Solution Property — House Management System

PHP + MySQL (PDO) + Bootstrap 5. Same look as CampusNest, rebuilt as a management system.

## Roles
| Role | How they get an account | What they do |
|---|---|---|
| **Admin** (One Step staff) | `database/seed_admin.php` (once) | Creates landlord accounts, approves houses, manages bookings/tenants, sends announcements |
| **Staff** (office receptionists) | Created by admin (*Team*) | Work **on behalf of landlords**: add/edit houses, mark availability, confirm/reject bookings, register walk-in clients and book for them. Cannot approve houses, create landlords or delete records. Every action is logged |
| **Agent** | Created by admin (*Team*) with a commission rate | List houses they source (admin approves), book for clients, track bookings and **commission** (paid out by admin) |
| **Landlord** | Created **by admin** after the management agreement (no posting fee) | Adds houses (pinned on a map) → admin approves → live. Sees booking requests |
| **Tenant** | Self-registers (student **or** non-student) | Browses, books, wishlists, reviews (after a confirmed booking) |

## Staff & agent notes
* **Upgrading an existing database?** Run `database/upgrade_staff_agents.sql` once in phpMyAdmin. Fresh installs just import `schema.sql`.
* Houses added or edited by staff need admin approval; set `STAFF_HOUSES_NEED_APPROVAL` to `false` in `includes/ops.php` to let office staff publish directly. Agent-added houses always need approval.
* Commission = agent's % of the house's monthly rent, created automatically when a booking credited to them is **confirmed** (they book it themselves, or the house is assigned to them). Admin marks it paid under *Commissions*.
* Staff and agents reuse some pages under `admin/` (houses, bookings, add house); the sidebar and permissions depend on the logged-in role.
* *Admin → Activity Log* records what staff/agents/admin do on landlords' behalf.

## Key features
* **OTP login for everyone**: password → 6-digit code emailed to the real email → in. Codes are hashed, expire in 10 min, 5 attempts, resend after 60 s.
* **Notifications by email / SMS / both** (user's choice at sign-up). If SMS is not configured or fails, email is used automatically. Everything is logged in *Admin → Messages*.
* **Live location pin**: OpenStreetMap + Leaflet (free, no API key). Landlords/admin click the map, search a place, or tap "Use my current location". Tenants see the pin, a results map on *Browse Houses*, and a "Get directions" button.
* Approval workflow: new or edited houses are `pending` until admin approves; rejection reasons are emailed to the landlord.
* Houses can be "students only", "non-students only" or "anyone".

## Settings & multiple admins
* **Admin → Settings** (super admin only): company name & logo, official email/phones/address/hours/social links, payment details (Paybill, Till, bank), email (SMTP) with a *Send test email* button, SMS (Africa's Talking) with a *Send test SMS* button, OTP & security rules, map defaults. SMTP password and SMS key are stored **encrypted** (key file `storage/.secret_key`, created automatically — keep it when moving the site, otherwise re-enter those two secrets).
* **Admin → Admins** (super admin only): add more admins; optionally make them super admins. Admins have full day-to-day access; only super admins manage admins and Settings. The first admin account is the super admin.
* After the first run you no longer need to edit `config/config.php` except the database login.
* Upgrading an existing database? Run `database/upgrade_settings_admins.sql` once (it also marks your oldest admin as super admin).

## Setup (XAMPP)
1. Copy this folder to `C:\xampp\htdocs\one-step-property`.
2. Start Apache + MySQL. In phpMyAdmin → **Import** → `database/schema.sql`.
3. Log in as the admin, open **Settings**, and fill in company details and **email (SMTP)** (fresh installs can also pre-set them in `config/config.php`):
   * Gmail: `SMTP_HOST 'smtp.gmail.com'`, `SMTP_PORT 587`, `SMTP_SECURE 'tls'`, `SMTP_USER` = your Gmail, `SMTP_PASS` = a Google **App Password** (Google Account → Security → 2-Step Verification → App passwords).
   * If XAMPP on Windows says SSL verification failed, set `SMTP_VERIFY_PEER` to `false` for local testing only.
   * Until SMTP is set, with `DEV_MODE true` emails are written to `storage/mail.log` (open it to read OTP codes while testing).
4. Visit `http://localhost/one-step-property/database/seed_admin.php`, create the admin with a **real email**, then **delete that file**.
5. Log in → *Landlords* → create a landlord → they get their login by email.

## SMS (optional, Africa's Talking)
Create an account at africastalking.com, put `AT_USERNAME` and `AT_API_KEY` in `config/config.php`. Use `sandbox` while testing.
OTP codes always go by email; SMS is used for notifications.

## Going live
* Set `DEV_MODE` to `false`, use HTTPS, keep `config/config.php` private.
* PHP extensions needed: `pdo_mysql`, `mbstring`, `fileinfo`, `curl`, `openssl` (all on in XAMPP by default).
* Free hosts (e.g. InfinityFree) often block outbound SMTP/SMS; use a host that allows it, or Gmail/Brevo SMTP on a paid plan.
