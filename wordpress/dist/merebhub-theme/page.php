<?php get_header(); ?>
<main id="primary" class="mh-shell mh-page">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class('mh-article'); ?>><h1 class="mh-page-title"><?php the_title(); ?></h1><?php the_content(); ?></article>
    <?php endwhile; ?>
</main>
<?php get_footer(); ?>
