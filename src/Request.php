<?php

namespace Truust;

class Request
{
	/**
	 * get_return_url($order) returns a url with the following format:
	 * http://localhost:8001/?page_id=8&order-received=21&key=wc_order_DIr56QeBwbcuM
	 *
	 * wc_get_checkout_url() returns a url with the following format:
	 * http://localhost:8001/?page_id=8
	 * we append '?status=failed&key=' . $order->get_order_key() to indicate the payment failed
	 *
	 */
	public function send($order)
	{
		if (!config('api_key')) {
			return;
		}

		$separator = '';
		$name = '';
		foreach ($order->get_items() as $item) {
			$name = $name . $separator . $item['name'];
			$separator = ' | ';
		}

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => config('api_url') . '/2.0/orders',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => [
				'buyer_id' => $order->get_customer_id(),
				'seller_id' => 747,
				'name' => mb_strimwidth($name, 0, 120),
				'value' => $order->get_total(),
				'tag' => $order->get_id(),
				'seller_confirmed_url' => config('seller_confirmed_url'),
				'seller_denied_url' => config('seller_denied_url'),
				'buyer_confirmed_url' => html_entity_decode(truust('gateway')->get_return_url($order)),
				'buyer_denied_url' => wc_get_checkout_url() . '&status=failed&key=' . $order->get_order_key(),
			],
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Authorization: Bearer ' . config('api_key'),
			],
		]);

		$response = curl_exec($curl);
		$response = $this->remove_utf8_bom($response);
		$response = json_decode($response, true);

		curl_close($curl);

		if (isset($response['data'])) {
			$order->update_status('pending', __($response['status_nicename'], config('text-domain')));

			return $this->create_payin($response['data']);
		}

		return false;
	}

	private function create_payin($data)
	{
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => config('api_url') . '/2.0/payins',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => [
				'order_id' => $data['id'],
				'type' => 'REDSYS_V2'
			],
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Authorization: Bearer ' . config('api_key'),
			],
		));

		$response = curl_exec($curl);

		$response = curl_exec($curl);
		$response = $this->remove_utf8_bom($response);
		$response = json_decode($response, true);

		curl_close($curl);

		if (isset($response['data']) && isset($response['data']['direct_link'])) {
			return [
				'order_id' => $data['id'],
				'order_name' => $data['name'],
				'buyer_link' => $data['buyer_link'],
				'redirect' => $response['data']['direct_link'],
			];
		}

		return false;
	}

	private function remove_utf8_bom($text)
	{
		$bom = pack('H*', 'EFBBBF');

		return preg_replace("/^$bom/", '', $text);
	}
}
