<?php
/**
 * dbdModel.php :: dbdModel Class File
 *
 * @package dbdMVC
 * @version 1.16
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2011 by Don't Blink Design
 */

/**
 * dbdMVC Database Table Model
 *
 * <b>Simple Model Example</b>
 * <code>
 * class Widget extends dbdModel
 * {
 * 	const TABLE_NAME = "widgets";
 * 	const TABLE_KEY = "widget_id";
 *
 * 	public function __construct($id = 0)
 * 	{
 * 		parent::__construct(__CLASS__, $id);
 * 	}
 *
 * 	public static function getAll()
 * 	{
 * 		return parent::getAll(__CLASS__);
 * 	}
 * }
 * </code>
 * @package dbdMVC
 * @abstract
 */
abstract class dbdModel
{
	const CONST_TABLE_NAME = 'TABLE_NAME';
	const CONST_TABLE_KEY = 'TABLE_KEY';
	const OPT_IDS_ONLY = 'dbdModel::OPT_IDS_ONLY';
	const OPT_GROUP_BY = 'dbdModel::OPT_GROUP_BY';
	/**
	 * A list of class reflections to limit overhead
	 * @var array ReflectionClass
	 */
	private static $reflections = array();
	/**
	 * Static instance of dbdDB
	 * @var dbdDB
	 */
	protected static $db = null;
	/**
	 * The name of the class
	 * @var string
	 */
	private $class_name = null;
	/**
	 * The name of the table
	 * @var string
	 */
	private $table_name = null;
	/**
	 * The key for the table
	 * @var string
	 */
	private $table_key = null;
	/**
	 * Row id
	 * @var integer
	 */
	protected $id = 0;
	/**
	 * Array of fields from row
	 * @var array
	 */
	protected $data = array();
	/**
	 * The constructer's job is to set the table info,
	 * get the db, and call the initializer.
	 * @param string $class
	 * @param integer $id
	 */
	public function __construct($id = 0)
	{
		$this->class_name = get_called_class();
		$this->table_name = self::getConstant($this->class_name, self::CONST_TABLE_NAME);
		$this->table_key = self::getConstant($this->class_name, self::CONST_TABLE_KEY);
		self::ensureDB();
		$this->id = $id;
		if ($this->id > 0)
			$this->init();
		else
			$this->initFields();
	}
	/**
	 * Select all the fields for this row
	 */
	protected function init()
	{
		$sql = "select * from `".$this->table_name."` where `".$this->table_key."` = ?";
		$this->data = self::$db->prepExec($sql, array($this->id))->fetch(PDO::FETCH_ASSOC);
		if (!is_array($this->data))
			$this->initFields();
	}
	/**
	 * Select all the fields names for this table
	 */
	protected function initFields()
	{
		$sql = "describe ".$this->table_name;
		foreach (self::$db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $f)
		{
			if (!isset($this->data[$f['field']]))
				$this->data[$f['field']] = null;
		}
		if ($this->id > 0)
		{
			$this->data[$this->table_key] = $this->id;
			$this->id = 0;
		}
	}
	/**
	 * Save all the fields for this row
	 * @param array $fields
	 */
	public function save($fields = array())
	{
		foreach ($fields as $k => $v)
		{
//			if (!key_exists($k, $this->data))
//				throw new dbdException(get_class().": Field ('".$k."') not valid!");
//			$this->__set($k, $v);
			if (key_exists($k, $this->data))
				$this->__set($k, $v);
		}
		$sql = "";
		$sql_end = "";
		if ($this->id > 0)
		{
			$sql .= "update";
			$sql_end .= " where `".$this->table_key."` = :".$this->table_key;
		}
		else
		{
			$sql .= "insert into";
		}
		$sql .= " `".$this->table_name."` set ";
		$i = 0;
		foreach ($this->data as $k => $v)
		{
			if ($i++ > 0) $sql .= ",";
			$sql .= "`".$k."` = :".$k;
		}
		$sql .= $sql_end;
		self::$db->prepExec($sql, array_merge(array($this->table_key => $this->id), $this->data));
		if ($this->id == 0)
			$this->id = self::$db->lastInsertId($this->table_name);
		$this->init();
	}
	/**
	 * Delete this row
	 */
	public function delete()
	{
		$sql = "delete from `".$this->table_name."` where `".$this->table_key."` = ?";
		self::$db->prepExec($sql, array($this->id));
		$this->id = 0;
		$this->init();
	}
	/**
	 * Get a count of all rows from this table
	 * @param array $table_keys
	 * @return array dbdModel
	 */
	public static function getCount($table_keys = array(), $options = array())
	{
		self::ensureDB();
		$class = get_called_class();
		$tmp = array();
		$sql = "select count(1) from `".self::getConstant($class, self::CONST_TABLE_NAME)."`";
		$sql .= self::buildWhereClause($table_keys);
		if (is_array($options) && key_exists(self::OPT_GROUP_BY, $options) && $options[self::OPT_GROUP_BY] !== null)
			$sql .= " group by ".$options[self::OPT_GROUP_BY];
		return self::$db->prepExec($sql, $table_keys)->fetchColumn();
	}
	/**
	 * Get all rows from this table
	 * @param array $table_keys
	 * @param string $order
	 * @param string $limit
	 * @param boolean $ids_only
	 * @return array dbdModel
	 */
	public static function getAll($table_keys = array(), $order = null, $limit = null, $options = array())
	{
		self::ensureDB();
		$class = get_called_class();
		$tmp = array();
		$sql = "select `".self::getConstant($class, self::CONST_TABLE_KEY)."` from `".self::getConstant($class, self::CONST_TABLE_NAME)."`";
		$sql .= self::buildWhereClause($table_keys);
		if (is_array($options) && key_exists(self::OPT_GROUP_BY, $options) && $options[self::OPT_GROUP_BY] !== null)
			$sql .= " group by ".$options[self::OPT_GROUP_BY];
		if ($order !== null)
			$sql .= " order by ".$order;
		if ($limit !== null)
			$sql .= " limit ".$limit;
		foreach (self::$db->prepExec($sql, $table_keys)->fetchAll(PDO::FETCH_COLUMN, 0) as $id)
			$tmp[] = $options === true || (is_array($options) && key_exists(self::OPT_IDS_ONLY, $options) && $options[self::OPT_IDS_ONLY]) ? $id : self::getReflection($class)->newInstance($id);
		return $tmp;
	}
	/**
	 * Build where clause for count and getAll methods
	 * @param array $table_keys
	 * @return string
	 */
	private static function buildWhereClause(&$table_keys = array(), $group = false, $options = array())
	{
		$sql = "";
		$i = 0;
		$table_keys2 = array();
		foreach ($table_keys as $k => $v)
		{
			if ($group)
				$sql .= $i++ > 0 ? " or " : "";
			else
				$sql .= " ".($i++ > 0 ? "and" : "where")." ";
			if (is_int($k))
			{
				if (is_array($v))
				{
					if (key_exists(dbdDB::GROUP, $v))
					{
						$sql .= "(".self::buildWhereClause($v[dbdDB::GROUP], true).")";
						$table_keys2 = array_merge($table_keys2, $v[dbdDB::GROUP]);
					}
				}
				else
				{
					$sql .= $v;
				}
				continue;
			}
			$type = dbdDB::COMP_EQ;
			if (!is_array($v))
				$v = array($v);
			if (key_exists(dbdDB::COMP_TYPE, $v))
			{
				$type = $v[dbdDB::COMP_TYPE];
				unset($v[dbdDB::COMP_TYPE]);
			}
			switch ($type)
			{
				case dbdDB::COMP_SUBQ_LIKE:
					$sql .= "`".$k."` like ".$v[0];
					break;
				case dbdDB::COMP_LIKE:
					$sql .= "`".$k."` like :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_NLIKE:
					$sql .= "`".$k."` not like ".$v[0];
					break;
				case dbdDB::COMP_NLIKE:
					$sql .= "`".$k."` not like :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_IN:
					$sql .= "`".$k."` in";
					if (is_array($v[0]))
					{
						$sql .= "(";
						foreach ($v[0] as $j => $a)
						{
							if ($j > 0)
								$sql .= ",";
							$sql .= $a;
						}
						$sql .= ")";
					}
					else
					{

						$sql .= $v[0];
					}
					break;
				case dbdDB::COMP_IN:
					$sql .= "`".$k."` in(";
					foreach ($v[0] as $j => $a)
					{
						if ($j > 0)
							$sql .= ",";
						$sql .= ":".$k."__".$j;
						$table_keys2[$k."__".$j] = $a;
					}
					$sql .= ")";
					break;
				case dbdDB::COMP_SUBQ_NIN:
					$sql .= "`".$k."` not in";
					if (is_array($v[0]))
					{
						$sql .= "(";
						foreach ($v[0] as $j => $a)
						{
							if ($j > 0)
								$sql .= ",";
							$sql .= $a;
						}
						$sql .= ")";
					}
					else
					{

						$sql .= $v[0];
					}
					break;
				case dbdDB::COMP_NIN:
					$sql .= "`".$k."` not in(";
					foreach ($v[0] as $j => $a)
					{
						if ($j > 0)
							$sql .= ",";
						$sql .= ":".$k."__".$j;
						$table_keys2[$k."__".$j] = $a;
					}
					$sql .= ")";
					break;
				case dbdDB::COMP_SUBQ_BETWEEN:
					$sql .= "`".$k."` between ".$v[0]." and ".$v[1];
					break;
				case dbdDB::COMP_BETWEEN:
					$sql .= "`".$k."` between :".$k."__0 and :".$k."__1";
					$table_keys2[$k.'__0'] = $v[0];
					$table_keys2[$k.'__1'] = $v[1];
					break;
				case dbdDB::COMP_SUBQ_NBETWEEN:
					$sql .= "`".$k."` not between ".$v[0]." and ".$v[1];
					break;
				case dbdDB::COMP_NBETWEEN:
					$sql .= "`".$k."` not between :".$k."__0 and :".$k."__1";
					$table_keys2[$k.'__0'] = $v[0];
					$table_keys2[$k.'__1'] = $v[1];
					break;
				case dbdDB::COMP_SUBQ_GTEQ:
					$sql .= "`".$k."` >= ".$v[0];
					break;
				case dbdDB::COMP_GTEQ:
					$sql .= "`".$k."` >= :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_GT:
					$sql .= "`".$k."` > ".$v[0];
					break;
				case dbdDB::COMP_GT:
					$sql .= "`".$k."` > :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_LTEQ:
					$sql .= "`".$k."` <= ".$v[0];
					break;
				case dbdDB::COMP_LTEQ:
					$sql .= "`".$k."` <= :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_LT:
					$sql .= "`".$k."` < ".$v[0];
					break;
				case dbdDB::COMP_LT:
					$sql .= "`".$k."` < :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_NEQ:
					$sql .= "`".$k."` != ".$v[0];
					break;
				case dbdDB::COMP_NEQ:
					$sql .= "`".$k."` != :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_NULLEQ:
					$sql .= "`".$k."` <=> ".$v[0];
					break;
				case dbdDB::COMP_NULLEQ:
					$sql .= "`".$k."` <=> :".$k;
					$table_keys2[$k] = $v[0];
					break;
				case dbdDB::COMP_SUBQ_EQ:
					$sql .= "`".$k."` = ".$v[0];
					break;
				case dbdDB::COMP_EQ:
				default:
					$sql .= "`".$k."` = :".$k;
					$table_keys2[$k] = $v[0];
					break;
			}
		}
		$table_keys = $table_keys2;
		return $sql;
	}
	/**
	 * Try to manufacture a dbdModel sub class based on simple text transformations
	 * @param string $class
	 */
	public static function factory($class)
	{
		self::ensureDB();
		$table = strtolower(preg_replace("/([^A-Z]{1})([A-Z]{1})/", "$1_$2", $class));
		if (strpos($table, "s", strlen($table) - 1) !== false)
			$table .= "e";
		elseif (strpos($table, "y", strlen($table) - 1) !== false)
			$table = substr($table, 0, -1)."ie";
		$table .= "s";
		if (self::$db->tableExists($table) && ($key = self::$db->primaryKey($table)))
		{
			$model = "class ".$class." extends ".__CLASS__."\n{\n\tconst ".self::CONST_TABLE_NAME." = '".$table."';\n\tconst ".self::CONST_TABLE_KEY." = '".$key."';\n}";
			eval($model);
			file_put_contents(DBD_APP_DIR.dbdLoader::getModelDir().DBD_DS.$class.".php", "<?php\n".$model."\n?>");
		}
		else
		{
			throw new dbdException(__CLASS__.": Model for Class (".$class.") could not be generated!");
		}
	}
	/**
	 * Return the Reflection of a class
	 * @param string $class
	 * @return ReflectionClass
	 */
	private static function getReflection($class)
	{
		if (!key_exists($class, self::$reflections))
			self::$reflections[$class] = new ReflectionClass($class);
		return self::$reflections[$class];
	}
	/**
	 * Return the value of a class constant using Reflection
	 * @param string $class
	 * @param string $constant
	 * @return mixed
	 */
	private static function getConstant($class, $constant)
	{
		if (!self::getReflection($class)->hasConstant($constant))
			throw new dbdException($class."::".$constant." not defined!");
		return self::getReflection($class)->getConstant($constant);
	}
	/**
	 * Make sure we have an instance of the db
	 */
	public static function ensureDB()
	{
		if (self::$db === null)
			self::$db = dbdDB::getInstance();
	}
	/**
	 * Get the database object
	 * @return dbdDB
	 */
	public static function getDB()
	{
		return self::$db;
	}
	/**
	 * Set the database object
	 * @param dbdDB $db
	 * @return dbdDB
	 */
	public static function setDB($db)
	{
		return self::$db = $db;
	}
	/**
	 * Get the row id
	 * @return integer
	 */
	public function getID()
	{
		return $this->id;
	}
	/**
	 * Get the fields for this row
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}
	/**
	 * Magic function for setting field values
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}
	/**
	 * Magic function for getting field values
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}
	/**
	 * Magic function to check if a field is set
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name)
	{
		return isset($this->data[$name]);
	}
	/**
	 * Magic function to unset a field
	 * @param string $name
	 */
	public function __unset($name)
	{
		unset($this->data[$name]);
	}
	/**
	 * Magic function allows calling of extra magic functions for testing field values.
	 * Inlcudes: has, is, get, and set.
	 * @throws dbdException
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public function __call($name, $args)
	{
		if (preg_match("/^([a-z]{2,3})([A-Z][a-zA-Z0-9]+)$/", $name, $tmp))
		{
			$var = strtolower(preg_replace("/([^A-Z]{1})([A-Z]{1})/", "$1_$2", $tmp[2]));
			switch ($tmp[1])
			{
				case 'has':
					return isset($this->$var) && !empty($this->$var);
				case 'is':
					return (isset($this->$var) && (count($args) ? $this->$var == $args[0] : $this->$var == true));
				case 'get':
					return $this->$var;
				case 'set':
					return $this->$var = (is_array($args) && isset($args[0])) ? $args[0] : null;
			}
		}
		throw new dbdException("Method (".$this->class_name."::".$name.") does not exists!");
	}
}
?>
