# JavaScript Patterns

All front-end JS lives in **one file**, `assets/js/script.js` (1123 lines), enqueued both on the front end and (with WP editor dependencies added) inside the block editor. There's no bundler, no modules, no framework — just vanilla ES5/ES6 in a series of independent, self-contained blocks.

## Universal conventions

- **IIFE-per-feature** — nearly every block is `(function () { ... })()`, isolating its variables. A few top-level blocks (the two `IntersectionObserver` ones near the top) skip the IIFE wrapper and just register a `DOMContentLoaded` listener directly.
- **Init guards** — features that could double-run if the script were somehow included twice guard themselves with a `window.__someFlag` check at the top (e.g. `window.__stirEditorInit`, `window.__sufoMaterialPickerInit`).
- **Ready-state check pattern** — repeated verbatim across several IIFEs:
  ```js
  if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
  } else {
      init();
  }
  ```
  Used instead of always waiting for `DOMContentLoaded`, in case the script somehow runs after the event already fired.
- **Delegated event listeners** — click handling is done on `document` with `event.target.closest('.selector')`, not per-element listeners. This means dynamically injected content (e.g. server-rendered picker buttons, cloned repeater rows) works without any re-binding step.
- **rAF-throttled scroll/resize** — every scroll or resize listener uses a `ticking` boolean + `requestAnimationFrame` guard to avoid running expensive layout work more than once per frame:
  ```js
  var ticking = false;
  window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () { doWork(); ticking = false; });
  }, { passive: true });
  ```
  Follow this pattern for any new scroll/resize-driven effect.
- **Animation durations hardcoded to match CSS** — `setTimeout(fn, 200)` appears repeatedly with a `// matches --animation-fast` comment, because the CSS token can't be read into JS directly. If `--animation-fast` (style.css:103) ever changes from 200ms, every one of these `setTimeout` calls needs updating too (grep `matches --animation` to find them all).

## Feature-by-feature map

| Lines | Feature | Notes |
|---|---|---|
| 1-131 | Block-editor `scheme` attribute integration | See [color-scheme-system.md](color-scheme-system.md). Only executable path in the block editor context. |
| 133-154 | Section reveal-on-scroll | `IntersectionObserver` adds `.visible` to `.section-container` once, then unobserves (one-shot reveal, never removes the class) — pairs with the `opacity: 0 → 1` transition in `style.css:366-373`. |
| 156-201 | Header scheme tracking | See [color-scheme-system.md](color-scheme-system.md) §5. |
| 203-472 | Gallery helpers | Two independent systems sharing `whenImagesReady()`/`classifyOrientation()` utilities: (1) `initGallerySwipers()` — opt-in Swiper.js autoplay marquee for any `.gallery-swiper` gallery (currently unused by any section but kept for reuse — see comment at script.js:239-243); (2) `initGalleryScroll()` — the actual `section--gallery` behavior, a scroll-position-driven horizontal drift built from cloned slides (`LOOPS = 0.4` controls drift speed, `REST_VISIBLE_FRACTION` controls how much of the first slide peeks in at rest). Respects `prefers-reduced-motion` by bailing out entirely and leaving the static flex gallery in place. |
| 474-546 | Material picker click handler | Crossfades the `section--material` hero image: clones the current `[data-role="material-image"]` img as an absolutely-positioned overlay, fades it in, then after 200ms swaps the real `src`/`srcset`/`alt` and removes the overlay clone. Also preloads every material's image via `new Image()` on page load so the crossfade has no popup-in delay on first click. |
| 548-588 | Material column drag-to-scroll | Only active under the `max-width: 786px` media query (checked live via `matchMedia`, not just a CSS breakpoint gate) — mouse-drag scrolling for the `section--material` columns row, with a `didDrag` flag that swallows the subsequent click event (capture-phase listener) so a drag doesn't also fire the underlying picker button. |
| 590-785 | Nav highlight pill | The animated `.island__highlight` background that follows nav items — handles three states: hover preview, scroll-spy (which section is in view, via `IntersectionObserver` with a `-45% ... -45%` rootMargin band centered on viewport middle), and a 300ms leave-delay before falling back from hover to the scroll-spy'd item. Also manages horizontal-scroll edge fade classes (`can-scroll-left`/`can-scroll-right`) for the `max-width: 1200px` collapsed nav. Opt-in via `.nav-highlight` class on the island. |
| 787-843 | Admin repeater fields | Add/remove row buttons, `wp.media()` image picker wiring — for the `sufo_object_fields` meta box. See [custom-post-type-objects.md](custom-post-type-objects.md). Admin-context only, but shipped in the same bundle as front-end code. |
| 845-855 | FAQ accordion toggle | Click-anywhere-on-the-block-to-toggle (not just the `<summary>`), with a guard for active text selection so users can still select FAQ text without accidentally toggling. |
| 857-1087 | Object bar dropdowns | See [object-bar-component.md](object-bar-component.md) — the largest and most stateful block in the file. |
| 1089-1123 | Newsletter form validation | Keeps the MailerLite submit button disabled until both the email field is non-empty and the consent checkbox is checked. Uses a `MutationObserver` on the button's `disabled` attribute because MailerLite's own embedded script re-enables the button asynchronously after an internal nonce fetch — the observer re-asserts the theme's own validation state whenever that happens, so third-party script timing can't leave an unconsented/empty submission enabled. |

## If adding a new interactive feature

Follow the existing shape: one new IIFE appended to `script.js` (no new file), delegated `document`-level listeners where possible, rAF-throttled scroll/resize, and a ready-state check at the bottom if it needs the DOM. There is no module bundler, so anything added here ships to every page unconditionally — guard DOM queries with early-return null checks (as every existing block does) rather than assuming the relevant markup exists on the current page.
