<?php

namespace Truust;

use Truust\Traits\Customizable;

defined('ABSPATH') || exit;

class Gateway extends \WC_Payment_Gateway
{
	use Customizable;

	public $valid_key = false;

	public $allow_shipping = false;

	public function __construct()
	{
		$this->id = config('id');
		$this->icon = truust('url') . config('image');
		$this->has_fields = true;
		$this->method_title = __(config('name'), config('text-domain'));
		$this->method_description = __(config('description'), config('text-domain'));

		$this->validate();

		$this->init_form_fields();
		$this->init_settings();
		
		$this->title = __(config('name'), config('text-domain'));
		$this->description = __($this->settings['description'], config('text-domain'));

		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
	}

	// ---------- payment ---------- //

	public function process_payment($order_id)
	{
		$order = new \WC_Order($order_id);

		$data = truust('request')->send($order);

		if ($data) {
			$this->store_order($order->get_id(), $data['truust_order_id'], $data['order_name'], $data['buyer_link']);

			return [
				'result' => 'success',
				'redirect' => $data['redirect']
			];
		} else {
			return [
				'result' => 'error',
			];
		}
	}

	private function store_order($order_id, $truust_order_id, $order_name, $buyer_link)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'truust_orders';
		$wpdb->insert($table, [
			'order_id' => $order_id,
			'truust_order_id' => $truust_order_id,
			'products_name' => $order_name,
			'shortlink' => $buyer_link,
		]);

		$wpdb->insert_id;
	}

	// ---------- validation ---------- //

	public function validate()
	{
		global $wpdb;

		$key = $this->get_option('api_key');
		$table = $wpdb->prefix . 'truust_customers';
		$isTruncated = truust('request')->truncateTable($table);
		if ($key && $isTruncated) {
			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_URL => $this->base_url($key) . '/2.0/accounts/me',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'GET',
				CURLOPT_HTTPHEADER => [
					'Accept: application/json',
					'Authorization: Bearer ' . $key,
				],
			]);

			$response = curl_exec($curl);
			$response = remove_utf8_bom($response);
			$response = json_decode($response, true);

			curl_close($curl);

			if (isset($response['data'])) {
				$data = $response['data'];

				$this->valid_key = true;
				$this->allow_shipping = $data['allow_shipping'];

				return true;
			}

			$this->valid_key = false;
			$this->allow_shipping = false;
	
			return false;
		}

		$this->valid_key = false;
		$this->allow_shipping = false;

		return false;
	}

	// ---------- setup ---------- //

	public function enqueue_scripts()
	{
		wp_enqueue_script(config('text-domain') . '_settings', truust('url') . 'assets/js/truust.js', ['jquery'], config('version'), false);
	}

	// ---------- utilities ---------- //

	public function base_url($key)
	{
		return preg_match('/(sk_stage_)/', $key) ? config('api.sandbox') : config('api.production');
	}

	public function envrionment_desc()
	{
		if (preg_match('/(sk_stage_)/', $this->settings['api_key'])) {
			return 'Key is for use in a sandbox envrionment';
		} else if (preg_match('/(sk_production_)/', $this->settings['api_key'])) {
			return 'Key is for use in a production envrionment';
		} else {
			return 'Enter your Truust API Key';
		}
	}
}
