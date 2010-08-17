<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty number_format modifier plugin
 *
 * Type:     modifier<br>
 * Name:     number_format<br>
 * Purpose:  format strings via sprintf
 * @link http://smarty.php.net/manual/en/language.modifier.string.format.php
 *          number_format (Smarty online manual)
 * @author   Will Mason <will at dontblinkdesign dot com>
 * @param float
 * @param intiger
 * @param string
 * @param string
 * @return string
 */
function smarty_modifier_number_format($num, $decimals = null, $dec_point = null, $thousands_seperator = null)
{
    return $dec_point !== null && $thousands_seperator !== null ? number_format($num, $decimals, $dec_point, $thousands_seperator) : number_format($num, $decimals);
}

/* vim: set expandtab: */

?>
