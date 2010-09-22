<?php
/**
 * dbdJS.php :: dbdJS Class File
 *
 * @package dbdMVC
 * @version 1.8
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2009 by Don't Blink Design
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
			$this->cache_mtime = filemtime($path);
			foreach ($this->files as $f)
			{
				if (filemtime($f) > $this->cache_mtime)
					return false;
			}
			$this->files = array();
			$this->addFile($this->cache_file, DBD_CACHE_DIR);
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
			@file_put_contents($file, $this->buffer);
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
			throw new dbdException("Invalid path (".$path.")!");
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
	 * Read and close a file.
	 * <b>Note:</b> Can except a string file name or open resource.
	 * @param mixed $fp
	 */
	private function readFiles()
	{
		foreach ($this->files as $fp)
		{
			$this->buffer .= "\n/**\n * ".get_class()."(".$fp.")\n */\n";
			$this->ensureResource($fp);
			while (!feof($fp))
				$this->buffer .= fgets($fp, 4096);
			fclose($fp);
		}
	}
	/**
	 * Minify buffer.
	 */
	private function minify()
	{
		if (dbdLoader::search("jsMin.php") && class_exists("JSMin"))
			$this->buffer = JSMin::minify($this->buffer);
	}
	/**
	 * Set js headers
	 */
	private function setHeaders($cache = false)
	{
		if ($cache)
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
		header("Content-Type: text/js");
		if (function_exists("mb_strlen"))
			header("Content-Length: ".mb_strlen($this->buffer));
		return true;
	}
	/**#@-*/
	/**
	 * Check a file for existence and then
	 * add it to the array for later proccessing.
	 * @param string $file
	 * @param string $dir
	 */
	protected function addFile($file, $dir)
	{
		if (!preg_match("/^.+\.[jJ][sS]$/", $file))
			$file .= ".js";
		$path = dbdLoader::search($file, $dir);
		if ($path === false)
			throw new dbdException("Invalid file (".$file.")!");
		$this->files[] = $path;
	}
	/**
	 * Set headers, minify, and echo buffer.
	 */
	protected function output()
	{
		$cache = $this->checkCache();
		$this->readFiles();
		if (!$cache && !$this->debug)
		{
			$this->minify();
			$this->createCache();
		}
		$this->addVars();
		if ($this->setHeaders($cache))
			echo $this->buffer;
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
		$files = $this->getParam("files");
		if (!is_array($files))
			$files = array($files);
		foreach ($files as $f)
		{
			$tmp = preg_split(self::DIR_DELIM_REGEX, $f);
			$file = array_pop($tmp);
			$dir = count($tmp) ? implode(DBD_DS, $tmp).DBD_DS : null;
			$this->addFile($file, $dir);
		}
		$this->output();
	}
	/**
	 * Generate a combine url from an array of files
	 * @param array $files
	 * @param array $vars
	 * @return string
	 */
	public static function genURL($files = array(), $vars = array())
	{
		for ($i = 0; $i < count($files); $i++)
			$files[$i] = str_replace(DBD_DS, ",", $files[$i]);
		$vars['files'] = $files;
		return dbdURI::create("dbdJS", "combine", $vars);
	}
}
?>
