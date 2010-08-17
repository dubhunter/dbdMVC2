<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {dbdjs} function plugin
 *
 * Type:     function<br>
 * Name:     dbdjs<br>
 * Purpose:  split a string into an array and assign it to the template<br>
 * @link http://smarty.php.net/manual/en/language.function.dbdjs.php {dbdjs}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
 * @return string uri
 */
function smarty_function_dbdjs($params, &$smarty)
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
				$smarty->addJS($files);
				break;
			case 'host':
				$smarty->setJsHost($value);
				break;
			default:
				$smarty->trigger_error("dbdjs: '".$key."' is not a valid parameter");
		}
	}
}

/* vim: set expandtab: */

?>
