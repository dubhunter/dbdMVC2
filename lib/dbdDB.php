<?php
/**
 * dbdDB.php :: dbdDB Class File
 *
 * @package dbdMVC
 * @version 1.4
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2009 by Don't Blink Design
 */

/**
 * dbdMVC Database Abstraction Layer
 * @package dbdMVC
 */
class dbdDB
{
	/**
	 * path to database connection string file
	 * @var string constant
	 */
	const DBCONN_PATH = "constant/dbconn.inc";
	/**
	 * SQL compliant date formating string
	 * @var string constant
	 */
	const DATE_FORMAT = "Y-m-d H:i:s";
	/**
	 * SQL compliant time formating string
	 * @var string constant
	 */
	const TIME_FORMAT = "H:i:s";

	const COMP_TYPE = "dbdDB::COMP_TYPE";
	const COMP_EQ = 1;
	const COMP_NEQ = 2;
	const COMP_NULLEQ = 3;
	const COMP_GTEQ = 4;
	const COMP_GT = 5;
	const COMP_LTEQ = 6;
	const COMP_LT = 7;
	const COMP_IN = 8;
	const COMP_NIN = 9;
	const COMP_LIKE = 10;
	const COMP_NLIKE = 11;
	const COMP_BETWEEN = 12;
	const COMP_NBETWEEN = 13;
	/**
	 * array of dbdDB objects
	 * to be filled by the static
	 * getInstance method
	 * @var array dbdDB
	 */
	private static $instances = array();
	/**
	 * PDO instance variable
	 * @var PDO
	 */
	private $pdo = null;
	/**
	 * Array of PDO options to set after construction
	 * @var array
	 */
	private $options = array(
		PDO::ATTR_CASE => PDO::CASE_LOWER,
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	);
	/**
	 * The constructors job is to instantiate the needed PDO object and set attributes
	 * @access private
	 * @param string $dsn
	 * @param string $user
	 * @param string $pass
	 * @param array $driver_options
	 */
	private function __construct($dsn, $user = "", $pass = "", $driver_options = array())
	{
		$this->pdo = new PDO($dsn, $user, $pass, $driver_options);
		$this->setAttributes();
	}
	/**
	 * Set default PDO attributes
	 */
	private function setAttributes()
	{
		foreach ($this->options as $k => $v)
			$this->pdo->setAttribute($k, $v);
	}
	/**
	 * Include db_conn string file.
	 * Key method of Singleton Pattern!!
	 * @access private
	 * @param string $dsn
	 * @param string $user
	 * @param string $pass
	 * @param array $driver_options
	 * @return dbdDB object
	 */
	public static function getInstance($dsn = "", $user = "", $pass = "", $driver_options = array())
	{
		self::includeDBConn();
		if (!strpos($dsn, ":"))
			$dsn = self::assembleDSN($dsn);
		$i = md5($dsn);
		if (!isset(self::$instances[$i]) || !is_object(self::$instances[$i]))
		{
			if (empty($user))
				$user = DB_USER;
			if (empty($pass))
				$pass = DB_PASS;
			self::$instances[$i] = new self($dsn, $user, $pass, $driver_options);
		}
		return self::$instances[$i];
	}
	/**
	 * Include dbconn string file.
	 * @throws dbdException
	 * @access private
	 */
	private static function includeDBConn()
	{
		dbdLoader::load(self::DBCONN_PATH);
		if (!DBCONN_INCLUDED)
			throw new dbdException("Database Connection String file could not be included. PATH=".self::DBCONN_PATH);
	}
	/**
	 * Assemble driver specific DSN's
	 * @throws dbdException
	 * @param string $db
	 * @return string
	 */
	private static function assembleDSN($db = "")
	{
		if (empty($db)) $db = DBCONN_DEFAULT_DB;
		switch (DB_DRIVER)
		{
			case 'mysql':
				return "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".$db;
		}
		throw new dbdException("Database Driver (".DB_DRIVER.") is not supported!");
	}
	/**
	 * Format date for SQL insert
	 * @param integer $timestamp
	 * @return string
	 */
	public static function date($timestamp = null)
	{
		return $timestamp ? date(self::DATE_FORMAT, $timestamp) : date(self::DATE_FORMAT);
	}
	/**
	 * Format time for SQL insert
	 * @param integer $timestamp
	 * @return string
	 */
	public static function time($timestamp = null)
	{
		return $timestamp ? date(self::TIME_FORMAT, $timestamp) : date(self::TIME_FORMAT);
	}
	/**
	 * Prepare & excute a query statement
	 * @throws dbdException
	 * @param string $statement
	 * @param array $input_parameters
	 * @param array $driver_options
	 * @return PDOStatement
	 */
	public function prepExec($statement, $input_parameters = array(), $driver_options = array())
	{
		$sth = $this->prepare($statement, $driver_options);
		if (!$sth->execute($input_parameters))
			throw new dbdException("Statement could not be executed!");
		return $sth;
	}
	/**
	 * Selects the next auto_increment id
	 * @return int last_id
	 */
	public function nextAutoID($table)
	{
		$sql = "show table status like ".$this->quote($table);
		$tmp = $this->query($sql)->fetch(PDO::FETCH_ASSOC);
		return key_exists('auto_increment', $tmp) ? $tmp['auto_increment'] : 0;
	}
	/**
	 * Checks for exisitance of a table
	 * @return boolean
	 */
	public function tableExists($table)
	{
		$sql = "show tables like ".$this->quote($table);
		return $this->query($sql)->fetchColumn() ? true : false;
	}
	/**
	 * Trys to figure out the primary key for a table
	 * @return mixed string upon success | false on failure
	 */
	public function primaryKey($table)
	{
		$sql = "show index from `".$table."` where key_name = 'primary'";
		$tmp = $this->query($sql)->fetch(PDO::FETCH_ASSOC);
		return key_exists('column_name', $tmp) ? $tmp['column_name'] : false;
	}
	/**
	 * Magic function to call PDO functions and rethrow any
	 * exceptions as dbdException
	 * @throws dbdException
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		try
		{
			if (!method_exists($this->pdo, $name))
				throw new dbdException("Method (".__CLASS__."::".$name.") does not exists!");
			return call_user_func_array(array($this->pdo, $name), $args);
		}
		catch (Exception $e)
		{
			throw new dbdException($e->getMessage(), $e->getCode());
		}
	}
}
?>