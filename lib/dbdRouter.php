<?php
/**
 * dbdRouter.php :: dbdRouter Class File
 *
 * @package dbdMVC
 * @version 1.6
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * Request routing class
 * @package dbdMVC
 * @uses dbdURI
 * @uses dbdRequest
 */
class dbdRouter
{
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Instance of dbdRequest
	 * @var dbdRequest
	 */
	private $request = null;
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
	 * Base url with controller and action
	 * @var string
	 */
	private $baseUrl = null;
	/**
	 * Key value pairs of parameters
	 * @var array
	 */
	private $params = array();
	/**#@-*/
	/**
	 * Set request object, parse request uri, and build base url
	 * @param dbdRequest $request
	 */
	public function __construct(dbdRequest $request = null)
	{
		$this->request = $request !== null ? $request : dbdMVC::getRequest();
		$this->parseRequest();
		$this->buildURL();
	}
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Parse request uri
	 */
	private function parseRequest()
	{
		dbdURI::determinePrefix($this->request->get("DOCUMENT_ROOT"));
		dbdURI::set($this->request->get("REQUEST_URI"));
		$this->setController(dbdURI::getController());
		$this->setAction(dbdURI::getAction());
		foreach (dbdURI::getParams() as $k => $v)
			$this->setParam($k, $v);
	}
	/**
	 * Build base url
	 */
	private function buildURL()
	{
		$this->baseUrl = dbdURI::create($this->controller, $this->action);
	}
	/**#@-*/
	/**
	 * Set controller name
	 * @param string $controller
	 * @return string
	 */
	public function setController($controller)
	{
		$this->controller = $controller;
		$this->buildURL();
		return $this->controller;
	}
	/**
	 * Set action name
	 * @param string $action
	 * @return string
	 */
	public function setAction($action)
	{
		$this->action = $action;
		$this->buildURL();
		return $this->action;
	}
	/**
	 * Set named parameter
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function setParam($name, $value)
	{
		$this->params[$name] = $value;
		return $this->params[$name];
	}
	/**
	 * Unset named parameter
	 * @param string $name
	 */
	public function unsetParam($name)
	{
		if (key_exists($name, $this->params))
			unset($this->params[$name]);
	}
	/**
	 * Add value to array parameter
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function addParam($name, $value)
	{
		if (!is_array($value))
			$value = array($value);
		$cur = $this->getParam($name);
		if (empty($cur))
			$cur = array();
		if (!is_array($cur))
			$cur = array($cur);
		return $this->setParam($name, array_merge($cur, $value));
	}
	/**
	 * Get controller name
	 * @return string
	 */
	public function getController()
	{
		return $this->controller;
	}
	/**
	 * Get action name
	 * @return string
	 */
	public function getAction()
	{
		return $this->action;
	}
	/**
	 * Get named parameter
	 * @param string $name
	 * @return mixed
	 */
	public function getParam($name)
	{
		if (isset($this->params[$name]))
			return $this->params[$name];
		else
			return $this->request->get($name);
	}
	/**
	 * Get all paramters
	 * @return array
	 */
	public function getParams()
	{
		$params = $this->params;
		$params = array_merge_recursive($params, $this->request->getParam());
		$params = array_merge_recursive($params, $this->request->getQuery());
		$params = array_merge_recursive($params, $this->request->getPost());
		$params = array_merge_recursive($params, $this->request->getPut());
		$params = array_merge_recursive($params, $this->request->getDelete());
		return $params;
	}
	/**
	 * Get current url.
	 * If optional paramter is passed, all current paramters are included in the url.
	 * @param bollean $get_params
	 * @return string
	 */
	public function getURL($get_params = false)
	{
		if ($get_params)
			return dbdURI::create($this->getController(), $this->getAction(), $this->getParams());
		return $this->baseUrl;
	}
}
