<?php
/**
 * dbdCSS.php :: dbdCSS Class File
 *
 * @package dbdMVC
 * @version 1.33
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * Controller class for compressing and serving css files.
 * Can be used to combine files using @import statments and the doCombine action.
 * Also extends CSS by adding internal/external variable support and simple math.
 * <b>Set Variable Example:</b>
 * <code>
 * @variables
 * {
 *  host: http://dbdmvc.com;
 * 	dbdRed: #ba1319;
 * 	pageWidth: 1024;
 * 	fontBase: 12;
 * }
 * </code>
 * <b>Use Variable Example:</b>
 * <code>
 * body
 * {
 *  width: var(pageWidth)px;
 * }
 * a
 * {
 *  color: var(dbdRed);
 * }
 * h1.logo
 * {
 * 	background:transparent url(var(host)/images/gfx/dbd_mvc_logo.jpg) no-repeat scroll center top;
 * 	height: 155px;
 * 	width: 283px;
 * }
 * </code>
 * <b>Math Example:</b>
 * <code>
 * h2
 * {
 *  font-size: math(var(fontBase) * 1.2)px;
 * }
 * table
 * {
 * 	padding: 10px;
 * }
 * td
 * {
 * 	width: math((var(pageWidth) - 20) / var(numCols))px;
 * }
 * </code>
 * <b>Note:</b> Use @debug to turn off minification.
 * @package dbdMVC
 * @uses dbdController
 * @uses dbdException
 */
dbdLoader::load("wmGD.php");
class dbdCSS extends dbdController
{
	/**
	 * Css import regular expression.
	 * @todo allow for url() syntax
	 */
	const IMPORT_REGEX = '/^@import [\'\"]([a-z0-9-_\.]+\.css)[\'\"];/i';
	/**
	 * Debug comment to turn off minification.
	 */
	const DEBUG_REGEX = '/^\/\*.*@debug.*\*\//i';
	/**
	 * Css file extension pattern.
	 */
	const CSS_EXT_REGEX = '/\.css$/i';
	/**
	 * Css block open pattern.
	 */
	const BLOCK_OPEN_REGEX = '/{/';
	/**
	 * Css block close pattern.
	 */
	const BLOCK_CLOSE_REGEX = '/}/';
	/**
	 * Css variables at-tag pattern.
	 * Uses implimentation specs from http://disruptive-innovations.com/zoo/cssvariables/
	 */
	const VAR_TAG_REGEX = '/^@variables/i';
	/**
	 * Css variable definition pattern.
	 */
	const VAR_DEF_REGEX = '/([a-z][a-z0-9-_]*)[ \t\n]*:[ \t\n]*([^;]+);/i';
	/**
	 * Css variable call pattern. (for dereferencing)
	 */
	const VAR_CALL_REGEX = '/var[(]([a-z][a-z0-9-_]*)[)]/ie';
	/**
	 * Css math call pattern. (for simple equations)
	 */
	const MATH_CALL_REGEX = '/math[(](.+)[)]/ie';
	/**
	 * Css math safe characters pattern. (for simple equations)
	 */
	const MATH_SAFE_REGEX = '!^[ 0-9.()%/+*-]+$!i';
	/**
	 * Css math safe functions pattern. (these will be stripped in testing to match above pattern)
	 */
	const MATH_SAFE_FUNC_REGEX = '!(abs|acos|acosh|asin|asinh|atan2|atan|atanh|base_convert|bindec|ceil|cos|cosh|decbin|dechex|decoct|deg2rad|exp|expm1|floor|fmod|getrandmax|hexdec|hypot|is_finite|is_infinite|is_nan|lcg_value|log10|log1p|log|max|min|mt_getrandmax|mt_rand|mt_srand|octdec|pi|pow|rad2deg|rand|round|sin|sinh|sqrt|srand|tan|tanh)!i';
	/**
	 * Css buttons at-tag pattern.
	 */
	const BUTT_TAG_REGEX = '/^@buttons/i';
	/**
	 * Css generated buttons at-tag pattern.
	 */
	const BUTT_GEN_TAG_REGEX = '/@generated/i';
	/**
	 * Css generated buttons template tag pattern.
	 */
	const BUTT_GEN_TPL_TAG_REGEX = '/#([a-z][a-z0-9]*)(?![^;{]*;)/i';
	/**
	 * Css button definition pattern.
	 */
	const BUTT_DEF_TAG_REGEX = '/([#.]?[a-z][a-z0-9#.:-]+ )?(?<![a-z0-9#.:-])(a|button|input)([#.])([a-z][a-z0-9#.-]*)[,]?/i';
	/**
	 * Css button property definition pattern.
	 */
	const BUTT_PROP_DEF_REGEX = '/([a-z][a-z-]*)[ \t\n]*[:][ \t\n]*([^;]+);/i';
	/**
	 * Css button property hover pattern.
	 */
	const BUTT_PROP_DEF_HOVER_REGEX = '/[-]hover$/i';
	/**
	 * Css button property active pattern.
	 */
	const BUTT_PROP_DEF_ACTIVE_REGEX = '/[-]active$/i';
	/**
	 * Css button property current pattern.
	 */
	const BUTT_PROP_DEF_CURRENT_REGEX = '/[-]current$/i';
	/**
	 * Css button property disabled pattern.
	 */
	const BUTT_PROP_DEF_DISABLED_REGEX = '/[-]disabled$/i';
	/**
	 * Css button property loading pattern.
	 */
	const BUTT_PROP_DEF_LOADING_REGEX = '/[-]loading$/i';
	/**
	 * Css caching info sprite pattern.
	 */
	const CACHE_INFO_SPRITE = '/^[ ]?\*[ ]?@sprites (.+)$/i';
	/**
	 * Css caching info file list pattern.
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
	 * Array of regex pairs for minifying the css.
	 * @var array
	 */
	private $minify_regex = array(
		"spaces" => array("/ [ ]+/", " "),
		"whitespace" => array("/(\n|\t)+/", ""),
		"spec_char_left" => array("/([{}:;]+)\s+/", "\\1"),
		"spec_char_right" => array("/\s+([{}:;]+)/", "\\1"),
		"comments" => array("%(?(?=/\*[^/]*?\\\\\*/)(/\*.*?\\\\\*/[^/]*?/\*.*?\*/)|/\*.*?\*/)%", "\\1")
	);
	/**
	 * List of css varables that have been defined.
	 * @var array
	 */
	private $vars = array();
	/**
	 * List of available css button properties.
	 * @var array
	 */
	private $button_props = array(
		'global' => array(
			'offset' => 0,
			'transparent' => '#7fffffff',
			'format' => 'png',
			'cache' => null,
			'src' => null
		),
		'generated_global' => array(),
		'generated_tpl' => array(
			'font' => null,
			'font-size' => null,
			'font-color' => null,
			'min-text-width' => null,
			'text-align' => 'center',
			'text-decoration' => 'none',
			'text-horizontal-offset' => 0,
			'text-vertical-offset' => 0,
			'text-shadow' => null,
			'text-shadow-horizontal-offset' => 0,
			'text-shadow-vertical-offset' => 0,
			'cap-width' => null,
			'src-body' => null,
			'src-shade' => null,
			'src-high' => null
		),
		'generated_def' => array(
			'sprite' => 'default',
			'generated' => null,
			'align' => 'left',
			'text' => null,
			'background-color' => '#7fffffff',
			'foreground-color' => null,
			'font-color' => null,
			'font-size' => null,
			'text-shadow' => null,
			'src-body' => null,
			'src-shade' => null,
			'src-high' => null
		),
		'def' => array(
			'sprite' => 'default',
			'align' => 'left',
			'background-image' => null
		)
	);
	/**
	 * List of required css button properties.
	 * @var array
	 */
	private $button_props_required = array(
		'global' => array(
			'offset',
			'transparent',
			'format',
			'cache',
			'src'
		),
		'generated_global' => array(),
		'generated_tpl' => array(
			'font',
			'font-size',
			'font-color',
			'min-text-width',
			'cap-width'
		),
		'generated_def' => array(
			'sprite',
			'generated',
			'align',
			'text',
			'background-color',
//			'foreground-color',
			'font-color',
			'text-align',
			'text-decoration',
			'text-horizontal-offset',
			'text-vertical-offset',
			'src-body'
		),
		'def' => array(
			'sprite',
			'align',
			'background-image'
		)
	);
	/**
	 * List of css button global properties.
	 * @var array
	 */
	private $button_props_global = array();
	/**
	 * List of css generated button global properties.
	 * @var array
	 */
	private $button_props_generated_global = array();
	/**
	 * List of css generated button template properties.
	 * @var array
	 */
	private $button_props_generated_tpl = array();
	/**
	 * List of css buttons that have been generated.
	 * @var array
	 */
	private $buttons_generated = array();
	/**
	 * List of css buttons that have been defined.
	 * @var array
	 */
	private $buttons = array();
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
	 * Sprite file name
	 * @var array
	 */
	private $sprites = array();
	/**
	 * Current parser file
	 * @var string
	 */
	private $file = "";
	/**
	 * Current parser line
	 * @var string
	 */
	private $line = "";
	/**
	 * Current parser line number
	 * @var integer
	 */
	private $line_num = 0;
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
		return __CLASS__.".".md5(serialize($this->vars)).".css";
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
				elseif (preg_match(self::CACHE_INFO_SPRITE, $line, $tmp))
				{
					$sprites = explode(",", $tmp[1]);
				}
				elseif (preg_match(self::CACHE_INFO_FILES, $line, $tmp))
				{
					$files = explode(",", $tmp[1]);
				}
				elseif (preg_match("/^[ ]?\*\/$/", $line))
				{
					$info = false;
					foreach ($sprites as $s)
					{
						if (!file_exists(DBD_ASSET_DIR.$s))
							return false;
					}
					break;
				}
			}
			$this->cache_mtime = filemtime($path);
			foreach ($files as $f)
			{
				switch (true)
				{
					case file_exists(DBD_STYLE_DIR.$f):
						if (filemtime(DBD_STYLE_DIR.$f) > $this->cache_mtime)
							return false;
						break;
					case file_exists(DBD_ASSET_DIR.$f):
						if (filemtime(DBD_ASSET_DIR.$f) > $this->cache_mtime)
							return false;
						break;
				}
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
			$info .= " * @sprites ".implode(",", $this->sprites)."\n";
			$info .= " * @files ".implode(",", $this->files)."\n";
			$info .= " */\n";
			@file_put_contents($file, $info.$this->buffer);
			$this->cache_mtime = filemtime($file);
		}
	}
	/**
	 * Parse css files for import statements and recurse when found.
	 * Dump files upon no match.
	 * @param string $file
	 * @param string $dir
	 * @throws dbdException
	 */
	private function parseImports($file, $dir)
	{
		$this->file = $file;
		if (!preg_match(self::CSS_EXT_REGEX, $this->file))
			$this->file .= ".css";
		$path = dbdLoader::search($this->file, $dir);
		if ($path === false)
			throw new dbdException("Invalid file (".$this->file.")!");
		$this->files[] = $dir.$this->file;
		$this->ensureResource($path);
		$this->buffer .= "\n/* dbdCSS(".$this->file.") */\n";
		$var_at_tag = false;
		$var_block = false;
		$butt_at_tag = false;
		$butt_block = false;
		$butt_gen_at_tag = false;
		$butt_gen_block = false;
		$butt_gen_tpl_sel_tag = false;
		$butt_gen_tpl_block = false;
		$butt_def_sel_tag = false;
		$butt_def_block = false;
		$this->line_num = 0;
		while (!feof($path))
		{
			$this->line_num++;
			$this->line = fgets($path, 4096);
			$this->line = preg_replace(self::VAR_CALL_REGEX, "\$this->getVar('\\1')", $this->line);
			$this->line = preg_replace(self::MATH_CALL_REGEX, "\$this->math('\\1')", $this->line);
			if ($var_at_tag || preg_match(self::VAR_TAG_REGEX, $this->line))
			{
				$var_at_tag = true;
				if (!$var_block && preg_match(self::BLOCK_OPEN_REGEX, $this->line))
				{
					$var_block = true;
				}
				if ($var_block)
				{
					if (preg_match_all(self::VAR_DEF_REGEX, $this->line, $tmp, PREG_SET_ORDER))
					{
						foreach ($tmp as $t)
							$this->vars[$t[1]] = $t[2];
					}
				}
				if (preg_match(self::BLOCK_CLOSE_REGEX, $this->line))
				{
					$var_block = false;
					$var_at_tag = false;
				}
			}
			elseif ($butt_at_tag || preg_match(self::BUTT_TAG_REGEX, $this->line))
			{
				$butt_at_tag = true;
				if (!$butt_block && preg_match(self::BLOCK_OPEN_REGEX, $this->line))
				{
					$butt_block = true;
				}
				if ($butt_block)
				{
					if (preg_match(self::BUTT_DEF_TAG_REGEX, $this->line, $selector) || $butt_def_sel_tag)
					{
						if (!$butt_def_sel_tag)
						{
							$selectors = array();
							$keys = array();
						}
						$butt_def_sel_tag = true;
						if (!$butt_def_block && preg_match(self::BLOCK_OPEN_REGEX, $this->line))
						{
							$butt_def_block = true;
							foreach ($selectors as $selector)
							{
								$key = $selector[1].$selector[2].$selector[3].$selector[4];
								$this->buttons[$key] = array(
									'parent' => $selector[1],
									'tag' => $selector[2],
									'type' => $selector[3],
									'name' => $selector[4],
									'props' => array(),
									'css' => array(),
									'hover' => array(
										'props' => array(),
										'css' => array()
									),
									'active' => array(
										'props' => array(),
										'css' => array()
									),
									'current' => array(
										'props' => array(),
										'css' => array()
									),
									'disabled' => array(
										'props' => array(),
										'css' => array()
									),
									'loading' => array(
										'props' => array(),
										'css' => array()
									)
								);
								$keys[] = $key;
							}
							$p = array();
						}
						else
						{
							$selectors[] = $selector;
						}
						if ($butt_def_block)
						{
							if (preg_match_all(self::BUTT_PROP_DEF_REGEX, $this->line, $tmp, PREG_SET_ORDER))
							{
								foreach ($tmp as $t)
								{
									$p[$t[1]] = $t[2];
								}
							}
						}
						if (preg_match(self::BLOCK_CLOSE_REGEX, $this->line))
						{
							$generated = false;
							if (key_exists('generated', $p))
							{
								$generated = true;
								foreach ($keys as $key)
								{
									if (!key_exists($key, $this->buttons_generated))
									{
										$this->buttons_generated[$key] = array(
											'hover' => array(),
											'active' => array(),
											'current' => array(),
											'disabled' => array(),
											'loading' => array()
										);
									}
								}
							}
							foreach ($p as $k => $v)
							{
								$hover = false;
								$active = false;
								$current = false;
								$disabled = false;
								$loading = false;
								if (preg_match(self::BUTT_PROP_DEF_HOVER_REGEX, $k))
								{
									$hover = true;
									$k = preg_replace(self::BUTT_PROP_DEF_HOVER_REGEX, '', $k);
								}
								if (preg_match(self::BUTT_PROP_DEF_ACTIVE_REGEX, $k))
								{
									$active = true;
									$k = preg_replace(self::BUTT_PROP_DEF_ACTIVE_REGEX, '', $k);
								}
								if (preg_match(self::BUTT_PROP_DEF_CURRENT_REGEX, $k))
								{
									$current = true;
									$k = preg_replace(self::BUTT_PROP_DEF_CURRENT_REGEX, '', $k);
								}
								if (preg_match(self::BUTT_PROP_DEF_DISABLED_REGEX, $k))
								{
									$disabled = true;
									$k = preg_replace(self::BUTT_PROP_DEF_DISABLED_REGEX, '', $k);
								}
								if (preg_match(self::BUTT_PROP_DEF_LOADING_REGEX, $k))
								{
									$loading = true;
									$k = preg_replace(self::BUTT_PROP_DEF_LOADING_REGEX, '', $k);
								}
								foreach ($keys as $key)
								{
									if ($generated)
									{
										if (key_exists($k, $this->button_props['generated_def']))
										{
											if ($hover)
												$this->buttons_generated[$key]['hover'][$k] = $v;
											elseif ($active)
												$this->buttons_generated[$key]['active'][$k] = $v;
											elseif ($current)
												$this->buttons_generated[$key]['current'][$k] = $v;
											elseif ($disabled)
												$this->buttons_generated[$key]['disabled'][$k] = $v;
											elseif ($loading)
												$this->buttons_generated[$key]['loading'][$k] = $v;
											else
												$this->buttons_generated[$key][$k] = $v;
										}
										else
										{
											if ($hover)
												$this->buttons[$key]['hover']['css'][$k] = $v;
											elseif ($active)
												$this->buttons[$key]['active']['css'][$k] = $v;
											elseif ($current)
												$this->buttons[$key]['current']['css'][$k] = $v;
											elseif ($disabled)
												$this->buttons[$key]['disabled']['css'][$k] = $v;
											elseif ($loading)
												$this->buttons[$key]['loading']['css'][$k] = $v;
											else
												$this->buttons[$key]['css'][$k] = $v;
										}
									}
									else
									{
										if (key_exists($k, $this->button_props['def']))
										{
											if ($hover)
												$this->buttons[$key]['hover']['props'][$k] = $v;
											elseif ($active)
												$this->buttons[$key]['active']['props'][$k] = $v;
											elseif ($current)
												$this->buttons[$key]['current']['props'][$k] = $v;
											elseif ($disabled)
												$this->buttons[$key]['disabled']['props'][$k] = $v;
											elseif ($loading)
												$this->buttons[$key]['loading']['props'][$k] = $v;
											else
												$this->buttons[$key]['props'][$k] = $v;
										}
										else
										{
											if ($hover)
												$this->buttons[$key]['hover']['css'][$k] = $v;
											elseif ($active)
												$this->buttons[$key]['active']['css'][$k] = $v;
											elseif ($current)
												$this->buttons[$key]['current']['css'][$k] = $v;
											elseif ($disabled)
												$this->buttons[$key]['disabled']['css'][$k] = $v;
											elseif ($loading)
												$this->buttons[$key]['loading']['css'][$k] = $v;
											else
												$this->buttons[$key]['css'][$k] = $v;
										}
									}
								}
							}
							$this->line = preg_replace(self::BLOCK_CLOSE_REGEX, '', $this->line, 1);
							$butt_def_block = false;
							$butt_def_sel_tag = false;
						}
					}
					elseif ($butt_gen_at_tag || preg_match(self::BUTT_GEN_TAG_REGEX, $this->line))
					{
						$butt_gen_at_tag = true;
						if (!$butt_gen_block && preg_match(self::BLOCK_OPEN_REGEX, $this->line))
						{
							$butt_gen_block = true;
						}
						if ($butt_gen_block)
						{
							if ($butt_gen_tpl_sel_tag || preg_match(self::BUTT_GEN_TPL_TAG_REGEX, $this->line, $name))
							{
								$butt_gen_tpl_sel_tag = true;
								if (!$butt_gen_tpl_block && preg_match(self::BLOCK_OPEN_REGEX, $this->line))
								{
									$butt_gen_tpl_block = true;
									$key = $name[1];
									$this->button_props_generated_tpl[$key] = array(
										'hover' => array(),
										'active' => array(),
										'current' => array(),
										'disabled' => array(),
										'loading' => array()
									);
								}
								if ($butt_gen_tpl_block)
								{
									if (preg_match_all(self::BUTT_PROP_DEF_REGEX, $this->line, $tmp, PREG_SET_ORDER))
									{
										foreach ($tmp as $t)
										{
											$k = $t[1];
											$v = $t[2];
											$hover = false;
											$active = false;
											$current = false;
											$disabled = false;
											$loading = false;
											if (preg_match(self::BUTT_PROP_DEF_HOVER_REGEX, $k))
											{
												$hover = true;
												$k = preg_replace(self::BUTT_PROP_DEF_HOVER_REGEX, '', $k);
											}
											if (preg_match(self::BUTT_PROP_DEF_ACTIVE_REGEX, $k))
											{
												$active = true;
												$k = preg_replace(self::BUTT_PROP_DEF_ACTIVE_REGEX, '', $k);
											}
											if (preg_match(self::BUTT_PROP_DEF_CURRENT_REGEX, $k))
											{
												$current = true;
												$k = preg_replace(self::BUTT_PROP_DEF_CURRENT_REGEX, '', $k);
											}
											if (preg_match(self::BUTT_PROP_DEF_DISABLED_REGEX, $k))
											{
												$disabled = true;
												$k = preg_replace(self::BUTT_PROP_DEF_DISABLED_REGEX, '', $k);
											}
											if (preg_match(self::BUTT_PROP_DEF_LOADING_REGEX, $k))
											{
												$loading = true;
												$k = preg_replace(self::BUTT_PROP_DEF_LOADING_REGEX, '', $k);
											}
											if (key_exists($k, $this->button_props['generated_tpl']) || key_exists($k, $this->button_props['generated_def']))
											{
												if ($hover)
													$this->button_props_generated_tpl[$key]['hover'][$k] = $v;
												elseif ($active)
													$this->button_props_generated_tpl[$key]['active'][$k] = $v;
												elseif ($current)
													$this->button_props_generated_tpl[$key]['current'][$k] = $v;
												elseif ($disabled)
													$this->button_props_generated_tpl[$key]['disabled'][$k] = $v;
												elseif ($loading)
													$this->button_props_generated_tpl[$key]['loading'][$k] = $v;
												else
													$this->button_props_generated_tpl[$key][$k] = $v;
											}
										}
									}
								}
								if (preg_match(self::BLOCK_CLOSE_REGEX, $this->line))
								{
									$this->line = preg_replace(self::BLOCK_CLOSE_REGEX, '', $this->line, 1);
									$butt_gen_tpl_block = false;
									$butt_gen_tpl_sel_tag = false;
								}
							}
							elseif (preg_match_all(self::BUTT_PROP_DEF_REGEX, $this->line, $tmp, PREG_SET_ORDER))
							{
								foreach ($tmp as $t)
								{
									$k = $t[1];
									$v = $t[2];
									$hover = false;
									$active = false;
									$current = false;
									$disabled = false;
									$loading = false;
									if (preg_match(self::BUTT_PROP_DEF_HOVER_REGEX, $k))
									{
										$hover = true;
										$k = preg_replace(self::BUTT_PROP_DEF_HOVER_REGEX, '', $k);
									}
									if (preg_match(self::BUTT_PROP_DEF_ACTIVE_REGEX, $k))
									{
										$active = true;
										$k = preg_replace(self::BUTT_PROP_DEF_ACTIVE_REGEX, '', $k);
									}
									if (preg_match(self::BUTT_PROP_DEF_CURRENT_REGEX, $k))
									{
										$current = true;
										$k = preg_replace(self::BUTT_PROP_DEF_CURRENT_REGEX, '', $k);
									}
									if (preg_match(self::BUTT_PROP_DEF_DISABLED_REGEX, $k))
									{
										$disabled = true;
										$k = preg_replace(self::BUTT_PROP_DEF_DISABLED_REGEX, '', $k);
									}
									if (preg_match(self::BUTT_PROP_DEF_LOADING_REGEX, $k))
									{
										$loading = true;
										$k = preg_replace(self::BUTT_PROP_DEF_LOADING_REGEX, '', $k);
									}
									if (key_exists($k, $this->button_props['generated_global']) || key_exists($k, $this->button_props['generated_tpl']) || key_exists($k, $this->button_props['generated_def']))
									{
										if ($hover)
											$this->button_props_generated_global['hover'][$k] = $v;
										elseif ($active)
											$this->button_props_generated_global['active'][$k] = $v;
										elseif ($current)
											$this->button_props_generated_global['current'][$k] = $v;
										elseif ($disabled)
											$this->button_props_generated_global['disabled'][$k] = $v;
										elseif ($loading)
											$this->button_props_generated_global['loading'][$k] = $v;
										else
											$this->button_props_generated_global[$k] = $v;
									}
									if (key_exists($t[1], $this->button_props['generated_global']) || key_exists($t[1], $this->button_props['generated_tpl']) || key_exists($t[1], $this->button_props['generated_def']))
										$this->button_props_generated_global[$t[1]] = $t[2];
								}
							}
						}
						if (preg_match(self::BLOCK_CLOSE_REGEX, $this->line))
						{
							$this->line = preg_replace(self::BLOCK_CLOSE_REGEX, '', $this->line, 1);
							$butt_gen_block = false;
							$butt_gen_at_tag = false;
						}
					}
					elseif (preg_match_all(self::BUTT_PROP_DEF_REGEX, $this->line, $tmp, PREG_SET_ORDER))
					{
						foreach ($tmp as $t)
						{
							if (key_exists($t[1], $this->button_props['global']))
								$this->button_props_global[$t[1]] = $t[2];
						}
					}
				}
				if (preg_match(self::BLOCK_CLOSE_REGEX, $this->line))
				{
					$this->line = preg_replace(self::BLOCK_CLOSE_REGEX, '', $this->line, 1);
					$butt_block = false;
					$butt_at_tag = false;
					$this->genButtonCSS();
				}
			}
			elseif (preg_match(self::IMPORT_REGEX, $this->line, $tmp))
			{
				$this->parseImports($tmp[1], $dir);
			}
			elseif (preg_match(self::DEBUG_REGEX, $this->line))
			{
				$this->debug = true;
			}
			else
			{
				$this->buffer .= $this->line;
//				$this->dumpFile($path);
//				break;
			}
		}
	}
	/**
	 * Generate css for the defined buttons.
	 */
	private function genButtonCSS()
	{
		if (count($this->buttons) == 0) return;
		$this->genButtons();
		$this->sprites = $this->genButtonSprites();
		$this->buffer .= ".hiddenButtonDiv{overflow: hidden; position: relative;}";
		$this->buffer .= ".hiddenButton,.hiddenButtonDiv a{display: inline; font-size: 100px; height: 100%; opacity: 0; filter: alpha(opacity=0); position: absolute; right: 0; top: 0;}";
		$this->buffer .= ".hiddenButtonDiv.disabled a,.hiddenButtonDiv.loading a,a.ui-state-disabled{cursor: default;}";
		$this->buffer .= ".hiddenButtonDiv div{position: absolute; top: 0; left: 0;}";
		$this->buffer .= ".hiddenButtonDiv a{width: 100%;}";
		$tmp = "";
		$hide = "";
		$sprites = array();
		$i = array();
		foreach ($this->buttons as $s => $b)
		{
			if (!key_exists($b['sprite'], $sprites))
				$sprites[$b['sprite']] = "";
			if (!key_exists($b['sprite'], $i))
				$i[$b['sprite']] = 0;
			if ($i[$b['sprite']]++ > 0)
				$sprites[$b['sprite']] .= ",";
			if ($b['tag'] == 'input')
			{
				$s = $b['parent'].$b['type'].$b['name'];
				$l = $s."Off,".$s."On,".$s."Dn";
				$d = $s."Div.disabled ".$b['type'].$b['name']."Off,".$s."Div.disabled ".$b['type'].$b['name']."On,".$s."Div.disabled ".$b['type'].$b['name']."Dn";
				$d .= ",Div.loading ".$b['type'].$b['name']."Off,".$s."Div.loading ".$b['type'].$b['name']."On,".$s."Div.loading ".$b['type'].$b['name']."Dn";
				$sprites[$b['sprite']] .= $l;
				$tmp .= $l.",".$s."Div{display: block; width: ".$b['css']['width']."; height: ".$b['css']['height'].";}";
				$tmp .= $l."{";
				foreach ($b['css'] as $k => $v)
					$tmp .= $k.": ".$v.";";
				$tmp .= "}";
				if (count($b['hover']['css']))
				{
					$tmp .= $s."On{";
					foreach ($b['hover']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
				if (count($b['active']['css']))
				{
					$tmp .= $s."Dn{";
					foreach ($b['active']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
				if (count($b['disabled']['css']))
				{
					$tmp .= $d."{";
					foreach ($b['disabled']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
				if (count($b['loading']['css']))
				{
					$tmp .= $d."{";
					foreach ($b['loading']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
			}
			else
			{
				$sprites[$b['sprite']] .= $s;
				if (!empty($hide))
					$hide .= ",";
				$hide .= $s." span";
				$tmp .= $s."{";
				foreach ($b['css'] as $k => $v)
					$tmp .= $k.": ".$v.";";
				$tmp .= "}";
				if (count($b['hover']['css']))
				{
					$tmp .= $s.":hover,".$s.":focus{";
					foreach ($b['hover']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
				if (count($b['active']['css']))
				{
					$tmp .= $s.":active{";
					foreach ($b['active']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
				if (count($b['current']['css']) || count($b['hover']['css']))
				{
					$tmp .= $s.".current{";
					foreach ($b[(count($b['current']['css']) ? 'current' : 'hover')]['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
				if (count($b['disabled']['css']))
				{
					$tmp .= $s.".disabled,".$s.".ui-state-disabled{";
					foreach ($b['disabled']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
				if (count($b['loading']['css']))
				{
					$tmp .= $s.".loading{";
					foreach ($b['loading']['css'] as $k => $v)
						$tmp .= $k.": ".$v.";";
					$tmp .= "}";
				}
			}
		}
		foreach ($this->sprites as $name => $file)
			$this->buffer .= $sprites[$name]."{display: block; background: transparent url(".$file.") no-repeat 0 0; border: none;}";
		$this->buffer .= $tmp;
		$this->buffer .= $hide."{position: absolute; display: block; width: 0; height: 0; overflow: hidden; font-size: 0;}";
	}
	/**
	 * Generate the sprite with all of the buttons
	 * and register their offsets in the properties array.
	 * @return string filename
	 */
	private function genButtonSprites()
	{
		$global = $this->checkButtonPropsRequired('global');
		$imgs = array();
		foreach ($this->buttons as $s => $b)
		{
			$b['props'] = array_merge($this->checkButtonPropsRequired('def', $s), $b['props']);
			if (!key_exists($b['props']['sprite'], $imgs))
				$imgs[$b['props']['sprite']] = array();
			$imgs[$b['props']['sprite']][$s] = array(
				'src' => $b['props']['background-image'],
				'align' => $b['props']['align']
			);
			if (count($b['hover']['props']))
			{
				$imgs[$b['props']['sprite']][$s.":hover"] = array(
					'src' => isset($b['hover']['props']['background-image']) ? $b['hover']['props']['background-image'] : $b['props']['background-image'],
					'align' => $b['props']['align']
				);
			}
			if (count($b['hover']['props']))
			{
				$imgs[$b['props']['sprite']][$s.":hover"] = array(
					'src' => isset($b['hover']['props']['background-image']) ? $b['hover']['props']['background-image'] : $b['props']['background-image'],
					'align' => $b['props']['align']
				);
			}
			if (count($b['active']['props']))
			{
				$imgs[$b['props']['sprite']][$s.":active"] = array(
					'src' => isset($b['active']['props']['background-image']) ? $b['active']['props']['background-image'] : $b['props']['background-image'],
					'align' => $b['props']['align']
				);
			}
			if (count($b['current']['props']))
			{
				$imgs[$b['props']['sprite']][$s.":current"] = array(
					'src' => isset($b['current']['props']['background-image']) ? $b['current']['props']['background-image'] : $b['props']['background-image'],
					'align' => $b['props']['align']
				);
			}
			if (count($b['disabled']['props']))
			{
				$imgs[$b['props']['sprite']][$s.":disabled"] = array(
					'src' => isset($b['disabled']['props']['background-image']) ? $b['disabled']['props']['background-image'] : $b['props']['background-image'],
					'align' => $b['props']['align']
				);
			}
			if (count($b['loading']['props']))
			{
				$imgs[$b['props']['sprite']][$s.":loading"] = array(
					'src' => isset($b['loading']['props']['background-image']) ? $b['loading']['props']['background-image'] : $b['props']['background-image'],
					'align' => $b['props']['align']
				);
			}
		}
		$dims = array();
		$default = array(
			'width' => 0,
			'height' => 0,
			'x' => 0,
			'y' => 0
		);
		foreach (array_keys($imgs) as $s)
		{
			if (!key_exists($s, $dims))
				$dims[$s] = $default;
			foreach (array_keys($imgs[$s]) as $i)
			{
				if (strpos($imgs[$s][$i]['src'], DBD_DS) === false)
					$imgs[$s][$i]['src'] = DBD_ASSET_DIR.$global['src'].$imgs[$s][$i]['src'];
				list($imgs[$s][$i]['width'], $imgs[$s][$i]['height']) = getimagesize($imgs[$s][$i]['src']);
				if ($imgs[$s][$i]['width'] > $dims[$s]['width'])
					$dims[$s]['width'] = $imgs[$s][$i]['width'];
				if ($dims[$s]['height'] > 0)
					$dims[$s]['height'] += $global['offset'];
				$dims[$s]['height'] += $imgs[$s][$i]['height'];
			}
		}
		$sprites = array();
		foreach ($dims as $s => $d)
		{
			$a = array();
			$out = imageCreateTrueColor($d['width'], $d['height']);
			imagealphablending($out, false);
			imagesavealpha($out, true);
			$bgColor = new GDColor($out, $global['transparent']);
			imageFill($out, 0, 0, $bgColor->getColor()); // this isn't automatic w/ imageCreateTrueColor
			imagecolortransparent($out, $bgColor->getColor());
			foreach (array_keys($imgs[$s]) as $i)
			{
				$a[] = $imgs[$s][$i]['src'];
				$this->files[] = str_replace(DBD_ASSET_DIR, "", $imgs[$s][$i]['src']);
				$tmp = imageCreateFrom($imgs[$s][$i]['src']);
				switch ($imgs[$s][$i]['align'])
				{
					case 'right':
						$d['x'] = $d['width'] - $imgs[$s][$i]['width'];
						$imgs[$s][$i]['left'] = 'right';
						break;
					case 'center':
						$d['x'] = ($d['width'] - $imgs[$s][$i]['width']) / 2;
						$imgs[$s][$i]['left'] = 'center';
						break;
					default:
						$imgs[$s][$i]['left'] = 'left';
						$d['x'] = 0;
				}
				imagecopy($out, $tmp, $d['x'], $d['y'], 0, 0, $imgs[$s][$i]['width'], $imgs[$s][$i]['height']);
				$imgs[$s][$i]['top'] = $d['y'];
				if (preg_match('/:hover$/', $i))
				{
					$j = preg_replace('/:hover$/', '', $i);
					$this->buttons[$j]['hover']['css']['background-position'] = $imgs[$s][$i]['left']." -".$imgs[$s][$i]['top']."px";
				}
				elseif (preg_match('/:active$/', $i))
				{
					$j = preg_replace('/:active$/', '', $i);
					$this->buttons[$j]['active']['css']['background-position'] = $imgs[$s][$i]['left']." -".$imgs[$s][$i]['top']."px";
				}
				elseif (preg_match('/:current$/', $i))
				{
					$j = preg_replace('/:current$/', '', $i);
					$this->buttons[$j]['current']['css']['background-position'] = $imgs[$s][$i]['left']." -".$imgs[$s][$i]['top']."px";
				}
				elseif (preg_match('/:disabled$/', $i))
				{
					$j = preg_replace('/:disabled$/', '', $i);
					$this->buttons[$j]['disabled']['css']['background-position'] = $imgs[$s][$i]['left']." -".$imgs[$s][$i]['top']."px";
				}
				elseif (preg_match('/:loading$/', $i))
				{
					$j = preg_replace('/:loading$/', '', $i);
					$this->buttons[$j]['loading']['css']['background-position'] = $imgs[$s][$i]['left']." -".$imgs[$s][$i]['top']."px";
				}
				else
				{
					$this->buttons[$i]['css']['width'] = $imgs[$s][$i]['width']."px";
					$this->buttons[$i]['css']['height'] = $imgs[$s][$i]['height']."px";
					$this->buttons[$i]['css']['background-position'] = $imgs[$s][$i]['left']." -".$imgs[$s][$i]['top']."px";
					$this->buttons[$i]['sprite'] = $s;
				}
				$d['y'] += $imgs[$s][$i]['height'] + $global['offset'];
			}
			$a = array_merge($global, $a);
			$file = $this->genButtonCacheFile($global['format'], $a);
			outputAndDestroy($out, $global['format'], $file, true);
			$sprites[$s] = $global['cache'].basename($file);
		}
		return $sprites;
	}
	/**
	 * Genrate layered buttons from source images.
	 */
	private function genButtons()
	{
		foreach ($this->buttons_generated as $id => $b)
		{
			$p = $this->checkButtonPropsRequired('generated_def', $id);
			$this->buttons[$id]['props']['background-image'] = $this->genButton($id, $p);
			if (key_exists('hover', $p))
				$this->buttons[$id]['hover']['props']['background-image'] = $this->genButton($id, array_merge($p, $p['hover']));
			if (key_exists('active', $p))
				$this->buttons[$id]['active']['props']['background-image'] = $this->genButton($id, array_merge($p, $p['active']));
			if (key_exists('current', $p))
				$this->buttons[$id]['current']['props']['background-image'] = $this->genButton($id, array_merge($p, $p['current']));
			if (key_exists('disabled', $p))
				$this->buttons[$id]['disabled']['props']['background-image'] = $this->genButton($id, array_merge($p, $p['disabled']));
			if (key_exists('loading', $p))
				$this->buttons[$id]['loading']['props']['background-image'] = $this->genButton($id, array_merge($p, $p['loading']));
		}
	}
	/**
	 * Generate a layered button from source images.
	 * @param integer $id
	 * @param array $p
	 * @return string filename
	 */
	private function genButton($id, $p)
	{
		$dir = DBD_ASSET_DIR.$p['src'];
		$srcs = array('body' => $dir.$p['src-body']);
		if (isset($p['src-shade']))
			$srcs['shade'] = $dir.$p['src-shade'];
		if (isset($p['src-high']))
			$srcs['high'] = $dir.$p['src-high'];
		$value = $p['text'];
		$btn_body = imageCreateFrom($srcs['body']);
		$btn_ht = imagesy($btn_body);
		$start_wd = imagesx($btn_body);
		// calculate the width of the text block
		$tbb = imageFTBBox ($p['font-size'], 0, $p['font'], $value);
		$text_wd = abs($tbb[0] - $tbb[4]); // image width
		// use text width to calculate final button width & x,y
		$btn_wd = max($text_wd, $p['min-text-width']) + ($p['cap-width'] * 2);
		$btn_wd += $btn_wd % 2;
		switch ($p['text-align'])
		{
			case 'left':
				$x = $p['cap-width'];
				break;
			case 'right':
				$x = $btn_wd - $text_wd - $p['cap-width'];
				break;
			default:
				$x = floor(($btn_wd - $text_wd) / 2);
				break;
		}
		$x = $x - 1 + $p['text-horizontal-offset'];
		$y =  $p['font-size'] + ceil(($btn_ht - $p['font-size']) / 2) - 1 + $p['text-vertical-offset'];
		$butts = array(array(
			"fg" => isset($p['foreground-color']) ? $p['foreground-color'] : null,
			"fc" => $p['font-color'],
			"ts" => isset($p['text-shadow']) ? $p['text-shadow'] : null
		));
		if (isset($p['foreground-color-hover']) || isset($p['font-color-hover']) || isset($p['text-shadow-hover']))
		{
			$butts[] = array(
				"fg" => isset($p['foreground-color-hover']) ? $p['foreground-color-hover'] : isset($p['foreground-color']) ? $p['foreground-color'] : null,
				"fc" => isset($p['font-color-hover']) ? $p['font-color-hover'] : $p['font-color'],
				"ts" => isset($p['text-shadow-hover']) ? $p['text-shadow-hover'] : isset($p['text-shadow']) ? $p['text-shadow'] : null
			);
		}
		if (isset($p['foreground-color-active']) || isset($p['font-color-active']) || isset($p['text-shadow-active']))
		{
			$butts[] = array(
				"fg" => isset($p['foreground-color-active']) ? $p['foreground-color-active'] : isset($p['foreground-color']) ? $p['foreground-color'] : null,
				"fc" => isset($p['font-color-active']) ? $p['font-color-active'] : $p['font-color'],
				"ts" => isset($p['text-shadow-active']) ? $p['text-shadow-active'] : isset($p['text-shadow']) ? $p['text-shadow'] : null
			);
		}
		if (isset($p['foreground-color-current']) || isset($p['font-color-current']) || isset($p['text-shadow-current']))
		{
			$butts[] = array(
				"fg" => isset($p['foreground-color-current']) ? $p['foreground-color-current'] : isset($p['foreground-color']) ? $p['foreground-color'] : null,
				"fc" => isset($p['font-color-current']) ? $p['font-color-current'] : $p['font-color'],
				"ts" => isset($p['text-shadow-current']) ? $p['text-shadow-current'] : isset($p['text-shadow']) ? $p['text-shadow'] : null
			);
		}
		if (isset($p['foreground-color-disabled']) || isset($p['font-color-disabled']) || isset($p['text-shadow-disabled']))
		{
			$butts[] = array(
				"fg" => isset($p['foreground-color-disabled']) ? $p['foreground-color-disabled'] : isset($p['foreground-color']) ? $p['foreground-color'] : null,
				"fc" => isset($p['font-color-disabled']) ? $p['font-color-disabled'] : $p['font-color'],
				"ts" => isset($p['text-shadow-disabled']) ? $p['text-shadow-disabled'] : isset($p['text-shadow']) ? $p['text-shadow'] : null
			);
		}
		if (isset($p['foreground-color-loading']) || isset($p['font-color-loading']) || isset($p['text-shadow-loading']))
		{
			$butts[] = array(
				"fg" => isset($p['foreground-color-loading']) ? $p['foreground-color-loading'] : isset($p['foreground-color']) ? $p['foreground-color'] : null,
				"fc" => isset($p['font-color-loading']) ? $p['font-color-loading'] : $p['font-color'],
				"ts" => isset($p['text-shadow-loading']) ? $p['text-shadow-loading'] : isset($p['text-shadow']) ? $p['text-shadow'] : null
			);
		}
		$out_ht = ($btn_ht + $p['offset']) * count($butts) - $p['offset'];
		$btn_out = imageCreateTrueColor($btn_wd, $out_ht);
		$bgColor = new GDColor($btn_out, $p['background-color']);
		imageFill($btn_out, 0, 0, $bgColor->getColor()); // this isn't automatic w/ imageCreateTrueColor
		imagecolortransparent($btn_out, $bgColor->getColor());
		foreach ($butts as $i => $b)
		{
			if ($i > 0)
				$btn_body = imageCreateFrom($srcs['body']);
			$off_y = ($btn_ht * $i) + ($p['offset'] * $i);
			$fontColor = new GDColor($btn_out, $b['fc']);
			if (isset($b['fg']))
			{
				$fgColor = new GDColor($btn_out, $b['fg']);
				colorizeImage($btn_body, $fgColor);
			}
			if (isset($srcs['shade']))
			{
			    $btn_shadow = imageCreateFrom($srcs['shade']);
				stretchCopyButton($btn_out, $btn_shadow, $btn_wd, $start_wd, $btn_ht, $off_y, $p['cap-width']);
			}
			stretchCopyButton($btn_out, $btn_body, $btn_wd, $start_wd, $btn_ht, $off_y, $p['cap-width']);
			if (isset($srcs['high']))
			{
				$btn_highlight = imageCreateFrom($srcs['high']);
				stretchCopyButton($btn_out, $btn_highlight, $btn_wd, $start_wd, $btn_ht, $off_y, $p['cap-width']);
			}
			if (isset($b['ts']))
			{
				$tsColor = new GDColor($btn_out, $b['ts']);
				$tbb = imageFTText($btn_out, $p['font-size'], 0, $x + $p['text-shadow-horizontal-offset'], $y + $off_y + $p['text-shadow-vertical-offset'], $tsColor->getColor(), $p['font'], $value);
			}
			imageFTText($btn_out, $p['font-size'], 0, $x, $y + $off_y, $fontColor->getColor(), $p['font'], $value);
			switch ($p['text-decoration'])
			{
				case 'overline':
					$pad = round($p['font-size'] * 0.15);
					imageLineThick($btn_out, $x, $y + $off_y - ($pad * 2) - $p['font-size'], $x + $text_wd, $y + $off_y - ($pad * 2) - $p['font-size'], $fontColor->getColor(), $pad);
					if ($tsColor)
						imageLineThick($btn_out, $x + $p['text-shadow-horizontal-offset'], $y + $off_y - ($pad * 2) - $p['font-size'] + $p['text-shadow-vertical-offset'], $x + $text_wd + $p['text-shadow-horizontal-offset'], $y + $off_y - ($pad * 2) - $p['font-size'] + $p['text-shadow-vertical-offset'], $fontColor->getColor(), $pad);
					break;
				case 'underline':
					$pad = round($p['font-size'] * 0.15);
					imageLineThick($btn_out, $x, $y + $off_y + $pad, $x + $text_wd, $y + $off_y + $pad, $fontColor->getColor(), $pad);
					if ($tsColor)
						imageLineThick($btn_out, $x + $p['text-shadow-horizontal-offset'], $y + $off_y + $pad + $p['text-shadow-vertical-offset'], $x + $text_wd + $p['text-shadow-horizontal-offset'], $y + $off_y + $pad + $p['text-shadow-vertical-offset'], $fontColor->getColor(), $pad);
					break;
				case 'line-through':
					$pad = round($p['font-size'] * 0.15);
					imageLineThick($btn_out, $x, $y + $off_y - (($pad + $p['font-size']) / 2), $x + $text_wd, $y + $off_y - (($pad + $p['font-size']) / 2), $fontColor->getColor(), $pad);
					if ($tsColor)
						imageLineThick($btn_out, $x + $p['text-shadow-horizontal-offset'], $y + $off_y - (($pad + $p['font-size']) / 2) + $p['text-shadow-vertical-offset'], $x + $text_wd + $p['text-shadow-horizontal-offset'], $y + $off_y - (($pad + $p['font-size']) / 2) + $p['text-shadow-vertical-offset'], $fontColor->getColor(), $pad);
					break;
			}
		}
		imagealphablending($btn_out, false);
		imagesavealpha($btn_out, true);
		$file = $this->genButtonCacheFile($p['format'], $p);
		outputAndDestroy($btn_out, $p['format'], $file, true);
		return $file;
	}
	/**
	 * Generate the cached sprite file name.
	 * @param string $type
	 * @param array $params
	 * @return string
	 */
	private function genButtonCacheFile($type, $params)
	{
		ksort($params);
		$str = __CLASS__;
		if (key_exists('hover', $params))
			unset($params['hover']);
		if (key_exists('active', $params))
			unset($params['active']);
		if (key_exists('current', $params))
			unset($params['current']);
		if (key_exists('disabled', $params))
			unset($params['disabled']);
		if (key_exists('loading', $params))
			unset($params['loading']);
		$str .= ".".md5(strtolower(implode(",", $params)));
		$file = DBD_ASSET_DIR.$params['cache'].$str.".".$type;
		return $file;
	}
	/**
	 * Check for a cached button.
	 * @param string $type
	 * @param array $params
	 * @return boolean
	 */
	private function checkButtonCache($type, $params)
	{
		$cache = $this->genButtonCacheFile($type, $params);
		if (file_exists($cache))
		{
			$im = imageCreateFrom($cache);
			if (!$im) return;
			imagesavealpha($im, true);
//			outputAndDestroy($im);
			return true;
		}
		else
		{
			return false;
		}
	}
	/**
	 * Check that the required property exists.
	 * @param string $key
	 * @param string $selector
	 * @return string
	 */
	private function checkButtonPropsRequired($key, $selector = null)
	{
		$p = $this->getButtonProps($key, $selector);
		foreach ($this->button_props_required[$key] as $r)
		{
			if (!key_exists($r, $p) || $p[$r] === "" || $p[$r] === null)
				throw new dbdException(__CLASS__.": Button css missing property (".$r.": ".$p[$r].") (".$selector.")");
		}
		return $p;
	}
	/**
	 * Get properties for a button
	 * @param string $key
	 * @param string $selector
	 * @return array
	 */
	private function getButtonProps($key, $selector = null)
	{
		switch ($key)
		{
			case 'global':
				$p = $this->button_props[$key];
				foreach ($this->button_props_global as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				return $p;
			case 'generated_global':
				$p = $this->button_props[$key];
				foreach ($this->button_props_generated_global as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				return $p;
			case 'generated_tpl':
				$p = $this->getButtonProps('generated_global');
				foreach ($this->button_props[$key] as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				foreach ($this->button_props_generated_tpl[$selector] as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				return $p;
			case 'generated_def':
				$p = $this->getButtonProps('global');
				foreach ($this->getButtonProps('generated_tpl', $this->buttons_generated[$selector]['generated']) as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				foreach ($this->button_props[$key] as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				foreach ($this->buttons_generated[$selector] as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				return $p;
			case 'def':
				$p = $this->getButtonProps('global');
				foreach ($this->button_props[$key] as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				foreach ($this->buttons[$selector]['props'] as $k => $v)
				{
					if ($v !== "" && $v !== null && (!is_array($v) || count($v)))
						$p[$k] = $v;
				}
				return $p;
			default:
				return array();
		}
	}
	/**
	 * Get the value of a defined variable.
	 * @param string $name
	 * @return mixed
	 */
	private function getVar($name)
	{
		if (key_exists($name, $this->vars))
			return $this->vars[$name];
		else
			(__CLASS__.":".$this->file.":".$this->line_num.": Variable (".$name.") is not defined!");
	}
	/**
	 * Evaluate a math equation and return the value. (All PHP math functions are safe)
	 * @param string $eq
	 * @return mixed
	 */
	private function math($eq)
	{
		if (preg_match(self::MATH_SAFE_REGEX, preg_replace(self::MATH_SAFE_FUNC_REGEX, '', $eq)))
			return eval("return ".$eq.";");
		else
			dbdLog(__CLASS__.":".$this->file.":".$this->line_num.": Math equation (".$eq.") is not safe!");
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
	 * Set headers, minify, and echo buffer.
	 */
	private function output($cache = false)
	{
		if (!$cache && !$this->debug)
		{
			$this->minify();
			$this->createCache();
		}
		dbdOB::start();
		echo $this->buffer;
		if ($this->setHeaders())
			dbdOB::flush();
		else
			dbdOB::end();
	}
	/**
	 * Minify buffer.
	 */
	private function minify()
	{
		foreach ($this->minify_regex as $k => $v)
			$this->buffer = preg_replace($v[0], $v[1], $this->buffer);
		$this->buffer = trim($this->buffer);
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
			throw new dbdException(__CLASS__.": Invalid path (".$path.")!");
	}
	/**
	 * Set css headers
	 */
	private function setHeaders()
	{
		if ($this->cache_mtime != null)
		{
			$etag = md5(serialize($this->getParams()));
			if (strtotime(dbdRequest::getHeader("if-modified-since")) >= $this->cache_mtime && dbdRequest::getHeader("if-none-match") == $etag)
			{
				header("HTTP/1.1 304 Not Modified");
				return false;
			}
			header("Last-Modified: ".gmdate("D, d M Y H:i:s", $this->cache_mtime)." GMT");
			header("ETag: ".$etag);
		}
		header("Content-type: text/css");
		return true;
	}
	/**#@-*/
	/**
	 * Set debug
	 */
	public function init()
	{
		$this->noRender();
		$this->debug = dbdMVC::debugMode(DBD_DEBUG_CSS);
		$this->vars = $this->getParams();
	}
	/**
	 * Serve multiple css files as one
	 */
	public function doCombine()
	{
		$files = $this->getParam("files");
		if (!is_array($files))
			$files = array($files);
		try
		{
			$cache = $this->checkCache();
			if (!$cache)
			{
				foreach ($files as $f)
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
		return dbdURI::create("dbdCSS", "combine", $vars);
	}
}
?>
