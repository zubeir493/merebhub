<?php

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
?>
<section class="mh-account-dashboard">
    <p class="mh-eyebrow"><?php esc_html_e('Your account', 'merebhub'); ?></p>
    <h1><?php echo esc_html(sprintf(__('Welcome back, %s', 'merebhub'), $current_user->display_name)); ?></h1>
    <p><?php esc_html_e('Review your orders, access license keys, manage saved software, and update your account details.', 'merebhub'); ?></p>
    <div class="mh-account-shortcuts">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>"><?php echo merebhub_icon('file-text'); ?><strong><?php esc_html_e('Previous orders', 'merebhub'); ?></strong><small><?php esc_html_e('Receipts and license keys', 'merebhub'); ?></small></a>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('wishlist')); ?>"><?php echo merebhub_icon('heart'); ?><strong><?php esc_html_e('Wishlist', 'merebhub'); ?></strong><small><?php esc_html_e('Software saved for later', 'merebhub'); ?></small></a>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>"><?php echo merebhub_icon('user-round'); ?><strong><?php esc_html_e('Account settings', 'merebhub'); ?></strong><small><?php esc_html_e('Profile and password', 'merebhub'); ?></small></a>
    </div>
</section>
