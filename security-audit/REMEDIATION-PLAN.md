# Remediation Plan — Shopperzz Laravel Security Incident

**Mode:** Safe, phased remediation. Do **not** destroy evidence before Phase 2 backups.  
**Related:** `SECURITY-AUDIT-REPORT.md`, `IOC.json`

---

## Phase 1 — Containment (immediate, hosting)

1. Change all control-panel passwords (cPanel/DirectAdmin) and enable 2FA if available.
2. Rotate/disable unknown FTP and SSH accounts; keep one admin account you control.
3. Temporarily **disable outbound SMTP** or set a very low send limit while investigating (hosting mail settings).
4. At web server / WAF / `.htaccess`, block or throttle:
   - `/api/auth/forgot-password/otp-email`
   - `/api/auth/signup/otp-email`
   - `/api/auth/forgot-password/reset-password`
   - `/api/auth/forgot-password/` (POST)
5. Take the site to maintenance mode if account takeover risk is unacceptable until password-reset is patched.
6. Do **not** mass-delete files yet; quarantine unknown PHP by renaming after backup (Phase 2).
7. Compare live `public/index.php`, root `index.php`, `.htaccess`, `.user.ini` to the clean git copy.

---

## Phase 2 — Evidence Preservation

Before deleting or restoring anything:

1. Full archive of the **live** document root and home directory (outside web root if possible).
2. Export hosting email delivery logs for the last 7–30 days.
3. Export access/error logs covering the outage and high-mail period.
4. Screenshot / export Cron Jobs list.
5. List all FTP/SSH accounts and last login times.
6. Copy of live `.env` stored offline (encrypt); never commit it.
7. Keep `public.zip` offline copy if needed for comparison (SHA256 `6CE8BAAF…CAFF`), then remove from web-accessible paths.
8. Preserve `storage/logs` from production.

---

## Phase 3 — Malware Removal

**From this local audit:** no classic webshells were found to delete in the git tree.

On **production**, after backup:

1. Remove any PHP files under `public/` that are **not** in git (except intentional assets).
2. Remove `public/link_storage.php` from production once storage is linked.
3. Remove web-accessible archives (`public.zip`, `.sql`, `.bak`) from the document root.
4. Scan `storage/app/public` for `.php`, `.phtml`, `.phar`; quarantine unknowns.
5. If live indexes differ from git, restore from git / known-good backup — **after** diffing and saving the malicious copies as evidence.
6. Check for `.user.ini` / `auto_prepend_file` under the live docroot; remove unauthorized entries.

Do **not** blindly `rm -rf vendor` on production until Phase 4.

---

## Phase 4 — Dependency Restoration

1. On a **safe copy** (not destroying the forensic image):
   - `composer install --no-dev` (or project’s production flags) from `composer.lock`
   - `npm ci` / `npm install` then `npm run build` if frontend assets are built in deploy
2. Prefer replacing production `vendor/` with the clean install artifact after host cleanup.
3. Pin currently unpinned packages (`*`, `dev-master`) to known versions in a follow-up change.
4. Run `composer audit` and `npm audit`; remediate high/critical advisories.

---

## Phase 5 — Credential Rotation

Rotate (do not paste values into tickets/chat):

- Hosting panel password
- FTP / SSH passwords and keys
- Database password and any extra DB users
- SMTP / mail account passwords and API keys
- `APP_KEY` only if compromise of encrypted data is confirmed (plan downtime)
- Sanctum / API keys (`VITE_API_KEY` and server equivalents)
- Payment gateway secrets (Swich, JazzCash, EasyPaisa, Stripe, etc.)
- Twilio / Vonage / Firebase server keys
- Git / deploy tokens
- Admin user passwords (force reset for all administrators after fix)

Revoke all Sanctum personal access tokens after the password-reset vulnerability is fixed.

---

## Phase 6 — Hosting Security

Checklist:

- [ ] File Manager vs git tree diff
- [ ] FTP accounts / last logins
- [ ] SSH keys / authorized_keys
- [ ] Control-panel login history
- [ ] Cron jobs (remove unknown `curl`/`wget`/`php` mailers)
- [ ] Email accounts, forwarders, autoresponders
- [ ] SMTP relay / send limits / authentication logs
- [ ] PHP version and disabled functions
- [ ] `.user.ini` / `php.ini` / `auto_prepend_file` / `auto_append_file`
- [ ] ModSecurity / WAF hits
- [ ] Access + error logs
- [ ] Other domains/apps on same account
- [ ] Document root points at `public/` (preferred) or intentional root fallback
- [ ] Deny web access to `.env`, `.git`, `vendor`, `storage/logs`, archives

---

## Phase 7 — Email Abuse Prevention

1. Patch OTP routes with Laravel `throttle` (per IP and per email).
2. Require CAPTCHA on OTP send if abuse continues.
3. Fix password reset so mail OTP cannot be skipped.
4. Confirm `EventServiceProvider` registration or rely intentionally on discovery; ensure no duplicate sends.
5. Monitor SMTP daily volume after changes.
6. If volume continues with OTP routes blocked, treat as **hosting/SMTP credential abuse** and rotate SMTP immediately.
7. Disable unused mail features (subscriber blast) until admin RBAC is verified.

---

## Phase 8 — Verification

1. Diff production tree against clean git (exclude `storage`, `node_modules`, built assets as needed).
2. Confirm no unexpected PHP in `public/` or uploads.
3. Attempt password reset: OTP required; reset without OTP must fail.
4. Attempt OTP flood: throttled (429).
5. Customer Sanctum token must not access admin media upload (403).
6. Mail volume returns to baseline for 48–72 hours.
7. Site loads; `/api/*` hits Laravel (not bare Apache 404).
8. Re-run malware pattern scan and composer/npm audit.
9. Keep heightened log review for 7 days.

---

## Application code fixes (after you approve changes)

These were **not** applied during the forensic audit:

1. `ForgotPasswordController::resetPassword` — require verified OTP/token.
2. Throttle auth OTP routes in `routes/api.php`.
3. Add permission middleware to `MediaController` and any admin routes missing RBAC.
4. Delete or protect `public/link_storage.php`.
5. Harden `LanguageService::fileTextStore` path whitelist.
6. Stop returning raw `QueryException` messages in `bootstrap/app.php`.
7. Add `php_flag engine off` / Apache deny for `*.php` under `storage/app/public`.

Await explicit approval before modifying production or this repository for remediation.
