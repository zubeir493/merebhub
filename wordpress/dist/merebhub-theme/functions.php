<?php

defined('ABSPATH') || exit;

function merebhub_setup(): void
{
    load_theme_textdomain('merebhub', get_template_directory().'/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', ['height' => 44, 'width' => 180, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    register_nav_menus([
        'primary' => __('Primary navigation', 'merebhub'),
        'footer_marketplace' => __('Footer marketplace', 'merebhub'),
        'footer_developers' => __('Footer developers', 'merebhub'),
    ]);
    add_image_size('merebhub-card', 720, 450, true);
}
add_action('after_setup_theme', 'merebhub_setup');

function merebhub_assets(): void
{
    wp_enqueue_style('merebhub-font', 'https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800', [], null);
    wp_enqueue_style('dashicons');
    wp_enqueue_style('merebhub-theme', get_template_directory_uri().'/assets/css/theme.css', [], wp_get_theme()->get('Version'));
    wp_enqueue_script('merebhub-theme', get_template_directory_uri().'/assets/js/theme.js', [], wp_get_theme()->get('Version'), true);
}
add_action('wp_enqueue_scripts', 'merebhub_assets');

function merebhub_body_classes(array $classes): array
{
    $classes[] = 'merebhub';

    return $classes;
}
add_filter('body_class', 'merebhub_body_classes');

remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

function merebhub_cart_count(): int
{
    return function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}

function merebhub_cart_fragment(array $fragments): array
{
    ob_start();
    ?>
    <span class="mh-count mh-cart-count<?php echo merebhub_cart_count() ? '' : ' is-empty'; ?>"><?php echo esc_html(merebhub_cart_count()); ?></span>
    <?php
    $fragments['.mh-cart-count'] = ob_get_clean();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'merebhub_cart_fragment');

function merebhub_product_author(int $product_id): ?WP_Term
{
    $authors = get_the_terms($product_id, 'merebhub_author');

    return is_array($authors) ? $authors[0] : null;
}

function merebhub_product_platforms(int $product_id): string
{
    $platforms = wc_get_product_terms($product_id, 'pa_platform', ['fields' => 'names']);

    return implode(', ', $platforms);
}

function merebhub_category_strip(): void
{
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'parent' => 0,
        'number' => 9,
    ]);

    if (is_wp_error($categories) || $categories === []) {
        return;
    }
    ?>
    <nav class="mh-category-strip" aria-label="<?php esc_attr_e('Software categories', 'merebhub'); ?>">
        <div class="mh-shell mh-category-strip__inner">
            <?php foreach ($categories as $index => $category) : ?>
                <a href="<?php echo esc_url(get_term_link($category)); ?>">
                    <span class="dashicons <?php echo esc_attr(merebhub_category_icon($index)); ?>"></span>
                    <span><?php echo esc_html($category->name); ?></span>
                </a>
            <?php endforeach; ?>
            <a class="mh-category-all" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><span class="dashicons dashicons-grid-view"></span><span><?php esc_html_e('View all', 'merebhub'); ?></span></a>
        </div>
    </nav>
    <?php
}

function merebhub_category_icon(int $index): string
{
    $icons = ['dashicons-editor-code', 'dashicons-lightbulb', 'dashicons-portfolio', 'dashicons-art', 'dashicons-megaphone', 'dashicons-chart-bar', 'dashicons-shield', 'dashicons-admin-generic', 'dashicons-grid-view'];

    return $icons[$index % count($icons)];
}

function merebhub_product_query(array $args = []): WP_Query
{
    return new WP_Query(array_merge([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 8,
        'no_found_rows' => true,
        'meta_query' => WC()->query->get_meta_query(),
        'tax_query' => WC()->query->get_tax_query(),
    ], $args));
}

function merebhub_render_product_grid(WP_Query $query, string $class = ''): void
{
    if (! $query->have_posts()) {
        return;
    }

    echo '<ul class="products mh-product-grid '.esc_attr($class).'">';
    while ($query->have_posts()) {
        $query->the_post();
        wc_get_template_part('content', 'product');
    }
    echo '</ul>';
    wp_reset_postdata();
}

function merebhub_customize_register(WP_Customize_Manager $customizer): void
{
    $customizer->add_section('merebhub_home', [
        'title' => __('MerebHub homepage', 'merebhub'),
        'priority' => 30,
    ]);
    $customizer->add_setting('merebhub_hero_image');
    $customizer->add_control(new WP_Customize_Image_Control($customizer, 'merebhub_hero_image', [
        'label' => __('Campaign banner', 'merebhub'),
        'section' => 'merebhub_home',
    ]));
}
add_action('customize_register', 'merebhub_customize_register');
