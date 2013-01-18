<?php
class dbdSession
{
	const TABLE_NAME = "session";
	const TABLE_KEY = "session_id";
	const TABLE_FIELD_PHP_ID = "php_session_id";
	const TABLE_CHILD_NAME = "session_params";
	const TABLE_CHILD_KEY = "session_param_id";
	const TABLE_CHILD_FIELD_NAME = "param_name";
	const TABLE_CHILD_FIELD_VALUE = "param_value";
	const TABLE_HISTORY_NAME = "history";
	const TABLE_HISTORY_KEY = "history_id";
	const SESS_PREFIX = "dbdmvc_sess_";
	const COOKIE_DOMAIN_REGEX = '/(([0-9.]+)|([0-9a-z-]+(?<!www)\.)?[0-9a-z-]+\.[0-9a-z-]{2,3})$/i';
	const COOKIE_LIFE = 31536000; //365 days
	const REMEMBER_ME_TIME = 1209600; //14 days
	const LOGIN_TIME = 3600; //1 hour
	const ONLINE_TIME = 900; //15 minutes

	/**
	 * @var dbdDB
	 */
	protected static $db = null;
	protected $id = 0;
	protected $user = null;

	public function __construct($php_sess_id = null)
	{
		self::$db = dbdDB::getInstance();
		$this->setOptions();
		$this->startSession();
		$this->load($php_sess_id);
		$this->retainLogin();
	}

	public function __destruct()
	{
		$this->save();
	}

	protected function load($id = null)
	{
		if ($id !== null)
		{
			$this->destroySession();
			session_id($id);
			$this->startSession();
		}
		else
		{
			$id = session_id();
		}
		$this->id = self::$db->prepExec("select `".self::TABLE_KEY."` from `".self::TABLE_NAME."` where `".self::TABLE_FIELD_PHP_ID."` = ?", array($id))->fetchColumn();
		if ($this->id < 1)
		{
			self::$db->prepExec("insert into `".self::TABLE_NAME."` set `".self::TABLE_FIELD_PHP_ID."` = :id, `date_created` = :date, `date_modified` = :date", array("id" => $id, "date" => dbdDB::date()));
			$this->id = self::$db->lastInsertID();
		}
		else
		{
			self::$db->prepExec("update `".self::TABLE_NAME."` set `date_modified` = :date where `".self::TABLE_KEY."` = :id", array("id" => $this->id, "date" => dbdDB::date()));
		}
		$this->loadParams();
	}

	public function loadParams()
	{
		$this->clearParams();
		foreach (self::$db->prepExec("select * from `".self::TABLE_CHILD_NAME."` where `".self::TABLE_KEY."` = ?", array($this->id))->fetchAll() as $p)
			$this->setParam($p['param_name'], $p['param_value']);
	}

	protected function save()
	{
		$this->saveParams();
	}

	protected function saveParams()
	{
		foreach ($this->getParams() as $k => $v)
			$this->saveParam($k, $v);
	}

	public function saveParam($name, $value)
	{
		$name = str_replace(self::SESS_PREFIX, "", $name);
		if ($value === null)
		{
			$sql = "delete from `".self::TABLE_CHILD_NAME."`";
			$sql .= " where `".self::TABLE_KEY."` = ?";
			$sql .= " and `".self::TABLE_CHILD_FIELD_NAME."` = ?";
			self::$db->prepExec($sql, array($this->id, $name));
		}
		else
		{
			$id = self::$db->prepExec("select `".self::TABLE_CHILD_KEY."` from `".self::TABLE_CHILD_NAME."` where `".self::TABLE_KEY."` = ? and `".self::TABLE_CHILD_FIELD_NAME."` = ?", array($this->id, $name))->fetchColumn();
			$a = array($this->id, $name, $value);
			if ($id > 0)
				$sql = "update";
			else
				$sql = "insert into";
			$sql .= " `".self::TABLE_CHILD_NAME."`";
			$sql .= " set `".self::TABLE_KEY."` = ?";
			$sql .= ",`".self::TABLE_CHILD_FIELD_NAME."` = ?";
			$sql .= ",`".self::TABLE_CHILD_FIELD_VALUE."` = ?";
			if ($id > 0)
			{
				$sql .= " where `".self::TABLE_CHILD_KEY."` = ?";
				$a[] = $id;
			}
			self::$db->prepExec($sql, $a);
		}
	}

	protected function setOptions()
	{
		preg_match(self::COOKIE_DOMAIN_REGEX, dbdMVC::getRouter()->getParam("HTTP_HOST"), $matches);
		ini_set("session.cookie_lifetime", self::COOKIE_LIFE);
		ini_set("session.cookie_domain", $matches[0]);
	}

	protected function startSession()
	{
		@session_start();
	}

	protected function destroySession()
	{
		@session_destroy();
	}

	public function setParam($name, $value = null)
	{
		$_SESSION[self::SESS_PREFIX.$name] = $value;
	}

	public function getParam($name)
	{
		return isset($_SESSION[self::SESS_PREFIX.$name]) ? $_SESSION[self::SESS_PREFIX.$name] : null;
	}

	public function getParams()
	{
		$tmp = array();
		foreach ($_SESSION as $k => $v)
			$tmp[preg_replace("/^".self::SESS_PREFIX."/", "", $k)] = $v;
		return $tmp;
	}

	public function clearParams()
	{
		$_SESSION = array();
//		session_unset();
	}

	public function getUser()
	{
		return $this->user;
	}

	private function setUserParams()
	{
		if (!$this->user)
		{
			$this->delMemberParams();
		}
		else
		{
			$this->setParam(dbdUser::TABLE_KEY, $this->user->getID());
			$this->setParam("email", $this->user->getEmail());
			$this->setParam("first_name", $this->user->getFirstName());
			$this->setParam("last_name", $this->user->getLastName());
			$this->setParam("nick_name", $this->user->getNickName());
//			$this->setParam("last_login", $this->user->getLastLogin());
			$this->setParam("user_logged_in", time());
		}
	}

	private function delMemberParams()
{
		$this->setParam(dbdUser::TABLE_KEY, null);
		$this->setParam("user_name", null);
//		$this->setParam("last_login", null);
		$this->setParam("user_remember_me", null);
		$this->setParam("user_logged_in", null);
	}

	public function processLogin($email, $pass, $remember_me = false)
	{
		$this->user = dbdUser::authenticate($email, $pass);
		$this->setUserParams();
		$this->setParam("user_remember_me", $remember_me);
		session_write_close();
		return true;
	}

	public function processLogout()
	{
		$this->startSession();
		$this->user = null;
		$this->delMemberParams();
		$this->destroySession();
	}

	private function retainLogin()
	{
		if (($time = $this->getParam("user_logged_in")) > 0)
		{
			$diff = time() - $time;
			if ($diff <= self::LOGIN_TIME || ($diff <= self::REMEMBER_ME_TIME && $this->getParam("user_remember_me")))
			{
				$this->user = new dbdUser($this->getParam(dbdUser::TABLE_KEY));
				$this->setUserParams();
				return true;
			}
		}
		$this->delMemberParams();
		return false;
	}

	public function isLoggedIn()
	{
		if ($this->user instanceof dbdUser)
		{
			if ($this->user->getID() > 0)
				return true;
		}
		return false;
	}

	public function isUserOnline($id)
	{
		$time = 0;
		$sql = "select `".self::TABLE_KEY."` from `".self::TABLE_CHILD_NAME."`";
		$sql .= " where `".self::TABLE_CHILD_FIELD_NAME."` = 'user_id' and `".self::TABLE_CHILD_FIELD_VALUE."` = ?";
		foreach (self::$db->prepExec($sql, array($id))->fetchAll() as $sid)
		{
			$sql2 = "select `".self::TABLE_CHILD_FIELD_VALUE."` from `".self::TABLE_CHILD_NAME."`";
			$sql2 .= " where `".self::TABLE_CHILD_FIELD_NAME."` = 'user_logged_in'";
			$sql2 .= " and `".self::TABLE_KEY."` = ?".$sid;
			if (($tmp = self::$db->prepExec($sql2, array($sid[0]))->fetchColumn()) > $time)
				$time = $tmp;
		}
		return (time() - $time <= self::ONLINE_TIME);
	}

	public function logLanding($controller, $action, $params = array(), $title = "", $type = 0)
	{
		$sql = "insert into `".self::TABLE_HISTORY_NAME."` set ";
		$sql .= "session_id = ?, page_controller = ?, page_action = ?, page_params = ?, page_title = ?, page_type = ?, referer = ?, user_ip = ?, user_agent = ?, date = ?";
		$referrer = dbdMVC::getRequest()->get("HTTP_REFERER");
		$ip = dbdMVC::getRequest()->get("REMOTE_ADDR");
		$agent = dbdMVC::getRequest()->get("HTTP_USER_AGENT");
		self::$db->prepExec($sql, array(
				$this->id,
				$controller,
				$action,
				serialize($params),
				$title,
				$type,
				$referrer ? $referrer : "",
				$ip ? $ip : "",
				$agent ? $agent : "",
				dbdDB::date()
			));
	}

	public function getHistory($date_start = null, $date_end = null, $limit = null, $id = null, $type = 0, $noempty = true)
	{
		$sql = "select * from `".self::TABLE_HISTORY_NAME."` where session_id = ".$this->id;
		if ($date_start !== null)
			$sql .= " and date >= '".date("Y-m-d 00:00:00", strtotime($date_start))."'";
		if ($date_end !== null)
			$sql .= " and date <= '".date("Y-m-d 23:59:59", strtotime($date_end))."'";
		if ($id !== null)
			$sql .= " and history_id = ".$id;
		if ($type > -1)
			$sql .= " and page_type = ".$type;
		if ($noempty)
			$sql .= " and page_title != ''";
		$sql .= " order by date desc";
		if ($limit !== null)
			$sql .= " limit ".$limit;
		return self::$db->query($sql)->fetchAll();
	}

	public function getHistoryCount($date_start = null, $date_end = null, $type = 0, $noempty = true)
	{
		$sql = "select count(1) from `".self::TABLE_HISTORY_NAME."` where `".self::TABLE_KEY."` = ".$this->id;
		if ($date_start !== null)
			$sql .= " and date >= '".date("Y-m-d 00:00:00", strtotime($date_start))."'";
		if ($date_end !== null)
			$sql .= " and date <= '".date("Y-m-d 23:59:59", strtotime($date_end))."'";
		if ($type > -1)
			$sql .= " and page_type = ".$type;
		if ($noempty)
			$sql .= " and page_title != ''";
		return self::$db->query($sql)->fetchColumn();
	}

	public function delHistory($ids)
	{
		foreach ($ids as $id)
		{
			$sql = "delete from `".self::TABLE_HISTORY_NAME."` where `".self::TABLE_HISTORY_KEY."` = ?";
			self::$db->prepExec($sql, array($id));
		}
	}
}
