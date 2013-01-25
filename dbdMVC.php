<?php
/**
 * dbdMVC.php :: dbdMVC Include File & Front Controller Class
 *
 * dbdMVC version 2.2.0
 * Copyright (c) 2006-2011 by Don't Blink Design
 * http://dbdmvc.com
 *
 * dbdMVC is a light weight model-view-controller framework.
 *
 * dbdMVC is released under the terms of the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package dbdMVC
 * @version 2.2.0
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 * @license http://www.gnu.org/copyleft/lesser.html
 * @todo Create readme and update dbdMVC description
 */

/**
 * Shorthand for DIRECTORY_SEPARATOR
 */
define("DBD_DS", DIRECTORY_SEPARATOR);
/**
 * dbdMVC base directory
 */
if (!defined("DBD_MVC_DIR"))
	define("DBD_MVC_DIR", dirname(__FILE__).DBD_DS);
/**
 * Smarty package directory
 */
define("DBD_SMARTY_DIR", DBD_MVC_DIR."Smarty-2.6.26".DBD_DS."libs".DBD_DS);
/**
 * Smarty add0n plug-in directory directory (used to avoid disrupting standard plug-in dir)
 */
define("DBD_SMARTY_PLUG_DIR", DBD_SMARTY_DIR.DBD_DS."plugins_dbd".DBD_DS);
/**
 * FirePHP core directory
 */
define("DBD_FIREPHP_DIR", DBD_MVC_DIR."FirePHPCore-0.3.2".DBD_DS."lib".DBD_DS);
/**
 * dbdMVC plug-in directory
 */
define("DBD_PLUG_DIR", DBD_MVC_DIR."plugins".DBD_DS);
/**
 * dbdMVC built in template directory
 */
define("DBD_TPL_DIR", DBD_MVC_DIR."templates".DBD_DS);
/**
 * dbdMVC javascript plug-in directory
 */
define("DBD_JS_PLUG_DIR", DBD_MVC_DIR."javascript".DBD_DS);
/**
 * dbdMVC views directory
 */
define("DBD_VIEW_DIR", DBD_APP_DIR."views".DBD_DS);
/**
 * dbdMVC app specific stylesheet directory
 */
define("DBD_STYLE_DIR", DBD_VIEW_DIR."stylesheets".DBD_DS);
/**
 * dbdMVC app specific javascript directory
 */
define("DBD_JS_DIR", DBD_APP_DIR."javascript".DBD_DS);
/**
 * dbdMVC asset directory
 */
define("DBD_ASSET_DIR", DBD_APP_DIR."assets".DBD_DS);
/**
 * dbdMVC asset download directory
 */
define("DBD_ASSET_DL_DIR", DBD_ASSET_DIR."downloads".DBD_DS);
/**
 * dbdMVC cache directory
 */
define("DBD_CACHE_DIR", DBD_APP_DIR."cache".DBD_DS);
/**
 * Are we running in CLI mode?
 */
define("DBD_MVC_CLI", php_sapi_name() == "cli");
/**
 * Turn on all debugging
 */
define("DBD_DEBUG_ALL", 255);
/**
 * Turn on all debugging
 */
define("DBD_DEBUG_DB", 1);
define("DBD_DEBUG_HTML", 2);
define("DBD_DEBUG_JS", 4);
define("DBD_DEBUG_CSS", 8);
/**
 * error_log function that handles complex types too
 */
if (!function_exists("dbdLog"))
{
//	require_once(DBD_FIREPHP_DIR."FirePHP.class.php");
	function dbdLog($msg = "", $type = null, $dest = null)
	{
//		$firephp = FirePHP::getInstance(true);
//		if (dbdMVC::PHPisExposed())
//			$firephp->log($msg, "dbdLog");
		if (is_array($msg) || is_object($msg))
			$msg = print_r($msg, 1);
		if ($dest === null)
			$dest = dbdMVC::getErrorLog();
		if (file_exists($dest) && is_writeable($dest))
		{
			if ($type === null)
				$type = 3;
			$msg = date("[d-M-Y H:i:s] ").$msg."\n";
		}
		error_log($msg, $type, $dest);
	}
}
/**
 * After the dbdLoader is included, all further dbdMVC includes use dbdLoader::load() or __autoload()
 */
require_once("lib/dbdLoader.php");
/**
 * Add all of the dbdMVC directories to dbdLoader, where there are also ini_set() to the php_include_path
 */
dbdLoader::addPath(DBD_MVC_DIR);
dbdLoader::addPath(DBD_MVC_DIR."lib");
dbdLoader::addPath(DBD_SMARTY_DIR);
dbdLoader::addPath(DBD_PLUG_DIR);
dbdLoader::addPath(DBD_JS_PLUG_DIR);
dbdLoader::addPath(DBD_JS_DIR);
dbdLoader::addPath(DBD_STYLE_DIR);
//dbdLoader::addPath(DBD_ASSET_DIR);

/**
 * dbdMVC Front Controller Class
 * @package dbdMVC
 * @uses dbdDispatcher
 * @uses dbdLoader
 * @uses dbdRequest
 * @uses dbdRouter
 */
class dbdMVC
{
	/**
	 * Current version number
	 * <b>Note:</b> May not match file @version number
	 */
	const VERSION = "2.2.0";
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Active instance of dbdMVC Singleton
	 * @staticvar object
	 */
	private static $instance = null;
	/**
	 * Start time of dbdApp, from microtime()
	 * @staticvar float
	 */
	private static $start = null;
	/**
	 * Allow phpinfo() via dbdInfo controller
	 * @staticvar boolean
	 */
	private static $expose_php_info = null;
	/**
	 * Current debug mode.
	 * @var integer
	 */
	private $debug_mode = 0;
	/**
	 * Name of dbdApp
	 * @var string
	 */
	private $app_name = null;
	/**
	 * Path to dbdApp files (models, views, and controllers dirs)
	 * @var string
	 */
	private $app_dir = null;
	/**
	 * Fallback/Default controller (if left null, the dbdDispatcher::FALLBACK_CONTROLLER will be used)
	 * @var string
	 */
	private $fallback_controller = null;
	/**
	 * Error/Default controller (if left null, the dbdDispatcher::ERROR_CONTROLLER will be used)
	 * @var string
	 */
	private $error_controller = null;
	/**
	 * Controller prefix that can be used on controller files/classes (even if set, they are optional)
	 * @var string
	 */
	private $controller_prefix = null;
	/**
	 * Controller suffix that can be used on controller files/classes (even if set, they are optional)
	 * @var string
	 */
	private $controller_suffix = null;
	/**
	 * Error log file (if left null, system default will be used)
	 * @var string
	 */
	private $error_log = null;
	/**
	 * Instance of dbdRequest
	 * @var object dbdRequest
	 */
	private $request = null;
	/**
	 * Instance of dbdRouter
	 * @var object dbdRouter
	 */
	private $router = null;
	/**
	 * Instance of dbdDispatcher
	 * @var object dbdDispatcher
	 */
	private $dispatcher = null;
	/**
	 * Constructer sets exception handler and the default app_name.
	 * <b>Note:</b> The cunstructer is private because this class can only
	 * be instantiated by dbdMVC::getInstance() to achieve a Singleton.
	 */
	private function __construct()
	{
		ob_start();
		set_exception_handler(array("dbdMVC", "e"));
		$this->app_name = "dbdMVC v".self::VERSION;
	}
	/**
	 * Prepare for dispatch by setting up the dbdDispatcher
	 * and by adding the application specific paths to dbdLoader.
	 */
	private function init()
	{
		$this->request = new dbdRequest((DBD_MVC_CLI ? $this->genCLIURI() : null));
		$this->router = new dbdRouter();
		$this->dispatcher = new dbdDispatcher();
		dbdLoader::addPath($this->app_dir);
		foreach (dbdLoader::getControllerDirs() as $d)
		{
			if (substr($d, 0, 1) != DBD_DS)
				$d = $this->app_dir.$d;
			dbdLoader::addPath($d);
		}
		dbdLoader::addPath($this->app_dir.dbdLoader::getModelDir());
	}
	/**
	 * Dispatch dbdDispatcher.
	 */
	private function dispatch()
	{
		$this->dispatcher->dispatch();
	}
	/**
	 * Generate a URI from the Command Line Arguments.
	 * <b>Usage:</b> dbdcli -c controller -a action --param=value
	 */
	private function genCLIURI()
	{
		$c = $a = null;
		$p = array();
		$argv = $_SERVER['argv'];
		for ($i = 1; $i < $_SERVER['argc']; $i++)
		{
			if (strpos($argv[$i], '=') !== false && strpos($argv[$i], '--') !== false)
			{
				list($k, $v) = explode('=', $argv[$i], 2);
			}
			else
			{
				$k = $argv[$i];
				$v = isset($argv[$i + 1]) ? $argv[++$i] : null;
			}

			switch ($k)
			{
				case '-c':
				case '--controller':
					$c = $v;
					break;
				case '-a':
				case '--action':
					$a = $v;
					break;
				default:
					$p[ltrim($k, '-')] = $v;
					break;
			}
		}
		return dbdURI::create($c, $a, $p);
	}
    /**#@-*/
	/**
	 * #@+
	 * dbdMVC interface methods
	 * @static
	 */
	/**
	 * Set application name property.
	 * @param string $app_name
	 */
	public static function setAppName($app_name)
	{
		self::getInstance()->app_name = $app_name;
	}
	/**
	 * Set fallback_controller property.
	 * @param string $fallback
	 */
	public static function setFallbackController($fallback)
	{
		self::getInstance()->fallback_controller = $fallback;
	}
	/**
	 * Set error_controller property.
	 * @param string $error
	 */
	public static function setErrorController($error)
	{
		self::getInstance()->error_controller = $error;
	}
	/**
	 * Set controller_prefix property.
	 * @param string $prefix
	 */
	public static function setControllerPrefix($prefix)
	{
		self::getInstance()->controller_prefix = $prefix;
	}
	/**
	 * Set controller_suffix property.
	 * @param string $suffix
	 */
	public static function setControllerSuffix($suffix)
	{
		self::getInstance()->controller_suffix = $suffix;
	}
	/**
	 * Set error_log property.
	 * @param string $path
	 */
	public static function setErrorLog($path)
	{
		self::getInstance()->error_log = $path;
	}
	/**
	 * Set application directory property.
	 * <b>Note:</b> Accepts paths with or without trailing slash.
	 * @param string $app_dir
	 */
	public static function setAppDir($app_dir)
	{
		if (substr($app_dir, -1) != DBD_DS)
			$app_dir .= DBD_DS;
		self::getInstance()->app_dir = $app_dir;
	}
	/**
	 * Set debug mode.
	 * @param integer $mode
	 */
	public static function setDebugMode($mode)
	{
		self::getInstance()->debug_mode = $mode;
	}
	/**
	 * Get the active instance of dbdMVC or create one.
	 * @return dbdMVC
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
			self::$instance = new self();
		return self::$instance;
	}
	/**
	 * Default ecpetion handler
	 * @param Exception $e
	 */
	public static function e(Exception $e)
	{
		dbdLog($e->getMessage());
		try
		{
			if (($c = self::getErrorController()) === null)
				$c = dbdDispatcher::ERROR_CONTROLLER;
			$uri = dbdURI::create($c, "default", array("code" => $e->getCode(), "msg" => urlencode($e->getMessage()), "trace" => urlencode($e->__toString())));
			$that = self::getInstance();
			$that->dispatcher = new dbdDispatcher(new dbdRouter(new dbdRequest($uri)));
			$that->dispatch();
		}
		catch (dbdException $e2)
		{
			dbdLog($e2->__toString());
			echo "<h1>".$e->getCode()." - ".$e->getMessage()."</h1>";
		}
	}
	/**
	 * Run dbdMVC on the given dbdApp dir.
	 * This is the main method of dbdMVC.
	 * @param string $app_dir
	 * @param string $app_name
	 */
	public static function run($app_dir, $app_name = null, $fallback_controller = null, $error_controller = null, $error_log = null, $debug_mode = null)
	{
		header('X-Powered-By: dbdMVC/'.self::VERSION);
		self::$start = microtime(true);
		$that = self::getInstance();
		if ($app_name !== null)
			self::setAppName($app_name);
		if ($fallback_controller !== null)
			self::setFallbackController($fallback_controller);
		if ($error_controller !== null)
			self::setErrorController($error_controller);
		if ($error_log !== null)
			self::setErrorLog($error_log);
		if ($debug_mode !== null)
			self::setDebugMode($debug_mode);
		$that->setAppDir($app_dir);
		$that->init();
		$that->dispatch();
	}
	/**
	 * Get application name.
	 * @return string app_name
	 */
	public static function getAppName()
	{
		return self::getInstance()->app_name;
	}
	/**
	 * Get application directory.
	 * @return string app_dir
	 */
	public static function getAppDir()
	{
		return self::getInstance()->app_dir;
	}
	/**
	 * Get fallback controller name.
	 * @return string fallback_controller
	 */
	public static function getFallbackController()
	{
		return self::getInstance()->fallback_controller;
	}
	/**
	 * Get error controller name.
	 * @return string error_controller
	 */
	public static function getErrorController()
	{
		return self::getInstance()->error_controller;
	}
	/**
	 * Set controller prefix.
	 * @return string controller_prefix
	 */
	public static function getControllerPrefix()
	{
		return self::getInstance()->controller_prefix;
	}
	/**
	 * Set controller suffix.
	 * @return string controller_suffix
	 */
	public static function getControllerSuffix()
	{
		return self::getInstance()->controller_suffix;
	}
	/**
	 * Return the error_log file name.
	 * @return float
	 */
	public static function getErrorLog()
	{
		return self::getInstance()->error_log === null ? ini_get("error_log") : self::getInstance()->error_log;
	}
	/**
	 * Get the instance of dbdRequest.
	 * @return dbdRequest
	 */
	public static function getRequest()
	{
		return self::getInstance()->request;
	}
	/**
	 * Get the instance of dbdRouter.
	 * @return dbdRouter
	 */
	public static function getRouter()
	{
		return self::getInstance()->router;
	}
	/**
	 * Calculate execution time of the application and return.
	 * @return float
	 */
	public static function getExecutionTime()
	{
		return round(microtime(true) - self::$start, 4)." seconds";
	}
	/**
	 * Log execution time of the application to the error_log.
	 * @throws dbdException
	 */
	public static function logExecutionTime()
	{
		if (self::$start === null)
			throw new dbdException("dbdMVC::run() must be called prior to calling dbdMVC::logExecutionTime()!");
		error_log("execution time: ".self::getExecutionTime(), 0);
	}
	/**
	 * Check if debug mode is set.
	 * @return boolean
	 */
	public static function debugMode($mode)
	{
		return (self::getInstance()->debug_mode & $mode) === $mode ? true : false;
	}
	/**
	 * Allow phpinfo() display via dbdInfo.
	 * <b>WARNING:</b> Allowing system information to be view by the public
	 * can be a major security risk. This should be used with caution for development only!
	 * @see dbdInfo
	 */
	public static function exposePHPInfo()
	{
		self::$expose_php_info = true;
	}
	/**
	 * Check if phpinfo display is allowed.
	 * @return boolean
	 */
	public static function PHPisExposed()
	{
		return (self::$expose_php_info === true);
	}
    /**#@-*/
}