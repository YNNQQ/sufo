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
        .sufo-repeater-row__flag { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #50575e; }
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
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt'],
        'taxonomies'   => ['category'],
        'rewrite'      => ['slug' => 'objects'],
        'menu_position' => 6,
    ]);
});

// Modals CPT — classic editor (no show_in_rest); 'editor' support holds the description
add_action('init', function () {
    register_post_type('sufo_modal', [
        'labels' => [
            'name'          => __('Modals'),
            'singular_name' => __('Modal'),
            'add_new_item'  => __('Add Modal'),
            'edit_item'     => __('Edit Modal'),
            'all_items'     => __('All Modals'),
            'search_items'  => __('Search Modals'),
            'not_found'     => __('No modals found'),
        ],
        'public'        => true,
        'supports'      => ['title', 'editor', 'thumbnail'],
        'rewrite'       => ['slug' => 'modals'],
        'menu_position' => 7,
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

// single source for the Object CPT's repeater fields — label, meta key, and which repeater columns to show
function sufo_object_fields(): array {
    return [
        'delivery'  => ['meta' => 'sufo_delivery',  'label' => 'Delivery',  'color' => false, 'image' => false, 'photo' => false, 'subtitle' => false, 'shipping' => true],
        'materials' => ['meta' => 'sufo_materials', 'label' => 'Material',  'color' => true,  'image' => true,  'photo' => true,  'subtitle' => true,  'shipping' => false],
        'finishes'  => ['meta' => 'sufo_finishes',  'label' => 'Finish',    'color' => false, 'image' => false, 'photo' => false, 'subtitle' => true,  'shipping' => false],
    ];
}

// used by template-parts/object-bar.php's Customise panel group headings
function sufo_object_field_labels(): array {
    return array_map(fn($field) => $field['label'], sufo_object_fields());
}

// Objects meta box: Tags / Materials / Finishes repeaters
add_action('add_meta_boxes', function () {
    add_meta_box('sufo_object_fields', __('Object Fields'), 'sufo_render_object_fields', 'sufo_object', 'normal', 'default');
});

function sufo_render_object_fields($post) {
    wp_nonce_field('sufo_object_fields', 'sufo_object_fields_nonce');

    $price      = get_post_meta($post->ID, 'sufo_price', true);
    $available  = sufo_is_available($post->ID);
    $stripe_id  = get_post_meta($post->ID, 'sufo_stripe_product_id', true);

    echo '<div class="sufo-meta-box">';
    echo '<div class="sufo-field"><label>Price</label><input type="number" step="0.01" name="sufo_price" value="' . esc_attr($price) . '" placeholder="Base price"></div>';
    echo '<div class="sufo-field"><label><input type="checkbox" name="sufo_available" value="1"' . checked($available, true, false) . '> Available</label></div>';
    echo '<div class="sufo-field"><label>Stripe Product ID</label><input type="text" name="sufo_stripe_product_id" value="' . esc_attr($stripe_id) . '" placeholder="prod_…"></div>';
    foreach (sufo_object_fields() as $field) {
        $items = get_post_meta($post->ID, $field['meta'], true) ?: [[]];
        sufo_render_media_repeater_field($field['label'], $field['meta'], $items, $field['color'], $field['image'], $field['photo'], $field['subtitle'], $field['shipping']);
    }
    echo '</div>';
}

// materials / finishes: title + subtitle + optional color/image(s) repeater
// name="x[][a]" / name="x[][b]" auto-indexing splits them into separate rows
function sufo_render_media_repeater_field($label, $name, $items, $show_color = true, $show_image = true, $show_photo = false, $show_subtitle = true, $show_shipping = false) {
    echo '<div class="sufo-field"><label>' . esc_html($label) . '</label>';
    echo '<div class="sufo-repeater" data-repeater="' . esc_attr($name) . '">';
    foreach ($items as $index => $item) {
        echo sufo_media_row($name, $item, 'row-' . $index, $show_color, $show_image, $show_photo, $show_subtitle, $show_shipping);
    }
    echo '<template class="sufo-repeater-template">' . sufo_media_row($name, [], '__index__', $show_color, $show_image, $show_photo, $show_subtitle, $show_shipping) . '</template>';
    echo '</div>';
    echo '<button type="button" class="sufo-add-row" data-target="' . esc_attr($name) . '">+ Add item</button></div>';
}

function sufo_media_row($name, $item, $index, $show_color = true, $show_image = true, $show_photo = false, $show_subtitle = true, $show_shipping = false) {
    $title    = $item['title'] ?? '';
    $subtitle = $item['subtitle'] ?? '';
    $price    = $item['price'] ?? '';
    $prefix   = esc_attr($name) . '[' . esc_attr($index) . ']';

    $html = '<div class="sufo-repeater-row sufo-repeater-row--media">'
        . '<input type="text" name="' . $prefix . '[title]" value="' . esc_attr($title) . '" placeholder="Title">'
        . ($show_subtitle ? '<input type="text" name="' . $prefix . '[subtitle]" value="' . esc_attr($subtitle) . '" placeholder="Subtitle">' : '')
        . '<input type="number" step="0.01" name="' . $prefix . '[price]" value="' . esc_attr($price) . '" placeholder="Additional price">'
        // marks a delivery option that needs a real address collected at checkout
        . ($show_shipping ? '<label class="sufo-repeater-row__flag"><input type="checkbox" name="' . $prefix . '[shipping]" value="1"' . checked(!empty($item['shipping']), true, false) . '> Requires shipping address</label>' : '');

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
    update_post_meta($post_id, 'sufo_available', isset($_POST['sufo_available']) ? '1' : '0');
    update_post_meta($post_id, 'sufo_stripe_product_id', sanitize_text_field($_POST['sufo_stripe_product_id'] ?? ''));

    foreach (sufo_object_fields() as $field) {
        $clean = [];
        foreach (($_POST[$field['meta']] ?? []) as $row) {
            $title    = sanitize_text_field($row['title'] ?? '');
            $subtitle = sanitize_text_field($row['subtitle'] ?? '');
            $image_id = absint($row['image_id'] ?? 0);
            $photo_id = absint($row['photo_id'] ?? 0);
            $color    = sanitize_hex_color($row['color'] ?? '');
            $row_price = isset($row['price']) && $row['price'] !== '' ? (float) $row['price'] : 0;
            $shipping  = !empty($row['shipping']);

            if ($title === '' && $subtitle === '' && !$image_id && !$photo_id && !$row_price) continue;

            $clean[] = ['title' => $title, 'subtitle' => $subtitle, 'image_id' => $image_id, 'photo_id' => $photo_id, 'color' => $color, 'price' => $row_price, 'shipping' => $shipping];
        }
        update_post_meta($post_id, $field['meta'], $clean);
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

// items of the nav menu assigned to a theme location, or [] if none is assigned
function sufo_nav_menu_items(string $location): array {
    $locations = get_nav_menu_locations();
    if (empty($locations[$location])) return [];

    $menu = wp_get_nav_menu_object($locations[$location]);
    return $menu ? (wp_get_nav_menu_items($menu->term_id) ?: []) : [];
}

// site-wide Organization schema.org JSON-LD — echoed once by footer.php.
// Phone/email/social links are read from the existing Contact/footer nav menus
// so they stay in sync; the address is fixed here since there's no admin field for it.
function sufo_organization_schema_json_ld(): string {
    $telephone = null;
    $email     = null;
    foreach (sufo_nav_menu_items('contact') as $item) {
        if (str_starts_with($item->url, 'tel:')) {
            $telephone = preg_replace('/^00/', '+', substr($item->url, 4));
        } elseif (str_starts_with($item->url, 'mailto:')) {
            $email = substr($item->url, 7);
        }
    }

    // sameAs is for the org's other profiles, not every external-looking footer
    // link — match against known social/profile platforms only, so Jobs/Privacy/
    // Terms (which can live on a different suf.studio subdomain) aren't swept in
    $social_hosts = ['instagram.com', 'linkedin.com', 'facebook.com', 'twitter.com', 'x.com', 'youtube.com', 'tiktok.com', 'pinterest.com'];
    $same_as = [];
    foreach (sufo_nav_menu_items('footer') as $item) {
        $host = wp_parse_url($item->url, PHP_URL_HOST);
        if ($host && preg_match('/(^|\.)(' . implode('|', array_map('preg_quote', $social_hosts)) . ')$/i', $host)) {
            $same_as[] = $item->url;
        }
    }

    $schema = array_filter([
        '@context'  => 'https://schema.org',
        '@type'     => 'Organization',
        'name'      => 'SU—F',
        'url'       => home_url('/'),
        'logo'      => get_template_directory_uri() . '/assets/svg/logo.svg',
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => 'Frankrijklei 5',
            'postalCode'      => '2000',
            'addressLocality' => 'Antwerpen',
            'addressCountry'  => 'BE',
        ],
        'telephone' => $telephone,
        'email'     => $email,
        'sameAs'    => $same_as ?: null,
    ], fn($value) => $value !== null && $value !== '');

    return '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
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

// a field is absent from the result (not rendered) when its post meta is empty
function sufo_get_object_options(int $post_id): array {
    $options = [];
    foreach (sufo_object_fields() as $key => $field) {
        $value = get_post_meta($post_id, $field['meta'], true);
        if (is_array($value) && !empty($value)) {
            $options[$key] = $value;
        }
    }
    return $options;
}

function sufo_get_price(int $post_id): float {
    $price = get_post_meta($post_id, 'sufo_price', true);
    return $price !== '' ? (float) $price : 0;
}

// unset meta (never saved through the checkbox yet) defaults to available
function sufo_is_available(int $post_id): bool {
    return get_post_meta($post_id, 'sufo_available', true) !== '0';
}

function sufo_format_price(float $price): string {
    return $price == floor($price) ? number_format($price, 0) : number_format($price, 2);
}

// plain-text excerpt for schema.org description — get_the_excerpt() strips tags
// without adding spaces at block/line boundaries, so words run together and its
// "[&hellip;]" more-marker is left undecoded; build it from post_content instead
function sufo_object_description(WP_Post $post, int $word_count = 55): string {
    $content = apply_filters('the_content', $post->post_content);
    $content = preg_replace('/<(br|\/p|\/h[1-6]|\/li)\b[^>]*>/i', ' ', $content);
    $content = html_entity_decode(wp_strip_all_tags($content), ENT_QUOTES, 'UTF-8');
    $content = trim(preg_replace('/\s+/', ' ', $content));

    return wp_trim_words($content, $word_count, '…');
}

// images from the object's own section--gallery block, at full attachment size —
// these are the real hero photography, unlike the material swatch photos
function sufo_object_images(int $post_id): array {
    $post = get_post($post_id);
    if (!$post) return [];

    foreach (parse_blocks($post->post_content) as $block) {
        if (!str_contains($block['attrs']['className'] ?? '', 'section--gallery')) continue;

        // innerHTML is just the empty outer wrapper — the actual <img> tags live
        // in innerBlocks, so render the block to get them
        preg_match_all('/\bwp-image-(\d+)\b/', render_block($block), $matches);
        $attachment_ids = array_unique($matches[1]);

        return array_values(array_filter(array_map(
            fn($id) => wp_get_attachment_image_url((int) $id, 'full'),
            $attachment_ids
        )));
    }

    return [];
}

// Product schema.org JSON-LD for the front-page object — echoed by object-bar.php.
// Price is composed client-side from material/finish/delivery deltas, so it's
// represented as an AggregateOffer range rather than one fixed Offer price.
function sufo_product_schema_json_ld(int $post_id): string {
    $post = get_post($post_id);
    if (!$post) return '';

    $price        = sufo_get_price($post_id);
    $options      = sufo_get_object_options($post_id);
    $field_labels = sufo_object_field_labels();

    $high_price   = $price;
    $offer_count  = 1;
    $properties   = [];

    foreach ($options as $key => $items) {
        $item_prices = array_map(fn($item) => (float) ($item['price'] ?? 0), $items);
        $high_price += !empty($item_prices) ? max($item_prices) : 0;
        $offer_count *= count($items);

        $properties[] = [
            '@type' => 'PropertyValue',
            'name'  => $field_labels[$key] ?? ucfirst($key),
            'value' => implode(', ', array_map(fn($item) => $item['title'] ?? '', $items)),
        ];
    }

    $categories = get_the_category($post_id);
    $category   = !empty($categories) ? implode(', ', wp_list_pluck($categories, 'name')) : null;
    $images     = sufo_object_images($post_id);

    // manual excerpt if the editor wrote one, otherwise fall back to the content-derived one
    $description = $post->post_excerpt !== ''
        ? trim(wp_strip_all_tags($post->post_excerpt))
        : sufo_object_description($post);

    $schema = array_filter([
        '@context'           => 'https://schema.org',
        '@type'              => 'Product',
        'name'               => $post->post_title,
        'description'        => $description,
        'sku'                => (string) $post_id,
        'url'                => home_url('/'),
        'image'              => $images ?: null,
        'brand'              => ['@type' => 'Brand', 'name' => 'SU—F'],
        'category'           => $category,
        'additionalProperty' => $properties ?: null,
        'offers'             => [
            '@type'         => 'AggregateOffer',
            'priceCurrency' => 'EUR',
            'lowPrice'      => sufo_format_price($price),
            'highPrice'     => sufo_format_price($high_price),
            'offerCount'    => $offer_count,
            'availability'  => sufo_is_available($post_id) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'           => home_url('/'),
        ],
    ], fn($value) => $value !== null && $value !== '');

    return '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
}

// fills the empty section--material picker columns with real buttons
function sufo_inject_material_pickers(string $section_html, int $post_id): string {
    if (!str_contains($section_html, 'section--material')) {
        return $section_html;
    }

    $materials = sufo_get_object_options($post_id)['materials'] ?? [];
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

// FAQPage schema.org JSON-LD, built from the same <details>/<summary> markup the
// editor authors — must run before sufo_inject_faq_icons() so <summary> text
// doesn't pick up the injected icon svg
function sufo_inject_faq_schema(string $section_html): string {
    if (!str_contains($section_html, 'section--faq')) {
        return $section_html;
    }

    preg_match_all('/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>(.*?)<\/details>/s', $section_html, $matches, PREG_SET_ORDER);
    if (empty($matches)) {
        return $section_html;
    }

    $questions = [];
    foreach ($matches as $match) {
        $question = html_entity_decode(trim(wp_strip_all_tags($match[1])), ENT_QUOTES, 'UTF-8');
        $answer   = trim($match[2]);
        if ($question === '' || $answer === '') continue;

        $questions[] = [
            '@type' => 'Question',
            'name'  => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text'  => $answer,
            ],
        ];
    }

    if (empty($questions)) {
        return $section_html;
    }

    $script = '<script type="application/ld+json">' . wp_json_encode([
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $questions,
    ]) . '</script>';

    return preg_replace('/<\/section>\s*$/', $script . '</section>', $section_html, 1);
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
// $field_key is the sufo_object_fields() key ('materials'), which differs from the
// picker's own $type ('material') — checkout submits by field key
function sufo_render_object_picker(string $type, string $generic_label, array $items, bool $show_swatch, string $field_key = ''): string {
    if (empty($items)) return '';

    $chevron = file_get_contents(get_template_directory() . '/assets/svg/chevron.svg');

    $options = '';
    foreach ($items as $index => $item) {
        // data-index is the checkout's source of truth — the server re-looks-up the
        // real price by it, so a tampered data-price can't affect what's charged
        $options .= sprintf(
            '<button type="button" class="object-picker__option button" data-price="%s" data-index="%s" aria-pressed="%s">%s<span class="object-picker__option-label">%s</span></button>',
            esc_attr($item['price'] ?? 0),
            esc_attr($index),
            $index === 0 ? 'true' : 'false',
            $show_swatch ? sufo_picker_swatch($item) : '',
            esc_html($item['title'] ?? '')
        );
    }

    $first = $items[0];

    return sprintf(
        '<div class="object-picker" data-object-picker="%1$s" data-field-key="%6$s" data-generic-label="%2$s">
            <button type="button" class="object-picker__toggle button" aria-expanded="false" aria-haspopup="listbox">
                <span class="object-picker__label">%3$s</span>
                <span class="icon">%4$s</span>
            </button>
            <div class="island object-picker__panel" hidden>%5$s</div>
        </div>',
        esc_attr($type),
        esc_attr($generic_label),
        esc_html($first['title'] ?? ''),
        $chevron,
        $options,
        esc_attr($field_key ?: $type)
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

            $section_html  = '<section class="' . esc_attr($section_classes) . '">';
            $section_html .= '<div class="' . esc_attr($container_classes) . '">';
            $section_html .= $block_html;
            $section_html .= '</div>';
            $section_html .= '</section>';

            $section_html = sufo_inject_material_pickers($section_html, $post_id);
            $section_html = sufo_inject_faq_schema($section_html);
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
// 9. STRIPE CHECKOUT
// ============================================================

// Resolves a submitted selection (field key => row index) into the real rows.
// Indexes come from the client, prices never do — every price is re-read from
// post meta here, so tampering with the form only changes *which* option is
// picked, never what it costs.
function sufo_resolve_selection(int $post_id, array $submitted): array {
    $options   = sufo_get_object_options($post_id);
    $labels    = sufo_object_field_labels();
    $total     = sufo_get_price($post_id);
    $selected  = [];
    $needs_shipping = false;

    foreach ($options as $key => $items) {
        $index = isset($submitted[$key]) ? (int) $submitted[$key] : 0;
        $item  = $items[$index] ?? $items[0] ?? null; // unknown index falls back to the default
        if (!$item) continue;

        $total += (float) ($item['price'] ?? 0);
        if (!empty($item['shipping'])) $needs_shipping = true;

        $selected[$key] = [
            'label' => $labels[$key] ?? ucfirst($key),
            'title' => $item['title'] ?? '',
        ];
    }

    return ['total' => $total, 'selected' => $selected, 'needs_shipping' => $needs_shipping];
}

// banner shown when Stripe returns the customer to the front page
function sufo_checkout_notice(): string {
    $status = $_GET['checkout'] ?? '';

    $messages = [
        'success'   => ['Thank you for your order.', 'You will receive a confirmation email shortly.'],
        'cancelled' => ['Your checkout was cancelled.', 'Nothing has been charged.'],
    ];

    if (!isset($messages[$status])) return '';

    return sprintf(
        '<div class="island checkout-notice checkout-notice--%s" role="status"><span class="checkout-notice__title">%s</span><span class="checkout-notice__body">%s</span></div>',
        esc_attr($status),
        esc_html($messages[$status][0]),
        esc_html($messages[$status][1])
    );
}

add_action('admin_post_nopriv_sufo_checkout', 'sufo_start_checkout');
add_action('admin_post_sufo_checkout', 'sufo_start_checkout');

function sufo_start_checkout() {
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;

    if (!wp_verify_nonce($_POST['sufo_checkout_nonce'] ?? '', 'sufo_checkout_' . $post_id)) {
        wp_die(__('Your session expired. Please go back and try again.'), '', ['response' => 403, 'back_link' => true]);
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'sufo_object' || $post->post_status !== 'publish') {
        wp_die(__('This product is not available.'), '', ['back_link' => true]);
    }

    if (!sufo_is_available($post_id)) {
        wp_die(__('This product is currently unavailable.'), '', ['back_link' => true]);
    }

    if (!defined('STRIPE_RESTRICTED_KEY') || !STRIPE_RESTRICTED_KEY) {
        wp_die(__('Payments are not configured.'), '', ['back_link' => true]);
    }

    $submitted = isset($_POST['options']) && is_array($_POST['options']) ? wp_unslash($_POST['options']) : [];
    $resolved  = sufo_resolve_selection($post_id, $submitted);

    if ($resolved['total'] <= 0) {
        wp_die(__('This product cannot be purchased online.'), '', ['back_link' => true]);
    }

    // "Material: Black, Finish: Vinyl" — shown as the Stripe line-item description
    $summary = implode(', ', array_map(
        fn($sel) => $sel['label'] . ': ' . $sel['title'],
        $resolved['selected']
    ));

    $body = [
        'mode'                  => 'payment',
        'success_url'           => home_url('/?checkout=success'),
        'cancel_url'            => home_url('/?checkout=cancelled'),
        'line_items[0][quantity]'                        => 1,
        'line_items[0][price_data][currency]'            => 'eur',
        'line_items[0][price_data][unit_amount]'         => (int) round($resolved['total'] * 100),
        'metadata[post_id]'                              => $post_id,
        'metadata[selection]'                            => $summary,
    ];

    // reuse the existing Stripe Product when set, so orders roll up under one
    // product in Stripe's reporting instead of ad-hoc line items
    $stripe_product_id = get_post_meta($post_id, 'sufo_stripe_product_id', true);
    if ($stripe_product_id) {
        $body['line_items[0][price_data][product]'] = $stripe_product_id;
    } else {
        $body['line_items[0][price_data][product_data][name]']        = $post->post_title;
        $body['line_items[0][price_data][product_data][description]'] = $summary;
    }

    if ($resolved['needs_shipping']) {
        foreach (['BE', 'NL', 'LU', 'FR', 'DE'] as $i => $country) {
            $body['shipping_address_collection[allowed_countries][' . $i . ']'] = $country;
        }
    }

    $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . STRIPE_RESTRICTED_KEY,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        error_log('sufo checkout: ' . $response->get_error_message());
        wp_die(__('Could not reach the payment provider. Please try again.'), '', ['back_link' => true]);
    }

    $session = json_decode(wp_remote_retrieve_body($response), true);

    if (wp_remote_retrieve_response_code($response) !== 200 || empty($session['url'])) {
        error_log('sufo checkout: ' . wp_remote_retrieve_body($response));
        wp_die(__('Could not start checkout. Please try again.'), '', ['back_link' => true]);
    }

    wp_redirect($session['url']);
    exit;
}


// ============================================================
// 10. ADMIN PAGES
// ============================================================