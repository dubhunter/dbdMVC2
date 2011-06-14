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
 * Purpose:  encode a variable in JSON<br>
 * @link http://smarty.php.net/manual/en/language.function.json.php {explode}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 */
function smarty_function_json($params, &$smarty)
{
    if (!isset($params['value'])) {
        $smarty->trigger_error("json: missing 'value' parameter");
        return;
    }
    return json_encode($params['value']);
}

/* vim: set expandtab: */

?>
