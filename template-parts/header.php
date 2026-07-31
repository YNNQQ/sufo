<header class="header" id="site-header">
    <div class="header-container">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="header-logo">
            <?php echo file_get_contents(get_template_directory() . '/assets/svg/logo.svg'); ?>
            <h2>Objects</h2>
        </a>

        <div class="header-nav">

            <?php if (has_nav_menu('primary')) : ?>
                <nav class="island header__primary-nav" aria-label="Primary">
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
                <nav class="island header__secondary-nav" aria-label="Secondary">
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
