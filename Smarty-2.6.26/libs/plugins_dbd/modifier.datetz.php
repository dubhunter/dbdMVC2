<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
/**
 * Smarty date modifier plugin
 *
 * Type:     modifier<br>
 * Name:     datetz<br>
 * Purpose:  format datestamps via DateTime with the timezone<br>
 * Input:<br>
 *         - string: input date string
 *         - format: date format for output
 *         - timezone: timezone for return, default is used if omitted
 * @link http://smarty.php.net/manual/en/language.modifier.datetz.php
 *          date (Smarty online manual)
 * @author   Will Mason <will at dontblinkdesign dot com>
 * @param string
 * @param string
 * @param string
 * @return string|void
 */
function smarty_modifier_datetz($string, $format = 'm/j/y', $timezone = '')
{
	if (is_int($string)) {
		$D = new DateTime();
		$D->setTimestamp($string);
	}
	else {
		$D = new DateTime($string);
	}
	if ($timezone != '')
		$D->setTimezone(new DateTimeZone($timezone));
    return $D->format($format);
}

/* vim: set expandtab: */

?>
