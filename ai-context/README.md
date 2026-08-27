# AI Context — sufo WordPress Theme

This folder is a map of the `sufo` codebase for AI assistants and new contributors. It documents systems that aren't obvious from file names alone — the custom section-rendering pipeline, the color scheme system, the Object CPT/pricing logic, and the hand-rolled JS component patterns.

## What this project is

`sufo` is a **classic PHP WordPress theme** (not a block theme / FSE) for `objects.suf.studio`, built by SU-F Studio. It's a single-product showcase site: the front page renders one `sufo_object` custom post type entirely out of Gutenberg blocks, with a sticky "object bar" at the bottom that lets visitors pick material/finish/delivery options and see a live price. There's no build step (no npm/webpack/vite) — CSS and JS are hand-written and enqueued directly.

## Index

| File | Covers |
|---|---|
| [architecture-overview.md](architecture-overview.md) | High-level map: request flow, directory layout, how the pieces fit together |
| [theme-bootstrap-and-hooks.md](theme-bootstrap-and-hooks.md) | `functions.php` theme setup, nav menu registration, admin UI trimming, every `add_action`/`add_filter` hook |
| [custom-post-type-objects.md](custom-post-type-objects.md) | The `sufo_object` CPT, product fields, Color/Finish/Sides/Delivery repeaters, and save/sanitize logic |
| [section-rendering-system.md](section-rendering-system.md) | The `render_sections()` pipeline that turns Gutenberg blocks tagged `section--*` into themed `<section>` markup — the core custom pattern of this theme |
| [color-scheme-system.md](color-scheme-system.md) | The `scheme-*` token system: PHP registry, CSS custom properties, block-editor integration, and the CSS rules that merge adjacent same-scheme sections |
| [object-bar-component.md](object-bar-component.md) | The sticky configuration bar, reusable `choice-list` component, generalized popover menus, and live price preview |
| [checkout-and-orders.md](checkout-and-orders.md) | Server-authoritative pricing, Stripe Checkout, signed webhooks, payment synchronization, and the Orders CPT |
| [icon-injection-system.md](icon-injection-system.md) | How `.button--icon` classes get inline SVGs injected server-side via a `render_block` filter |
| [design-tokens-and-css.md](design-tokens-and-css.md) | The token scale (spacing/type/radius/width) in `style.css`, the `.island`/`.card`/`.dropdown` component styles, and the responsive breakpoint structure |
| [javascript-patterns.md](javascript-patterns.md) | Conventions used across `assets/js/script.js`: IIFE modules, `IntersectionObserver` reveal/scroll-spy, the gallery scroll strip, drag-to-scroll, nav highlight pill |
| [gutenberg-editor-integration.md](gutenberg-editor-integration.md) | Editor-only customizations: the `scheme` attribute added to `core/column`/`core/columns`, forcing `core/image` to default to `full` size |
| [build-and-deployment.md](build-and-deployment.md) | Local dev via Docker Compose, FTP deploy via GitHub Actions, upload-size config, and what's git-ignored |

## Reading order for a newcomer

1. [architecture-overview.md](architecture-overview.md) — get oriented
2. [section-rendering-system.md](section-rendering-system.md) + [color-scheme-system.md](color-scheme-system.md) — the theme's central custom pattern
3. [custom-post-type-objects.md](custom-post-type-objects.md) + [object-bar-component.md](object-bar-component.md) + [checkout-and-orders.md](checkout-and-orders.md) — product configuration and commerce
4. Everything else as needed
