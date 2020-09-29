<?php

namespace Truust;

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

		$this->singleton('gateway', config('dev.gateway'));

		$this->bind('request', 'Truust\Request');
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
			config('dev.gateway')
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
		wp_enqueue_style('admin_page', truust('url') . 'assets/css/truust-icons.css', [], config('version'), 'all');
	}

	public function load_plugin_textdomain()
	{
		$locale = apply_filters('plugin_locale', get_locale(), config('text-domain'));
		$dir = trailingslashit(WP_LANG_DIR);

		load_textdomain(config('text-domain'), $dir . 'truust/truust-' . $locale . '.mo');
		load_plugin_textdomain(config('text-domain'), false, dirname(plugin_basename(__FILE__)) . '/languages/');
	}

	// payment response
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
		if (isset($_GET['key']) && isset($_GET['order-received'])) {
			$order = new \WC_Order(wc_get_order_id_by_order_key($_GET['key']));

			if ($order) {
				$order->payment_complete();
			}
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
}
