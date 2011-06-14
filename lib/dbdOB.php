<?php
/**
 * dbdOB.php :: dbdOB Class File
 *
 * @package dbdMVC
 * @version 1.1
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * Output Buffering class that is non blocking once flushed.
 * @package dbdMVC
 */
class dbdOB
{
	/**
	 * Flag for if the OB has started
	 * @var boolean
	 */
	private static $started = false;
	/**
	 * Flag for if the OB has been flushed
	 * @var boolean
	 */
	private static $flushed = false;
	/**
	 * Start non blocking output buffering (will clear ALL current buffers in PHP)
	 */
	public static function start()
	{
		if (!self::$started)
		{
			//clear out existing buffers
			while(ob_get_level()) ob_end_clean();
			//tell the browser to close the connection when done
			header('Connection: close');
			//start buffer to catch gz'ed content
			ob_start();
			//start gz buffer
			ob_start('ob_gzhandler');
			self::$started = true;
		}
	}
	/**
	 * Flush output buffer with a Content-Length header
	 */
	public static function flush()
	{
		if (self::$started && !self::$flushed)
		{
			//calculate size of gz'ed content (flush the gz'ed buffer)
			ob_end_flush();
			$length = ob_get_length();
			if (preg_match('/Mozilla.*Firefox/i', dbdMVC::getRequest()->get('HTTP_USER_AGENT')))
				$length++;
			header('Content-Length: '.$length);
			//send to browser
			ob_end_flush();
			//for some reason we need both...
			flush();
			self::$flushed = true;
		}
	}
	/**
	 * End output buffering and clear the buffers
	 */
	public static function end()
	{
		if (self::$started)
		{
			ob_end_clean();
			ob_end_clean();
			self::$started = false;
		}
	}
}
?>