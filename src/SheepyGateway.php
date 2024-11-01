<?php
/**
 * @description Sheepy For WooCommerce gateway for plugin.
 * @author      Sheepy https://www.sheepy.com/
 * @version     1.0.0
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Sheepy\WooCommerce;

use Exception;
use Sheepy\Api\Client;
use Throwable;
use WC_Logger;
use WC_Logger_Interface;
use WC_Payment_Gateway;

class SheepyGateway extends WC_Payment_Gateway
{
    private const STATUSES_FIELD = 'order_states';

    public const GATEWAY_OPTIONS_KEY = 'sheepy_plugin_settings';

    /** @var string */
    public $id = 'sheepy';

    /** @var bool */
    public $has_fields = false;

    /** @var string */
    public $method_title = 'Sheepy';

    /** @var array */
    public $order_states;

    /** @var string */
    public $debug;

    /** @var string */
    private $api_key;

    /** @var string */
    private $secret_key;

    /** @var string */
    private $notification_key;

    /** @var null|WC_Logger|WC_Logger_Interface */
    private $logger;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        // General
        $this->order_button_text = __('Cryptocurrency, stablecoins and other digital assets', 'sheepy-for-woocommerce');

        $this->method_title = 'Sheepy';
        $this->method_description = __('Cryptocurrency payments are processed by Sheepy.com.', 'sheepy-for-woocommerce');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->order_states = $this->get_option(self::STATUSES_FIELD);
        $this->debug = 'yes' === $this->get_option('debug', 'no');

        // Define Sheepy settings
        $this->api_key = $this->settings['api_key'];
        $this->secret_key = $this->settings['secret_key'];
        $this->notification_key = $this->settings['notification_key'];

        // Actions
        add_action(
            "woocommerce_update_options_payment_gateways_$this->id",
            [$this, 'process_admin_options']
        );

        add_action(
            "woocommerce_update_options_payment_gateways_$this->id",
            [$this, 'save_order_states']
        );
    }

    /**
     * Initialise Gateway Settings Form Fields.
     *
     * @return void
     */
    public function init_form_fields(): void
    {
        $log_file = 'sheepy-' . sanitize_file_name(wp_hash('sheepy')) . '-log';
        $logs_href = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-status&tab=logs&log_file=' . $log_file;

        $php_version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $plugin_version = get_option('sheepy_plugin_version');
        $help_link = '<a href="' . SheepyConstants::SHEEPY_HELP_LINK . '">' . __('help center', 'sheepy-for-woocommerce') . '</a>';

        $support_description = sprintf(
            /* translators: %1$s: plugin version, %2$s: php version, %3$s: help link */
            __('This plugin version is %1$s and your PHP version is %2$s. If you need assistance, please contact us on %3$s. Thank you for using Sheepy!', 'sheepy-for-woocommerce'),
            $plugin_version,
            $php_version,
            $help_link
        );

        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'sheepy-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Payments via Sheepy', 'sheepy-for-woocommerce'),
                'default' => 'yes',
            ],
            'title' => [
                /** validation: @see SheepyGateway::validate_title_field() */
                'title' => __('Title', 'sheepy-for-woocommerce'),
                'type' => 'text',
                'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'sheepy-for-woocommerce'),
                'default' => __('Cryptocurrency, stablecoins and other digital assets', 'sheepy-for-woocommerce'),
                'desc_tip' => true,
            ],
            'description' => [
                /** validation: @see SheepyGateway::validate_description_field() */
                'title' => __('Customer Message', 'sheepy-for-woocommerce'),
                'type' => 'textarea',
                'description' => __('Message to explain how the customer will be paying for the purchase.', 'sheepy-for-woocommerce'),
                'default' => __('Cryptocurrency payments are processed by Sheepy.com', 'sheepy-for-woocommerce'),
                'desc_tip' => true,
            ],
            'api_key' => [
                /** validation: @see SheepyGateway::validate_api_key_field() */
                'title' => '<a href="' . SheepyConstants::SHEEPY_API_INTEGRATION_LINK . '">' . __('API Key', 'sheepy-for-woocommerce') . '</a>',
                'type' => 'text',
                'description' => __('API Key', 'sheepy-for-woocommerce'),
                'default' => '',
                'placeholder' => __('API Key', 'sheepy-for-woocommerce'),
                'desc_tip' => true,
            ],
            'secret_key' => [
                /** validation: @see SheepyGateway::validate_secret_key_field() */
                'title' => '<a href="' . SheepyConstants::SHEEPY_API_INTEGRATION_LINK . '">' . __('API Secret Key', 'sheepy-for-woocommerce') . '</a>',
                'type' => 'text',
                'description' => __('API Secret Key', 'sheepy-for-woocommerce'),
                'default' => '',
                'placeholder' => __('API Secret Key', 'sheepy-for-woocommerce'),
                'desc_tip' => true,
            ],
            'notification_key' => [
                /** validation: @see SheepyGateway::validate_notification_key_field() */
                'title' => '<a href="' . SheepyConstants::SHEEPY_API_NOTIFICATIONS_LINK . '">' . __('Notification Secret Key', 'sheepy-for-woocommerce') . '</a>',
                'type' => 'text',
                'description' => __('Notification Secret Key', 'sheepy-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ],
            self::STATUSES_FIELD => [
                /** validation: @see SheepyGateway::validate_order_states_field() */
                /** form view: @see SheepyGateway::generate_order_states_html() */
                'type' => self::STATUSES_FIELD,
            ],
            'debug' => [
                'title' => __('Debug Log', 'sheepy-for-woocommerce'),
                'type' => 'checkbox',
                'label' => '<a href="' . $logs_href . '">' . __('View Logs', 'sheepy-for-woocommerce') . '</a>',
                'default' => 'no',
                'description' => __('Log Sheepy events, such as webhook requests', 'sheepy-for-woocommerce'),
                'desc_tip' => true,
            ],
            'support_details' => [
                'title' => __('Plugin & Support Information', 'sheepy-for-woocommerce'),
                'type' => 'title',
                'description' => $support_description,
            ],
        ];
    }

    /**
     * HTML output for form field type `order_states`.
     *
     * @return false|string
     */
    public function generate_order_states_html()
    {
        ob_start();

        $sheepy_statuses = SheepyConstants::SHEEPY_STATUSES_DESCRIPTIONS;

        $wc_statuses = wc_get_order_statuses();

        ?>
        <tr style="vertical-align: top;">
            <th scope="row" class="titledesc">
                <a href="<?php echo esc_url(SheepyConstants::SHEEPY_INVOICE_STATUSES_LINK); ?>">
                    <?php echo esc_html(__('Order Statuses', 'sheepy-for-woocommerce')); ?>
                </a>
            </th>
            <td class="forminp" id="sheepy_order_states">
                <table style="padding: 0; margin: 0;">
                    <?php foreach ($sheepy_statuses as $status => $sheepy_description): ?>
                        <tr>
                            <th><?php echo esc_html($sheepy_description); ?></th>
                            <td>
                                <select name="woocommerce_sheepy_order_states[<?php echo esc_html($status); ?>]">
                                    <?php foreach ($wc_statuses as $wc_status => $wc_status_description): ?>
                                        <option value="<?php echo esc_html($wc_status); ?>"
                                            <?php echo esc_html($this->selected($status, $wc_status)); ?>>
                                            <?php echo esc_html($wc_status_description); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * Save order states.
     *
     * @return void
     */
    public function save_order_states(): void
    {
        $sheepy_statuses = SheepyConstants::SHEEPY_STATUSES_DESCRIPTIONS;

        $wc_statuses = wc_get_order_statuses();

        $request_order_states = $this->get_field_value(self::STATUSES_FIELD, []);

        $order_states = $this->get_option(self::STATUSES_FIELD);

        foreach ($sheepy_statuses as $sheepy_status => $sheepy_status_describe) {
            if (false === isset($request_order_states[$sheepy_status])) {
                continue;
            }

            $wc_state = sanitize_text_field($request_order_states[$sheepy_status]);

            if (true === array_key_exists($wc_state, $wc_statuses)) {
                $this->log('    [Info] Updating order state ' . $sheepy_status . ' to ' . $wc_state);
                $order_states[$sheepy_status] = $wc_state;
            }
        }

        $this->update_option(self::STATUSES_FIELD, $order_states);
    }

    /**
     * Validate API Secret KEY.
     *
     * @param string $key
     * @param $value
     * @return string
     */
    public function validate_secret_key_field(string $key, $value): string
    {
        $secret_key = $this->get_option($key);

        if ($secret_key !== $value) {
            $secret_key = $value;
        }

        return sanitize_text_field($secret_key);
    }

    /**
     * Validate API Secret KEY.
     *
     * @param string $key
     * @param $value
     * @return string
     */
    public function validate_notification_key_field(string $key, $value): string
    {
        $notification_key = $this->get_option($key);

        if ($notification_key !== $value) {
            $notification_key = $value;
        }

        return sanitize_text_field($notification_key);
    }

    /**
     * Validate API Name.
     *
     * @param string $key
     * @param $value
     * @return string
     */
    public function validate_api_key_field(string $key, $value): string
    {
        $api_key = $this->get_option($key);

        if ($api_key !== $value) {
            $api_key = $value;
        }

        return sanitize_text_field($api_key);
    }

    /**
     * Validate Customer Message.
     *
     * @param string $key
     * @param $value
     *
     * @return string
     */
    public function validate_description_field(string $key, $value): string
    {
        $desc = $this->get_option($key);

        if ($desc !== $value) {
            $desc = $value;
        }

        return sanitize_text_field($desc);
    }

    /**
     * Validate Title.
     *
     * @param string $key
     * @param $value
     *
     * @return string
     */
    public function validate_title_field(string $key, $value): string
    {
        $title = $this->get_option($key);

        if ($title !== $value) {
            $title = $value;
        }

        return sanitize_text_field($title);
    }

    /**
     * Validate Order States.
     *
     * @param string $key
     * @param $value
     *
     * @return array
     */
    public function validate_order_states_field(string $key, $value): array
    {
        /** @var array $order_states */
        $order_states = $value;

        if (!empty($order_states) && is_array($order_states)) {
            foreach ($order_states as $key => $val) {
                $order_states[$key] = sanitize_text_field($val);
            }
        }

        return $order_states;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     *
     * @return array
     * @throws Exception
     */
    public function process_payment($order_id): array
    {
        $this->log('    [Info] Entered process_payment() with order_id = ' . $order_id . '...');

        if (true === empty($order_id)) {
            $this->log('    [Error] The Sheepy payment plugin was called to process a payment but the order_id was missing.');
            wc_add_notice('The Sheepy payment plugin was called to process a payment but the order_id was missing. Cannot continue!', 'error');
            return [];
        }

        $order = wc_get_order($order_id);

        if (false === $order) {
            $this->log('    [Error] The Sheepy payment plugin was called to process a payment but could not retrieve the order details for order_id ' . $order_id);
            wc_add_notice('The Sheepy payment plugin was called to process a payment but could not retrieve the order details for order_id ' . $order_id . '. Cannot continue!', 'error');
            return [];
        }

        $shopUrl = home_url();

        $data = [
            'amount' => $order->calculate_totals(),
            'reference' => (string)$order->get_id(),
            'description' => "$shopUrl Order #{$order->get_id()}",
            'email' => $order->get_billing_email(),
            'back_url' => $shopUrl,
            'success_url' => $this->get_return_url($order),
            'settings' => [
                'currency' => get_woocommerce_currency(),
                'notification_url' => $this->notification_url(),
            ],
        ];

        $sheepyService = new Client(
            $this->api_key,
            $this->secret_key,
            'WordPress Sheepy plugin v ' . SheepyConstants::SHEEPY_PLUGIN_VERSION
        );

        try {
            $invoice = $sheepyService->createInvoice($data);
        } catch (Throwable $t) {
            $this->log('    [Error] Error generating invoice for ' . $order->get_order_number() . ', error: ' . $t->getMessage());

            return $this->get_invoice_error_response();
        }

        if (!empty($invoice)) {
            // Remove cart
            WC()->cart->empty_cart();

            $this->log('    [Info] Leaving process_payment()...');

            // Redirect the customer to the Sheepy invoice
            return [
                'result' => 'success',
                'redirect' => $invoice['data']['url'],
            ];
        } else {
            $this->log('    [Error] API empty invoice');

            return $this->get_invoice_error_response();
        }
    }

    /**
     * Log message.
     *
     * @param $message
     * @return void
     */
    public function log($message): void
    {
        if (true === isset($this->debug) && 'yes' == $this->debug) {
            if (false === isset($this->logger) || true === empty($this->logger)) {
                $this->logger = wc_get_logger();
            }

            $this->logger->debug($message, ['source' => 'SHEEPY LOG']);
        }
    }

    /**
     * Get notification URL.
     *
     * @return string
     */
    public function notification_url(): string
    {
        $route = SheepyConstants::SHEEPY_REST_API_ROUTE;

        return get_site_url(null, "wp-json/$route");
    }

    /**
     * Handle sheepy API notification.
     *
     * @throws Exception
     */
    public function handle_notification(array $requestParams)
    {
        (new SheepyCallbackHandler())->handle($requestParams, $this, $this->notification_key);
    }

    /**
     * Return the name of the option in the WP DB.
     *
     * @return string
     */
    public function get_option_key(): string
    {
        return self::GATEWAY_OPTIONS_KEY;
    }

    /**
     * Get invoice error response.
     *
     * @return string[]
     */
    private function get_invoice_error_response(): array
    {
        return [
            'result' => 'error',
            'messages' => 'Sorry, but checkout with Sheepy does not appear to be working.',
        ];
    }

    /**
     * Get selected for option.
     *
     * @param string $status
     * @param string $wc_status
     * @return string
     */
    private function selected(string $status, string $wc_status): string
    {
        $default_statuses = SheepyConstants::SHEEPY_TO_WC_DEFAULT_STATUS_MATCHING;

        /** @var $options_statuses array */
        $options_statuses = $this->get_option(self::STATUSES_FIELD, []);

        $current = empty($options_statuses[$status])
            ? $default_statuses[$status]
            : $options_statuses[$status];

        return $current === $wc_status ? ' selected' : '';
    }
}
