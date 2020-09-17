<?php

namespace Truust;

class Request
{
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
			$data = $response['data'];

			$this->insert_settlor_order($data['id'], $data['name'], $data['buyer_link']);

			return $response['data']['buyer_link'];
		}

		return false;
	}

	private function insert_settlor_order($order_id, $name, $link)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'lp_settlor_order';
		$wpdb->insert($table, [
			'order_id' => $order_id,
			'settlor_shortlink' => $link,
			'products_name' => $name,
		]);

		$wpdb->insert_id;
	}

	private function remove_utf8_bom($text)
	{
		$bom = pack('H*', 'EFBBBF');

		return preg_replace("/^$bom/", '', $text);
	}
}
