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
        <button class="mh-icon-button mh-menu-toggle" type="button" aria-expanded="false" aria-controls="mh-mobile-nav"><?php echo merebhub_icon('menu'); ?><span class="screen-reader-text"><?php esc_html_e('Menu', 'merebhub'); ?></span></button>
        <a class="mh-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <?php if (has_custom_logo()) { ?>
                <?php echo wp_get_attachment_image((int) get_theme_mod('custom_logo'), 'full', false, ['class' => 'custom-logo', 'alt' => get_bloginfo('name')]); ?>
            <?php } else { ?>
                <span class="mh-brand__mark">M</span><span><?php bloginfo('name'); ?></span>
            <?php } ?>
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
            <?php echo merebhub_icon('search'); ?>
            <input type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search software, categories, or makers', 'merebhub'); ?>">
            <input type="hidden" name="post_type" value="product">
        </form>
        <nav class="mh-actions" aria-label="<?php esc_attr_e('Account actions', 'merebhub'); ?>">
            <a class="mh-action-link" href="<?php echo esc_url(wc_get_account_endpoint_url('wishlist')); ?>"><?php echo merebhub_icon('heart'); ?><span><?php esc_html_e('Wishlist', 'merebhub'); ?></span></a>
            <a class="mh-action-link" href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php echo merebhub_icon('shopping-cart'); ?><span><?php esc_html_e('Cart', 'merebhub'); ?></span><span class="mh-count mh-cart-count<?php echo merebhub_cart_count() ? '' : ' is-empty'; ?>"><?php echo esc_html(merebhub_cart_count()); ?></span></a>
            <div class="mh-account-slot" data-account-status-url="<?php echo esc_url(admin_url('admin-ajax.php?action=merebhub_account_status')); ?>">
                <?php echo merebhub_account_action_markup(); ?>
            </div>
        </nav>
    </div>
    <div id="mh-mobile-nav" class="mh-mobile-nav">
        <form role="search" method="get" class="mh-search" action="<?php echo esc_url(home_url('/')); ?>"><?php echo merebhub_icon('search'); ?><input type="search" name="s" placeholder="<?php esc_attr_e('Search software', 'merebhub'); ?>"><input type="hidden" name="post_type" value="product"></form>
        <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => false]); ?>
    </div>
    <?php if (! is_cart() && ! is_checkout()) {
        merebhub_category_strip();
    } ?>
</header>
