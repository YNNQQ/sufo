<?php
$post_id = isset($args['post_id']) ? (int) $args['post_id'] : 0;

if (!$post_id && is_front_page()) {
    $object_ids = get_posts([
        'post_type'      => 'sufo_object',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);
    $post_id = (int) ($object_ids[0] ?? 0);
}

$post_id     = $post_id ?: (int) get_queried_object_id();
$header_post = get_post($post_id);
if (!$header_post) return;
?>

<header class="header" id="site-header">
    <div class="header-container">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="header-logo">
            <?php echo file_get_contents(get_template_directory() . '/assets/svg/logo.svg'); ?>
            <div class="header-logo__titles">
                <h2 class="header-logo__title header-logo__title--objects">Objects</h2>
                <h2 class="header-logo__title header-logo__title--post"><?php echo esc_html($header_post->post_title); ?></h2>
            </div>

        </a>

        <div class="header-nav">

            <?php if (has_nav_menu('primary')) : ?>
                <nav class="island nav-highlight header__primary-nav" aria-label="Primary">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'nav-menu',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </nav>
            <?php endif; ?>

            <?php if (has_nav_menu('secondary')) : ?>
                <nav class="island header__secondary-nav scheme-glass" aria-label="Secondary">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'secondary',
                        'container'      => false,
                        'menu_class'     => 'nav-menu',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </nav>
            <?php endif; ?>

        </div>

        <button class="nav-toggle menu-item" aria-expanded="false" aria-controls="nav-menu" aria-label="Open menu">
        </button>

    </div>
</header>
