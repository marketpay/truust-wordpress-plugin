<?php

defined( 'ABSPATH' ) || exit;

return [
	'name' => 'Truust',
	'id' => 'truust',
	'description' => 'Your smart payments platform',
	'text-domain' => 'truust',
	'email' => 'hello@truust.io',
	'version' => '2.2.0',
	'image' => 'assets/images/logo.png',
	'gateway' => 'Truust\Gateway',
	'request' => 'Truust\Request',
	'api' => [
		'sandbox' => 'https://api-sandbox.truust.io',
		'production' => 'https://api.truust.io'
	]
];
