# Royal Hotel — Project Rules, Skills and Durable Context

Last consolidated: 2026-09-25

This document is the durable entry point for future work on Royal Hotel. Read it before changing customer, admin, staff, authentication, booking, payment, email, chatbot, or shared UI code.

## 1. Sources of truth

Read these documents together. When they differ, the newest explicit user decision and then this file take precedence.

1. [`DESIGN.md`](../DESIGN.md) — foundational visual system and tokens.
2. [`DESIGN_REFERENCE.md`](../DESIGN_REFERENCE.md) — current customer experience, Letters references, motion and accepted UI decisions.
3. [`ADMIN_DESIGN_PLAN.md`](../ADMIN_DESIGN_PLAN.md) — admin and staff information architecture.
4. [`docs/EXPERIENCE_AND_DATA_ARCHITECTURE.md`](EXPERIENCE_AND_DATA_ARCHITECTURE.md) — GSAP, Vue, AI/RAG and data direction.
5. [`docs/RAG_ARCHITECTURE.md`](RAG_ARCHITECTURE.md) — chatbot retrieval architecture.
6. [`SECURITY_AUDIT.md`](../SECURITY_AUDIT.md) — backend and deployment security findings.
7. [`docs/GOOGLE_OAUTH_SETUP.md`](GOOGLE_OAUTH_SETUP.md) — customer Google sign-in configuration.
8. [`docs/QA_2026-09-23.md`](QA_2026-09-23.md) — latest QA handoff notes.

Do not create another design system when these documents already cover the need. Extend the existing system and record durable changes here or in the relevant source document.

## 2. Working method

- Required cycle: **inspect and understand → implement → verify → fix → verify again → finish**.
- Inspect existing routes, controllers, database fields, Blade components, CSS and JavaScript before redesigning a workflow.
- Keep database and route compatibility. UI changes must not break booking, search, payment, browser Back/Forward, refresh, session or role boundaries.
- Prefer the smallest implementation that solves the actual problem. Apply Ponytail/YAGNI: native platform behavior and existing shared components before new dependencies or parallel systems.
- Reuse components and tokens. Remove redundant cards, titles, explanatory copy, banners and duplicate statuses when they obstruct the task.
- Never expose secrets, API keys, passwords, OTPs or provider tokens in Blade, JavaScript, logs, screenshots or Git.
- Do not claim completion before relevant rendering and behavior have been verified.

## 3. Product surfaces and boundaries

Royal Hotel has three distinct surfaces:

### Customer

- Calm, immersive, editorial hospitality experience.
- Minimal and clean with restrained copy, strong centered section headings and clear actions.
- Uses the blue gradient, white content surfaces, ambient stars and selective GSAP.
- Customer authentication is email/password or Google. Customer routes must never expose internal controls.

### Admin

- Operational and analytical workspace with a persistent sidebar.
- Dashboard behaves like an interactive report: coordinated filters, KPI cards and charts update without full reload when practical.
- Full RBAC management features; admin routes require the admin role.
- Current approved direction is a light-only interface to avoid theme flashes and low-contrast components.

### Staff / front desk

- Fast room, booking, check-in, checkout, extension, refund and QR workflows.
- Uses the same shell and icon language as admin with fewer capabilities enforced by middleware and backend authorization.
- Dense data must remain scannable. Avoid decorative motion that slows operations.

The admin and staff sidebar/header must remain persistent across their pages. Navigation must not jump to a legacy layout. Sidebar state follows the user and must not reset during navigation.

## 4. Customer visual system

- Typeface: **Inter** across display, body, navigation, labels and forms.
- Core palette:
  - Deep blue: `#779bc1`
  - Sky blue: `#9abfda`
  - Pale blue: `#cbdcec`
  - Accent blue: `#2597d0`
  - Ink: `#070709` / `#151515`
  - White: `#ffffff`
  - Muted text: `#60606c`
  - Hairline: `#bebecc` or low-opacity neutral borders
- Customer top surfaces use the Royal gradient `#779bc1 → #9abfda → #cbdcec`, rounded framing and a fine white edge.
- Homepage first viewport contains header, hero and availability search. Content below changes to a white canvas with tighter section spacing.
- Interior customer pages use a compact framed blue hero followed by white sections.
- Do not replace the blue welcome surface with landscape photography unless explicitly requested.
- Customer header contains no Royal wordmark at the upper left. Authentication and internal portals may use the approved pale-blue Royal crown/wordmark.
- The homepage header implementation is the reference for other customer pages except login/register. It blends into the hero at the top and gains translucent blur when scrolled.
- Keep headings short, preferably English on customer section titles where already approved. Do not add trailing full stops to display headlines.
- Use genuine room interior photography appropriate to the room tier. Do not mix exterior property images into room galleries.

## 5. Layout and component rules

- Use an 8pt spacing rhythm and a bounded fluid layout; avoid uncontrolled full-width stretching.
- Rounded corners are soft and deliberate. Do not use excessive pills or excessive nesting.
- Cards exist to group a real task or decision. Avoid card-on-card-on-card layouts.
- Primary actions are high contrast. Secondary actions must remain legible without competing with the primary action.
- Forms use real labels, helper/error text and inline validation. Never show the same error twice or place a redundant error banner above the form.
- Popups fit the viewport, scroll internally when necessary and preserve clear action hierarchy.
- Responsive states must be designed, not obtained by letting labels wrap unpredictably.

### Approved interaction primitives

- **Traffic Lights:** use macOS-style controls only where the actions are real. A normal popup usually exposes only the red close control; omit yellow/green when minimize/zoom is unsupported. Controls must render in Blade when the popup is known at render time.
- **Date Picker:** use the shared accessible calendar popover in `resources/js/date-picker.js`, not the browser-native picker. Preserve civil `yyyy-mm-dd` values, Monday-first grid, previous/next month, today, range start/middle/end and keyboard arrows/PageUp/PageDown/Escape.
- **Steps:** ordered list, one `aria-current="step"`, numbered indicators, connectors, completed checkmarks and muted future steps. One zero-based state drives all visual states.
- **Pagination:** semantic `nav`, numbered links, one `aria-current="page"`, previous/next and a non-interactive ellipsis. Do not display “Showing 1 to 10…” text.
- **Status Dot:** accessible named state, small solid core, surface-colored separation ring and optional motion-safe ping for live state. Never rely on color alone.
- **Search Field:** leading search action, trailing clear action only when non-empty, appropriate label and debounced filtering where queries are live.
- **Form Field:** real `label for`, required attribute, optional helper text and one inline error linked by `aria-describedby`/`aria-invalid`.
- **Sign-in Form:** official four-color Google mark; `autocomplete="username"` and `autocomplete="current-password"`; password visibility control is `type="button"`; Enter submits.
- **Chat Bubble:** message list uses `role="log"`; customer and assistant messages are aligned distinctly; typing state uses three animated dots; timestamps/status only when useful.
- **Resize Handle:** feedback/review textareas resize vertically within defined minimum and maximum heights.

## 6. Motion rules

- GSAP is shared by all three surfaces, with stronger storytelling on customer pages and brief feedback on admin/staff.
- Reuse the existing GSAP registration and utilities. Do not create a second scroll engine or register duplicate plugins.
- Customer motion may use GSAP, ScrollTrigger, ScrollToPlugin, SplitText, Flip, Draggable and Lenis selectively.
- Use transforms and opacity for performance. Avoid layout-thrashing scroll handlers.
- Animations must explain hierarchy, reveal a chapter, confirm an action or clarify a state change.
- Customer blue surfaces use layered stars behind content: near/mid/far depth, drift, twinkle, pointer parallax and occasional comets without affecting document flow.
- Admin/staff motion is brief: panel/modal transitions, coordinated dashboard updates, room matrix FLIP and button feedback.
- Never animate a sticky payment/booking summary with scroll merely for decoration.
- Preserve keyboard, touch and responsive behavior. Provide a sensible reduced-motion path for functional movement.
- Three.js is intentionally not implemented until explicitly approved.

## 7. Authentication, email and account rules

- Customer login accepts email/password and Google OAuth. Admin/staff login uses username/password only.
- Google users are stored as verified customers with normalized email, stable `google_id` and optional `avatar_url`.
- Never allow a Google customer flow to link to an admin/receptionist account or to two different customer rows.
- Password managers use native autocomplete; the application must not store plaintext passwords in its own cookies or local storage.
- Login, registration and OTP endpoints are rate limited. Sessions and cookies use appropriate lifetime, HttpOnly, SameSite and production Secure settings.
- Registration and password reset validate email/phone/password and display one inline message per invalid field.
- SMTP credentials belong to the system sender, not the registering customer. Gmail SMTP requires a valid App Password when used with password authentication.

## 8. Booking, payment and operational logic

- Same-day check-in cannot be selected after 17:00. Communicate that check-in is available from 12:00 to 17:00.
- A booking may reserve capacity greater than the entered guest count. It must reject selected room capacity lower than the guest count with one clear inline/animated notice.
- Payment holds expire after 30 minutes.
- Checkout late fee starts only after the scheduled checkout time plus one-hour grace and equals 50% of one room-night rate, not 50% of the booking total.
- Stay extension supports hourly extension at 200,000 VND/hour and daily extension at the normal nightly room price.
- Customer booking views show one clear booking status, not several overlapping status badges.
- Payment supports configured online methods and cash where the business flow permits it. Steps before payment may be revisited to edit information safely.
- Live availability, booking, payment and room status come from transactional database queries/tools, never stale vector-store content.
- Use optimistic UI only with rollback on server failure. Debounce or abort superseded searches and keep meaningful filters synchronized with the URL.

## 9. Backend and security rules

- Enforce authorization on the server for customer ownership, admin role and staff capabilities. Hiding a UI control is never sufficient authorization.
- Use validation and parameterized Eloquent/query-builder operations. Do not concatenate untrusted SQL.
- Protect state-changing web routes with CSRF and apply rate limits to authentication, OTP and abuse-prone endpoints.
- Secrets live only in `.env`/server secret storage. `.env.example` contains placeholders only.
- Production uses HTTPS and HSTS with secure cookies. Local HTTP remains supported for development.
- Prevent duplicate payments, refunds, checkout and booking mutations with transactions, status preconditions and idempotent checks.
- RLS applies only when the selected database platform supports it; otherwise enforce equivalent ownership/role policy in Laravel and document the limitation.
- Run dependency audits and targeted security tests after dependency or authentication changes.

## 10. Chatbot and RAG rules

- The chatbot should converse naturally, not force users into a fixed list of questions.
- Stable hotel knowledge such as policies, amenities and FAQs belongs in retrieval documents.
- Volatile facts such as availability, price, payment and a customer's bookings must be obtained from authorized live tools.
- Booking tools derive customer identity from the authenticated session, never from a model-supplied user ID.
- Never request or repeat passwords, OTPs, card data or secrets.
- Responses distinguish retrieved hotel facts from general conversation and state uncertainty instead of inventing operational data.
- Keep the customer chatbot icon-only when closed; opening/minimizing returns to the same launcher rather than creating duplicate icons.

## 11. Skills and external references

Use these references selectively; Royal Hotel's accepted rules override generic repository advice.

- Local skills: `royal-hotel-design`, `ponytail`.
- GSAP skills: <https://github.com/greensock/gsap-skills>
- GSAP documentation: <https://gsap.com/docs/v3/>
- Taste audit framework: <https://github.com/leonxlnx/taste-skill>
- Minimal coding/YAGNI reference: <https://github.com/dietrichgebert/ponytail>
- Letters visual/layout reference: <https://letters.app/>
- Refero layout reference: <https://styles.refero.design/>
- ElevenLabs internal portal reference: <https://elevenlabs.io/app/home>
- Cloning/extraction workflow reference: <https://github.com/JCodesMore/ai-website-cloner-template>
- Security testing reference: <https://github.com/usestrix/strix>
- Animated icon reference: <https://animate-ui.com/docs/icons>
- Interaction naming/anatomy reference: <https://namethatui.com/>
- Future experiential reference, without approved Three.js implementation: <https://www.doordennis.nl/>

Do not copy third-party brand assets, medical copy or proprietary layouts. Extract structural principles and adapt them to Royal Hotel.

## 12. Definition of done

A change is complete only when applicable checks pass:

- Correct visual hierarchy at desktop and mobile breakpoints.
- Keyboard and screen-reader semantics for new interactive controls.
- No duplicate validation, status or notification output.
- No console errors, broken routes or missing assets.
- Booking/search/payment refresh and Back/Forward behavior remain coherent.
- Relevant Laravel tests pass; frontend production build passes when CSS/JS changes.
- Database migration is reversible and applied when schema changes.
- Secrets remain server-only.
- Durable decisions are added to the relevant document instead of relying on conversation memory.
