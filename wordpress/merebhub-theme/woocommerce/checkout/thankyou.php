<?php

defined('ABSPATH') || exit;
?>
<section class="mh-thankyou">
    <?php if ($order) { ?>
        <?php if ($order->has_status('failed')) { ?>
            <?php echo merebhub_icon('circle-alert'); ?><h1><?php esc_html_e('Payment was not completed', 'merebhub'); ?></h1><p><?php esc_html_e('Please try your payment again or contact support.', 'merebhub'); ?></p>
            <div class="mh-thankyou__actions"><a class="button pay" href="<?php echo esc_url($order->get_checkout_payment_url()); ?>"><?php esc_html_e('Try payment again', 'merebhub'); ?></a><a class="button" href="<?php echo esc_url($order->get_cancel_order_url()); ?>"><?php esc_html_e('My account', 'merebhub'); ?></a></div>
        <?php } else { ?>
            <?php echo merebhub_icon('circle-check-big'); ?><h1><?php esc_html_e('Thank you for your purchase', 'merebhub'); ?></h1><p><?php esc_html_e('Your order is confirmed. License details appear below and have also been sent by email.', 'merebhub'); ?></p>
            <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
                <li><?php esc_html_e('Order number', 'merebhub'); ?><strong><?php echo esc_html($order->get_order_number()); ?></strong></li>
                <li><?php esc_html_e('Date', 'merebhub'); ?><strong><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></strong></li>
                <li><?php esc_html_e('Total', 'merebhub'); ?><strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong></li>
                <li><?php esc_html_e('Payment method', 'merebhub'); ?><strong><?php echo wp_kses_post($order->get_payment_method_title()); ?></strong></li>
            </ul>
        <?php } ?>
    <?php } else { ?>
        <?php echo merebhub_icon('circle-check-big'); ?><h1><?php esc_html_e('Thank you', 'merebhub'); ?></h1>
    <?php } ?>
</section>
<?php if ($order) {
    do_action('woocommerce_thankyou_'.$order->get_payment_method(), $order->get_id());
    do_action('woocommerce_thankyou', $order->get_id());
} ?>
