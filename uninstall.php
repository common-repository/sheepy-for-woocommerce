<?php

use Sheepy\WooCommerce\SheepyGateway;

// Prevents script from being called directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require_once __DIR__ . '/vendor/autoload.php';

// Delete options
delete_option(SheepyGateway::GATEWAY_OPTIONS_KEY);

delete_option('sheepy_plugin_version');