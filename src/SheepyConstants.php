<?php
/**
 * @description Sheepy For WooCommerce constants for plugin.
 * @author      Sheepy https://www.sheepy.com/
 * @version     1.0.0
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Sheepy\WooCommerce;

class SheepyConstants
{
    public const REQUIRED_PHP_VERSION = '7.2';
    public const REQUIRED_WOOCOMMERCE_VERSION = '6.5';
    public const REQUIRED_WORDPRESS_VERSION = '5.7';
    public const SHEEPY_PLUGIN_VERSION = '1.0.0';
    public const SHEEPY_API_INTEGRATION_LINK = 'https://www.sheepy.com/integration/api';
    public const SHEEPY_API_NOTIFICATIONS_LINK = 'https://www.sheepy.com/integration/profile#notifications';
    public const SHEEPY_INVOICE_STATUSES_LINK = 'https://www.sheepy.com/api#tag/Invoices/Invoice-statuses';
    public const SHEEPY_HELP_LINK = 'https://help.sheepy.com/#login';
    public const SHEEPY_REST_API_ROUTE = 'sheepy-payments/gateway';
    public const SHEEPY_STATUS_NEW = 'new';
    public const SHEEPY_STATUS_PARTIALLY_PAID = 'partially_paid';
    public const SHEEPY_STATUS_CONFIRMING = 'confirming';
    public const SHEEPY_STATUS_EXPIRED = 'expired';
    public const SHEEPY_STATUS_INVALID = 'invalid';
    public const SHEEPY_STATUS_DONE = 'done';
    public const SHEEPY_STATUS_REFUND_REQUESTED = 'refund_requested';
    public const SHEEPY_STATUS_REFUNDED = 'refunded';
    public const SHEEPY_STATUS_ERROR = 'error';
    public const WC_STATUS_PENDING = 'wc-pending';
    public const WC_STATUS_PROCESSING = 'wc-processing';
    public const WC_STATUS_ON_HOLD = 'wc-on-hold';
    public const WC_STATUS_COMPLETED = 'wc-completed';
    public const WC_STATUS_CANCELLED = 'wc-cancelled';
    public const WC_STATUS_REFUNDED = 'wc-refunded';
    public const WC_STATUS_FAILED = 'wc-failed';

    public const SHEEPY_STATUSES_DESCRIPTIONS = [
        self::SHEEPY_STATUS_NEW => 'Awaiting Sheepy payment: "new" status',
        self::SHEEPY_STATUS_PARTIALLY_PAID => 'Sheepy partial payment received: "partially_paid" status',
        self::SHEEPY_STATUS_CONFIRMING => 'Awaiting Sheepy payment confirmations: "confirming" status',
        self::SHEEPY_STATUS_EXPIRED => 'Sheepy payment expired: "expired" status',
        self::SHEEPY_STATUS_INVALID => 'Sheepy payment is invalid: "invalid" status',
        self::SHEEPY_STATUS_DONE => 'Sheepy payment successfully received: "done" status',
        self::SHEEPY_STATUS_REFUND_REQUESTED => 'Sheepy payment refund requested: "refund_requested" status',
        self::SHEEPY_STATUS_REFUNDED => 'Sheepy payment refunded: "refunded" status',
        self::SHEEPY_STATUS_ERROR => 'Sheepy payment error: "error" status',
    ];

    public const SHEEPY_TO_WC_DEFAULT_STATUS_MATCHING = [
        self::SHEEPY_STATUS_NEW => self::WC_STATUS_PENDING,
        self::SHEEPY_STATUS_CONFIRMING => self::WC_STATUS_PROCESSING,
        self::SHEEPY_STATUS_PARTIALLY_PAID => self::WC_STATUS_ON_HOLD,
        self::SHEEPY_STATUS_EXPIRED => self::WC_STATUS_CANCELLED,
        self::SHEEPY_STATUS_INVALID => self::WC_STATUS_ON_HOLD,
        self::SHEEPY_STATUS_DONE => self::WC_STATUS_COMPLETED,
        self::SHEEPY_STATUS_REFUND_REQUESTED => self::WC_STATUS_PROCESSING,
        self::SHEEPY_STATUS_REFUNDED => self::WC_STATUS_REFUNDED,
        self::SHEEPY_STATUS_ERROR => self::WC_STATUS_FAILED,
    ];

    public const SHEEPY_PROHIBITED_COUNTRIES = [
        'AF',
        'AL',
        'BY',
        'BF',
        'CF',
        'CN',
        'CD',
        'ER',
        'GU',
        'GW',
        'HT',
        'IR',
        'IQ',
        'JM',
        'LB',
        'LY',
        'ML',
        'MM',
        'KP',
        'PA',
        'RU',
        'SN',
        'SO',
        'SS',
        'SD',
        'SY',
        'VI',
        'UG',
        'UM',
        'YE',
    ];
}
