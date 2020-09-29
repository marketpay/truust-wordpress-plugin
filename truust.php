<?php

/**
 * Plugin Name: Truust 2.0
 * Plugin URI: https://truust.io
 * Description: Your smart payments platform
 * Version: 1.0.0
 * Author: Truust <hello@truust.io>
 * License: GPL3
 * Requires at least: 5.4.2
 * Requires PHP: 7.0
 */

require __DIR__ . '/vendor/autoload.php';

use Truust\Truust;

$truust = new Truust(__FILE__);

if ($truust->activation_check(__FILE__)) {
	register_activation_hook(__FILE__, [$truust, 'activate']);

	add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$truust, 'plugin_action_links']);
	add_action('plugins_loaded', [$truust, 'init_gateway'], 0);
	add_filter('woocommerce_payment_gateways', [$truust, 'add_gateway']);
	add_action('admin_menu', [$truust, 'add_admin_menu'], 57);
	add_action('admin_enqueue_scripts', [$truust, 'enqueue_styles']);
	add_action('parse_request', [$truust, 'handle_payment_response'], 0);

	$truust->load_plugin_textdomain();
}

add_action('admin_notices', 'display_flash_notices', 12);
