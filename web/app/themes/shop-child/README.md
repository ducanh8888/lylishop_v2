# shop-child

Child theme of **Botiga Free** (parent theme decision: `docs/THEME-DECISION.md`, accepted 2026-08-04). Presentation only — see the "must not contain" list in `docs/THEME-DECISION.md` section 8.

## Current state

Metadata skeleton only (`style.css`, `functions.php`). No visual styling, colors, patterns, or template overrides yet — those are blocked on the open decisions in `docs/THEME-DECISION-BRIEF.md` (primary brown color, navigation structure) and the implementation sequence in `docs/THEME-IMPLEMENTATION-PLAN.md`.

## Planned file structure (not yet created)

```text
shop-child/
├── style.css                  # theme header + eventual compiled/authored CSS
├── functions.php              # enqueue + minimal bootstrap (present)
├── README.md                  # this file
├── inc/
│   ├── enqueue.php            # split out of functions.php once it grows
│   ├── design-tokens.php      # CSS custom properties from the color/type decision
│   └── template-overrides.php # narrowly justified WooCommerce template overrides
├── patterns/                  # controlled Gutenberg block patterns
└── templates/                 # WooCommerce template overrides, only when justified
```

Directories above are created when the corresponding step in `docs/THEME-IMPLEMENTATION-PLAN.md` is actually implemented, not in advance.
