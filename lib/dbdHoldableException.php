<?php
/**
 * dbdHoldableException.php :: dbdHoldableException Class File
 *
 * @package dbdMVC
 * @version 1.1
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * dbdMVC Exception class.
 * @package dbdMVC
 */
class dbdHoldableException extends dbdException
{
	/**
	 * Flag holding status
	 * @staticvar boolean
	 */
	protected static $hold = false;
	/**
	 * Array of held exceptions
	 * @staticvar array dbdHoldableException
	 */
	protected static $held = array();
	/**
	 * Enable holding
	 * @static
	 */
	public static function hold()
	{
		if (self::$hold === false)
		{
			self::$hold = true;
			self::$held = array();
			set_exception_handler(array(get_class(), "intercept"));
		}
	}
	/**
	 * Exception handler and method to call when ready to throw
	 * @param dbdHoldableException $e
	 * @throws dbdHoldableException
	 * @static
	 */
	public static function intercept(Exception $e)
	{
		if ($e instanceof dbdHoldableException && self::$hold === true)
			self::$held[] = $e;
		else
			throw $e;
	}
	/**
	 * Disable holding and throw the first held excetion, if any
	 * @throws dbdHoldableException
	 * @static
	 */
	public static function release()
	{
		if (self::$hold === true)
		{
			self::$hold = false;
			restore_exception_handler();
			if (count(self::$held) > 0)
				throw self::$held[0];
		}
	}
	/**
	 * Get array of held exceptions
	 * @static
	 * @return dbdHoldableException[]
	 */
	public static function getHeld()
	{
		return self::$held;
	}
}
