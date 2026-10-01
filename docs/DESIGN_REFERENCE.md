# Royal Hotel — Design Reference and Motion Guide

Latest durable decisions (2026-09-25): guest header has no brand at the top left; Royal's supplied pale-blue crown/wordmark is used for authentication and internal portal branding plus favicon. The admin and staff workspace is light-only to prevent theme flashes. The customer chatbot launcher is an icon-only blue glass control without a black pill.

Shared interaction primitives (2026-09-25): customer booking uses a semantic three-stage ordered stepper; date fields use the shared accessible calendar popover while retaining civil `yyyy-mm-dd` values; internal search fields include a leading search action and a trailing clear action; modal traffic lights expose only actions the popup supports; active internal navigation uses an accessible labeled state plus a small presence dot; review comments resize vertically within bounded heights. Customer sign-in uses the official Google provider mark, password-manager autocomplete, and Google OAuth while internal staff/admin sign-in remains username/password only.

This is the persistent design reference for the Royal Hotel website. Read it before designing or restyling any page in this project. Treat the original `DESIGN.md` and the Letter/GSAP links below as references; preserve the hotel's identity and make page-specific choices that keep content clear and usable.

## Source material

- Original palette and style specification, copied into this repo: [`DESIGN.md`](./DESIGN.md); original Desktop source: `C:\Users\thinh\OneDrive\Desktop\DESIGN.md`.
- Letter visual reference: [https://letters.app/](https://letters.app/).
- GSAP skills repository: [https://github.com/greensock/gsap-skills](https://github.com/greensock/gsap-skills).
- GSAP core skill: [SKILL.md](https://raw.githubusercontent.com/greensock/gsap-skills/main/skills/gsap-core/SKILL.md).
- GSAP timeline skill: [SKILL.md](https://raw.githubusercontent.com/greensock/gsap-skills/main/skills/gsap-timeline/SKILL.md).
- GSAP ScrollTrigger skill: [SKILL.md](https://raw.githubusercontent.com/greensock/gsap-skills/main/skills/gsap-scrolltrigger/SKILL.md).
- GSAP performance skill: [SKILL.md](https://raw.githubusercontent.com/greensock/gsap-skills/main/skills/gsap-performance/SKILL.md).
- Official GSAP docs: [GSAP](https://gsap.com/docs/v3/GSAP/), [ScrollTrigger](https://gsap.com/docs/v3/Plugins/ScrollTrigger/), [ScrollToPlugin](https://gsap.com/docs/v3/Plugins/ScrollToPlugin/), [SplitText](https://gsap.com/docs/v3/Plugins/SplitText/), [Draggable](https://gsap.com/docs/v3/Plugins/Draggable/), and [Flip](https://gsap.com/docs/v3/Plugins/Flip/).
- Lenis smooth-scroll project and docs: [GitHub](https://github.com/darkroomengineering/lenis) · [Documentation](https://lenis.darkroom.engineering/).

The Letter site is a visual reference, not a request to copy its content or source code. The shared cues from the supplied screenshots are a spacious, transparent header over the hero; a centered navigation group; brand at left; login and a high-contrast pill action at right; no visible header border while at the top; and a light, frosted surface with a subtle edge and shadow after scrolling. Apply the same header states across guest-facing pages, adapting text contrast to the underlying surface.

Reference review (2026-09-22): inspected the live Letters home, Letters feature page, Transcribe feature page, and Pricing page. Reusable structural cues: generous hero with oversized centered headline and one concise supporting paragraph; clear section titles centered above content; alternating spacious white/light surfaces; feature cards and product demonstrations arranged in a few strong blocks; proof/testimonial and pricing sections separated into distinct chapters; consistent CTA and compact navigation. Use these patterns as layout inspiration only, never copy their medical content, labels, or assets. Royal Hotel's customer pages use a rounded blue-gradient first panel with a fine white outline, then transition to a white canvas for the next sections. Keep the homepage hero, floating availability form, and header together in the first viewport; below it, make the next section visibly white with a centered headline.

## Brand foundation

The visual direction is quiet luxury hospitality: Japanese-inspired restraint, architectural whitespace, precise alignment, calm surfaces, and discreet interactions. Prefer clarity and generous breathing room over decoration. Keep Royal Hotel's blue hero gradient and subtle star/dot texture as the welcome identity; do not replace it with landscape photography unless explicitly requested.

### Color tokens

| Role | Color | Use |
| --- | --- | --- |
| Obsidian | `#070709` | Primary buttons, strong actions, icons, selected states |
| Ink | `#151515` | Main headings and strong text |
| Paper | `#ffffff` | Main canvas and elevated cards |
| Cloud | `#f5f5f5` | Quiet alternate sections and recessed surfaces |
| Sky tint | `#d7e6f5` | Soft background wash and ambient shadow tint |
| Body charcoal | `#60606c` | Body copy and subdued structural lines |
| Slate | `#8b8b8b` | Metadata, labels, hints, inactive details |
| Surgical blue | `#2597d0` | Tiny status dots, strokes, links, and punctuation only; never a large fill or primary button |
| Royal blue gradient | `#779bc1` → `#9abfda` → `#cbdcec` | Reuse as the full-bleed hero and shared background across customer-facing interfaces; keep long-form content on clear, light surfaces for readability |

The supplied semantic palette also defines `surface/background #f9f9f9`, `surface-container-low #f3f3f3`, `surface-container #eeeeee`, `surface-container-high #e8e8e8`, `on-surface #1a1c1c`, `on-surface-variant #47464a`, and outline colors `#78767b` / `#c8c5cb`. Use these as supporting semantic steps where useful; preserve the core brand colors above.

### Type and layout

- Use Inter throughout customer-facing pages, including display headings, body copy, labels, forms, and navigation. Keep heading hierarchy through size, weight, and tracking rather than switching typefaces; reserve any expressive script font for rare decorative flourishes only.
- Keep headings compact with tight tracking and line-height around `0.90–1.10`; body copy should remain easy to read, around `1.55–1.60` line-height.
- Use a 1360px maximum content width. Reference rhythm: 12-column desktop, 8-column tablet, 4-column mobile; gutters 24px/20px/16px respectively. Maintain safe mobile margins around 20px.
- Use large section pauses (roughly 64–120px on desktop, reduced on mobile), consistent with the existing page rather than applying one fixed gap everywhere.
- Buttons, tabs, tags, and compact actions use full-pill radii. Structural cards and image frames use about 18px radii; larger hero surfaces can use larger radii. Keep form cells readable and responsive.

### Components and depth

- Primary action: obsidian pill, white text, medium weight, soft layered shadow; hover lift only a couple of pixels.
- Secondary action: transparent/ghost pill with a fine neutral outline; hover fills with a subtle cloud tint. Blue is only a micro-accent.
- Cards: white surfaces, spacious padding, subtle borders only when needed, and soft multi-layer shadows tinted with cool sky blue. Avoid heavy borders and harsh shadows.
- Inputs: white surface, fine neutral border, clear labels, comfortable touch targets, visible keyboard focus, and a soft sky-tinted focus ring.
- Booking ribbon: preserve the current frosted-white capsule treatment on desktop and responsive grid form on mobile. Keep all fields and the submit action visible and usable at narrow widths.
- Header: keep the guest header borderless and transparent at the top so it blends into the current canvas; over the blue hero use white navigation text, and over white content use dark text. When scrolled, show a translucent blurred white surface, fine edge and soft shadow across guest-facing pages.
- Room-detail pages reuse the homepage header component and its full blue top canvas. Never place its transparent white navigation over a white page surface; it must switch to the shared frosted header state on scroll.
- Frame the top blue panel on the homepage and other customer landing pages with a fine white edge and rounded outer corners. The home first-view frame contains the header, hero message/actions, and availability form; the following content begins on white. On interior pages, use a compact blue framed hero followed by white content sections.
- Center section titles and their short introductory copy when the section is a discovery/marketing section. Keep operational forms, data tables, and room details aligned for scanning rather than centering every control.
- Keep an experiential page rhythm: hero, one clear next step, spacious centered section intro, then a restrained grid/list; separate testimonials, location, pricing, or final CTA as distinct chapters where those sections exist.

## Motion rules

GSAP and Lenis are already part of this project. Reuse the existing setup in `resources/js/app.js`; avoid adding a second scroll engine or duplicate plugin registration.

- Use ScrollTrigger for short, one-time section/card reveals and restrained hero parallax. Keep trigger order top-to-bottom and refresh after layout/font changes.
- Use SplitText sparingly for a hero or prominent display heading, with `autoSplit` for responsive reflow and a meaningful accessible label.
- Use ScrollToPlugin for intentional in-page navigation such as the hero booking action.
- Use Flip for meaningful layout changes such as the mobile navigation opening; use Draggable only for an actual carousel or draggable control; use Flip/Draggable only where the interaction helps the visitor.
- Use Lenis with ScrollTrigger synchronization through the GSAP ticker. Avoid fighting native anchor behavior or creating nested smooth-scroll systems.
- Favor transform and opacity; use restrained duration/easing and avoid animating layout-heavy properties. Keep scroll movement calm and let content lead.
- Keep the Royal Hotel motion system enabled regardless of the operating system's `prefers-reduced-motion` setting, following the approved project preference; retain keyboard and touch controls.
- Blue guest-facing hero surfaces use a dense, layered ambient-star field driven by GSAP. Keep stars behind the content, combine near/mid/far depth, smooth drift, twinkle, pointer parallax and occasional comets without changing document flow.
- The combined Về Royal & Liên hệ page and every room detail page include clean image albums with drag, arrow keys, visible controls, slide indicators and calm autoplay with an explicit pause/play control. Do not place copy or branded captions over the images.
- Room cards and albums read their curated 1920px image set from `config/room_images.php`; the first image is also stored in `room_types.image` as the canonical cover. Match image scale and finish to each room category.

### Selective Taste Skill rules

- Reference: [Leonxlnx/taste-skill](https://github.com/leonxlnx/taste-skill). Use it as an audit framework alongside this document, with Royal's established brand rules taking precedence.
- Default dials for guest pages: design variance `6/10`, motion intensity `6/10`, visual density `3/10`.
- Prefer open editorial layouts, strong type hierarchy and real imagery. Avoid repeated equal card rows, cards nested inside cards, decorative labels over images, fake product UI and section-number ornaments.
- Use a single accent and a consistent radius family. Keep task-oriented booking and payment panels visibly grouped when that improves scanning.
- Every animation must support hierarchy, storytelling, feedback or a state change. Use transforms and opacity, clean up GSAP triggers/listeners and avoid custom window scroll calculations.
- During a redesign, preserve routes, field names, navigation labels, booking logic and factual hotel data unless a separate task explicitly changes them.

## Page-by-page application

For admin and staff pages, keep the room board focused on search, floor, and the three frequent states (available, occupied, cleaning); expose less frequent filters and actions through a compact options control while retaining true room status on each card. Use a lighter slate-blue dark theme instead of near-black, a single search control in the top bar, and account/theme/logout controls at the sidebar foot. The shared hotel pictogram comes from Lucide's licensed hotel icon, recolored to the Royal blue palette and reused in the favicon and brand mark. Internal scroll motion is brief and uses GSAP ScrollTrigger; room selection uses a soft lift without a blue outline.

Use this guide across guest-facing and management pages while preserving each page's task flow. Shared buttons, headers, forms, cards, spacing, focus states, and typography should feel like one product. Keep data-dense admin tables and management workflows functional and legible; apply the same color and type tokens without forcing the marketing hero layout onto them. Do not add animation solely for decoration.

- The guest navigation contains Trang chủ, Phòng nghỉ, and Về Royal & Liên hệ. The former standalone Giới thiệu content is part of the combined contact page, while the former standalone Tiện nghi page has been removed. Room-specific amenity data and filters remain part of the room journey.

Before finishing a visual change, check the target page at desktop, tablet, and mobile widths; check horizontal overflow, navigation, form/card alignment, keyboard focus and animation continuity where relevant; inspect the browser console and run the existing build. Avoid changing unrelated dirty files.

## Interaction component vocabulary

- Booking steps are one ordered list with three stages: Chọn phòng, Thông tin, Thanh toán. Exactly one item carries `aria-current="step"`; completed stages show a checkmark and may link backward, while upcoming stages remain muted.
- Paginated lists use a `nav` landmark labeled “Phân trang”, addressable page links, exactly one `aria-current="page"`, disabled range ends, and a noninteractive ellipsis for collapsed ranges. Keep display pages one-based and leave offset conversion to Laravel's paginator.
- Status uses a solid dot plus visible text. The dot never carries meaning alone: green is active or complete, amber is pending, red is cancelled or blocked, and gray is neutral or offline. Keep a surface-colored separation ring.
- Guest date fields use native `input[type="date"]` controls. Treat values as civil `yyyy-mm-dd` dates and calculate durations from UTC day numbers so the browser timezone cannot move a stay by one day.
- Traffic lights are functional controls on framed application surfaces. Red closes the local panel and leaves a clear restore action, yellow minimizes without discarding field state, and green uses the browser Fullscreen API; `Alt/Option + click` toggles an in-page zoom fallback. Give every control an accessible name. These controls operate the Royal panel only and never claim to close or minimize the browser itself.
- Chat messages use a single `role="log"` message list. Assistant bubbles align to the start with a neutral fill; guest bubbles align to the end with the obsidian accent. Keep bubbles below 80% width, use one tight corner as the tail on the final bubble in a run, and show a timestamp plus delivery/read status only below the latest guest message. The typing indicator is a received-style bubble with three sequenced dots and is removed as soon as the response starts.
- Animate the entrance of steps and window details with short transform and opacity sequences. State, focus, and navigation semantics must remain correct without motion.
