# Architecture Overview

This is a classic PHP WordPress theme for a single-product showcase and checkout site. It has no bundler or `theme.json`; PHP, CSS, and JavaScript are served directly with file-modification cache versions.

## Main files

- `functions.php`: theme setup, assets, post types, meta boxes, rendering helpers, Stripe checkout/webhook handling, and order administration.
- `header.php` / `footer.php`: document shell, site chrome, newsletter, and organization JSON-LD.
- `page.php`: front-page object lookup and section rendering.
- `template-parts/header.php`: logo and desktop navigation.
- `template-parts/object-bar.php`: sticky configuration/checkout bar, language switch, and small-screen navigation menu.
- `assets/js/script.js`: block-editor integration, gallery, navigation, choices, menus, FAQ, admin repeater, newsletter, and notice behavior.
- `style.css` and `assets/css/colors.css`: layout/components/responsive rules and the semantic color system.

There are no runtime Mapbox or Swiper dependencies.

## Front-page request flow

1. `page.php` selects the published `sufo_object`.
2. `render_sections()` parses its Gutenberg content, wraps authored `section--*` blocks, applies schemes, injects Color choices/FAQ schema/icons, and returns the page HTML.
3. `template-parts/object-bar.php` renders reusable `choice-list` groups, hidden selection fields, the checkout button, language navigation, and `[data-menu]` popovers.
4. JavaScript initializes reveals, the local scroll-driven gallery, navigation highlight, choice selection/live price, popovers, FAQ behavior, newsletter gating, and checkout notices.
5. Checkout resolves all choices and prices again on the server. Stripe webhook events are the authoritative payment-state source; the return URL and admin sync are fallbacks.

## Content model

`sufo_object` block content is the page builder. Editors use ordinary Gutenberg blocks with `section--<name>` classes and optional `scheme-*` classes/attributes. Product choices live in repeatable post meta for Colors, Finishes, Sides, and Delivery.

`sufo_modal` supplies the optional small-screen navigation promo card. `sufo_order` stores checkout/order state in the WordPress admin.

## Important documentation

- [section-rendering-system.md](section-rendering-system.md)
- [color-scheme-system.md](color-scheme-system.md)
- [custom-post-type-objects.md](custom-post-type-objects.md)
- [object-bar-component.md](object-bar-component.md)
- [checkout-and-orders.md](checkout-and-orders.md)
