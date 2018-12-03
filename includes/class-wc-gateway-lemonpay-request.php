<?php

class WC_Gateway_Lemonpay_Request {

  /**
  * Pointer to gateway making the request.
  * @var WC_Gateway_Lemonpay
  */
  protected $gateway;

  /**
  * Endpoint for notification from Lemonpay.
  * @var string
  */
  protected $notify_url;

  /**
  * Constructor.
  * @param WC_Gateway_Lemonpay $gateway
  */
  public function __construct( $gateway ) {
    $this->gateway    = $gateway;
    $this->notify_url = WC()->api_request_url( 'WC_Gateway_Lemonpay' );
  }

  public function get_request_url($order) {
    //url to lemon pay api. Remove the "pre-" from the url to enable the online payment.
    $separator = "";
    $name = "";
    foreach($order->get_items() as $item) {
      $name = $name.$separator.$item['name'];
      $separator = " | ";
    }

    $data = array(
      "trustee_email" => $order->get_billing_email(),
      "trustee_prefix" => "+".prefix($order->get_billing_country()),
      "trustee_phone" => $order->get_billing_phone(),
      "settlor_email" => $this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_EMAIL),
      "settlor_prefix" => $this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_PREFIX),
      "settlor_phone" => $this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_PHONE),
      "settlor_tag" => $this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_TAG),
      "name" => $name,
      "amount" => $order->get_total(),
      "trustee_confirmed_url" => $this->gateway->get_return_url($order).'&type=trustee&status=confirmation',
      "trustee_denied_url" => $this->gateway->get_return_url($order).'&type=trustee&status=denial', // Context::getContext()->link->getModuleLink($this->module->name, 'validation', array("status" => "ko", "type" => "client"), true),
      "settlor_confirmed_url" => $this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_CONFIRMED_URL),
      "settlor_denied_url" => $this->gateway->get_option(WC_Gateway_Lemonpay::SETTLOR_DENIED_URL), // Context::getContext()->link->getModuleLink($this->module->name, 'validation', array("status" => "ko", "type" => "client"), true)
      "source" => $this->gateway->get_option(WC_Gateway_Lemonpay::API_SOURCE),
      "currency" => $order->get_currency(),
      "tag" => $order->get_id()
    );

    $response = $this->postExpress($data);
    $address = $this->postAddress($order, $response->trustee_shortlink->hash);

    $lemonpay = new LemonPay();
    $this->insert_settlor_order($lemonpay->get_order_id_from_link($response->shortlink->trustee_confirmed_url, $this->gateway->get_return_url()), $name, $response->settlor_shortlink->short_url);

    $request_url = $response->shortlink->short_url;

    if ($address->id) {
      $request_url = $request_url.'?addressId='.$address->id;
    }

    return $request_url;
  }

  private function postExpress($data)
  {
    $environment = $this->gateway->get_option(WC_Gateway_Lemonpay::API_ENVIRONMENT);
    $email = $this->gateway->get_option(WC_Gateway_Lemonpay::API_EMAIL);
    $password = $this->gateway->get_option(WC_Gateway_Lemonpay::API_PASSWORD);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 120);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);

    if ($environment == 'pro') {
      curl_setopt($curl, CURLOPT_URL, "https://api.truust.io/1.0/express");
    } else {
      curl_setopt($curl, CURLOPT_URL, "https://api-sandbox.truust.io/1.0/express");
    }

    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    curl_setopt($curl, CURLOPT_USERPWD, $email . ":" . $password);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($curl);

    if(curl_errno($curl)) {
      throw new Exception(curl_error($curl));
    } else {
      return json_decode($result);
    }
  }

  private function postAddress($order, $hash)
  {
    $address = array(
      "hash" => $hash,
      "name" => empty($order->get_shipping_first_name())? $order->get_billing_first_name().' '. $order->get_billing_last_name() : $order->get_shipping_first_name().' '.$order->get_shipping_last_name(),
      "line1" => empty($order->get_shipping_address_1())? $order->get_billing_address_1() : $order->get_shipping_address_1(),
      "line2" => empty($order->get_shipping_address_2())? $order->get_billing_address_2() : $order->get_shipping_address_2(),
      "city" => empty($order->get_shipping_city())? $order->get_billing_city() : $order->get_shipping_city(),
      "state" => empty($order->get_shipping_state())? $order->get_billing_state() : $order->get_shipping_state(),
      "zip_code" => empty($order->get_shipping_postcode())? $order->get_billing_postcode() : $order->get_shipping_postcode(),
      "country" => empty($order->get_shipping_country())? $order->get_billing_country() : $order->get_shipping_country()
    );

    $environment = $this->gateway->get_option(WC_Gateway_Lemonpay::API_ENVIRONMENT);
    $email = $this->gateway->get_option(WC_Gateway_Lemonpay::API_EMAIL);
    $password = $this->gateway->get_option(WC_Gateway_Lemonpay::API_PASSWORD);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 120);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);

    if ($environment == 'pro') {
      curl_setopt($curl, CURLOPT_URL, "https://api.truust.io/1.0/express/address");
    } else {
      curl_setopt($curl, CURLOPT_URL, "https://api-sandbox.truust.io/1.0/express/address");
    }

    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    curl_setopt($curl, CURLOPT_USERPWD, $email . ":" . $password);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($address));

    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($curl);

    if(curl_errno($curl)) {
      throw new Exception(curl_error($curl));
    } else {
      return json_decode($result);
    }
  }

  private function insert_settlor_order($order_id, $name, $link) {
    global $wpdb;

    $table = $wpdb->prefix.'lp_settlor_order';
    $wpdb->insert($table, array(
      'order_id' => $order_id,
      'settlor_shortlink' => $link,
      'products_name' => $name ,
    ));

    $my_id = $wpdb->insert_id;
  }
}
