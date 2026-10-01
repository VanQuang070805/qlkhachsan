# Royal Hotel — Workspace Rules

## 1. Canonical Documentation & Precedence
Always follow the newest explicit user instruction first, then [`docs/PROJECT_RULES_AND_SKILLS.md`](../../docs/PROJECT_RULES_AND_SKILLS.md) and [`docs/AGENTS.md`](../../docs/AGENTS.md), alongside:
- [`docs/DESIGN.md`](../../docs/DESIGN.md) & [`docs/DESIGN_REFERENCE.md`](../../docs/DESIGN_REFERENCE.md) (Customer & shared visual system, Letters reference, GSAP/Lenis motion)
- [`docs/ADMIN_DESIGN_PLAN.md`](../../docs/ADMIN_DESIGN_PLAN.md) (Admin & Staff workspace shell and IA)
- [`docs/EXPERIENCE_AND_DATA_ARCHITECTURE.md`](../../docs/EXPERIENCE_AND_DATA_ARCHITECTURE.md), [`docs/RAG_ARCHITECTURE.md`](../../docs/RAG_ARCHITECTURE.md) & [`docs/CHATBOT_PLAN.md`](../../docs/CHATBOT_PLAN.md) (Vue islands, RAG, KPI snapshots)
- [`docs/SECURITY_AUDIT.md`](../../docs/SECURITY_AUDIT.md) & [`docs/GOOGLE_OAUTH_SETUP.md`](../../docs/GOOGLE_OAUTH_SETUP.md)

## 2. Required Delivery Cycle (Ponytail / YAGNI)
1. **Inspect**: Read routes, controllers, models, migrations, Blade views, CSS, and JS before changing any workflow.
2. **Implement**: Build the smallest complete solution reusing existing components, tokens, and utilities.
3. **Verify**: Check desktop/mobile layouts, keyboard/ARIA semantics, and run `php artisan test --compact`, `npm run build`, and `php artisan view:cache`.
4. **Fix & Re-verify**: Fix regressions and re-run checks before reporting completion.

## 3. Non-Negotiable Product & Domain Rules
- **Surface Boundaries**: Keep Customer (`customer_user_id`), Staff (`staff_user_id`, `role:receptionist,admin`), and Admin (`role:admin`) sessions, routes, and layouts strictly separated.
- **Customer Visuals**: Keep `Inter` typography, the Royal blue hero gradient (`#779bc1 → #9abfda → #cbdcec`) with ambient stars, borderless-at-top frosted scroll header (no top-left brand on guest pages), white content canvases, and `#2597d0` strictly as a micro-accent (never as a button fill). Do not replace the blue hero with landscape photography unless explicitly asked.
- **Internal Portals**: Persistent collapsible sidebar and topbar, light-only theme (`data-theme="light"`), scannable data tables and room matrix with FLIP transitions.
- **Booking & Payment Logic**:
  - Check-in: `12:00–17:00`; block same-day check-in at/after `17:00`.
  - Room capacity (`max_guests`) must be `>=` total guests; unpaid `pending` holds expire after `10` minutes and are cancelled by the backend.
  - Late checkout fee starts after `13:00` (12:00 + 1h grace) at **50% of one room-night rate** (not 50% of stay total).
  - Stay extension: `200,000 VND/hour` or normal nightly rate per day.
  - Room status cannot jump from `occupied` to `cleaning` to bypass checkout settlement.
- **Chatbot & Security**:
  - Live availability, prices, and bookings must come from server tools, never guessed or read from stale vector data.
  - `get_booking_for_user` must read `customer_user_id` from the session, never from model arguments.
  - Never expose secrets, API keys, OTPs, or credentials in Blade, JS, logs, or Git.
- **Knowledge Graph (`graphify`)**:
  - Skill: [`.agents/skills/graphify/SKILL.md`](../skills/graphify/SKILL.md) (`https://github.com/Graphify-Labs/graphify`).
  - When the user types `/graphify` or asks for codebase/knowledge graph analysis, follow `.agents/skills/graphify/SKILL.md` to extract AST + semantic nodes/edges (`EXTRACTED`, `INFERRED`, `AMBIGUOUS`), cluster communities, and output `graphify-out/graph.html`, `graphify-out/graph.json`, `graphify-out/obsidian/`, and `graphify-out/GRAPH_REPORT.md`.
