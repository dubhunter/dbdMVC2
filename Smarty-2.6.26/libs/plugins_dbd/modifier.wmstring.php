<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty wmString modifier plugin
 *
 * Type:     modifier<br>
 * Name:     wmString<br>
 * Purpose:  Allow access to the methods of wmString
 * @link http://smarty.php.net/manual/en/language.modifier.lower.php
 *          lower (Smarty online manual)
 * @author   Will Mason <monte at ohrt dot com>
 * @param string
 * @param string
 * @return string
 */
function smarty_modifier_wmstring($string, $method = null, $arg = null)
{
	if (!class_exists("wmString") || !method_exists(wmString, $method))
		return $string;
    return $arg === null ? wmString::$method($string) : wmString::$method($string, $arg);
}

?>
