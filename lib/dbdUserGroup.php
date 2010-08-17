<?php
class dbdUserGroup extends dbdModel
{
	const TABLE_NAME = "user_groups";
	const TABLE_KEY = "user_group_id";

	public function getUsers()
	{
		return dbdUser::getAll($this->id);
	}

	public function getAccess()
	{
		$a = array();
		$a['grant'] = $this->hasGrantAccess();
		$a['dash'] = $this->hasDashAccess();
		return $a;
	}
}
?>