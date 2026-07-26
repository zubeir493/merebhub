<?php

defined('ABSPATH') || exit;

do_action('woocommerce_before_cart');
?>
<header class="mh-commerce-heading"><p><?php esc_html_e('Ready when you are', 'merebhub'); ?></p><h1><?php esc_html_e('Your cart', 'merebhub'); ?></h1></header>
<form class="woocommerce-cart-form" action="<?php echo esc_url(wc_get_cart_url()); ?>" method="post">
    <?php do_action('woocommerce_before_cart_table'); ?>
    <table class="shop_table shop_table_responsive cart woocommerce-cart-form__contents">
        <thead><tr><th class="product-remove"><span class="screen-reader-text"><?php esc_html_e('Remove item', 'merebhub'); ?></span></th><th class="product-thumbnail"><span class="screen-reader-text"><?php esc_html_e('Thumbnail', 'merebhub'); ?></span></th><th class="product-name"><?php esc_html_e('Software', 'merebhub'); ?></th><th class="product-price"><?php esc_html_e('Price', 'merebhub'); ?></th><th class="product-quantity"><?php esc_html_e('Seats', 'merebhub'); ?></th><th class="product-subtotal"><?php esc_html_e('Subtotal', 'merebhub'); ?></th></tr></thead>
        <tbody>
        <?php do_action('woocommerce_before_cart_contents'); ?>
        <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
            $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
            if (! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0) { continue; }
            $product_permalink = $_product->is_visible() ? $_product->get_permalink($cart_item) : '';
            ?>
            <tr class="woocommerce-cart-form__cart-item <?php echo esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
                <td class="product-remove"><?php echo apply_filters('woocommerce_cart_item_remove_link', sprintf('<a href="%s" class="remove" aria-label="%s" data-product_id="%s">&times;</a>', esc_url(wc_get_cart_remove_url($cart_item_key)), esc_attr(sprintf(__('Remove %s', 'merebhub'), wp_strip_all_tags($_product->get_name()))), esc_attr($product_id)), $cart_item_key); ?></td>
                <td class="product-thumbnail"><?php $thumbnail = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $cart_item, $cart_item_key); echo $product_permalink ? sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail) : $thumbnail; ?></td>
                <td class="product-name" data-title="<?php esc_attr_e('Software', 'merebhub'); ?>"><?php echo $product_permalink ? wp_kses_post(sprintf('<a href="%s">%s</a>', esc_url($product_permalink), $_product->get_name())) : wp_kses_post($_product->get_name()); do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key); echo wc_get_formatted_cart_item_data($cart_item); ?></td>
                <td class="product-price" data-title="<?php esc_attr_e('Price', 'merebhub'); ?>"><?php echo apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key); ?></td>
                <td class="product-quantity" data-title="<?php esc_attr_e('Seats', 'merebhub'); ?>"><?php echo woocommerce_quantity_input(['input_name' => "cart[{$cart_item_key}][qty]", 'input_value' => $cart_item['quantity'], 'max_value' => $_product->get_max_purchase_quantity(), 'min_value' => 0, 'product_name' => $_product->get_name()], $_product, false); ?></td>
                <td class="product-subtotal" data-title="<?php esc_attr_e('Subtotal', 'merebhub'); ?>"><?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php do_action('woocommerce_cart_contents'); ?>
        <tr><td colspan="6" class="actions">
            <?php if (wc_coupons_enabled()) : ?><div class="coupon"><label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Coupon', 'merebhub'); ?></label><input type="text" name="coupon_code" id="coupon_code" class="input-text" placeholder="<?php esc_attr_e('Coupon code', 'merebhub'); ?>"><button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e('Apply coupon', 'merebhub'); ?>"><?php esc_html_e('Apply coupon', 'merebhub'); ?></button></div><?php endif; ?>
            <button type="submit" class="button" name="update_cart" value="<?php esc_attr_e('Update cart', 'merebhub'); ?>"><?php esc_html_e('Update cart', 'merebhub'); ?></button>
            <?php do_action('woocommerce_cart_actions'); wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
        </td></tr>
        <?php do_action('woocommerce_after_cart_contents'); ?>
        </tbody>
    </table>
    <?php do_action('woocommerce_after_cart_table'); ?>
</form>
<?php do_action('woocommerce_before_cart_collaterals'); ?>
<div class="cart-collaterals"><?php do_action('woocommerce_cart_collaterals'); ?></div>
<?php do_action('woocommerce_after_cart'); ?>
