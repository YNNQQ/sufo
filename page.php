<?php get_header(); ?>

<?php
    $front_page_id  =   get_option('page_on_front');
?>

<main class="site-main scheme-white">

    <?php if (is_front_page()):
        $object = get_posts(['post_type' => 'sufo_object', 'post_status' => 'publish', 'numberposts' => 1])[0] ?? null;
        if ($object):
            ?>
        
        <?php echo render_sections($object->post_content, $object->ID); ?>

    <?php endif; endif; ?>


</main>

<?php // outside <main>, which is its own stacking context — the notice must sit above the backdrop ?>
<?php echo sufo_checkout_notice(); ?>

<?php if (is_front_page() && !empty($object)): ?>
    <?php get_template_part('template-parts/object-bar', null, ['post_id' => $object->ID]); ?>
<?php endif; ?>

<?php get_footer(); ?>