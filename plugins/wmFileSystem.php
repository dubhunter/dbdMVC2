<?php
/**
 * wmFileSystem.php :: Will Mason's file system class
 *
 * @package dbdCommon
 * @version 1.2
 * @author Will Mason <will@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2007 by Don't Blink Design
 */
require_once("wmSort.php");
/**
 * The wmFileSystem class is collection of specialized file
 * system utility methods.
 * @static
 * @package dbdCommon
 */
class wmFileSystem
{
	/**
	 * Reads directory contents recursively and returns
	 * them as an associative array.
	 * <i>Usage:</i> <code>
	 * $files = wmFileSystem::scanDir("./somedir/");
	 * </code>
	 * @static
	 * @param string $dir target directory
	 * @param int $max_depth maximum depth for recursion
	 * @return array list of directory contents
	 */
	public static function scanDir($dir, $max_depth = -1)
	{
		$ret = array();
		if (!is_dir($dir))
		{
			$ret[$dir] = "DIR NOT READABLE!";
			return $ret;
		}
		if ($max_depth > -1)
		{
			if (count($ret) >= $max_depth)
				return $ret;
		}
		$dh = opendir($dir);
		while (false !== ($file = readdir($dh)))
		{
			if ($file != "." && $file != "..")
			{
				$fname = $dir."/".$file;
				$type = filetype($fname);
				switch ($type)
				{
					case 'dir':
						$ret[] = array(
							"type" => "dir",
							"name" => $file,
							"files" => self::scanDir($fname, $max_depth - 1)
						);
						break;
					default:
						$ret[] = array(
							"type" => "file",
							"mtime" => filemtime($fname),
							"size" => filesize($fname),
							"extension" => pathinfo($fname, PATHINFO_EXTENSION),
							"name" => $file
						);
				}
			}
		}
		closedir($dh);
		wmSort::multiSort($ret, "type,name");
		return $ret;
	}
	/**
	 * Get the file sixe of a directory and its subcontents in bytes
	 * @param string $dir
	 * @return integer
	 */
	public static function dirSize($dir, $exclude = array())
	{
		if (!is_dir($dir))
			return 0;
		$cmd = "/usr/bin/du -sb ";
		if (!is_array($exclude))
			$exclude = array($exclude);
		foreach ($exclude as $e)
			$cmd .= "--exclude=".$e." ";
		$du = shell_exec($cmd.$dir);
		list($size) = preg_split("/[^0-9]/", $du, 2);
		return $size;
	}
	/**
	 * Makes byte counts human readble (i.e. 1024 -> 1KB).
	 * <i>Usage:</i> <code>
	 * $size = wmFileSystem::bytesToHuman($size);
	 * </code>
	 * @static
	 * @param int $size byte count
	 * @return string human readable byte count
	 */
	public static function bytesToHuman($size)
	{
		$i = 0;
		$iec = array("B", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB");
		while (($size / 1024) > 1)
		{
			$size = $size / 1024;
			$i++;
		}
		return number_format($size, $i)." ".$iec[$i];
	}
	/**
	 * Get the mime type from mime_magic
	 * @param string $file
	 * @return string
	 */
	public static function mimeType($file)
	{
        $finfo = finfo_open(FILEINFO_MIME, "/usr/share/misc/magic");
        $mimetype = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mimetype;
	}
}
?>