<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {flashmovie} function plugin
 *
 * Type:     function<br>
 * Name:     flashmovie<br>
 * Purpose:  assign a flash movie to the page<br>
 * @link http://smarty.php.net/manual/en/language.function.flashmovie.php {flashmovie}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 * @return string uri
 */
function smarty_function_flashmovie($params, &$smarty)
{
	$a = array();
	foreach ($params as $key => $value)
		$a[] = $key."=".$value;
	call_user_func_array(array($smarty, 'addFlashMovie'), $a);
}

/* vim: set expandtab: */

?>
