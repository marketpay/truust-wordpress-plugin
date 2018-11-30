<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

/**
* Settings for Lemonpay Gateway.
*/
return array(
  'api_configuration' => array(
    'title'       => __( 'Account configuration', LEMONPAY_TEXT_DOMAIN ),
    'type'        => 'title'
  ),
  WC_Gateway_Lemonpay::API_ENVIRONMENT => array(
    'title'       => __( 'Environment', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'select',
    'default'     => 'pre',
    'options'     => array(
      'pro' => 'Production',
      'pre' => 'Sandbox'
    )
  ),
  WC_Gateway_Lemonpay::API_EMAIL => array(
    'title'       => __( 'Email', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'text',
    'description' => 'https://dashboard.truust.io/managers',
    'desc_tip'    => true
  ),
  WC_Gateway_Lemonpay::API_PASSWORD => array(
    'title'       => __( 'Password', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'password'
  ),
  WC_Gateway_Lemonpay::API_SOURCE => array(
    'title'       => __( 'Public Key', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'text',
    'description' => 'https://dashboard.truust.io/sources',
    'desc_tip'    => true
  ),
  'payment_configuration' => array(
    'title'       => __( 'Payment method display', LEMONPAY_TEXT_DOMAIN ),
    'type'        => 'title'
  ),
  WC_Gateway_Lemonpay::TITLE => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'default'     => __( 'Truust', LEMONPAY_TEXT_DOMAIN )
	),
	WC_Gateway_Lemonpay::DESCRIPTION => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'text',
		'default'     => __( 'You will be redirect to Truust payment page after you submit order.', LEMONPAY_TEXT_DOMAIN )
	),
  'seller_configuration' => array(
    'title'       => __( 'Seller information', LEMONPAY_TEXT_DOMAIN ),
    'type'        => 'title',
    'description' => 'If you want have more than one seller, contact with us: hello@truust.io',
		'desc_tip'    => true
  ),
	WC_Gateway_Lemonpay::SETTLOR_EMAIL => array(
		'title'       => __( 'Seller\'s email', LEMONPAY_TEXT_DOMAIN ),
		'type'        => 'text'
	),
  WC_Gateway_Lemonpay::SETTLOR_PREFIX => array(
    'title'       => __( 'Seller\'s prefix', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'text'
  ),
  WC_Gateway_Lemonpay::SETTLOR_PHONE => array(
    'title'       => __( 'Seller\'s phone', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'text'
  ),
  WC_Gateway_Lemonpay::SETTLOR_TAG => array(
    'title'       => __( 'Seller\'s tag', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'text'
  ),
  WC_Gateway_Lemonpay::SETTLOR_CONFIRMED_URL => array(
    'title'       => __( 'Seller\'s confirmed URL', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'text'
  ),
  WC_Gateway_Lemonpay::SETTLOR_DENIED_URL => array(
    'title'       => __( 'Seller\'s denied URL', LEMONPAY_TEXT_DOMAIN),
    'type'        => 'text'
  )
);
