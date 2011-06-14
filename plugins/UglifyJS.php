<?php
class UglifyJS
{
	const WHICH = 'which';
	const CMD = 'uglifyjs';
	const OPTS = '-nc';

	public static function installed()
	{
		$proc = popen(self::WHICH.' '.self::CMD, 'r');
		if (is_resource($proc))
		{
			$stdout = stream_get_contents($proc);
			pclose($proc);
			return !empty($stdout);
		}
		return false;
	}

	public static function uglify($js)
	{
		$dspec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w')
		);
		$proc = proc_open(self::CMD.' '.self::OPTS, $dspec, $pipes);
		if (is_resource($proc))
		{
			fwrite($pipes[0], $js);
			fclose($pipes[0]);
			$stdout = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[2]);
			proc_close($proc);
			return $stdout;
		}
		return $js;
	}
}
?>