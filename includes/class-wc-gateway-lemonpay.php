<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
* WC_Gateway_Lemonpay class.
*
* @extends WC_Payment_Gateway
*/
class WC_Gateway_Lemonpay extends WC_Payment_Gateway {

	/** @var bool Whether or not logging is enabled */
	public static $log_enabled = false;

	/** @var WC_Logger Logger instance */
	public static $log = false;

	/**
	*
	* @var string $apiEmail
	*/
	protected $apiEmail;

	/**
	*
	* @var string $apiPassword
	*/
	protected $apiPassword;

	//API CONFIGURATION
	const API_ENVIRONMENT = 'api_environment';
	const API_EMAIL = 'api_email';
	const API_PASSWORD = 'api_password';
	const API_SOURCE = 'api_source';

	const TITLE = 'title';
	const DESCRIPTION = 'description';

	const SETTLOR_EMAIL = 'seller_email';
	const SETTLOR_PREFIX = 'seller_prefix';
	const SETTLOR_PHONE = 'seller_phone';
	const SETTLOR_TAG = 'seller_tag';
	const SETTLOR_CONFIRMED_URL = 'seller_confirmed_url';
	const SETTLOR_DENIED_URL = 'seller_denied_url';
	/**
	* Constructor for the gateway.
	*/
	public function __construct() {
		$this->id = 'lemonpay';
		$this->icon = ''; // TODO
		$this->has_fields = true;
		$this->method_title = __( 'Truust', LEMONPAY_TEXT_DOMAIN );
		$this->method_description = __('Your all-in-one smart payments platform', LEMONPAY_TEXT_DOMAIN);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// API information
		$this->apiEnvironment = $this->get_option( self::API_ENVIRONMENT );
		$this->apiEmail = $this->get_option( self::API_EMAIL );
		$this->apiPassword = $this->get_option( self::API_PASSWORD );
		$this->apiPassword = $this->get_option( self::API_SOURCE );

		// Define user set variables.
		$this->title = $this->get_option( self::TITLE );
		$this->description = $this->get_option( self::DESCRIPTION );

		// Seller information
		$this->sellerEmail = $this->get_option( self::SETTLOR_EMAIL );
		$this->sellerPrefix = $this->get_option( self::SETTLOR_PREFIX );
		$this->sellerPhone = $this->get_option( self::SETTLOR_PHONE );
		$this->sellerTag = $this->get_option( self::SETTLOR_TAG );
		$this->sellerTag = $this->get_option( self::SETTLOR_CONFIRMED_URL );
		$this->sellerTag = $this->get_option( self::SETTLOR_DENIED_URL );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	* Initialise Gateway Settings Form Fields.
	*/
	public function init_form_fields() {
		$this->form_fields = include( 'settings-lemonpay.php' );
	}

	/**
	* Process the payment and return the result.
	* @param  int $order_id
	* @return array
	*/
	public function process_payment( $order_id ) {
		include_once( 'class-wc-gateway-lemonpay-request.php' );

		$order          = $this->get_order($order_id);
		$lp_request = new WC_Gateway_Lemonpay_Request( $this );

		return array(
			'result'   => 'success',
			'redirect' => $lp_request->get_request_url($order)
		);
	}

	public function get_order($order_id) {
		return wc_get_order( $order_id );
	}
}
