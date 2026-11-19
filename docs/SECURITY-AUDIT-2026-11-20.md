# Security Audit — www.bareeda-injera.com (letter verification feature + e-commerce site)

**Date:** 20 November 2026
**Auditor scope:** public-facing website (PHP e-commerce + letter verification), public API endpoints, TLS/hosting posture, and the verification data-replication pipeline.
**Method:** black-box, non-destructive testing (request-level probes only; no data dumped, no state modified). Access to the cPanel environment was used only to verify server-side controls that black-box testing cannot see (file permissions, blocked paths, logs).
**Verdict: PASS with recommendations.** No critical or high-severity vulnerability was found. Five findings follow, none exploitable remotely in a practical way, plus hardening recommendations.

---

## 1. Executive summary

The site is a PHP e-commerce application behind Cloudflare, with a newly
deployed letter-verification feature that verifies printed letters from a
**replicated local database** (no factory dependency). The audit covered:

- Transport and header security
- Sensitive file and directory exposure
- SQL injection and XSS probing on public inputs
- Authentication / IDOR on orders, payments, admin
- The letter-verification feature: public page, JSON endpoint, ingest API,
  token model, anti-phishing controls
- Session and cookie security
- Rate limiting and abuse resistance

| Severity | Count | Summary |
|---|---|---|
| Critical | 0 | — |
| High | 0 | — |
| Medium | 2 | Weak/guessable shared secrets; no rate limiting on ingest/verify |
| Low | 3 | Referrer-Policy override; no site-wide CSP/HSTS; dead QR proxy route |
| Info | 3 | Sensitive-looking files in home dir; unknown-token latency; logs hygiene |

---

## 2. Verified secure (evidence)

| Control | Test | Result |
|---|---|---|
| TLS | TLS 1.3, valid cert, HTTP/2 | ✅ |
| File exposure | Probed `.env`, `config/config.php`, `database/*.sql`, `.git/HEAD`, `storage/`, `logs/`, `bareeda-os/*` internals, `bin/create-admin.php`, `cron/telegram.php`, root `index.html`, `composer.json` | ✅ all **403/404** — the `.htaccess` deny rule `bareeda-os/(?!public/...) - [F,L]` works, and PHP files outside doc-root are unreachable |
| SQL injection | Error-based probes on `products?id`, `categories?id`, `search?q` (quotes, OR/1=1, time-based) | ✅ no DB error strings in any response; suspicious payloads are blocked at the WAF (403) |
| XSS | `<script>alert(1)</script>` reflection on search | ✅ no unescaped reflection (request blocked upstream) |
| Auth/IDOR | `/orders`, `/orders/1`, `/admin`, `/payment/...`, `/dashboard` unauthenticated | ✅ all redirect to the Login page; `order/confirmation/<fake>` shows a benign product page, no data |
| Session cookies | `PHPSESSID`: `Secure`, `HttpOnly`, `SameSite=Strict` | ✅ textbook |
| Registration CSRF | Register page carries CSRF token fields | ✅ |
| Verification: ingest auth | POST `/api/verify/ingest` with no secret → 403; wrong secret → 403; payload validated (token format + shape) | ✅ |
| Verification: token model | 128-bit random tokens; malformed and traversal-shaped tokens → 404 | ✅ enumeration is computationally infeasible |
| Verification: anti-phishing | Check code + Ed25519 seal rendered on the public page; confirmed **live** with a sealed test letter (`BIF/GM/2019/0009`) | ✅ |
| Verification: revocation & confidentiality | Revoke → public "Cancelled" in seconds; withheld flag → details hidden publicly (confirmed live E2E) | ✅ |
| Verification: tamper-evidence | v2 snapshots carry the issuance seal; consistent DB edits report `Altered` | ✅ (unit-tested + live) |
| Installer hygiene | `install-verify.php` ran once and self-deleted → now 404 | ✅ |

---

## 3. Findings

### MEDIUM-1 — Shared secrets live in `.htaccess` and are weak

**Evidence:** the document-root `.htaccess` carries `DB_PASS`, `PAYMENT_WEBHOOK_SECRET`, `CRON_SECRET_KEY` via `SetEnv`. Two of them are weak/pattern-based (`Bareedainjera2535#`, `bareeda-cron-2026-secure`). `.htaccess` is not web-readable (verified 403), but it is world-readable to every process and account on the server, and both values appeared in a working session during deployment.

**Risk:** if any other account/plugin on the shared host is compromised, the database and cron/webhook integrations fall with it. The cron secret being guessable makes `cron/telegram.php` brute-forceable (currently 404 from the web — the deny rule holds — but the value should not be guessable anyway).

**Remediation:**
1. Rotate the database password (cPanel → MySQL Databases) and update `.htaccess` + any local configs.
2. Replace `CRON_SECRET_KEY` with a 32-byte random value.
3. Rotate `PAYMENT_WEBHOOK_SECRET` with the payment provider.
4. Longer term: consider storing secrets in a file outside doc-root with `0600`, or cPanel's environment tools, rather than `.htaccess`.

### MEDIUM-2 — No rate limiting on public verify and ingest endpoints

**Evidence:** 20 rapid requests to the public verify endpoint all returned 200; the ingest endpoint accepted unlimited 403 attempts with the wrong secret.

**Risk:** token enumeration is infeasible (128-bit), so this is not a data risk — but (a) ingest-secret brute-forcing has no friction, (b) scanning abuse (persistence probing, load) is unlimited, and (c) the ERP-side rate limiter never sees traffic that the replicated store serves.

**Remediation:** add a lightweight PHP sliding-window limiter (file-based, no dependencies) on `VerifyController` routes and the ingest endpoint (e.g. 60/min per IP, 20/min on ingest failures), or a Cloudflare rate-limit rule for `/api/verify/ingest`. Priority: medium — cheap to implement.

### LOW-1 — Referrer-Policy override on the verify page

**Evidence:** the controller sends `Referrer-Policy: no-referrer`, but the live response carries `strict-origin-when-cross-origin` (set later by the framework/host).

**Risk:** minimal — the token is in the URL *path*; cross-origin referrers only leak the origin under this policy. Still, the intended header is not the one shipped.

**Remediation:** find the later setter (BaseController/PHP `ini`/CDN) or enforce at Cloudflare (Transform Rule) for `/verify/*` and `/api/verify/*`.

### LOW-2 — No site-wide CSP or HSTS

**Evidence:** root page sends `X-Frame-Options: SAMEORIGIN`, `nosniff`, `Referrer-Policy` — but no `Content-Security-Policy` and no `Strict-Transport-Security`.

**Risk:** standard hardening gap; the verify page itself does send a tight CSP (`default-src 'self'; frame-ancestors 'none'` — verified live).

**Remediation:** add HSTS in Cloudflare (one toggle) and a baseline CSP (even `default-src 'self'` + script/style allowances) via the `.htaccess`/Cloudflare headers.

### LOW-3 — QR proxy route on the website is dead

**Evidence:** `/verify/{token}/qr` proxies to the ERP's verify host, which is intentionally offline (factory is closed; the ERP is not public). Requests return 503.

**Risk:** none (customers scan the printed QR; the ERP generates QRs at print time locally). It is an inconsistency that could confuse.

**Remediation:** either render QRs in PHP (a small library) from the replicated `verify_url`, or remove the QR route from the site.

### INFO-1 — Sensitive-looking files in the home directory

`admin_bootstrap_result.log`, `schema_dump.txt` (19 KB), `deploy_log.txt`, `import_schema.php` sit in `/home/bareedzr/`. None are web-reachable (verified 404), but they are one misconfiguration away from exposure and may contain schema/credential traces.

**Remediation:** delete or move them into a non-web path with restricted permissions.

### INFO-2 — Unknown-token verification costs an ERP timeout

When a token is unknown, the site falls back to the offline ERP lookup, adding seconds before the "unavailable" page. Honest behaviour, but slow.

**Remediation (optional):** after the ERP publish pipeline has replicated every issued letter for a few weeks, retire the live fallback entirely — the store is then authoritative and unknown tokens return instantly.

### INFO-3 — Error logs contain stack traces with full paths

`bareeda-os/logs/php_errors.log` captured a full stack trace during testing. Not web-reachable (verified 403), but keep log rotation + permissions tight on shared hosting.

---

## 4. The verification feature — security posture summary

The design is defence-in-depth and each layer was confirmed live during this audit's E2E run:

| Layer | Status |
|---|---|
| 128-bit random tokens, format-validated at every hop | ✅ |
| Replicated snapshot store (site verifies with **no factory dependency**) | ✅ live |
| Ed25519 issuance seal (`bk-673de5`), published public keys | ✅ live |
| Printed check-code (anti QR-swap phishing) | ✅ live |
| Tamper-evidence (`Altered` state) incl. consistent DB edits | ✅ |
| Revocation propagation (~seconds) | ✅ live |
| Confidential-letter withholding (presentation-only, seal unaffected) | ✅ live |
| Scan-spike detection + Owner alert (in-app + Telegram) | ✅ live |
| Ingest endpoint: secret-authenticated, payload-validated, HTTPS-only | ✅ |

The weakest link in the whole chain is not cryptography — it is **operational secret hygiene** (MEDIUM-1) and **abuse friction** (MEDIUM-2), both cheap to fix.

---

## 5. Prioritized remediation plan

| # | Action | Effort | Priority |
|---|---|---|---|
| 1 | Rotate DB password + CRON secret + payment webhook secret; generate with `openssl rand -hex 32` | 15 min | **Do now** |
| 2 | Add PHP rate limiter to verify + ingest endpoints | 2 h | **This week** |
| 3 | Cloudflare: enable HSTS + always-use-HTTPS (one toggle) | 5 min | This week |
| 4 | Site-wide baseline CSP | 1 h | This month |
| 5 | Clean up home-dir logs/dumps | 10 min | This month |
| 6 | Fix/retire the QR proxy route | 1 h | Optional |
| 7 | Reconcile Referrer-Policy override | 30 min | Optional |

---

## 6. Test notes

- All testing was non-destructive: no records created or modified beyond the dedicated E2E test letter (`BIF/GM/2019/0009`, left **Cancelled** in the register as pipeline proof — safe to delete from Airtable; the public page will correctly remain "Cancelled").
- The ERP (factory) was confirmed **not reachable** from the internet at any point — all public verification served by the replicated store.
- The cPanel API token used for deployment has been superseded — revoke it in cPanel → Security → Manage API Tokens. The FTP account password should also be rotated (both appeared in a working chat session).
