<?php
/**
 * dbdError.php :: dbdError Class File
 *
 * @package dbdMVC
 * @version 1.2
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2009 by Don't Blink Design
 */

/**
 * Error handling controller class.
 * @package dbdMVC
 * @uses dbdController
 */
class dbdError extends dbdController
{
	/**
	 * HTTP Status 400 Description
	 */
	const ERR_400 = "Bad Request";
	/**
	 * HTTP Status 401 Description
	 */
	const ERR_401 = "Unauthorized";
	/**
	 * HTTP Status 403 Description
	 */
	const ERR_403 = "Forbidden";
	/**
	 * HTTP Status 404 Description
	 */
	const ERR_404 = "Not Found";
	/**
	 * HTTP Status 408 Description
	 */
	const ERR_408 = "Request Timeout";
	/**
	 * HTTP Status 500 Description
	 */
	const ERR_500 = "Internal Server Error";
	/**
	 * Attept to render error.tpl or just echo error.
	 */
	public function doDefault()
	{
		self::doError($this);
	}
	/**
	 * Crazy static action method expects intstance of dbdController for dbdError extension.
	 * @param dbdController $that
	 * @static
	 */
	public static function doError(dbdController $that)
	{
		$code = $that->getParam("code");
		$msg = nl2br(urldecode($that->getParam("msg")));
		switch ($code)
		{
			case 400:
				$name = self::ERR_400;
				break;
			case 401:
				$name = self::ERR_401;
				break;
			case 403:
				$name = self::ERR_403;
				break;
			case 404:
				$name = self::ERR_404;
				break;
			case 408:
				$name = self::ERR_408;
				break;
			case 500:
			default:
				$code = 500;
				$name = self::ERR_500;
		}
		header("HTTP/1.1 ".$code." ".$name);
		if ($that->view->template_exists("error.tpl"))
		{
			$that->setTemplate("error.tpl");
			$that->view->assign("code", $code);
			$that->view->assign("name", $name);
			$that->view->assign("msg", $msg);
		}
		else
		{
			error_log("dbdMVC: error.tpl was not found!");
			$that->noRender();
			echo "<h1>".$code." - ".$name."</h1>";
			echo "<h2>".$msg."</h2>";
		}
		return $name;
	}
}
?>