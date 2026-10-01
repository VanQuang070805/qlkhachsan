---
name: royal-gsap-motion
description: >-
  Use this skill when adding, modifying, or debugging animations, smooth scrolling,
  carousels, page transitions, or interactive motion in Royal Hotel (GSAP, ScrollTrigger,
  ScrollToPlugin, SplitText, Flip, Draggable, Lenis).
---

# Royal Hotel — GSAP & Lenis Motion Architecture

## 1. Single Registration & Entry Points

- **Customer bundle (`resources/js/app.js`)**: Registers `Draggable`, `Flip`, `ScrollToPlugin`, `ScrollTrigger`, and `SplitText`, and synchronizes `Lenis` smooth scrolling with `gsap.ticker` and `ScrollTrigger.update`.
- **Internal bundle (`resources/js/internal.js`)**: Registers `Flip` and `ScrollTrigger` for Admin and Staff portals.
- **Rule**: Never register duplicate scroll engines or duplicate GSAP plugins in inline Blade scripts. Reuse the existing setup.

## 2. Surface Motion & Anti-Lag Rules

- **Customer Surfaces**:
  - **Ambient Starfield**: Capped at **28 stars + 1 comet** per `[data-star-surface]` (`.hero`, `.page-hero`, `.search-hero`, `.editorial-hero`, `.contact-hero`, `.contact-panel`, `.auth-container`) with pointer parallax (`gsap.quickTo`). Must pause infinite star/comet tweens via `ScrollTrigger` (`onToggle`) when scrolled out of the viewport, and must not apply `will-change: transform, opacity` or `filter: blur()` to individual star spans.
  - **Page Loader (`[data-page-loader]`)**: Plays a fast (~0.5s) entrance only on the first session visit (`sessionStorage.getItem('royal-loader-seen')`), kills its `.page-loader__stars` tween in `onComplete`, and **never** intercepts `<a>` link clicks to delay page navigation.
  - **Hero & Section Reveals**: Short, one-time `ScrollTrigger` reveals (`once: true`) and `SplitText` (`autoSplit: true`, `type: 'lines'`) on prominent hero titles. Do not double-animate `.room-card` or `.review-card` inside `.section` with both `customerReveals` (`[data-reveal]`) and the `.section` loop.
  - **Image Galleries (`[data-resort-gallery]`, `.room-detail-gallery`)**: Driven by GSAP `Draggable` + button/keyboard arrow controls + dot indicators + calm autoplay (`gsap.delayedCall`) with explicit play/pause toggle.
  - **Sticky Summaries & Forms**: Never animate sticky booking or payment summaries (`.bk-card`, `.rs-card`, `.payment-summary`) on scroll or hover lift.
- **Admin & Staff Surfaces**:
  - Keep motion brief (`120ms–340ms`): metric/card batch reveal (`duration: 0.32–0.34`, `y: 12–14`) and `window.animateInternalRoomLayout` (`Flip.getState` / `Flip.from`) for the front-desk room matrix.
  - Exclude `.floor-section` room matrices from scroll-batch delays so front-desk staff see all rooms immediately.
  - Never intercept `.workspace-sidebar nav a` clicks with fade-out delays.

## 3. No Overlapping GSAP + CSS Transitions

- Do **not** attach GSAP `show.bs.modal` tweens, JS `pointerenter`/`pointerleave` card lift loops, JS button `pointerdown`/`click` scale tweens, or `.auth-card` entrance tweens when CSS transitions/keyframes (`client-layout-finish.css`, `internal-admin.css`, `auth.css`) already animate those states.
- Animate `transform` (`x`, `y`, `scale`) and `opacity`/`autoAlpha` only (never `backgroundPosition` scroll scrubs); use `clearProps: 'opacity,visibility,transform'` after entrance tweens so resting elements keep clean stacking contexts.
- Always store triggers/listeners in cleanup arrays or kill superseded tweens (`gsap.killTweensOf`, `overwrite: 'auto'`).
- Preserve full keyboard focus, touch usability, and functional `prefers-reduced-motion` paths.
- Do **not** add Three.js unless explicitly requested and approved by the user.
