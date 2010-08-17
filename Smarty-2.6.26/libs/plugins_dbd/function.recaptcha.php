<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {recaptcha} function plugin
 *
 * Type:     function<br>
 * Name:     recaptcha<br>
 * Purpose:  create a captcha challenge form<br>
 * @link http://smarty.php.net/manual/en/language.function.recaptcha.php {recaptcha}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 * @return string html
 */
function smarty_function_recaptcha($params, &$smarty)
{
	if (!class_exists("reCaptcha"))
		$smarty->trigger_error("recaptcha: reCaptcha class could not be found");
	return reCaptcha::get(key_exists('ssl', $params) ? $params['ssl'] : false);
}

/* vim: set expandtab: */

?>
