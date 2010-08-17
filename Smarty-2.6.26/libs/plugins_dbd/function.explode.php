<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {explode} function plugin
 *
 * Type:     function<br>
 * Name:     explode<br>
 * Purpose:  split a string into an array and assign it to the template<br>
 * @link http://smarty.php.net/manual/en/language.function.explode.php {explode}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 */
function smarty_function_explode($params, &$smarty)
{
    if (!isset($params['subject'])) {
        $smarty->trigger_error("explode: missing 'subject' parameter");
        return;
    }

    if (!isset($params['search'])) {
        $smarty->trigger_error("explode: missing 'search' parameter");
        return;
    }

    if (!isset($params['assign'])) {
        $smarty->trigger_error("explode: missing 'assign' parameter");
        return;
    }

    if (isset($params['limit']))
	    $array = explode($params['search'], $params['subject'], $params['limit']);
	else
    	$array = explode($params['search'], $params['subject']);
    $smarty->assign($params['assign'], $array);
}

/* vim: set expandtab: */

?>
