<?php

defined('ABSPATH') || exit;

global $product;

if (! is_a($product, WC_Product::class) || ! $product->is_visible()) {
    return;
}

$author = merebhub_product_author($product->get_id());
?>
<li <?php wc_product_class('mh-product-card', $product); ?>>
    <a class="mh-product-card__media" href="<?php the_permalink(); ?>">
        <?php echo $product->get_image('merebhub-card', ['loading' => 'lazy']); ?>
        <?php if ($product->is_on_sale()) { ?><span class="mh-sale-badge"><?php esc_html_e('Deal', 'merebhub'); ?></span><?php } ?>
    </a>
    <div class="mh-product-card__body">
        <div>
            <a href="<?php the_permalink(); ?>"><h3><?php the_title(); ?></h3></a>
            <p><?php echo esc_html($author?->name ?: get_bloginfo('name')); ?> &middot; <?php echo wp_kses_post(wc_get_product_category_list($product->get_id(), ', ')); ?></p>
        </div>
        <div class="mh-product-card__price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
    </div>
    <div class="mh-product-card__meta"><span><?php echo merebhub_icon('star', 'mh-star'); ?> <?php echo esc_html($product->get_average_rating() ?: 'New'); ?></span><span>(<?php echo esc_html($product->get_review_count()); ?>)</span></div>
</li>
