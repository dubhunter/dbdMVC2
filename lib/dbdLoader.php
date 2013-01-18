<?php
/**
 * dbdLoader.php :: dbdLoader Class File & __autoload() Function
 *
 * @package dbdMVC
 * @version 1.8
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * dbdException class
 */
require_once("dbdException.php");
/**
 * dbdMVC File and Class Loader
 * @package dbdMVC
 * @uses dbdException
 */
class dbdLoader
{
	/**
	 * Controller directory
	 */
	const DEFAULT_CONTROLLER_DIR = "controllers";
	/**
	 * Model directory
	 */
	const DEFAULT_MODEL_DIR = "models";
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Active instance of dbdLoader Singleton
	 * @staticvar object
	 */
	private static $instance = null;
	/**
	 * Array of directories to find classes and other files
	 * @var array strings
	 */
	private $include_paths = array();
	/**
	 * Controller directory
	 * @var array
	 */
	private $controller_dirs = array();
	/**
	 * Model directory
	 * @var string
	 */
	private $model_dir = null;
	/**
	 * Initialize the loader
	 * <b>Note:</b> The cunstructer is private because this class can only
	 * be instantiated by dbdLoader::getInstance() to achieve a Singleton.
	 */
	private function __construct()
	{
		$this->init();
	}
	/**
	 * Register php.ini include_path(s), controller dir, and model dir
	 */
	private function init()
	{
		foreach (explode(":", trim(ini_get("include_path"), ":")) as $p)
			$this->register($p);
		if (count($this->controller_dirs) == 0)
			$this->controller_dirs = array(self::DEFAULT_CONTROLLER_DIR);
		if ($this->model_dir === null)
			$this->model_dir = self::DEFAULT_MODEL_DIR;
	}
	/**
	 * Add path to array and to php.ini
	 * @param string $path
	 */
	private function register($path)
	{
		if (empty($path))
			return;
		$path = realpath($path);
		if (substr($path, -1) != DBD_DS)
			$path .= DBD_DS;
		if (!in_array($path, $this->include_paths))
			$this->include_paths[] = $path;
		$ini = ini_get("include_path");
		if (!in_array($path, explode(":", $ini)))
			ini_set("include_path", $ini.":".$path);
	}
	/**#@-*/
	/**
	 * #@+
	 * @static
	 */
	/**
	 * Get the active instance or create one.
	 * @return dbdLoader object instance of dbdLoader
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
			self::$instance = new self();
		return self::$instance;
	}
	/**
	 * Add path to dbdLoader and php.ini
	 * @param string $path
	 */
	public static function addPath($path)
	{
		self::getInstance()->register($path);
	}
	/**
	 * Get all paths registered with dbdLoader
	 * @return array
	 */
	public static function getPaths()
	{
		return self::getInstance()->include_paths;
	}
	/**
	 * Set controller directory
	 * @param string $dir
	 */
	public static function setControllerDir($dir)
	{
		self::getInstance()->controller_dirs = array($dir);
	}
	/**
	 * Add a controller directory
	 * @param string $dir
	 */
	public static function addControllerDir($dir)
	{
		self::getInstance()->controller_dirs[] = $dir;
	}
	/**
	 * Set model directory
	 * @param string $dir
	 */
	public static function setModelDir($dir)
	{
		self::getInstance()->model_dir = $dir;
	}
	/**
	 * Get controller directory
	 * @return array
	 */
	public static function getControllerDirs()
	{
		return self::getInstance()->controller_dirs;
	}
	/**
	 * Get model directory
	 * @return string
	 */
	public static function getModelDir()
	{
		return self::getInstance()->model_dir;
	}
	/**
	 * Search for a file in the list of path(s).
	 * Returns path to file if found and false upon failure.
	 * @param string $file
	 * @param string $dir
	 * @return mixed
	 */
	public static function search($file, $dir = null)
	{
		if ($dir !== null)
		{
			if (strpos($dir, "/") === 0)
				self::addPath($dir);
			else
				$file = $dir.DBD_DS.$file;
		}
		foreach (array_merge(array(""), self::getPaths()) as $p)
		{
			if (file_exists($p.$file))
				return $p.$file;
		}
		return false;
	}
	/**
	 * Load a php file using require_once if not already loaded.
	 * @param string $file
	 * @return boolean
	 * @param string $dir
	 * @throws dbdException
	 */
	public static function load($file, $dir = null)
	{
		if (in_array($file, get_included_files()))
		{
			return true;
		}
		elseif (($path = self::search($file, $dir)))
		{
			require_once($path);
			return true;
		}
		throw new dbdException("File (".$file.") could not be found!\n\nPATHS:\n".implode("\n", self::getPaths()));
	}
	/**
	 * Load a class file. Calls dbdLoader::load().
	 * Called by __autoload()
	 * @param string $class
	 */
	public static function loadClass($class)
	{
		$file = $class.".php";
		try
		{
			self::load($file);
		}
		catch (dbdException $e)
		{
			$file = $class.".class.php";
			try
			{
				self::load($file);
			}
			catch (dbdException $e)
			{
				$file = str_replace("_", DBD_DS, $class).".php";
				try
				{
					self::load($file);
				}
				catch (dbdException $e)
				{
					try
					{
						dbdModel::factory($class);
					}
					catch (dbdException $e)
					{
						throw $e;
					}
				}
			}
		}
	}
	/**#@-*/
}
/**
 * Autoload classes using dbdLoader::loadClass();
 * @param string $class
 */
function __autoload($class)
{
	try
	{
		dbdLoader::loadClass($class);
	}
	catch (dbdException $e)
	{
		dbdLog("Class (".$class.") could not be autoloaded!");
	}
}
