<?php

defined('ABSPATH') || exit;
?>
<section class="mh-empty">
    <span class="dashicons dashicons-cart"></span>
    <?php if (! is_user_logged_in()) : ?>
        <h1><?php esc_html_e('Log in to use your cart', 'merebhub'); ?></h1>
        <p><?php esc_html_e('Your cart is linked to your MerebHub account so it stays available across devices.', 'merebhub'); ?></p>
        <a class="button" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('Log in', 'merebhub'); ?></a>
    <?php else : ?>
        <h1><?php esc_html_e('Your cart is empty', 'merebhub'); ?></h1>
        <p><?php esc_html_e('Explore the catalog and add software when you are ready.', 'merebhub'); ?></p>
        <?php if (wc_get_page_id('shop') > 0) : ?><a class="button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Browse software', 'merebhub'); ?></a><?php endif; ?>
    <?php endif; ?>
</section>
