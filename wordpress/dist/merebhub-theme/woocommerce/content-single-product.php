<?php

defined('ABSPATH') || exit;

global $product;

if (post_password_required()) {
    echo get_the_password_form();

    return;
}

$author = merebhub_product_author($product->get_id());
$platforms = merebhub_product_platforms($product->get_id());
?>
<article id="product-<?php the_ID(); ?>" <?php wc_product_class('mh-single-product', $product); ?>>
    <nav class="mh-breadcrumb"><?php woocommerce_breadcrumb(); ?></nav>
    <section class="mh-product-hero">
        <div class="mh-product-gallery"><?php do_action('woocommerce_before_single_product_summary'); ?></div>
        <div class="mh-product-summary">
            <p class="mh-product-kicker"><?php echo wp_kses_post(wc_get_product_category_list($product->get_id(), ', ')); ?><?php echo $platforms ? ' · '.esc_html($platforms) : ''; ?></p>
            <h1><?php the_title(); ?></h1>
            <?php if ($product->get_short_description()) : ?><div class="mh-product-tagline"><?php echo wp_kses_post(wpautop($product->get_short_description())); ?></div><?php endif; ?>
            <?php if ($author) : ?><a class="mh-author-link" href="<?php echo esc_url(get_term_link($author)); ?>"><?php echo esc_html(sprintf(__('By %s', 'merebhub'), $author->name)); ?><span class="dashicons dashicons-yes-alt"></span></a><?php endif; ?>
            <div class="mh-product-rating"><?php woocommerce_template_single_rating(); ?></div>
            <div class="mh-buy-panel">
                <div class="mh-single-price"><?php woocommerce_template_single_price(); ?></div>
                <?php woocommerce_template_single_add_to_cart(); ?>
                <?php if (function_exists('merebhub_wishlist_button')) { merebhub_wishlist_button($product->get_id()); } ?>
                <p><span class="dashicons dashicons-lock"></span><?php esc_html_e('Secure Chapa checkout and automatic license delivery', 'merebhub'); ?></p>
            </div>
        </div>
    </section>
    <section class="mh-product-details">
        <div><h2><?php esc_html_e('About this software', 'merebhub'); ?></h2><div class="mh-product-description"><?php the_content(); ?></div></div>
        <aside><h3><?php esc_html_e('What you get', 'merebhub'); ?></h3><ul><li><span class="dashicons dashicons-yes"></span><?php esc_html_e('Secure Chapa checkout', 'merebhub'); ?></li><li><span class="dashicons dashicons-yes"></span><?php esc_html_e('Automatic license delivery', 'merebhub'); ?></li><li><span class="dashicons dashicons-yes"></span><?php esc_html_e('Purchase history in your account', 'merebhub'); ?></li></ul></aside>
    </section>
    <section class="mh-related"><?php woocommerce_output_related_products(['posts_per_page' => 4, 'columns' => 4]); ?></section>
</article>
