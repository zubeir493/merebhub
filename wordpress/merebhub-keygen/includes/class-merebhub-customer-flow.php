<?php

defined('ABSPATH') || exit;

final class MerebHub_Customer_Flow
{
    private const PENDING_PRODUCT = 'merebhub_pending_product';

    public function __construct()
    {
        add_filter('woocommerce_add_to_cart_validation', [$this, 'require_login'], 10, 3);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'cart_redirect']);
        add_filter('woocommerce_product_single_add_to_cart_text', static fn (): string => __('Buy now', 'merebhub'));
        add_filter('woocommerce_loop_add_to_cart_args', [$this, 'disable_ajax_cart']);
        add_filter('woocommerce_checkout_registration_required', '__return_true');
        add_filter('woocommerce_enable_myaccount_registration', '__return_true');
        add_filter('pre_option_woocommerce_enable_myaccount_registration', static fn (): string => 'yes');
        add_filter('woocommerce_login_redirect', [$this, 'login_redirect'], 10, 2);
        add_filter('woocommerce_registration_redirect', [$this, 'registration_redirect']);
        add_action('template_redirect', [$this, 'prevent_private_page_caching'], 1);
    }

    public function require_login(bool $passed, int $product_id, int $quantity): bool
    {
        if (is_user_logged_in()) {
            return $passed;
        }

        if (WC()->session) {
            WC()->session->set(self::PENDING_PRODUCT, [
                'product_id' => $product_id,
                'quantity' => max(1, $quantity),
            ]);
        }

        wc_add_notice(__('Log in to add software to your cart.', 'merebhub'), 'notice');

        if (! wp_doing_ajax()) {
            wp_safe_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }

        return false;
    }

    public function disable_ajax_cart(array $args): array
    {
        $args['class'] = str_replace('ajax_add_to_cart', '', (string) ($args['class'] ?? ''));

        return $args;
    }

    public function cart_redirect(string $url): string
    {
        return is_user_logged_in() ? wc_get_cart_url() : wc_get_page_permalink('myaccount');
    }

    public function login_redirect(string $redirect, WP_User $user): string
    {
        return $this->resume_cart($redirect);
    }

    public function registration_redirect(string $redirect): string
    {
        return $this->resume_cart($redirect);
    }

    public function prevent_private_page_caching(): void
    {
        if (! is_user_logged_in() && ! is_account_page() && ! is_cart() && ! is_checkout()) {
            return;
        }

        if (! defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }

        nocache_headers();
    }

    private function resume_cart(string $fallback): string
    {
        $pending = WC()->session?->get(self::PENDING_PRODUCT);

        if (! is_array($pending) || empty($pending['product_id'])) {
            return $fallback;
        }

        WC()->cart->add_to_cart(absint($pending['product_id']), max(1, absint($pending['quantity'] ?? 1)));
        WC()->session->set(self::PENDING_PRODUCT, null);

        return wc_get_cart_url();
    }
}
