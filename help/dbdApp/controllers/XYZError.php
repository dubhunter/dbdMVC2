<?php
class XYZError extends XYZController
{
	public function doDefault()
	{
		dbdError::doError($this);
	}
}
?>