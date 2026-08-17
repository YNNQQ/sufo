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
?>
<div class="island object-bar scheme-white" data-object-bar data-base-price="<?php echo esc_attr($price); ?>">
    <span class="object-bar__name label"><?php echo esc_html($post->post_title); ?></span>

    <?php
    echo sufo_render_object_picker('material', $field_labels['materials'], $materials, true);
    echo sufo_render_object_picker('finish', $field_labels['finishes'], $finishes, false);
    echo sufo_render_object_picker('delivery', $field_labels['delivery'], $delivery, false);
    ?>

    <div class="object-picker dropdown object-bar__customise" data-object-picker="customise" data-generic-label="Customise">
        <button type="button" class="object-picker__toggle dropdown__toggle button" aria-expanded="false" aria-haspopup="dialog">
            <span class="dropdown__label object-picker__label">Customise</span>
            <span class="icon"><?php echo $chevron; ?></span>
        </button>
        <div class="island object-picker__panel object-bar__customise-panel dropdown__panel" hidden>
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

    <div class="object-bar__price button"><span>Buy for</span> <span data-price-value>€<?php echo esc_html(sufo_format_price($price)); ?></span></div>
</div>

<div class="object-bar__backdrop" data-object-bar-backdrop></div>
