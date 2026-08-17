# Color Scheme System

A themeable "scheme" concept that lets editors recolor individual sections/columns, implemented in three coordinated layers: a PHP registry, CSS custom-property sets, and a block-editor Inspector control.

## 1. PHP registry — single source of truth

`get_color_schemes()` (functions.php:99-108):

```php
function get_color_schemes(): array {
    return [
        ''                  => __('None'),
        'scheme-black'      => __('Black'),
        'scheme-grey'       => __('Grey'),
        'scheme-light-grey' => __('Light grey'),
        'scheme-dark-grey'  => __('Dark grey'),
    ];
}
```

Note: `scheme-white` exists in CSS as the implicit default but is **not** in this registry — it's what sections fall back to when no scheme class is present (see `assets/js/script.js:167-170`, `schemeOf()`).

This registry is consumed by:
- `render_color_scheme_selector()` (functions.php:110-124) — a `<select>` + live preview swatch, used wherever a classic (non-block-editor) scheme picker is needed.
- `wp_localize_script('sufo-script', 'SCHEMES', ...)` (functions.php:196-207, repeated for the block-editor script at 231-241) — exposes the same list to JS as `window.SCHEMES.schemes`, an array of `{label, value}`.

**To add a new scheme**: add one entry to `get_color_schemes()`, add a matching `.scheme-<name>` rule block to `assets/css/colors.css` (see below), done — both the classic selector and the block-editor dropdown pick it up automatically since neither hardcodes the list.

## 2. CSS token layer (`assets/css/colors.css`)

Base color primitives on `:root`:

```css
--color-white, --color-white-66, --color-highlight-66, --color-dark-grey-66,
--color-black, --color-dark-grey, --color-grey, --color-smoke, --color-light-grey
```

Then each `.scheme-*` class (`scheme-white`, `scheme-black`, `scheme-grey`, `scheme-light-grey`, `scheme-dark-grey`) maps those primitives into a small set of **semantic** custom properties that components actually consume:

```css
--scheme-bg          background-color of the section
--scheme-text        body/secondary text color
--scheme-accent      headings / emphasized text color
--scheme-solid-bg    solid-fill color for "inverted" chips (e.g. object-bar price pill)
--scheme-solid-text  text color on top of --scheme-solid-bg
--scheme-opacity     scheme-text at 35% via color-mix(), used for translucent overlays
```

...and applies `background-color`, `color`, `fill` directly from those. This means **any component styled with `var(--scheme-text)` / `var(--scheme-accent)` / etc. automatically re-colors correctly inside whatever scheme wraps it** — no per-component scheme variants needed. This is the pattern to follow for any new component: never hardcode a color, always reference the `--scheme-*` custom properties so it works inside every scheme.

Also in this file: `.scheme-preview` / `.editor-styles-wrapper .scheme-preview` — the small swatch preview shown next to scheme selectors, both in the classic meta box and inside the block editor's Inspector panel.

## 3. Block-editor integration (`assets/js/script.js:1-131`)

An IIFE (guarded by `window.__stirEditorInit` so it only runs once, and bails immediately if `wp.blocks`/`wp.blockEditor`/`wp.data` aren't present — i.e., outside the block editor) does three things via `wp.hooks.addFilter`:

1. **`blocks.registerBlockType`** — adds a `scheme: {type: 'string', default: ''}` attribute to `core/column` and `core/columns`.
2. **`editor.BlockEdit`** (HOC) — injects an `InspectorControls` panel with a `SelectControl` bound to that attribute, populated from `window.SCHEMES.schemes`. For `core/column` specifically, it only shows the control if the column is actually nested inside a `core/columns` block (checked via `getBlockParents`), so it doesn't clutter unrelated column usage.
3. **`blocks.getSaveContent.extraProps`** — on save, appends the chosen `scheme` value as a literal CSS class on the saved block markup.

This is a different mechanism from how `section--*` blocks pick up a scheme (they typically just get a `scheme-*` class added directly to the block's className in the editor's normal "Additional CSS class(es)" field) — but `render_sections()` normalizes both into the same `section-container` class list (see [section-rendering-system.md](section-rendering-system.md), step 4).

## 4. Scheme-aware section spacing (`style.css`, `#region LAYOUT`)

A set of `:has()`-based selectors (style.css:392-415) governs the visual "merging" of adjacent sections that share a scheme, so consecutive same-scheme sections read as one continuous colored block instead of each having its own padding:

- If a section has a scheme and its **neighbour above** doesn't (or has a different one), add `margin-top`/`padding-top` to visually separate them.
- If a section's scheme **matches its neighbour above**, remove the redundant top padding (they visually merge).
- If a section's scheme **matches its neighbour below**, remove the redundant bottom padding.
- `.section--card` is always excluded from merging — cards keep their own padding regardless of neighbours (style.css:1108-1115).

Anyone adding a new scheme-driven layout rule should extend this `:has()` selector block rather than adding one-off spacing overrides on individual sections.

## 5. Runtime header re-theming (`assets/js/script.js:156-201`)

The sticky header tracks scroll position and adds an `on-<scheme>` class to `#site-header` matching whichever section is currently scrolled behind it (with a `THEME_AHEAD = -60px` look-ahead so the header updates just before the section reaches it). `schemeOf()` reads the scheme off `.section > .section-container`'s className, defaulting to `'white'` if none is set. Currently only `.header.on-black .header-logo h2` has a distinct rule in CSS — extend this selector if other on-scheme header states are needed.
