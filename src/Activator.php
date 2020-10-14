<?php

namespace Truust;

defined( 'ABSPATH' ) || exit;

class Activator
{
	public static function activate()
	{
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		self::truust_customers_table($wpdb);
		self::truust_orders_table($wpdb);
	}

	private static function truust_customers_table($wpdb)
	{
		$charset_collate = $wpdb->get_charset_collate();

		$sql = [];
		$sql[] = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'truust_customers` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`email` varchar(256) NOT NULL,
			`truust_customer_id` int(11) NOT NULL,
			PRIMARY KEY  (`id`)
		) ENGINE=InnoDB ' . $charset_collate . ';';

		foreach ($sql as $q) {
			dbDelta($q);
		}
	}

	private static function truust_orders_table($wpdb)
	{
		$charset_collate = $wpdb->get_charset_collate();

		$sql = [];
		$sql[] = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'truust_orders` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`truust_order_id` int(11) NOT NULL,
			`products_name` varchar(256) NOT NULL,
			`shortlink` varchar(256) NOT NULL,
			PRIMARY KEY  (`id`)
		) ENGINE=InnoDB ' . $charset_collate . ';';

		foreach ($sql as $q) {
			dbDelta($q);
		}
	}
}
