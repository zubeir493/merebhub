<?php
get_header();

$hero = get_theme_mod('merebhub_hero_image', get_template_directory_uri().'/assets/images/hero-built-here.webp');
$sale_ids = wc_get_product_ids_on_sale();
$deals = merebhub_product_query([
    'post__in' => $sale_ids ?: [0],
    'posts_per_page' => 5,
]);
$top = merebhub_product_query([
    'meta_key' => 'total_sales',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'posts_per_page' => 9,
]);
$featured = merebhub_product_query([
    'tax_query' => array_merge(WC()->query->get_tax_query(), [[
        'taxonomy' => 'product_visibility',
        'field' => 'name',
        'terms' => 'featured',
    ]]),
    'posts_per_page' => 4,
]);
$section_categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'number' => 2]);
?>
<main id="primary">
    <section class="mh-hero">
        <img src="<?php echo esc_url($hero); ?>" alt="<?php esc_attr_e('Ethiopian software marketplace', 'merebhub'); ?>">
        <div class="mh-hero__shade"></div>
        <div class="mh-shell mh-hero__content">
            <div>
                <p><?php esc_html_e('Built here. Ready for everywhere.', 'merebhub'); ?></p>
                <h1><?php esc_html_e("Ethiopia's home for remarkable software", 'merebhub'); ?></h1>
                <span><?php esc_html_e('Curated apps, tools, and games from independent makers. Secure checkout and instant license delivery included.', 'merebhub'); ?></span>
                <a class="button mh-button" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Explore software', 'merebhub'); ?><span class="dashicons dashicons-arrow-right-alt"></span></a>
            </div>
        </div>
    </section>

    <?php if ($deals->have_posts()) : ?>
        <section class="mh-deal-strip" aria-label="<?php esc_attr_e('Current deals', 'merebhub'); ?>">
            <div class="mh-shell mh-deal-strip__grid">
                <?php $deal_rank = 1; while ($deals->have_posts()) : $deals->the_post(); $deal_product = wc_get_product(get_the_ID()); ?>
                    <a href="<?php the_permalink(); ?>" class="mh-deal">
                        <strong class="mh-deal__rank"><?php echo esc_html($deal_rank++); ?></strong>
                        <?php echo get_the_post_thumbnail(get_the_ID(), 'thumbnail', ['class' => 'mh-deal__image']); ?>
                        <span><b><?php the_title(); ?></b><small><?php echo esc_html(merebhub_product_author(get_the_ID())?->name ?: get_bloginfo('name')); ?></small></span>
                        <em><?php echo wp_kses_post($deal_product->get_price_html()); ?></em>
                    </a>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="mh-shell mh-home-content">
        <?php if ($top->have_posts()) : ?>
            <section class="mh-home-section">
                <header class="mh-section-heading"><h2><?php esc_html_e('Top selling this week', 'merebhub'); ?></h2><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('See all', 'merebhub'); ?></a></header>
                <div class="mh-ranked-grid">
                    <?php $rank = 1; while ($top->have_posts()) : $top->the_post(); $product = wc_get_product(get_the_ID()); ?>
                        <a href="<?php the_permalink(); ?>" class="mh-ranked-product">
                            <strong><?php echo esc_html($rank++); ?></strong>
                            <?php echo get_the_post_thumbnail(get_the_ID(), 'thumbnail'); ?>
                            <span><b><?php the_title(); ?></b><small>★ <?php echo esc_html($product->get_average_rating() ?: 'New'); ?> · <?php echo esc_html(implode(', ', wp_get_post_terms(get_the_ID(), 'product_cat', ['fields' => 'names']))); ?></small><em><?php echo wp_kses_post($product->get_price_html()); ?></em></span>
                        </a>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($featured->have_posts()) : ?>
            <section class="mh-home-section">
                <header class="mh-section-heading"><h2><?php esc_html_e('Made in Ethiopia', 'merebhub'); ?></h2><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('See all', 'merebhub'); ?></a></header>
                <?php merebhub_render_product_grid($featured); ?>
            </section>
        <?php endif; ?>

        <?php if (! is_wp_error($section_categories)) : foreach ($section_categories as $category) :
            $category_products = merebhub_product_query([
                'tax_query' => array_merge(WC()->query->get_tax_query(), [[
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category->term_id,
                ]]),
                'posts_per_page' => 4,
            ]);
            if (! $category_products->have_posts()) { continue; }
            ?>
            <section class="mh-home-section">
                <header class="mh-section-heading"><h2><?php echo esc_html($category->name); ?></h2><a href="<?php echo esc_url(get_term_link($category)); ?>"><?php esc_html_e('See all', 'merebhub'); ?></a></header>
                <?php merebhub_render_product_grid($category_products); ?>
            </section>
        <?php endforeach; endif; ?>
    </div>
</main>
<?php get_footer(); ?>
