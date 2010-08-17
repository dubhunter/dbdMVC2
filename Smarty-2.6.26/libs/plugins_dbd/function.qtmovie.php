<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {qtmovie} function plugin
 *
 * Type:     function<br>
 * Name:     qtmovie<br>
 * Purpose:  assign a quicktime movie to the page<br>
 * @link http://smarty.php.net/manual/en/language.function.qtmovie.php {qtmovie}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 */
function smarty_function_qtmovie($params, &$smarty)
{
	$a = array();
	foreach ($params as $key => $value)
		$a[] = $key."=".$value;
	call_user_func_array(array($smarty, 'addQTMovie'), $a);
}

/* vim: set expandtab: */

?>
