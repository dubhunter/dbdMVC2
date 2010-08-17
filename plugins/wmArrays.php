<?php
/**
 * wmArrays.php :: Will Mason's array processing class
 *
 * @package dbdCommon
 * @version 1.0
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2007 by Don't Blink Design
 */
/**
 * The wmArrays class is used to preform complex routines on arrays.
 * @static
 * @package dbdCommon
 */
	class wmArrays
	{
		/**
		 * Searches through a multidimensional array for a given value
		 * and if found, returns an array of keys to its location.
		 * <i>Usage:</i> <code>
		 * $index = wmArrays::arraySearchRecursive($arr, "will@dontblinkdesign.com");
		 * </code>
		 * @static
		 * @param array $arr array to search through
		 * @param mixed $val value to search for in array
		 * @param mixed $key optional key to match value with
		 * @param int $max_depth optional maximum depth of recursion
		 * @return array indexe(s) of value
		 */
		public static function arraySearchRecursive($arr, $val, $key = false, $max_depth = -1)
		{
			$ret = array();
			if ($max_depth > -1)
			{
				if (count($ret) >= $max_depth)
					return $ret;
			}
			foreach ($arr as $k => $v)
			{
				if ((!$key || $k == $key) && $v == $val)
				{
					$ret[] = $k;
				}
				else if (is_array($v))
				{
					$res = self::arraySearchRecursive($v, $val, $key, $max_depth - 1);
					if (count($res) > 0)
						$ret = array_merge($ret, array($k), $res);
				}
			}
			return $ret;
		}

		/**
		 * Merges multidimentional arrays, and sums the integer values
		 * of fields in given list.
		 * <i>Usage:</i> <code>
		 * $arr1 = array(
		 * 		"page" => "index",
		 * 		"hits" => 10
		 * 		);
		 * $arr2 = array(
		 * 		"page" => "index",
		 * 		"hits" => 20
		 * 		);
		 * $marr = wmArrays::arrayMergeRecursiveSumFields($arr1, $arr2, array("hits"));
		 * //result
		 * $marr = array(
		 * 		"page" => "index",
		 * 		"hits" => 30
		 * 		);
		 * </code>
		 * @static
		 * @param array $arr1 first array
		 * @param array $arr2 second array
		 * @param array $fields list of string keys of values to sum
		 * @return array merged array
		 */
		public static function arrayMergeRecursiveSumFields($arr1, $arr2, $fields)
		{
			if (!is_array($arr1)) $arr1 = array();
			if (!is_array($arr2)) $arr2 = array();
			if (!is_array($fields)) $fields = array($fields);
			foreach ($arr1 as $key => $val)
			{
				if (is_array($val))
					$arr2[$key] = self::arrayMergeRecursiveSumFields($val, $arr2[$key], $fields);
				else if (in_array($key, $fields))
					$arr2[$key] += $val;
				else
					$arr2[$key] = $val;
			}
			return $arr2;
		}

		public static function arrayValuesRecursive($arr)
		{
			if (!is_array($arr))
				return array();
			$ret = array();
			foreach ($arr as $a)
			{
				if (is_array($a))
					$ret = array_merge($ret, self::arrayValuesRecursive($a));
				else
					$ret[] = $a;
			}
			return $ret;
		}

		public static function arrayRemoveValue($arr, $value)
		{
			if (!is_array($arr))
				return array();
			$arr2 = array();
			foreach ($arr as $a)
			{
				if ($a != $value)
					$arr2[] = $a;
			}
			return $arr2;
		}

		public static function pregExplode($pattern, $string, $limit = -1)
		{
			return preg_split($pattern, $string, $limit, PREG_SPLIT_NO_EMPTY);
		}

		public static function explodeNewline($string)
		{
			return self::pregExplode("/[\r\n]+/", trim($string));
		}

		public static function implodeObjArray($glue, $arr, $method)
		{
			if (count($arr) == 0 || !is_object($arr[0]) || !method_exists($arr[0], $method))
				return;
			$str = "";
			for ($i = 0; $i < count($arr); $i++)
			{
				if ($i > 0)
					$str .= $glue;
				$str .= call_user_func(array($arr[$i], $method));
			}
			return $str;
		}
	}
?>