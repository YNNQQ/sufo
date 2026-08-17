# Theme Bootstrap & WordPress Hooks

Everything below lives in [functions.php](../functions.php), organized under its own numbered `// ====` section comments (1, 2, 3, 4, 5, 6, 7, 9 — section 8 doesn't exist, section 9 is an empty placeholder).

## Theme setup (`functions.php:39-59`)

`setup()` hooked to `after_setup_theme` adds:
- `title-tag` support
- `post-thumbnails` support
- `automatic-feed-links`
- `html5` support for `search-form`, `comment-form`, `comment-list`, `gallery`, `caption`

## Registered nav menu locations (`functions.php:61-69`)

| Location key | Used in |
|---|---|
| `primary` | `template-parts/header.php` — main site nav (island, `nav-highlight`) |
| `secondary` | `template-parts/header.php` — secondary nav island |
| `tagline` | `footer.php` — footer top tagline |
| `studio` | `footer.php` — footer "Studio" column |
| `askai` | `footer.php` — footer "Ask AI" column |
| `contact` | `footer.php` — footer "Contact" column |
| `footer` | `footer.php` — bottom legal links |

## Admin UI trimming

- `show_admin_bar` filter forced `false` — the front-end admin bar never renders (functions.php:19).
- `my_remove_admin_menus()` on `admin_menu` removes the Comments admin page (functions.php:72-76).
- `remove_comment_support()` on `init` (priority 100) strips comment support from `post` and `page` (functions.php:79-84).
- `mytheme_admin_bar_render()` on `wp_before_admin_bar_render` removes the Comments node from the admin bar menu itself, belt-and-suspenders with the `show_admin_bar` filter (functions.php:87-92).

## Upload limits

- `upload_size_limit` filter raised to 512 MB (functions.php:20).
- An `init` hook calls `insert_with_markers()` to write `php_value upload_max_filesize/post_max_size/memory_limit 512M` into the site root `.htaccess` on every request — this is how the theme pushes PHP upload limits on shared/Apache+mod_php hosting where `php.ini` isn't editable (functions.php:23-31). The Docker dev stack instead sets these via `uploads.ini` (see [build-and-deployment.md](build-and-deployment.md)).

## Asset enqueuing (`functions.php:177-259`)

- `enqueue_assets()` on `wp_enqueue_scripts`: enqueues `style.css` and `assets/js/script.js`, both cache-busted with `filemtime()`. Localizes `SCHEMES` (the color-scheme registry, see [color-scheme-system.md](color-scheme-system.md)) onto the script handle as `window.SCHEMES`.
- A separate `enqueue_block_editor_assets` callback re-enqueues `script.js` with WP editor script dependencies (`wp-blocks`, `wp-element`, `wp-components`, `wp-compose`, `wp-editor`, `wp-block-editor`, `wp-data`, `wp-hooks`), plus `assets/css/colors.css` and an inline style block (`sufo_meta_box_css()`) for meta boxes that render below the block editor. It also calls `wp_enqueue_media()` and localizes `sufo_ADMIN.projects` (all published `post` titles/IDs — legacy naming, unrelated to `sufo_object`) for use inside the editor.
- A near-identical `admin_enqueue_scripts` callback handles the **classic** (non-block) edit screens (`post.php`/`post-new.php`), bailing early if the current screen is block-editor-based to avoid double-enqueueing.

`sufo_meta_box_css()` (functions.php:131-174) returns a raw CSS string for the custom repeater/media-picker meta box UI — not a stylesheet file, injected inline via `wp_add_inline_style()`.

## Custom post type

`sufo_object` registered on `init` — see [custom-post-type-objects.md](custom-post-type-objects.md).

## Gutenberg block filters

- `register_block_type_args` filter forces `core/image`'s `sizeSlug` attribute to default to `full` instead of WordPress's usual `large` (functions.php:435-440). See [gutenberg-editor-integration.md](gutenberg-editor-integration.md).
- `render_block` and `wp_nav_menu_items` filters both run `sufo_inject_icons()` — see [icon-injection-system.md](icon-injection-system.md).

## Template helper functions (`functions.php:494-790`)

General-purpose helpers used by templates and template parts:

| Function | Purpose |
|---|---|
| `get_page_id_by_slug()` | Slug → page ID lookup |
| `echo_page_content()` | Print a page's filtered `the_content` by ID |
| `the_content_after_separator()` / `the_content_before_separator()` | Split a post's blocks around the Nth `core/separator` block and render only one side — used to lift a portion of a page's content into another template location |
| `get_first_block()` | Render just the first block of a post |
| `sufo_get_object_options()`, `sufo_get_price()`, `sufo_format_price()` | Object CPT meta readers — see [custom-post-type-objects.md](custom-post-type-objects.md) |
| `sufo_inject_material_pickers()`, `sufo_inject_faq_icons()`, `sufo_picker_swatch()`, `sufo_render_object_picker()` | Section post-processing / object-bar rendering — see [section-rendering-system.md](section-rendering-system.md) and [object-bar-component.md](object-bar-component.md) |
| `render_sections()` | The section pipeline itself — see [section-rendering-system.md](section-rendering-system.md) |

## Hook reference table

| Hook | Type | Callback | File:line |
|---|---|---|---|
| `wp_head` (`wp_site_icon`) | removed action | — | functions.php:10 |
| `show_admin_bar` | filter | `__return_false` | functions.php:19 |
| `upload_size_limit` | filter | inline `fn()` | functions.php:20 |
| `init` | action | inline (writes `.htaccess` markers) | functions.php:23-31 |
| `after_setup_theme` | action | `setup()` | functions.php:39-59 |
| `admin_menu` | action | `my_remove_admin_menus()` | functions.php:72-76 |
| `init` (priority 100) | action | `remove_comment_support()` | functions.php:79-84 |
| `wp_before_admin_bar_render` | action | `mytheme_admin_bar_render()` | functions.php:87-92 |
| `wp_enqueue_scripts` | action | `enqueue_assets()` | functions.php:177-210 |
| `enqueue_block_editor_assets` | action | inline | functions.php:212-259 |
| `init` | action | registers `sufo_object` CPT | functions.php:267-284 |
| `admin_enqueue_scripts` | action | inline (classic meta box assets) | functions.php:292-314 |
| `add_meta_boxes` | action | registers `sufo_object_fields` box | functions.php:331-333 |
| `save_post_sufo_object` | action | sanitize + persist meta | functions.php:401-425 |
| `register_block_type_args` | filter | force `core/image` sizeSlug=full | functions.php:435-440 |
| `render_block` | filter | `sufo_inject_icons()` | functions.php:485 |
| `wp_nav_menu_items` | filter | `sufo_inject_icons()` | functions.php:486 |
