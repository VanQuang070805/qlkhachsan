---
name: royal-hotel-design
description: >-
  Use this skill whenever designing, restyling, or auditing customer, admin, or staff UI,
  Blade templates, CSS, or interactive components in the Royal Hotel repository. Enforces
  Royal Hotel's quiet luxury visual system, color tokens, typography, surface boundaries,
  shared interaction primitives, and Taste Skill audit standards.
---

# Royal Hotel — Design System & Interaction Primitives

Before making UI or visual changes, cross-reference:
- [`docs/DESIGN.md`](../../../docs/DESIGN.md)
- [`docs/DESIGN_REFERENCE.md`](../../../docs/DESIGN_REFERENCE.md)
- [`docs/ADMIN_DESIGN_PLAN.md`](../../../docs/ADMIN_DESIGN_PLAN.md)
- [`docs/PROJECT_RULES_AND_SKILLS.md`](../../../docs/PROJECT_RULES_AND_SKILLS.md)

## 1. Three Product Surfaces

1. **Customer (`resources/views/client/`, `resources/css/client*.css`, `customer-pages.css`)**
   - Calm, immersive, editorial hospitality sanctuary inspired by Letters (`letters.app`).
   - Top surfaces use the Royal gradient `linear-gradient(180deg, #779bc1 0%, #9abfda 58%, #cbdcec 100%)` with rounded framing and a fine white edge, transitioning to a stark white (`#ffffff`) / cloud (`#f5f5f5`) canvas below.
   - Homepage first viewport holds header, hero, and availability search together. Interior customer pages use a compact framed blue hero followed by white sections.
   - Do **not** replace the blue welcome surface with landscape photography unless explicitly requested.
   - Customer header has **no** brand wordmark at the top left; it is transparent over the blue hero and gains a frosted blur when scrolled (`> 48px`).
   - Use genuine 1920px room interior photography from `config/room_images.php` matching each room tier; never mix exterior property photos into room galleries or place text captions over gallery images.

2. **Admin (`resources/views/admin/`, `resources/views/layouts/admin.blade.php`, `resources/css/internal.css`)**
   - Operational and analytical workspace with a persistent collapsible sidebar (`248px` expanded / `72px` collapsed) and top utility bar.
   - Light-only interface (`data-theme="light"`) to prevent theme flashes and low-contrast states.
   - Dashboard acts as an interactive report (coordinated filters, KPI strip: Revenue, ADR, RevPAR, Occupancy, Bookings, and Chart.js charts).

3. **Staff / Front Desk (`resources/views/staff/`, `resources/views/layouts/dashboard.blade.php`)**
   - Shares the internal shell and icon language with Admin, scoped by role middleware (`role:receptionist,admin`).
   - Focused on fast room matrix scanning (search, floor tabs, available/occupied/cleaning states), check-in, checkout, stay extension, refund, and VietQR workflows.

## 2. Core Tokens & Typography

- **Typeface**: `Inter` across all surfaces (`-0.04em` to `-0.03em` tracking on headings; tabular numerals for metrics).
- **Palette**:
  - Obsidian: `#070709` (primary pill CTAs, active chips, strong actions)
  - Ink: `#151515` (headings)
  - Paper White: `#ffffff` / Cloud Gray: `#f5f5f5` / Warm Surface: `#f9f9f9`
  - Sky Tint: `#d7e6f5` (ambient shadow wash)
  - Charcoal: `#60606c` (body copy) / Slate: `#8b8b8b` (muted labels/metadata)
  - Surgical Blue: `#2597d0` (micro-accents, 1.5px strokes, status dots only — **never** use as a button fill or large surface)
  - Royal Blue Gradient: `#779bc1 → #9abfda → #cbdcec`
- **Geometry**:
  - 8pt spacing grid; max content width `1360px` on customer desktop (`12`-col desktop / `8`-col tablet / `4`-col mobile).
  - Full-pill radius (`100px` / `9999px`) for buttons, chips, filter tags, and the reservation ribbon.
  - `18px` radius for architectural cards on customer pages; `12px–16px` panels on internal portals.

## 3. Approved Interaction Primitives

- **Traffic Lights (`.window-controls`)**: Expose only supported actions. Standard modals render only the red close control; omit yellow/green when minimize/zoom is unsupported. Render in Blade when known at render time.
- **Date Picker (`resources/js/date-picker.js`)**: Always use `enhanceDatePickers()` accessible popover calendar with civil `yyyy-mm-dd` values, Monday-first grid, range highlights, and keyboard navigation (`Arrow` keys, `PageUp`/`PageDown`, `Escape`).
- **Booking Steps (`resources/views/client/partials/booking-steps.blade.php`)**: Ordered list (`Chọn phòng`, `Thông tin`, `Thanh toán`) with exactly one `aria-current="step"`, checkmarks on completed links, and muted future steps.
- **Pagination (`resources/views/pagination/royal.blade.php`)**: Semantic `<nav aria-label="Phân trang">`, one `aria-current="page"`, disabled range ends, non-interactive ellipsis, no "Showing 1 to 10…" text.
- **Status Dot**: Solid core + surface separation ring + visible text label (never color alone).
- **Search Field**: Leading search submit button, trailing clear button (`hidden` when empty), `180ms` debounce + `AbortController` on live queries.
- **Form Field**: Real `<label for>`, `required`, single inline error linked via `aria-describedby`/`aria-invalid`. Never duplicate error banners above the form.
- **Chat Bubble (`resources/js/components/RoyalChat.vue`)**: Icon-only blue glass launcher (`✦`) when closed; `role="log"` message list; assistant start-aligned neutral bubble, guest end-aligned obsidian bubble; three-dot typing indicator.

## 4. Selective Taste Audit Checklist

- Dials for guest pages: design variance `6/10`, motion intensity `6/10`, visual density `3/10`.
- Avoid card-on-card nesting, repeated equal card rows when an editorial layout fits better, fake UI ornaments, or trailing periods on display headlines.
- Verify desktop, tablet, and mobile breakpoints, keyboard focus, and `npm run build` before reporting completion.
