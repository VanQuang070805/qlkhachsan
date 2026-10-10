---
name: royal-ops-security-rag
description: >-
  Use this skill when working on authentication, session contexts, booking, room availability,
  check-in/checkout, late fees, stay extensions, payments, refunds, Royal Concierge RAG chatbot,
  or security tests in Royal Hotel.
---

# Royal Hotel — Operations, Security & RAG Architecture

## 1. Dual Session & Authorization Boundaries

- **Separated Session Contexts (`SetContextSessionMiddleware`, `AuthCustomMiddleware`, `RoleMiddleware`)**:
  - Customer routes use `customer_user_id` / `customer_user` (`auth.custom`, `verified.custom`).
  - Internal routes (`/staff*`, `/admin*`, `/receptionist*`, `/internalauth*`) use `staff_user_id` / `staff_user` and `role:receptionist,admin` or `role:admin`.
- **Login Methods**:
  - Customers: Email/password or Google OAuth (`GoogleAuthController`). Google OAuth must never link to an `admin` or `receptionist` account or merge two customer accounts.
  - Staff/Admin: Username/password only (`/internalauth/login`, rate-limited via `throttle:internal-login`).
- **Ownership Enforcement**:
  - Every customer booking, payment (`PaymentController`), cancellation (`CancellationController`), and review (`ReviewController`) route must verify `$booking->user_id === Auth::id()` on the server.

## 2. Booking, Pricing & Front-Desk Rules

- **Check-in Window**: `12:00` until before `16:00` (`Asia/Ho_Chi_Minh`). Same-day check-in is rejected at or after `16:00`.
- **Capacity Validation**: Total `max_guests` of selected rooms must be `>= adult_count + child_count`. Over-capacity is allowed; under-capacity is rejected.
- **10-Minute Hold (`Booking::reservedRoomIds`)**: Unpaid `pending` bookings hold rooms for `10` minutes from creation, then the backend cancels them and releases their rooms. Confirmed, checked-in, or paid bookings hold rooms across `[check_in, check_out)`.
- **Checkout & Late Fee**:
  - Scheduled checkout is before `12:00` with a 1-hour grace period until `13:00`.
  - After `13:00` (unless `waive_late_fee` is true), late fee = **50% of one room-night rate** (sum of attached rooms' nightly rates × `0.5`), never 50% of the multi-night booking total.
  - Occupied rooms cannot be manually switched to `cleaning` via status update to bypass checkout settlement.
- **Stay Extension**: Hourly extension costs 10% of the room-night price per hour through 18:00. Crossing 18:00 converts to one full night, crediting prior hourly charges. Daily extension uses the normal nightly rate.
- **Cancellations & Refunds**:
  - Customers cannot cancel after `checked_in`.
  - Refund confirmation (`processRefund`) requires `status === 'cancelled'` and `refund_status === 'eligible'`, and is idempotent.
- **Concurrency & Idempotency**:
  - Wrap booking creation, walk-in check-in, checkout, cancellation, and refund mutations in `DB::transaction` with `lockForUpdate()`.
  - Payment webhooks/callbacks (VietQR, MoMo, ZaloPay, VNPay) must verify signatures, exact deposit/payment amounts, and reject duplicate transaction IDs.

## 3. Royal Concierge Chatbot & RAG (`ChatbotController`, `RoyalKnowledgeService`)

- **Retrieval Hierarchy**:
  1. OpenAI Vector Store (`ROYAL_AI_VECTOR_STORE_ID`) when configured.
  2. Local `knowledge_chunks` table (semantic cosine similarity + keyword score).
  3. Markdown files in `resources/knowledge/*.md` (re-indexed via `php artisan knowledge:index` or `php artisan knowledge:index --no-embeddings`).
- **Allowlisted Read-Only Tools**:
  - `get_room_types`: current room types, prices, capacities.
  - `search_rooms`: live availability using `Booking::reservedRoomIds` and the 16:00 same-day rule.
  - `get_booking_for_user`: reads bookings strictly from `$request->session()->get('customer_user_id')` — never accept a user ID from LLM arguments.
- **Safety**: Never expose `ROYAL_AI_API_KEY`, passwords, OTPs, or payment secrets to the client or prompt logs. Strip/escape HTML in fallback replies.

## 4. Verification Commands

Run these checks before completing any backend or full-stack task:
- `php artisan test --compact`
- `npm run build`
- `php artisan view:cache`
