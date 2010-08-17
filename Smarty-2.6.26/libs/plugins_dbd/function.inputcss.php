<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {inputcss} function plugin
 *
 * Type:     function<br>
 * Name:     inputcss<br>
 * Purpose:  Smarty wrapper to InputCSS class for creating complex and stylable XHTML input tags<br>
 * @link http://smarty.php.net/manual/en/language.function.dbduri.php {dbduri}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 * @return string xhtml
 */
function smarty_function_inputcss($params, &$smarty)
{
	if (!class_exists("InputCSS"))
		$smarty->trigger_error("inputcss: InputCSS class could not be found");

	if (!isset($params['id']))
	{
        $smarty->trigger_error("inputcss: missing 'id' parameter");
        return;
	}
	if (!isset($params['name']))
	{
        $smarty->trigger_error("inputcss: missing 'name' parameter");
        return;
	}
	if (!isset($params['type']))
	{
        $smarty->trigger_error("inputcss: missing 'type' parameter");
        return;
	}
	if (isset($params['tabindex']))
	{
		$params['extra'] .= " tabindex=\"".$params['tabindex']."\"";
	}
	switch ($params['type'])
	{
		case 'submit':
		case 'button':
			return InputCSS::button($params['id'], $params['name'], $params['value'], $params['disabled'], $params['extra']);
		case 'reset':
			return InputCSS::reset($params['id'], $params['name'], $params['value'], $params['disabled'], $params['extra']);
		case 'text':
			return InputCSS::text($params['id'], $params['name'], $params['disabled'], $params['extra']);
		case 'file':
			return InputCSS::file($params['id'], $params['name'], $params['disabled'], $params['extra']);
		case 'checkbox':
			return InputCSS::checkbox($params['id'], $params['name'], $params['value'], $params['disabled'], $params['checked'], $params['extra']);
		case 'radio':
			return InputCSS::radio($params['id'], $params['name'], $params['value'], $params['disabled'], $params['checked'], $params['extra']);
		default:
			$smarty->trigger_error("inputcss: type '".$params['type']."' is not valid");
	}
}

/* vim: set expandtab: */

?>
