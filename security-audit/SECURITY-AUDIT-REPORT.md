# Laravel E-Commerce Security Forensic Audit Report

**Project:** shopperzz (ejeweller.pk / Shopperzz jewellery e-commerce)  
**Audit date:** 2026-10-01  
**Mode:** READ-ONLY forensic analysis (no files deleted, overwritten, or reinstalled)  
**Scope:** Local project tree under `shopperzz/`  
**Classification legend:** CRITICAL / HIGH / MEDIUM / LOW / FALSE POSITIVE

---

## 1. Executive Summary

Local source review found **no classic web shells, no obfuscated PHP malware, and no malicious injection in index / `.htaccess` files** in this repository copy.

There **is** strong evidence of **severe application vulnerabilities** that can explain account takeover and high outbound email volume **without** requiring a web shell:

| Finding | Severity |
|---------|----------|
| Password reset without OTP verification | **CRITICAL** |
| Unauthenticated OTP email endpoints (mail flood) | **HIGH** |
| Admin API (media upload, etc.) gated only by `auth:sanctum` (no permission middleware) | **HIGH** |
| Unauthenticated storage-link utility in `public/` | **MEDIUM** |
| Language file write via request-controlled path | **MEDIUM** |
| `QueryException` message leaked to clients | **MEDIUM** |
| Large `public.zip` archive in project root | **MEDIUM** (ops / exposure) |

**Hosting compromise cannot be ruled out.** This audit cannot inspect cPanel/FTP/cron/SMTP logs. A clean local tree does **not** prove the live server is clean.

**Compromise verdict for malware in this local Laravel tree:** **INCONCLUSIVE** (no malware found locally; severe vulns present; hosting-side abuse still plausible).

---

## 2. Project Information

| Item | Value |
|------|--------|
| Application | Laravel + Vue 3 (Vite) SPA e-commerce |
| Laravel (composer.lock) | **v12.56.0** |
| PHP requirement | `^8.2` |
| PHP CLI (audit host) | **8.5.7** |
| Auth | Laravel Sanctum |
| Permissions package | `spatie/laravel-permission` (present; **not applied as route middleware** in `routes/api.php`) |
| Media | `spatie/laravel-medialibrary` |
| Document root expected | `public/` (root `index.php` + `.htaccess` fallback if docroot = project root) |
| Frontend | Vue 3, Vite 6, Tailwind, Firebase client |
| Lockfiles | `composer.lock`, `package-lock.json` present |
| Git | Yes (`main` tracking `origin/main`) |
| Composer CLI on audit host | Not installed (`composer audit` unavailable) |
| npm audit | Not completed in this pass |

### Inventory (high level)

- `app/` — Controllers, Services, Mail, Listeners, Payment gateways, Analytics
- `bootstrap/app.php` — Laravel 12 bootstrap, middleware aliases, exception rendering
- `bootstrap/providers.php` — `AppServiceProvider`, `AnalyticsServiceProvider` only
- `config/`, `database/`, `routes/` (`api.php`, `web.php`, `analytics.php`, `console.php`)
- `public/` — `index.php`, `link_storage.php`, built assets
- `resources/` — Vue SPA + Blade mail views
- `storage/` — logs, framework cache/views, `app/public` uploads
- `vendor/`, `node_modules/` present
- Root: `.env`, `.env.example`, `.htaccess`, `index.php`, `public.zip`, `composer.*`, `package.*`

---

## 3. Incident Indicators (as reported)

| Indicator | Local evidence |
|-----------|----------------|
| Modified/replaced `index` files; site stopped working | **Not corroborated** in this copy: indexes match intentional Laravel / hosting-fallback patterns (git history for root `index.php`) |
| 100+ outbound emails/day | **Plausible via app:** unauthenticated OTP mail routes; also **plausible via hosting** SMTP/cron/malware outside repo |
| File-manager / hosting access by attacker | **Cannot verify** from local project |

---

## 4. Suspicious Files

| Path | Severity | Notes |
|------|----------|-------|
| `app/Http/Controllers/Auth/ForgotPasswordController.php` | **CRITICAL** | `resetPassword` sets password with no OTP/token check |
| `app/Services/OtpManagerService.php` | **HIGH** | OTP send with no application-level rate limit |
| `public/link_storage.php` | **MEDIUM** | Public symlink/rename utility; should not remain on production |
| `app/Services/LanguageService.php` | **MEDIUM** | Writes via `x_language_file_path` from request |
| `public.zip` (root) | **MEDIUM** | ~148.6 MB archive (SHA256 `6CE8BAAF…CAFF`); dated 2026-09-04; not malware by itself; risk if web-accessible |
| `storage/app/public/.htaccess` | **MEDIUM** | No PHP-execution deny rules for uploads |

No additional unknown PHP droppers found under `app/`, `public/`, `bootstrap/`, or project root beyond the items above.

---

## 5. Modified Index Files

### Application-relevant index files

| Path | Size / mtime (local FS) | Assessment |
|------|-------------------------|------------|
| `public/index.php` | Stock Laravel 12 front controller | **FALSE POSITIVE / LEGITIMATE** — no injected eval/redirect/iframe |
| `index.php` (project root) | Added 2026-05-26 | **FALSE POSITIVE / LEGITIMATE** — `require public/index.php` fallback; git commit `d9991b04` (“Add root index.php fallback when Apache cannot rewrite to public/”) |

### Other index hits (noise)

- `vendor/**` example/fixture `index.php` files — framework/SDK samples
- `node_modules/**/index.html` — frontend package assets

**Conclusion:** In this local tree, index files do **not** show malware injection. If production indexes differ, host File Manager / FTP must be compared to this clean copy.

---

## 6. Suspicious Dependencies

No package was labeled malicious solely for being unfamiliar. Notable risk posture:

| Package | Version constraint | Why present | Legitimate? | Suspicious code | Risk | Action |
|---------|-------------------|-------------|-------------|-----------------|------|--------|
| `kingflamez/laravelrave` | `dev-master` | Flutterwave payments | Known ecosystem package; **unstable pin** | Not scanned as malware | **MEDIUM** | Pin stable tag; verify publisher |
| `dipesh79/laravel-phonepe` | `*` | PhonePe | Known; **unpinned** | — | **MEDIUM** | Pin version |
| `nyawach/laravel-pesapal` | `*` | Pesapal | Known; **unpinned** | — | **MEDIUM** | Pin version |
| `obydul/laraskrill` | `*` | Skrill | Known; **unpinned** | — | **MEDIUM** | Pin version |
| `dipokhalder/settings`, `dipokhalder/laravel-env-editor` | ^1.0 | Theme/settings stack | Expected for this product | — | **LOW** | Keep; restrict env-editor on prod |
| Payment SDKs (Stripe, PayPal, Razorpay, etc.) | various | Multi-gateway theme | Legitimate for multi-payment product | — | **LOW** | Unused gateways can stay disabled in admin |
| npm `firebase` | ^10.5.0 | Push / client config | Legitimate | — | **LOW** | Ensure only public Firebase keys in frontend |

`composer audit` / full `npm audit` were **not runnable/completed** on this host (Composer binary missing). Re-run on a machine with Composer/npm after containment.

**No typosquat / clearly unrelated malware package identified** in `composer.json` / `package.json` names alone.

---

## 7. Web Shell / Backdoor Findings

| Check | Result |
|-------|--------|
| `eval(`, `base64_decode(`, `gzinflate(`, `shell_exec(`, `passthru(`, `assert(`, `create_function` in `app/`, `public/`, `bootstrap/` | **No matches** |
| Hidden PHP under unexpected upload dirs (local) | Only normal compiled Blade under `storage/framework/views/` |
| Backdoor routes (`/cmd`, `/shell`, etc.) | **Not found** as dedicated malware routes |

**CRITICAL application backdoor-equivalent behavior (not a classic webshell):**

- **File:** `app/Http/Controllers/Auth/ForgotPasswordController.php`  
- **Lines:** ~189–250 (`resetPassword`)  
- **Behavior:** Accepts email/phone + new password; updates hash; issues Sanctum token; **never checks** that OTP was verified  
- **Why suspicious:** Unauthenticated account takeover for any known email/phone  
- **Severity:** CRITICAL  
- **Confidence:** High  
- **Action:** Require verified OTP/token before password change; invalidate sessions; rotate compromised accounts

---

## 8. Obfuscated Code Findings

No long single-line obfuscated PHP payloads, `chr()` chains, nested `base64_decode(gzinflate(...))`, or hex-named malware stubs found in application PHP.

Vendor complexity is treated as **FALSE POSITIVE** unless integrity differs from `composer.lock` (full vendor hash verification not completed; recommend clean reinstall in a **copy** later).

---

## 9. `.htaccess` Findings

| File | Finding | Severity |
|------|---------|----------|
| `.htaccess` (root) | Rewrites non-file requests into `/public/` when `public/` exists | **LEGITIMATE** (shared-hosting pattern) |
| `public/.htaccess` | Standard Laravel rewrite + Authorization header pass-through | **LEGITIMATE** |
| `storage/app/public/.htaccess` | Cache-Control for video only; **does not** deny `.php` execution | **MEDIUM** |

No cloaking, User-Agent redirects, external domain `RewriteRule`s, or `auto_prepend_file` / `auto_append_file` in project `.htaccess` files.

---

## 10. `.user.ini` / PHP Configuration Findings

- No project-level `.user.ini` / `php.ini` outside vendor Laravel scaffolding stubs.
- Vendor Laravel `.user.ini` samples are **FALSE POSITIVE**.
- **Hosting-side** `.user.ini` under the live document root must still be checked manually.

---

## 11. Laravel Route Findings

### CRITICAL / HIGH auth routes (unauthenticated group)

Prefix: `/api/auth/...` with middleware `installed`, `apiKey`, `localization` (no `auth:sanctum`):

| Method | Route | Risk |
|--------|-------|------|
| POST | `auth/forgot-password/otp-email` | Mail send / flood |
| POST | `auth/forgot-password/` | Triggers OTP email when verification enabled |
| POST | `auth/forgot-password/reset-password` | **Password reset without OTP** |
| POST | `auth/signup/otp-email` | Mail send / flood |
| POST | `auth/login/otp-send` | Possible mail/SMS depending on login flow |

`apiKey` only requires a configured front-end API key (typically extractable from built JS) — **not** user authentication.

### Admin surface

- `Route::prefix('admin')->middleware(['auth:sanctum'])` — **no** `permission:` / role middleware in `routes/api.php`.
- Media library upload/delete available to any Sanctum-authenticated user if controller does not re-check roles (**HIGH**).

### Scheduler / console

- `routes/console.php` — only `inspire` demo command.
- No `app/Console/Kernel.php` (Laravel 11+ style).
- No suspicious scheduled mail/HTTP jobs in local console routes.

---

## 12. Upload Security

| Control | Observation | Severity |
|---------|-------------|----------|
| Media library auth | `auth:sanctum` only | **HIGH** |
| Extensions | Images including **SVG** stored on `public` disk | **MEDIUM** (XSS via SVG) |
| Storage location | `storage/app/public` → public URL via symlink | Expected; executable PHP not blocked by `.htaccess` | **MEDIUM** |
| `storeFromUrl` | Remote fetch into storage (SSRF / malicious content risk) | **MEDIUM** |
| `public/link_storage.php` | Anyone who can hit URL can manipulate `public/storage` | **MEDIUM** |

No confirmed webshell upload in local `storage/` PHP listing beyond compiled views.

---

## 13. Email Abuse Analysis

### Code paths that send mail

| Component | Trigger |
|-----------|---------|
| `SendEmailCodeNotification` / `SendVerifyEmailCodeNotification` | OTP events from forgot-password / signup |
| `OrderMail` / `OrderGotMail` listeners | Order create / status |
| `SubscriberMail` via `SubscriberService::sendEmail` | Admin subscriber blast |
| Analytics `Mail::raw` | Admin intelligence feature |

### OTP flood path (best app-level explanation for 100+/day)

1. Attacker calls `POST /api/auth/forgot-password/otp-email` or `signup/otp-email` with arbitrary emails (subject to validation).
2. `OtpManagerService::resetOtpEmail` / `otpEmail` inserts token and dispatches mail events (**no rate limit / throttle** in service).
3. Listeners send `Mail::to(...)->send(...)`.

**Note:** `app/Providers/EventServiceProvider.php` maps these listeners but is **not** listed in `bootstrap/providers.php`. Laravel may still deliver mail via event discovery; production behavior should be confirmed. If listeners are inactive, emails may be coming from **outside** this app (hosting malware / stolen SMTP).

### Other questions

| Question | Answer |
|----------|--------|
| Emails without auth? | **Yes** — OTP routes |
| Arbitrary recipients? | **Yes** — request `email` field (for OTP flows) |
| Attacker-controlled body? | OTP body is app-controlled; subscriber blast is admin-driven |
| Scheduled spam loop in app? | **Not found** locally |
| Hardcoded spam SMTP in source? | Not found (secrets live in `.env` — redacted) |

**Likely email-abuse source (application):** unauthenticated OTP endpoints + missing throttling.  
**Cannot exclude:** compromised SMTP credentials / hosting cron / PHP mailer outside this repo.

---

## 14. Cron / Scheduler Analysis

### LOCAL PROJECT FINDINGS

- No malicious Laravel schedule entries in `routes/console.php`.
- No Console Kernel with unexpected jobs.

### HOSTING-SIDE (must check manually)

- cPanel / DirectAdmin Cron Jobs
- `wget`/`curl` to PHP mailers
- Extra PHP files outside the git tree
- Queue workers / Supervisor configs sending mail

---

## 15. Authentication Findings

| Finding | Severity | Evidence |
|---------|----------|----------|
| Password reset without proving OTP | **CRITICAL** | `ForgotPasswordController::resetPassword` |
| OTP verify endpoints exist but are optional for reset | **CRITICAL** | `verify-email` not required before reset |
| Admin APIs without Spatie permission middleware | **HIGH** | `routes/api.php` admin group |
| Hardcoded passwords in source | Not found | — |

Do not print hashes or live credentials.

---

## 16. Environment / Secrets Exposure

| Check | Result |
|-------|--------|
| `.env` in repo working tree | Present locally (expected for deploy); **must not** be web-accessible |
| `public/.env` | Not found |
| Routes exposing `.env` | Not found in this audit pass |
| Report policy | All secrets **[REDACTED]** |

Ensure hosting denies HTTP access to `.env`, `.git`, `composer.json`, `storage/logs`, and root archives.

---

## 17. Backup Files

| Path | Notes | Severity |
|------|-------|----------|
| `public.zip` | ~155,787,943 bytes; SHA256 `6CE8BAAFEEF1A516F6B50B811A1E5EDD4F2B9A2EB5C58E17D80DD1B3CF41CAFF`; mtime 2026-09-04 | **MEDIUM** — remove from web root after offline backup |
| `database/locations/*.sql` | Seed geo data | **LOW** / expected |
| `index.php.bak` / `.old` | **Not found** locally | — |

---

## 18. Timeline (local metadata / git)

| When | Event |
|------|--------|
| 2026-04-20 | Base Laravel/theme files; `public/index.php`, `composer.lock` |
| 2026-05-15 | `public/link_storage.php` |
| 2026-05-26 | Root `index.php` + root `.htaccess` rewrite (intentional hosting fix) |
| 2026-06-07 | `.env` / `.env.example` touch |
| 2026-08-15 | Recent payment/order PHP edits (Swich / gateways) |
| 2026-09-04 | `public.zip` created; `public/` directory mtime |
| 2026-09-29+ | Git activity on Swich waiting UX (commits on `main`) |

No git evidence that indexes were replaced with malware in this repository.

---

## 19. Logs / Indicators of Compromise

Local `storage/logs` sampling was incomplete during the audit pass (large/slow I/O). **No confirmed IoC list from production access logs** is available in this report.

**Hosting must supply:** access logs for `/api/auth/forgot-password/*`, `/api/auth/signup/otp-email`, spikes of `POST` with many distinct emails, and SMTP send logs.

---

## 20. Dependency Audit

| Action | Status |
|--------|--------|
| Manual review of `composer.json` / `package.json` | Done |
| `composer audit` | **Unavailable** (Composer not on PATH) |
| `npm audit --omit=dev` | **Done** — 27 issues (4 low, 14 moderate, 7 high, 2 critical); notable: `websocket-driver` critical advisories via transitive deps; `@vueuse/head` / `unhead` chain |
| Vendor vs lockfile binary integrity | Not fully verified |
| `.env` / `public/.env` | `.env` present locally; **no** `public/.env` |

These npm findings are dependency CVEs, not evidence of installed malware. Run `composer audit` on a machine with Composer after containment.

---

## 21. False Positives

| Item | Why legitimate |
|------|----------------|
| Root `index.php` require of `public/index.php` | Documented shared-hosting fallback; git-authored |
| Laravel `public/index.php` / `public/.htaccess` | Framework defaults |
| `vendor/**` complex PHP | Framework/SDK code |
| `storage/framework/views/*.php` | Compiled Blade |
| Payment gateway sprawl | Theme multi-gateway design |
| `Mail::` usage in order/OTP flows | Expected e-commerce behavior |

---

## 22. Recommended Containment Steps

1. Rotate hosting panel, FTP, SSH, DB, SMTP, Sanctum, payment, and Firebase-related secrets (**[REDACTED]** individually).
2. Disable or throttle OTP mail endpoints at web-server / WAF until patched.
3. Force logout all users / revoke Sanctum tokens after password-reset fix.
4. Compare **live** `public/index.php`, root `index.php`, `.htaccess`, `.user.ini` to this repo.
5. Quarantine/remove web-accessible `public.zip` and any unknown PHP under `public/` / uploads.
6. Review hosting cron and email queue; stop unauthorized SMTP.

---

## 23. Recommended Remediation

1. Fix `resetPassword` to require verified OTP (and bind to that email/phone).
2. Add `throttle` middleware to all OTP send routes.
3. Apply `permission:` / role middleware to all `admin/*` routes.
4. Delete `public/link_storage.php` from production after linking storage once.
5. Harden Language file write paths (whitelist under `lang/` only).
6. Stop leaking `QueryException` messages to clients.
7. Deny PHP execution in `storage/app/public`.
8. Rebuild `vendor/` from lockfile in a clean copy after host cleanup.
9. Re-run composer/npm audits.

---

## 24. Hosting-Side Checks

Must be done in cPanel/DirectAdmin/File Manager (not verifiable from this repo):

- File Manager anomalies vs this git tree  
- FTP / SSH accounts and login history  
- Control-panel login history  
- Cron jobs  
- Email delivery logs / accounts / forwarders  
- SMTP auth failures and relay volume  
- PHP version / `.user.ini` / `auto_prepend_file`  
- ModSecurity / access / error logs  
- API tokens, Git deploy keys  
- Database users  
- Other domains/apps on same account (WordPress, old PHP sites)  
- Whether document root points at unexpected folders  

**Attacker may have compromised the hosting account itself**, not only Laravel code.

---

## 25. Final Evidence Summary

| Question | Result |
|----------|--------|
| Classic malware / webshell in local tree? | **Not found** |
| Malicious index injection in local tree? | **Not found** |
| App vulns that match incident symptoms? | **Yes** (reset + OTP flood) |
| Can local audit clear the server? | **No** |

Deliverables:

- `security-audit/SECURITY-AUDIT-REPORT.md` (this file)
- `security-audit/IOC.json`
- `security-audit/REMEDIATION-PLAN.md`
