<?php

namespace Truust\Processes;

abstract class AbstractProcessStep implements ProcessStep
{
	private $nextStep;

	public function next(ProcessStep $step)
	{
		$this->nextStep = $step;

		return $step;
	}

	public function process($data)
	{
		if ($this->nextStep) {
			return $this->nextStep->process($data);
		}

		return [
			'success' => true,
			'data' => $data,
		];
	}

	public function fail($message)
	{
		return [
			'success' => false,
			'error' => $message,
		];
	}

	// ---------- utilities ---------- //

	public function request($api_key, $url, $type, $params = [])
	{
		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $type,
			CURLOPT_POSTFIELDS => $params,
			CURLOPT_HTTPHEADER => [
				'Accept: application/json',
				'Authorization: Bearer ' . $api_key,
			],
		]);

		$response = curl_exec($curl);

		curl_close($curl);

		$response = remove_utf8_bom($response);

		return json_decode($response, true);
	}

	public function get_truust_id_from_order_id($order_id)
	{
		global $wpdb;

		$table = $wpdb->prefix . 'truust_orders';

		$truust_order = $wpdb->get_row('SELECT * FROM ' . $table . ' WHERE order_id = ' . $order_id);

		if ($truust_order) {
			return $truust_order->truust_order_id;
		}

		return false;
	}
}