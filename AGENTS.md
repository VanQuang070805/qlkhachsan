# Project-wide instructions

The canonical agent instructions are stored in [`docs/AGENTS.md`](docs/AGENTS.md) and [`docs/PROJECT_RULES_AND_SKILLS.md`](docs/PROJECT_RULES_AND_SKILLS.md). Read both files before working in this repository.

## Workspace Rules & Skills (`.agents/`)

- **Rule file**: [`.agents/rules/royal-hotel-rules.md`](.agents/rules/royal-hotel-rules.md)
- **Workspace skills**:
  - [`royal-hotel-design`](.agents/skills/royal-hotel-design/SKILL.md) — Visual system, tokens, Customer/Admin/Staff surfaces, shared interaction primitives, and Taste Skill audit rules (`docs/DESIGN.md`, `docs/DESIGN_REFERENCE.md`, `docs/ADMIN_DESIGN_PLAN.md`).
  - [`ponytail`](.agents/skills/ponytail/SKILL.md) — Minimal coding / YAGNI discipline and required `inspect → implement → verify → fix → verify again → finish` delivery cycle.
  - [`royal-gsap-motion`](.agents/skills/royal-gsap-motion/SKILL.md) — GSAP v3 (`ScrollTrigger`, `ScrollToPlugin`, `SplitText`, `Flip`, `Draggable`) and `Lenis` motion rules for `resources/js/app.js` and `resources/js/internal.js`.
  - [`royal-ops-security-rag`](.agents/skills/royal-ops-security-rag/SKILL.md) — Booking/payment/checkout business logic, Customer vs Staff/Admin session boundaries, Google OAuth, Royal Concierge RAG (`RoyalKnowledgeService`, `ChatbotController`), and QA verification commands.
  - [`graphify`](.agents/skills/graphify/SKILL.md) — Knowledge graph extraction, community clustering, HTML/Obsidian visualization, and audit report generation (`graphify-out/`). Trigger: `/graphify`.
  - [`antislop`](.agents/skills/antislop/SKILL.md) — Mandatory anti-slop rules (R-01 to R-38), Liveliness Toolkit, and human-centered design filters to prevent generic AI UI, placeholder copy, and repetitive layout patterns.

