# Project-wide agent instructions

Before any work in this repository, read [`PROJECT_RULES_AND_SKILLS.md`](PROJECT_RULES_AND_SKILLS.md).

For UI, layout, or visual design work, also read [`../DESIGN.md`](../DESIGN.md) and [`../DESIGN_REFERENCE.md`](../DESIGN_REFERENCE.md) and apply them across the requested pages.

Keep Royal Hotel's palette, spacing, typography, shared components, Letters layout reference, and restrained GSAP/Lenis motion consistent while adapting to each page's purpose. Do not replace the blue hero with landscape photography unless the user explicitly asks. Preserve accessibility, responsive behavior and functional reduced-motion behavior. Verify rendered layouts at relevant breakpoints.

Follow the required delivery cycle:

1. Inspect the current implementation and related data flow.
2. Implement the smallest complete solution.
3. Verify the affected UI, logic and edge cases.
4. Fix discovered regressions.
5. Re-run the relevant verification before reporting completion.

Keep customer, admin and staff route, middleware, session and authorization boundaries explicit. Do not expose secrets or rely on hidden UI controls for authorization.
