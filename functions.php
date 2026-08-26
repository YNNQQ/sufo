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

// Front-end UI text hardcoded in the theme (not WP content), registered so
// it shows up on Polylang's Languages > String translations screen. No-ops
// when Polylang isn't active.
add_action('init', function () {
    if (!function_exists('pll_register_string')) return;

    $strings = [
        'Header logo fallback'          => 'Objects',
        'Primary nav landmark'          => 'Primary',
        'Secondary nav landmark'        => 'Secondary',
        'Open menu button'              => 'Open menu',
        'Footer tagline landmark'       => 'Tagline',
        'Footer studio column'          => 'Studio',
        'Footer contact column'         => 'Contact',
        'Footer Ask AI column'          => 'Ask AI',
        'Footer newsletter column'      => 'Newsletter',
        'Newsletter signup blurb'       => 'Sign up and get the latest news and insights.',
        'Newsletter consent text'       => 'By completing and sending this form you accept our privacy statement.',
        'Customise toggle label'        => 'Customise',
        'Buy button prefix'             => 'Buy for',
        'Price VAT suffix'              => 'excl. VAT',
        'Checkout error: expired session'      => 'Your session expired. Please go back and try again.',
        'Checkout error: product unavailable'  => 'This product is not available.',
        'Checkout error: temporarily unavailable' => 'This product is currently unavailable.',
        'Checkout error: payments not configured' => 'Payments are not configured.',
        'Checkout error: below minimum'        => 'This product cannot be purchased online. Please contact us to order.',
        'Checkout error: session failed'       => 'Could not start checkout. Please try again.',
        'Stripe VAT note'                => 'Incl. %1$s%% VAT (€%2$s excl. VAT)',
        'Stripe submit message'          => 'Total €%1$s includes %2$s%% Belgian VAT.',
    ];

    foreach ($strings as $name => $string) {
        pll_register_string($name, $string, 'sufo');
    }
});

// Current-language translation of a registered front-end string, or the
// string itself when Polylang isn't active.
function sufo_pll__(string $string): string {
    return function_exists('pll__') ? pll__($string) : $string;
}

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
        'scheme-glass'      => __('Glass'),
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

// single source for the Object CPT's repeater fields — label, meta key, which admin meta
// box it renders in, and which repeater columns to show
function sufo_object_fields(): array {
    return [
        'delivery'  => ['meta' => 'sufo_delivery',  'label' => 'Delivery',  'box' => 'delivery', 'color' => false, 'image' => false, 'photo' => false, 'subtitle' => false, 'shipping' => true,  'hide_sides' => false],
        'materials' => ['meta' => 'sufo_materials', 'label' => 'Material',  'box' => 'material', 'color' => true,  'image' => true,  'photo' => true,  'subtitle' => true,  'shipping' => false, 'hide_sides' => false],
        'finishes'  => ['meta' => 'sufo_finishes',  'label' => 'Finish',    'box' => 'finish',   'color' => false, 'image' => false, 'photo' => false, 'subtitle' => false, 'shipping' => false, 'hide_sides' => true],
        'sides'     => ['meta' => 'sufo_sides',     'label' => 'Sides',     'box' => 'finish',   'color' => false, 'image' => false, 'photo' => false, 'subtitle' => false, 'shipping' => false, 'hide_sides' => false],
    ];
}

// used by template-parts/object-bar.php's Customise panel group headings
function sufo_object_field_labels(): array {
    return array_map(fn($field) => $field['label'], sufo_object_fields());
}

// one meta box per concern, each saved independently (see the save_post_sufo_object
// handler below) so hiding one via Screen Options can't wipe the others' data
function sufo_object_meta_boxes(): array {
    return [
        'product'  => ['label' => 'Product',  'nonce_action' => 'sufo_product_fields',  'nonce_name' => 'sufo_product_nonce',  'render' => 'sufo_render_product_fields'],
        'material' => ['label' => 'Material', 'nonce_action' => 'sufo_material_fields', 'nonce_name' => 'sufo_material_nonce', 'render' => 'sufo_render_material_fields'],
        'finish'   => ['label' => 'Finish',   'nonce_action' => 'sufo_finish_fields',   'nonce_name' => 'sufo_finish_nonce',   'render' => 'sufo_render_finish_fields'],
        'delivery' => ['label' => 'Delivery', 'nonce_action' => 'sufo_delivery_fields', 'nonce_name' => 'sufo_delivery_nonce', 'render' => 'sufo_render_delivery_fields'],
    ];
}

add_action('add_meta_boxes', function () {
    foreach (sufo_object_meta_boxes() as $box => $config) {
        add_meta_box('sufo_' . $box . '_fields', __($config['label']), $config['render'], 'sufo_object', 'normal', 'default');
    }
});

function sufo_render_product_fields($post) {
    wp_nonce_field('sufo_product_fields', 'sufo_product_nonce');

    $price      = get_post_meta($post->ID, 'sufo_price', true);
    $available  = sufo_is_available($post->ID);
    $stripe_id  = get_post_meta($post->ID, 'sufo_stripe_product_id', true);

    echo '<div class="sufo-meta-box">';
    echo '<div class="sufo-field"><label>Price</label><input type="number" step="0.01" name="sufo_price" value="' . esc_attr($price) . '" placeholder="Base price"></div>';
    echo '<div class="sufo-field"><label><input type="checkbox" name="sufo_available" value="1"' . checked($available, true, false) . '> Available</label></div>';
    echo '<div class="sufo-field"><label>Stripe Product ID</label><input type="text" name="sufo_stripe_product_id" value="' . esc_attr($stripe_id) . '" placeholder="prod_…"></div>';
    echo '</div>';
}

// renders every sufo_object_fields() repeater whose 'box' matches — Finish holds both
// the Finishes repeater and the Sides repeater (Same / Different), Material and Delivery
// each hold just their own
function sufo_render_box_fields($post, string $box) {
    echo '<div class="sufo-meta-box">';
    foreach (sufo_object_fields() as $field) {
        if ($field['box'] !== $box) continue;
        $items = get_post_meta($post->ID, $field['meta'], true) ?: [[]];
        sufo_render_media_repeater_field($field['label'], $field['meta'], $items, $field['color'], $field['image'], $field['photo'], $field['subtitle'], $field['shipping'], $field['hide_sides']);
    }
    echo '</div>';
}

function sufo_render_material_fields($post) {
    wp_nonce_field('sufo_material_fields', 'sufo_material_nonce');
    sufo_render_box_fields($post, 'material');
}

function sufo_render_finish_fields($post) {
    wp_nonce_field('sufo_finish_fields', 'sufo_finish_nonce');
    sufo_render_box_fields($post, 'finish');
}

function sufo_render_delivery_fields($post) {
    wp_nonce_field('sufo_delivery_fields', 'sufo_delivery_nonce');
    sufo_render_box_fields($post, 'delivery');
}

// materials / finishes: title + subtitle + optional color/image(s) repeater
// name="x[][a]" / name="x[][b]" auto-indexing splits them into separate rows
function sufo_render_media_repeater_field($label, $name, $items, $show_color = true, $show_image = true, $show_photo = false, $show_subtitle = true, $show_shipping = false, $show_hide_sides = false) {
    echo '<div class="sufo-field"><label>' . esc_html($label) . '</label>';
    echo '<div class="sufo-repeater" data-repeater="' . esc_attr($name) . '">';
    foreach ($items as $index => $item) {
        echo sufo_media_row($name, $item, 'row-' . $index, $show_color, $show_image, $show_photo, $show_subtitle, $show_shipping, $show_hide_sides);
    }
    echo '<template class="sufo-repeater-template">' . sufo_media_row($name, [], '__index__', $show_color, $show_image, $show_photo, $show_subtitle, $show_shipping, $show_hide_sides) . '</template>';
    echo '</div>';
    echo '<button type="button" class="sufo-add-row" data-target="' . esc_attr($name) . '">+ Add item</button></div>';
}

function sufo_media_row($name, $item, $index, $show_color = true, $show_image = true, $show_photo = false, $show_subtitle = true, $show_shipping = false, $show_hide_sides = false) {
    $title    = $item['title'] ?? '';
    $subtitle = $item['subtitle'] ?? '';
    $price    = $item['price'] ?? '';
    $prefix   = esc_attr($name) . '[' . esc_attr($index) . ']';

    $html = '<div class="sufo-repeater-row sufo-repeater-row--media">'
        . '<input type="text" name="' . $prefix . '[title]" value="' . esc_attr($title) . '" placeholder="Title">'
        . ($show_subtitle ? '<input type="text" name="' . $prefix . '[subtitle]" value="' . esc_attr($subtitle) . '" placeholder="Subtitle">' : '')
        . '<input type="number" step="0.01" name="' . $prefix . '[price]" value="' . esc_attr($price) . '" placeholder="Additional price">'
        // marks a delivery option that needs a real address collected at checkout
        . ($show_shipping ? '<label class="sufo-repeater-row__flag"><input type="checkbox" name="' . $prefix . '[shipping]" value="1"' . checked(!empty($item['shipping']), true, false) . '> Requires shipping address</label>' : '')
        // marks a finish that hides the Sides field entirely — the front end and checkout
        // both use this flag (not the option's title) to gate the Sides field
        . ($show_hide_sides ? '<label class="sufo-repeater-row__flag"><input type="checkbox" name="' . $prefix . '[hide_sides]" value="1"' . checked(!empty($item['hide_sides']), true, false) . '> Hide Sides</label>' : '');

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

// each box is only saved if its own nonce is present and valid, so a box hidden via
// Screen Options (its nonce field never renders) simply doesn't get touched — it can't
// wipe meta belonging to the other boxes
add_action('save_post_sufo_object', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['sufo_product_nonce']) && wp_verify_nonce($_POST['sufo_product_nonce'], 'sufo_product_fields')) {
        $price = isset($_POST['sufo_price']) && $_POST['sufo_price'] !== '' ? (float) $_POST['sufo_price'] : 0;
        update_post_meta($post_id, 'sufo_price', $price);
        update_post_meta($post_id, 'sufo_available', isset($_POST['sufo_available']) ? '1' : '0');
        update_post_meta($post_id, 'sufo_stripe_product_id', sanitize_text_field($_POST['sufo_stripe_product_id'] ?? ''));
    }

    $verified_boxes = [];
    foreach (sufo_object_meta_boxes() as $box => $config) {
        if ($box === 'product') continue;
        if (isset($_POST[$config['nonce_name']]) && wp_verify_nonce($_POST[$config['nonce_name']], $config['nonce_action'])) {
            $verified_boxes[] = $box;
        }
    }

    foreach (sufo_object_fields() as $field) {
        if (!in_array($field['box'], $verified_boxes, true)) continue;

        $clean = [];
        foreach (($_POST[$field['meta']] ?? []) as $row) {
            $title    = sanitize_text_field($row['title'] ?? '');
            $subtitle = sanitize_text_field($row['subtitle'] ?? '');
            $image_id = absint($row['image_id'] ?? 0);
            $photo_id = absint($row['photo_id'] ?? 0);
            $color    = sanitize_hex_color($row['color'] ?? '');
            $row_price  = isset($row['price']) && $row['price'] !== '' ? (float) $row['price'] : 0;
            $shipping   = !empty($row['shipping']);
            $hide_sides = !empty($row['hide_sides']);

            if ($title === '' && $subtitle === '' && !$image_id && !$photo_id && !$row_price) continue;

            $clean[] = ['title' => $title, 'subtitle' => $subtitle, 'image_id' => $image_id, 'photo_id' => $photo_id, 'color' => $color, 'price' => $row_price, 'shipping' => $shipping, 'hide_sides' => $hide_sides];
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
            '<div class="wp-block-column"><button type="button" class="material-picker card card__inside" style="--swatch-color:%s" data-image="%s" data-srcset="%s" data-alt="%s" aria-pressed="%s"><span class="material-picker__swatch">%s</span><span class="material-picker__title h5">%s</span><span class="material-picker__subtitle h5">%s</span></button></div>',
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

function sufo_render_object_options(array $items, bool $show_swatch): string {
    $options = '';
    foreach ($items as $index => $item) {
        $price = (float) ($item['price'] ?? 0);

        $options .= sprintf(
            '<button type="button" class="object-picker__option button" data-price="%s" data-index="%s" aria-pressed="%s"%s>%s<span class="object-picker__option-label">%s</span></button>',
            esc_attr($price),
            esc_attr($index),
            $index === 0 ? 'true' : 'false',
            !empty($item['hide_sides']) ? ' data-hide-sides="1"' : '',
            $show_swatch ? sufo_picker_swatch($item) : '',
            esc_html($item['title'] ?? '')
        );

        $options .= sprintf(
            '<span class="object-picker__option-price">%s</span>',
            $price != 0 ? ($price > 0 ? '+' : '-') . '€' . esc_html(sufo_format_price(abs($price))) : ''
        );
    }
    return $options;
}

function sufo_render_customise_group(string $type, string $label, array $items, bool $show_swatch, string $field_key = ''): string {
    if (empty($items)) return '';

    return sprintf(
        '<div class="object-bar__customise-group" data-customise-slot="%1$s" data-field-key="%2$s">
            <span class="object-bar__customise-label">%3$s</span>
            <div class="island object-picker__panel">%4$s</div>
        </div>',
        esc_attr($type),
        esc_attr($field_key ?: $type),
        esc_html($label),
        sufo_render_object_options($items, $show_swatch)
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

        if ($is_section && !empty($block['attrs']['className'])) {
            $classes = explode(' ', $block['attrs']['className']);

            $filtered = array_filter($classes, function ($cls) {
                return !str_starts_with($cls, 'section--') && $cls !== 'section' && !str_starts_with($cls, 'scheme-');
            });

            if (!empty($filtered)) {
                $block['attrs']['className'] = implode(' ', $filtered);
            } else {
                unset($block['attrs']['className']);
            }
        }

        $block_html = render_block($block);

        if ($is_section) {
            $block_html = preg_replace_callback('/\bclass="([^"]*)"/i', function ($m) {
                $classes = preg_replace('/\bsection(?:--[\w-]+)?\b/', '', $m[1]);
                $classes = preg_replace('/\s{2,}/', ' ', trim($classes));
                return 'class="' . $classes . '"';
            }, $block_html);

            $block_html = preg_replace_callback('/^(\s*<[a-z0-9]+\b[^>]*\bclass=")([^"]*)(")/i', function ($m) {
                $classes = preg_replace('/\bscheme-[\w-]+\b/', '', $m[2]);
                $classes = preg_replace('/\s{2,}/', ' ', trim($classes));
                return $m[1] . $classes . $m[3];
            }, $block_html, 1);
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

function sufo_vat_rate(): float {
    return (float) apply_filters('sufo_vat_rate', 0.21);
}

function sufo_add_vat(float $amount): float {
    return round($amount * (1 + sufo_vat_rate()), 2);
}

function sufo_resolve_selection(int $post_id, array $submitted): array {
    $options   = sufo_get_object_options($post_id);
    $labels    = sufo_object_field_labels();
    $total     = sufo_get_price($post_id);
    $selected  = [];
    $needs_shipping = false;

    // a finish flagged "hide sides" has nothing to personalise per side — re-resolved by
    // index the same tamper-proof way, independent of loop order below, since it gates
    // the 'sides' field before that key is necessarily reached
    $finish_items = $options['finishes'] ?? [];
    $finish_index = isset($submitted['finishes']) ? (int) $submitted['finishes'] : 0;
    $finish_item  = $finish_items[$finish_index] ?? $finish_items[0] ?? null;
    $hide_sides   = !empty($finish_item['hide_sides']);

    foreach ($options as $key => $items) {
        $index = isset($submitted[$key]) ? (int) $submitted[$key] : 0;
        if ($key === 'sides' && $hide_sides) $index = 0; // force back to the default option

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

// Stripe sends the customer back here after paying — sync the order right away so
// it doesn't sit at "Awaiting payment" until someone presses Sync by hand
add_action('template_redirect', function (): void {
    if (($_GET['checkout'] ?? '') !== 'success' || empty($_GET['session_id'])) return;

    $order_id = sufo_find_order_by_session(sanitize_text_field(wp_unslash($_GET['session_id'])));
    if (!$order_id) return;

    $result = sufo_sync_order_from_stripe($order_id);
    if (is_wp_error($result)) {
        error_log('sufo order sync (' . $order_id . '): ' . $result->get_error_message());
    }
});

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
        wp_die(sufo_pll__('Your session expired. Please go back and try again.'), '', ['response' => 403, 'back_link' => true]);
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'sufo_object' || $post->post_status !== 'publish') {
        wp_die(sufo_pll__('This product is not available.'), '', ['response' => 404, 'back_link' => true]);
    }

    if (!sufo_is_available($post_id)) {
        wp_die(sufo_pll__('This product is currently unavailable.'), '', ['response' => 409, 'back_link' => true]);
    }

    if (!defined('STRIPE_RESTRICTED_KEY') || !STRIPE_RESTRICTED_KEY) {
        wp_die(sufo_pll__('Payments are not configured.'), '', ['response' => 503, 'back_link' => true]);
    }

    $submitted = isset($_POST['options']) && is_array($_POST['options']) ? wp_unslash($_POST['options']) : [];
    $resolved  = sufo_resolve_selection($post_id, $submitted);

    // Stripe rejects card charges under ~€0.50; a 0 total means the object is
    // misconfigured (no base price), not that checkout itself failed
    if ($resolved['total'] < 0.5) {
        wp_die(sufo_pll__('This product cannot be purchased online. Please contact us to order.'), '', ['response' => 409, 'back_link' => true]);
    }

    // "Material: Black, Finish: Vinyl" — shown as the Stripe line-item description
    $summary = implode(', ', array_map(
        fn($sel) => $sel['label'] . ': ' . $sel['title'],
        $resolved['selected']
    ));

    $total_incl_vat = sufo_add_vat($resolved['total']);

    // With a Stripe Tax Rate configured, send the net amount and let Stripe add
    // and itemise the VAT; otherwise fall back to charging the gross amount as
    // one undifferentiated line.
    $tax_rate_id = defined('STRIPE_TAX_RATE_ID') ? trim(STRIPE_TAX_RATE_ID) : '';
    $unit_amount = $tax_rate_id ? $resolved['total'] : $total_incl_vat;

    $body = [
        'mode'                  => 'payment',
        // Stripe substitutes the real id, letting the return visit sync the order
        'success_url'           => home_url('/?checkout=success&session_id={CHECKOUT_SESSION_ID}'),
        'cancel_url'            => home_url('/?checkout=cancelled'),
        'line_items[0][quantity]'                        => 1,
        'line_items[0][price_data][currency]'            => 'eur',
        'line_items[0][price_data][unit_amount]'         => (int) round($unit_amount * 100),
        'metadata[post_id]'                              => $post_id,
        'metadata[selection]'                            => $summary,
        'metadata[net_amount]'                           => number_format($resolved['total'], 2, '.', ''),
        'metadata[vat_rate]'                             => sufo_vat_rate(),
        // lets the customer add a VAT number on Stripe's page — recorded on the
        // invoice so they can reclaim it; it never changes what they're charged
        'tax_id_collection[enabled]'                     => 'true',
    ];

    if ($tax_rate_id) {
        $body['line_items[0][tax_rates][0]'] = $tax_rate_id;
    }

    $vat_pct  = rtrim(rtrim(number_format(sufo_vat_rate() * 100, 2, '.', ''), '0'), '.');
    $vat_note = sprintf(
        /* translators: 1: VAT percentage, 2: net amount */
        sufo_pll__('Incl. %1$s%% VAT (€%2$s excl. VAT)'),
        $vat_pct,
        sufo_format_price($resolved['total'])
    );

    // shown directly above the pay button on Stripe's page — the one VAT label
    // that appears regardless of whether a tax rate or Product ID is configured
    $body['custom_text[submit][message]'] = sprintf(
        /* translators: 1: total incl. VAT, 2: VAT percentage */
        sufo_pll__('Total €%1$s includes %2$s%% Belgian VAT.'),
        sufo_format_price($total_incl_vat),
        $vat_pct
    );

    // reuse the existing Stripe Product when set, so orders roll up under one
    // product in Stripe's reporting instead of ad-hoc line items
    $stripe_product_id = get_post_meta($post_id, 'sufo_stripe_product_id', true);
    if ($stripe_product_id) {
        $body['line_items[0][price_data][product]'] = $stripe_product_id;
    } else {
        $body['line_items[0][price_data][product_data][name]'] = $post->post_title;
        // Stripe itemises the VAT itself when a tax rate is attached
        $body['line_items[0][price_data][product_data][description]'] = $tax_rate_id ? $summary : $summary . ' — ' . $vat_note;
    }

    if ($resolved['needs_shipping']) {
        $body['shipping_address_collection[allowed_countries][0]'] = 'BE';
    }

    $session = sufo_stripe_request('checkout/sessions', $body);

    if (is_wp_error($session)) {
        error_log('sufo checkout: ' . $session->get_error_message());
        wp_die(sufo_pll__('Could not start checkout. Please try again.'), '', ['response' => 502, 'back_link' => true]);
    }

    $order_id = sufo_create_order([
        'post_id'    => $post_id,
        'session_id' => $session['id'] ?? '',
        'net'        => $resolved['total'],
        'total'      => $total_incl_vat,
        'selected'   => $resolved['selected'],
    ]);

    wp_redirect($session['url']);
    exit;
}

// One entry point for the Stripe REST API. $body omitted = GET, else POST.
// Returns the decoded response, or WP_Error on transport/API failure.
function sufo_stripe_request(string $path, ?array $body = null) {
    if (!defined('STRIPE_RESTRICTED_KEY') || !STRIPE_RESTRICTED_KEY) {
        return new WP_Error('sufo_stripe_no_key', 'Stripe key is not configured.');
    }

    $args = [
        'method'  => $body === null ? 'GET' : 'POST',
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . STRIPE_RESTRICTED_KEY,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
    ];
    if ($body !== null) $args['body'] = $body;

    $response = wp_remote_request('https://api.stripe.com/v1/' . $path, $args);

    if (is_wp_error($response)) return $response;

    $decoded = json_decode(wp_remote_retrieve_body($response), true);

    if (wp_remote_retrieve_response_code($response) !== 200) {
        return new WP_Error('sufo_stripe_error', $decoded['error']['message'] ?? 'Unknown Stripe error.');
    }

    return $decoded;
}


// ============================================================
// 10. ORDERS CPT
// ============================================================

add_action('init', function () {
    register_post_type('sufo_order', [
        'labels' => [
            'name'               => __('Orders'),
            'singular_name'      => __('Order'),
            'edit_item'          => __('Edit order'),
            'view_item'          => __('View order'),
            'search_items'       => __('Search orders'),
            'not_found'          => __('No orders found'),
            'not_found_in_trash' => __('No orders in trash'),
        ],
        'public'          => false,   // orders are admin-only records, never a front-end URL
        'show_ui'         => true,
        'show_in_menu'    => true,
        'menu_icon'       => 'dashicons-clipboard',
        'menu_position'   => 8,
        'supports'        => ['title'],
        'capability_type' => 'post',
        'capabilities'    => ['create_posts' => 'do_not_allow'], // only checkout creates orders
        'map_meta_cap'    => true,
    ]);
});

function sufo_order_statuses(): array {
    return [
        'pending_payment' => __('Awaiting payment'),
        'paid'            => __('Paid'),
        'in_production'   => __('In production'),
        'completed'       => __('Completed'),
        'cancelled'       => __('Cancelled'),
    ];
}

function sufo_order_status_label(string $status): string {
    return sufo_order_statuses()[$status] ?? $status;
}

function sufo_order_status_color(string $status): string {
    return match ($status) {
        'paid'          => '#15803d',
        'in_production' => '#1d4ed8',
        'completed'     => '#6b7280',
        'cancelled'     => '#dc2626',
        default         => '#b45309',
    };
}

function sufo_create_order(array $data): int {
    $post_id  = (int) ($data['post_id'] ?? 0);
    $product  = get_post($post_id);
    $selected = $data['selected'] ?? [];

    $order_id = wp_insert_post([
        'post_type'   => 'sufo_order',
        'post_status' => 'publish',
        'post_title'  => sprintf('%s — %s', $product ? $product->post_title : __('Order'), current_time('d/m/Y H:i')),
    ]);

    if (!$order_id || is_wp_error($order_id)) return 0;

    update_post_meta($order_id, '_sufo_order_status',        'pending_payment');
    update_post_meta($order_id, '_sufo_order_session_id',    sanitize_text_field($data['session_id'] ?? ''));
    update_post_meta($order_id, '_sufo_order_total_cents',   (int) round(((float) ($data['total'] ?? 0)) * 100));
    update_post_meta($order_id, '_sufo_order_net_cents',     (int) round(((float) ($data['net'] ?? 0)) * 100));
    update_post_meta($order_id, '_sufo_order_vat_rate',      sufo_vat_rate());
    update_post_meta($order_id, '_sufo_order_product_id',    $post_id);
    update_post_meta($order_id, '_sufo_order_product_name',  $product ? $product->post_title : '');
    update_post_meta($order_id, '_sufo_order_stripe_product_id', get_post_meta($post_id, 'sufo_stripe_product_id', true));

    // one meta row per option axis, so Material/Finish/Delivery stay separately queryable
    foreach ($selected as $key => $sel) {
        update_post_meta($order_id, '_sufo_order_opt_' . $key, sanitize_text_field($sel['title'] ?? ''));
    }
    update_post_meta($order_id, '_sufo_order_options', wp_json_encode($selected));

    return $order_id;
}

function sufo_find_order_by_session(string $session_id): int {
    if (!$session_id) return 0;

    $found = get_posts([
        'post_type'      => 'sufo_order',
        'post_status'    => 'publish',
        'numberposts'    => 1,
        'fields'         => 'ids',
        'meta_key'       => '_sufo_order_session_id',
        'meta_value'     => $session_id,
    ]);

    return $found[0] ?? 0;
}

// Pulls customer details, address and payment state from Stripe onto the order.
// Used by both the Sync button and the automatic sync on checkout return.
function sufo_sync_order_from_stripe(int $order_id) {
    $session_id = get_post_meta($order_id, '_sufo_order_session_id', true);
    if (!$session_id) return new WP_Error('sufo_no_session', __('This order has no Stripe session.'));

    $session = sufo_stripe_request('checkout/sessions/' . $session_id);
    if (is_wp_error($session)) return $session;

    $customer = $session['customer_details'] ?? [];
    if (!empty($customer['name']))  update_post_meta($order_id, '_sufo_order_customer_name',  sanitize_text_field($customer['name']));
    if (!empty($customer['email'])) update_post_meta($order_id, '_sufo_order_customer_email', sanitize_email($customer['email']));
    if (!empty($customer['phone'])) update_post_meta($order_id, '_sufo_order_customer_phone', sanitize_text_field($customer['phone']));

    $address = sufo_format_stripe_address($session);
    if ($address) update_post_meta($order_id, '_sufo_order_address', $address);

    // VAT number the customer added on Stripe's page (invoice reference only —
    // domestic Belgian B2B is still charged 21%, so it never alters the total)
    $tax_id = $customer['tax_ids'][0]['value'] ?? '';
    if ($tax_id) update_post_meta($order_id, '_sufo_order_vat_number', sanitize_text_field($tax_id));

    // Stripe puts the business name in the billing name when a VAT number is added
    if (!empty($customer['business_name'])) {
        update_post_meta($order_id, '_sufo_order_company', sanitize_text_field($customer['business_name']));
    }

    if (!empty($session['amount_total'])) {
        update_post_meta($order_id, '_sufo_order_total_cents', (int) $session['amount_total']);
    }

    // only ever advance out of pending — never overwrite a status set by hand
    $paid = in_array($session['payment_status'] ?? '', ['paid', 'no_payment_required'], true);
    if ($paid && get_post_meta($order_id, '_sufo_order_status', true) === 'pending_payment') {
        update_post_meta($order_id, '_sufo_order_status', 'paid');
    }

    update_post_meta($order_id, '_sufo_order_synced_at', current_time('mysql'));

    return true;
}

function sufo_format_stripe_address(array $session): string {
    $shipping = $session['shipping_details'] ?? $session['collected_information']['shipping_details'] ?? null;
    $address  = $shipping['address'] ?? $session['customer_details']['address'] ?? null;
    $name     = $shipping['name'] ?? $session['customer_details']['name'] ?? '';

    if (empty($address) || empty($address['line1'])) return '';

    $lines = array_filter([
        $name,
        $address['line1'] ?? '',
        $address['line2'] ?? '',
        trim(($address['postal_code'] ?? '') . ' ' . ($address['city'] ?? '')),
        $address['country'] ?? '',
    ]);

    return sanitize_textarea_field(implode("\n", $lines));
}

// Orders list columns
add_filter('manage_sufo_order_posts_columns', function (array $columns): array {
    return [
        'cb'             => $columns['cb'],
        'title'          => __('Order'),
        'order_status'   => __('Status'),
        'order_customer' => __('Customer'),
        'order_options'  => __('Configuration'),
        'order_total'    => __('Total'),
        'date'           => __('Date'),
    ];
});

add_action('manage_sufo_order_posts_custom_column', function (string $column, int $post_id): void {
    switch ($column) {
        case 'order_status':
            $status = get_post_meta($post_id, '_sufo_order_status', true) ?: 'pending_payment';
            printf(
                '<span style="color:%s;font-weight:600;">%s</span>',
                esc_attr(sufo_order_status_color($status)),
                esc_html(sufo_order_status_label($status))
            );
            break;

        case 'order_customer':
            $name    = get_post_meta($post_id, '_sufo_order_customer_name', true);
            $email   = get_post_meta($post_id, '_sufo_order_customer_email', true);
            $company = get_post_meta($post_id, '_sufo_order_company', true);
            $vat     = get_post_meta($post_id, '_sufo_order_vat_number', true);
            if ($name)    echo esc_html($name) . '<br>';
            if ($company) echo '<small>' . esc_html($company) . '</small><br>';
            if ($email)   printf('<a href="mailto:%1$s">%1$s</a><br>', esc_attr($email));
            if ($vat)     echo '<small><code>' . esc_html($vat) . '</code></small>';
            if (!$name && !$email) echo '<span style="color:#a7aaad;">—</span>';
            break;

        case 'order_options':
            $options = json_decode(get_post_meta($post_id, '_sufo_order_options', true) ?: '[]', true);
            foreach ($options as $sel) {
                printf('<small>%s: <strong>%s</strong></small><br>', esc_html($sel['label'] ?? ''), esc_html($sel['title'] ?? ''));
            }
            break;

        case 'order_total':
            $cents = (int) get_post_meta($post_id, '_sufo_order_total_cents', true);
            echo '€' . esc_html(number_format($cents / 100, 2, ',', '.'));
            break;
    }
}, 10, 2);

add_filter('manage_edit-sufo_order_sortable_columns', fn(array $columns): array => $columns + ['order_status' => 'order_status']);

add_action('add_meta_boxes', function () {
    add_meta_box('sufo-order-details', __('Order details'), 'sufo_render_order_details_meta_box', 'sufo_order', 'normal', 'high');
    add_meta_box('sufo-order-status',  __('Status'),        'sufo_render_order_status_meta_box',  'sufo_order', 'side',   'high');
});

function sufo_render_order_details_meta_box(WP_Post $post): void {
    $meta = fn(string $key) => get_post_meta($post->ID, '_sufo_order_' . $key, true);

    $options    = json_decode($meta('options') ?: '[]', true);
    $product_id = (int) $meta('product_id');
    $session_id = $meta('session_id');
    $synced_at  = $meta('synced_at');
    $rows       = [];

    $rows[__('Product')] = $meta('product_name')
        . ($product_id ? sprintf(' <a href="%s">(edit)</a>', esc_url(get_edit_post_link($product_id))) : '');

    if ($meta('stripe_product_id')) {
        $rows[__('Stripe product')] = '<code>' . esc_html($meta('stripe_product_id')) . '</code>';
    }

    foreach ($options as $sel) {
        $rows[$sel['label'] ?? ''] = esc_html($sel['title'] ?? '');
    }

    $net_cents   = (int) $meta('net_cents');
    $total_cents = (int) $meta('total_cents');
    $vat_pct     = rtrim(rtrim(number_format(((float) $meta('vat_rate')) * 100, 2, '.', ''), '0'), '.');

    if ($net_cents) {
        $rows[__('Price excl. VAT')] = '€' . esc_html(number_format($net_cents / 100, 2, ',', '.'));
        $rows[sprintf(__('VAT (%s%%)'), $vat_pct)] = '€' . esc_html(number_format(($total_cents - $net_cents) / 100, 2, ',', '.'));
    }
    $rows[__('Total incl. VAT')] = '<strong>€' . esc_html(number_format($total_cents / 100, 2, ',', '.')) . '</strong>';
    $rows[__('Ordered')] = esc_html(get_the_date('d/m/Y H:i', $post));

    $name  = $meta('customer_name');
    $email = $meta('customer_email');
    $phone = $meta('customer_phone');
    if ($name)  $rows[__('Name')]  = esc_html($name);
    if ($email) $rows[__('Email')] = sprintf('<a href="mailto:%1$s">%1$s</a>', esc_attr($email));
    if ($phone) $rows[__('Phone')] = esc_html($phone);
    if ($meta('company'))    $rows[__('Company')]    = esc_html($meta('company'));
    if ($meta('vat_number')) $rows[__('VAT number')] = '<code>' . esc_html($meta('vat_number')) . '</code>';
    if ($meta('address')) $rows[__('Delivery address')] = nl2br(esc_html($meta('address')));

    if ($session_id) {
        $rows[__('Stripe session')] = sprintf(
            '<code>%s</code> <a href="https://dashboard.stripe.com/payments?query=%s" target="_blank" rel="noopener">%s</a>',
            esc_html($session_id),
            esc_attr($session_id),
            esc_html__('Open in Stripe')
        );
    }
    ?>
    <table class="widefat striped">
        <tbody>
        <?php foreach ($rows as $label => $value) : ?>
            <tr>
                <th style="width:180px;"><?php echo esc_html($label); ?></th>
                <td><?php echo wp_kses_post($value); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($session_id) : ?>
        <p style="margin-top:12px;">
            <a href="<?php echo esc_url(wp_nonce_url(
                admin_url('admin-post.php?action=sufo_sync_order&order_id=' . $post->ID),
                'sufo_sync_order_' . $post->ID
            )); ?>" class="button">&#8635; <?php esc_html_e('Sync from Stripe'); ?></a>
            <span style="margin-left:8px;color:#777;font-size:12px;">
                <?php esc_html_e('Fetches customer details, delivery address and payment status.'); ?>
                <?php if ($synced_at) printf(esc_html__('Last synced %s'), esc_html($synced_at)); ?>
            </span>
        </p>
    <?php endif; ?>
    <?php
}

function sufo_render_order_status_meta_box(WP_Post $post): void {
    $current = get_post_meta($post->ID, '_sufo_order_status', true) ?: 'pending_payment';
    wp_nonce_field('sufo_order_status', 'sufo_order_status_nonce');
    ?>
    <select name="sufo_order_status" style="width:100%;">
        <?php foreach (sufo_order_statuses() as $value => $label) : ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}

add_action('save_post_sufo_order', function (int $post_id): void {
    if (!isset($_POST['sufo_order_status_nonce']) || !wp_verify_nonce($_POST['sufo_order_status_nonce'], 'sufo_order_status')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $status = sanitize_text_field($_POST['sufo_order_status'] ?? '');
    if (isset(sufo_order_statuses()[$status])) {
        update_post_meta($post_id, '_sufo_order_status', $status);
    }
});

add_action('admin_post_sufo_sync_order', function (): void {
    $order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

    if (!$order_id || !current_user_can('edit_post', $order_id)) wp_die(__('You are not allowed to do this.'));
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'sufo_sync_order_' . $order_id)) wp_die(__('Invalid request.'));

    $result = sufo_sync_order_from_stripe($order_id);

    wp_safe_redirect(add_query_arg([
        'post'   => $order_id,
        'action' => 'edit',
        'synced' => is_wp_error($result) ? 'error' : '1',
    ], admin_url('post.php')));
    exit;
});

add_action('admin_notices', function (): void {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'sufo_order' || empty($_GET['synced'])) return;

    if ($_GET['synced'] === 'error') {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Could not sync from Stripe. Check the error log for details.') . '</p></div>';
    } else {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Order synced from Stripe.') . '</p></div>';
    }
});


// ============================================================
// 11. ADMIN PAGES
// ============================================================