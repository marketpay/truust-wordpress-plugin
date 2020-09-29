<?php

return [
	'name' => 'Truust',
	'id' => 'truust',
	'description' => 'Your smart payments platform',
	'text-domain' => 'truust',
	'version' => '1.0.0',
	'image' => 'assets/images/logo.png',
	'api' => [
		'sandbox' => 'https://api-sandbox.truust.io',
		'production' => 'https://api.truust.io'
	],
	
	'dev' => [
		'gateway' => 'Truust\WC_Gateway_Truust'
	]
];
