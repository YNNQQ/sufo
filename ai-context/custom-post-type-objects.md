# Custom Post Type: `sufo_object`

The theme's one piece of "real" content model. There is normally exactly one published `sufo_object` — the front page renders `get_posts(['post_type' => 'sufo_object', ..., 'numberposts' => 1])[0]` (page.php:10) as "the product." The CPT is designed so it could hold more than one object, but the current templates only ever pull the first.

## Registration

`functions.php:267-284`, on `init`:

```php
register_post_type('sufo_object', [
    'public'        => true,
    'show_in_rest'  => true,       // block-editor compatible
    'supports'      => ['title', 'editor', 'thumbnail'],
    'rewrite'       => ['slug' => 'objects'],
    'menu_position' => 6,
]);
```

`'editor'` support means the object's `post_content` **is** its page body, authored in the block editor using the [section-rendering-system](section-rendering-system.md) conventions (`section--*` classNames).

## Meta field schema

Defined once, as the single source of truth, in `sufo_object_fields()` (functions.php:317-323):

```php
function sufo_object_fields(): array {
    return [
        'delivery'  => ['meta' => 'sufo_delivery',  'label' => 'Delivery',  'color' => false, 'image' => false, 'photo' => false, 'subtitle' => false],
        'materials' => ['meta' => 'sufo_materials', 'label' => 'Materials', 'color' => true,  'image' => true,  'photo' => true,  'subtitle' => true],
        'finishes'  => ['meta' => 'sufo_finishes',  'label' => 'Finishes',  'color' => false, 'image' => false, 'photo' => false, 'subtitle' => true],
    ];
}
```

Each key drives:
1. A post-meta key (`sufo_delivery`, `sufo_materials`, `sufo_finishes`) storing an **array of repeater rows**.
2. Which optional columns the admin repeater UI shows (color swatch input, swatch image picker, full-size photo picker, subtitle text).
3. The generic label shown in the front-end object-bar dropdown before an option is picked.

Plus one scalar meta field, `sufo_price` (float, the object's base price — functions.php:338).

### Repeater row shape

Each row in `sufo_delivery` / `sufo_materials` / `sufo_finishes` is an associative array:

```php
[
    'title'    => string,
    'subtitle' => string,   // only for materials/finishes
    'price'    => float,    // additional price added to the base when selected
    'image_id' => int,      // attachment ID, materials only — swatch thumbnail
    'photo_id' => int,      // attachment ID, materials only — full "main photo" shown on the object
    'color'    => string,   // hex color, materials only — fallback swatch when no image
]
```

`sufo_object_field_labels()` (functions.php:326-328) maps each key to its `label` for use in the object-bar's "Customise" panel headings.

## Admin meta box

Registered on `sufo_object` edit screens via `add_meta_boxes` → `sufo_render_object_fields()` (functions.php:331-347). Renders:
- A `Price` number input.
- One repeater block per field in `sufo_object_fields()`, built by `sufo_render_media_repeater_field()` → `sufo_media_row()` → `sufo_image_col()` (functions.php:351-399).

### Repeater UI mechanics

- Each row's inputs are named `sufo_materials[<index>][title]`, `[subtitle]`, `[price]`, `[image_id]`, `[photo_id]`, `[color]` — PHP's native `[]`-indexed POST array grouping means no client-side JSON serialization is needed; the browser submits a naturally-nested array.
- A `<template class="sufo-repeater-template">` holds a blank row with `__index__` placeholders. The "+ Add item" JS handler (`assets/js/script.js:787-843`) clones it and replaces `__index__` with `new-N` so new rows get a unique group index without colliding with existing ones.
- Image selection uses `wp.media()` (the WP media modal), wired in the same JS block.

### Save / sanitize (`functions.php:401-425`)

Hooked to `save_post_sufo_object`. Standard nonce + autosave + capability guard, then:
- `sufo_price` cast to float.
- For each field in `sufo_object_fields()`, every submitted row is sanitized (`sanitize_text_field`, `absint`, `sanitize_hex_color`, float cast) and **dropped entirely if every field on the row is empty** (functions.php:419) — so partially-filled rows left over from the repeater UI don't get persisted as junk data.

## Reading the data (front end)

| Function | Returns |
|---|---|
| `sufo_get_object_options(int $post_id): array` | `['materials' => [...], 'finishes' => [...], 'delivery' => [...]]` — **a key is omitted entirely if its meta array is empty**, not just empty-valued. Templates rely on this: `object-bar.php` checks `!empty($materials)` etc. to decide whether to render a picker at all. |
| `sufo_get_price(int $post_id): float` | Base price, defaults to `0` if unset. |
| `sufo_format_price(float): string` | Whole numbers render without decimals (`120` not `120.00`); otherwise 2 decimal places. |

Consumed by [template-parts/object-bar.php](../template-parts/object-bar.php) and the material-picker injection in [section-rendering-system.md](section-rendering-system.md).
