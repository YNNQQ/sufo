# JavaScript Patterns

All theme JavaScript currently lives in `assets/js/script.js` and is enqueued on the front end, in the block editor, and on applicable classic edit screens. Features are isolated in IIFEs and guard their DOM queries so unrelated contexts safely do nothing.

## Block-editor integration

The first IIFE runs only when the Gutenberg APIs exist and uses `window.__sufoEditorInit` as its double-initialization guard. It adds the shared scheme attribute/control to Columns and Column blocks.

## Front-end features

- Section reveal: an `IntersectionObserver` adds `.is-visible` to each main-content `.section-container` once.
- Header scheme tracking: scroll-driven `on-<scheme>` state on `#site-header`.
- Gallery strip: waits for image dimensions, classifies landscape/portrait images, clones enough cycles to cover the viewport, and paints horizontal movement from page scroll. Scroll and resize listeners are bound when the first asynchronous gallery instance is ready, so uncached images cannot skip listener setup.
- Color choices: crossfade the material image and maintain `aria-pressed`.
- Material-row drag: small-screen mouse drag-to-scroll with click suppression after a real drag.
- Navigation highlight: opt-in `.nav-highlight` islands receive the animated `.island__highlight` pill, section scroll-spy, focus/hover preview, resizing, and horizontal edge fades.
- Admin repeaters: delegated add/remove/media-selection behavior.
- FAQ: non-interactive card areas toggle the containing `details`; links, buttons, inputs, selects, textareas, and labels retain their normal behavior.
- Menus and choices: generalized `[data-menu]` open/close behavior plus `.choice-list__option` selection, checkout-field synchronization, conditional Sides behavior, and live price display.
- Newsletter: keeps the MailerLite submit button disabled until local email/consent conditions pass.
- Checkout notice: uses the shared backdrop and removes both `checkout` and `session_id` from the URL when dismissed.

Swiper and Mapbox are not dependencies. The gallery is implemented entirely by the local scroll-strip code.

## Conventions

- Prefer one IIFE per independent feature.
- Prefer delegated document listeners for server-rendered or dynamic controls.
- Throttle scroll/resize layout work through `requestAnimationFrame`.
- Use `.is-visible` for visibility state and `data-open` for menu state.
- Use behavior attributes such as `data-menu`, `data-field-key`, and `data-checkout-option` instead of coupling JavaScript to layout-only classes.
- Hardcoded 200ms timeouts are paired with `--animation-fast`; update both together.
