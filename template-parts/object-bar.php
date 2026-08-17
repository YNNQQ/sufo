<?php
$post_id = $args['post_id'] ?? 0;
$post    = get_post($post_id);
if (!$post) return;

$materials = sufo_get_materials($post_id);
$finishes  = sufo_get_finishes($post_id);
$shipping  = sufo_get_shipping($post_id);
$price     = sufo_get_price($post_id);

$field_labels = sufo_object_field_labels();

$chevron = file_get_contents(get_template_directory() . '/assets/svg/chevron.svg');
?>
<div class="island object-bar scheme-white" data-object-bar data-base-price="<?php echo esc_attr($price); ?>">
    <span class="object-bar__name label"><?php echo esc_html($post->post_title); ?></span>

    <?php
    echo sufo_render_object_picker('material', 'Color', $materials, true);
    echo sufo_render_object_picker('finish', 'Finish', $finishes, false);
    echo sufo_render_object_picker('shipping', 'Shipping', $shipping, false);
    ?>

    <div class="object-picker dropdown object-bar__customise" data-object-picker="customise" data-generic-label="Customise">
        <button type="button" class="object-picker__toggle dropdown__toggle button" aria-expanded="false" aria-haspopup="dialog">
            <span class="dropdown__label object-picker__label">Customise</span>
            <span class="icon"><?php echo $chevron; ?></span>
        </button>
        <div class="island object-picker__panel object-bar__customise-panel dropdown__panel" hidden>
            <div class="object-bar__customise-group" data-customise-slot="material">
                <span class="object-bar__customise-label"><?php echo esc_html($field_labels['materials']); ?></span>
            </div>
            <div class="object-bar__customise-group" data-customise-slot="finish">
                <span class="object-bar__customise-label"><?php echo esc_html($field_labels['finishes']); ?></span>
            </div>
            <div class="object-bar__customise-group" data-customise-slot="shipping">
                <span class="object-bar__customise-label"><?php echo esc_html($field_labels['shipping']); ?></span>
            </div>
        </div>
    </div>

    <div class="object-bar__price button"><span>Buy for</span> <span data-price-value>€<?php echo esc_html(sufo_format_price($price)); ?></span></div>
</div>

<div class="object-bar__backdrop" data-object-bar-backdrop></div>
