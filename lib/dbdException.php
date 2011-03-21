<?php
/**
 * dbdException.php :: dbdException Class File
 *
 * @package dbdMVC
 * @version 1.3
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * dbdMVC Exception class.
 * @package dbdMVC
 */
class dbdException extends Exception
{
	/**
	 * Pass paremeters on to parent exception with prefixed message.
	 * @param string $message
	 * @param integer $code
	 */
	public function __construct($message = null, $code = 500)
	{
		parent::__construct($message, $code);
	}
}
?>