<?php
/**
 * Plugin Name: Sheepy for WooCommerce
 * Requires Plugins: woocommerce
 * Description: Accept Bitcoin and other cryptocurrencies from anywhere in the world, and attract new customers who prefer crypto payments with Sheepy plugin.
 * Author:      Sheepy
 * Author URI:  https://www.sheepy.com/
 * Version:     1.0.0
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

use Sheepy\WooCommerce\SheepyConstants;
use Sheepy\WooCommerce\SheepyGateway;
use Sheepy\WooCommerce\SheepyPaymentMethodType;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/vendor/autoload.php';

// Ensures WooCommerce is loaded before initializing the plugin
add_action('plugins_loaded', 'sheepy_init', 0);
register_activation_hook(__FILE__, 'sheepy_activate');

/**
 * Get settings link button.
 *
 * @param $links
 * @param $file
 * @return array
 */
function sheepy_settings_link($links, $file): array
{
    static $this_plugin;

    if (false === isset($this_plugin) || true === empty($this_plugin)) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $log_file = 'sheepy-' . sanitize_file_name(wp_hash('sheepy')) . '-log';
        $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=sheepy');
        $logs_url = admin_url('admin.php?page=wc-status&tab=logs') . "&log_file=$log_file";

        $settings_link = '<a href="' . $settings_url . '">' . __('Settings', 'sheepy-for-woocommerce') . '</a>';
        $logs_link = '<a href="' . $logs_url . '">' . __('Logs', 'sheepy-for-woocommerce') . '</a>';

        array_unshift($links, $settings_link, $logs_link);
    }

    return $links;
}

/**
 * Initializes the plugin.
 *
 * @return SheepyGateway
 */
function sheepy_init(): SheepyGateway
{
    if (false === class_exists('WC_Payment_Gateway')) {
        wp_die('WooCommerce does not appear to be installed and activated.');
    }

    add_filter('woocommerce_payment_gateways', function ($plugins) {
        return array_merge([SheepyGateway::class], $plugins);
    });

    /**
     * Add Settings link to the plugin entry in the plugins menu
     **/
    add_filter('plugin_action_links', 'sheepy_settings_link', 10, 2);

    add_action('woocommerce_blocks_loaded', 'woocommerce_sheepy_woocommerce_block_support');

    function woocommerce_sheepy_woocommerce_block_support()
    {
        if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                    $payment_method_registry->register(new SheepyPaymentMethodType());
                },
                5
            );
        }
    }

    return new SheepyGateway();
}

/**
 * Add webhook request handler.
 *
 * @param WP_REST_Request $request
 * @return array
 */
function sheepy_add_webhook_handler(WP_REST_Request $request): array
{
    $gateway = new SheepyGateway();

    $params = $request->get_params();

    $gateway->log('    [Info] Entered sheepy_webhook()...');

    try {
        $gateway->handle_notification($params);
    } catch (Throwable $t) {
        return [
            'success' => false,
            'error' => $t->getMessage(),
        ];
    }

    $gateway->log('    [Info] Leaving sheepy_webhook()...');

    return [
        'success' => true,
        'error' => null,
    ];
}

/**
 * Register webhook route.
 *
 * @return void
 */
function sheepy_register_webhook(): void
{
    [$namespace, $method] = explode('/', SheepyConstants::SHEEPY_REST_API_ROUTE);

    register_rest_route(
        $namespace,
        $method,
        [
            'methods' => WP_REST_Server::CREATABLE,
            'permission_callback' => '__return_true',
            /** @see sheepy_add_webhook_handler */
            'callback' => 'sheepy_add_webhook_handler',
        ]
    );
}

add_action('rest_api_init', 'sheepy_register_webhook');

/**
 * Checks if the plugin requirements are met.
 *
 * @return false|string
 */
function sheepy_check_requirements()
{
    global $wp_version;
    global $woocommerce;

    $errors = [];

    // PHP version check
    if (true === version_compare(PHP_VERSION, SheepyConstants::REQUIRED_PHP_VERSION, '<')) {
        /* translators: %s: required PHP version */
        $errors[] = sprintf(__('Your PHP version is too old. The Sheepy payment plugin requires PHP %s or higher to function. Please contact your web server administrator for assistance.', 'sheepy-for-woocommerce'), SheepyConstants::REQUIRED_PHP_VERSION);
    }

    // WordPress version check
    if (true === version_compare($wp_version, SheepyConstants::REQUIRED_WORDPRESS_VERSION, '<')) {
        /* translators: %s: required WordPress version */
        $errors[] = sprintf(__('Your WordPress version is too old. The Sheepy payment plugin requires WordPress %s or higher to function. Please contact your web server administrator for assistance.', 'sheepy-for-woocommerce'), SheepyConstants::REQUIRED_WORDPRESS_VERSION);
    }

    // WooCommerce version check
    if (true === empty($woocommerce)) {
        $errors[] = __('The WooCommerce plugin for WordPress needs to be installed and activated. Please contact your web server administrator for assistance.', 'sheepy-for-woocommerce');
    } elseif (true === version_compare($woocommerce->version, SheepyConstants::REQUIRED_WOOCOMMERCE_VERSION, '<')) {
        /* translators: %1$s: required WooCommerce version, %2$s: current WooCommerce version */
        $errors[] = sprintf(__('Your WooCommerce version is too old. The Sheepy payment plugin requires WooCommerce %1$s or higher to function. Your version is %2$s. Please contact your web server administrator for assistance.', 'sheepy-for-woocommerce'), SheepyConstants::REQUIRED_WOOCOMMERCE_VERSION, $woocommerce->version);
    }

    // Curl required
    if (false === extension_loaded('curl')) {
        $errors[] = __('The Sheepy payment plugin requires the Curl extension for PHP in order to function. Please contact your web server administrator for assistance.', 'sheepy-for-woocommerce');
    }

    // Prohibited countries check
    if (true === in_array(wc_get_base_location()['country'], SheepyConstants::SHEEPY_PROHIBITED_COUNTRIES)) {
        $errors[] = __('Your store is registered in a country where Sheepy payments does not supports.', 'sheepy-for-woocommerce');
    }

    return false === empty($errors)
        ? implode("<br>\n", $errors)
        : false;
}

/**
 * Activate plugin.
 *
 * @return void
 */
function sheepy_activate()
{
    // Check for Requirements
    $failed = sheepy_check_requirements();

    $plugins_url = admin_url('plugins.php');

    // Requirements met, activate the plugin
    if ($failed === false) {
        update_option('sheepy_plugin_version', SheepyConstants::SHEEPY_PLUGIN_VERSION);
    } else {
        // Requirements not met, return an error message
        wp_die(
            esc_html($failed) . '<br><a href="' . esc_url($plugins_url) . '">' . esc_html(__('Return to plugins page', 'sheepy-for-woocommerce')) . '</a>'
        );
    }
}
