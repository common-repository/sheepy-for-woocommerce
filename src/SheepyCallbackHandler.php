<?php
/**
 * @description Sheepy For WooCommerce callback handler for plugin.
 * @author      Sheepy https://www.sheepy.com/
 * @version     1.0.0
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Sheepy\WooCommerce;

use Exception;
use Sheepy\Api\Client;
use WC_Order;

class SheepyCallbackHandler
{
    /** @var mixed|null */
    private $requestTimestamp;
    /** @var mixed|null */
    private $requestSignature;

    /**
     * SheepyCallbackModuleFrontController constructor.
     */
    public function __construct()
    {
        $this->requestTimestamp = sanitize_text_field($_SERVER[Client::TIMESTAMP_HEADER]) ?? null;
        $this->requestSignature = sanitize_text_field($_SERVER[Client::SIGNATURE_HEADER]) ?? null;
    }

    /**
     * Handle incoming sheepy notification.
     *
     * @param array $requestBody
     * @param SheepyGateway $gateway
     * @param string $notificationKey
     *
     * @return void
     * @throws Exception
     */
    public function handle(array $requestBody, SheepyGateway $gateway, string $notificationKey)
    {
        $url = $gateway->notification_url();

        /** @var array $optionStatusesMatch */
        $optionStatusesMatch = $gateway->get_option('order_states');

        if (!$this->checkRequestLifetime()) {
            throw new Exception('Received Sheepy notification request has invalid timestamp or the request has expired.', 400);
        }

        if (!$this->checkRequestSignature($url, $notificationKey)) {
            throw new Exception('Received Sheepy notification request has invalid signature.', 400);
        }

        if ($requestBody['type'] != 'invoice_status_changed') {
            return;
        }

        $invoiceData = $requestBody['data'];

        $orderId = $invoiceData['invoice']['reference'];
        $order = wc_get_order($orderId);

        if (!$order) {
            throw new Exception('The Sheepy payment plugin was called to process an API notification but could not retrieve the order details for order_id ' . esc_html($orderId) . '. Cannot continue!');
        }

        $this->updateOrder($order, $optionStatusesMatch, $invoiceData['invoice']['status']);
    }

    /**
     * Check request lifetime.
     *
     * @return bool
     */
    private function checkRequestLifetime(): bool
    {
        $time = time();
        $timestamp = (int)$this->requestTimestamp;

        return $timestamp !== 0 && $timestamp <= $time && $time - $timestamp <= 5;
    }

    /**
     * Check request signature.
     *
     * @param string $notificationUrl
     * @param string $secret
     * @return bool
     */
    private function checkRequestSignature(string $notificationUrl, string $secret): bool
    {
        $signature = Client::createSignature(
            $this->requestTimestamp,
            'POST',
            $notificationUrl,
            sanitize_text_field(file_get_contents('php://input')),
            $secret
        );

        return !empty($this->requestSignature) && $this->requestSignature == $signature;
    }

    /**
     * Update WooCommerce order status.
     *
     * @param WC_Order $order
     * @param array $optionStatusesMatch
     * @param string $sheepyStatus
     * @return void
     * @throws Exception
     */
    private function updateOrder(WC_Order $order, array $optionStatusesMatch, string $sheepyStatus): void
    {
        $wcOrderStatus = $optionStatusesMatch[$sheepyStatus] ?? false;

        if ($wcOrderStatus === false) {
            throw new Exception('Sheepy API notification has not supported by current plugin status: ' . esc_html($sheepyStatus) . '. Please contact Sheepy support team.');
        }

        $order->update_status($wcOrderStatus);

        switch ($sheepyStatus) {
            case SheepyConstants::SHEEPY_STATUS_PARTIALLY_PAID:
                $order->add_order_note(
                    __('Sheepy payment is paid partially. Please contact to merchant to refund or complete the payment.', 'sheepy-for-woocommerce')
                );
                break;

            case SheepyConstants::SHEEPY_STATUS_CONFIRMING:
                $order->add_order_note(
                    __('Sheepy payment is confirming.', 'sheepy-for-woocommerce')
                );
                break;

            case SheepyConstants::SHEEPY_STATUS_EXPIRED:
                $order->add_order_note(
                    __('Sheepy payment is expired.', 'sheepy-for-woocommerce')
                );
                break;

            case SheepyConstants::SHEEPY_STATUS_INVALID:
                $order->add_order_note(
                    __('Sheepy payment is invalid.', 'sheepy-for-woocommerce')
                );
                break;

            case SheepyConstants::SHEEPY_STATUS_DONE:
                $order->payment_complete();
                $order->add_order_note(
                    __('Sheepy invoice payment completed. Payment credited to your merchant account.', 'sheepy-for-woocommerce')
                );
                break;

            case SheepyConstants::SHEEPY_STATUS_REFUND_REQUESTED:
                $order->payment_complete();
                $order->add_order_note(
                    __('Sheepy invoice payment refund requested.', 'sheepy-for-woocommerce')
                );
                break;

            case SheepyConstants::SHEEPY_STATUS_REFUNDED:
                $order->payment_complete();
                $order->add_order_note(
                    __('Sheepy invoice payment refunded.', 'sheepy-for-woocommerce')
                );
                break;

            case SheepyConstants::SHEEPY_STATUS_ERROR:
                $order->payment_complete();
                $order->add_order_note(
                    __('Sheepy invoice payment error.', 'sheepy-for-woocommerce')
                );
                break;
        }
    }
}
