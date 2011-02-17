<?php
/**
 * wmString.php :: Will Mason's string manipulation class
 *
 * @package dbdCommon
 * @version 1.5
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2007 by Don't Blink Design
 */
/**
 * The wmString class is used to manipulate strings in various ways.
 * @static
 * @package dbdCommon
 */
class wmString
{
	const TIME_LEN_TYPE_SEC = 0;
	const TIME_LEN_TYPE_MIN = 1;
	const TIME_LEN_TYPE_HRS = 2;
	const TIME_LEN_TYPE_DAY = 3;
	const TIME_LEN_TYPE_WEEK = 4;
	const TIME_LEN_TYPE_MON = 5;
	const TIME_LEN_TYPE_YEAR = 6;
	const TIME_LEN_TIME_SEC = 1;
	const TIME_LEN_TIME_MIN = 60;
	const TIME_LEN_TIME_HRS = 3600;
	const TIME_LEN_TIME_DAY = 86400;
	const TIME_LEN_TIME_WEEK = 604800;
	const TIME_LEN_TIME_MON = 2592000;
	const TIME_LEN_TIME_YEAR = 31536000;
	const TIME_LEN_LABEL_SEC = "Second";
	const TIME_LEN_LABEL_SEC_SHORT = "Sec";
	const TIME_LEN_LABEL_MIN = "Minute";
	const TIME_LEN_LABEL_MIN_SHORT = "Min";
	const TIME_LEN_LABEL_HRS = "Hour";
	const TIME_LEN_LABEL_HRS_SHORT = "Hr";
	const TIME_LEN_LABEL_DAY = "Day";
	const TIME_LEN_LABEL_DAY_SHORT = "Day";
	const TIME_LEN_LABEL_WEEK = "Week";
	const TIME_LEN_LABEL_WEEK_SHORT = "Wk";
	const TIME_LEN_LABEL_MON = "Month";
	const TIME_LEN_LABEL_MON_SHORT = "Mon";
	const TIME_LEN_LABEL_YEAR = "Year";
	const TIME_LEN_LABEL_YEAR_SHORT = "Yr";
	const TIME_LEN_LABEL_LAST = "Last";
	const TIME_LEN_LABEL_AGO = "Ago";
	const TIME_LEN_LABEL_YESTERDAY = "Yesterday";
	const TIME_LEN_LABEL_TODAY = "Today";
	const TIME_LEN_FORMAT_SHORT = 0;
	const TIME_LEN_FORMAT_LONG = 1;
	const VALID_EMAIL = '/[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i';
	const VALID_DATE = '/\\A(?:^((\\d{2}(([02468][048])|([13579][26]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])))))|(\\d{2}(([02468][1235679])|([13579][01345789]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|(1[0-9])|(2[0-8]))))))(\\s(((0?[0-9])|(1[0-9])|(2[0-3]))\\:([0-5][0-9])((\\s)|(\\:([0-5][0-9])))?))?$)\\z/';
	const STRIP_HTML_FIND = '%<[^>]+>%';
	const STRIP_HTML_REPLACE = '';
	private static $random_word_cons = array('b', 'd', 'f', 'g', 'h', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'z', 'ng', 'ch', 'ty', 'ny', 'gy', 'py', 'by');
	private static $random_word_vowl = array('a', 'e', 'i', 'o', 'u');
	private static $number_suffixes = array('th', 'st', 'nd', 'rd');
	private static $plural_exceptions = array('pain');
	/**
	 * Takes a string (mailto:email@domain.com) and converts it to unicode
	 * to protect it from spiders.
	 * <i>Usage:</i> <code>
	 * $email = wmString::emailEncode("mailto:email@domain.com");
	 * </code>
	 * @static
	 * @param string $str email address and/or mailto
	 * @return string encoded string
	 */
	public static function emailEncode($str)
	{
		$str2 = "";
		$n = strlen($str);
		for ($i = 0; $i < $n; $i++)
			$str2 .= "&#".ord($str[$i]).";";
		return $str2;
	}
	/**
	 * Takes a string and converts to the singular version.
	 * @static
	 * @param string $str
	 * @return string singular string
	 */
	public static function makeSingular($str)
	{
		if (strpos($str, "s", strlen($str) - 1) !== false)
		{
			$str = substr($str, 0, -1);
			if (strpos($str, "ie", strlen($str) - 2) !== false)
				$str = substr($str, 0, -2)."y";
		}
		return $str;
	}
	/**
	 * Takes a string and converts to the plural version.
	 * @static
	 * @param string $str
	 * @return string plural string
	 */
	public static function makePlural($str)
	{
		if (in_array(strtolower($str), self::$plural_exceptions))
			return $str;
		if (strpos($str, "s", strlen($str) - 1) !== false)
			$str .= "e";
		elseif (strpos($str, "y", strlen($str) - 1) !== false)
			$str = substr($str, 0, -1)."ie";
		return $str."s";
	}
	/**
	 * Takes a super capped string (SuperCapString) and adds spaces
	 * @static
	 * @param string $str
	 * @return string spaced string
	 */
	public static function superCapAddSpaces($str)
	{
		$regex = "/([^A-Z]{1})([A-Z]{1})/";
		$replace = "$1 $2";
		return preg_replace($regex, $replace, $str);
	}
	/**
	 * Takes a super capped string (SuperCapString) and adds underscores and makes lowercase
	 * @static
	 * @param string $str
	 * @return string
	 */
	public static function superCap2UnderLower($str)
	{
		$str = self::superCapAddSpaces($str);
		return str_replace(" ", "_", strtolower($str));
	}
	/**
	 * Takes an underscored lower case string (under_lower_string) converts it to SuperCaps
	 * @static
	 * @param string $str
	 * @return string
	 */
	public static function underLower2SuperCap($str)
	{
		$str = str_replace("_", " ", $str);
		$str = ucwords($str);
		return str_replace(" ", "", $str);
	}
	/**
	 * Turns the first characher of a string to lower case
	 * @static
	 * @param string $str
	 * @return string
	 */
	public static function lcFirst($str)
	{
		$regex = "/^([A-Z]{1})(.*)$/e";
		$replace = "strtolower('$1').'$2'";
		return preg_replace($regex, $replace, $str);
	}
	/**
	 * Escapes special characters for use in javascript function calls.
	 * @static
	 * @param string $str
	 * @return string
	 */
	public static function jsSpecialChars($str)
	{
		$regex = '/([^ !#$%@()*+,-.\x30-\x5b\x5d-\x7e\x90-\xff])/e';
		$replace = "'\\x'.(ord('\\1')<16? '0': '').dechex(ord('\\1'))";
	    return preg_replace($regex, $replace, $str);
	}
	/**
	 * Format a length of time.
	 * @static
	 * @param integer $time
	 * @param integer $type_in
	 * @param integer $type_out
	 * @param integer $format
	 * @return string
	 */
	public static function timeLengthFormat($time, $type_in = self::TIME_LEN_TYPE_SEC, $type_out = self::TIME_LEN_TYPE_SEC, $format = self::TIME_LEN_FORMAT_LONG)
	{
		$ret = "";
		switch ($type_in)
		{
			case self::TIME_LEN_TYPE_DAY:
				$time *= 24;
			case self::TIME_LEN_TYPE_HRS:
				$time *= 60;
			case self::TIME_LEN_TYPE_MIN:
				$time *= 60;
		}
		switch ($type_out)
		{
			case self::TIME_LEN_TYPE_DAY:
				$tmp = floor($time / self::TIME_LEN_TIME_DAY);
				if ($tmp > 0)
					$ret .= $tmp." ".($format == self::TIME_LEN_FORMAT_SHORT ? substr(self::TIME_LEN_LABEL_DAY, 0, 1) : self::TIME_LEN_LABEL_DAY.($tmp > 1 ? "s" : ""))." ";
				$time -= $tmp * self::TIME_LEN_TIME_DAY;
			case self::TIME_LEN_TYPE_HRS:
				$tmp = floor($time / self::TIME_LEN_TIME_HRS);
				if ($tmp > 0)
					$ret .= $tmp." ".($format == self::TIME_LEN_FORMAT_SHORT ? substr(self::TIME_LEN_LABEL_HRS, 0, 1) : self::TIME_LEN_LABEL_HRS.($tmp > 1 ? "s" : ""))." ";
				$time -= $tmp * self::TIME_LEN_TIME_HRS;
			case self::TIME_LEN_TYPE_MIN:
				$tmp = floor($time / self::TIME_LEN_TIME_MIN);
				if ($tmp > 0)
					$ret .= $tmp." ".($format == self::TIME_LEN_FORMAT_SHORT ? substr(self::TIME_LEN_LABEL_MIN, 0, 1) : self::TIME_LEN_LABEL_MIN.($tmp > 1 ? "s" : ""))." ";
				$time -= $tmp * self::TIME_LEN_TIME_MIN;
			case self::TIME_LEN_TYPE_SEC:
				$tmp = $time;
				if ($tmp > 0)
					$ret .= $tmp." ".($format == self::TIME_LEN_FORMAT_SHORT ? substr(self::TIME_LEN_LABEL_SEC, 0, 1) : self::TIME_LEN_LABEL_SEC.($tmp > 1 ? "s" : ""))." ";
		}
		return trim($ret);
	}

	/**
	 * Format the abount of time past.
	 * @static
	 * @param mixed $date
	 * @return string
	 */
	public static function timePastFormat($date, $short_names = false, $use_today = false)
	{
		$time = strtotime($date);
		$diff = time() - $time;
		if (floor($diff / self::TIME_LEN_TIME_DAY) > 0)
		{
			$time = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));
			$diff = time() - $time;
		}
		switch (true)
		{
			case (($n = floor($diff / self::TIME_LEN_TIME_YEAR)) > 0):
				return $n > 1 ? $n." ".($short_names ? self::TIME_LEN_LABEL_YEAR_SHORT : self::TIME_LEN_LABEL_YEAR)."s ".self::TIME_LEN_LABEL_AGO : self::TIME_LEN_LABEL_LAST." ".self::TIME_LEN_LABEL_YEAR;
			case (($n = floor($diff / self::TIME_LEN_TIME_MON)) > 0):
				return $n > 1 ? $n." ".($short_names ? self::TIME_LEN_LABEL_MON_SHORT : self::TIME_LEN_LABEL_MON)."s ".self::TIME_LEN_LABEL_AGO : self::TIME_LEN_LABEL_LAST." ".self::TIME_LEN_LABEL_MON;
			case (($n = floor($diff / self::TIME_LEN_TIME_WEEK)) > 0):
				return $n > 1 ? $n." ".($short_names ? self::TIME_LEN_LABEL_WEEK_SHORT : self::TIME_LEN_LABEL_WEEK)."s ".self::TIME_LEN_LABEL_AGO : self::TIME_LEN_LABEL_LAST." ".self::TIME_LEN_LABEL_WEEK;
			case (($n = floor($diff / self::TIME_LEN_TIME_DAY)) > 0):
				return $n > 1 ? $n." ".($short_names ? self::TIME_LEN_LABEL_DAY_SHORT : self::TIME_LEN_LABEL_DAY)."s ".self::TIME_LEN_LABEL_AGO : self::TIME_LEN_LABEL_YESTERDAY;
			case $use_today:
				return self::TIME_LEN_LABEL_TODAY;
			case (($n = floor($diff / self::TIME_LEN_TIME_HRS)) > 0):
				return $n." ".($short_names ? self::TIME_LEN_LABEL_HRS_SHORT : self::TIME_LEN_LABEL_HRS).($n > 1 ? "s" : "")." ".self::TIME_LEN_LABEL_AGO;
			case (($n = floor($diff / self::TIME_LEN_TIME_MIN)) > 0):
				return $n." ".($short_names ? self::TIME_LEN_LABEL_MIN_SHORT : self::TIME_LEN_LABEL_MIN).($n > 1 ? "s" : "")." ".self::TIME_LEN_LABEL_AGO;
			default:
				return floor($diff / self::TIME_LEN_TIME_SEC)." ".($short_names ? self::TIME_LEN_LABEL_SEC_SHORT : self::TIME_LEN_LABEL_SEC).($n > 1 ? "s" : "")." ".self::TIME_LEN_LABEL_AGO;
		}
	}

	/**
	 * Format the amount of time in seconds to hh::mm:ss
	 * @static
	 * @param mixed $seconds
	 * @return string
	 */
	public static function timeFormat($seconds)
	{
		$h = floor($seconds / (60 * 60));
		$seconds -= $h * 60 * 60;
		$m = floor($seconds / 60);
		$seconds -= $m * 60;
		if (strlen($h) < 2)
			$h = "0".$h;
		if (strlen($m) < 2)
			$m = "0".$m;
		if (strlen($seconds) < 2)
			$seconds = "0".$seconds;
		return $h.":".$m.":".$seconds;
	}
	/**
	 * Give the day of week given day number 0-6.
	 * @static
	 * @param integer $day_num
	 * @param string $format_string
	 * @return string
	 */
	public static function dayOfWeek($day_num = 0, $format_string = "l")
	{
		if ($day_num < 0)
			$day_num = 0;
		if ($day_num > 6)
			$day_num = 6;
		if ($format_string != "l" && $format_string != "D")
			$format_string = "l";
		return gmdate($format_string, strtotime("+".($day_num + 3)." days", 0));
	}
	/**
	 * Give the month name given month number 1-12.
	 * @static
	 * @param integer $month_num
	 * @param string $format_string
	 * @return string
	 */
	public static function monthName($month_num = 0, $format_string = "F")
	{
		if ($month_num < 1)
			$month_num = 1;
		if ($month_num > 12)
			$month_num = 12;
		if ($format_string != "F" && $format_string != "M")
			$format_string = "F";
		return gmdate($format_string, strtotime("+".($month_num - 1)." months 3 days", 0));
	}
	/**
	 * Give the year for the given years ago.
	 * @static
	 * @param integer $month_num
	 * @param string $format_string
	 * @return string
	 */
	public static function yearsAgo($years_ago = 0, $format_string = "Y")
	{
		if ($format_string != "Y" && $format_string != "y")
			$format_string = "y";
		return date($format_string, strtotime($years_ago." years ago"));
	}
	/**
	 * Get the unix timestamp of Hanukkah for this year.
	 * @static
	 * @param integer $year
	 * @return integer
	 */
	public static function hanukkahStart($year = null)
	{
		$reference = '12/1/'.($year !== null ? $year : date('Y'));
		list($month, $day, $year) = explode("/", jdtojewish(unixtojd(strtotime($reference))));
		return jdtounix(jewishtojd(3, 25, $year));
	}
	/**
	 * Add suffixes to numbers.
	 * @static
	 * @param mixed $num
	 * @return string
	 */
	public static function numberSuffix($num)
	{
		if (!is_numeric($num))
		{
			return preg_replace('/(\d+)/e', "wmString::numberSuffix('\\1')", $num);
		}
		else
		{
			$i = intval(($num > 10 && $num < 20) ? 0 : $num) % 10;
			if ($i > 3)
				$i = 0;
			$num .= self::$number_suffixes[$i];
			return $num;
		}
	}
	/**
	 * Generate a random readable word.
	 * @static
	 * @param integer $length
	 * @return string
	 */
	public static function randomWord($length = 5)
	{
        $word = "";
        while (strlen($word) < $length + 18)
        {
            srand((double)microtime() * 1000000);
            $word .= sprintf("%s", self::$random_word_cons[array_rand(self::$random_word_cons)]);
            $word .= sprintf("%s", self::$random_word_vowl[array_rand(self::$random_word_vowl)]);
        }
        return substr($word, rand(0, 9), $length);
	}
	/**
	 * Truncate a string...
	 * @static
	 * @param string $str
	 * @param integer $len
	 * @param string $etc
	 * @param boolean $break_words
	 * @param boolean $middle
	 * @return string
	 */
	public static function truncate($str, $len = 80, $etc = "...", $break_words = false, $middle = false)
	{
	    if ($len == 0)
	        return '';
		if (strlen($str) > $len)
		{
	        if (!$break_words && !$middle)
	            $str = preg_replace('/\s+?(\S+)?$/', '', substr($str, 0, $len + 1));
	        if(!$middle)
	            return substr($str, 0, $len).$etc;
	        else
	            return substr($str, 0, $len / 2).$etc.substr($str, -$len / 2);
		}
		return $str;
	}
	/**
	 * Strip all HTML like tags
	 * @static
	 * @param string $string
	 * @return return
	 */
	public static function stripHtml($string)
	{
		return preg_replace(self::STRIP_HTML_FIND, self::STRIP_HTML_REPLACE, $string);
	}
	/**
	 * Test if an email address is valid.
	 * @static
	 * @param string $email
	 * @return boolean
	 */
	public static function emailValid($email)
	{
		return preg_match(self::VALID_EMAIL, $email) ? true : false;
	}
	/**
	 * Test if a date is valid.
	 * @static
	 * @param string $date
	 * @return boolean
	 */
	public static function dateValid($date)
	{
		return preg_match(self::VALID_DATE, $date) ? true : false;
	}
	/**
	 * Convert WKT to KML.
	 * @static
	 * @param string $wkt
	 * @param string $name
	 * @param string $desc
	 * @param string $style_id
	 * @return string
	 */
	public static function wkt2kml($wkt, $name = false, $desc = false, $style_id = false)
	{
		$wkt = preg_replace("/([0-9\.\-]+) ([0-9\.\-]+),*/", "$1,$2,0 ", $wkt);
		$wkt = substr($wkt, 15);
		$wkt = substr($wkt, 0, -3);
		$kml = "";
		$kml .= "<Placemark>\n";
		$kml .= "<MultiGeometry>\n";
		if ($name)
			$kml .= "<name>".$name."</name>\n";
		if ($desc)
			$kml .= "<description><![CDATA[\n".$desc."\n]]></description>";
		if ($style_id)
			$kml .= "<styleUrl>#".$style_id."</styleUrl>\n";
		foreach (preg_split("/[)]{2},[(]{2}/", $wkt) as $p)
		{
			$b = preg_split("/[)],[(]/", $p);
			$kml .= "<Polygon>\n";
			$kml .= "<outerBoundaryIs>\n";
			$kml .= "<LinearRing>\n";
			$kml .= "<coordinates> ".$b[0]."</coordinates>\n";
			$kml .= "</LinearRing>\n";
			$kml .= "</outerBoundaryIs>\n";
			for ($i = 1; $i < count($b); $i++)
			{
				$kml .= "<innerBoundaryIs>\n";
				$kml .= "<LinearRing>\n";
				$kml .= "<coordinates>".$b[$i]."</coordinates>\n";
				$kml .= "</LinearRing>\n";
				$kml .= "</innerBoundaryIs>\n";
			}
			$kml .= "</Polygon>\n";
		}
		$kml .= "</MultiGeometry>\n";
		$kml .= "</Placemark>\n";
		return $kml;
	}
}
?>