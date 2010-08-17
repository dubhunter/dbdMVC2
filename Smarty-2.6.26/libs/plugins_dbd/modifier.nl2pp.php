<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty plugin
 *
 * Type:     modifier<br>
 * Name:     nl2pp<br>
 * Date:     Feb 26, 2003
 * Purpose:  convert \r\n, \r or \n to </p><p>
 * Input:<br>
 *         - contents = contents to replace
 * Example:  {$text|nl2pp}
 * @link http://smarty.php.net/manual/en/language.modifier.nl2pp.php
 *          nl2pp (Smarty online manual)
 * @version  1.0
 * @author   Will Mason <will at dontblinkdesign dot com>
 * @param string
 * @return string
 */
function smarty_modifier_nl2pp($string)
{
	return preg_replace('/\r?\n/', '</p><p>', $string);
}

/* vim: set expandtab: */

?>
