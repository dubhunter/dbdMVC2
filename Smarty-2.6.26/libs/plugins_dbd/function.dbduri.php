<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {dbduri} function plugin
 *
 * Type:     function<br>
 * Name:     dbduri<br>
 * Purpose:  split a string into an array and assign it to the template<br>
 * @link http://smarty.php.net/manual/en/language.function.dbduri.php {dbduri}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 * @return string uri
 */
function smarty_function_dbduri($params, &$smarty)
{
	if (!class_exists("dbdURI"))
		$smarty->trigger_error("dbduri: dbdURI class could not be found");
	$replace = null;
	$controller = null;
	$action = null;
	$args = array();

	foreach ($params as $key => $value)
	{
		switch ($key)
		{
			case 'r':
			case 'replace':
				$replace = $value;
				break;
			case 'c':
			case 'controller':
				$controller = $value;
				break;
			case 'a':
			case 'action':
				$action = $value;
				break;
			case 'p':
			case 'params':
				$args = $value;
				if (!is_array($args))
				{
					$tmp = explode(",", $args);
					$args = array();
					dbdURI::set("");
					for ($i = 0; $i < count($tmp); $i += 2)
						dbdURI::setParam($tmp[$i], $tmp[$i + 1]);
					$args = dbdURI::getParams();
				}
				break;
			default:
				$smarty->trigger_error("dbduri: '".$key."' is not a valid parameter");
		}
	}
	if ($replace !== null)
		return dbdURI::replace($replace, $controller, $action, $args);
	else
		return dbdURI::create($controller, $action, $args);
}

/* vim: set expandtab: */

?>
