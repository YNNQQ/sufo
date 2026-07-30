<header class="header" id="site-header">
    <div class="header-container grid--12">

        <a href="<?php echo esc_url(home_url('/')); ?>" class="header-logo">
            <?php echo file_get_contents(get_template_directory() . '/assets/svg/logo.svg'); ?>
        </a>

        <button class="nav-toggle menu-item" aria-expanded="false" aria-controls="nav-menu" aria-label="Open menu">
        </button>

    </div>
</header>
