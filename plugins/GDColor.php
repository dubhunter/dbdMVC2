<?php
/**
 * GDColor.php :: The GD Color Class
 *
 * GDColor version 1.2
 * Copyright (c) 2006-2007 by Don't Blink Design
 * http://www.dontblinkdesign.com
 *
 * GDColor is released under the terms of the LGPL license
 * http://www.gnu.org/copyleft/lesser.html
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package dbdCommon
 * @version 1.2
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2007 by Don't Blink Design
 * @license http://www.gnu.org/copyleft/lesser.html
 */
/**
 * The GDColor Class is used to easily
 * handle colors while using the GD Library.
 * @package dbdCommon
 */
class GDColor
{
	/**
	 * GD Image resource to allocate colors for.
	 * @var resource
	 */
	private $im;

	/**
	 * Red, Green, Blue, and Alpha channel values
	 * stored in an array respectively.
	 * @var array of ints
	 */
	private $rgba;

	/**
	 * Integer color value to be used by GD functions.
	 * @var int
	 */
	private $color;

	/**
	 * The constructor's job is to convert HEX color to its integer parts
	 * and then allocate the color.
	 * @param resource $i of image from imagecreate...
	 * @param string $hex color value
	 */
	public function __construct($i, $hex)
	{
		$this->im = $i;
		$this->rgba = self::hex2arr($hex);
		if ($this->rgba[3] > 0)
			$this->color = imagecolorallocatealpha($this->im, $this->rgba[0], $this->rgba[1], $this->rgba[2], $this->rgba[3]);
		else
			$this->color = imagecolorallocate($this->im, $this->rgba[0], $this->rgba[1], $this->rgba[2]);
	}

	/**
	 * Clean up the allocated colors.
	 */
	public function __destruct()
	{
		if (is_resource($this->im))
			imagecolordeallocate($this->im, $this->color);
	}

	/**
	 * Get the integer color value for use in GD functions.
	 * @return int
	 */
	public function getColor()
	{
		return $this->color;
	}

	/**
	 * Get the HEX color value of the allocated color.
	 * @return string hex color
	 */
	public function getHex()
	{
		return dechex($this->color);
	}

	/**
	 * Get the integer color value for the Red channel.
	 * @return int
	 */
	public function getRed()
	{
		return $this->rgba[0];
	}

	/**
	 * Get the integer color value for the Green channel.
	 * @return int
	 */
	public function getGreen()
	{
		return $this->rgba[1];
	}

	/**
	 * Get the integer color value for the Blue channel.
	 * @return int
	 */
	public function getBlue()
	{
		return $this->rgba[2];
	}

	/**
	 * Get the integer color value for the Alpha channel.
	 * @return int
	 */
	public function getAlpha()
	{
		return $this->rgba[3];
	}

	/**
	 * Static function for breaking an integer
	 * color into its various channels.
	 * @static
	 * @param int $int color
	 * @return array ints foreach channel
	 */
	public static function int2arr($int)
	{
		$rgba = array();
		$rgba[] = 0xFF & ($int >> 16);
		$rgba[] = 0xFF & ($int >> 8);
		$rgba[] = 0xFF & ($int >> 0);
		$rgba[] = 0xFF & ($int >> 24);
		return $rgba;
	}

	/**
	 * Static function for breaking a HEX
	 * color into its various channels.
	 * @static
	 * @param string $hex color
	 * @return array ints foreach channel
	 */
	public static function hex2arr($hex)
	{
		return self::int2arr(hexdec($hex));
	}

	/**
	 * Static function for combining an rgba
	 * color into a HEX color.
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return string
	 */
	public static function arr2hex($r, $g, $b, $a = null)
	{
		$r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
		$g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
		$b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));
		if ($a !== null)
			$a = dechex($a < 0 ? 0 : ($a > 127 ? 127 : $a));
		$color = (strlen($r) < 2 ? '0' : '').$r;
		$color .= (strlen($g) < 2 ? '0' : '').$g;
		$color .= (strlen($b) < 2 ? '0' : '').$b;
		if ($a !== null)
			$color .= (strlen($a) < 2 ? '0' : '').$a;
		return '#'.$color;
	}
}
?>
