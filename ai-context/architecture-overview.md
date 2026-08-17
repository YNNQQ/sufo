# Architecture Overview

## What kind of theme this is

Classic PHP template-hierarchy WordPress theme (`style.css` theme header, `functions.php`, template files at the root). No block-theme `theme.json`, no `templates/` FSE folder, no bundler — `assets/css` and `assets/js` are hand-authored and enqueued as-is with `filemtime()` cache-busting.

## Directory layout

```
sufo/
├── functions.php              theme setup, CPT, meta boxes, section-rendering pipeline
├── header.php / footer.php    <head>/<body> shell — CDN script tags, page-wrap, footer nav+newsletter
├── index.php                  generic fallback template (mostly empty — see constraints below)
├── page.php                   front-page template: renders the single sufo_object + object bar
├── single.php                 single-post template (mostly empty — blog isn't the focus of this theme)
├── template-parts/
│   ├── header.php              site header markup (logo, primary/secondary nav, mobile toggle)
│   └── object-bar.php          the sticky pricing/customization bar (template part, takes $args['post_id'])
├── assets/
│   ├── css/colors.css          color tokens + scheme-* definitions (imported by style.css)
│   ├── js/script.js            all theme JS — one file, several self-contained IIFEs
│   ├── fonts/                  Denim variable-ish font (Regular 400 / Medium 500), .otf + .woff2
│   └── svg/                    inline-injected icons (arrow, chevron, logo, menu, plus, search)
├── style.css                   design tokens, base styles, components, section styles, responsive overrides
├── docker-compose.yml          local WP + MySQL dev stack (git-ignored, present locally)
├── uploads.ini                 PHP upload limits for the Docker container
└── .github/workflows/deploy.yml  push-to-main → FTP deploy to objects.suf.studio
```

## Request flow (front page)

1. WordPress resolves the front page to [page.php](../page.php).
2. `page.php` fetches the single published `sufo_object` post (there's exactly one "product" on this site) and passes its `post_content` through `render_sections()` — see [section-rendering-system.md](section-rendering-system.md).
3. `render_sections()` walks the object's Gutenberg blocks, wraps every block tagged `section--*` in a themed `<section><div class="section-container scheme-*">...</div></section>`, and runs two post-processing filters over the resulting HTML:
   - `sufo_inject_material_pickers()` — fills empty columns in the `section--material` block with real material-swatch buttons built from the object's `sufo_materials` post meta.
   - `sufo_inject_faq_icons()` — injects a plus-icon SVG into every `<summary>` in `section--faq`.
4. If the front page rendered an object, `template-parts/object-bar.php` is included below `<main>` — a sticky bar with Material/Finish/Delivery pickers and a live price total. See [object-bar-component.md](object-bar-component.md).
5. `template-parts/header.php` and `footer.php` wrap everything; both are pulled in via `get_header()`/`get_footer()` from [header.php](../header.php)/[footer.php](../footer.php).

## The core insight

The theme's real domain logic lives almost entirely in **one custom post type's block content**. There's no traditional page builder — instead, editors compose the object's page in the block editor using `core/group`/`core/columns` blocks with a `className` of `section section--<name>` (plus an optional `scheme-*` class on the group, or a "scheme" attribute added to `core/column`/`core/columns`). PHP then re-interprets those classes at render time to apply layout, spacing, and cross-section scheme-merging rules. See [section-rendering-system.md](section-rendering-system.md) and [color-scheme-system.md](color-scheme-system.md).

## Known content/constraint quirks

- [index.php](../index.php) and [single.php](../single.php) are effectively empty shells — this theme is built around one CPT and one front page, not a general blog.
- `functions.php` has a commented-out `wp_create_user()` block for bootstrapping a temp admin (functions.php:13-17) — dead code, left in intentionally as a break-glass snippet, not wired up.
- `functions.php:792-793` has an empty `// 9. ADMIN PAGES` section header with no body — a placeholder for a future admin screen, not a bug to "fix" by removing.
- The admin bar, comments, and the "Comments" admin menu item are all deliberately disabled theme-wide (functions.php:19, 71-92).
