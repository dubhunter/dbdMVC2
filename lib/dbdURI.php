<?php
/**
 * dbdURI.php :: dbdURI Class File
 *
 * @package dbdMVC
 * @version 1.13
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2009 by Don't Blink Design
 */

/**
 * dbdMVC URI Parsing and creating class
 * @package dbdMVC
 */
class dbdURI
{
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Active instance of dbdURI Singleton
	 * @staticvar object
	 */
	private static $instance = null;
	/**
	 * Prefix portion of URL, usually /
	 * @var string
	 */
	private $prefix = null;
	/**
	 * Controller name
	 * @var string
	 */
	private $controller = null;
	/**
	 * Action name
	 * @var string
	 */
	private $action = null;
	/**
	 * Key value pairs of parameters
	 * @var array
	 */
	private $params = array();
	/**
	 * Parameter key lists, organized by controller by action
	 * @var array
	 */
	private $position_lists = array();
	/**#@-*/
	/**
	 * #@+
	 * @static
	 */
	/**
	 * Get the active instance or create one.
	 * @return object instance of dbdURI
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
			self::$instance = new self();
		return self::$instance;
	}
	/**
	 * Determine the potential URL prefix from DBD_DOC_ROOT and real DOCUMENT_ROOT
	 * @param string $uri
	 */
	public static function determinePrefix($dir)
	{
		$that = self::getInstance();
		$that->prefix = str_replace($dir, "", DBD_DOC_ROOT);
	}
	/**
	 * Set request uri
	 * @param string $uri
	 */
	public static function set($uri)
	{
		$that = self::getInstance();
		$that->params = array();
		$parts = preg_split("/(?<![\\\])\//", str_replace($that->prefix, "", $uri), null, PREG_SPLIT_NO_EMPTY);
		$that->controller = array_shift($parts);
		$that->action = array_shift($parts);
		if (key_exists($that->controller, $that->position_lists) && key_exists($that->action, $that->position_lists[$that->controller]))
		{
			for ($i = 0; $i < count($that->position_lists[$that->controller][$that->action]); $i++)
			{
				if (isset($parts[0]))
					self::setParam(rawurldecode($that->position_lists[$that->controller][$that->action][$i]), rawurldecode(rawurldecode(array_shift($parts))));
			}
		}
		for ($i = 0; $i < count($parts); $i += 2)
		{
			if (isset($parts[$i + 1]))
				self::setParam(rawurldecode($parts[$i]), rawurldecode(rawurldecode($parts[$i + 1])));
		}
	}
	/**
	 * Set request parameter, recursively
	 * @param string $name
	 * @param mixed $value
	 */
	public static function setParam($name, $value)
	{
		$that = self::getInstance();
		if (preg_match("/^(.*)\[([^\]]*)\]$/", $name, $tmp))
		{
			if (!empty($tmp[2]))
				$v = array($tmp[2] => $value);
			else
				$v = array($value);
			self::setParam($tmp[1], $v);
		}
		elseif (is_array($value))
		{
			if (!key_exists($name, $that->params) || !is_array($that->params[$name]))
				$that->params[$name] = array();
			$that->params[$name] = array_merge_recursive($that->params[$name], $value);
		}
		else
		{
			$that->params[$name] = $value;
		}
	}
	/**
	 * Set a parameter position list for a specific controller/action.
	 * This allows for key-less parameritized urls
	 * @param string $controller
	 * @param string $action
	 * @param array $params
	 */
	public static function setPositionList($controller, $action, $params = array())
	{
		$that = self::getInstance();
		if (!key_exists($controller, $that->position_lists))
			$that->position_lists[$controller] = array();
		$that->position_lists[$controller][$action] = $params;
	}
	/**
	 * Get controller name
	 * @return string
	 */
	public static function getController()
	{
		return self::getInstance()->controller;
	}
	/**
	 * Get action name
	 * @return string
	 */
	public static function getAction()
	{
		return self::getInstance()->action;
	}
	/**
	 * Get all parameter
	 * @return array
	 */
	public static function getParams()
	{
		return self::getInstance()->params;
	}
	/**
	 * Create a new url based on the given components
	 * @param string $controller
	 * @param string $action
	 * @param associative array $args
	 * @return string
	 */
	public static function create($controller = null, $action = null, $args = array())
	{
		$that = self::getInstance();
		$uri = $that->prefix."/";
		if ($controller)
		{
			$uri .= $controller."/";
			if (empty($action))
				$action = dbdDispatcher::DEFAULT_ACTION;
			if (!(count($args) == 0 && $action == dbdDispatcher::DEFAULT_ACTION))
				$uri .= $action."/";
			if (key_exists($controller, $that->position_lists) && key_exists($action, $that->position_lists[$controller]))
			{
				for ($i = 0; $i < count($that->position_lists[$controller][$action]); $i++)
				{
					if (key_exists($that->position_lists[$controller][$action][$i], $args))
					{
						$uri .= preg_replace("/\//", "%5C/", rawurlencode($args[$that->position_lists[$controller][$action][$i]]))."/";
						unset($args[$that->position_lists[$controller][$action][$i]]);
					}
				}
			}
			$braces = false;
			foreach ($args as $k => $v)
			{
				if ($v === "" || $v === null)
					continue;
				if (is_array($v))
				{
					$braces = true;
				}
				else
				{
					$braces = false;
					$v = array($v);
				}
				foreach ($v as $k2 => $v2)
					$uri .= $k.($braces ? "%5B".(is_string($k2) ? $k2 : "")."%5D" : "")."/".rawurlencode(rawurlencode($v2))."/";
			}
		}
		return $uri;
	}
	/**
	 * Replace the given url with the new components given
	 * @param string $baseUri
	 * @param string $controller
	 * @param string $action
	 * @param associative array $args
	 * @return string
	 */
	public static function replace($baseUri = "", $controller = "", $action = "", $args = array())
	{
		if ($baseUri !== null)
			self::set($baseUri);
		if ($controller == null)
			$controller = self::getController();
		if ($action == null)
			$action = self::getAction();
		foreach ($args as $k => $v)
			self::setParam($k, $v);
		$tmp = self::getParams();
		return self::create($controller, $action, $tmp);
	}
	/**#@-*/
}
?>
