<div class="glassy" style="position:fixed;left:0;bottom:0;right:0;height:5px;z-index:8675309"></div>
<footer class="footer scheme-black">
    <div class="footer-container">

        <div class="footer-top">
            <?php if (has_nav_menu('tagline')) : ?>
                <span aria-label="Tagline">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'tagline',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </span>
            <?php endif; ?>


            <div class="footer__nav">
                <div class="footer__col" aria-label="Studio">
                    <h4 class="footer__label">Studio</h4>
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'studio',
                        'container'      => false,
                        'menu_class'     => 'footer__menu',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </div>

                <div class="footer__col" aria-label="Contact">
                    <h4 class="footer__label">Contact</h4>
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'contact',
                        'container'      => false,
                        'menu_class'     => 'footer__menu',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </div>

                <div class="footer__col" aria-label="Ask AI">
                    <h4 class="footer__label">Ask AI</h4>
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'askai',
                        'container'      => false,
                        'menu_class'     => 'footer__menu',
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ]);
                    ?>
                </div>

                <div class="footer__col footer__col--newsletter" aria-label="Newsletter">
                    <h4 class="footer__label">Newsletter</h4>
                    <p class="footer__label">Sign up and get the latest news and insights.</p>
                    
                </div>
            </div>


        </div>


        <div class="footer-bottom">
            <span class="footer__logo"><?php echo file_get_contents(get_template_directory() . '/assets/svg/logo.svg'); ?></span>
            
            <?php
            wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'footer__legal',
                'depth'          => 1,
                'fallback_cb'    => false,
            ]);
            ?>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>

</html>
