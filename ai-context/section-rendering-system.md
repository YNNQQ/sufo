# Section Rendering System

This is the theme's central custom pattern: editors compose a page in the Gutenberg block editor using ordinary blocks (`core/group`, `core/columns`, galleries, etc.) tagged with special `className` conventions, and `render_sections()` (functions.php:707-790) re-interprets those classes into themed `<section>` markup at render time — instead of building actual custom Gutenberg blocks.

## Authoring convention

An editor marks a top-level block as a "section" by giving it a className containing `section--<name>` (e.g. `section--gallery`, `section--material`, `section--faq`, `section--card`, `section--contact`, `section--values`, `section--blocks`, `section--editorial`, `section--50`). Optionally they also add a `scheme-*` class, or use the Inspector "Color scheme" dropdown added to `core/column`/`core/columns` (see [color-scheme-system.md](color-scheme-system.md)) which stores a `scheme` block attribute instead of a class.

## What `render_sections()` does

Called from `page.php` on the object's `post_content`. For each top-level block:

1. **Skip empty blocks** — blank `core/paragraph`-style spacer blocks with no `blockName` and empty `innerHTML` are dropped (functions.php:715-717).
2. **Detect sections** — `str_contains($className, 'section--')`.
3. **Strip section classes from the original block** before rendering it, so `section--*` and bare `section` never leak into the inner block's own `render_block()` output (functions.php:725-737), then strip any that leaked through anyway via a `class="..."` regex over the rendered HTML (functions.php:742-748).
4. **Rebuild the wrapper** — re-assembles a clean, predictable shell:
   ```html
   <section class="section section--<name>">
     <div class="section-container scheme-<x>">
       ...block_html...
     </div>
   </section>
   ```
   The `scheme-*` class list is the union of any `scheme-*` classes found in the original className **and** the block's `scheme` attribute if set (functions.php:756-761) — so scheme can be set at the section level (via className) or the column level (via the editor dropdown), and both funnel into the same `section-container` class list.
5. **Section-specific post-processing** on the assembled HTML string:
   - `sufo_inject_material_pickers()` — only runs if the html contains `section--material`; fills the block's empty `<div class="wp-block-column">` placeholders with real material-swatch `<button>` elements built from the object's `materials` meta (functions.php:591-639). Also tags the section's main image `<img>` with `data-role="material-image"` so the front-end crossfade JS knows what to swap (see [javascript-patterns.md](javascript-patterns.md)).
   - `sufo_inject_faq_icons()` — only runs if the html contains `section--faq`; appends a plus-icon SVG (via [icon-injection-system.md](icon-injection-system.md)'s `sufo_render_icon()`) into every `</summary>` (functions.php:642-653).
6. **Drop truly-empty sections** — if after all processing the container is just `<div class="section-container"></div>`, the whole section is skipped (functions.php:775-777). This is what makes it safe for `sufo_inject_material_pickers`/`sufo_inject_faq_icons` to be no-ops when there's no matching data — an editor can add a `section--material` block with no materials configured yet and it silently won't render.
7. **Non-section blocks** render normally, untouched, and are appended straight to the output (functions.php:781-786).

## Why classes are stripped and rebuilt rather than left alone

Keeping the section-level classes (`section--*`, `scheme-*`) off the *inner* rendered block and instead applying them to a synthetic outer wrapper is what lets `style.css`'s [color-scheme-system.md](color-scheme-system.md) CSS reliably target `.section > .section-container[class*="scheme-"]` for padding/margin merging between adjacent sections, regardless of what block type or nesting the editor used inside.

## Known `section--*` names in use (from `style.css` `#region SECTIONS`)

| Class | Purpose |
|---|---|
| `section--card` | Rounded, padded "card" section (`--radius-xl`, `--section-pad-lg/md`) |
| `section--gallery` | Horizontal auto-scrolling image strip (see [javascript-patterns.md](javascript-patterns.md)) |
| `section--values` | Numbered list (CSS `counter()` on nested columns) |
| `section--material` | The material picker: clickable swatches that crossfade the object's hero image, plus a drag-to-scroll strip on mobile |
| `section--blocks` | Generic multi-column content blocks |
| `section--editorial` | Long-form multi-column text with extra vertical rhythm |
| `section--faq` | `<details>`/`<summary>` accordion with click-anywhere-to-open JS and animated max-height |
| `section--contact` | Contact section heading spacing |
| `section--50` | Referenced in the `max-width: 1200px` media query (sticky figure column) — a two-column 50/50 layout |

Adding a new section type means: authoring it as a normal block in the editor with a `section--<newname>` className, and (only if it needs special server-side markup injection like materials/FAQ do) adding a new `sufo_inject_*()` post-processor called from `render_sections()`. Pure styling for a new section type belongs in `style.css`'s `#region SECTIONS` block.
