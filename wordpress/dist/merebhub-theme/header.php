<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'merebhub'); ?></a>
<header class="mh-header">
    <div class="mh-shell mh-header__main">
        <button class="mh-icon-button mh-menu-toggle" type="button" aria-expanded="false" aria-controls="mh-mobile-nav"><span class="dashicons dashicons-menu-alt3"></span><span class="screen-reader-text"><?php esc_html_e('Menu', 'merebhub'); ?></span></button>
        <a class="mh-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <?php if (has_custom_logo()) : the_custom_logo(); else : ?>
                <span class="mh-brand__mark">M</span><span><?php bloginfo('name'); ?></span>
            <?php endif; ?>
        </a>
        <nav class="mh-primary-nav" aria-label="<?php esc_attr_e('Primary navigation', 'merebhub'); ?>">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'menu_class' => 'mh-menu', 'fallback_cb' => false]);
            } else {
                echo '<ul class="mh-menu"><li><a href="'.esc_url(home_url('/')).'">'.esc_html__('Discover', 'merebhub').'</a></li><li><a href="'.esc_url(wc_get_page_permalink('shop')).'">'.esc_html__('Categories', 'merebhub').'</a></li><li><a href="'.esc_url(wc_get_page_permalink('shop')).'?on_sale=1">'.esc_html__('Deals', 'merebhub').'</a></li></ul>';
            }
            ?>
        </nav>
        <form role="search" method="get" class="mh-search" action="<?php echo esc_url(home_url('/')); ?>">
            <span class="dashicons dashicons-search"></span>
            <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search software, categories, or makers', 'merebhub'); ?>">
            <input type="hidden" name="post_type" value="product">
        </form>
        <nav class="mh-actions" aria-label="<?php esc_attr_e('Account actions', 'merebhub'); ?>">
            <a class="mh-action-link" href="<?php echo esc_url(wc_get_account_endpoint_url('wishlist')); ?>"><span class="dashicons dashicons-heart"></span><span><?php esc_html_e('Wishlist', 'merebhub'); ?></span></a>
            <a class="mh-action-link" href="<?php echo esc_url(wc_get_cart_url()); ?>"><span class="dashicons dashicons-cart"></span><span><?php esc_html_e('Cart', 'merebhub'); ?></span><span class="mh-count mh-cart-count<?php echo merebhub_cart_count() ? '' : ' is-empty'; ?>"><?php echo esc_html(merebhub_cart_count()); ?></span></a>
            <div class="mh-account">
                <button class="mh-action-link mh-account__toggle" type="button" aria-expanded="false"><span class="dashicons dashicons-admin-users"></span><span><?php echo is_user_logged_in() ? esc_html__('My Account', 'merebhub') : esc_html__('Login', 'merebhub'); ?></span><span class="dashicons dashicons-arrow-down-alt2"></span></button>
                <div class="mh-account__menu">
                    <?php if (is_user_logged_in()) : ?>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('Previous Orders', 'merebhub'); ?></a>
                        <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>"><?php esc_html_e('Account settings', 'merebhub'); ?></a>
                        <a class="mh-logout" href="<?php echo esc_url(wc_logout_url()); ?>"><?php esc_html_e('Logout', 'merebhub'); ?></a>
                    <?php else : ?>
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('Log in', 'merebhub'); ?></a>
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('Create account', 'merebhub'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>
    <div id="mh-mobile-nav" class="mh-mobile-nav">
        <form role="search" method="get" class="mh-search" action="<?php echo esc_url(home_url('/')); ?>"><span class="dashicons dashicons-search"></span><input type="search" name="s" placeholder="<?php esc_attr_e('Search software', 'merebhub'); ?>"><input type="hidden" name="post_type" value="product"></form>
        <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => false]); ?>
    </div>
    <?php if (! is_cart() && ! is_checkout()) { merebhub_category_strip(); } ?>
</header>
