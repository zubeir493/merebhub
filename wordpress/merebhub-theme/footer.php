<footer class="mh-footer">
    <div class="mh-shell mh-footer__grid">
        <div><a class="mh-brand mh-brand--footer" href="<?php echo esc_url(home_url('/')); ?>"><span class="mh-brand__mark">M</span><span><?php bloginfo('name'); ?></span></a><p><?php esc_html_e('Independent Ethiopian software, reviewed and ready to use.', 'merebhub'); ?></p></div>
        <div><h2><?php esc_html_e('Marketplace', 'merebhub'); ?></h2><?php wp_nav_menu(['theme_location' => 'footer_marketplace', 'container' => false, 'fallback_cb' => false]); ?></div>
        <div><h2><?php esc_html_e('Developers', 'merebhub'); ?></h2><?php wp_nav_menu(['theme_location' => 'footer_developers', 'container' => false, 'fallback_cb' => false]); ?></div>
        <div><h2><?php esc_html_e('Your account', 'merebhub'); ?></h2><ul><li><a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php esc_html_e('Previous orders', 'merebhub'); ?></a></li><li><a href="<?php echo esc_url(wc_get_account_endpoint_url('wishlist')); ?>"><?php esc_html_e('Wishlist', 'merebhub'); ?></a></li><li><a href="<?php echo esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Cart', 'merebhub'); ?></a></li></ul></div>
    </div>
    <div class="mh-shell mh-footer__bottom"><span>© <?php echo esc_html(gmdate('Y')); ?> <?php bloginfo('name'); ?></span><span><?php esc_html_e('Secure payments by Chapa', 'merebhub'); ?></span></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
