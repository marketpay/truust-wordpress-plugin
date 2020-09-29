<?php

namespace Truust;

class WC_Gateway_Truust extends \WC_Payment_Gateway
{
	public function __construct()
	{
		$this->id = config('id');
		$this->icon = truust('url') . config('image');
		$this->has_fields = true;
		$this->method_title = __(config('name'), config('text-domain'));
		$this->method_description = __(config('description'), config('text-domain'));

		$this->init_settings();
		$this->init_form_fields();

		config()->set('api_key', $this->settings['api_key']);
		config()->set('api_url', $this->base());
		config()->set('seller_confirmed_url', $this->settings['seller_confirmed_url']);
		config()->set('seller_denied_url', $this->settings['seller_denied_url']);

		$this->title = __(config('name'), config('text-domain'));
		$this->description = __($this->settings['description'], config('text-domain'));

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
	}

	public function init_form_fields()
	{
		$this->form_fields = [

			// ---------- api credentials ---------- //
			'credentials_section_heading' => [
				'title' => __('Truust API Credentials', config('text-domain')),
				'type' => 'title',
			],
			'api_key' => [
				'title' => __('API Key', config('text-domain')),
				'type' => 'text',
				'description' => __($this->envrionment_desc(), config('text-domain')),
			],

			// ---------- display settings ---------- //
			'display_section_heading' => [
				'title' => __('Display Options', config('text-domain')),
				'type' => 'title',
			],
			'description' => [
				'title' => __('Description', 'woocommerce'),
				'type' => 'textarea',
				'default' => __('You will be redirect to Truust payment page after you submit order', config('text-domain')),
				'description' => 'The customer will see this description when making a purchase',
				'css' => 'width: 400px;',
			],

			// ---------- seller information ---------- //
			'seller_section_heading' => [
				'title' => __('Seller Information', config('text-domain')),
				'type' => 'title',
				'description' => 'If you want have more than one seller, contact with us: hello@truust.io',
			],
			'email' => [
				'title' => __('Email', config('text-domain')),
				'type' => 'email',
			],
			'phone' => [
				'title' => __('Phone', config('text-domain')),
				'type' => 'text',
			],
			'seller_confirmed_url' => [
				'title' => __('Seller Confirmed URL', config('text-domain')),
				'type' => 'text',
			],
			'seller_denied_url' => [
				'title' => __('Seller Denied URL', config('text-domain')),
				'type' => 'text',
			],
		];
	}

	public function process_payment($order_id)
	{
		$order = new \WC_Order($order_id);

		$data = truust('request')->send($order);

		if ($data) {
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

	private function insert_settlor_order($order_id, $name, $link)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'lp_settlor_order';
		$wpdb->insert($table, [
			'order_id' => $order_id,
			'settlor_shortlink' => $link,
			'products_name' => $name,
		]);

		$wpdb->insert_id;
	}

	// ---------- utilities ---------- //

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

	public function base()
	{
		return preg_match('/(sk_stage_)/', $this->settings['api_key']) ? config('api.sandbox') : config('api.production');
	}
}
