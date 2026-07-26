<?php
get_header();
$author = get_queried_object();
$bio = function_exists('get_field') ? get_field('bio', $author) : $author->description;
$avatar = function_exists('get_field') ? get_field('avatar', $author) : '';
?>
<main id="primary" class="mh-shell mh-commerce">
    <header class="mh-author-hero">
        <?php if ($avatar) { ?><img src="<?php echo esc_url($avatar); ?>" alt=""><?php } else { ?><?php echo merebhub_icon('user-round'); ?><?php } ?>
        <div><p><?php esc_html_e('Software author', 'merebhub'); ?></p><h1><?php echo esc_html($author->name); ?></h1><?php if ($bio) { ?><div><?php echo wp_kses_post(wpautop($bio)); ?></div><?php } ?></div>
    </header>
    <?php if (have_posts()) { ?><ul class="products mh-product-grid"><?php while (have_posts()) {
        the_post();
        wc_get_template_part('content', 'product');
    } ?></ul><?php } else { ?><div class="mh-empty"><h2><?php esc_html_e('No published software yet', 'merebhub'); ?></h2></div><?php } ?>
</main>
<?php get_footer(); ?>
