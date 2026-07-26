<?php

defined('ABSPATH') || exit;

final class MerebHub_Wishlist
{
    private const META_KEY = '_merebhub_wishlist';

    public function __construct()
    {
        add_action('init', [$this, 'endpoint']);
        add_filter('query_vars', [$this, 'query_vars']);
        add_filter('woocommerce_get_query_vars', [$this, 'woocommerce_query_vars']);
        add_filter('woocommerce_account_menu_items', [$this, 'account_menu']);
        add_action('woocommerce_account_wishlist_endpoint', [$this, 'render']);
        add_action('admin_post_merebhub_toggle_wishlist', [$this, 'toggle']);
        add_action('admin_post_nopriv_merebhub_toggle_wishlist', [$this, 'guest']);
    }

    public function endpoint(): void
    {
        add_rewrite_endpoint('wishlist', EP_ROOT | EP_PAGES);
    }

    public function query_vars(array $vars): array
    {
        $vars[] = 'wishlist';

        return $vars;
    }

    public function woocommerce_query_vars(array $vars): array
    {
        $vars['wishlist'] = 'wishlist';

        return $vars;
    }

    public function account_menu(array $items): array
    {
        $logout = $items['customer-logout'] ?? null;
        unset($items['customer-logout']);
        $items['wishlist'] = __('Wishlist', 'merebhub');

        if ($logout !== null) {
            $items['customer-logout'] = $logout;
        }

        return $items;
    }

    public function toggle(): void
    {
        if (! is_user_logged_in()) {
            $this->guest();
        }

        $product_id = absint($_POST['product_id'] ?? 0);
        $token = sanitize_text_field(wp_unslash($_POST['wishlist_token'] ?? ''));

        if (! wc_get_product($product_id) || ! hash_equals(self::token($product_id), $token)) {
            wc_add_notice(__('Your wishlist request could not be verified. Please try again.', 'merebhub'), 'error');
            $this->redirect_back();
        }

        $wishlist = self::ids();

        if (in_array($product_id, $wishlist, true)) {
            $wishlist = array_values(array_diff($wishlist, [$product_id]));
            wc_add_notice(__('Removed from your wishlist.', 'merebhub'), 'notice');
        } else {
            $wishlist[] = $product_id;
            $wishlist = array_values(array_unique(array_map('absint', $wishlist)));
            wc_add_notice(__('Added to your wishlist.', 'merebhub'), 'success');
        }

        update_user_meta(get_current_user_id(), self::META_KEY, $wishlist);
        $this->redirect_back();
    }

    public function guest(): void
    {
        wc_add_notice(__('Log in to use your wishlist.', 'merebhub'), 'notice');
        wp_safe_redirect(wc_get_page_permalink('myaccount'));
        exit;
    }

    public function render(): void
    {
        $ids = self::ids();

        echo '<section class="mh-wishlist"><header><p>'.esc_html__('Saved for later', 'merebhub').'</p><h2>'.esc_html__('Wishlist', 'merebhub').'</h2></header>';

        if ($ids === []) {
            echo '<div class="mh-empty"><i class="mh-icon" data-lucide="heart" aria-hidden="true"></i><h3>'.esc_html__('Nothing saved yet', 'merebhub').'</h3><a class="button" href="'.esc_url(wc_get_page_permalink('shop')).'">'.esc_html__('Explore software', 'merebhub').'</a></div></section>';

            return;
        }

        $query = new WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'post__in' => $ids,
            'orderby' => 'post__in',
            'posts_per_page' => -1,
        ]);
        merebhub_render_product_grid($query);
        echo '</section>';
    }

    public static function button(int $product_id): void
    {
        $active = is_user_logged_in() && in_array($product_id, self::ids(), true);
        ?>
        <form class="mh-wishlist-button" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="merebhub_toggle_wishlist">
            <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
            <input type="hidden" name="wishlist_token" value="<?php echo esc_attr(self::token($product_id)); ?>">
            <button type="submit" class="<?php echo $active ? 'is-active' : ''; ?>"><i class="mh-icon" data-lucide="heart" aria-hidden="true"></i><?php echo $active ? esc_html__('Saved to wishlist', 'merebhub') : esc_html__('Add to wishlist', 'merebhub'); ?></button>
        </form>
        <?php
    }

    private static function token(int $product_id): string
    {
        return hash_hmac(
            'sha256',
            get_current_user_id().':'.$product_id,
            wp_salt('auth'),
        );
    }

    private function redirect_back(): void
    {
        $fallback = wc_get_account_endpoint_url('wishlist');
        $redirect = wp_validate_redirect((string) wp_get_referer(), $fallback);

        wp_safe_redirect($redirect);
        exit;
    }

    private static function ids(): array
    {
        if (! is_user_logged_in()) {
            return [];
        }

        $ids = get_user_meta(get_current_user_id(), self::META_KEY, true);

        return is_array($ids) ? array_values(array_filter(array_map('absint', $ids))) : [];
    }
}
