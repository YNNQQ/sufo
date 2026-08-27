# Gutenberg Editor Integration

This theme doesn't ship any custom blocks — it customizes **core** block behavior and adds editor-only tooling so content editors can author the [section-rendering-system](section-rendering-system.md) conventions without hand-typing CSS classes.

## `core/image` default size forced to `full`

`register_block_type_args` filter (functions.php:435-440):

```php
add_filter('register_block_type_args', function ($args, $block_type) {
    if ($block_type === 'core/image' && isset($args['attributes']['sizeSlug'])) {
        $args['attributes']['sizeSlug']['default'] = 'full';
    }
    return $args;
}, 10, 2);
```

WordPress core defaults `core/image` to the `large` registered size; this theme wants full-resolution images by default (the design relies on large hero/material photography), so every newly-inserted image block starts at `full` instead of requiring the editor to manually change it each time.

## `scheme` attribute on `core/column` / `core/columns`

Implemented entirely in JS via `wp.hooks.addFilter`, not PHP — see [color-scheme-system.md](color-scheme-system.md) §3 for the full mechanism (`assets/js/script.js:1-131`). Summary: adds a `scheme` string attribute, an Inspector `SelectControl` (only shown for `core/column` when nested inside `core/columns`), and persists the choice as a literal CSS class on save.

## Editor asset loading

`enqueue_block_editor_assets` (functions.php:212-259) is where the editor gets:
- `assets/js/script.js` with WP script dependencies (`wp-blocks`, `wp-element`, `wp-components`, `wp-compose`, `wp-editor`, `wp-block-editor`, `wp-data`, `wp-hooks`) so the `scheme`-attribute IIFE (guarded to only run when those globals exist) can execute.
- `window.SCHEMES` localized data (see [color-scheme-system.md](color-scheme-system.md) §1).
- `assets/css/colors.css` — needed so the `.scheme-preview` swatch in the Inspector panel renders real theme colors, not the editor's own black/white palette.
- Inline meta-box CSS (`sufo_meta_box_css()`) — the `sufo_object_fields` meta box still renders as a **classic** meta box below the block editor canvas even on `sufo_object` posts (WP always does this for non-block-attribute meta boxes), so its styles need loading in the block-editor context too.
- `wp_enqueue_media()` supports the repeater's image-picker buttons. The separately localized `sufo_ADMIN.projects` payload is legacy data and currently has no JavaScript consumer.

## Editing a page's sections

There is no dedicated "section" block or block pattern registered in this theme — an editor builds a section by:
1. Adding a `core/group` (or similar container) block.
2. Setting its "Additional CSS class(es)" (Advanced panel) to include `section section--<name>` (the leading `section` class is optional in authoring since `render_sections()` derives it, but conventionally included) and optionally a `scheme-*` class.
3. Nesting ordinary content blocks inside, following whatever markup shape the section's PHP post-processor (if any — e.g. `section--material`, `section--faq`) expects. See [section-rendering-system.md](section-rendering-system.md) for the exact structural expectations of the two sections with server-side injection.

Because none of this is enforced by real Gutenberg block schemas, there's no editor-side validation that a `section--material` block has the right empty-column structure for Color choices. `sufo_inject_color_pickers()` stops once it runs out of exact placeholder columns or configured colors. Anyone extending this system with a real custom block should weigh the current flexibility against gaining editor-side structure validation.
