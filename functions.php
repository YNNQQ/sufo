<?php

/**
 * sufo Theme Functions
 *
 * @package SU—F Objects
 */

// Remove WP's auto-injected site icon tags — we output our own in header.php
remove_action('wp_head', 'wp_site_icon', 99);


// add_action('init', function() {
//   wp_create_user('tempadmin', 'temppassword123', 'your@email.com');
//   $user = get_user_by('login', 'tempadmin');
//   $user->set_role('administrator');
// });

add_filter('show_admin_bar', '__return_false');
add_filter('upload_size_limit', fn() => 512 * MB_IN_BYTES);

// Write PHP upload limits into the root .htaccess (works with Apache + mod_php)
add_action('init', function () {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    insert_with_markers(get_home_path() . '.htaccess', 'PHP Upload Limits', [
        'php_value upload_max_filesize 512M',
        'php_value post_max_size 512M',
        'php_value memory_limit 512M',
    ]);
});


// ============================================================
// 1. THEME SETUP & CONFIGURATION
// ============================================================

// Theme setup
function setup()
{
    // Add title tag support
    add_theme_support('title-tag');

    // Add post thumbnails support
    add_theme_support('post-thumbnails');

    // Add automatic feed links
    add_theme_support('automatic-feed-links');

    // Add HTML5 support
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'setup');

register_nav_menus([
    'primary'       => __('Primary menu'),
    'secondary'     => __('Secondary menu'),
    'tagline'       => __('Tagline menu'),
    'studio'        => __('Studio menu'),
    'askai'         => __('Ask AI menu'),
    'contact'       => __('Contact menu'),
    'footer'        => __('Footer menu'),
]);

// Removes from admin menu
add_action( 'admin_menu', 'my_remove_admin_menus' );

function my_remove_admin_menus() {
    remove_menu_page('edit-comments.php');
}

// Removes comment support from post and pages
add_action('init', 'remove_comment_support', 100);

function remove_comment_support() {
    remove_post_type_support( 'post', 'comments' );
    remove_post_type_support( 'page', 'comments' );
}

// Removes from admin bar
add_action( 'wp_before_admin_bar_render', 'mytheme_admin_bar_render' );

function mytheme_admin_bar_render() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}

// ============================================================
// 2. COLOR SCHEME REGISTRY
// ============================================================

// Color scheme registry (single source of truth)
function get_color_schemes(): array {
    return [
        ''                  => __('None'),
        'scheme-black'      => __('Black'),
        'scheme-grey'       => __('Grey'),
        'scheme-light-grey' => __('Light grey'),
        'scheme-dark-grey'  => __('Dark grey'),

    ];
}

function render_color_scheme_selector($name, $current = '') {
    ?>
    <select name="<?php echo esc_attr($name); ?>" class="color-scheme-select">
        <option value="">—</option>
        <?php foreach (get_color_schemes() as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div class="scheme-preview <?php echo esc_attr($current); ?>">
    </div>
    <?php
}


// ============================================================
// 3. ASSET ENQUEUING
// ============================================================

function sufo_meta_box_css(): string {
    return '
        .sufo-meta-box { padding: 0; }
        .sufo-field { display: flex; flex-direction: column; gap: 6px; padding: 6px 0; }
        .sufo-field:last-child { border-bottom: none; }
        .sufo-field > label { font-weight: 600; color: #1d2327; font-size: 13px; }
        .sufo-field input[type="text"],
        .sufo-field input[type="email"],
        .sufo-field input[type="number"],
        .sufo-field select { max-width: 500px; }
        .sufo-project-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .sufo-project-row select { flex: 1; max-width: 380px; }
        .sufo-remove-project { background: none; border: 1px solid #d63638; color: #d63638; border-radius: 3px; cursor: pointer; padding: 3px 8px; font-size: 13px; line-height: 1.6; }
        .sufo-remove-project:hover { background: #d63638; color: #fff; }
        .sufo-repeater { display: flex; flex-direction: column; gap: 8px; margin-bottom: 8px; }
        .sufo-repeater-row { display: flex; align-items: center; gap: 8px; }
        .sufo-repeater-row input[type="text"] { flex: 1; max-width: 260px; }
        .sufo-repeater-row input[type="color"] { width: 40px; padding: 2px; }
        .sufo-repeater-row--media { flex-direction: column; align-items: stretch; border: 1px solid #dcdcde; border-radius: 4px; padding: 10px; position: relative; }
        .sufo-repeater-row--media input[type="text"] { max-width: none; }
        .sufo-repeater-row--media .sufo-remove-row { position: absolute; top: 8px; right: 8px; }
        .sufo-remove-row { background: none; border: 1px solid #d63638; color: #d63638; border-radius: 3px; cursor: pointer; padding: 3px 8px; font-size: 13px; line-height: 1.6; }
        .sufo-remove-row:hover { background: #d63638; color: #fff; }
        .sufo-add-row { align-self: flex-start; background: none; border: 1px solid #2271b1; color: #2271b1; border-radius: 3px; cursor: pointer; padding: 5px 10px; font-size: 13px; line-height: 1.6; }
        .sufo-add-row:hover { background: #2271b1; color: #fff; }
        .sufo-repeater-template { display: none; }
        .sufo-field.sufo-conditional { display: none; }
        .sufo-field.sufo-conditional.visible { display: flex; }
        .sufo-checkbox-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; }
        .sufo-checkbox-row label { font-weight: 700; font-size: 13px; color: #1d2327; cursor: pointer; }
        .sufo-image-preview { max-width: 120px; height: auto; display: block; margin-top: 8px; border-radius: 4px; }
        .sufo-image-cols { display: flex; gap: 16px; flex-wrap: wrap; }
        .sufo-image-col { display: flex; flex-direction: column; }
        .sufo-image-col__label { font-size: 12px; font-weight: 600; color: #50575e; margin-bottom: 4px; }
        .sufo-image-col .sufo-image-buttons { display: flex; gap: 8px; }
        .sufo-icon-grid { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 6px; }
        .sufo-icon-option { display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; padding: 8px; border: 2px solid #ddd; border-radius: 6px; min-width: 64px; }
        .sufo-icon-option.is-selected { border-color: #2271b1; background: #f0f6fc; }
        .sufo-icon-option:hover { border-color: #2271b1; }
        .sufo-icon-preview { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
        .sufo-icon-preview svg { width: 100%; height: 100%; }
        .sufo-icon-option span { font-size: 11px; color: #50575e; }
    ';
}

// Enqueue styles and scripts
function enqueue_assets()
{
    // Theme stylesheet
    wp_enqueue_style(
        'sufo-style',
        get_stylesheet_uri(),
        [],
        filemtime(get_stylesheet_directory() . '/style.css')
    );

    // Theme script
    wp_enqueue_script(
        'sufo-script',
        get_template_directory_uri() . '/assets/js/script.js',
        [],
        filemtime(get_template_directory() . '/assets/js/script.js'),
        true
    );

    // Expose color schemes to JS
    wp_localize_script(
        'sufo-script',
        'SCHEMES',
        [
            'schemes' => array_map(
                fn($label, $value) => ['label' => $label, 'value' => $value],
                get_color_schemes(),
                array_keys(get_color_schemes())
            ),
        ]
    );

}
add_action('wp_enqueue_scripts', 'enqueue_assets');

add_action('enqueue_block_editor_assets', function () {

    wp_enqueue_script(
        'sufo-editor-script',
        get_template_directory_uri() . '/assets/js/script.js',
        [
            'wp-blocks',
            'wp-element',
            'wp-components',
            'wp-compose',
            'wp-editor',
            'wp-block-editor',
            'wp-data',
            'wp-hooks',
        ],
        filemtime(get_template_directory() . '/assets/js/script.js'),
        true
    );

    wp_localize_script(
        'sufo-editor-script',
        'SCHEMES',
        [
            'schemes' => array_map(
                fn($label, $value) => ['label' => $label, 'value' => $value],
                get_color_schemes(),
                array_keys(get_color_schemes())
            ),
        ]
    );

    wp_enqueue_style(
        'sufo-editor-color-schemes',
        get_template_directory_uri() . '/assets/css/colors.css',
        [],
        filemtime(get_template_directory() . '/assets/css/colors.css')
    );

    // Meta box styles for the block editor (meta boxes render below the editor)
    wp_add_inline_style('sufo-editor-color-schemes', sufo_meta_box_css());

    // Make media picker and meta box data available inside the block editor
    wp_enqueue_media();
    $admin_projects = get_posts(['post_type' => 'post', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    wp_localize_script('sufo-editor-script', 'sufo_ADMIN', [
        'projects' => array_map(fn($p) => ['id' => $p->ID, 'title' => $p->post_title], $admin_projects),
    ]);
});


// ============================================================
// 4. CUSTOM POST TYPES & TAXONOMIES
// ============================================================

// Objects CPT
add_action('init', function () {
    register_post_type('sufo_object', [
        'labels' => [
            'name'          => __('Objects'),
            'singular_name' => __('Object'),
            'add_new_item'  => __('Add Object'),
            'edit_item'     => __('Edit Object'),
            'all_items'     => __('All Objects'),
            'search_items'  => __('Search Objects'),
            'not_found'     => __('No objects found'),
        ],
        'public'       => true,
        'show_in_rest' => true,
        'supports'     => ['title', 'editor', 'thumbnail'],
        'rewrite'      => ['slug' => 'objects'],
        'menu_position' => 6,
    ]);
});


// ============================================================
// 5. META BOXES & POST META
// ============================================================

// Admin scripts & styles
add_action('admin_enqueue_scripts', function ($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'])) return;

    wp_enqueue_media();

    $screen = get_current_screen();
    if ($screen && $screen->is_block_editor()) return; // block editor pages handled by enqueue_block_editor_assets

    wp_enqueue_script(
        'sufo-admin',
        get_template_directory_uri() . '/assets/js/script.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/script.js'),
        true
    );

    $admin_projects = get_posts(['post_type' => 'post', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    wp_localize_script('sufo-admin', 'sufo_ADMIN', [
        'projects' => array_map(fn($p) => ['id' => $p->ID, 'title' => $p->post_title], $admin_projects),
    ]);

    wp_add_inline_style('wp-admin', sufo_meta_box_css());
});

// Objects meta box: Tags / Materials / Finishes repeaters
add_action('add_meta_boxes', function () {
    add_meta_box('sufo_object_fields', __('Object Fields'), 'sufo_render_object_fields', 'sufo_object', 'normal', 'default');
});

function sufo_render_object_fields($post) {
    wp_nonce_field('sufo_object_fields', 'sufo_object_fields_nonce');

    $price     = get_post_meta($post->ID, 'sufo_price', true);
    $shipping  = get_post_meta($post->ID, 'sufo_shipping', true) ?: [[]];
    $materials = get_post_meta($post->ID, 'sufo_materials', true) ?: [[]];
    $finishes  = get_post_meta($post->ID, 'sufo_finishes', true) ?: [[]];

    echo '<div class="sufo-meta-box">';
    echo '<div class="sufo-field"><label>Price</label><input type="number" step="0.01" name="sufo_price" value="' . esc_attr($price) . '" placeholder="Base price"></div>';
    // Shipping: title + additional price only, no subtitle
    sufo_render_media_repeater_field('Shipping', 'sufo_shipping', $shipping, false, false, false, false);
    // Materials: color + swatch image + a second "main photo" image (drives
    // the big wp-block-image in section--material when this material is active)
    sufo_render_media_repeater_field('Materials', 'sufo_materials', $materials, true, true, true);
    // Finishes: title/subtitle only
    sufo_render_media_repeater_field('Finishes', 'sufo_finishes', $finishes, false, false);
    echo '</div>';
}

// materials / finishes: title + subtitle + optional color/image(s) repeater
// each row needs a shared index across its fields, or the browser's
// name="x[][a]" / name="x[][b]" auto-indexing splits them into separate rows
function sufo_render_media_repeater_field($label, $name, $items, $show_color = true, $show_image = true, $show_photo = false, $show_subtitle = true) {
    echo '<div class="sufo-field"><label>' . esc_html($label) . '</label>';
    echo '<div class="sufo-repeater" data-repeater="' . esc_attr($name) . '">';
    foreach ($items as $index => $item) {
        echo sufo_media_row($name, $item, 'row-' . $index, $show_color, $show_image, $show_photo, $show_subtitle);
    }
    echo '<template class="sufo-repeater-template">' . sufo_media_row($name, [], '__index__', $show_color, $show_image, $show_photo, $show_subtitle) . '</template>';
    echo '</div>';
    echo '<button type="button" class="sufo-add-row" data-target="' . esc_attr($name) . '">+ Add item</button></div>';
}

function sufo_media_row($name, $item, $index, $show_color = true, $show_image = true, $show_photo = false, $show_subtitle = true) {
    $title    = $item['title'] ?? '';
    $subtitle = $item['subtitle'] ?? '';
    $price    = $item['price'] ?? '';
    $prefix   = esc_attr($name) . '[' . esc_attr($index) . ']';

    $html = '<div class="sufo-repeater-row sufo-repeater-row--media">'
        . '<input type="text" name="' . $prefix . '[title]" value="' . esc_attr($title) . '" placeholder="Title">'
        . ($show_subtitle ? '<input type="text" name="' . $prefix . '[subtitle]" value="' . esc_attr($subtitle) . '" placeholder="Subtitle">' : '')
        . '<input type="number" step="0.01" name="' . $prefix . '[price]" value="' . esc_attr($price) . '" placeholder="Additional price">';

    if ($show_image) {
        $html .= '<div class="sufo-image-cols">';
        $html .= sufo_image_col($prefix, 'image_id', $item['image_id'] ?? '', 'Swatch image', $show_color, $item['color'] ?? '');
        if ($show_photo) {
            $html .= sufo_image_col($prefix, 'photo_id', $item['photo_id'] ?? '', 'Main photo');
        }
        $html .= '</div>';
    }

    $html .= '<button type="button" class="sufo-remove-row">&times;</button></div>';

    return $html;
}

function sufo_image_col($prefix, $field, $image_id, $label, $show_color = false, $color = '') {
    $image_url = $image_id ? wp_get_attachment_image_url((int) $image_id, 'thumbnail') : '';

    return '<div class="sufo-image-col">'
        . '<span class="sufo-image-col__label">' . esc_html($label) . '</span>'
        . '<input type="hidden" name="' . $prefix . '[' . esc_attr($field) . ']" value="' . esc_attr($image_id) . '">'
        . ($show_color ? '<input type="color" name="' . $prefix . '[color]" value="' . esc_attr($color ?: '#ffffff') . '">' : '')
        . '<img class="sufo-image-preview" src="' . esc_url($image_url) . '"' . ($image_url ? '' : ' style="display:none;"') . '>'
        . '<div class="sufo-image-buttons">'
        . '<button type="button" class="sufo-select-image">Select image</button>'
        . '<button type="button" class="sufo-remove-image">Remove</button>'
        . '</div></div>';
}

add_action('save_post_sufo_object', function ($post_id) {
    if (!isset($_POST['sufo_object_fields_nonce']) || !wp_verify_nonce($_POST['sufo_object_fields_nonce'], 'sufo_object_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $price = isset($_POST['sufo_price']) && $_POST['sufo_price'] !== '' ? (float) $_POST['sufo_price'] : 0;
    update_post_meta($post_id, 'sufo_price', $price);

    foreach (['sufo_shipping', 'sufo_materials', 'sufo_finishes'] as $field) {
        $clean = [];
        foreach (($_POST[$field] ?? []) as $row) {
            $title    = sanitize_text_field($row['title'] ?? '');
            $subtitle = sanitize_text_field($row['subtitle'] ?? '');
            $image_id = absint($row['image_id'] ?? 0);
            $photo_id = absint($row['photo_id'] ?? 0);
            $color    = sanitize_hex_color($row['color'] ?? '');
            $row_price = isset($row['price']) && $row['price'] !== '' ? (float) $row['price'] : 0;

            if ($title === '' && $subtitle === '' && !$image_id && !$photo_id && !$row_price) continue;

            $clean[] = ['title' => $title, 'subtitle' => $subtitle, 'image_id' => $image_id, 'photo_id' => $photo_id, 'color' => $color, 'price' => $row_price];
        }
        update_post_meta($post_id, $field, $clean);
    }
});


// ============================================================
// 6. GUTENBERG BLOCKS
// ============================================================

/**
 * Change the default image size for core/image blocks to 'full'
 */
add_filter('register_block_type_args', function ($args, $block_type) {
    if ($block_type === 'core/image' && isset($args['attributes']['sizeSlug'])) {
        $args['attributes']['sizeSlug']['default'] = 'full';
    }
    return $args;
}, 10, 2);


// Render icons
function sufo_render_icon(string $name, string $rotate_deg = ''): string {
    $path = get_template_directory() . '/assets/svg/' . $name . '.svg';
    if (!file_exists($path)) return '';

    $style = $rotate_deg !== '' ? ' style="transform:rotate(' . esc_attr($rotate_deg) . 'deg)"' : '';
    return '<span class="icon"' . $style . '>' . file_get_contents($path) . '</span>';
}

function sufo_inject_icons(string $html): string {
    if (!str_contains($html, 'button--icon')) {
        return $html;
    }

    return preg_replace_callback(
        '/<([a-z0-9]+)([^>]*\sclass="[^"]*\bbutton--icon\b[^"]*"[^>]*)>(?!<span class="icon")/i',
        function ($m) {
            preg_match('/\sclass="([^"]*)"/i', $m[2], $class_match);
            preg_match_all('/\bicon--([a-z0-9]+)\b/i', $class_match[1] ?? '', $mods);

            $rotate_deg = '';
            $icon_names = [];
            foreach ($mods[1] as $modifier) {
                if (preg_match('/^(\d+)deg$/i', $modifier, $deg)) {
                    $rotate_deg = $deg[1];
                } else {
                    $icon_names[] = $modifier;
                }
            }

            $icons = '';
            foreach ($icon_names as $name) {
                $icons .= sufo_render_icon($name, $rotate_deg);
            }

            return '<' . $m[1] . $m[2] . '>' . $icons;
        },
        $html
    );
}

// covers block content (buttons, etc) and wp_nav_menu() items (menu-item CSS classes)
add_filter('render_block', 'sufo_inject_icons');
add_filter('wp_nav_menu_items', 'sufo_inject_icons');


// ============================================================
// 7. TEMPLATE HELPER FUNCTIONS
// ============================================================

// Get page by slug
function get_page_id_by_slug(string $slug): ?int {
    $page = get_page_by_path($slug);
    return $page ? (int) $page->ID : null;
}

function echo_page_content(int $page_id): void {
    $post = get_post($page_id);
    if ($post) {
        echo apply_filters('the_content', $post->post_content);
    }
}

function the_content_after_separator($index = 1, $page_id = null) {
    $post = $page_id ? get_post($page_id) : get_post();
    if (! $post) return;

    $blocks = parse_blocks($post->post_content);

    $currentSeparatorIndex = 0;
    $foundStartSeparator = false;

    foreach ($blocks as $block) {
        if ($block['blockName'] === 'core/separator') {
            $currentSeparatorIndex++;
            if ($currentSeparatorIndex === $index) {
                $foundStartSeparator = true;
                continue;
            } elseif ($currentSeparatorIndex === $index + 1) {
                break;
            }
        }

        if ($foundStartSeparator && $currentSeparatorIndex === $index) {
            echo do_shortcode(render_block($block));
        }
    }
}

function the_content_before_separator($index = 1) {
    global $post;
    $blocks = parse_blocks($post->post_content);

    $currentSeparatorIndex = 0;
    $contentBlocks = [];

    foreach ($blocks as $block) {
        if ($block['blockName'] === 'core/separator') {
            $currentSeparatorIndex++;
            if ($currentSeparatorIndex >= $index) {
                break; // Stop when reaching or exceeding the specified separator index
            }
        } else {
            // Add block to contentBlocks array if it's before the specified separator
            $contentBlocks[] = $block;
        }
    }

    foreach ($contentBlocks as $block) {
        echo render_block($block);
    }
}

function get_first_block($post = null)
{
  $post = get_post($post);
  if (! $post) return '';

  $blocks = parse_blocks($post->post_content);
  if (! empty($blocks)) {
    return apply_filters('the_content', render_block($blocks[0]));
  }

  return '';
}

// section--material picker data — falls back to placeholders if no sufo_materials meta
function sufo_get_materials(int $post_id): array {
    $materials = get_post_meta($post_id, 'sufo_materials', true);

    if (is_array($materials) && !empty($materials)) {
        return $materials;
    }

    return [
        ['title' => 'Black',      'subtitle' => 'Our signature finish.',        'color' => '#343434', 'image_id' => 0, 'photo_id' => 0],
        ['title' => 'Brown',      'subtitle' => 'Warm and natural.',            'color' => '#5c4a3d', 'image_id' => 0, 'photo_id' => 0],
        ['title' => 'Grey',       'subtitle' => 'Architectural and understated', 'color' => '#b9b9b5', 'image_id' => 0, 'photo_id' => 0],
        ['title' => 'Light grey', 'subtitle' => 'Soft and contemporary',        'color' => '#e7e6e2', 'image_id' => 0, 'photo_id' => 0],
    ];
}

// object-bar finish dropdown data — falls back to placeholders if no sufo_finishes meta
function sufo_get_finishes(int $post_id): array {
    $finishes = get_post_meta($post_id, 'sufo_finishes', true);

    if (is_array($finishes) && !empty($finishes)) {
        return $finishes;
    }

    return [
        ['title' => 'Laser cut', 'subtitle' => '', 'price' => 0],
        ['title' => 'Vinyl',     'subtitle' => '', 'price' => 0],
        ['title' => 'Blank',     'subtitle' => '', 'price' => 0],
    ];
}

// object-bar shipping dropdown data — falls back to placeholders if no sufo_shipping meta
function sufo_get_shipping(int $post_id): array {
    $shipping = get_post_meta($post_id, 'sufo_shipping', true);

    if (is_array($shipping) && !empty($shipping)) {
        return $shipping;
    }

    return [
        ['title' => 'Standard', 'price' => 0],
        ['title' => 'Express',  'price' => 0],
    ];
}

function sufo_get_price(int $post_id): float {
    $price = get_post_meta($post_id, 'sufo_price', true);
    return $price !== '' ? (float) $price : 0;
}

function sufo_format_price(float $price): string {
    return $price == floor($price) ? number_format($price, 0) : number_format($price, 2);
}

// fills the empty section--material picker columns with real buttons
function sufo_inject_material_pickers(string $section_html, int $post_id): string {
    if (!str_contains($section_html, 'section--material')) {
        return $section_html;
    }

    $materials = sufo_get_materials($post_id);
    if (empty($materials)) {
        return $section_html;
    }

    $section_html = preg_replace(
        '/<figure class="wp-block-image size-full"><img /',
        '<figure class="wp-block-image size-full"><img data-role="material-image" ',
        $section_html,
        1
    );

    $empty_column = '<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"></div>';

    foreach ($materials as $index => $material) {
        if (!str_contains($section_html, $empty_column)) {
            break;
        }

        $image_id     = !empty($material['image_id']) ? (int) $material['image_id'] : 0;
        $swatch_url   = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
        $swatch_img   = $swatch_url ? '<img src="' . esc_url($swatch_url) . '" alt="">' : '';

        $photo_id     = !empty($material['photo_id']) ? (int) $material['photo_id'] : 0;
        $photo_url    = $photo_id ? wp_get_attachment_image_url($photo_id, 'large') : '';
        $photo_srcset = $photo_id ? wp_get_attachment_image_srcset($photo_id, 'large') : '';

        $button = sprintf(
            '<div class="wp-block-column"><button type="button" class="material-picker card" style="--swatch-color:%s" data-image="%s" data-srcset="%s" data-alt="%s" aria-pressed="%s"><span class="material-picker__swatch">%s</span><span class="material-picker__title h5">%s</span><span class="material-picker__subtitle h5">%s</span></button></div>',
            esc_attr($material['color'] ?? ''),
            esc_url($photo_url ?: ''),
            esc_attr($photo_srcset ?: ''),
            esc_attr($material['title'] ?? ''),
            $index === 0 ? 'true' : 'false',
            $swatch_img,
            esc_html($material['title'] ?? ''),
            esc_html($material['subtitle'] ?? '')
        );

        $section_html = preg_replace('/' . preg_quote($empty_column, '/') . '/', $button, $section_html, 1);
    }

    return $section_html;
}

// injects the plus icon into every FAQ <summary>, replacing the old mask-image ::after
function sufo_inject_faq_icons(string $section_html): string {
    if (!str_contains($section_html, 'section--faq')) {
        return $section_html;
    }

    $icon = sufo_render_icon('plus');
    if (!$icon) {
        return $section_html;
    }

    return str_replace('</summary>', $icon . '</summary>', $section_html);
}

// swatch markup for one picker item — color if set, image if set (image wins), else nothing
function sufo_picker_swatch(array $item): string {
    $image_id  = !empty($item['image_id']) ? (int) $item['image_id'] : 0;
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
    $color     = $item['color'] ?? '';

    if ($image_url) {
        return '<span class="object-picker__swatch icon"><img src="' . esc_url($image_url) . '" alt=""></span>';
    }

    if ($color) {
        return '<span class="object-picker__swatch icon" style="--swatch-color:' . esc_attr($color) . '"></span>';
    }

    return '';
}

// one object-bar dropdown (toggle button + island panel) — reused for Material & Finish
function sufo_render_object_picker(string $type, string $generic_label, array $items, bool $show_swatch): string {
    if (empty($items)) return '';

    $chevron = file_get_contents(get_template_directory() . '/assets/svg/chevron.svg');

    $options = '';
    foreach ($items as $index => $item) {
        $options .= sprintf(
            '<button type="button" class="object-picker__option button" data-price="%s" aria-pressed="%s">%s<span class="object-picker__option-label">%s</span></button>',
            esc_attr($item['price'] ?? 0),
            $index === 0 ? 'true' : 'false',
            $show_swatch ? sufo_picker_swatch($item) : '',
            esc_html($item['title'] ?? '')
        );
    }

    $first = $items[0];

    return sprintf(
        '<div class="object-picker dropdown" data-object-picker="%1$s" data-generic-label="%2$s">
            <button type="button" class="object-picker__toggle dropdown__toggle button" aria-expanded="false" aria-haspopup="listbox">
                <span class="dropdown__label object-picker__label">%3$s</span>
                <span class="icon">%4$s</span>
            </button>
            <div class="island object-picker__panel dropdown__panel" hidden>%5$s</div>
        </div>',
        esc_attr($type),
        esc_attr($generic_label),
        esc_html($first['title'] ?? ''),
        $chevron,
        $options
    );
}

// fixed bottom bar: object name, Material/Finish pickers, dynamic price
function sufo_render_object_bar(int $post_id): string {
    $post = get_post($post_id);
    if (!$post) return '';

    $material_picker = sufo_render_object_picker('material', 'Color', sufo_get_materials($post_id), true);
    $finish_picker    = sufo_render_object_picker('finish', 'Finish', sufo_get_finishes($post_id), false);
    $shipping_picker  = sufo_render_object_picker('shipping', 'Shipping', sufo_get_shipping($post_id), false);
    $price            = sufo_get_price($post_id);

    return sprintf(
        '<div class="island object-bar scheme-white" data-object-bar data-base-price="%1$s">
            <span class="object-bar__name label">%2$s</span>
            %3$s%4$s%5$s
            <div class="object-bar__price button"><span>Buy for</span> <span data-price-value>€%6$s</span></div>
        </div>',
        esc_attr($price),
        esc_html($post->post_title),
        $material_picker,
        $finish_picker,
        $shipping_picker,
        esc_html(sufo_format_price($price))
    );
}

function render_sections($content, $post_id = null) {
    $post_id = $post_id ?? get_the_ID();
    $blocks = parse_blocks($content);
    $output = '';

    foreach ($blocks as $block) {

        // Skip empty blocks
        if (empty($block['blockName']) && trim($block['innerHTML'] ?? '') === '') {
            continue;
        }

        $className = $block['attrs']['className'] ?? '';

        // Detect section blocks
        $is_section = str_contains($className, 'section--');

        // Remove section-related classes from the original block
        if ($is_section && !empty($block['attrs']['className'])) {
            $classes = explode(' ', $block['attrs']['className']);

            $filtered = array_filter($classes, function ($cls) {
                return !str_starts_with($cls, 'section--') && $cls !== 'section';
            });

            if (!empty($filtered)) {
                $block['attrs']['className'] = implode(' ', $filtered);
            } else {
                unset($block['attrs']['className']);
            }
        }

        $block_html = render_block($block);

        // Remove section classes that might still exist in rendered HTML
        if ($is_section) {
            $block_html = preg_replace_callback('/\bclass="([^"]*)"/i', function ($m) {
                $classes = preg_replace('/\bsection(?:--[\w-]+)?\b/', '', $m[1]);
                $classes = preg_replace('/\s{2,}/', ' ', trim($classes));
                return 'class="' . $classes . '"';
            }, $block_html);
        }

        if ($is_section) {

            $classes = explode(' ', $className);

            $section_classes_array = array_filter($classes, fn($cls) => str_starts_with($cls, 'section--'));

            $scheme_classes_array = array_filter($classes, fn($cls) => str_starts_with($cls, 'scheme-'));

            $scheme_attr = trim($block['attrs']['scheme'] ?? '');
            if ($scheme_attr) {
                $scheme_classes_array[] = $scheme_attr;
            }

            $section_classes = 'section ' . implode(' ', $section_classes_array);
            $container_classes = trim('section-container ' . implode(' ', $scheme_classes_array));

            // last section--* class wins (eg. "section--50 section--material" -> section--material),
            // giving nav anchors like #section--gallery something to actually scroll to
            $section_id = '';
            foreach ($classes as $cls) {
                if (str_starts_with($cls, 'section--')) {
                    $section_id = $cls;
                }
            }

            $section_html  = '<section class="' . esc_attr($section_classes) . '"' . ($section_id ? ' id="' . esc_attr($section_id) . '"' : '') . '>';
            $section_html .= '<div class="' . esc_attr($container_classes) . '">';
            $section_html .= $block_html;
            $section_html .= '</div>';
            $section_html .= '</section>';

            $section_html = sufo_inject_material_pickers($section_html, $post_id);
            $section_html = sufo_inject_faq_icons($section_html);

            if (preg_match('/<div class="section-container">\s*<\/div>/', $section_html)) {
                continue;
            }

            $output .= $section_html;

        } else {

            // fallback: just render normally
            $output .= $block_html;

        }
    }

    return $output;
}

// ============================================================
// 9. ADMIN PAGES
// ============================================================