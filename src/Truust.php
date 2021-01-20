<?php

namespace Truust;

defined('ABSPATH') || exit;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

use Truust\Config;
use Truust\Activator;
use Illuminate\Container\Container;

final class Truust extends Container
{
	public function __construct($base)
	{
		static::setInstance($this);

		$this->instance('plugin', $this);

		$this->instance('path', realpath(plugin_dir_path($base)) . DIRECTORY_SEPARATOR);
		$this->instance('path.views', $this->path . 'src' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);
		$this->instance('url', plugin_dir_url($base));

		$this->singleton('config', function () {
			return new Config($this->path . 'config.php');
		});

		$this->singleton('gateway', config('gateway'));
		$this->bind('request', config('request'));
	}

	// ---------- setup and initialization ---------- //

	public function plugin_action_links($links)
	{
		return array_merge([
			'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=truust') . '">' . __('Settings', config('text-domain')) . '</a>',
		], $links);
	}

	public function init_gateway()
	{
		if (!class_exists('WC_Payment_Gateway')) {
			return;
		}
	}

	public function add_gateway($methods)
	{
		return array_merge($methods, [
			config('gateway')
		]);
	}

	public function add_admin_menu()
	{
		add_menu_page(
			__('Truust', config('text-domain')),
			__('Truust', config('text-domain')),
			'manage_product_terms',
			config('text-domain') . 'configuration',
			null,
			'dashicons-truust-logo',
			'58'
		);

		add_submenu_page(
			config('text-domain'),
			__('Configuration ', config('text-domain')),
			__('Configuration ', config('text-domain')),
			'manage_product_terms',
			config('text-domain') . 'configuration',
			[
				$this,
				'redirect_configuration'
			]
		);
	}

	public function redirect_configuration()
	{
		wp_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=truust'), 301);
	}

	public function enqueue_styles()
	{
		wp_enqueue_style('admin_page', truust('url') . 'assets/css/truust.css', [], config('version'), 'all');
	}

	public function load_plugin_textdomain()
	{
		$locale = apply_filters('plugin_locale', get_locale(), config('text-domain'));
		$dir = trailingslashit(WP_LANG_DIR);

		load_textdomain(config('text-domain'), $dir . 'truust/truust-' . $locale . '.mo');
		load_plugin_textdomain(config('text-domain'), false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	// ---------- payment response ---------- //

	public function handle_payment_response()
	{
		// handle payment failed
		if (isset($_GET['key']) && isset($_GET['status'])) {
			$order = new \WC_Order(wc_get_order_id_by_order_key($_GET['key']));

			if ($order) {
				switch ($_GET['status']) {
					case 'failed':
						wc_add_notice(__('Payment failed.', config('text-domain')), 'error');
						$order->update_status('failed', __('Payment failed', config('text-domain')));
						break;
					default:
						break;
				}
			}
		}

		// handle payment succeeded
		if (isset($_GET['key']) && is_wc_endpoint_url( 'order-received' )) {
			$order_id = wc_get_order_id_by_order_key($_GET['key']);
			$order = new \WC_Order($order_id);

			if ($order) {
				$order->payment_complete();
				$this->accept_order($order_id);
			}
		}
	}

	public function admin_order_truust_order_id($order)
	{
		$truust_order_id = $this->get_truust_id_from_order_id($order->get_id());

		require_once(truust('path') . 'views/truust_order_id.php');
	}

	// ---------- payment complete ---------- //

	public function truust_order_status_completed($order_id)
	{
		if (truust('gateway')->valid_key) {
			$this->accept_order($order_id);
		}
	}

	public function accept_order($order_id)
	{
		$truust_order_id = $this->get_truust_id_from_order_id($order_id);

		$api_key = truust('gateway')->settings['api_key'];
		$url = api_base_url($api_key) . '/2.0/orders/' . $truust_order_id . '/accept';

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Authorization: Bearer ' . $api_key,
			],
		]);

		$response = curl_exec($curl);
		$response = remove_utf8_bom($response);
		$response = json_decode($response, true);

		curl_close($curl);

		if ($response['data'] && $response['data']['status'] == 'ACCEPTED') {
			if (truust('gateway')->allow_shipping) {
				$this->initiate_shipping($order_id);
			} else {
				$message = __('Order accepted', config('text-domain'));

				add_flash_notice($message, 'info', true);
			}
		} else {
			$error = __('There has been an error accepting this order. Please contact us at: <a href="mailto:' . config('email') . '">' . config('email') . '</a>', config('text-domain'));

			add_flash_notice($error, 'error', true);
		}
	}

	public function initiate_shipping($order_id)
	{
		$order = new \WC_Order($order_id);
		$truust_order_id = $this->get_truust_id_from_order_id($order_id);
		$api_key = truust('gateway')->settings['api_key'];
		$url = api_base_url($api_key) . '/2.0/shippings';
		$settings = truust('gateway')->settings;
		$items = [];

		foreach ($order->get_items() as $item)
		{
			$product = wc_get_product($item->get_product_id());
			$items [] = [
				'sku' => $product->get_sku(),
				'quantity' => $item->get_quantity()
			];
		}

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => [
				'order_id' => $truust_order_id,
				'origin_name' => $settings['origin_name'],
				'origin_line1' => $settings['origin_address'],
				'origin_city' => $settings['origin_city'],
				'origin_state' => $settings['origin_state'],
				'origin_zip_code' => $settings['origin_zip_code'],
				'origin_country' => 'ES', //DISABLED DUE BUG $settings['origin_country'],
				'destination_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'destination_line1' => $order->get_billing_address_1(),
                'destination_line2' => $order->get_billing_address_2(),
				'destination_city' => $order->get_billing_city(),
				'destination_state' => $order->get_billing_state(),
				'destination_zip_code' => $order->get_billing_postcode(),
				'destination_country' => $order->get_billing_country(),
				'reference_data' => json_encode([ 'items' => $items]),
			],
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Authorization: Bearer ' . $api_key,
			],
		]);

		$response = curl_exec($curl);
		$response = remove_utf8_bom($response);
		$response = json_decode($response, true);

		curl_close($curl);

		if ($response['data'] && $response['data']['status'] == 'CARRIER_READY') {
			$message = __('Order accepted and shipping initiated', config('text-domain'));

			add_flash_notice($message, 'info', true);
		} else {
			$error = __('There has been an error initializing shipping for this order. Please contact us at: <a href="mailto:' . config('email') . '">' . config('email') . '</a>', config('text-domain'));

			add_flash_notice($error, 'error', true);
		}
	}

	// ---------- activation ---------- //

	public function activate()
	{
		Activator::activate();
	}

	public function activation_check($path)
	{
		if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			return true;
		}

		if (is_plugin_active(plugin_basename($path))) {
			deactivate_plugins(plugin_basename($path));

			$message = __('Sorry, <strong>' . config('name') . '</strong> requires WooCommerce to be installed and activated first');

			add_flash_notice($message, 'error', true);

			if (isset($_GET['activate'])) {
				unset($_GET['activate']);
			}
		}

		return false;
	}

	//

	public function get_truust_id_from_order_id($order_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'truust_orders';

		$truust_order = $wpdb->get_row('SELECT * FROM ' . $table . ' WHERE order_id = ' . $order_id);

		if ($truust_order) {
			return $truust_order->truust_order_id;
		}

		return false;
	}
}
