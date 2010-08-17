<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {inlineimage} function plugin
 *
 * Type:     function<br>
 * Name:     inlineimage<br>
 * Purpose:  return the base64 encoded image for use inline<br>
 * @link http://smarty.php.net/manual/en/language.function.inlineimage.php {inlineimage}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 */
function smarty_function_inlineimage($params, &$smarty)
{
    if (!isset($params['path']) && !isset($params['src'])) {
        $smarty->trigger_error("inlineimage: missing 'path' parameter");
        return;
    }

    $path = $params['path'] ? $params['path'] : $params['src'];

    if (strpos($path, "http") === false) {
    	$path = $_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.$path;
    }

    if (!file_exists($path)) {
        $smarty->trigger_error("inlineimage: file '".$path."' could not be found!");
        return;
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return "data:image/".$ext.";base64,".base64_encode(file_get_contents($path));
}

/* vim: set expandtab: */

?>
