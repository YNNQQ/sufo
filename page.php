<?php get_header(); ?>

<?php
    $front_page_id  =   get_option('page_on_front');
?>

<main class="site-main scheme-grey">

    <?php if (is_front_page()): ?>

        <?php the_content(); ?>
    <?php endif?>


</main>

<?php get_footer(); ?>