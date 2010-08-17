<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {dbdcss} function plugin
 *
 * Type:     function<br>
 * Name:     dbdcss<br>
 * Purpose:  split a string into an array and assign it to the template<br>
 * @link http://smarty.php.net/manual/en/language.function.dbdcss.php {dbdcss}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 * @return string uri
 */
function smarty_function_dbdcss($params, &$smarty)
{
	foreach ($params as $key => $value)
	{
		switch ($key)
		{
			case 'files':
				$files = array();
				$files = $value;
				if (!is_array($files))
				{
					$tmp = explode(",", $files);
					$files = array();
					foreach ($tmp as $f)
						$files[] = $f;
				}
				$smarty->addCss($files);
				break;
			case 'host':
				$smarty->setCssHost($value);
				break;
			default:
				$smarty->trigger_error("dbdcss: '".$key."' is not a valid parameter");
		}
	}
}

/* vim: set expandtab: */

?>
