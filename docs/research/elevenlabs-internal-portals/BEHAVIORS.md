# Behaviors

- Sidebar collapse: click-driven, persisted in localStorage.
- Theme: click-driven dark/light, persisted in localStorage.
- Command palette: Cmd/Ctrl+K, Escape closes, focuses global actions.
- Page entry: restrained y 8px + fade; disabled for reduced motion.
- Room search: 180ms debounce. Latest refresh request owns an AbortController.
- Filters: synchronized with URLSearchParams using replaceState; popstate restores controls.
- Room mutation: UI remains responsive; refresh rollback is triggered on request failure.
- Empty/error states: visible text, no silent blank panels.
