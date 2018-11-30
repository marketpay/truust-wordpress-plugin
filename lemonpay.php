<?php
include( 'classes/abstract/prefix.php' );
/*
Plugin Name: Truust
Plugin URI: https://truust.io
Description: Your all-in-one smart payments platform
Version: 2.0.0
Author: Xavi Toro <xavi@volcanicinternet.com>
Author URI: http://www.volcanicinternet.com
License: GPL3
*/

if(!defined('ABSPATH')) exit; // Exit if accessed directly

final class Lemonpay {

	/**
	* @var Lemonpay The single instance of the class
	*/
	protected static $_instance = null;

	protected $name = "Your all-in-one smart payments platform";
	protected $slug = 'truust';

	/**
	* Pointer to gateway making the request.
	* @var WC_Gateway_Lemonpay
	*/
	protected $gateway;

	const DB_VERSION = '2.0.0';

	/**
	* Constructor
	*/
	public function __construct(){
		// Define constants
		$this->define_constants();

		// Check plugin requirements
		$this->check_requirements();

		register_activation_hook( __FILE__, array($this,'lp_install') );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'plugins_loaded', array( $this, 'init_gateway' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateway' ) );

		//Add menu elements
		add_action('admin_menu', array($this, 'add_admin_menu'), 57);

		//Add parse request
		add_action('parse_request', array($this, 'response_payment'), 0);

		//Override Woocommerce actions
		add_action( 'woocommerce_email_before_order_table', array($this, 'add_accept_order'), 11, 4);

		$this->load_plugin_textdomain();
	}

	public function add_accept_order($order, $sent_to_admin, $plain_text, $email) {
		if (get_post_meta($order->id, '_payment_method', true) == 'lemonpay'
		&& $email->get_recipient() == WC()->mailer()->get_emails()['WC_Email_New_Order']->recipient) {
			$settlor_order = $this->get_settlor_order($order->id);
			echo '<form style="display: flex;"
									action="'.$settlor_order->settlor_shortlink.'">
							<button style="padding: .5rem 2.5rem;
														text-transform: uppercase;
														font-size: medium;
														border-radius: 0.25rem;
														background-color: #96588a;
														color: white;
										    		border-color: #96588a;
										    		font-weight: 500;
														margin: 0 auto 1rem;
														outline: none;">
									'.__( 'Accept order', LEMONPAY_TEXT_DOMAIN ).'
							</form>
						</div>';
		}
	}

	/**
	* Add menu Lemonpay
	*/
	public function add_admin_menu(){
		add_menu_page( __( 'Truust', LEMONPAY_TEXT_DOMAIN ),__( 'Truust', LEMONPAY_TEXT_DOMAIN ), 'manage_product_terms', $this->slug. 'configuration', null, null, '58' );
		add_submenu_page($this->slug, __( 'Configuration ', LEMONPAY_TEXT_DOMAIN ), __( 'Configuration ', LEMONPAY_TEXT_DOMAIN ), 'manage_product_terms', $this->slug . 'configuration', array($this, 'redirect_configuration'));
	}

	/**
	* Init Gateway
	*/
	public function init_gateway() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Includes
		include_once( 'includes/class-wc-gateway-lemonpay.php' );
		$this->gateway = new WC_Gateway_Lemonpay();
	}

	/**
	* Load Localisation files.
	*
	* Note: the first-loaded translation file overrides any following ones if
	* the same translation is present.
	*
	* Locales found in:
	*      - WP_LANG_DIR/lemonpay/woocommerce-gateway-lemonpay-LOCALE.mo
	*      - WP_LANG_DIR/plugins/lemonpay-LOCALE.mo
	*/
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), LEMONPAY_TEXT_DOMAIN );
		$dir    = trailingslashit( WP_LANG_DIR );

		load_textdomain( LEMONPAY_TEXT_DOMAIN, $dir . 'lemonpay/lemonpay-' . $locale . '.mo' );
		load_plugin_textdomain( LEMONPAY_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	* Add the gateway to methods
	*/
	public function add_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Lemonpay';
		return $methods;
	}

	public function redirect_configuration(){
		wp_redirect(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_lemonpay' ), 301);
	}

	/**
	*
	* @param string $walletId
	* @throws Exception
	* @return Wallet
	*/
	public function getWalletDetails($walletId){

		$kit = $this->gateway->getDirectkit();

		try {
			return $kit->GetWalletDetails(array('wallet' => $walletId));
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	* Add relevant links to plugins page
	* @param  array $links
	* @return array
	*/
	public function plugin_action_links( $links ) {

		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_lemonpay' ) . '">' . __( 'Settings', LEMONPAY_TEXT_DOMAIN ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	* Main Lemonpay Instance
	*
	* Ensures only one instance of Lemonpay is loaded or can be loaded.
	*
	* @static
	* @see LP()
	* @return Lemonpay - Main instance
	*/
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	* Define Constants
	*
	* @access private
	*/
	private function define_constants() {

		$woo_version_installed = get_option('woocommerce_version');
		define( 'LEMONPAY_WOOVERSION', $woo_version_installed );
		define( 'LEMONPAY_NAME', $this->name );
		define( 'LEMONPAY_TEXT_DOMAIN', $this->slug );
	}

	/**
	* Checks that the WordPress setup meets the plugin requirements.
	*
	* @access private
	* @global string $wp_version
	* @return boolean
	*/
	private function check_requirements() {
		//global $wp_version, $woocommerce;

		require_once(ABSPATH.'/wp-admin/includes/plugin.php');

		//@TODO version compare

		if( function_exists( 'is_plugin_active' ) ) {
			if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				add_action('admin_notices', array( &$this, 'alert_woo_not_active' ) );
				return false;
			}
		}

		return true;
	}


	/**
	* Display the WooCommerce requirement notice.
	*
	* @access static
	*/
	static function alert_woo_not_active() {
		echo '<div id="message" class="error"><p>';
		echo sprintf( __('Sorry, <strong>%s</strong> requires WooCommerce to be installed and activated first. Please <a href="%s">install WooCommerce</a> first.', LEMONPAY_TEXT_DOMAIN), LEMONPAY_NAME, admin_url('plugin-install.php?tab=search&type=term&s=WooCommerce') );
		echo '</p></div>';
	}

	public function response_payment() {
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$prefix = $this->gateway->get_return_url();

		if (strncmp($actual_link, $prefix, strlen($prefix)) !== 0) {
			return;
		} else {
			$order_id = $this->get_order_id_from_link($actual_link, $prefix);
			$order = $this->gateway->get_order( $order_id );

			if (get_post_meta($order->id, '_payment_method', true) == 'lemonpay') {
				$params =  explode('&', str_replace($prefix, '', $actual_link));

				foreach ($params as $param) {
					$param_exploded = explode('=', $param);
					if ($param_exploded[0] === 'status') {
						$status = $param_exploded[1];
					} elseif ($param_exploded[0] === 'type') {
						$type = $param_exploded[1];
					}
				}

				if ($type === 'trustee') {
					if ($status === 'denial') {
						$order->update_status('failed'); // cancelled
					} else {
						$this->change_mail();
						$order->update_status('processing');
					}
				} elseif ($type === 'settlor') {
					if ($status === 'denial') {
						$order->update_status('failed'); // cancelled
						if (wp_redirect($this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_DENIED_URL))) {
							exit;
						}
					} else {
						$order->update_status('completed');
						if (wp_redirect($this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_CONFIRMED_URL))) {
							exit;
						}
					}
				}
			} else {
				return;
			}
		}
	}

	public function get_order_id_from_link($link, $prefix) {
		$params = explode('&', str_replace($prefix, '', $link));

		if (sizeof(explode('/', $params[0])) > 1) {
			$order_id = explode('/', $params[0])[0];
		} else {
			$order_id = str_replace('=', '', $params[0]);
		}

		return $order_id;
	}

	private function change_mail() {
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: LemonPay <'.get_option('admin_email').'>'
		);
	}

	public function get_settlor_order($order_id) {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lp_settlor_order WHERE order_id = {$order_id}", OBJECT )[0];
	}

	/**
	* Setup SQL
	*/

	function lp_install(){
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();
		$sql[] = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.'lp_settlor_order` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`products_name` varchar(256) NOT NULL,
			`settlor_shortlink` varchar(256) NOT NULL,
			PRIMARY KEY  (`id`)
			) ENGINE=InnoDB '.$charset_collate.';';

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			foreach($sql as $q){
				dbDelta( $q );
			}

			add_option( 'lp_db_version', self::DB_VERSION);
		}
	}

	function LP(){
		return Lemonpay::instance();
	}
	LP();
