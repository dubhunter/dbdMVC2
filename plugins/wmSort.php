<?php
/**
 * wmSort.php :: Will Mason's array sorting class
 *
 * @package dbdCommon
 * @version 1.1
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2007 by Don't Blink Design
 */
/**
 * The wmSort class is used for advanced two-dimentional array sorting.
 * @static
 * @package dbdCommon
 */
	class wmSort
	{
		private static $sortby;
		/**
		 * Sorts a two-dimentional array by one to many key "indexes".
		 * <i>Usage:</i> <code>
		 * wmSort::multiSort($arr, "name:desc,date:asc");
		 * </code>
		 * @static
		 * @param array $arr reference to array to be sorted
		 * @param string $sortby key(s) to sort by and direction
		 */
		public static function multiSort(&$arr, $sortby)
		{
			self::$sortby = $sortby;
			usort($arr, array("wmSort", "_wmMultiSort"));
		}

		/**
		 * Sorts a two-dimentional array by one to many key "indexes"
		 * and maintains its associative indexes.
		 * <i>Usage:</i> <code>
		 * wmSort::multiASort($arr, "name:desc,date:asc");
		 * </code>
		 * @static
		 * @param array $arr reference to associative array to be sorted
		 * @param string $sortby key(s) to sort by and direction
		 */
		public static function multiASort(&$arr, $sortby)
		{
			self::$sortby = $sortby;
			uasort($arr, array("wmSort", "_wmMultiSort"));
		}

		/**
		 * Finds value for comparison.
		 * This method checks to see if the field given for sorting
		 * is an associative array, object, or string.
		 * @static
		 * @access private
		 * @param mixed $var reference to variable containing a comapriable value
		 * @param string $field array index, or object method name to get value for comparison
		 * @return mixed value
		 */
		private static function _getVal(&$var, $field)
		{
			switch (gettype($var))
			{
				case "array":
					if (isset($var[$field]))
						return $var[$field];
					else if (is_callable($field))
						return call_user_func($field, $var);
					else
						return 0;
				case "object":
					if (is_callable(array($var, $field)))
						return call_user_func(array(&$var, $field));
					else if (isset($var->$field))
						return $var->$field;
					else
						return 0;
				default:
					return $var;
			}
		}

		/**
		 * Callback function passed to usort() and uasort().
		 * This method does the actual value comparison.
		 * @static
		 * @access private
		 * @param mixed $var1 value of current array postion
		 * @param mixed $var2 value of next array postion
		 * @param string $sortby optional sortby argument
		 * @return int comparison result
		 */
		private static function _wmMultiSort(&$var1, &$var2, $sortby = false)
		{
			if (!$sortby) $sortby = self::$sortby;
			$sortbys = explode(",", $sortby);

			$tmp = explode(":", $sortbys[0]);
			$field = $tmp[0];
			$order = isset($tmp[1]) ? $tmp[1] : "";

			if ($order == "desc")
			{
				$v1 = self::_getVal($var2, $field);
				$v2 = self::_getVal($var1, $field);
			}
			else
			{
				$v1 = self::_getVal($var1, $field);
				$v2 = self::_getVal($var2, $field);
			}

			$v1 = strtolower($v1);
			$v2 = strtolower($v2);

			if ($v1 > $v2)
			{
				return 1;
			}
			else if ($v1 < $v2)
			{
				return -1;
			}
			else
			{
				if (!empty($sortbys[1]))
					return self::_wmMultiSort($var1, $var2, str_replace($sortbys[0].",", "", $sortby));
				return 0;
			}
		}
	}
?>