<?php get_header(); ?>
<main id="primary" class="mh-shell mh-page">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class('mh-article'); ?>><h1><?php the_title(); ?></h1><?php the_content(); ?></article>
    <?php endwhile; else : ?>
        <section class="mh-empty"><span class="dashicons dashicons-search"></span><h1><?php esc_html_e('Nothing found', 'merebhub'); ?></h1><p><?php esc_html_e('Try a different search or browse the software catalog.', 'merebhub'); ?></p></section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
