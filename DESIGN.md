---
name: Letters — Sanctuary System
colors:
  surface: '#f9f9f9'
  surface-dim: '#dadada'
  surface-bright: '#f9f9f9'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f3f3f3'
  surface-container: '#eeeeee'
  surface-container-high: '#e8e8e8'
  surface-container-highest: '#e2e2e2'
  on-surface: '#1a1c1c'
  on-surface-variant: '#47464a'
  inverse-surface: '#2f3131'
  inverse-on-surface: '#f1f1f1'
  outline: '#78767b'
  outline-variant: '#c8c5cb'
  surface-tint: '#5f5e61'
  primary: '#000000'
  on-primary: '#ffffff'
  primary-container: '#1c1b1e'
  on-primary-container: '#858386'
  inverse-primary: '#c8c5c9'
  secondary: '#5d5d69'
  on-secondary: '#ffffff'
  secondary-container: '#e0deec'
  on-secondary-container: '#62626e'
  tertiary: '#000000'
  on-tertiary: '#ffffff'
  tertiary-container: '#001e2e'
  on-tertiary-container: '#098cc5'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#e5e1e5'
  primary-fixed-dim: '#c8c5c9'
  on-primary-fixed: '#1c1b1e'
  on-primary-fixed-variant: '#474649'
  secondary-fixed: '#e3e1ef'
  secondary-fixed-dim: '#c6c5d3'
  on-secondary-fixed: '#1a1b25'
  on-secondary-fixed-variant: '#454651'
  tertiary-fixed: '#c8e6ff'
  tertiary-fixed-dim: '#86ceff'
  on-tertiary-fixed: '#001e2e'
  on-tertiary-fixed-variant: '#004c6d'
  background: '#f9f9f9'
  on-background: '#1a1c1c'
  surface-variant: '#e2e2e2'
typography:
  display-hero:
    fontFamily: Inter
    fontSize: 64px
    fontWeight: '600'
    lineHeight: 60px
    letterSpacing: -0.04em
  display-hero-mobile:
    fontFamily: Inter
    fontSize: 40px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.04em
  headline-lg:
    fontFamily: Inter
    fontSize: 44px
    fontWeight: '500'
    lineHeight: 46px
    letterSpacing: -0.04em
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '500'
    lineHeight: 34px
    letterSpacing: -0.03em
  headline-md:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '500'
    lineHeight: 32px
    letterSpacing: -0.03em
  headline-sm:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '500'
    lineHeight: 24px
    letterSpacing: -0.02em
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
    letterSpacing: -0.01em
  body-md:
    fontFamily: Inter
    fontSize: 15px
    fontWeight: '400'
    lineHeight: 24px
    letterSpacing: -0.01em
  body-sm:
    fontFamily: Inter
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 20px
    letterSpacing: 0em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.04em
  label-sm:
    fontFamily: Inter
    fontSize: 10px
    fontWeight: '600'
    lineHeight: 12px
    letterSpacing: 0.08em
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 3rem
  margin-mobile: 1.25rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
  space-2xl: 4rem
---

## Brand & Style

This design system embodies a serene, clinical luxury hospitality sanctuary. The aesthetic fuses Japanese architectural minimalism with high-end editorial restraint, cultivating an atmosphere of radical quietude, discretion, and effortless precision. It addresses an audience of discerning travelers, private estate guests, and wellness connoisseurs who seek clarity over decoration and understated sophistication over overt ornamentation.

The visual style is characterized by:
- **Pristine Monochromatic Discipline**: Grounded by an absolute obsidian anchor (`#070709`) contrasting against stark paper-white canvases and airy cloud surfaces.
- **Architectural Air & Atmospheric Depth**: Generous negative space, expansive card paddings, and ethereal sky-tinted ambient shadows that mimic natural daylight filtering through frosted skylights.
- **Pure Pill Geometries**: Interaction points are shaped as sculptural, high-radius obsidian pills, lending tactile softness to an otherwise rigid, precise grid.
- **Restrained Micro-Accents**: Surgical blue appears exclusively as needle-thin structural accents and communicative strokes, strictly avoiding mass fill usage to preserve calm.

## Colors

The palette is engineered to create a calming, clinical luxury hospitality environment. Color values are strictly codified into functional layers:

### Primary & Dark UI Elements
- **Obsidian (`#070709`)**: The primary brand anchor. Used for high-impact filled CTA pill buttons, primary active titles, navigation iconography, and dark UI surfaces.
- **Ink (`#151515`)**: Used for primary display headings, major section titles, and structural high-contrast hairpins.

### Canvas & Surfaces
- **Paper White (`#ffffff`)**: The primary canvas surface and elevated card backgrounds. Represents pure, undisturbed architectural plane.
- **Cloud Gray (`#f5f5f5`)**: Secondary surface tier, alternating section backdrops, subtle card groupings, and recessed interaction containers.
- **Sky Tint (`#d7e6f5`)**: An ethereal, cool blue undertone utilized exclusively for delicate atmospheric surface washes and shadow blending tints.

### Text & Neutral Midtones
- **Charcoal (`#60606c`)**: The primary body copy color, offering maximum readability without the harshness of pure black. Also applied to muted hairline borders.
- **Slate (`#8b8b8b`)**: Tertiary copy, metadata labels, footnotes, timestamps, and inactive component boundaries.

### Accents & Gradients
- **Surgical Blue (`#2597d0`)**: Reserved strictly for micro-accents, 1.5px icon strokes, active status indicators, and punctuation marks. It is strictly forbidden to use Surgical Blue as button fills or large planar surfaces.
- **Atmospheric Sky Gradient (`linear-gradient(180deg, #779bc1 0%, #9abfda 58%, #cbdcec 100%)`)**: Restricted solely to hero backgrounds and full-bleed retreat presentation canvases.

## Typography

The typographic hierarchy is rooted in geometric precision and tight tracking, yielding an editorial, architectural finish. 

- **Display & Headings**: Rendered with ultra-tight tracking (`-0.04em` to `-0.03em`) and razor-sharp vertical compression (line heights ranging between `0.90` and `1.10`). This creates a monolithic, sculpted feel across titles.
- **Body Text**: Tuned for effortless readability in hospitality suites, set with `-0.01em` kerning and open line heights (`1.55` to `1.60`) to allow natural breathing room between lines.
- **Editorial Signature Accents**: For private suite signatures, welcome correspondence, and authentic human touches, an expressive casual script font (such as Caveat or Kalam) may be incorporated strictly as a sporadic, decorative flourish—never for UI actions or navigational elements.

## Layout & Spacing

This design system uses a strict 12-column fluid grid system bounded by maximum content wrappers (1360px on desktop) to preserve tranquility and prevent uncontrolled stretching.

### Grid Rhythm & Adapters
- **Desktop (1024px and above)**: 12-column grid with `1.5rem` (24px) gutters and a minimum outer margin of `3rem` (48px) to frame the interface in tranquil negative space.
- **Tablet (768px - 1023px)**: 8-column grid with `1.25rem` (20px) gutters and `2rem` (32px) margins.
- **Mobile (below 768px)**: 4-column layout with `1rem` (16px) gutters and `1.25rem` (20px) safe margins.

### Vertical Cadence
Section boundaries employ large vertical spacing intervals (`space-2xl` / 64px to 120px) to simulate spatial pauses found in physical luxury architecture.

## Elevation & Depth

Visual hierarchy eschews heavy borders in favor of diffuse, atmospheric shadows tinted with Sky Tint blue undertones. Depth mimics natural skylight cascading through serene spatial enclosures:

- **Surface Level 0 (Ground Canvas)**: `#ffffff` (Paper White) or `#f5f5f5` (Cloud Gray) flat surface. No shadow.
- **Surface Level 1 (Signature Atmospheric Cards)**: `#ffffff` surfaces elevated over `#f5f5f5` backdrops using a signature 3-tier ultra-soft drop shadow tinted with deep sapphire and sky tones:
  `box-shadow: 0 17px 37px rgba(16, 55, 132, 0.03), 0 67px 67px rgba(16, 55, 132, 0.02), 0 150px 90px rgba(16, 55, 132, 0.01);`
- **Surface Level 2 (Floating Action Pills & Popovers)**: `#070709` buttons and active floating pill elements utilize an exclusive 4-layer obsidian drop shadow:
  `box-shadow: 0 4px 6px rgba(7, 7, 9, 0.06), 0 10px 15px rgba(7, 7, 9, 0.08), 0 20px 25px rgba(7, 7, 9, 0.05), 0 30px 40px rgba(7, 7, 9, 0.03);`
- **Hairline Outlines**: Subordinate interactive surfaces and ghost containers leverage a single 1px hairline border in `#60606c` at 12% opacity (`rgba(96, 96, 108, 0.12)`).

## Shapes

The design system is based on roundedness level 3, balancing full-radius pill silhouettes with soft architectural containers:

- **Interactive Pills (100px / Full Radius)**: All interactive buttons, status tags, pill tabs, filters, and chips adopt an uncompromising `100px` (or `9999px`) border radius. This creates smooth, ergonomic touch points.
- **Architectural Cards (18px Radius)**: Structural cards, imagery containers, and content blocks employ an exact `18px` border radius (`1.125rem`). This provides a gentle contour that frames retreat imagery cleanly without appearing toy-like.
- **Text Inputs & Form Cells**: Softened with a matching `12px` to `18px` perimeter or configured as full-pill capsules when standalone.

## Components

### Buttons
- **Primary Action**: Full pill shape (`border-radius: 100px`), solid Obsidian (`#070709`) background, pure white text, medium weight, with the multi-layer Obsidian drop shadow. On hover, translates -1px upward with heightened atmospheric shadow.
- **Ghost Action**: Full pill shape with a crisp 1px border in Charcoal (`rgba(96, 96, 108, 0.25)`), transparent background, Obsidian text. On hover, fills with Cloud Gray (`#f5f5f5`).
- **Surgical Micro-Action**: Text-only or icon-accompanied link styled in Obsidian with a `1.5px` Surgical Blue (`#2597d0`) terminal dot or micro icon stroke.

### Cards & Sanctuary Tiles
- Constructed with an `18px` border radius on Paper White (`#ffffff`).
- Internal padding is strictly spacious, set between `40px` and `48px` (`2.5rem` to `3rem`) to evoke unhurried luxury.
- Integrated with the signature 3-tier Sky Tint shadow (`rgba(16, 55, 132, 0.03)`).
- Borders remain invisible unless placed over stark white canvases, where a 1px border of `rgba(96, 96, 108, 0.08)` is permissible.

### Chips & Filter Tags
- Height: 36px; padding: 0 18px; `border-radius: 100px`.
- Inactive: Background `#f5f5f5`, text `#60606c`, no border.
- Active: Background `#070709`, text `#ffffff`.
- Optional status pill indicator features a 6px circular dot in Surgical Blue (`#2597d0`).

### Input Fields & Booking Selectors
- Standard inputs: Background `#ffffff`, border `1px solid rgba(96, 96, 108, 0.20)`, border radius `14px`, 16px vertical padding by 20px horizontal padding.
- Focus state: Border color changes to `#070709` with a subtle outer glow tinted by Sky Tint (`rgba(215, 230, 245, 0.8)`).
- Placeholder copy is set in Slate (`#8b8b8b`).

### Checkboxes & Radios
- Checkboxes: 20px square with a 6px rounded corner. Unchecked: `1.5px solid #8b8b8b`. Checked: Solid `#070709` with a white checkmark.
- Radio buttons: 20px diameter circles. Checked: Solid `#070709` outer ring with a 6px inner dot in Surgical Blue (`#2597d0`).

### Editorial Reservation Ribbon (Custom Component)
- A floating, bottom-anchored or hero-docked glass pill housing calendar, room suite choice, and guest count.
- Encased in a `100px` capsule with frosted Paper White backing (`rgba(255, 255, 255, 0.85)` + 20px backdrop-blur) and an embedded Obsidian primary pill CTA on the far right.