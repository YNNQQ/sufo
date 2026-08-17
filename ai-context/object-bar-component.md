# Object Bar Component

The sticky bottom bar on the front page that lets a visitor pick Material / Finish / Delivery options and shows a live total price. This is the theme's most complex piece of interactive UI, spanning PHP rendering, template markup, and a dedicated JS module.

## Pieces

| Layer | Where |
|---|---|
| Reusable picker markup generator | `sufo_render_object_picker()` — functions.php:673-705 |
| Swatch rendering | `sufo_picker_swatch()` — functions.php:656-670 |
| Bar template | [template-parts/object-bar.php](../template-parts/object-bar.php) |
| Included from | `page.php:22`, only `if (is_front_page() && !empty($object))` |
| Styling | `style.css` `#region COMPONENTS` → "Object Bar" / "Object Bar: Customise" (style.css:956-1090) |
| Behavior | `assets/js/script.js:857-1087` |

## PHP: building one picker

`sufo_render_object_picker(string $type, string $generic_label, array $items, bool $show_swatch): string` builds one dropdown (used for material, finish, and delivery — three calls in object-bar.php:20-22). Returns `''` if `$items` is empty (functions.php:674), which is how a bar with no configured finishes, say, simply omits that dropdown.

Markup shape:
```html
<div class="object-picker dropdown" data-object-picker="{type}" data-generic-label="{label}">
  <button class="object-picker__toggle dropdown__toggle button" aria-expanded="false" aria-haspopup="listbox">
    <span class="dropdown__label object-picker__label">{first item's title}</span>
    <span class="icon">{chevron.svg}</span>
  </button>
  <div class="island object-picker__panel dropdown__panel" hidden>
    <button class="object-picker__option button" data-price="{item.price}" aria-pressed="{true if first}">
      {swatch if $show_swatch}<span class="object-picker__option-label">{item.title}</span>
    </button>
    ... one per item ...
  </div>
</div>
```

The first item in each array is always pre-selected (`aria-pressed="true"`) — meta-field ordering in the admin repeater directly determines the default selection.

`sufo_picker_swatch()` renders a small swatch: prefers the item's `image_id` thumbnail, falls back to its `color` hex value as a CSS custom property (`--swatch-color`), or renders nothing if neither is set.

## Template: [template-parts/object-bar.php](../template-parts/object-bar.php)

Pulls `sufo_get_object_options()`, `sufo_get_price()`, `sufo_object_field_labels()` for the given `$args['post_id']` (see [custom-post-type-objects.md](custom-post-type-objects.md)), then:
1. Renders the object name.
2. Emits Material/Finish/Delivery pickers via `sufo_render_object_picker()`.
3. Emits a fourth pseudo-picker, `data-object-picker="customise"` — not backed by its own item list; instead it has empty `<div data-customise-slot="material|finish|delivery">` placeholders that the JS layer populates by **moving** (not cloning) the real picker panels into it. This lets "Customise" work as a single consolidated dialog on narrow viewports while the three separate dropdown toggles still exist for wider ones.
4. Renders the price pill: `<div class="object-bar__price"><span>Buy for</span> <span data-price-value>€{base price}</span></div>`.

Root element carries `data-base-price="{price}"` — the JS price calculator reads this as its starting point.

A sibling `<div class="object-bar__backdrop" data-object-bar-backdrop></div>` is emitted right after the bar (object-bar.php:52) — a full-viewport dimmed scrim shown only while the Customise panel is open.

## JS: `assets/js/script.js:857-1087`

Single IIFE, delegated click-handling (no per-picker listeners). Key mechanisms:

- **Mutual exclusion** — opening any picker closes all others first (`openPicker()`, functions.php-equivalent JS at line 957-960).
- **Label swap animation** — `setLabel()` fades the toggle's label text out/in via a `is-fading` class + `setTimeout` matched to `--animation-fast` (200ms), rather than an instant text swap.
- **Customise portal** — `togglePortal()` physically moves the Customise panel to `document.body` while open (script.js:920-930), because the object-bar's `backdrop-filter` creates a new stacking/containing-block context that breaks `position: fixed` positioning for descendants. It restores the panel to its original parent/sibling position (`_homeParent`/`_homeNext`) on close.
- **Sub-panel relocation** — `toggleCustomiseGroups()` moves the Material/Finish/Delivery panel DOM nodes into the Customise panel's slot `<div>`s on open, and back to their original toggles on close (script.js:897-918), tracked via a `_customiseHome` property stashed on each panel element.
- **Live price** — `updatePrice()` sums `bar.dataset.basePrice` + each non-customise picker's currently `aria-pressed="true"` option's `data-price`, and writes it into `[data-price-value]` (script.js:993-1005). Runs on init and after every `selectOption()`.
- **Label width lock** — `matchLabelWidth()` measures the widest option label in a panel (temporarily un-hiding it to measure) and sets `--picker-label-width` on the picker root, so the toggle button doesn't reflow/resize as the user picks different-length option labels (script.js:1007-1020).
- **Responsive teardown** — a `matchMedia('(max-width: 560px)')` change listener force-closes the Customise picker when crossing back above 560px, so it can't get stranded open with its toggle no longer visible (`.object-bar__customise { display: none }` above that breakpoint — style.css:1042-1044) (script.js:1071-1075).
- Closes all open pickers on scroll (script.js:1066-1068) and on outside-click (script.js:1058-1062).

## If extending this component

- New picker types: add a field to `sufo_object_fields()` ([custom-post-type-objects.md](custom-post-type-objects.md)), call `sufo_render_object_picker()` for it in `object-bar.php`, add a matching `data-customise-slot="<type>"` block, and add its type string to the `GROUPS` array in the JS (script.js:860) so it gets ported into the Customise panel correctly.
- Any new "priced option" picker must use `data-price` on its `.object-picker__option` buttons and `aria-pressed` for state — `updatePrice()` and `activeOption()` both key off exactly those attributes.
