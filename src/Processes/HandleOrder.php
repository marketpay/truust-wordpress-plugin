<?php

namespace Truust\Processes;

class HandleOrder
{
	public static function run($order)
	{
		return (new VerifyOrder())->process($order);
	}
}

class VerifyOrder extends AbstractProcessStep {
	public function process($order)
	{
		$truust_order_id = $this->get_truust_id_from_order_id($order->get_id());
		
		$api_key = truust('gateway')->settings['api_key'];
		$url = api_base_url($api_key) . '/2.0/orders/' . $truust_order_id;

		$response = $this->request($api_key, $url, 'GET');

		if ($response['data'] && $response['data']['status'] == 'PUBLISHED') {
			$order->payment_complete();

			if (truust('gateway')->valid_key && truust('gateway')->allow_shipping) {
				$this->next(new CompletePayment());
			}
		} else {
			return $this->fail('Unable to verify order');
		}

		return parent::process($order);
	}
}

class CompletePayment extends AbstractProcessStep {
	public function process($order)
	{
		$this->next(new AcceptOrder());

		$order->payment_complete();

		return parent::process($order);
	}
}

class AcceptOrder extends AbstractProcessStep {
	public function process($order)
	{
		$this->next(new InitiateShipping());

		$truust_order_id = $this->get_truust_id_from_order_id($order->get_id());
		
		$api_key = truust('gateway')->settings['api_key'];
		$url = api_base_url($api_key) . '/2.0/orders/' . $truust_order_id . '/accept';

		$response = $this->request($api_key, $url, 'POST');

		if ($response['data'] && $response['data']['status'] == 'ACCEPTED') {
			$this->next(new InitiateShipping());
		} else {
			return $this->fail('Unable to accept order');
		}

		return parent::process($order);
	}
}

class InitiateShipping extends AbstractProcessStep {
	public function process($order)
	{
		$truust_order_id = $this->get_truust_id_from_order_id($order->get_id());

		$api_key = truust('gateway')->settings['api_key'];
		$url = api_base_url($api_key) . '/2.0/shippings';
		$settings = truust('gateway')->settings;

		$response = $this->request($api_key, $url, 'POST', [
			'order_id' => $truust_order_id,
			'origin_name' => $settings['origin_name'],
			'origin_line1' => $settings['origin_address'],
			'origin_city' => $settings['origin_city'],
			'origin_state' => $settings['origin_state'],
			'origin_zip_code' => $settings['origin_zip_code'],
			'origin_country' => $settings['origin_country'],
			'destination_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
			'destination_line1' => $order->get_billing_address_1(),
			'destination_city' => $order->get_billing_city(),
			'destination_state' => $order->get_billing_state(),
			'destination_zip_code' => $order->get_billing_postcode(),
			'destination_country' => $order->get_billing_country(),
		]);

		if (isset($response['data']) && $response['data']['status'] == 'CARRIER_READY') {
			return parent::process($response['data']);
		}

		return $this->fail('Unable to initiate shipping');
	}
}