---
name: ponytail
description: >-
  Use this skill when planning, implementing, refactoring, or debugging code in Royal Hotel.
  Enforces the Ponytail / YAGNI minimal engineering discipline and Royal Hotel's required
  inspect-implement-verify delivery cycle.
---

# Ponytail — Minimal Engineering & Delivery Workflow

## 1. Required Delivery Cycle

Follow this sequence on every task without skipping steps:
1. **Inspect & Understand**: Read existing routes (`routes/web.php`), controllers, models, database schema/migrations, Blade components, CSS, and JS before proposing or writing changes.
2. **Implement**: Write the smallest complete solution that solves the actual problem.
3. **Verify**: Run automated tests (`php artisan test --compact`), asset build (`npm run build`), view compilation (`php artisan view:cache`), and check responsive UI/edge cases.
4. **Fix**: Resolve any regressions or lint/test failures discovered during verification.
5. **Re-verify & Finish**: Re-run verification before reporting completion and record durable decisions in `docs/PROJECT_RULES_AND_SKILLS.md`.

## 2. Core Principles (YAGNI)

- **Reuse Before Creating**: Use existing Laravel conventions, Eloquent models, shared Blade partials (`resources/views/client/partials/`, `resources/views/layouts/partials/`), CSS tokens, and JS utilities (`resources/js/app.js`, `resources/js/internal.js`, `resources/js/date-picker.js`) instead of introducing parallel systems or new dependencies.
- **Subtract Noise**: Remove redundant cards, duplicate headings, explanatory filler copy, duplicate status badges, and double validation banners when they obstruct the user's task.
- **Preserve Contracts**: Never break existing route names, form field names, database column compatibility (`booking_date` vs `created_at`), browser Back/Forward (`pageshow` / `popstate`), page refresh, or session context boundaries.
- **Progressive Enhancement**: Keep server-rendered Laravel Blade as the backbone; mount Vue 3 only as targeted islands (such as `RoyalChat.vue`) where stateful client interaction warrants it.
