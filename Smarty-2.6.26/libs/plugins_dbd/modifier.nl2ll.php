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
 * Name:     nl2ll<br>
 * Date:     Feb 26, 2003
 * Purpose:  convert \r\n, \r or \n to <</li><li>>
 * Input:<br>
 *         - contents = contents to replace
 * Example:  {$text|nl2ll}
 * @link http://smarty.php.net/manual/en/language.modifier.nl2ll.php
 *          nl2ll (Smarty online manual)
 * @version  1.0
 * @author   Will Mason <will at dontblinkdesign dot com>
 * @param string
 * @return string
 */
function smarty_modifier_nl2ll($string)
{
	return preg_replace('/\r?\n/', '</li><li>', $string);
}

/* vim: set expandtab: */

?>
