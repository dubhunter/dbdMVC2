<?php
/**
 * FlashLoaderSWFO.php :: Flash movie loading/embedding class
 *
 * @package dbdCommon
 * @version 1.3
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2007 by Don't Blink Design
 */
/**
 * The FlashLoaderSWFO class is used to effortlessly embedd Flash movies
 * in a (X)HTML Document.
 *
 * <b>REQUIRED ITEM:</b> SWFObject.js
 *
 * <i>Simple Usage:</i> <code>
 * $FL = new FlashLoaderSWFO("version=6,0,29,0", "quality=high");
 * $FL->loadMovie("src=swfs/movie.swf", "element_id=mainFlash", "width=800", "height=600");
 * </code>
 *
 * <i>Advanced Usage:</i> <code>
 * $FL = new FlashLoaderSWFO("version=6,0,29,0", "quality=high");
 * $FL->addMovie("src=swfs/movie.swf", "element_id=mainFlash", "width=800", "height=600");
 * $FL->loadMovies();
 * </code>
 * @package dbdCommon
 */
class FlashLoaderSWFO
{
	/**#@+
	 * @access private
	 */
	/**
	 * path to SWFObject external JavaScripts library
	 * @link http://blog.deconcept.com/swfobject/
	 * @var string
	 */
	private $_js_path = "common_js/swfobject.js";
	/**
	 * the flash plugin class id
	 * @var string
	 */
	private $_class_id = "D27CDB6E-AE6D-11cf-96B8-444553540000";
	/**
	 * codebase without version number
	 * @var string
	 */
	private $_codebase = "http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=";
	/**
	 * plugins page url
	 * @var string
	 */
	private $_pluginspage = "http://www.macromedia.com/go/getflashplayer";
	/**
	 * flag to be set so we write JavaScript only once
	 * @var boolean
	 */
	private $_common_js_shown = false;
	/**
	 * list of movies added my addMovie()
	 * @see function addMovie
	 * @var array
	 */
	private $_movies = array();
	/**
	 * list of required attribute defaults
	 * @link http://www.adobe.com/cfusion/knowledgebase/index.cfm?id=tn_12701
	 * @var array
	 */
	private $_req_atts = array(
			"src" => false,
			"width" => false,
			"height" => false,
			"version" => false,
			"element_id" => false,
			"id" => 1
			);
	/**
	 * list of SWFObject attribute defaults
	 * @link http://blog.deconcept.com/swfobject/
	 * @var array
	 */
	private $_swfo_atts = array(
			"xiredirecturl" => false,
			"redirecturl" => false,
			"detectkey" => false
			);
	/**
	 * list of main attribute defaults
	 * @link http://www.adobe.com/cfusion/knowledgebase/index.cfm?id=tn_12701
	 * @var array
	 */
	private $_atts = array(
			"useexpressinstall" => false,
			"swliveconnect" => false,
			"allowfullscreen" => false,
			"play" => false,
			"loop" => false,
			"menu" => false,
			"quality" => false,
			"scale" => false,
			"align" => false,
			"salign" => false,
			"wmode" => false,
			"bgcolor" => false,
			"base" => false,
			"flashvars" => false,
			"allowscriptaccess" => false
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
		$this->_atts = $this->_req_atts + $this->_swfo_atts + $this->_atts;
		$this->_atts = $this->_getAtts($args);
	}
	/**
	 * Fill the attribute list with the arguments (if any).
	 * @param string $attr,... "attribute=value",... optional arguments
	 */
	public function setAtts()
	{
		$args = func_get_args();
		$this->_atts = $this->_getAtts($args);
	}
	/**
	 * Parses attribute/value string pairs into an array and
	 * returns them combined with current attribute list.
	 * @access private
	 * @param array $argv attribute/value string pairs
	 * @return array attribute list
	 */
	private function _getAtts($argv)
	{
		$arr = array();
		$ret = array();
		for ($i = 0; $i < count($argv); $i++)
		{
			list($k, $v) = explode("=", $argv[$i], 2);
			$arr[$k] = $v;
		}
		foreach ($this->_atts as $k => $v)
			$ret[$k] = isset($arr[$k]) ? $arr[$k] : $v;
		return $ret;
	}
	/**
	 * Checks attribute list against required attribute list.
	 * @access private
	 * @param array $atts
	 * @return mixed string key if attribute is found missing else boolean false
	 */
	private function _missingReqAtt(&$atts)
	{
		foreach (array_keys($this->_req_atts) as $k)
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
	private function _optionalAtt($att)
	{
		if (array_key_exists($att, $this->_req_atts))
			return false;
		else
			return true;
	}
	/**
	 * Builds the SWFObject JavaScript calls.
	 * @access private
	 * @param array $atts
	 * @return string html code
	 */
	private function _buildSWFObject($atts)
	{
		$js_var = "swfo".$atts['id'];
		$js_func = "swfoLoadMovie".$atts['id'];
		$html = "<script type=\"text/javascript\">\n";
		$html .= "var ".$js_var." = new SWFObject(";
		$html .= "\"".$atts['src']."\",";
		$html .= "\"".$atts['id']."\",";
		$html .= "\"".$atts['width']."\",";
		$html .= "\"".$atts['height']."\",";
		$html .= "\"".$atts['version']."\",";
		$html .= "\"".$atts['bgcolor']."\",";
//		$html .= "\"".$atts['useexpressinstall']."\",";
		$html .= "\"".$atts['quality']."\",";
		$html .= "\"".$atts['xiredirecturl']."\",";
		$html .= "\"".$atts['redirecturl']."\",";
		$html .= "\"".$atts['detectkey']."\"";
		$html .= ");\n";
		//insert the optional attributes
		foreach ($atts as $k => $v)
		{
			if ($this->_optionalAtt($k) && !empty($v))
				$html .= $js_var.".addParam(\"".$k."\", \"".$v."\");\n";
		}
		//write the content to the element_id
		$html .= "if (document.getElementById('".$atts['element_id']."')){".$js_var.".write('".$atts['element_id']."');}else{var ".$js_var."Timer = setInterval(function(){if (document.getElementById('".$atts['element_id']."')){".$js_var.".write('".$atts['element_id']."');clearTimeout(".$js_var."Timer);}}, 10);}\n";
		$html .= "</script>";
		return $html;
	}
	/**
	 * Prints include to JavaScript library.
	 */
	public function getCommonJS()
	{
		echo "<script src=\"".$this->_js_path."\" type=\"text/javascript\"></script>";
		$this->_common_js_shown = true;
	}
	/**
	 * Prints include to JavaScript library.
	 */
	public function getCommonJSPath()
	{
		$this->_common_js_shown = true;
		return $this->_js_path;
	}
	/**
	 * Adds movie arguments to list of movies to be embedded later by loadMovies().
	 * @see function loadMovies
	 * @param string $attr,... "attribute=value",... optional arguments
	 */
	public function addMovie()
	{
		$this->_movies[] = func_get_args();
	}
	/**
	 * Check to see if there are any movies to load.
	 * @return boolean
	 */
	public function hasMovies()
	{
		return count($this->_movies) > 0;
	}
	/**
	 * Gets movies in list created by addMovie()
	 * @see function loadMovie
	 */
	public function getMovies()
	{
		$movies = "";
		foreach ($this->_movies as $m)
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
	 * Gets (embbeds) flash movie in (X)HTML document.
	 * @param mixed $attr,... "attribute=value",... optional arguments or array $atts list
	 */
	public function getMovie()
	{
		if (!$this->_common_js_shown) $this->getCommonJS();
		$args = func_get_args();
		//check to see if we we're passed one array (already pulled from func_get_args)
		if (is_array($args[0]) && count($args) == 1)
			$args = $args[0];
		//get movie specific attributes
		$atts = $this->_getAtts($args);
		//throw an error if we are missing any of the mission critical atts
		if (($error = $this->_missingReqAtt($atts)))
		{
			echo "ERROR: Missing required attribute (".$error.")!";
			return false;
		}
		$name = basename($atts['src'], ".swf");
		$html = "<!--\n/***** START FLASH MOVIE (".$name.") *****/\n-->";
		$html .= $this->_buildSWFObject($atts);
		$html .= "<!--\n/***** END FLASH MOVIE (".$name.") *****/\n-->";
		//keep the master id up to date
		$this->_atts['id']++;
		return $html;
	}
	/**
	 * Loads (embbeds) flash movie in (X)HTML document.
	 * @param mixed $attr,... "attribute=value",... optional arguments or array $atts list
	 */
	public function loadMovie()
	{
		echo $this->getMovie();
	}
} // end class FlashLoaderSWFO
?>
