<?php

/**
 * Plugin Name: MerebHub for WooCommerce
 * Plugin URI: https://merebhub.et
 * Description: Connects WooCommerce products, orders, and customer accounts to Keygen licensing.
 * Version: 1.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce, advanced-custom-fields
 * Author: MerebHub
 * Author URI: https://merebhub.et
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: merebhub
 */
defined('ABSPATH') || exit;

define('MEREBHUB_PLUGIN_VERSION', '1.1.0');
define('MEREBHUB_PLUGIN_FILE', __FILE__);
define('MEREBHUB_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once MEREBHUB_PLUGIN_PATH.'includes/class-merebhub-keygen-client.php';
require_once MEREBHUB_PLUGIN_PATH.'includes/class-merebhub-admin.php';
require_once MEREBHUB_PLUGIN_PATH.'includes/class-merebhub-fulfillment.php';
require_once MEREBHUB_PLUGIN_PATH.'includes/class-merebhub-wishlist.php';
require_once MEREBHUB_PLUGIN_PATH.'includes/class-merebhub-customer-flow.php';

final class MerebHub_Plugin
{
    public static function boot(): void
    {
        add_action('plugins_loaded', [self::class, 'initialize']);
        register_activation_hook(MEREBHUB_PLUGIN_FILE, [self::class, 'activate']);
    }

    public static function initialize(): void
    {
        if (! class_exists('WooCommerce')) {
            add_action('admin_notices', static function (): void {
                echo '<div class="notice notice-error"><p>'.esc_html__('MerebHub requires WooCommerce.', 'merebhub').'</p></div>';
            });

            return;
        }

        $client = new MerebHub_Keygen_Client;
        new MerebHub_Admin($client);
        new MerebHub_Fulfillment($client);
        new MerebHub_Wishlist;
        new MerebHub_Customer_Flow;
    }

    public static function activate(): void
    {
        add_rewrite_endpoint('wishlist', EP_ROOT | EP_PAGES);

        if (! function_exists('wc_create_attribute')) {
            flush_rewrite_rules();

            return;
        }

        if (! taxonomy_exists('pa_platform') && ! wc_attribute_taxonomy_id_by_name('platform')) {
            wc_create_attribute([
                'name' => 'Platform',
                'slug' => 'platform',
                'type' => 'select',
                'order_by' => 'name',
                'has_archives' => false,
            ]);
        }

        flush_rewrite_rules();
    }
}

MerebHub_Plugin::boot();

function merebhub_wishlist_button(int $product_id): void
{
    MerebHub_Wishlist::button($product_id);
}
