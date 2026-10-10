# Royal Hotel — Security audit

## Scope

Customer, staff and admin routes; authentication, authorization, booking ownership,
payment access, CSRF, session security, SQL construction, dependencies and transport.

## Implemented controls

- Separate customer and internal session contexts.
- Role middleware on staff and admin route groups; receptionists cannot access admin reports.
- Booking ownership checks protect customer payment and cancellation pages.
- Booking creation, walk-in check-in, cancellation, refund and checkout use transactions and row locks
  around the state changes that can compete for the same room.
- A pending unpaid booking reserves a room for 30 minutes. The same reservation rule is shared by
  room search, room detail, customer booking, payment and the front desk.
- Payment confirmation rejects cancelled/completed bookings and duplicate successful gateway
  transaction IDs. VietQR requires an exact amount match.
- Refund confirmation accepts only eligible cancelled bookings and is idempotent. Customer
  cancellation is blocked after check-in.
- Password-reset authorization is bound to one customer account, expires after ten minutes and is
  cleared after use. Password changes rotate the remember token.
- Login, registration and OTP endpoints have rate limits.
- CSRF remains enabled for browser mutations; only the documented webhook path is excluded.
- Report filters and operational raw SQL use bound parameters.
- Chatbot tools are allowlisted and read-only. Booking retrieval is scoped to the customer id held by the authenticated customer session; the model cannot supply another user id.
- Opening the room matrix is read-only and no longer rewrites maintenance or transitional room statuses.
- Passwords use Laravel hashing and internal account passwords require at least eight characters.
- Production HTTP requests redirect to HTTPS. Secure responses include HSTS, anti-sniffing,
  same-origin framing, referrer and permissions headers.
- Encrypted sessions are enabled; production must set `SESSION_SECURE_COOKIE=true`.

## Row-level access

The project uses MySQL, which has no native PostgreSQL-style RLS. Equivalent enforcement is
implemented in Laravel through route role middleware and per-booking ownership checks. Database
queries remain parameterized. Moving to PostgreSQL would be required for native database RLS.

## Verification

- Laravel: 32 tests, including booking ownership, chatbot tool scoping, streamed responses, RAG retrieval, KPI snapshots and read-only room-matrix loading.
- Composer audit: no known vulnerability advisories.
- npm production audit: 0 vulnerabilities.
- Vite production build: passed.
- Blade view compilation: passed.

The automated suite covers route roles, customer ownership, rate limiting, reset authorization,
customer remember-cookie issuance, booking date validation, room capacity and duplicate holds, cancellation/refund state transitions,
checkout fees, room-status bypass prevention, the 30-minute hold expiry, and production HSTS.

## External verification still required

- Live SMTP delivery and provider callbacks for VNPay, MoMo, ZaloPay and VietQR require sandbox or
  production credentials and were not charged or contacted by this local test run.
- Google Maps behavior requires its configured browser API key and allowed-domain settings.
- HTTPS is enforced in production. The local preview remains HTTP at `127.0.0.1` by design.

## Strix status

The Strix repository and its security playbook were reviewed. Its automated agent scan could not
run on this workstation because Docker Engine is unavailable and no Strix-compatible LLM API key
is configured. The local audit therefore used its OWASP-oriented workflow, source inspection,
route/role tests and dependency scanners. An automated Strix result must not be inferred from this
report.
