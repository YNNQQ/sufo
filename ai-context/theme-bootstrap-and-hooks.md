# Theme Bootstrap and Hooks

`functions.php` contains theme setup, assets, content types, admin fields, rendering helpers, checkout, and order administration.

## Setup

- Removes WordPress's automatic site-icon output.
- Disables the front-end admin bar and comment UI/support.
- Registers theme support for title tags, thumbnails, feeds, and HTML5 markup.
- Registers primary, secondary, language, tagline, studio, Ask AI, contact, and footer menu locations.
- Registers Polylang strings and provides `sufo_pll__()` as a safe fallback when Polylang is absent.
- Raises WordPress's displayed upload limit and writes production upload directives through the existing `.htaccess` hook.

## Assets

`enqueue_assets()` enqueues `style.css` and `assets/js/script.js` with `filemtime()` versions and exposes the PHP color-scheme registry as `window.SCHEMES`.

The block-editor enqueue adds Gutenberg dependencies, `colors.css`, media APIs, and meta-box styles. Classic post edit screens load the same script for repeater/media behavior unless the current screen is the block editor.

Mapbox and Swiper are not loaded. The gallery has no external JavaScript dependency.

## Content and rendering hooks

- `init`: registers `sufo_object`, `sufo_modal`, and admin-only `sufo_order` post types.
- `add_meta_boxes` and `save_post_sufo_object`: configure and persist product fields and repeaters.
- `register_block_type_args`: defaults Core Image blocks to full size.
- `render_block` and `wp_nav_menu_items`: inject inline icons for `.button--icon` modifiers.
- Front-page templates call `render_sections()` and render the object bar.

## Checkout and order hooks

- `admin_post_sufo_checkout` and `admin_post_nopriv_sufo_checkout`: create Stripe Checkout Sessions from server-resolved selections.
- `rest_api_init`: registers `POST /wp-json/sufo/v1/stripe-webhook` for signature-verified Stripe events.
- `template_redirect`: performs a return-page fallback sync after successful checkout.
- Order list/meta-box/save/admin-post hooks render order data, save manual status changes, and provide manual Stripe synchronization.

The order-status admin column is display-only and is not registered as sortable.

See [checkout-and-orders.md](checkout-and-orders.md) for configuration and payment-state behavior.
