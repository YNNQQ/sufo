<?php
$post_id = $args['post_id'] ?? 0;
$post    = get_post($post_id);
if (!$post) return;

$options   = sufo_get_object_options($post_id);
$materials = $options['materials'] ?? [];
$finishes  = $options['finishes'] ?? [];
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

        <?php
        echo sufo_render_object_picker('material', $field_labels['materials'], $materials, true);
        echo sufo_render_object_picker('finish', $field_labels['finishes'], $finishes, false);
        echo sufo_render_object_picker('delivery', $field_labels['delivery'], $delivery, false);
        ?>

        <div class="menu menu--mobile" data-object-picker="customise" data-generic-label="Customise">
            <button type="button" class="menu__toggle button" aria-expanded="false" aria-haspopup="dialog">
                <span class="menu__label">Customise</span>
                <span class="icon"><?php echo $chevron; ?></span>
            </button>
            <div class="menu__panel" hidden>
                <div class="island object-bar__customise-list">
                    <?php if (!empty($materials)): ?>
                    <div class="object-bar__customise-group" data-customise-slot="material">
                        <span class="object-bar__customise-label"><?php echo esc_html($field_labels['materials']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($finishes)): ?>
                    <div class="object-bar__customise-group" data-customise-slot="finish">
                        <span class="object-bar__customise-label"><?php echo esc_html($field_labels['finishes']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($delivery)): ?>
                    <div class="object-bar__customise-group" data-customise-slot="delivery">
                        <span class="object-bar__customise-label"><?php echo esc_html($field_labels['delivery']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="object-bar__price button"><span>Buy for</span> <span data-price-value>€<?php echo esc_html(sufo_format_price($price)); ?></span></div>
    </div>

    <div class="header-menu menu--mobile" data-object-picker="nav">
        <button type="button" class="island menu__toggle button" aria-expanded="false" aria-haspopup="dialog" aria-label="<?php esc_attr_e('Open menu'); ?>">
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
