<?php
/**
 * dbdInfo.php :: dbdInfo Class File
 *
 * @package dbdMVC
 * @version 1.2
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * Controller for displaying phpInfo.
 * Use /dbdInfo/.
 * @package dbdMVC
 * @uses dbdController
 * @uses dbdMVC
 */
class dbdInfo extends dbdController
{
	/**
	 * Render phpinfo() if PHPisExposed or forward home.
	 */
	public function doDefault()
	{
		if (dbdMVC::PHPisExposed())
		{
			$this->noRender();
			phpinfo();
		}
		else
		{
			$this->forward();
		}
	}
	/**
	 * Render apc.php if PHPisExposed or forward home.
	 */
	public function doApc()
	{
		if (dbdMVC::PHPisExposed())
		{
			$this->noRender();
			$_SERVER['PHP_SELF'] = $this->getURL();
			dbdLoader::load("apc.php");
		}
		else
		{
			$this->forward();
		}
	}
}
?>