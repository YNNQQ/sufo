# Design Tokens & CSS Architecture

All styling lives in two files: `assets/css/colors.css` (color tokens + schemes — see [color-scheme-system.md](color-scheme-system.md)) and `style.css` (everything else). No preprocessor, no CSS-in-JS, no build step — plain CSS with custom properties, `@import`, and modern selectors (`:has()`, `color-mix()`, nesting-free but heavily using descendant/adjacent combinators).

`style.css` is organized with `/* #region NAME */ ... /* #endregion */` comment markers (readable as folding regions in most editors) and is **mirrored twice**: once for desktop, once inside `@media (max-width: 786px)` (style.css:1351 onward) with the same region names repeated for mobile overrides. When editing a token or component style, check whether the mobile media-query block needs a matching override.

## Region map (desktop, `style.css`)

| Region | Lines | Contents |
|---|---|---|
| IMPORTS & TOKENS | 11-124 | `@import colors.css`, `@font-face` (Denim 400/500), all `:root` custom properties, `@keyframes opacity` |
| RESET & BASE | 127-247 | Universal box-sizing reset, body font stack, `.page-wrap`, image/video defaults, autofill background removal |
| TYPOGRAPHY | 250-327 | `h1`-`h6` / `.h1`-`.h6` scale, `.nav-menu a` |
| LAYOUT | 330-466 | Grid system (`[class*="grid--"]`, `.grid--12`), section structure & scheme-merging rules (see [color-scheme-system.md](color-scheme-system.md)), header/footer container padding |
| NAVIGATION | 469-675 | `.header`, `.header-logo`, `.footer`, footer grid columns, newsletter form styling |
| COMPONENTS | 679-1092 | Buttons, `.island`/`.card`/`details` glass-morphism base, dropdowns, `.material-picker`, `.object-bar`/`.object-picker` (see [object-bar-component.md](object-bar-component.md)) |
| SECTIONS | 1096-1289 | Per-`section--*` styling (see [section-rendering-system.md](section-rendering-system.md)) |

Then two `@media` breakpoints: `max-width: 1200px` (style.css:1292-1348 — sticky figure columns, material grid reflow, primary nav horizontal-scroll-with-fade treatment) and `max-width: 786px` (style.css:1351+ — full region remap for mobile).

## Token scale (`:root`, style.css:31-106)

```
Typography   --fs-xl/lg/base/sm/xs   60/36/20/15/12px
             --fw-rg/md              400 / 500
             --lh-tight/snug/default 1.1 / 1.2 / 1.33
             --ls-default/wide       0 / 0.02em

Rhythm       --rhythm-block  fs-xl * lh-tight        (vertical space after headings)
             --rhythm-flow   fs-base * lh-default     (space after body text)
             --rhythm-tight  fs-sm * lh-default        (space after h5)
             --rhythm-loose  rhythm-block * 1.2

Spacing      --space-1..5, -10, -15, -20   6/12/18/24/30/60/90/120px  (6px base unit, roughly *1x steps then jumps to *10/15/20)
             --space-px-15/20/40           15/20/40px  (pixel-named exceptions to the 6px scale)

Widths       --width-1..11, -15, -20       60px increments up to 660px, then 900/1200px  (content max-widths, e.g. paragraph/heading constraints)

Radius       --radius-sm/md/lg/xl          4/5/8/12px

Section pad  --section-pad-md/lg           space-10 (60px) / space-15 (90px)

Component    --header-height    space-px-40 + space-5 (70px)
             --shadow-default   2px 2px 20px 0 rgb(135 134 131 / 6%)
             --backdrop-blur/-scrim  6px / 16px

Animation    --animation-fast/base/slow   200ms / 300ms / 400ms, all `ease`
```

When building new UI, reuse these tokens rather than introducing new magic numbers — nearly every existing spacing/sizing value in the theme traces back to one of them. The JS layer also references `--animation-fast` numerically (as `200` in `setTimeout` calls) in a few places — see [javascript-patterns.md](javascript-patterns.md) — so if `--animation-fast` changes, those JS timeouts should be updated to match.

## Core reusable components

- **`.button`** (style.css:712-742) — the universal button reset (`all: unset` + flex layout), also applied to `.island li`, `.island .label`, `.dropdown__toggle`, and the MailerLite submit button so they all share sizing/padding.
- **`.icon`** (style.css:744-759) — 14×14px flex container for injected SVGs (see [icon-injection-system.md](icon-injection-system.md)).
- **`.island` / `.card` / `details`** (style.css:775-892) — the shared "glassy panel" look: translucent white background + blur backdrop-filter + border + shadow, with an `@supports not (backdrop-filter)` fallback to a solid background. `.island` is the base for the nav bars, dropdown panels, and the object bar. `.island__highlight` is the animated pill used by the nav-highlight JS (see [javascript-patterns.md](javascript-patterns.md)).
- **`.dropdown`** (style.css:894-910) — generic toggle+panel dropdown styling, specialized by `.object-picker` for the object bar (see [object-bar-component.md](object-bar-component.md)).
- **`.material-picker`** (style.css:915-953) — swatch-image + title/subtitle button used inside `section--material`.
- **`.tag`** (style.css:761-772) — small bordered pill, shared with `.wp-block-button.is-style-outline`.

## Gotchas specific to this codebase

- `.glassy` fixed 5px strip at the very bottom of the page (footer.php:3) with a suspicious `z-index: 8675309` (a "Jenny" pop-culture reference number, not a meaningful stacking value) — sits beneath the footer, purpose is a subtle bottom-edge blur/vignette treatment tied to `.glassy`'s backdrop-filter rules.
- `main` has a negative `margin-top` equal to `-header-height` (style.css:169) and `.footer` does the same (style.css:530) — both are designed to slide *under* the sticky header/into the footer for the scroll-reveal and color-scheme-transition effects; don't "fix" these margins without understanding the sticky-header overlap they're producing.
