<?php

class NotifyrClient {

	const ENDPOINT = 'https://api.notifyr.io/publish/';

	protected $apiKey;
	protected $apiSecret;

	/**
	 * @param $apiKey
	 * @param $apiSecret
	 */
	public function __construct($apiKey, $apiSecret) {
		$this->apiKey = $apiKey;
		$this->apiSecret = $apiSecret;
	}

	/**
	 * @param $resource
	 * @param array $data
	 * @return mixed json_decoded response
	 * @throws DeveloperException
	 */
	protected function sendRequest($resource, $data = array()) {
		$opts = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
		);

		$payload = array(
			'key' => $this->apiKey,
			'secret' => $this->apiSecret,
		);

		if (!empty($data)) {
			$payload['data'] = $data;
		}

		$opts['data'] = json_encode($payload);

		$response = Requests::post(self::ENDPOINT . $resource, $opts);

		if (!$response->ok) {
			throw new DeveloperException(__CLASS__ . ' Error: ' . $response->error, $response->code);
		}

		return json_decode($response->text, true);
	}

	/**
	 * @param $channel
	 * @param $data
	 * @return mixed
	 */
	public function publish($channel, $data) {
		return $this->sendRequest($channel, $data);
	}

	/**
	 * @param $channel
	 * @param $permit
	 * @return mixed
	 */
	public function permit($channel, $permit) {
		return $this->sendRequest($channel . '/' . $permit);
	}
}
