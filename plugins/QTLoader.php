<?php
/**
 * QTLoader.php :: QuickTime movie loading/embedding class
 *
 * @package dbdCommon
 * @version 1.1
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2007 by Don't Blink Design
 */
/**
 * The QTLoader class is used to effortlessly embedd QuickTime movies
 * in a (X)HTML Document.
 *
 * <b>REQUIRED ITEM:</b> qtobject.js
 *
 * <i>Simple Usage:</i> <code>
 * $QT = new QTLoader();
 * $QT->loadMovie("src=movies/movie.mov", "element_id=mainQuickTime", "width=800", "height=600");
 * </code>
 *
 * <i>Advanced Usage:</i> <code>
 * $QT = new QTLoader();
 * $QT->addMovie("src=movies/movie1.mov", "element_id=movie1", "width=800", "height=600");
 * $QT->addMovie("src=movies/movie2.mov", "element_id=movie2", "width=800", "height=600");
 * $QT->loadMovies();
 * </code>
 * @package dbdCommon
 */
class QTLoader
{
	/**#@+
	 * @access private
	 */
	/**
	 * path to SWFObject external JavaScripts library
	 * @link http://blog.deconcept.com/2005/01/26/web-standards-compliant-javascript-quicktime-detect-and-embed/
	 * @var string
	 */
	private $js_path = "common_js/qtobject.js";
	/**
	 * the quicktime plugin class id
	 * @var string
	 */
	private $class_id = "02BF25D5-8C17-4B23-BC80-D3488ABDDC6B";
	/**
	 * codebase without version number
	 * @var string
	 */
	private $codebase = "http://www.apple.com/qtactivex/qtplugin.cab";
	/**
	 * plugins page url
	 * @var string
	 */
	private $pluginspage = "http://www.apple.com/quicktime/download/";
	/**
	 * flag to be set so we write JavaScript only once
	 * @var boolean
	 */
	private $common_js_shown = false;
	private $onload = "";
	/**
	 * list of movies added my addMovie()
	 * @see function addMovie
	 * @var array
	 */
	private $movies = array();
	/**
	 * list of required attribute defaults
	 * @link http://www.w3schools.com/media/media_quicktime.asp
	 * @var array
	 */
	private $req_atts = array(
			"src" => false,
			"width" => false,
			"height" => false,
			"element_id" => false,
			"id" => 1
			);
	/**
	 * list of main attribute defaults
	 * @link http://www.w3schools.com/media/media_quicktime.asp
	 * @var array
	 */
	private $atts = array(
			"href" => false,
			"target" => false,
			"kioskmode" => false,
			"bgcolor" => false,
			"showlogo" => true,
			"targetcache" => false,
			"controller" => true,
			"autoplay" => false
			);
	/**#@-*/
	/**
	 * The purpose of the constructor is to initialize the main
	 * attribute list and then fill it with the arguments (if any).
	 * @param string $attr,... "attribute=value",... optional arguments
	 */
	public function __construct()
	{
		$args = func_get_args();
		$this->atts = $this->req_atts + $this->atts;
		$this->atts = $this->getAtts($args);
	}
	/**
	 * Fill the attribute list with the arguments (if any).
	 * @param string $attr,... "attribute=value",... optional arguments
	 */
	public function setAtts()
	{
		$args = func_get_args();
		$this->atts = $this->getAtts($args);
	}
	/**
	 * Parses attribute/value string pairs into an array and
	 * returns them combined with current attribute list.
	 * @access private
	 * @param array $argv attribute/value string pairs
	 * @return array attribute list
	 */
	private function getAtts($argv)
	{
		$arr = array();
		$ret = array();
		for ($i = 0; $i < count($argv); $i++)
		{
			list($k, $v) = explode("=", $argv[$i], 2);
			$arr[$k] = $v;
		}
		if (isset($arr['onload']))
			$this->onload = $arr['onload'];
		if (isset($arr['image']))
		{
			$arr['href'] = $arr['src'];
			$arr['src'] = $arr['image'];
		}
		foreach ($this->atts as $k => $v)
			$ret[$k] = isset($arr[$k]) ? $arr[$k] : $v;
		return $ret;
	}
	/**
	 * Checks attribute list against required attribute list.
	 * @access private
	 * @param array $atts
	 * @return mixed string key if attribute is found missing else boolean false
	 */
	private function missingReqAtt(&$atts)
	{
		foreach (array_keys($this->req_atts) as $k)
		{
			if (empty($atts[$k]))
				return $k;
		}
		return false;
	}
	/**
	 * Checks if attribute is optional.
	 * @access private
	 * @param string $att attribute name
	 * @return boolean
	 */
	private function optionalAtt($att)
	{
		if (array_key_exists($att, $this->req_atts))
			return false;
		else
			return true;
	}
	/**
	 * Builds the QTObject JavaScript calls.
	 * @access private
	 * @param array $atts
	 * @return string html code
	 */
	private function buildQTObject($atts)
	{
		$js_var = "qt".$atts['id'];
		$js_func = "qtLoadMovie".$atts['id'];
		$html = "<script type='text/javascript'>\n";
		$html .= "var ".$js_var." = new QTObject(";
		$html .= "'".(empty($atts['image']) ? $atts['src'] : $atts['image'])."',";
		$html .= "'".$atts['id']."',";
		$html .= "'".$atts['width']."',";
		$html .= "'".$atts['height']."'";
		$html .= ");\n";
		//insert the optional attributes
		foreach ($atts as $k => $v)
		{
			if ($this->optionalAtt($k) && !empty($v))
				$html .= $js_var.".addParam('".$k."', '".$v."');\n";
		}
		//write the content to the element_id
		$html .= "if (document.getElementById('".$atts['element_id']."')){".$js_var.".write('".$atts['element_id']."');".$this->onload."}else{var ".$js_var."Timer = setInterval(function(){if (document.getElementById('".$atts['element_id']."')){".$js_var.".write('".$atts['element_id']."');".$this->onload."clearTimeout(".$js_var."Timer);}}, 10);}\n";
		$html .= "</script>";
		return $html;
	}
	/**
	 * Prints include to JavaScript library.
	 */
	public function getCommonJS()
	{
		echo "<script src=\"".$this->js_path."\" type=\"text/javascript\"></script>";
		$this->common_js_shown = true;
	}
	/**
	 * Prints include to JavaScript library.
	 */
	public function getCommonJSPath()
	{
		$this->common_js_shown = true;
		return $this->js_path;
	}
	/**
	 * Adds movie arguments to list of movies to be embedded later by loadMovies().
	 * @see function loadMovies
	 * @param string $attr,... "attribute=value",... optional arguments
	 */
	public function addMovie()
	{
		$this->movies[] = func_get_args();
	}
	/**
	 * Check to see if there are any movies to load.
	 * @return boolean
	 */
	public function hasMovies()
	{
		return count($this->movies) > 0;
	}
	/**
	 * Gets movies in list created by addMovie()
	 * @see function loadMovie
	 */
	public function getMovies()
	{
		$movies = "";
		foreach ($this->movies as $m)
			$movies .= $this->getMovie($m);
		return $movies;
	}
	/**
	 * Loads movies in list created by addMovie()
	 * @see function loadMovie
	 */
	public function loadMovies()
	{
		echo $this->getMovies();
	}
	/**
	 * Gets (embbeds) quicktime movie in (X)HTML document.
	 * @param mixed $attr,... "attribute=value",... optional arguments or array $atts list
	 */
	public function getMovie()
	{
		if (!$this->common_js_shown) $this->getCommonJS();
		$args = func_get_args();
		//check to see if we we're passed one array (already pulled from func_get_args)
		if (is_array($args[0]) && count($args) == 1)
			$args = $args[0];
		//get movie specific attributes
		$atts = $this->getAtts($args);
		//throw an error if we are missing any of the mission critical atts
		if (($error = $this->missingReqAtt($atts)))
		{
			echo "ERROR: Missing required attribute (".$error.")!";
			return false;
		}
		$name = basename($atts['src']);
		$html = "<!--\n/***** START QUICKTIME MOVIE (".$name.") *****/\n-->";
		$html .= $this->buildQTObject($atts);
		$html .= "<!--\n/***** END QUICKTIME MOVIE (".$name.") *****/\n-->";
		//keep the master id up to date
		$this->atts['id']++;
		return $html;
	}
	/**
	 * Loads (embbeds) quicktime movie in (X)HTML document.
	 * @param mixed $attr,... "attribute=value",... optional arguments or array $atts list
	 */
	public function loadMovie()
	{
		echo $this->getMovie();
	}
} // end class QTLoader
?>
