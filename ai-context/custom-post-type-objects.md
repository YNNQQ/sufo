# Custom Post Type: `sufo_object`

`sufo_object` is the product/content model rendered on the front page. It is public, REST-enabled, and supports title, editor, thumbnail, excerpt, and categories. The block-editor content becomes the section-based product page through `render_sections()`.

## Option schema

`sufo_object_fields()` is the shared registry for configurable choices:

| Key | Meta | Admin box | Optional fields |
|---|---|---|---|
| `colors` | `sufo_colors` | Color | subtitle, color, swatch image, main photo |
| `finishes` | `sufo_finishes` | Finish | `hide_sides` flag |
| `sides` | `sufo_sides` | Finish | standard title/price |
| `delivery` | `sufo_delivery` | Delivery | `shipping` flag |

Every row stores the normalized shape:

```php
[
    'title'      => string,
    'subtitle'   => string,
    'image_id'   => int,
    'photo_id'   => int,
    'color'      => string,
    'price'      => float,
    'shipping'   => bool,
    'hide_sides' => bool,
]
```

Fields that do not expose a property still store its neutral value. This keeps rendering and checkout code generic.

## Product fields and saving

The Product meta box stores `sufo_price`, `sufo_available`, and `sufo_stripe_product_id`. Color, Finish/Sides, and Delivery each have separate nonces. The save handler only touches a box whose nonce was rendered and verified, so hiding a meta box through Screen Options cannot erase its data.

The save path checks autosave/capabilities, sanitizes text and colors, normalizes attachment IDs and prices, and drops rows that contain no meaningful content.

## Front-end consumers

- `sufo_get_object_options()` returns non-empty option groups.
- `sufo_inject_color_pickers()` fills the material section's authored placeholder columns.
- `sufo_render_customise_group()` and `sufo_render_choice_options()` generate reusable `choice-list` markup for the bottom bar.
- `sufo_resolve_selection()` treats submitted indices as untrusted, resolves them against this registry, recalculates total price, and applies the Finish/Sides rule server-side.
- Product JSON-LD derives its option properties and offer range from the same data.

The first row in each repeater is the default selection. Reordering rows therefore changes both front-end defaults and checkout fallback behavior.
