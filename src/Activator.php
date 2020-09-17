<?php

namespace Truust;

class Activator
{
	public static function activate()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = [];
		$sql[] = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'lp_settlor_order` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`products_name` varchar(256) NOT NULL,
			`settlor_shortlink` varchar(256) NOT NULL,
			PRIMARY KEY  (`id`)
		) ENGINE=InnoDB ' . $charset_collate . ';';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ($sql as $q) {
			dbDelta($q);
		}
	}
}
