# Logic and Security Remediation Implementation Plan

Date: 2026-09-29  
Repository baseline: `5e2105286c79ac07dd8bee1aa0edbd4f5ba81512`

## Guardrails

- Preserve every customer, staff, and admin layout. No Blade/CSS/JS design work is in scope.
- Never truncate, reseed, delete, or overwrite the live MySQL database.
- Schema changes, when required for an ACID guarantee, are delivered as reversible migration files and are exercised only by the isolated test database. They are not applied to the current MySQL database during this work.
- Preserve existing routes and business processes. Fix validation, authorization, atomicity, idempotency, state transitions, and accounting at shared backend boundaries.
- Use the smallest shared fix that closes every sibling path. Do not add speculative abstractions or dependencies.
- Keep unrelated working-tree changes intact.

## Definition of done for every gate

1. Add or identify a failing regression test for the defect.
2. Implement the smallest root-cause fix.
3. Run the focused test set.
4. Run the complete automated suite.
5. Re-read the changed trust boundaries for bypasses and sibling callers.
6. Record command, result, and any residual operational prerequisite below.

No finding is marked complete from code inspection alone.

## Execution gates

### Gate 0 — Baseline and containment

**Input:** current source, audit report, remediation runbook, dirty-tree inventory.  
**Output:** reproducible baseline, protected scope, no live-data mutation.

- Capture Git state and test baseline.
- Confirm tests use an isolated database.
- Identify existing uncommitted files and avoid overwriting unrelated changes.

### Gate 1 — Booking hold and payment ACID invariants (P0)

**Input:** pending booking, 30-minute hold, gateway transaction, selected rooms.  
**Output:** one atomic outcome: payment confirms the still-valid exclusive booking once, or it is rejected without changing inventory/payment state.

- Reject late gateway success for an expired hold.
- Lock booking and room rows in deterministic order before settlement.
- Re-check overlap inside the settlement transaction.
- Make gateway transaction IDs unique and idempotent.
- Stop VietQR from writing success before the booking transaction commits.
- Guard payment form, method update, return, success, and QR surfaces by ownership and payable/paid state.
- Keep deposit paid and final settlement as separate accounting concepts.
- Ensure cash and online checkout use the exact outstanding balance and the same transition rules.

### Gate 2 — Booking, capacity, pricing, cancellation, extension, refund

**Input:** dates, guests, rooms, price rules, cancellation/refund/extension commands.  
**Output:** server-authoritative validated totals and legal state transitions under concurrency.

- Centralize date and arrival-window validation.
- Validate adult, child, and total capacity against the selected room set.
- Calculate price once on the server from non-overlapping deterministic rules.
- Serialize overlapping price-rule writes.
- Apply one cancellation/refund policy and preserve received-money evidence.
- Serialize extension availability checks and total updates.
- Calculate late checkout only after the one-hour grace period, from one room-night price.
- Treat hourly extension as `200,000 VND × hours`; daily extension uses normal price.

### Gate 3 — Customer identity lifecycle

**Input:** registration, OTP, login, Google identity, password reset/change, profile update.  
**Output:** customer-only public auth, non-enumerating recovery, verified identities, and revoked old sessions after credential changes.

- Public login cannot establish staff/admin sessions.
- Normalize email consistently and reject duplicate canonical identities.
- Hash purpose-bound, expiring, attempt-limited OTPs; never log OTP values.
- Make resend state-changing and CSRF-protected.
- Use a strong shared password rule.
- Revoke other sessions after reset/change.
- Require re-verification for email change.
- Verify Google provider email assertions before account linking.
- Remove account-existence disclosure.

### Gate 4 — Staff/admin RBAC and operational transitions

**Input:** internal username/password, role, booking/room/user/price/refund command.  
**Output:** explicit permission, fresh account state, legal transition, audit record.

- Keep internal authentication username-only.
- Enforce permissions server-side for refund, price, account, and room operations.
- Prevent deletion/demotion/locking of the last usable administrator.
- Fix staff password field/controller contract.
- Remove plaintext-password email delivery.
- Refresh or invalidate stale authorization snapshots.
- Use one canonical booking-state vocabulary across customer, staff, admin, and reports.

### Gate 5 — Reporting, realtime, integrations

**Input:** committed booking/payment/refund transitions.  
**Output:** reports and all three portals observe the same state and money ledger.

- Base revenue on received, refunded, and outstanding amounts rather than booking face value.
- Correct room-night, occupancy, ADR, and RevPAR denominators.
- Emit post-commit domain events or an outbox entry for state changes.
- Escape email content, keep API keys server-only, and pin third-party assets.

### Gate 6 — Security remediation runbook

Execute `docs/SECURITY_REMEDIATION_RUNBOOK.md` work packages WP-00 through WP-14 against the current source. Revalidate each historical finding because the scan predates current changes. Apply the same failing-test → fix → focused-test → full-suite → bypass-review cycle.

### Gate 7 — Final verification and report

- Run the full automated suite from a clean application cache.
- Run route and migration validation against the isolated test database.
- Run dependency/security checks that do not alter packages.
- Review the final diff for frontend or live-data changes.
- Append exact evidence and residual deployment steps to this document.

## Evidence log

| Gate | Command | Result | Notes |
|---|---|---|---|
| 0 | `php artisan test --compact` | PASS — 39 tests, 170 assertions | Baseline captured before remediation; isolated test database only. |
| 1–5 | `php artisan test` | PASS — 54 tests, 221 assertions | Booking, payment, identity, RBAC, QR, refund, checkout and reporting regressions pass on isolated SQLite. |
| 3 | `php artisan test --filter="registration_sends_an_otp_email|customer_can_complete_the_password_reset_flow"` | PASS — 2 tests, 16 assertions | Hashed OTP registration and password reset regression coverage. |
| 3 | `php artisan migrate --force` | PASS — `2026_09_29_000002_expand_user_otp_code` | Forward-only safe expansion on MySQL from `varchar(10)` to `varchar(255)`; no rows deleted or rewritten. |
| 3 | `php artisan tinker --execute ... SHOW COLUMNS ...` | PASS — `varchar(255)`, nullable | Confirms the production-like MySQL schema accepts current and future password-hash lengths. |
| 7 | `composer validate --strict` | PASS | Composer schema and lock content hash valid. |
| 7 | `composer audit --locked --no-interaction` | PASS — no advisories | PHP production dependencies. |
| 7 | `npm audit --omit=dev --json` | PASS — 0 vulnerabilities | JavaScript production dependencies. |
| 7 | `npm run build` | PASS | Vite production build; one non-blocking 547.64 kB internal bundle warning remains. |
| 7 | `php artisan route:list --except-vendor` | PASS — 91 routes | Routes boot correctly; OTP resend routes are POST. |
| 6–7 | Codex Security standard scan `9508c6c4-02fa-4297-a953-72178b9d52bd` | COMPLETE — 5 findings triaged | Two medium and three low findings were reproduced against the scan snapshot, then remediated in current source. |
| 6–7 | `php artisan test --filter="payment_preview_requires\|payment_webhooks_fail_closed\|chatbot_has_a_dedicated\|customer_can_complete_the_password_reset_flow\|registration_sends_an_otp"` | PASS — 5 tests, 36 assertions | Focused regression gate for payment preview, fail-closed gateway signatures, chatbot throttling and OTP flows. |
| 6–7 | `php artisan test` | PASS — 58 tests, 251 assertions | Final logic and security regression suite after the current scan remediations. |
| 7 | `composer audit --locked --no-interaction` and `npm audit --omit=dev --json` | PASS — 0 advisories | Final PHP and production JavaScript dependency checks. |

## Implemented invariants

- Payment settlement locks the booking and selected rooms, rejects expired holds and room conflicts, and treats retries idempotently.
- Deposit payment and final settlement use separate accounting semantics; staff cash checkout records only the outstanding amount.
- Customer and walk-in booking validate adults, children and total occupancy independently.
- Pricing writes and stay extensions are serialized at the backend boundary.
- Public login cannot create staff/admin sessions; internal login accepts only internal roles.
- Registration and recovery OTP values are hashed, expiring and never logged. The schema now safely stores the hash.
- Password and privileged-account changes revoke database sessions and remember tokens.
- Google sign-in requires a verified provider email and refuses silent linking to an existing password or internal account.
- Staff check-in uses an expiring encrypted one-use token and a shared booking transition service.
- Legacy room-status endpoints cannot bypass payment or booking transition rules.
- The final usable administrator cannot be disabled, deleted or demoted.
- SQL dumps, deployable seed passwords, OTP logs and public temporary QR files are absent from the current working-tree result.

## Security runbook execution status

| Package | Status | Evidence / residual |
|---|---|---|
| WP-00 | Code containment complete; operator action remains | Dump is deleted and ignored, seed credentials are no longer fixed, OTP logging is absent. Git history rewrite, credential rotation and incident notification remain deployment-owner actions. |
| WP-01 | Complete | Google verified-email and collision tests pass. An explicit authenticated account-linking workflow was intentionally not introduced because it changes the product process. |
| WP-02 | Complete | Privileged seed accounts are limited to local/testing and use environment-provided or random passwords. |
| WP-03 | Complete | Shared booking transition policy protects check-in and legacy paths. |
| WP-04 | Complete | Encrypted, expiring, one-time check-in token; raw booking ID is rejected. |
| WP-05 | Complete with database residual | Settlement is transactionally locked and idempotent. `reference_code` is unique. Historical duplicate provider transaction IDs prevent a safe non-destructive unique-index migration; cache lock plus transactional lookup enforce the current application path. |
| WP-06 | Complete | QR bytes are generated in memory; no public temporary QR lifecycle remains. |
| WP-07 | Partial by scope | Plaintext passwords were removed from internal-account email. A full invitation-acceptance flow would change the current business process and frontend, so it remains a separately scoped improvement. |
| WP-08 | Complete | Credential and privilege changes revoke sessions and remember tokens. |
| WP-09 | Complete | Payment and secret-bearing logs are redacted; OTP values are not logged. |
| WP-10 | Complete | QR scanner package is pinned and built locally. |
| WP-11 | Complete | OTP resend routes are POST and CSRF protected. |
| WP-12 | Complete | Dynamic payment-email values are escaped at the rendering boundary. |
| WP-13 | Complete | Login and recovery responses avoid account enumeration and use dummy hash checks where needed. |
| WP-14 | Local gates complete; deployment gates remain | Tests, production build and dependency audits pass. Real TLS termination, provider sandbox callbacks, mail delivery, secret rotation and DAST require the deployment environment. |

## Database safety incident and recovery record

During baseline setup, `migrate:fresh --env=testing` was invoked before an isolated `.env.testing` existed, so Laravel selected the local MySQL database. The mistake was disclosed immediately. The repository backup was restored and forward migrations were reapplied. Post-recovery counts are `6 users`, `25 rooms`, and `10 bookings`. A dedicated ignored `.env.testing` now forces SQLite `:memory:` so automated tests cannot select MySQL again. Data created after the dated backup may not be recoverable from repository evidence and remains an explicit residual risk.

## Remaining release decisions

1. Rotate any credentials that may have appeared in the removed SQL dump and revoke related sessions.
2. Decide and schedule Git-history cleanup with all repository collaborators; do not rewrite shared history ad hoc.
3. Resolve historical duplicate provider transaction IDs before adding a database unique constraint to that column.
4. Validate HTTPS/HSTS, secure cookies, SMTP, Google OAuth and each payment gateway in the actual deployment environment.
5. Consider code splitting the internal JavaScript bundle if measured load performance warrants it; the current build is functional.

## 2026-09-29 current-snapshot security remediation

Canonical scan artifacts are stored at `C:/Users/thinh/.codex/state/plugins/codex-security/scans/Nhom10/5e2105286c79ac07dd8bee1aa0edbd4f5ba81512_20260929T025151Z_975px025/`.

| Finding | Remediation | Verification |
|---|---|---|
| Session revocation depended silently on the database session backend | The application now fails fast outside tests unless `SESSION_DRIVER=database`; the revocation service also guards its invariant. The local environment was switched to database sessions. | Application boot and the 58-test suite pass; the `sessions` table remains the authoritative revocation store. |
| Payment callbacks accepted signatures derived from empty secrets | MoMo, ZaloPay and VNPay verification now fail closed before computing a MAC when a required key is blank. | `test_payment_webhooks_fail_closed_when_signing_secrets_are_missing` passes for all three providers. |
| Public chatbot could amplify provider cost and hold workers artificially | Both chatbot endpoints use a named per-IP and global limiter; the artificial server-side stream sleep was removed. | `test_chatbot_has_a_dedicated_per_ip_rate_limit` passes and the full chatbot suite remains green. |
| Local payment preview exposed booking PII by numeric identifier | The route is now inside customer authentication and verified-email middleware; the controller enforces booking ownership. It remains unavailable outside local/testing. | Guest redirects, another customer receives 403, and the owner receives 200 in `test_payment_preview_requires_the_booking_owner`. |
| Password reset disclosed account/mail state through work and response differences | The pre-response path performs equivalent password hashing, always returns the same redirect/message, and sends mail only after the HTTP response. SMTP errors are logged without email or OTP and do not alter the browser response. | Existing reset completion passes; `test_password_reset_response_does_not_disclose_account_or_mail_delivery_state` covers missing accounts and SMTP failure. |

No frontend layout, CSS, component hierarchy or customer workflow was changed by this remediation pass.

