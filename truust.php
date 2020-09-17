<?php

/**
 * Plugin Name: Lemonpay 2.0
 * Plugin URI: https://truust.io
 * Description: Your smart payments platform
 * Version: 1.0.0
 * Author: Richard Stovall
 * License: GPL3
 * Requires at least: 5.4.2
 * Requires PHP: 7.2
 */

require __DIR__ . '/vendor/autoload.php';

use Truust\Lemonpay;

$lemonpay = new Lemonpay(__FILE__);

if ($lemonpay->activation_check(__FILE__)) {
	register_activation_hook(__FILE__, [$lemonpay, 'activate']);

	add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$lemonpay, 'plugin_action_links']);
	add_action('plugins_loaded', [$lemonpay, 'init_gateway'], 0);
	add_filter('woocommerce_payment_gateways', [$lemonpay, 'add_gateway']);
	add_action('admin_menu', [$lemonpay, 'add_admin_menu'], 57);
	add_action('admin_enqueue_scripts', [$lemonpay, 'enqueue_styles']);
	add_action('parse_request', [$lemonpay, 'response_payment'], 0);
	add_action('woocommerce_email_before_order_table', [$lemonpay, 'add_accept_order'], 11, 4);

	$lemonpay->load_plugin_textdomain();
}

add_action('admin_notices', 'display_flash_notices', 12);
