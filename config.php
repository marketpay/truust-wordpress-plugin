<?php

defined( 'ABSPATH' ) || exit;

return [
	'name' => 'Truust',
	'id' => 'truust',
	'description' => 'Your smart payments platform',
	'text-domain' => 'truust',
	'email' => 'hello@truust.io',
	'version' => '1.0.0',
	'image' => 'assets/images/logo.png',
	'gateway' => 'Truust\Gateway',
	'api' => [
		'sandbox' => 'https://api-sandbox.truust.io',
		'production' => 'https://api.truust.io'
	]
];
