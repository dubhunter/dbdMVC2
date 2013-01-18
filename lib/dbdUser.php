<?php
class dbdUser extends dbdModel
{
	const TABLE_NAME = "users";
	const TABLE_KEY = "user_id";
	const TABLE_FIELD_EMAIL = "email";
	const TABLE_FIELD_PASS = "password";
	const IMAGE_DIR = "images/users";
	const IMAGE_TYPE = "jpg";
	const IMAGE_WIDTH = 200;
	const IMAGE_HEIGHT = 200;
	const HASH_ALGO = "sha512";
	const PASS_LENGTH = 8;

	private $user_group = null;

	public static function getAll($group_id = null)
	{
		$a = array();
		if ($group_id !== null)
			$a[dbdUserGroup::TABLE_KEY] = $group_id;
		return parent::getAll($a, "first_name");
	}

	public static function getUserByEmail($email)
	{
		self::ensureDB();
		$sql = "select `".self::TABLE_KEY."` from `".self::TABLE_NAME."` where `".self::TABLE_FIELD_EMAIL."` = ?";
		$id = self::$db->prepExec($sql, array($email))->fetchColumn();
		dbdAdminException::ensure($id > 0, dbdAdminException::USER_NOT_FOUND);
		return new self($id);
	}

	public static function authenticate($email, $pass)
	{
		self::ensureDB();
		$sql = "select `".self::TABLE_KEY."`";
		$sql .= " from `".self::TABLE_NAME."`";
		$sql .= " where `".self::TABLE_FIELD_EMAIL."` = ?";
		$sql .= " and `".self::TABLE_FIELD_PASS."` = ?";
		$id = self::$db->prepExec($sql, array($email, self::hash($pass)))->fetchColumn();
		dbdAdminException::ensure($id > 0, dbdAdminException::AUTH_MISMATCH);
		return new self($id);
	}

	public static function hash($str)
	{
		return hash(self::HASH_ALGO, $str);
	}

	public function save($fields = array())
	{
//		SRException::hold();
//		SRException::ensure($this->hasEmail() || !empty($fields[self::TABLE_FIELD_EMAIL]), SRException::USER_NAME_EMPTY);
//		SRException::release();
		if (isset($fields[self::TABLE_FIELD_PASS]))
			$fields[self::TABLE_FIELD_PASS] = self::hash($fields[self::TABLE_FIELD_PASS]);
		parent::save($fields);
	}

	public function savePassword($cur, $new, $confirm)
	{
		dbdAdminException::hold();
		dbdAdminException::ensure($this->getPassword() == self::hash($cur), dbdAdminException::AUTH_MISMATCH);
		dbdAdminException::ensure($new == $confirm, dbdAdminException::USER_PASS_CONFIRM_MISMATCH);
		dbdAdminException::release();
		$this->setPassword(self::hash($new));
		$this->save();
	}

	public function setTempPassword()
	{
		$pass = wmString::randomWord(self::PASS_LENGTH);
		$this->setPassword(self::hash($pass));
		$this->save();
		return $pass;
	}

	public function getUserGroup()
	{
		if ($this->user_group === null)
			$this->user_group = new dbdUserGroup($this->getUserGroupId());
		return $this->user_group;
	}

	public function getAccess()
	{
		return $this->getUserGroup()->getAccess();
	}
}
