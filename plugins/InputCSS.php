<?php
/**
 * InputCSS.php :: The Fancy XHTML/CSS Form Input Class
 *
 * @package dbdCommon
 * @version 1.6
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2009 by Don't Blink Design
 */
/**
 * The InputCSS Class is to generate the markup
 * and JQuery calls to make a fancy CSS form input.
 * @package dbdCommon
 */
class InputCSS
{
	/**
	 * Static value to increment to keep track of divs.
	 * @static
	 * @access private
	 * @var int
	 */
	private static $div_id = 0;
	/**
	 * Static array of uses input ids.
	 * @static
	 * @access private
	 * @var array of ints
	 */
	private static $input_ids = array();
	/**
	 * Generate id for input tag.
	 * @static
	 * @access private
	 * @param string $id
	 * @return string
	 */
	private static function getInputID($id)
	{
		if (!isset(self::$input_ids[$id]))
			self::$input_ids[$id] = 0;
		return $id."-".self::$input_ids[$id]++;
	}
	/**
	 * Generate id for div tag.
	 * @static
	 * @access private
	 * @return string
	 */
	private static function getDivID()
	{
		return "InputCSS-".md5(microtime(true))."-".self::$div_id++;
	}
	/**
	 * Generate all markup required for final output.
	 * @static
	 * @access private
	 * @param string $type
	 * @param string $id
	 * @param string $name
	 * @param string $value
	 * @param bool $disabled
	 * @param string $extra
	 * @return string
	 */
	private static function getXHTML($type, $id, $name, $value, $disabled, $checked = false, $extra = "")
	{
		$div_id = self::getDivID();
		$input_id = self::getInputID($id);
		$html = "<div class=\"hiddenButtonDiv ".$id."Div\">";
		$html .= "<input type=\"".$type."\" id=\"".$input_id."\" name=\"".$name."\" value=\"".$value."\" class=\"hiddenButton".($disabled ? "Disabled" : "")."\"";
		if (!$disabled)
		{
			if (!in_array($type, array("checkbox", "radio", "submit", "reset")))
			{
				$html .= " onmouseover=\"$('#".$div_id."').removeClass().addClass('".$id."On');\"";
				$html .= " onmouseout=\"$('#".$div_id."').removeClass().addClass('".$id."Off');\"";
				$html .= " onmousedown=\"$('#".$div_id."').addClass('".$id."Dn');\"";
				$html .= " onmouseup=\"$('#".$div_id."').removeClass('".$id."Dn');\"";
			}
		}
		else
		{
			$html .= " disabled=\"disabled\"";
		}
		if ($checked)
			$html .= " checked=\"checked\"";
		if (!empty($extra))
			$html .= " ".$extra;
		$html .= " />";
		if (in_array($type, array("checkbox", "radio")))
		{
			$html .= "<a href=\"#\" onclick=\"if (!$('#".$input_id."').attr('checked')){";
			if ($type == "radio")
				$html .= "$('input[name=".$name."]:checked').siblings('div').each(function (i){var old = $(this).attr('class');$(this).removeClass().addClass(old.replace('On', 'Off'));});";
			$html .= "$('#".$div_id."').removeClass().addClass('".$id."On');$('#".$input_id."').attr('checked', true);";
			$html .= "}else{";
			if ($type == "checkbox")
				$html .= "$('#".$div_id."').removeClass().addClass('".$id."Off'); $('#".$input_id."').attr('checked', false);";
			$html .= "} return false;\"";
			$html .= " onmouseover=\"if (!$('#".$input_id."').attr('checked'))$('#".$div_id."').removeClass().addClass('".$id."On');\"";
			$html .= " onmouseout=\"if (!$('#".$input_id."').attr('checked'))$('#".$div_id."').removeClass().addClass('".$id."Off');\"";
			$html .= " onmousedown=\"if (!$('#".$input_id."').attr('checked'))$('#".$div_id."').addClass('".$id."Dn');\"";
			$html .= " onmouseup=\"if (!$('#".$input_id."').attr('checked'))$('#".$div_id."').removeClass('".$id."Dn');\"";
			$html .= ">".$value."</a>";
		}
		elseif (in_array($type, array("submit", "reset")))
		{
			$html .= "<a href=\"#\" ".($disabled ? "class=\"disabled\"" : "")."onclick=\"$('#".$input_id."').click(); return false;\"";
			$html .= " onmouseover=\"if (!$('#".$input_id."').attr('disabled'))$('#".$div_id."').removeClass().addClass('".$id."On');\"";
			$html .= " onmouseout=\"if (!$('#".$input_id."').attr('disabled'))$('#".$div_id."').removeClass().addClass('".$id."Off');\"";
			$html .= " onmousedown=\"if (!$('#".$input_id."').attr('disabled'))$('#".$div_id."').addClass('".$id."Dn');\"";
			$html .= " onmouseup=\"if (!$('#".$input_id."').attr('disabled'))$('#".$div_id."').removeClass('".$id."Dn');\"";
			$html .= " title=\"".$value."\">".$value."</a>";
		}
		$html .= "<div id=\"".$div_id."\" class=\"".$id.($disabled ? "Na" : ($checked ? "On" : "Off"))."\"></div>";
		$html .= "</div>\n";
		return $html;
	}
	/**
	 * Generate a submit button.
	 * @static
	 * @param string $id
	 * @param string $name
	 * @param string $value
	 * @param bool $disabled
	 * @param string $extra
	 * @return string
	 */
	public static function button($id, $name, $value, $disabled = false, $extra = "")
	{
		return self::getXHTML("submit", $id, $name, $value, $disabled, false, $extra);
	}
	/**
	 * Generate a reset button.
	 * @static
	 * @param string $id
	 * @param string $name
	 * @param string $value
	 * @param bool $disabled
	 * @param string $extra
	 * @return string
	 */
	public static function reset($id, $name, $value, $disabled = false, $extra = "")
	{
		return self::getXHTML("reset", $id, $name, $value, $disabled, false, $extra);
	}
	/**
	 * Generate a file/browse input.
	 * @static
	 * @param string $id
	 * @param string $name
	 * @param string $value
	 * @param bool $disabled
	 * @param string $extra
	 * @return string
	 */
	public static function file($id, $name, $disabled = false, $extra = "")
	{
		return self::getXHTML("file", $id, $name, "", $disabled, false, $extra);
	}
	/**
	 * Generate a checkbox input.
	 * @static
	 * @param string $id
	 * @param string $name
	 * @param string $value
	 * @param bool $disabled
	 * @param bool $checked
	 * @param string $extra
	 * @return string
	 */
	public static function checkbox($id, $name, $value, $disabled = false, $checked = false, $extra = "")
	{
		return self::getXHTML("checkbox", $id, $name, $value, $disabled, $checked, $extra);
	}
	/**
	 * Generate a radio input.
	 * @static
	 * @param string $id
	 * @param string $name
	 * @param string $value
	 * @param bool $disabled
	 * @param bool $checked
	 * @param string $extra
	 * @return string
	 */
	public static function radio($id, $name, $value, $disabled = false, $checked = false, $extra = "")
	{
		return self::getXHTML("radio", $id, $name, $value, $disabled, $checked, $extra);
	}
}
?>
