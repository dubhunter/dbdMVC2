<?php
/**
 * dbdDispatcher.php :: dbdDispatcher Class File
 *
 * @package dbdMVC
 * @version 1.12
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * Dispatch controller actions.
 * @package dbdMVC
 * @uses dbdController
 * @uses dbdEmptyController
 * @uses dbdLoader
 * @uses dbdRouter
 */
class dbdDispatcher
{
	/**
	 * Builtin controller class prefix
	 */
	const BUILTIN_PREFIX = "dbd";
	/**
	 * Fallback controller class if none exists
	 */
	const FALLBACK_CONTROLLER = "dbdEmptyController";
	/**
	 * Error controller class if is set
	 */
	const ERROR_CONTROLLER = "dbdError";
	/**
	 * Default controller name
	 */
	const DEFAULT_CONTROLLER = "index";
	/**
	 * Default action name
	 */
	const DEFAULT_ACTION = "default";
	/**
	 * Magic action name
	 */
	const MAGIC_ACTION = "action";
	/**
	 * Action method name prefix
	 */
	const ACTION_PREFIX = "do";
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Instance of dbdRouter
	 * @var object
	 */
	private $router = null;
	/**
	 * Application directory
	 * @var string
	 */
	private $app_dir = null;
	/**
	 * Controller directory
	 * @var string
	 */
	private $controller_dirs = array();
	/**
	 * User defined fallback controller
	 * @var string
	 */
	private $fallback_controller = null;
	/**
	 * User defined controller prefix
	 * @var string
	 */
	private $controller_prefix = null;
	/**
	 * User defined controller suffix
	 * @var string
	 */
	private $controller_suffix = null;
	/**#@-*/
	/**
	 * Set router, app_dir, and controller_dir.
	 * @param dbdRouter $router
	 * @param string $app_dir
	 */
	public function __construct(dbdRouter $router = null, $app_dir = null)
	{
		$this->router = $router !== null ? $router : dbdMVC::getRouter();
		$this->app_dir = $app_dir !== null ? $app_dir : dbdMVC::getAppDir();
		$this->fallback_controller = dbdMVC::getFallbackController();
		if ($this->fallback_controller === null)
			$this->fallback_controller = self::FALLBACK_CONTROLLER;
		$this->controller_prefix = dbdMVC::getControllerPrefix();
		$this->controller_suffix = dbdMVC::getControllerSuffix();
		$this->controller_dirs = dbdLoader::getControllerDirs();
	}
	/**
	 * Dispatch actions
	 */
	public function dispatch()
	{
		$controller = $this->getController();
		$action = $this->getAction($controller);
		$controller->$action();
		$controller->autoExec();
	}
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Get controller object.
	 * Trys controller name from router, then fallback controller.
	 * @throws dbdException
	 * @return object
	 */
	private function getController()
	{
		$controller = $this->router->getController();
		if (empty($controller))
			$controller = $this->router->setController(self::DEFAULT_CONTROLLER);
		if (!preg_match("/^".self::BUILTIN_PREFIX.".+$/", $controller))
		{
			$controller = ucfirst($controller);
			$file = $controller.'.php';
			$loaded = false;
			foreach ($this->controller_dirs as $d)
			{
				if (dbdLoader::search($file, $d))
				{
					dbdLoader::load($file, $d);
					$loaded = true;
					break;
				}
				if ($this->controller_prefix !== null && dbdLoader::search($this->controller_prefix.$file, $d))
				{
					$controller = $this->controller_prefix.$controller;
					$file = $this->controller_prefix.$file;
					dbdLoader::load($file, $d);
					$loaded = true;
					break;
				}
				if ($this->controller_suffix !== null && dbdLoader::search($controller.$this->controller_suffix.'.php', $d))
				{
					$controller = $controller.$this->controller_suffix;
					$file = $controller.'.php';
					dbdLoader::load($file, $d);
					$loaded = true;
					break;
				}
			}
			if (!$loaded)
			{
				$controller = $this->fallback_controller;
				dbdLoader::loadClass($controller);
			}
		}
		if (!class_exists($controller))
			throw new dbdException("Controller (".$controller.") class could not be found!");
		$controller = new $controller($this->router, $this->app_dir);
		if ($controller instanceof dbdController)
			return $controller;
		else
			throw new dbdException("Controller (".$controller.") must extend dbdController!");
	}
	/**
	 * Get action method name.
	 * Trys controller name from router, or default action.
	 * @throws dbdException
	 * @param dbdController $controller
	 * @return string
	 */
	private function getAction(dbdController $controller)
	{
		$action = $this->router->getAction();
		if (empty($action))
		{
			$this->router->setAction(self::DEFAULT_ACTION);
			$action = $this->router->getAction();
		}
		$method = self::ACTION_PREFIX.ucfirst($action);
		if (!method_exists($controller, $method))
		{
			$magic = "__".self::ACTION_PREFIX.ucfirst(self::MAGIC_ACTION);
			if (!method_exists($controller, $magic))
			{
				$controller->noRender();
				throw new dbdException("Action (".$action.") could not be executed!");
			}
			$method = $magic;
		}
		$action = $method;
		return $action;
	}
	/**#@-*/
}
