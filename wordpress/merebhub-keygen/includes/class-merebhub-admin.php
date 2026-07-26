<?php

defined('ABSPATH') || exit;

final class MerebHub_Admin
{
    private MerebHub_Keygen_Client $client;

    public function __construct(MerebHub_Keygen_Client $client)
    {
        $this->client = $client;

        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'save_settings']);
        add_action('init', [$this, 'register_author_taxonomy']);
        add_action('acf/init', [$this, 'register_acf_fields']);
        add_filter('woocommerce_product_data_tabs', [$this, 'product_data_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'product_data_panel']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_link']);
        add_filter('woocommerce_rest_prepare_product_object', [$this, 'append_rest_data'], 10, 3);
        add_filter('plugin_action_links_'.plugin_basename(MEREBHUB_PLUGIN_FILE), [$this, 'plugin_action_links']);
        add_action('admin_notices', [$this, 'configuration_notice']);
        add_action('woocommerce_product_import_inserted_product_object', [$this, 'import_product_metadata'], 10, 2);
    }

    public function menu(): void
    {
        add_menu_page(
            __('MerebHub integration', 'merebhub'),
            __('MerebHub', 'merebhub'),
            'manage_woocommerce',
            'merebhub-settings',
            [$this, 'settings_page'],
            'dashicons-admin-network',
            56,
        );
    }

    public function plugin_action_links(array $links): array
    {
        array_unshift(
            $links,
            '<a href="'.esc_url(admin_url('admin.php?page=merebhub-settings')).'">'.esc_html__('Connect Keygen', 'merebhub').'</a>',
        );

        return $links;
    }

    public function configuration_notice(): void
    {
        if (! current_user_can('manage_woocommerce') || $this->client->configured()) {
            return;
        }

        $screen = get_current_screen();

        if (! $screen || ! in_array($screen->id, ['dashboard', 'plugins', 'woocommerce_page_wc-admin'], true)) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>'.esc_html__('MerebHub licensing is not connected.', 'merebhub').'</strong> <a href="'.esc_url(admin_url('admin.php?page=merebhub-settings')).'">'.esc_html__('Add your Keygen API details', 'merebhub').'</a></p></div>';
    }

    public function save_settings(): void
    {
        if (! isset($_POST['merebhub_save_settings'], $_POST['merebhub_settings_nonce'])) {
            return;
        }

        check_admin_referer('merebhub_settings', 'merebhub_settings_nonce');

        if (! current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You are not allowed to change these settings.', 'merebhub'));
        }

        $existing = get_option('merebhub_settings', []);
        $posted_token = sanitize_text_field(wp_unslash($_POST['keygen_api_token'] ?? ''));
        $settings = [
            'keygen_api_url' => esc_url_raw(wp_unslash($_POST['keygen_api_url'] ?? 'https://api.keygen.sh')),
            'keygen_account_id' => sanitize_text_field(wp_unslash($_POST['keygen_account_id'] ?? '')),
            'keygen_api_token' => $posted_token !== '' ? $posted_token : ($existing['keygen_api_token'] ?? ''),
        ];

        update_option('merebhub_settings', $settings, false);
        $this->client->flush_cache();

        $connection = 'connected';

        try {
            $this->client->connection_summary();
        } catch (Throwable $exception) {
            $connection = 'failed';
            set_transient('merebhub_keygen_connection_error_'.get_current_user_id(), $exception->getMessage(), MINUTE_IN_SECONDS);
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'merebhub-settings',
            'updated' => '1',
            'connection' => $connection,
        ], admin_url('admin.php')));
        exit;
    }

    public function settings_page(): void
    {
        $settings = get_option('merebhub_settings', []);
        $connection_error = get_transient('merebhub_keygen_connection_error_'.get_current_user_id());
        delete_transient('merebhub_keygen_connection_error_'.get_current_user_id());
        $summary = null;

        if ($this->client->configured() && ! $connection_error) {
            try {
                $summary = $this->client->connection_summary();
            } catch (Throwable $exception) {
                $connection_error = $exception->getMessage();
            }
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('MerebHub integration', 'merebhub'); ?></h1>
            <?php if ($summary) { ?>
                <div class="notice notice-success inline"><p><strong><?php esc_html_e('Keygen connected.', 'merebhub'); ?></strong> <?php echo esc_html(sprintf(__('%1$d products and %2$d policies are available.', 'merebhub'), $summary['products'], $summary['policies'])); ?></p></div>
            <?php } elseif ($connection_error) { ?>
                <div class="notice notice-error inline"><p><strong><?php esc_html_e('Keygen connection failed:', 'merebhub'); ?></strong> <?php echo esc_html($connection_error); ?></p></div>
            <?php } else { ?>
                <div class="notice notice-warning inline"><p><strong><?php esc_html_e('Keygen is not connected yet.', 'merebhub'); ?></strong></p></div>
            <?php } ?>
            <p><?php esc_html_e('Enter an account-level Keygen API token. Credentials stay in WordPress and are never exposed to storefront visitors.', 'merebhub'); ?></p>
            <form method="post">
                <?php wp_nonce_field('merebhub_settings', 'merebhub_settings_nonce'); ?>
                <table class="form-table" role="presentation">
                    <?php
                    $this->setting_row('keygen_api_url', 'Keygen API URL', $settings['keygen_api_url'] ?? 'https://api.keygen.sh', 'url');
        $this->setting_row('keygen_account_id', 'Keygen account ID', $settings['keygen_account_id'] ?? '');
        $this->setting_row('keygen_api_token', 'Keygen API token', '', 'password');
        ?>
                </table>
                <p class="submit"><button type="submit" name="merebhub_save_settings" class="button button-primary"><?php esc_html_e('Save and test connection', 'merebhub'); ?></button></p>
            </form>
            <hr>
            <h2><?php esc_html_e('Link a WooCommerce product', 'merebhub'); ?></h2>
            <ol>
                <li><?php esc_html_e('Open Products and edit a product.', 'merebhub'); ?></li>
                <li><?php esc_html_e('Open the MerebHub licensing tab in Product data.', 'merebhub'); ?></li>
                <li><?php esc_html_e('Select the matching Keygen product and policy, then update the product.', 'merebhub'); ?></li>
            </ol>
        </div>
        <?php
    }

    public function import_product_metadata(WC_Product $product, array $data): void
    {
        $author = sanitize_text_field((string) $product->get_meta('_merebhub_author_name'));

        if ($author === '') {
            return;
        }

        wp_set_object_terms($product->get_id(), $author, 'merebhub_author', false);
        $product->delete_meta_data('_merebhub_author_name');
        $product->save_meta_data();
    }

    public function register_author_taxonomy(): void
    {
        register_taxonomy('merebhub_author', ['product'], [
            'labels' => ['name' => __('Authors', 'merebhub'), 'singular_name' => __('Author', 'merebhub')],
            'public' => true,
            'rewrite' => ['slug' => 'authors'],
            'show_ui' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'hierarchical' => false,
        ]);
    }

    public function register_acf_fields(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_merebhub_product',
            'title' => 'MerebHub marketplace',
            'fields' => [
                $this->acf_select('field_merebhub_fulfillment', 'Fulfillment type', 'merebhub_fulfillment_type', [
                    'license_key' => 'License key',
                    'download' => 'Digital download',
                    'web_access' => 'Web app access',
                    'external' => 'External fulfillment',
                ], 'license_key'),
                $this->acf_select('field_merebhub_billing', 'Billing model', 'merebhub_billing_model', [
                    'free' => 'Free',
                    'one_time' => 'One-time payment',
                    'manual_subscription' => 'Subscription (manual renewal)',
                ], 'one_time'),
                $this->acf_select('field_merebhub_interval', 'Billing interval', 'merebhub_billing_interval', [
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                    'quarterly' => 'Quarterly',
                    'biannual' => 'Biannual',
                    'yearly' => 'Yearly',
                ]),
                ['key' => 'field_merebhub_trial', 'label' => 'Trial days', 'name' => 'merebhub_trial_days', 'type' => 'number', 'min' => 1],
                ['key' => 'field_merebhub_app_url', 'label' => 'App URL', 'name' => 'merebhub_app_url', 'type' => 'url'],
                ['key' => 'field_merebhub_weekly_sales', 'label' => 'Weekly sales override', 'name' => 'merebhub_weekly_sales', 'type' => 'number', 'min' => 0],
            ],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'product']]],
            'position' => 'normal',
        ]);

        acf_add_local_field_group([
            'key' => 'group_merebhub_author',
            'title' => 'Author profile',
            'fields' => [
                ['key' => 'field_merebhub_author_bio', 'label' => 'Bio', 'name' => 'bio', 'type' => 'textarea'],
                ['key' => 'field_merebhub_author_avatar', 'label' => 'Avatar', 'name' => 'avatar', 'type' => 'image', 'return_format' => 'url'],
                ['key' => 'field_merebhub_author_website', 'label' => 'Website', 'name' => 'website_url', 'type' => 'url'],
            ],
            'location' => [[['param' => 'taxonomy', 'operator' => '==', 'value' => 'merebhub_author']]],
        ]);
    }

    public function product_data_tab(array $tabs): array
    {
        $tabs['merebhub'] = [
            'label' => __('MerebHub licensing', 'merebhub'),
            'target' => 'merebhub_product_data',
            'class' => ['show_if_simple', 'show_if_variable'],
            'priority' => 75,
        ];

        return $tabs;
    }

    public function product_data_panel(): void
    {
        global $post;

        $products = [];
        $policies = [];
        $error = null;

        try {
            $products = $this->client->products();
            $policies = $this->client->policies();
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }

        $selected_product = (string) get_post_meta($post->ID, '_merebhub_keygen_product_id', true);
        $selected_policy = (string) get_post_meta($post->ID, '_merebhub_keygen_policy_id', true);
        ?>
        <div id="merebhub_product_data" class="panel woocommerce_options_panel hidden">
            <div class="options_group">
                <?php if ($error) { ?><p class="form-field"><span class="notice notice-error inline"><strong><?php echo esc_html($error); ?></strong></span></p><?php } ?>
                <p class="form-field">
                    <label for="_merebhub_keygen_product_id"><?php esc_html_e('Keygen product', 'merebhub'); ?></label>
                    <select id="_merebhub_keygen_product_id" name="_merebhub_keygen_product_id" class="select short">
                        <option value=""><?php esc_html_e('No Keygen licensing', 'merebhub'); ?></option>
                        <?php foreach ($products as $product) { ?>
                            <option value="<?php echo esc_attr($product['id']); ?>" <?php selected($selected_product, $product['id']); ?>><?php echo esc_html($product['attributes']['name'] ?? $product['id']); ?></option>
                        <?php } ?>
                    </select>
                </p>
                <p class="form-field">
                    <label for="_merebhub_keygen_policy_id"><?php esc_html_e('Keygen policy', 'merebhub'); ?></label>
                    <select id="_merebhub_keygen_policy_id" name="_merebhub_keygen_policy_id" class="select short">
                        <option value=""><?php esc_html_e('Choose a policy', 'merebhub'); ?></option>
                        <?php foreach ($policies as $policy) {
                            $product_id = $policy['relationships']['product']['data']['id'] ?? '';
                            ?>
                            <option data-product="<?php echo esc_attr($product_id); ?>" value="<?php echo esc_attr($policy['id']); ?>" <?php selected($selected_policy, $policy['id']); ?>><?php echo esc_html($policy['attributes']['name'] ?? $policy['id']); ?></option>
                        <?php } ?>
                    </select>
                    <span class="description"><?php esc_html_e('Licenses are issued against this policy. Only policies belonging to the selected product are shown.', 'merebhub'); ?></span>
                </p>
            </div>
            <script>
                (() => {
                    const product = document.getElementById('_merebhub_keygen_product_id');
                    const policy = document.getElementById('_merebhub_keygen_policy_id');
                    if (!product || !policy) return;
                    const options = Array.from(policy.options);
                    const filter = () => {
                        options.forEach((option) => {
                            option.hidden = Boolean(option.dataset.product && option.dataset.product !== product.value);
                        });
                        if (policy.selectedOptions[0]?.hidden) policy.value = '';
                    };
                    product.addEventListener('change', filter);
                    filter();
                })();
            </script>
        </div>
        <?php
    }

    public function save_product_link(int $post_id): void
    {
        $product = wc_get_product($post_id);

        if (! $product) {
            return;
        }

        $product_id = sanitize_text_field(wp_unslash($_POST['_merebhub_keygen_product_id'] ?? ''));
        $policy_id = sanitize_text_field(wp_unslash($_POST['_merebhub_keygen_policy_id'] ?? ''));
        $policy_product_id = '';

        try {
            foreach ($this->client->policies() as $policy) {
                if (($policy['id'] ?? '') === $policy_id) {
                    $policy_product_id = $policy['relationships']['product']['data']['id'] ?? '';
                    break;
                }
            }
        } catch (Throwable $exception) {
            $policy_product_id = $product_id;
        }

        if ($policy_id !== '' && $policy_product_id !== $product_id) {
            $policy_id = '';
        }

        $product->update_meta_data('_merebhub_keygen_product_id', $product_id);
        $product->update_meta_data('_merebhub_keygen_policy_id', $policy_id);
        $product->save_meta_data();
    }

    public function append_rest_data(WP_REST_Response $response, WC_Product $product, WP_REST_Request $request): WP_REST_Response
    {
        $author = wp_get_post_terms($product->get_id(), 'merebhub_author')[0] ?? null;
        $author_data = $author ? [
            'name' => $author->name,
            'slug' => $author->slug,
            'bio' => function_exists('get_field') ? get_field('bio', $author) : '',
            'avatar_url' => function_exists('get_field') ? get_field('avatar', $author) : '',
            'website_url' => function_exists('get_field') ? get_field('website_url', $author) : '',
        ] : [];
        $platforms = wp_get_post_terms($product->get_id(), 'pa_platform', ['fields' => 'names']);

        $response->data['merebhub'] = [
            'author' => $author_data,
            'platforms' => is_wp_error($platforms) ? [] : $platforms,
            'fulfillment_type' => get_field('merebhub_fulfillment_type', $product->get_id()) ?: 'license_key',
            'billing_model' => get_field('merebhub_billing_model', $product->get_id()) ?: 'one_time',
            'billing_interval' => get_field('merebhub_billing_interval', $product->get_id()) ?: null,
            'trial_days' => get_field('merebhub_trial_days', $product->get_id()) ?: null,
            'app_url' => get_field('merebhub_app_url', $product->get_id()) ?: null,
            'weekly_sales' => get_field('merebhub_weekly_sales', $product->get_id()) ?: $product->get_total_sales(),
            'keygen_product_id' => $product->get_meta('_merebhub_keygen_product_id'),
            'keygen_policy_id' => $product->get_meta('_merebhub_keygen_policy_id'),
        ];

        return $response;
    }

    private function setting_row(string $name, string $label, string $value, string $type = 'text'): void
    {
        printf(
            '<tr><th scope="row"><label for="%1$s">%2$s</label></th><td><input class="regular-text" type="%3$s" id="%1$s" name="%1$s" value="%4$s" autocomplete="off"></td></tr>',
            esc_attr($name),
            esc_html($label),
            esc_attr($type),
            esc_attr($value),
        );
    }

    private function acf_select(string $key, string $label, string $name, array $choices, ?string $default = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'name' => $name,
            'type' => 'select',
            'choices' => $choices,
            'default_value' => $default,
            'allow_null' => true,
            'return_format' => 'value',
        ];
    }
}
