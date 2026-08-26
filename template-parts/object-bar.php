<?php
$post_id = $args['post_id'] ?? 0;
$post    = get_post($post_id);
if (!$post) return;

$options   = sufo_get_object_options($post_id);
$colors    = $options['colors'] ?? [];
$finishes  = $options['finishes'] ?? [];
$sides     = $options['sides'] ?? [];
$delivery  = $options['delivery'] ?? [];
$price     = sufo_get_price($post_id);

$field_labels = sufo_object_field_labels();

$chevron = file_get_contents(get_template_directory() . '/assets/svg/chevron.svg');

$modal = get_posts(['post_type' => 'sufo_modal', 'post_status' => 'publish', 'numberposts' => 1])[0] ?? null;
?>
<?php echo sufo_product_schema_json_ld($post_id); ?>
<nav class="bottom-nav">

    <div class="island object-bar scheme-white" data-object-bar data-base-price="<?php echo esc_attr($price); ?>">
        <span class="object-bar__name label"><?php echo esc_html($post->post_title); ?></span>

        <div class="menu menu--mobile" data-object-picker="customise" data-generic-label="<?php echo esc_attr(sufo_pll__('Configure')); ?>">
            <button type="button" class="menu__toggle button" aria-expanded="false" aria-haspopup="dialog">
                <span class="menu__label"><?php echo esc_html(sufo_pll__('Configure')); ?></span>
                <span class="icon"><?php echo $chevron; ?></span>
            </button>
            <div class="menu__panel" hidden>
                <div class="island object-bar__customise-list">
                    <?php
                    echo sufo_render_customise_group('color', $field_labels['colors'], $colors, true, 'colors');
                    echo sufo_render_customise_group('finish', $field_labels['finishes'], $finishes, false, 'finishes');
                    echo sufo_render_customise_group('sides', $field_labels['sides'], $sides, false, 'sides');
                    echo sufo_render_customise_group('delivery', $field_labels['delivery'], $delivery, false, 'delivery');
                    ?>
                </div>
            </div>
        </div>

        <form class="object-bar__checkout" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-checkout-form>
            <input type="hidden" name="action" value="sufo_checkout">
            <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
            <?php wp_nonce_field('sufo_checkout_' . $post_id, 'sufo_checkout_nonce'); ?>
            <?php foreach (array_keys($options) as $option_key): ?>
            <input type="hidden" name="options[<?php echo esc_attr($option_key); ?>]" value="0" data-checkout-option="<?php echo esc_attr($option_key); ?>">
            <?php endforeach; ?>
            <button type="submit" class="object-bar__price button"><span><?php echo esc_html(sufo_pll__('Buy for')); ?></span> <span data-price-value>€<?php echo esc_html(sufo_format_price($price)); ?></span> <span class="object-bar__vat icon"><?php echo esc_html(sufo_pll__('excl. VAT')); ?></span></button>
        </form>
    </div>

    <div class="header-menu menu--mobile" data-object-picker="nav">
        <button type="button" class="island menu__toggle button" aria-expanded="false" aria-haspopup="dialog" aria-label="Open menu">
            <?php echo sufo_render_icon('menu'); ?>
        </button>
        <div class="menu__panel" hidden>
            <?php if ($modal): ?>
            <div class="island modal-card">
                <?php if (has_post_thumbnail($modal)): ?>
                <img class="modal-card__image" src="<?php echo esc_url(get_the_post_thumbnail_url($modal, 'thumbnail')); ?>" alt="">
                <?php endif; ?>
                <div class="modal-card__body">
                    <h6 class="modal-card__title"><?php echo esc_html(get_the_title($modal)); ?></h6>
                    <h6 class="modal-card__description"><?php echo esc_html(wp_strip_all_tags($modal->post_content)); ?></h6>
                </div>
            </div>
            <?php endif; ?>

            <div class="island menu__nav">
                <span class="menu__nav__label"><?php echo esc_html($post->post_title); ?></span>
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'nav-menu',
                    'depth'          => 1,
                    'fallback_cb'    => false,
                ]);
                ?>
            </div>
        </div>
    </div>

</nav>

<div class="menu-backdrop" data-menu-backdrop></div>
