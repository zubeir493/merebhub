<?php

defined('ABSPATH') || exit;

do_action('woocommerce_before_customer_login_form');

$registration_enabled = get_option('woocommerce_enable_myaccount_registration') === 'yes';
$redirect = isset($_GET['redirect_to'])
    ? wp_validate_redirect(esc_url_raw(wp_unslash($_GET['redirect_to'])), wc_get_page_permalink('myaccount'))
    : wc_get_page_permalink('myaccount');
?>
<section class="mh-auth">
    <header class="mh-auth__intro">
        <p class="mh-eyebrow"><?php esc_html_e('Your MerebHub account', 'merebhub'); ?></p>
        <h1><?php esc_html_e('Software you buy, all in one place', 'merebhub'); ?></h1>
        <p><?php esc_html_e('Sign in to manage your cart, saved apps, orders, downloads, and license keys.', 'merebhub'); ?></p>
    </header>

    <div class="mh-auth__grid<?php echo $registration_enabled ? '' : ' mh-auth__grid--single'; ?>" id="customer_login">
        <div class="mh-auth__panel">
            <h2><?php esc_html_e('Welcome back', 'merebhub'); ?></h2>
            <p><?php esc_html_e('Use the same WordPress account you use elsewhere on this site.', 'merebhub'); ?></p>

            <form class="woocommerce-form woocommerce-form-login login" method="post">
                <?php do_action('woocommerce_login_form_start'); ?>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="username"><?php esc_html_e('Email or username', 'merebhub'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
                    <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo isset($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required aria-required="true">
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                    <label for="password"><?php esc_html_e('Password', 'merebhub'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
                    <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true">
                </p>

                <?php do_action('woocommerce_login_form'); ?>
                <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                <input type="hidden" name="redirect" value="<?php echo esc_attr($redirect); ?>">
                <button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Log in', 'merebhub'); ?>"><?php esc_html_e('Log in', 'merebhub'); ?></button>

                <div class="mh-auth__meta">
                    <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                        <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever">
                        <span><?php esc_html_e('Remember me', 'merebhub'); ?></span>
                    </label>
                    <a href="<?php echo esc_url(wc_lostpassword_url()); ?>"><?php esc_html_e('Forgot password?', 'merebhub'); ?></a>
                </div>

                <?php do_action('woocommerce_login_form_end'); ?>
            </form>
        </div>

        <?php if ($registration_enabled) { ?>
            <div class="mh-auth__panel">
                <h2><?php esc_html_e('Create an account', 'merebhub'); ?></h2>
                <p><?php esc_html_e('Your purchases and licenses will stay available across devices.', 'merebhub'); ?></p>

                <form method="post" class="woocommerce-form woocommerce-form-register register">
                    <?php do_action('woocommerce_register_form_start'); ?>

                    <?php if (get_option('woocommerce_registration_generate_username') === 'no') { ?>
                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label for="reg_username"><?php esc_html_e('Username', 'merebhub'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
                            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo isset($_POST['username']) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required aria-required="true">
                        </p>
                    <?php } ?>

                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_email"><?php esc_html_e('Email address', 'merebhub'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo isset($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" required aria-required="true">
                    </p>

                    <?php if (get_option('woocommerce_registration_generate_password') === 'no') { ?>
                        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label for="reg_password"><?php esc_html_e('Password', 'merebhub'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
                            <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true">
                        </p>
                    <?php } else { ?>
                        <p class="mh-auth__privacy"><?php esc_html_e('A secure password setup link will be sent to your email address.', 'merebhub'); ?></p>
                    <?php } ?>

                    <?php do_action('woocommerce_register_form'); ?>
                    <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                    <input type="hidden" name="redirect" value="<?php echo esc_attr($redirect); ?>">
                    <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Create account', 'merebhub'); ?>"><?php esc_html_e('Create account', 'merebhub'); ?></button>

                    <div class="mh-auth__privacy"><?php wc_get_privacy_policy_text('registration'); ?></div>
                    <?php do_action('woocommerce_register_form_end'); ?>
                </form>
            </div>
        <?php } ?>
    </div>
</section>
<?php do_action('woocommerce_after_customer_login_form'); ?>
