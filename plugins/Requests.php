<?php

class Request {

	private $curlopts = array();
	private $retryAttempts = 0;
	private $shouldRetryRequestCallback = null;

	public function __construct($url, $method, array $opts = array()) {
		$default = array(
			'params' => array(),
			'data' => array(),
			'headers' => array(),
			'cookies' => array(),
			'files' => array(),
			'proxy' => array(),
			'auth' => false,
			'allow_redirects' => true,
			'retryAttempts' => 0,
			'shouldRetryRequestCallback' => function($response) { return false; },
			'queryStringStyle' => 'strict' // can be 'php' to do "loose" array styles in query strings
		);

		$options = array_merge($default, $opts);
		$parsed = parse_url($url);

		if (count($options['params'])) {
			if (isset($parsed['query'])) {
				$parsed['query'] .= '&' . self::buildQuery($options['params'], '', $options['queryStringStyle']);
			} else {
				$parsed['query'] = self::buildQuery($options['params'], '', $options['queryStringStyle']);
			}
		}

		$this->curlopts = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_URL => self::buildUrl($parsed),
		);

		if ($method == 'POST' || $method == 'PUT') {
			if (is_string($options['data'])) {
				$this->curlopts[CURLOPT_POSTFIELDS] = $options['data'];
			} else {
				$this->curlopts[CURLOPT_POSTFIELDS] = self::buildQuery($options['data'], '', $options['queryStringStyle']);
			}
		}

		if (isset($options['timeout'])) {
			$this->curlopts[CURLOPT_TIMEOUT] = $options['timeout'];
			$this->curlopts[CURLOPT_CONNECTTIMEOUT] = $options['timeout'];
		}

		if (isset($options['connectTimeout'])) {
			$this->curlopts[CURLOPT_CONNECTTIMEOUT] = $options['connectTimeout'];
		}

		$headers = array();

		foreach ($options['headers'] as $header => $value) {
			$headers[] = $header . ': ' . $value;
		}

		$this->curlopts[CURLOPT_HTTPHEADER] = $headers;

		if ($options['auth'] && isset($options['auth']['user']) && isset($options['auth']['pass'])) {
			$this->curlopts[CURLOPT_USERPWD] = $options['auth']['user'] . ':' . $options['auth']['pass'];
		}

		$proxyOptions = $options["proxy"];
		if (isset($proxyOptions["host"]) && isset($proxyOptions["port"])) {
			$this->curlopts[CURLOPT_PROXY] = $proxyOptions["host"];
			$this->curlopts[CURLOPT_PROXYPORT] = $proxyOptions["port"];
			if (isset($proxyOptions["userpwd"]) && isset($proxyOptions["auth"])) {
				$this->curlopts[CURLOPT_PROXYUSERPWD] = $proxyOptions["userpwd"];
				$this->curlopts[CURLOPT_PROXYAUTH] = $proxyOptions["auth"];
			}
		}

		$this->retryAttempts = $options['retryAttempts'];
		$this->shouldRetryRequestCallback = $options['shouldRetryRequestCallback'];
	}

	public function send() {
		$numberOfAttempts = 0;
		do {
			$ch = curl_init();
			curl_setopt_array($ch, $this->curlopts);
			$response = new Response(curl_exec($ch), $ch);
			if (call_user_func($this->shouldRetryRequestCallback, $response) == false) {
				return $response;
			}
		} while ($numberOfAttempts++ < $this->retryAttempts);

		return $response;
	}

	public static function buildUrl($parsed) {
		if (!isset($parsed['host'])) {
			return FALSE;
		}

		if (!isset($parsed['scheme'])) {
			$parsed['scheme'] = 'http';
		}

		$url = $parsed['scheme'] . '://';

		if (isset($parsed['user']) || isset($parsed['pass'])) {
			$user = isset($parsed['user']) ? $parsed['user'] : '';
			$pass = isset($parsed['pass']) ? $parsed['pass'] : '';
			$url .= $user . ':' . $pass . '@';
		}

		$url .= $parsed['host'];

		if (isset($parsed['port'])) {
			$url .= ':' . $parsed['port'];
		}

		if (isset($parsed['path'])) {
			$url .= $parsed['path'];
		}

		if (isset($parsed['query'])) {
			$url .= '?' . $parsed['query'];
		}

		if (isset($parsed['fragment'])) {
			$url .= '#' . $parsed['fragment'];
		}

		return $url;
	}

	/**
	 * Build the query string
	 * Uses $queryStringStyle to determine how to build the url
	 * - strict: Build a standards compliant query string without braces (can be hacked by using braces in key)
	 * - php: Build a PHP compatible query string with nested array syntax
	 *
	 * @static
	 * @param array $queryData
	 * @param string $numericPrefix
	 * @param string $queryStringStyle
	 * @return string
	 */
	public static function buildQuery(array $queryData, $numericPrefix = '', $queryStringStyle = 'strict') {
		switch ($queryStringStyle) {
			case 'php':
				$query = http_build_query($queryData, $numericPrefix);
				break;
			case 'strict':
			default:
				$query = '';
				// Loop through all of the $query_data
				foreach ($queryData as $key => $value) {
					// If the key is an int, add the numeric_prefix to the beginning
					if (is_int($key)) {
						$key = $numericPrefix . $key;
					}

					// If the value is an array, we will end up recursing
					if (is_array($value)) {
						// Loop through the values
						foreach ($value as $value2) {
							// Add an arg_separator if needed
							if ($query !== '') {
								$query .= '&';
							}
							// Recurse
							$query .= self::buildQuery(array($key => $value2), $numericPrefix, $queryStringStyle);
						}
					} else {
						// Add an arg_separator if needed
						if ($query !== '') {
							$query .= '&';
						}
						// Add the key and the urlencoded value (as a string)
						$query .= $key . '=' . urlencode((string)$value);
					}
				}
		}
		return $query;
	}
}

class Response {

	public $error = null;
	public $ok = true;
	public $text;
	public $url;
	public $code;

	public function __construct($result, $ch) {

		if ($result === false) {
			$this->ok = false;
			$this->error = curl_error($ch);
			curl_close($ch);
			return;
		}

		$this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$this->text = $result;

		if ($this->code >= 400) {
			$this->ok = false;
		}

		curl_close($ch);
	}
}

class Requests {

	/**
	 * @static
	 * @param $method
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function request($method, $url, array $opts = array()) {
		$request = new Request($url, $method, $opts);
		return $request->send();
	}

	/**
	 * @static
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function get($url, array $opts = array()) {
		return self::request('GET', $url, $opts);
	}

	/**
	 * @static
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function post($url, array $opts = array()) {
		return self::request('POST', $url, $opts);
	}

	/**
	 * @static
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function put($url, array $opts = array()) {
		return self::request('PUT', $url, $opts);
	}

	/**
	 * @static
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function delete($url, array $opts = array()) {
		return self::request('DELETE', $url, $opts);
	}

	/**
	 * @static
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function patch($url, array $opts = array()) {
		return self::request('PATCH', $url, $opts);
	}

	/**
	 * @static
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function head($url, array $opts = array()) {
		return self::request('HEAD', $url, $opts);
	}

	/**
	 * @static
	 * @param $url
	 * @param array $opts
	 * @return Response
	 */
	public static function options($url, array $opts = array()) {
		return self::request('OPTIONS', $url, $opts);
	}

	public function __call($name, $arguments) {
		return call_user_func_array(array(__CLASS__, $name), $arguments);
	}

}
