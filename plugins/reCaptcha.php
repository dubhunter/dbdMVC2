<?php
/**
 * The reCAPTCHA server URL's
 */
define("RECAPTCHA_API_SERVER", "http://www.google.com/recaptcha/api");
define("RECAPTCHA_API_SECURE_SERVER", "https://www.google.com/recaptcha/api");
define("RECAPTCHA_VERIFY_SERVER", "www.google.com");

class reCaptcha
{
	/**
	 * Path to keys string file
	 * @var string constant
	 */
	const KEYS_PATH = "constant/recaptcha.inc";
	/**
	 * Instance variable set by getInstance()
	 * @var reCaptcha
	 */
	private static $instance = null;

	private $error = null;

	private function __construct()
	{
		$this->includeKeys();
	}
	/**
	 * Get the active instance of reCaptcha or create one.
	 * Key method of Singleton Pattern!!
	 * @return reCaptcha
	 * @access private
	 */
	private static function getInstance()
	{
		if (self::$instance === null)
			self::$instance = new self();
		return self::$instance;
	}
	/**
	 * Include key string file.
	 * @throws dbdException
	 * @access private
	 */
	private function includeKeys()
	{
		dbdLoader::load(self::KEYS_PATH);
		if (!(RECAPTCHA_PUBKEY && RECAPTCHA_PRIKEY))
			throw new dbdException("reCaptcha Key file could not be included. PATH=".self::KEYS_PATH);
	}

	public static function get($ssl = false)
	{
		$that = self::getInstance();
		$server = $ssl ? RECAPTCHA_API_SECURE_SERVER : RECAPTCHA_API_SERVER;
		$query = "?k=".RECAPTCHA_PUBKEY;
		if ($that->error)
			$query .= "&amp;error=".$that->error;
		$html = '<script type="text/javascript" src="'.$server.'/challenge'.$query.'"></script>';
		$html .= '<noscript><iframe src="'.$server.'/noscript'.$query.'" height="300" width="500" frameborder="0"></iframe><br/ >';
		$html .= '<textarea id="recaptcha_challenge_field" name="recaptcha_challenge_field" rows="3" cols="40"></textarea>';
		$html .= '<input type="hidden" id="recaptcha_response_field" name="recaptcha_response_field" value="manual_challenge"/></noscript>';
		return $html;
	}

	/**
	 * gets a URL where the user can sign up for reCAPTCHA. If your application
	 * has a configuration page where you enter a key, you should provide a link
	 * using this function.
	 * @param string $domain The domain where the page is hosted
	 * @param string $appname The name of your application
	 */
	public static function getUrl($domain = null, $appname = null)
	{
		return "http://recaptcha.net/api/getkey?".self::queryEncode(array('domain' => $domain, 'app' => $appname));
	}
	/**
	  * Calls an HTTP POST function to verify if the user's guess was correct
	  * @param array $extra_params an array of extra variables to post to the server
	  * @return valid
	  */
	public static function check($extra_params = array())
	{
		$that = self::getInstance();
		$remoteip = dbdMVC::getRouter()->getParam("REMOTE_HOST");
		$challenge = dbdMVC::getRouter()->getParam("recaptcha_challenge_field");
		$response = dbdMVC::getRouter()->getParam("recaptcha_response_field");
		$valid = false;
		//discard spam submissions
		if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0)
		{
	        $that->error = 'incorrect-captcha-sol';
	        return $valid;
		}
		$response = $that->post(RECAPTCHA_VERIFY_SERVER, "/verify", array(
             'privatekey' => RECAPTCHA_PRIKEY,
             'remoteip' => $remoteip,
             'challenge' => $challenge,
             'response' => $response
             ) + $extra_params);
		$answers = explode ("\n", $response[1]);
		if (trim($answers[0]) == 'true')
	        $valid = true;
		else
	        $that->error = $answers[1];
		return $valid;
	}

	private static function queryEncode($data)
	{
        $req = "";
        foreach ($data as $k => $v)
            $req .= $k . '='.urlencode(stripslashes($v)).'&';
        return substr($req, 0, strlen($req) - 1);
	}
	/**
	 * Submits an HTTP POST to a reCAPTCHA server
	 * @param string $host
	 * @param string $path
	 * @param array $data
	 * @param int port
	 * @return array response
	 */
	private function post($host, $path, $data, $port = 80)
	{
        $req = self::queryEncode($data);
        $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;
        $response = "";
        if (false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10)))
        	throw new dbdException("Could not open socket to reCaptcha server.");
        fwrite($fs, $http_request);
        while (!feof($fs))
            $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        return explode("\r\n\r\n", $response, 2);
	}
}
?>