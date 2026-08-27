# Icon Injection System

A server-side pattern for adding inline SVG icons to buttons/menu items purely through CSS class naming, with no manual markup per icon.

## Storage

Icons live as raw `.svg` files in `assets/svg/`: `arrow.svg`, `chevron.svg`, `logo.svg`, `menu.svg`, `plus.svg`, `search.svg`.

## Rendering a single icon

`sufo_render_icon(string $name, string $rotate_deg = ''): string` (functions.php:444-450) reads `assets/svg/{name}.svg` off disk with `file_get_contents()` and wraps it: `<span class="icon" style="transform:rotate({deg}deg)">{svg}</span>`. Returns `''` if the file doesn't exist. Note this hits the filesystem on every call — fine at this theme's scale (a handful of icons per page), but not something to put in a hot loop.

## Automatic injection via class name (`functions.php:452-482`)

`sufo_inject_icons(string $html): string` is a `render_block` and `wp_nav_menu_items` filter (functions.php:485-486) — i.e. it runs over **every rendered block** and **every nav menu's output**. It:

1. Bails immediately if the HTML doesn't contain `button--icon` (cheap `str_contains` guard before the regex work).
2. Regex-matches any tag with a class containing `button--icon` that isn't already immediately followed by an injected icon span (the negative lookahead `(?!<span class="icon")` makes this idempotent/safe to run more than once over the same markup).
3. Reads every `icon--<modifier>` class on that tag. A modifier matching `^(\d+)deg$` (e.g. `icon--45deg`) is treated as a rotation, not an icon name; everything else is treated as an icon name to render.
4. Injects `sufo_render_icon()` output for each icon-name modifier, in order, right after the opening tag.

### Authoring convention

To get an icon on a button or nav menu item in the block editor, an editor adds classes like:

```
button--icon icon--arrow
```

or, for a rotated icon:

```
button--icon icon--chevron icon--180deg
```

`.button--icon` also flips the button to `flex-direction: row-reverse` in CSS (style.css:730-732), so icon-after-text buttons and icon-before-text buttons are both achievable just by class order/composition — the injection always places the icon right after the opening tag, and CSS ordering handles the rest.

## Direct (non-filter) usage elsewhere in the theme

Several places call `sufo_render_icon()` / read SVG files directly rather than going through the class-name filter, because they're generating markup programmatically rather than authored in the block editor:
- `sufo_inject_faq_icons()` — always injects `plus.svg` into every FAQ `<summary>` (functions.php:642-653).
- `template-parts/object-bar.php` reads `chevron.svg` for the Customise menu toggle; the navigation toggle uses `sufo_render_icon('menu')`.
- `header.php`/`footer.php` — inline `file_get_contents(get_template_directory() . '/assets/svg/logo.svg')` for the header/footer logo (no `<span class="icon">` wrapper, used directly as a decorative brand mark rather than a button icon).

If adding a new icon, drop the `.svg` into `assets/svg/` and it's immediately usable both via the `icon--<name>` class convention and via a direct `sufo_render_icon('<name>')` call — no registration step needed.
