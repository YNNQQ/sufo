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
        .sufo-repeater-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
        .sufo-repeater-row input[type="text"] { flex: 1; max-width: 380px; }
        .sufo-remove-row { background: none; border: 1px solid #d63638; color: #d63638; border-radius: 3px; cursor: pointer; padding: 3px 8px; font-size: 13px; line-height: 1.6; }
        .sufo-remove-row:hover { background: #d63638; color: #fff; }
        .sufo-field.sufo-conditional { display: none; }
        .sufo-field.sufo-conditional.visible { display: flex; }
        .sufo-checkbox-row { display: flex; align-items: center; gap: 8px; padding: 6px 0; }
        .sufo-checkbox-row label { font-weight: 700; font-size: 13px; color: #1d2327; cursor: pointer; }
        .sufo-image-preview { max-width: 120px; height: auto; display: block; margin-top: 8px; border-radius: 4px; }
        .sufo-image-col { display: flex; flex-direction: column; }
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

    // Gallery carousel: only load Swiper + its init script on pages that
    // actually contain a gallery block
    if (has_block('core/gallery')) {

        wp_enqueue_script(
            'sufo-gallery-swiper',
            get_template_directory_uri() . '/assets/js/gallery-swiper.js',
            ['swiper'],
            filemtime(get_template_directory() . '/assets/js/gallery-swiper.js'),
            true
        );
    }

    // Material picker: only load on pages with a .section--material
    $current_post = get_post();
    if ($current_post && str_contains($current_post->post_content, 'section--material')) {
        wp_enqueue_script(
            'sufo-material-picker',
            get_template_directory_uri() . '/assets/js/material-picker.js',
            [],
            filemtime(get_template_directory() . '/assets/js/material-picker.js'),
            true
        );
    }
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

/**
 * Material picker data for .section--material.
 *
 * Real content should be authored via the `sufo_materials` post meta — an
 * array of ['label' => string, 'color' => hex, 'image_id' => int, 'alt' =>
 * string], one per swatch. No meta box exists for it yet, so until one is
 * built (or ACF/similar is used to add the fields), this falls back to a
 * placeholder set reusing the section's own image so the interaction is
 * demonstrable.
 */
function sufo_get_materials(int $post_id): array {
    $materials = get_post_meta($post_id, 'sufo_materials', true);

    if (is_array($materials) && !empty($materials)) {
        return $materials;
    }

    return [
        ['label' => 'Black',      'color' => '#343434', 'image_id' => 16, 'alt' => 'SIGN/01 in black'],
        ['label' => 'Brown',      'color' => '#5c4a3d', 'image_id' => 16, 'alt' => 'SIGN/01 in brown'],
        ['label' => 'Grey',       'color' => '#b9b9b5', 'image_id' => 16, 'alt' => 'SIGN/01 in grey'],
        ['label' => 'Light grey', 'color' => '#e7e6e2', 'image_id' => 16, 'alt' => 'SIGN/01 in light grey'],
    ];
}

/**
 * Fills the empty picker columns in .section--material with real buttons
 * and tags the section's existing image as the swap target. The 4 columns
 * are authored as empty placeholders in the editor (see the rendered
 * .wp-block-column is-layout-flow wp-block-column-is-layout-flow markup),
 * so they're replaced one at a time, in order, rather than parsed as blocks.
 */
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
        $image_url    = $image_id ? wp_get_attachment_image_url($image_id, 'large') : '';
        $image_srcset = $image_id ? wp_get_attachment_image_srcset($image_id, 'large') : '';

        $button = sprintf(
            '<div class="wp-block-column"><button type="button" class="material-picker" style="--swatch-color:%s" data-image="%s" data-srcset="%s" data-alt="%s" aria-pressed="%s"><span class="material-picker__swatch"></span><span class="material-picker__label">%s</span></button></div>',
            esc_attr($material['color'] ?? ''),
            esc_url($image_url ?: ''),
            esc_attr($image_srcset ?: ''),
            esc_attr($material['alt'] ?? ''),
            $index === 0 ? 'true' : 'false',
            esc_html($material['label'] ?? '')
        );

        $section_html = preg_replace('/' . preg_quote($empty_column, '/') . '/', $button, $section_html, 1);
    }

    return $section_html;
}

function render_sections($content) {
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

            $section_classes_array = array_filter($classes, fn($cls) => str_starts_with($cls, 'section--') || str_starts_with($cls, 'scheme-'));

            $scheme_attr = trim($block['attrs']['scheme'] ?? '');
            if ($scheme_attr) {
                $section_classes_array[] = $scheme_attr;
            }

            $section_classes = 'section ' . implode(' ', $section_classes_array);

            $section_html  = '<section class="' . esc_attr($section_classes) . '">';
            $section_html .= '<div class="section-container">';
            $section_html .= $block_html;
            $section_html .= '</div>';
            $section_html .= '</section>';

            $section_html = sufo_inject_material_pickers($section_html, get_the_ID());

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