<?php
/**
 * dbdRequest.php :: dbdRequest Class File
 *
 * @package dbdMVC
 * @version 1.3
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * HTTP Request Class.
 * Based on Zend Framework Request Class
 * @package dbdMVC
 */
class dbdRequest
{
	/**
	 * @var array
	 */
	private static $rewrites = array();
	/**
	 * HTTP request URI
	 * @access private
	 * @var string
	 */
	private $request_uri = null;
	/**
	 * PUT parameters since $_SUPER_GLOBAL's don't exist.
	 * @var array
	 */
	private $_PUT = array();
	/**
	 * PUT parameters since $_SUPER_GLOBAL's don't exist.
	 * @var array
	 */
	private $_DELETE = array();
	/**
	 * Additional parameters set at runtime since the $_SUPER_GLOBAL's seem to be read only.
	 * @var array
	 */
	private $params = array();
	/**
	 * Prepare the request object for use by setting the request uri.
	 * If a string uri is not provided, it is pulled from $_SERVER['REQUEST_URI'].
	 * @param string $uri
	 */
	public function __construct($uri = null)
	{
		if ($uri)
		{
			$this->setRequestURI($uri);
			$tmp = explode("?", $uri, 2);
			$this->set("REDIRECT_URL", $tmp[0]);
			$this->set("REDIRECT_QUERY_STRING", isset($tmp[1]) ? $tmp[1] : "");
		}
		else
		{
			$this->setRequestURI(preg_replace("/\?.*$/", "", key_exists('REDIRECT_URL', $_SERVER) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI']));
		}
		switch ($this->getHeader("REQUEST_METHOD"))
		{
			case 'PUT':
				parse_str(file_get_contents('php://input'), $this->_PUT);
				break;
			case 'DELETE':
				parse_str(file_get_contents('php://input'), $this->_DELETE);
				break;
		}
	}

	/**
	 * @param $pattern
	 * @param $replacement
	 */
	public static function addRewrite($pattern, $replacement)
	{
		self::$rewrites[$pattern] = $replacement;
	}
	/**
	 * @param $url
	 * @return mixed
	 */
	private function rewrite($url)
	{
		$rewrittenUrl = preg_replace(array_keys(self::$rewrites), array_values(self::$rewrites), $url, 1);
		if ($rewrittenUrl != $url)
		{
			$tmp = explode("?", $rewrittenUrl, 2);
			$rewrittenUrl = $tmp[0];
			if (isset($tmp[1]))
			{
				parse_str($tmp[1], $this->params);
			}
		}
		return $rewrittenUrl;
	}
	/**
	 * Set request uri
	 * @param string $uri
	 */
	public function setRequestURI($uri)
	{
		$this->request_uri = $this->rewrite($uri);
	}
	/**
	 * Get request uri
	 * @param string $uri
	 */
	public function getRequestURI()
	{
		return $this->request_uri;
	}
	/**
	 * Set parameters at runtime
	 * @param string $key
	 * @param mixed $val
	 */
	public function set($key, $val)
	{
		$this->params[$key] = $val;
	}
    /**
     * Access values contained in the superglobals as public members
     * Order of precedence: 1. GET, 2. POST, 3. PUT, 4. DELETE, 5. COOKIE, 6. SERVER, 7. ENV
     * @see http://msdn.microsoft.com/en-us/library/system.web.httprequest.item.aspx
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        switch (true)
        {
            case isset($this->params[$key]):
                return $this->params[$key];
            case ($key == 'REQUEST_URI'):
                return $this->getRequestURI();
            case isset($_GET[$key]):
                return $_GET[$key];
            case isset($_POST[$key]):
                return $_POST[$key];
            case isset($this->_PUT[$key]):
                return $this->_PUT[$key];
            case isset($this->_DELETE[$key]):
                return $this->_DELETE[$key];
            case isset($_COOKIE[$key]):
                return $_COOKIE[$key];
            case isset($_SERVER[$key]):
                return $_SERVER[$key];
            case isset($_ENV[$key]):
                return $_ENV[$key];
            case isset($_FILES[$key]):
                return $_FILES[$key];
            default:
                return null;
        }
    }
    /**
     * Check to see if a property is set
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        switch (true)
        {
            case isset($this->params[$key]):
                return true;
            case isset($_GET[$key]):
                return true;
            case isset($_POST[$key]):
                return true;
            case isset($this->_PUT[$key]):
                return true;
            case isset($this->_DELETE[$key]):
                return true;
            case isset($_COOKIE[$key]):
                return true;
            case isset($_SERVER[$key]):
                return true;
            case isset($_ENV[$key]):
                return true;
            default:
                return false;
        }
    }

    /**
     * Alias to __isset()
     * @param string $key
     * @return boolean
     */
    public function has($key)
    {
        return $this->__isset($key);
    }

    /**
     * Retrieve a member of the $_GET superglobal.
     * If no $key is passed, returns the entire $_GET array.
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getQuery($key = null, $default = null)
    {
        if (null === $key)
            return $_GET;
        return (isset($_GET[$key])) ? $_GET[$key] : $default;
    }
    /**
     * Retrieve a member of the $_POST superglobal.
     * If no $key is passed, returns the entire $_POST array.
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getPost($key = null, $default = null)
    {
        if (null === $key)
            return $_POST;
        return (isset($_POST[$key])) ? $_POST[$key] : $default;
    }
    /**
     * Retrieve a member of the $this->_PUT fake superglobal.
     * If no $key is passed, returns the entire $this->_PUT array.
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getPut($key = null, $default = null)
    {
        if (null === $key)
            return $this->_PUT;
        return (isset($this->_PUT[$key])) ? $this->_PUT[$key] : $default;
    }
    /**
     * Retrieve a member of the $this->_DELETE fake superglobal.
     * If no $key is passed, returns the entire $this->_DELETE array.
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getDelete($key = null, $default = null)
    {
        if (null === $key)
            return $this->_DELETE;
        return (isset($this->_DELETE[$key])) ? $this->_DELETE[$key] : $default;
    }
    /**
     * Retrieve a member of the $_COOKIE superglobal.
     * If no $key is passed, returns the entire $_COOKIE array.
     * @todo How to retrieve from nested arrays
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getCookie($key = null, $default = null)
    {
        if (null === $key)
            return $_COOKIE;
        return (isset($_COOKIE[$key])) ? $_COOKIE[$key] : $default;
    }
    /**
     * Retrieve a member of the $_SERVER superglobal.
     * If no $key is passed, returns the entire $_COOKIE array.
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key)
            return $_SERVER;
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }
    /**
     * Retrieve a member of the $_ENV superglobal.
     * If no $key is passed, returns the entire $_COOKIE array.
     * @param string $key
     * @param mixed $default Default value to use if key not found
     * @return mixed Returns null if key does not exist
     */
    public function getEnv($key = null, $default = null)
    {
        if (null === $key)
            return $_ENV;
        return (isset($_ENV[$key])) ? $_ENV[$key] : $default;
    }
    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     * @param string HTTP header name
     * @return string|false HTTP header value, or false if not found
     * @throws dbdException
     */
    public function getHeader($header)
    {
        if (empty($header))
            throw new dbdException("An HTTP header name is required");

        // Try to get it from the $_SERVER array first
        $temp = "HTTP_" . strtoupper(str_replace("-", "_", $header));
        if (!empty($_SERVER[$temp]))
            return $_SERVER[$temp];

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists("apache_request_headers"))
        {
            $headers = apache_request_headers();
            if (!empty($headers[$header]))
                return $headers[$header];
        }
        return false;
    }
    /**
     * Is the request a Javascript XMLHttpRequest?
     * Should work with Prototype/Script.aculo.us, possibly others.
     * @return boolean
     */
    public function isXmlHttpRequest()
    {
        return ($this->getHeader("X_REQUESTED_WITH") == "XMLHttpRequest");
    }
}
?>
