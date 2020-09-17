<?php

namespace Truust;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

use Truust\Config;
use Truust\Activator;
use Illuminate\Container\Container;

final class Lemonpay extends Container
{
	private const GATEWAY = 'Truust\WC_Gateway_Lemonpay';

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

		$this->singleton('gateway', self::GATEWAY);

		$this->bind('request', 'Truust\Request');
	}

	// ---------- methods ---------- //

	public function response_payment()
	{
		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$prefix = $this->gateway->get_return_url();

		if (strncmp($actual_link, $prefix, strlen($prefix)) !== 0) {
			return;
		} else {
			$order_id = $this->get_order_id_from_link($actual_link, $prefix);
			$order = $this->gateway->get_order($order_id);

			if (get_post_meta($order->id, '_payment_method', true) == 'lemonpay') {
				$params = explode('&', str_replace($prefix, '', $actual_link));

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
						$order->update_status('failed');
					} else {
						$this->change_mail();
						$order->update_status('processing');
					}
				} elseif ($type === 'settlor') {
					if ($status === 'denial') {
						$order->update_status('failed');

						if (wp_redirect($this->gateway->get_option('seller_confirmed_url'))) {
							exit;
						}
					} else {
						$order->update_status('completed');

						if (wp_redirect($this->gateway->get_option('seller_denied_url'))) {
							exit;
						}
					}
				}
			} else {
				return;
			}
		}
	}

	public function get_order_id_from_link($link, $prefix)
	{
		$params = explode('&', str_replace($prefix, '', $link));

		if (sizeof(explode('/', $params[0])) > 1) {
			$order_id = explode('/', $params[0])[0];
		} else {
			$order_id = str_replace('=', '', $params[0]);
		}

		return $order_id;
	}

	private function change_mail()
	{
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: LemonPay <' . get_option('admin_email') . '>'
		];
	}

	public function add_accept_order($order, $sent_to_admin, $plain_text, $email)
	{
		$meta = get_post_meta($order->id, '_payment_method', true);

		if ($meta == 'lemonpay' && $email->get_recipient() == WC()->mailer()->get_emails()['WC_Email_New_Order']->recipient) {
			$settlor_order = $this->get_settlor_order($order->id);

			echo '<form style="display: flex;" action="' . $settlor_order->settlor_shortlink . '">
				<button style="padding: .5rem 2.5rem; text-transform: uppercase; font-size: medium; border-radius: 0.25rem; background-color: #96588a; color: white; border-color: #96588a; font-weight: 500; margin: 0 auto 1rem; outline: none;">
					' . __('Accept order', config('text-domain')) . '
				</button>
			</form>';
		}
	}

	public function get_settlor_order($order_id)
	{
		global $wpdb;

		return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}lp_settlor_order WHERE order_id = {$order_id}", OBJECT)[0];
	}

	public function getWalletDetails($wallet_id)
	{
		$kit = $this->gateway->getDirectkit();

		try {
			return $kit->GetWalletDetails(['wallet' => $wallet_id]);
		} catch (\Exception $e) {
			throw $e;
		}
	}

	// ---------- setup and initialization ---------- //

	public function plugin_action_links($links)
	{
		return array_merge([
			'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=lemonpay') . '">' . __('Settings', config('text-domain')) . '</a>',
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
			self::GATEWAY
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
		wp_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=lemonpay'), 301);
	}

	public function enqueue_styles()
	{
		wp_enqueue_style('admin_page', truust('url') . 'assets/css/truust-icons.css', [], config('version'), 'all');
	}

	public function load_plugin_textdomain()
	{
		$locale = apply_filters('plugin_locale', get_locale(), config('text-domain'));
		$dir = trailingslashit(WP_LANG_DIR);

		load_textdomain(config('text-domain'), $dir . 'lemonpay/lemonpay-' . $locale . '.mo');
		load_plugin_textdomain(config('text-domain'), false, dirname(plugin_basename(__FILE__)) . '/languages/');
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
}
