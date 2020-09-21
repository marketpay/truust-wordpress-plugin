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
				'buyer_confirmed_url' => config('buyer_confirmed_url'),
				'buyer_denied_url' => config('buyer_denied_url'),
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
