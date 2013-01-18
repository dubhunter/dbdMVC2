<?php
/**
 * dbdJS.php :: dbdJS Class File
 *
 * @package dbdMVC
 * @version 1.12
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */
/**
 * Controller class for compressing and serving js files.
 * Can be used to combine files using the doCombine action.
 * Files are combined, minified, and cached once for later use.
 * @package dbdMVC
 * @uses dbdController
 * @uses dbdException
 */
class dbdJS extends dbdController
{
	/**
	 * Js file extension pattern.
	 */
	const JS_EXT_REGEX = '/\.js$/i';
	/**
	 * JS import regular expression.
	 */
	const IMPORT_REGEX = '%^//@import [\'\"]?([a-z0-9-_\./]+\.js)[\'\"]?;%i';
	/**
	 * JS caching info file list pattern.
	 */
	const CACHE_INFO_FILES = '/^[ ]?\*[ ]?@files (.+)$/i';
	/**
	 * Directory delimiter for passing a string of files
	 */
	const DIR_DELIM_REGEX = "/[,\|]/";
	/**
	 * #@+
	 * @access private
	 */
	/**
	 * Flag to turn off minification.
	 * @var boolean
	 */
	private $debug = false;
	/**
	 * Cache file name
	 * @var string
	 */
	private $cache_file = null;
	/**
	 * Cache file last modified date
	 * @var string
	 */
	private $cache_mtime = null;
	/**
	 * List of files for proccessing
	 * @var array string
	 */
	private $files = array();
	/**
	 * List of external varables to be added as
	 * properties of the dbdJS javascript object.
	 * @var array
	 */
	private $vars = array();
	/**
	 * Output buffer that will be minified if debug is off.
	 * @var string
	 */
	private $buffer = "";
	/**
	 * Generate a cache file name based on the list of proccess files.
	 * @return string
	 */
	private function genCacheName()
	{
		return __CLASS__.".".md5(strtolower(implode(",", $this->files))).".js";
	}
	/**
	 * Check for a cache file and make sure its not outdated.
	 * @return boolean
	 */
	private function checkCache()
	{
		$this->cache_file = $this->genCacheName();
		if (($path = dbdLoader::search($this->cache_file, DBD_CACHE_DIR)) && !$this->debug)
		{
			$file = $path;
			$this->ensureResource($file);
			$files = array();
			$sprites = array();
			$info = false;
			$i = 0;
			while (!feof($file) && ($i++ == 0 || $info))
			{
				$line = fgets($file, 16384);
				if (!$info && preg_match("/^\/\*\*$/", $line))
				{
					$info = true;
				}
				elseif (preg_match(self::CACHE_INFO_FILES, $line, $tmp))
				{
					$files = explode(",", $tmp[1]);
				}
				elseif (preg_match("/^[ ]?\*\/$/", $line))
				{
					$info = false;
					break;
				}
			}
			$this->cache_mtime = filemtime($path);
			foreach ($files as $f)
			{
				if (file_exists(DBD_JS_PLUG_DIR.$f) && filemtime(DBD_JS_PLUG_DIR.$f) > $this->cache_mtime)
					return false;
				if (file_exists(DBD_JS_DIR.$f) && filemtime(DBD_JS_DIR.$f) > $this->cache_mtime)
					return false;
			}
			$this->dumpFile($file);
			return true;
		}
		return false;
	}
	/**
	 * Create cache file and dump buffer.
	 */
	private function createCache()
	{
		if (is_writable(DBD_CACHE_DIR))
		{
			$file = DBD_CACHE_DIR.$this->cache_file;
			$info = "/**\n";
			$info .= " * @files ".implode(",", $this->files)."\n";
			$info .= " */\n";
			@file_put_contents($file, $info.$this->buffer);
			$this->cache_mtime = filemtime($file);
		}
	}
	/**
	 * Parse js files for import statements and recurse when found.
	 * Dump files upon no match.
	 * @param string $file
	 * @param string $dir
	 * @throws dbdException
	 */
	private function parseImports($file, $dir)
	{
		if (!preg_match(self::JS_EXT_REGEX, $file))
			$file .= ".js";
		$path = dbdLoader::search($file, $dir);
		if ($path === false)
			throw new dbdException("Invalid file (".$file.")!");
		$this->files[] = $dir.$file;
		$this->ensureResource($path);
		$this->buffer .= "\n/* dbdJS(".$file.") */\n";
		while (!feof($path))
		{
			$this->line = fgets($path, 4096);
			if (preg_match(self::IMPORT_REGEX, $this->line, $tmp))
			{
				$this->parseImports($tmp[1], $dir);
			}
			else
			{
				$this->buffer .= $this->line;
				$this->dumpFile($path);
				break;
			}
		}
	}
	/**
	 * Ensure a resource is open and valid.
	 * @param mixed $fp
	 * @throws dbdException
	 */
	private function ensureResource(&$fp)
	{
		if (is_string($fp))
			$fp = @fopen($fp, 'r');
		if (!is_resource($fp))
			throw new dbdException(__CLASS__.": Invalid resource (".$fp.")!");
	}
	/**
	 * Add external variables to the buffer.
	 */
	private function addVars()
	{
		$vars = "\n/**\n * ".get_class()." External Variables\n */\n";
		$vars .= "var dbdJS = ".json_encode($this->vars).";";
		$this->buffer = $vars.$this->buffer;
	}
	/**
	 * Dump and close a file.
	 * <b>Note:</b> Can except a string file name or open resource.
	 * @param mixed $fp
	 */
	private function dumpFile(&$fp)
	{
		$this->ensureResource($fp);
		while (!feof($fp))
			$this->buffer .= fgets($fp, 4096);
		fclose($fp);
	}
	/**
	 * Minify buffer.
	 */
	private function minify()
	{
		if (dbdLoader::search("UglifyJS.php") && class_exists("UglifyJS") && UglifyJS::installed())
			$this->buffer = UglifyJS::uglify($this->buffer);
		elseif (dbdLoader::search("jsMin.php") && class_exists("JSMin"))
			$this->buffer = JSMin::minify($this->buffer);
	}
	/**
	 * Set js headers
	 */
	private function setHeaders()
	{
		if ($this->cache_mtime != null)
		{
			$etag = md5(serialize($this->vars));
			if (strtotime(dbdRequest::getHeader("if-modified-since")) >= $this->cache_mtime && dbdRequest::getHeader("if-none-match") == $etag)
			{
				header("HTTP/1.1 304 Not Modified");
				return false;
			}
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $this->cache_mtime)." GMT");
			header("ETag: ".$etag);
		}
		header("Content-type: text/javascript");
		return true;
	}
	/**#@-*/
	/**
	 * Set headers, minify, and echo buffer.
	 */
	protected function output($cache = false)
	{
//		$cache = $this->checkCache();
//		$this->readFiles();
		if (!$cache && !$this->debug)
		{
			$this->minify();
			$this->createCache();
		}
		$this->addVars();
		dbdOB::start();
		echo $this->buffer;
		if ($this->setHeaders())
			dbdOB::flush();
		else
			dbdOB::end();
	}
	/**
	 * Set no render, debug, and load JSMin
	 */
	protected function init()
	{
		$this->noRender();
		$this->debug = dbdMVC::debugMode(DBD_DEBUG_JS);
		if (dbdLoader::search("jsMin.php"))
			dbdLoader::load("jsMin.php");
		$this->vars = $this->getParams();
	}
	/**
	 * Alias of doGet()
	 */
	public function doDefault()
	{
		$this->doGet();
	}
	/**
	 * Serve js files...
	 */
	public function doGet()
	{
		$this->addFile($this->getParam("file"), $this->getParam("dir"));
		$this->output();
	}
	/**
	 * Serve multiple js files as one
	 */
	public function doCombine()
	{

		$this->files = $this->getParam("files");
		if (!is_array($this->files))
			$this->files = array($this->files);
		try
		{
			$cache = $this->checkCache();
			if (!$cache)
			{
				foreach ($this->files as $f)
				{
					$tmp = preg_split(self::DIR_DELIM_REGEX, $f);
					$file = array_pop($tmp);
					$dir = count($tmp) ? implode(DBD_DS, $tmp).DBD_DS : null;
					$this->parseImports($file, $dir);
				}
			}
			$this->output($cache);
		}
		catch (dbdException $e)
		{
			header("HTTP/1.1 ".$e->getCode()." ".$e->getMessage());
			dbdLog($e->getCode()." - ".$e->getMessage());
			echo $e->getCode()." - ".$e->getMessage();
		}


//		$files = $this->getParam("files");
//		if (!is_array($files))
//			$files = array($files);
//		foreach ($files as $f)
//		{
//			$tmp = preg_split(self::DIR_DELIM_REGEX, $f);
//			$file = array_pop($tmp);
//			$dir = count($tmp) ? implode(DBD_DS, $tmp).DBD_DS : null;
//			$this->addFile($file, $dir);
//		}
//		$this->output();
	}
	/**
	 * Generate a combine url from an array of files
	 * @param array $files
	 * @param array $vars
	 * @return string
	 */
	public static function genURL($files = array(), $vars = array())
	{
		if (count($files) == 1 && count($vars) == 0)
			return DBD_DS.$files[0];
		for ($i = 0; $i < count($files); $i++)
			$files[$i] = str_replace(DBD_DS, ",", $files[$i]);
		$vars['files'] = $files;
		return dbdURI::create("dbdJS", "combine", $vars);
	}
}
