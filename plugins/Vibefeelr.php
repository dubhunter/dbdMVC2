<?php
/**
 * Will Mason (will@dontblinkdesign.com) http://about.me/willmason
 *
 * Rewritten version of VibefeelrOAuth by
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 * http://github.com/abraham/vibefeelroauth
 *
 * The first PHP Library to support OAuth (using the PECL OAuth Extension) for Vibefeelr's REST API.
 */

/**
 * DEPENDS: PECL OAuth Extension http://www.php.net/manual/en/book.oauth.php
 */

/**
 * Vibefeelr PECL OAuth class
 */
class Vibefeelr
{
	/* User Agent */
	const USERAGENT = 'VibefeelrPECL';
	/* Version */
	const VERSION = 'v0.0.1';
	/* Set up the API root URL. */
	public $host = 'https://api.vibefeelr.com/';
	/* Respons format. */
	public $format = 'json';
	/* Decode returned json data. */
	public $decode_json = true;
	/* Return decoded json data as an associative array. */
	public $assoc = true;
	/* Verify SSL Cert. */
	public $ssl_verifypeer = false;
	/* PECL OAuth Consumer Class http://www.php.net/manual/en/class.oauth.php */
	private $oauth = null;
	/**
	 * Set API URLS
	 */
	private function accessTokenURL()  { return 'https://api.vibefeelr.com/oauth/accessToken'; }
	private function authenticateURL() { return 'https://vibefeelr.com/oauth/authenticate'; }
	private function authorizeURL()    { return 'https://vibefeelr.com/oauth/authorize'; }
	private function requestTokenURL() { return 'https://api.vibefeelr.com/oauth/requestToken'; }
	/**
	 * construct VibefeelrOAuth object
	 */
	public function __construct($consumer_key, $consumer_secret, $oauth_token = null, $oauth_token_secret = null)
	{
		$this->oauth = new OAuth($consumer_key, $consumer_secret, OAUTH_SIG_METHOD_HMACSHA1);
		$this->oauth->enableDebug();
		switch ($this->ssl_verifypeer)
		{
			case true:
				$this->oauth->enableSSLChecks();
				break;
			case true:
				$this->oauth->disableSSLChecks();
				break;
		}
		if (!empty($oauth_token) && !empty($oauth_token_secret))
			$this->oauth->setToken($oauth_token, $oauth_token_secret);
	}
	/**
	 * Format and sign an OAuth / API request
	 */
	private function request($url, $parameters, $method)
	{
		if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0)
			$url = "{$this->host}{$url}.{$this->format}";
		switch ($method)
		{
			case OAUTH_HTTP_METHOD_GET:
				$this->oauth->setAuthType(OAUTH_AUTH_TYPE_URI);
				break;
			default:
				$this->oauth->setAuthType(OAUTH_AUTH_TYPE_FORM);
				break;
		}
		$this->oauth->fetch($url, $parameters, $method, array('User-Agent' => self::USERAGENT.'-'.self::VERSION));
		$response = $this->oauth->getLastResponse();
		return $response;
	}
	/**
	 * Get a request_token from Vibefeelr
	 *
	 * @returns a key/value array containing oauth_token and oauth_token_secret
	 */
	public function getRequestToken($oauth_callback = null)
	{
		$token = $this->oauth->getRequestToken($this->requestTokenURL(), $oauth_callback);
		$this->oauth->setToken($token['oauth_token'], $token['oauth_token_secret']);
		return $token;

	}
	/**
	 * Get the authorize URL
	 *
	 * @returns a string
	 */
	public function getAuthorizeURL($token, $sign_in_with_vibefeelr = true)
	{
		if (is_array($token))
		{
			$token = $token['oauth_token'];
		}
		if (empty($sign_in_with_vibefeelr))
			return $this->authorizeURL() . "?oauth_token={$token}";
		else
			 return $this->authenticateURL() . "?oauth_token={$token}";
	}
	/**
	 * Exchange request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @returns array("oauth_token" => "the-access-token",
	 *                "oauth_token_secret" => "the-access-secret",
	 *                "user_id" => "9436992",
	 *                "screen_name" => "abraham")
	 */
	public function getAccessToken($oauth_verifier = null)
	{
		$token = $this->oauth->getAccessToken($this->accessTokenURL(), null, $oauth_verifier);
		$this->oauth->setToken($token['oauth_token'], $token['oauth_token_secret']);
		return $token;
	}
//	/**
//	 * One time exchange of username and password for access token and secret.
//	 *
//	 * @returns array("oauth_token" => "the-access-token",
//	 *                "oauth_token_secret" => "the-access-secret",
//	 *                "user_id" => "9436992",
//	 *                "screen_name" => "abraham",
//	 *                "x_auth_expires" => "0")
//	 */
//	public function getXAuthToken($username, $password)
//	{
//		$parameters = array();
//		$parameters['x_auth_username'] = $username;
//		$parameters['x_auth_password'] = $password;
//		$parameters['x_auth_mode'] = 'client_auth';
//		$response = $this->request($this->accessTokenURL(), $parameters, OAUTH_HTTP_METHOD_POST);
////		dbdLog($response);
//		$token = $response;
//		return $token;
//	}
	/**
	 * GET wrapper for oAuthRequest.
	 */
	public function get($url, $parameters = array())
	{
		$response = $this->request($url, $parameters, OAUTH_HTTP_METHOD_GET);
		if ($this->format === 'json' && $this->decode_json)
			return json_decode($response, $this->assoc);
		return $response;
	}
	/**
	 * POST wrapper for oAuthRequest.
	 */
	public function post($url, $parameters = array())
	{
		$response = $this->request($url, $parameters, OAUTH_HTTP_METHOD_POST);
		if ($this->format === 'json' && $this->decode_json)
			return json_decode($response, $this->assoc);
		return $response;
	}
	/**
	 * DELETE wrapper for oAuthReqeust.
	 */
	public function delete($url, $parameters = array())
	{
		$response = $this->request($url, $parameters, OAUTH_HTTP_METHOD_DELETE);
		if ($this->format === 'json' && $this->decode_json)
			return json_decode($response, $this->assoc);
		return $response;
	}
	/**
	 * Get last response info for debugging
	 * @return array
	 */
	public function getLastResponseInfo()
	{
		return $this->oauth->getLastResponseInfo();
	}
}
?>