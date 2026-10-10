# Royal Hotel — Security Remediation Runbook

> Implementation handoff for agents fixing the Codex Security scan completed on 2026-09-27.

## 1. Purpose and evidence

This document is the executable security-fix plan for Royal Hotel. It covers all 13 validated findings from Codex Security scan `25f1425d-dbed-4600-99f5-23ec34e31dee` at revision `a394592a5033754a9d37c21e56f139d563bd7f3f`.

The scan found:

| Severity | Count |
|---|---:|
| High | 4 |
| Medium | 6 |
| Low | 3 |
| Total | 13 |

Canonical evidence is read-only:

- Scan report: `C:/Users/thinh/.codex/state/plugins/codex-security/scans/Nhom10/a394592a5033754a9d37c21e56f139d563bd7f3f_20260927T044640Z_c15ciazf/report.md`
- Findings: `C:/Users/thinh/.codex/state/plugins/codex-security/scans/Nhom10/a394592a5033754a9d37c21e56f139d563bd7f3f_20260927T044640Z_c15ciazf/findings.json`
- Coverage: `C:/Users/thinh/.codex/state/plugins/codex-security/scans/Nhom10/a394592a5033754a9d37c21e56f139d563bd7f3f_20260927T044640Z_c15ciazf/coverage.json`

The working tree changed while the scan was running. Before fixing a finding, reopen the current source around every cited location and confirm that the vulnerable data flow still exists. Do not copy a patch blindly from the old snapshot.

## 2. Agent execution contract

Every agent implementing this runbook must follow this sequence:

1. Read `docs/AGENTS.md`, `docs/PROJECT_RULES_AND_SKILLS.md`, this runbook, and the canonical finding.
2. Inspect the current route, controller, model, migration, view, service, and existing tests involved in the work package.
3. Capture the **input state** defined by the work package.
4. Implement the smallest complete server-side fix.
5. Add or update automated tests for the happy path, negative path, and regression path.
6. Run the work-package verification commands.
7. Fix failures and run the same verification again.
8. Capture the **output state** and evidence defined by the work package.
9. Stop if its exit gate is not satisfied. Do not continue to dependent packages.

### 2.1 Mandatory execution record

For every work package, the agent must report this exact structure in its handoff:

```text
Work package: WP-XX
Input revision: <git SHA>
Input files inspected: <paths>
Finding still reproducible in source: yes/no + evidence
Changes made: <paths and concise explanation>
Database migration: none/<migration name>
Commands executed: <exact commands>
Command outputs: <exit code and concise result>
Security tests added or changed: <test names>
Functional regression tests: <result>
Residual risk: <none or explicit risk>
Rollback: <exact revert/migration action>
Output revision or diff: <commit SHA or git diff --stat>
Exit gate: passed/failed
```

Never place passwords, OTPs, customer PII, provider secrets, session IDs, complete cookies, or payment payloads in this record.

### 2.2 Change boundaries

- One work package per commit where practical.
- Do not combine UI redesign with a security fix.
- Do not change database structure unless the package explicitly requires it.
- Do not weaken CSRF, RBAC, ownership checks, validation, throttling, or TLS behavior to make a test pass.
- Do not run production data mutation, credential rotation, email delivery, payment callbacks, or Git history rewriting without explicit operator authorization.
- All authorization and state-transition rules must be authoritative on the server. UI checks are supplementary.
- Use Laravel/Eloquent/query builder bindings; never concatenate untrusted SQL.

## 3. Global inputs, outputs, and release gates

### 3.1 Baseline input

Capture before WP-00:

```powershell
git status --short
git rev-parse HEAD
php -v
php artisan --version
composer validate --strict
npm --version
php artisan route:list
php artisan test
npm run build
```

Expected input output:

- Current revision and dirty files are known.
- Composer metadata is valid.
- Route list can be generated without boot errors.
- Existing test/build failures are recorded separately from remediation regressions.

If the baseline suite is already failing, preserve its exact output. The agent may fix a pre-existing failure only when it blocks the selected package and must identify that change separately.

### 3.2 Required final output

After all packages:

```powershell
php artisan optimize:clear
composer validate --strict
composer audit
npm audit --omit=dev
php artisan test
npm run build
php artisan route:list
git status --short
```

Release output must include:

- All security and regression tests passing.
- Production frontend build passing.
- No known production dependency advisories, or a documented accepted exception with owner and expiry.
- No database dump, plaintext password, OTP, provider secret, or public temporary QR artifact tracked by Git.
- Every state-changing browser route uses a non-GET method and CSRF unless it is a separately authenticated webhook.
- A fresh Codex Security scan or scoped verification scan against the final revision.

## 4. Dependency map and required order

```text
WP-00 Containment
 ├─ WP-01 Google identity linking
 ├─ WP-02 Privileged account provisioning
 ├─ WP-03 Booking transition policy
 │   └─ WP-04 Signed check-in credentials
 ├─ WP-05 Atomic VietQR confirmation
 ├─ WP-06 Private QR lifecycle
 ├─ WP-07 Internal password invitation
 ├─ WP-08 Session revocation
 ├─ WP-09 Secret-safe logging
 ├─ WP-10 Locked frontend dependency
 ├─ WP-11 CSRF-safe OTP resend
 ├─ WP-12 Escaped payment email
 └─ WP-13 Enumeration-resistant authentication

WP-14 Deployment hardening and final verification runs last.
```

WP-00 is an incident-containment decision, not a code-only task. WP-01 through WP-13 may be implemented independently except WP-04, which depends on WP-03. WP-14 is the release gate.

## 5. WP-00 — Immediate containment and evidence preservation

**Findings covered:** SQL dump exposure, default privileged accounts, OTP logs.

### Input

- `database/qlkhachsan.sql`
- `database/seeders/DatabaseSeeder.php`
- Git history and remote visibility information
- Current privileged users and active sessions from an authorized environment
- Application log retention locations

### Execution

1. Confirm whether the repository is or has ever been public, mirrored, archived, or shared externally.
2. Record the affected time window and authorized repository readers. Do not copy the dump into tickets or chat.
3. Stop using known seed credentials immediately in every non-local environment.
4. Rotate or disable seeded privileged accounts through an approved operational channel.
5. Revoke their sessions and remember tokens.
6. Remove the SQL dump from the current tree and add an appropriate ignore rule.
7. Prepare a history-rewrite plan using `git filter-repo` or the hosting provider's documented sensitive-data procedure. Execute it only after operator approval because it rewrites shared history.
8. Search retained logs for OTP values, restrict access, and apply the retention/deletion policy.
9. Decide whether notification or incident-response obligations apply to the exposed PII.

### Output

- Incident owner and decision log.
- List of credential/session rotations completed, without secret values.
- SQL dump absent from the current index.
- Approved history-rewrite procedure or a documented reason it has not yet been run.
- Log-retention action recorded.

### Verification

```powershell
git ls-files database/qlkhachsan.sql
git grep -n -I -E "admin123|reception123"
git grep -n -I -E "otp.*(log|info|debug)|Log::.*otp" -- app config routes
```

Expected output:

- First command prints nothing.
- Credential search finds no deployable default password.
- No live OTP value is written to logs.

### Exit gate and rollback

- **Gate:** current source is clean of the dump/default password/OTP logging and operational rotations are assigned or complete.
- **Rollback:** do not restore exposed data or credentials. If history rewriting fails, restore repository availability from a protected backup while keeping the sensitive file removed, then reschedule the rewrite.

## 6. WP-01 — Safe Google account linking

**Finding:** `account-pre-hijack.oauth-email-linking` — High.

### Desired invariants

- Google login by itself never proves ownership of an existing local password credential.
- An unverified email collision cannot silently become a verified linked account.
- Linking an identity provider or changing the primary email revokes all older sessions and remember tokens.
- Google customer flows can never link to admin or receptionist identities.

### Input

- `app/Http/Controllers/GoogleAuthController.php`
- `app/Http/Controllers/AuthController.php`
- `app/Models/User.php`
- Google routes in `routes/web.php`
- Google tests in `tests/Feature/SecurityFlowsTest.php`
- Users table uniqueness constraints for email and `google_id`

### Execution

1. Normalize the provider email once with lowercase and trimming.
2. Keep lookup by `google_id` as the returning-provider path.
3. For an email-only collision:
   - reject internal roles;
   - never auto-link an unverified local account;
   - require an authenticated, recent-password-confirmed link flow for a verified local customer;
   - alternatively create a short-lived, single-use link approval sent to the already verified account email.
4. Store pending email changes separately or require verification before replacing `users.email`.
5. On successful provider link or primary email change:
   - rotate `remember_token`;
   - delete other database sessions for the user;
   - regenerate the current session;
   - clear obsolete OTP/link tokens.
6. Keep unique database constraints on normalized email and `google_id`; handle duplicate-key races as a controlled validation error.
7. Do not store Google access tokens unless a later feature needs them.

### Output

- Explicit link policy implemented in one server-owned service or narrowly scoped controller method.
- No email-match callback path upgrades an attacker-created unverified account.
- Session revocation is invoked after identity changes.
- Regression tests cover both registration and email-change pre-hijack variants.

### Required tests

- Attacker registers victim email; victim completes Google callback; attacker password must not authenticate.
- Verified customer tries explicit provider linking after recent password confirmation; link succeeds.
- Unauthenticated email collision is rejected without revealing whether the local account exists.
- Google ID already linked to another user is rejected.
- Admin/receptionist email cannot be linked through customer OAuth.
- Two concurrent callbacks cannot create duplicate identities.

### Verification

```powershell
php artisan test --filter=Google
php artisan test --filter=SecurityFlowsTest
```

Expected output: all Google identity, session, and general security tests pass with zero failures.

### Exit gate and rollback

- **Gate:** old local credentials cannot survive an unauthorized auto-link, while explicit legitimate linking works.
- **Rollback:** revert the provider-link feature to login by existing `google_id` plus new-account creation only; do not restore email auto-linking.

## 7. WP-02 — Remove default privileged credentials

**Finding:** `hardcoded-credentials.privileged-seeder` — High.

### Input

- `database/seeders/DatabaseSeeder.php`
- Internal login routes and `InternalAuthController`
- Environment detection and deployment scripts

### Execution

1. Remove fixed verified admin and receptionist credentials from the default seeder.
2. If demo users remain useful, move them to a seeder that fails unless `app()->environment(['local', 'testing'])`.
3. Provision the first production administrator through a one-time CLI command or invitation token.
4. Generate a cryptographically random bootstrap secret and reveal it once through the operator terminal, never logs or email.
5. Force password setup/change before privileged access.
6. Add CI checks for known default usernames/password literals.

### Output

- Production seeding creates no enabled privileged identity with a known password.
- Local demo seeding is explicitly environment-gated.
- Bootstrap procedure is documented without embedding secrets.

### Verification

```powershell
php artisan migrate:fresh --seed --env=testing
git grep -n -I -E "admin123|reception123"
php artisan test --filter=Internal
```

Expected output: no known password literal; internal role tests pass; testing-only fixtures remain usable.

### Exit gate and rollback

- **Gate:** no production-capable code path creates known privileged credentials.
- **Rollback:** restore only a local/testing fixture guarded by environment checks, never the global default account.

## 8. WP-03 — Central booking transition policy

**Finding:** `business-logic.booking-state-transition` — High.

### Desired invariants

- Every booking transition is checked against an explicit source state and destination state.
- Check-in is permitted only from `confirmed` and only when payment/deposit policy is satisfied.
- Pending, cancelled, refunded, failed, completed, and already checked-in bookings cannot enter check-in again.
- Transition validation and state mutation occur under the same transaction and row lock.

### Input

- `app/Http/Controllers/ReceptionController.php`
- `app/Http/Controllers/PaymentController.php`
- `app/Models/Booking.php`
- Customer success and staff booking views
- Current booking/payment/refund status values from schema and application code

### Execution

1. Inventory all code that updates `bookings.status`.
2. Define one transition map in a `BookingTransitionService`, policy object, or model domain method.
3. Define the payment predicate for online, cash, deposit, and walk-in cases.
4. In quick check-in, lock the booking row, reload room relations, evaluate transition and payment predicate, then mutate booking and rooms atomically.
5. Return a stable validation/domain error for invalid transitions.
6. Remove frontend logic that labels `pending` as eligible. Keep frontend state derived from the server response.
7. Route cancellation, refund, checkout, and extension through the same policy where practical; do not perform a broad refactor before regression coverage exists.

### Output

- One authoritative transition owner.
- Quick check-in accepts only the intended state/payment combinations.
- Staff UI does not advertise actions the backend rejects.
- Audit log records actor, booking, from-state, to-state, and result without PII-heavy payloads.

### Required tests

- Confirmed and paid booking checks in successfully.
- Allowed cash-at-desk booking checks in according to documented policy.
- Pending online booking is rejected.
- Cancelled, refunded, completed, and already checked-in bookings are rejected.
- Wrong check-in date is rejected.
- Concurrent quick check-in calls produce one success and one controlled rejection.
- Room conflict causes full rollback.

### Verification

```powershell
php artisan test --filter=QuickCheckin
php artisan test --filter=SecurityFlowsTest
```

Expected output: transition tests pass; no rejected transition mutates booking or room status.

### Exit gate and rollback

- **Gate:** invalid source states are unrepresentable through every server entry point reviewed.
- **Rollback:** revert callers and service together. Never leave UI permissive while reverting only backend policy or vice versa.

## 9. WP-04 — Signed, expiring check-in QR credentials

**Depends on:** WP-03.

### Input

- Payment/success QR generation in `PaymentController`
- Staff QR parsing in `resources/views/staff/bookings.blade.php`
- Quick check-in endpoint
- Laravel signing/encryption facilities and application key policy

### Execution

1. Stop encoding a raw booking ID and customer data as the authorization-bearing QR value.
2. Issue a signed token containing only booking ID, purpose `check-in`, random nonce, issued time, and expiry.
3. Keep expiry short enough for front-desk use and document it.
4. Verify signature, purpose, expiry, nonce, current booking state, ownership-independent operational role, and payment state on the server.
5. Consume or rotate the nonce after a successful check-in to prevent replay.
6. Do not expose the application key or token internals to JavaScript beyond the opaque QR value.

### Output

- Opaque signed QR token.
- Server rejects altered, expired, replayed, or wrong-purpose tokens.
- Raw PII no longer appears in QR payloads.

### Verification

```powershell
php artisan test --filter=Qr
php artisan test --filter=QuickCheckin
```

Expected output: valid token succeeds once; tampered, expired, and replayed tokens fail without mutation.

### Exit gate and rollback

- **Gate:** a booking ID alone cannot authorize check-in.
- **Rollback:** temporarily disable QR check-in and retain manual authorized lookup; do not restore unsigned booking-ID QR authorization.

## 10. WP-05 — Atomic and idempotent VietQR confirmation

**Finding:** `payment-state.vietqr-premature-log-success` — Medium.

### Desired invariants

- Payment log success, booking paid state, gateway transaction identity, and hold conversion commit together or not at all.
- Repeated gateway results are idempotent.
- A failed or mismatched amount never changes booking or room state.

### Input

- `app/Services/Payment/VietQRService.php`
- `app/Http/Controllers/PaymentController.php`
- `payment_logs` migration/model
- Existing payment status and transaction ID indexes

### Execution

1. Make `checkTransaction` return a verified provider result without committing local success.
2. Move local payment-log and booking updates into one transaction-owned confirmation method.
3. Lock the booking row and relevant payment log.
4. Verify gateway, external transaction ID, amount, booking eligibility, and currency before mutation.
5. Add or confirm a unique constraint for `(gateway, transaction_id)` or the correct provider-scoped idempotency key.
6. Treat duplicate delivery of the same successful transaction as a successful no-op only when it already belongs to the same booking and amount.
7. Return success to the client only after the transaction commits.

### Output

- No `PaymentLog=success` with an unpaid booking.
- Duplicate callbacks/polls are deterministic and safe.
- Provider payload stored only as needed, with secrets or excessive PII removed.

### Required tests

- Correct transaction confirms booking and log together.
- Duplicate same transaction does not double-apply.
- Same transaction ID for another booking is rejected.
- Amount mismatch is rejected without mutation.
- Exception between log and booking updates rolls back both.
- Cancelled/refunded/completed booking cannot be paid into an invalid state.

### Verification

```powershell
php artisan test --filter=VietQr
php artisan test --filter=Payment
php artisan migrate:status
```

Expected output: payment tests pass and any new idempotency migration is applied and reversible.

### Exit gate and rollback

- **Gate:** database constraints and transaction tests prove one atomic final state.
- **Rollback:** roll back schema only after reverting code that depends on the unique key; keep payment confirmation paused during rollback.

## 11. WP-06 — Private temporary QR lifecycle

**Finding:** `temporary-files.public-qr-pii` — Medium.

### Input

- QR generation and mail attachment code in `PaymentController`
- `public/temp` usage
- Laravel filesystem configuration

### Execution

1. Prefer QR bytes in memory and attach them with the mailer's in-memory API.
2. If a file is unavoidable, write it to `storage/app/private/tmp` using a random filename and least-privilege directory permissions.
3. Remove customer identity and stay details from the QR unless required by a documented business need.
4. Wrap creation/use in `try/finally` and delete the file in `finally` for both success and exception paths.
5. Add scheduled cleanup for abandoned private temp files older than a short maximum lifetime as defense in depth.
6. Remove legacy public files through an approved cleanup task.

### Output

- No predictable QR file under the public web root.
- Cleanup runs on success and failure.
- Public URL cannot retrieve generated QR artifacts.

### Verification

```powershell
git grep -n -I "public/temp\|chmod(.*0777\|qr_.*\.png" -- app config resources routes
php artisan test --filter=Qr
```

Expected output: no unsafe public temp pattern; tests confirm cleanup after simulated mail failure.

### Exit gate and rollback

- **Gate:** QR artifacts never persist in public storage.
- **Rollback:** fall back to private temporary files with strict cleanup, never `public/temp`.

## 12. WP-07 — Invitation-based internal account provisioning

**Finding:** `password-handling.plaintext-email` — Medium.

### Input

- `app/Http/Controllers/Admin/UserController.php`
- Internal account email templates
- Password-reset token infrastructure
- Admin RBAC routes

### Execution

1. Remove plaintext password values from all email payloads and templates.
2. Admin creates an inactive or setup-required account without learning its final password.
3. Generate a hashed, single-use, short-lived invitation token.
4. Email only the HTTPS invitation URL and expiry.
5. On redemption, validate token, set password with existing policy, verify/activate account, consume token, rotate remember token, and regenerate session.
6. Audit invitation creation, expiry, revocation, and redemption without logging tokens.
7. Allow admin to revoke and reissue an invitation.

### Output

- No email contains a password.
- Administrator never handles the staff member's final password.
- Invitation is single-use and expires.

### Verification

```powershell
git grep -n -I -E "password.*Mail|Mật khẩu.*\{|plain.*password" -- app resources
php artisan test --filter=Invitation
php artisan test --filter=Internal
```

Expected output: templates contain no password interpolation; invitation security tests pass.

### Exit gate and rollback

- **Gate:** every new/reset internal credential is chosen by the account owner through a one-time flow.
- **Rollback:** disable account creation temporarily; never restore plaintext-password email.

## 13. WP-08 — Revoke sessions after credential changes

**Finding:** `session-management.password-change-revocation` — Medium.

### Input

- Customer reset/change methods in `AuthController`
- Staff/admin credential-change controller
- `sessions` migration and configured session driver
- User `remember_token`

### Execution

1. Implement one reusable session-revocation service keyed by user ID.
2. After password reset, password change, primary email change, or identity-provider link:
   - rotate `remember_token`;
   - delete all other database sessions belonging to the user;
   - invalidate relevant password-reset/link tokens;
   - regenerate the current authenticated session when retaining it is intended.
3. If the configured session driver cannot identify user sessions, document and implement the supported invalidation strategy before claiming completion.
4. Do not log session payloads or cookie values.

### Output

- Old browsers/sessions lose access after a credential or identity change.
- Current intended session remains valid only where policy permits.

### Required tests

- Two sessions exist; password change in one invalidates the other.
- Reset from a logged-out flow invalidates all prior sessions.
- Old remember cookie no longer authenticates.
- Session revocation is scoped to the target user.

### Verification

```powershell
php artisan test --filter=Session
php artisan test --filter=Password
```

Expected output: all old-session and password-reset tests pass.

### Exit gate and rollback

- **Gate:** no pre-change session or remember cookie remains usable.
- **Rollback:** force logout of all sessions on every credential change; this is less convenient but safer than retaining old sessions.

## 14. WP-09 — Remove authentication secrets from logs

**Finding:** `sensitive-data.logging-otp` — Medium.

### Input

- Logging calls in `AuthController` and internal auth code
- Exception handlers and mail diagnostics
- Production log sink and retention configuration

### Execution

1. Remove OTP, password, reset token, provider token, cookie, complete email, and payment secret values from log context.
2. Use event names, opaque request IDs, user IDs where appropriate, and redacted destination hints.
3. Add a central redaction processor if multiple integrations log structured payloads.
4. Verify exception logging does not serialize whole requests or provider responses.
5. Restrict production log access and retention.
6. Purge or protect historical logs containing live authentication data according to incident policy.

### Output

- Authentication events remain diagnosable without exposing secrets.
- Automated tests assert sensitive markers are absent.

### Verification

```powershell
git grep -n -I -E "Log::.*(otp|password|token|cookie)|logger\(.*(otp|password|token)" -- app
php artisan test --filter=Logging
```

Expected output: no secret-bearing log statement; redaction tests pass.

### Exit gate and rollback

- **Gate:** no authentication secret reaches configured logs under success or failure paths.
- **Rollback:** reduce authentication log detail to event name and request ID only.

## 15. WP-10 — Pin and locally build `html5-qrcode`

**Finding:** `supply-chain.unpinned-runtime-script` — Medium.

### Input

- `resources/views/staff/bookings.blade.php`
- `package.json` and lockfile
- Vite entry points
- Current Content Security Policy

### Execution

1. Install a reviewed exact compatible version of `html5-qrcode` through npm.
2. Import it from the Vite bundle used by staff booking management.
3. Remove the unversioned runtime `<script>` from the Blade view.
4. Commit the lockfile change.
5. Add or tighten CSP so staff pages do not need arbitrary runtime CDN scripts.
6. Confirm camera permission and scanner fallback behavior remain usable.

### Output

- Staff scanner works with network access to unpkg blocked.
- Dependency version is visible in the lockfile.
- CSP permits only the built application scripts and required sources.

### Verification

```powershell
npm ci
npm run build
npm audit --omit=dev
git grep -n -I "unpkg.com/html5-qrcode"
```

Expected output: clean production build; no unpkg reference; audit result documented.

### Exit gate and rollback

- **Gate:** scanner loads from the reviewed local build and staff page has no mutable CDN dependency.
- **Rollback:** temporarily disable camera scanning and retain manual lookup; do not restore the unversioned URL.

## 16. WP-11 — CSRF-safe OTP resend

**Finding:** `csrf.otp-resend-get` — Low.

### Input

- OTP resend routes in `routes/web.php`
- Verification/reset Blade forms
- `AuthController` resend methods
- Existing throttles and resend-delay session state

### Execution

1. Change both resend routes from GET to POST.
2. Render real forms with `@csrf` and submit buttons.
3. Preserve per-IP and per-account resend throttles and the server-side cooldown.
4. Ensure browser refresh/back does not silently resend.
5. Return 405 for GET and 419/CSRF rejection for invalid POST.

### Output

- Resend is an explicit CSRF-protected user action.
- Existing cooldown and generic response semantics remain intact.

### Verification

```powershell
php artisan route:list --path=verify
php artisan route:list --path=forgot-password
php artisan test --filter=OtpResend
```

Expected output: resend routes show POST only; GET and missing-CSRF cases are rejected.

### Exit gate and rollback

- **Gate:** no state-changing resend endpoint accepts GET.
- **Rollback:** disable resend temporarily; do not restore GET mutation.

## 17. WP-12 — Escape dynamic payment-email content

**Finding:** `html-injection.payment-email` — Low.

### Input

- Handcrafted HTML in `PaymentController`
- Booking customer-name validation
- Existing email templates and mail test facilities

### Execution

1. Move the payment email to a Blade Mailable/template.
2. Render all customer-controlled values with escaped `{{ }}` output.
3. Validate and format money/dates before passing presentation values to the template.
4. Prefer the verified account email. If alternate delivery is required, verify it separately.
5. Avoid raw `{!! !!}` for customer or gateway data.
6. Add a plain-text alternative body.

### Output

- HTML entered as a customer name appears as text, not markup.
- Transactional email remains readable in HTML and plain-text clients.

### Verification

```powershell
php artisan test --filter=PaymentEmail
php artisan test --filter=Mail
```

Expected output: test email encodes `<`, `>`, quotes, and Unicode safely; no raw HTML executes/renders as supplied markup.

### Exit gate and rollback

- **Gate:** every dynamic email field is auto-escaped or explicitly encoded at one reviewed boundary.
- **Rollback:** send a minimal escaped/plain-text receipt; do not restore raw heredoc interpolation.

## 18. WP-13 — Enumeration-resistant login and reset

**Finding:** `information-disclosure.account-enumeration` — Low.

### Input

- Customer `AuthController`
- `InternalAuthController`
- Login and forgot-password forms
- Current rate limiters

### Execution

1. Return the same user-visible message, field placement, HTTP status, and redirect for missing identity and wrong password.
2. Forgot-password always redirects to the same confirmation surface regardless of account existence.
3. Perform a dummy password hash check when the user is missing to reduce timing distinction.
4. Normalize email/username before rate-limit keys.
5. Rate limit by normalized identity plus IP; monitor distributed attempts without exposing the identity list.
6. Preserve helpful validation for malformed input because format errors do not reveal account membership.

### Output

- Existing and missing identities are indistinguishable through content, redirect, status, and broadly comparable work.
- Rate limiting still protects both customer and internal logins.

### Required tests

- Missing and wrong-password customer login have identical external response shape.
- Missing and wrong-password staff login have identical external response shape.
- Existing and missing reset email return the same route/message.
- Correct credentials still authenticate and regenerate the session.
- Repeated attempts reach rate limits for both IP and normalized identity.

### Verification

```powershell
php artisan test --filter=Login
php artisan test --filter=PasswordReset
php artisan test --filter=RateLimit
```

Expected output: response-equivalence and rate-limit tests pass.

### Exit gate and rollback

- **Gate:** account membership cannot be classified from the tested response surface.
- **Rollback:** use one generic failure response for all authentication failures.

## 19. WP-14 — Deployment and release hardening

This package closes limitations that a source-only scan cannot prove.

### Input

- Production/staging environment configuration, with secret values redacted
- Reverse proxy and TLS configuration
- CI workflow
- Composer/npm lockfiles
- Repository visibility and branch protection
- Mail, Google OAuth, VietQR and other payment-provider sandbox configuration

### Execution

1. Confirm production uses `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, HSTS, and secure session cookies.
2. Confirm trusted-proxy settings cannot be forged to bypass HTTPS or IP-based throttles.
3. Remove or hard-disable local preview/debug routes outside local/testing.
4. Verify Google redirect URIs and allowed domains exactly match deployed HTTPS origins.
5. Verify webhook signatures, amount/currency checks, replay protection, and idempotency in provider sandboxes.
6. Run online dependency advisory checks and add them to CI.
7. Add secret scanning and a rule preventing tracked SQL/database exports.
8. Run targeted DAST against staging with test data only.
9. Run a fresh Codex Security scan after all fixes and triage any remaining or newly introduced findings.

### Output

- Redacted deployment-security checklist signed by an owner.
- CI gates for test, build, dependency audit, secret scan, and database-export detection.
- Provider sandbox evidence with transaction/customer data redacted.
- Fresh scan ID and finding comparison against the original 13.

### Verification

```powershell
php artisan about --only=environment
php artisan config:show session
composer audit
npm audit --omit=dev
php artisan test
npm run build
```

Expected output:

- No production advisory at High/Critical without an approved exception.
- Full test/build passes.
- Production cookie and HTTPS settings match policy.
- Preview/debug endpoints are inaccessible outside local/testing.

### Exit gate and rollback

- **Gate:** all 13 findings are fixed or explicitly risk-accepted by an owner with expiry, and the final scan has no unresolved High/Critical finding.
- **Rollback:** deploy the previous known-good release while retaining credential rotations, data removal, and containment controls. Never roll back to exposed secrets, public QR files, default credentials, or invalid booking transitions.

## 20. Finding-to-work-package traceability

| Finding | Severity | Primary work package | Required evidence of closure |
|---|---|---|---|
| Google email auto-link preserves attacker credential | High | WP-01 | Pre-hijack regression tests and session revocation |
| Quick check-in accepts invalid booking states | High | WP-03, WP-04 | Transition matrix tests and signed-token tests |
| Default privileged seeded credentials | High | WP-00, WP-02 | No default literal; environment-gated fixtures |
| Tracked SQL dump exposes PII and hashes | High | WP-00 | File/index/history response and credential rotations |
| VietQR premature local success | Medium | WP-05 | Atomic rollback and idempotency tests |
| Public predictable QR files | Medium | WP-06 | Private/in-memory generation and failure cleanup test |
| Plaintext internal password email | Medium | WP-07 | Invitation tests and template search |
| Password changes retain active sessions | Medium | WP-08 | Multi-session invalidation tests |
| OTP logged in plaintext | Medium | WP-00, WP-09 | Log redaction tests and retention action |
| Unpinned html5-qrcode runtime script | Medium | WP-10 | Local locked build and CSP evidence |
| OTP resend mutates state through GET | Low | WP-11 | Route and CSRF tests |
| Payment email HTML injection | Low | WP-12 | Escaping regression test |
| Authentication account enumeration | Low | WP-13 | Equivalent-response tests |

## 21. Final agent handoff template

The final implementation agent must produce:

```text
Security remediation summary

Input scan: 25f1425d-dbed-4600-99f5-23ec34e31dee
Final revision: <SHA>
Work packages completed: <list>
Work packages deferred: <list + owner + reason + expiry>
Migrations added: <list>
Secrets/credentials rotated by operator: <yes/no/assigned; never include values>
Git history remediation: <completed/pending approval/not required>
Full Laravel tests: <command, passed/failed, count>
Frontend build: <command, passed/failed>
Composer audit: <result>
npm production audit: <result>
Fresh security scan: <scan id and severity counts>
Residual High/Critical findings: <none or exact accepted items>
Rollback release: <tag/SHA>
Release recommendation: approve/block
```

The agent must recommend **block** when any High finding remains open, a security migration is not applied, required credential rotation is incomplete, or the full regression suite fails.

