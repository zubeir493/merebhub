<?php

defined('ABSPATH') || exit;

if (post_password_required()) {
    return;
}
?>
<section id="comments" class="mh-comments">
    <?php if (have_comments()) { ?>
        <h2>
            <?php
            printf(
                esc_html(_nx('One response', '%1$s responses', get_comments_number(), 'comments title', 'merebhub')),
                esc_html(number_format_i18n(get_comments_number())),
            );
        ?>
        </h2>
        <ol class="comment-list">
            <?php wp_list_comments(['style' => 'ol', 'short_ping' => true, 'avatar_size' => 48]); ?>
        </ol>
        <?php the_comments_navigation(); ?>
    <?php } ?>

    <?php
    if (! comments_open() && get_comments_number()) {
        echo '<p class="no-comments">'.esc_html__('Comments are closed.', 'merebhub').'</p>';
    }

if (comments_open()) {
    comment_form();
}
?>
</section>
