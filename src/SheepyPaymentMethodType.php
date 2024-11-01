<?php
/**
 * @description Sheepy For WooCommerce payment method type for plugin.
 * @author      Sheepy https://www.sheepy.com/
 * @version     1.0.0
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Sheepy\WooCommerce;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class SheepyPaymentMethodType extends AbstractPaymentMethodType
{
    /** @var string */
    protected $name = 'sheepy';

    /**
     * When called invokes any initialization/setup for the integration.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->settings = get_option(SheepyGateway::GATEWAY_OPTIONS_KEY, []);
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active(): bool
    {
        return filter_var($this->get_setting('enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Retrieves the value of the 'enable_for_virtual' setting and converts it to a boolean.
     *
     * @return bool
     */
    private function get_enable_for_virtual(): bool
    {
        return filter_var($this->get_setting('enable_for_virtual', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns an array of script handles to enqueue for this payment method in
     * the frontend context.
     *
     * @return string[]
     */
    public function get_payment_method_script_handles(): array
    {
        $script_name = 'sheepy-blocks-integration';

        $script_asset_path = plugin_dir_path(__FILE__) .'..' . DIRECTORY_SEPARATOR .'dist' . DIRECTORY_SEPARATOR . 'js'.DIRECTORY_SEPARATOR . 'sheepy.asset.php';
        $script_asset = file_exists($script_asset_path)
            ? require($script_asset_path)
            : [
                'dependencies' => [],
                'version' => SheepyConstants::SHEEPY_PLUGIN_VERSION,
            ];

        wp_register_script(
            $script_name,
            plugins_url('../dist/js/sheepy.js', __FILE__),
            $script_asset['dependencies'],
            $script_asset['version'],
            false
        );

        return [$script_name];
    }

    /**
     * Get payment method data.
     *
     * @return array
     */
    public function get_payment_method_data(): array
    {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
            'enableForVirtual' => $this->get_enable_for_virtual(),
            'supports' => $this->get_supported_features(),
        ];
    }
}
