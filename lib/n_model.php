<?php
require_once 'PEAR.php';
require_once 'n_object.php';
require_once 'n_db.php';
require_once 'controller/inflector.php';
require_once 'n_debug.php';
/**
 * This file is a fork from the PEAR:DB_DataObject found at
 * http://pear.php.net/package/DB_DataObject
 */

/**
 * Standard NModel defines.
 **/
define('N_DAO_CLASS_OK', 1);
define('N_DAO_ERROR', -1);
define('N_DAO_OBJECT_NOT_FOUND', -2);
define('N_DAO_OBJECT_EXISTS', -3);
define('N_DAO_FILE_NOT_FOUND', -4);
define('N_DAO_CLASS_NOT_FOUND', -5);
define('N_DAO_CLASS_ERROR', -6);
define('N_DAO_RECORD_NOT_FOUND', -7);
define('N_DAO_RECORD_EXISTS', -8);

define('N_DAO_WARNING', -1000);

define('N_DAO_INT', 1); // integer
define('N_DAO_STR', 2); // strings (varchar, char)
define('N_DAO_DATE', 4); // date fields
define('N_DAO_TIME', 8); // time fields
define('N_DAO_BOOL', 16);
define('N_DAO_TXT', 32);
define('N_DAO_BLOB', 64);
define('N_DAO_FLOAT', 128);
define('N_DAO_NOTNULL', 256);
define('N_DAO_MYSQLTIMESTAMP', 512);

if (substr(phpversion(),0,1) == 5) {
	class NModel_Overload extends Object {
		function __call($method,$args) {
			$return = null;
			$this->_call($method,$args,$return);
			return $return;
		}
		function __sleep() {
			return array_keys(get_object_vars($this)) ;
		}
		function __construct() {
			parent::__construct();
		}
		function __destruct() {
			parent::__destruct();
		}
	}
} else {
	if (!function_exists('clone')) {
		// emulate clone  - as per php_compact, slow but really the correct behaviour..
		eval('function clone($t) { $r = $t; if (method_exists($r,"__clone")) { $r->__clone(); } return $r; }');
	}
	eval('
		class NModel_Overload extends Object {
			function __call($method,$args,&$return) {
				return $this->_call($method,$args,$return);
			}
			function __construct() {
				parent::__construct();
			}
			function __destruct() {
				parent::__destruct();
			}
		}
	');
}

/**
 * NModel - Object Based Database Query Builder and data store
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Model
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @author		Alan Knowles <alan@akbkhome.com>
 * @copyright  	1997-2007 nonfiction studios inc. (not sure if this is correct)
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class NModel extends Object implements ArrayAccess {
	/**
	 * Container for the PEAR::DB object
	 *
	 * @var object
	 * @access private
	 */
	var $_db = null;
	/**
	 * Name of the table the model is associated with.
	 *
	 * This value is set in the __construct of the classes extending NModel
	 *
	 * @var string
	 * @access private
	 */
	var $__table = null;
	/**
	 * Holds the table config
	 *
	 * Holds a hash of field=>definition values for each table field.
	 * Definitions are computed values of the bit-value types:
	 * N_DAO_INT, N_DAO_STR, N_DAO_DATE, N_DAO_TIME,
	 * N_DAO_BOOL, N_DAO_TXT, N_DAO_BLOB, N_DAO_FLOAT,
	 * N_DAO_NOTNULL, N_DAO_MYSQLTIMESTAMP
	 *
	 * @see N_Model::table()
	 * @var array
	 * @access private
	 */
	var $_config = array();
	/**
	 * The fields that acts as the primary_key
	 *
	 * @see NModel::primaryKey()
	 * @var string
	 * @access public
	 */
	var $primary_key = 'id';
	/**
	 * Simple array with a list of the tables fields.
	 *
	 * @see NModel::fields()
	 * @var string
	 * @access private
	 */
	var $_fields = array();
	/**
	 * Holds the most recent DB_Result object
	 *
	 * @see NModel::_query()
	 * @var object
	 * @access private
	 */
	var $_result = null;
	/**
	 * Holds the number of rows selected/affected by the last query
	 *
	 * @see NModel::_query()
	 * @var int
	 * @access private
	 */
	var $_numrows = null;
	/**
	 * Tracks the current row in a fetch loop
	 *
	 * @see NModel::fetch()
	 * @var int
	 * @access private
	 */
	var $_current_row = 0;
	/**
	 * Holds the last executed sql query string
	 *
	 * @see NModel::fetch()
	 * @var string
	 * @access private
	 */
	var $_last_query = null;
	/**
	 * Holds a virgin copy of the the _query array
	 *
	 * @see NModel::_query
	 * @var array
	 * @access private
	 */
	var $_query_orig = null;
	/**
	 * A query array used to build final queries
	 *
	 * @see NModel::find()
	 * @var array
	 * @access private
	 */
	var $_query = null;
	/**
	 * The SELECT portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_select = '*';
	/**
	 * The WHERE portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_conditions = '';
	/**
	 * The GROUP BY portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_group_by = '';
	/**
	 * The ORDER BY portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_order_by = '';
	/**
	 * The HAVING portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_having = '';
	/**
	 * The LIMIT portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_limit = '';
	/**
	 * The OFFSET for the limit portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_offset = '0';
	/**
	 * The JOIN (inner, outer, left, etc) portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_join = '';
	/**
	 * The INCLUDE portion of the string to be part of the default query
	 *
	 * @see NModel::_buildQuery()
	 * @var string
	 * @access private
	 */
	var $_include = '';

	var $table_name_prefix = '';
	var $table_name_suffix = '';

	// form settings

	/**
	 * Field to read for the Form Header in CRUD operations
	 *
	 * Refers to a table field
	 *
	 * @todo This needs to get moved to the NModel class
	 * @var string
	 * @access public
	 */
	var $form_header = '_headline';

	/**
	 * Field to use for record display
	 *
	 * Can either be a string, referring to a table field or
	 * an array, referring to multiple fields
	 *
	 * @todo This needs to get moved to the NModel class
	 * @var mixed
	 * @access public
	 */
	var $_headline = null;
	/**
	 * Predefine fields that the auto-interpreter will get wrong
	 *
	 * The interpreter doesn't really get textareas, selects or a couple
	 * others. If you want to customize a field for the form, add it here.
	 *
	 * Usage:
	 * $this->form_elements['field_name'] = array('select', 'field_name', 'Field Label', array('select'=>'select', 'options'=>'options'));
	 *
	 * @var array
	 * @access public
	 */
	var $form_elements = array();
	/**
	 * Show only these fields
	 *
	 * @var array
	 * @access public
	 */
	var $form_display_fields = array();
	/**
	 * Do not show these fields
	 *
	 * @var array
	 * @access public
	 */
	var $form_ignore_fields = array();
	/**
	 * Default values for specific fields
	 *
	 * Usage:
	 * $this->form_field_defaults['field_name'] = 'default_value';
	 *
	 * @var array
	 * @access public
	 */
	var $form_field_defaults = array();
	/**
	 * Label names for specific fields
	 *
	 * Labels are auto-created, but if you want them customized,
	 * simply change them here
	 *
	 * Usage:
	 * $this->form_field_labels['field_name'] = 'Form Field Label';
	 *
	 * @var array
	 * @access public
	 */
	var $form_field_labels = array();
	/**
	 * Special options for the field element
	 *
	 * Usage:
	 * $this->form_field_options['field_name'] = array('option'=>'value');
	 *
	 * @var array
	 * @access public
	 */
	var $form_field_options = array();
	/**
	 * Special html attributes for the field element
	 *
	 * Usage:
	 * $this->form_field_attributes['field_name'] = array('class'=>'fieldclass');
	 *
	 * @var array
	 * @access public
	 */
	var $form_field_attributes = array();
	/**
	 * Validation check - required fields
	 *
	 * @var array
	 * @access public
	 */
	var $form_required_fields = array();
	/**
	 * Validation check - rules
	 *
	 * @var array
	 * @access public
	 */
	var $form_rules = array();
	/**
	 * Fields that will require bitmask functionality
	 *
	 * @var array
	 * @access public
	 */
	var $bitmask_fields = array();
	/**
	 * Delete the associated file uploads
	 */
	var $_delete_uploads = true;

	function __construct() {
		$db = &NDB::connect();
		if (PEAR::isError($db)) {
			die('Can\'t connect to the db.');
		} else {
			$this->_db = &$db;
		}
		$this->_loadConfig();
		foreach ($this->_config as $field=>$def) {
			$this->_fields[] = $field;
			$this->$field = null;
		}
		$this->table_name_prefix = defined('DB_TABLE_NAME_PREFIX')?constant('DB_TABLE_NAME_PREFIX'):'';
		$this->table_name_suffix = defined('DB_TABLE_NAME_SUFFIX')?constant('DB_TABLE_NAME_SUFFIX'):'';

		// load $this->_query with defaults
		// the defaults can be overridden in child classes
		$this->_buildQuery();
		// save the original query before we can do any damage
		$this->_query_orig = $this->_query;
		parent::__construct();
	}

	static function &factory($model) {
		$model_class = Inflector::camelize($model);
		$path = '%s/app/models/%s.php';
		if (file_exists(sprintf($path, ROOT_DIR, $model))) {
			include_once sprintf($path, ROOT_DIR, $model);
		} else if (file_exists(sprintf($path, BASE_DIR, $model))) {
			include_once sprintf($path, BASE_DIR, $model);
		}
		if (class_exists($model_class)) {
			$ret = new $model_class;
		} else {
			// TODO: raise an error here
			$ret = false;
		}
		return $ret;
	}

	/**
	 * Singleton pattern to return the same model from wherever it is called
	 *
	 * @param string $model - should be an underscored word
	 * @see NModel::factory
	 * @return object
	 */
	function &singleton($model) {
		static $models;
		if (!isset($models)) $models = array();
		$key = md5($model);
		if (!isset($models[$key])) {
			$models[$key] = &NModel::factory($model);
		}
		return $models[$key];
	}

	function tableName($add_prefix_suffix = false) {
		return $add_prefix_suffix?$this->table_name_prefix . $this->__table . $this->table_name_suffix:$this->__table;
	}

	function setHeadline($var) {
		$this->_headline = $var;
	}

	function getHeadline() {
		return $this->_headline;
	}

	function makeHeadline($separator = '-') {
		$str = '';
		$fields = $this->fields();
		$headline = $this->getHeadline()?$this->getHeadline():(in_array('cms_headline', $fields)?'cms_headline':null);
		if (is_array($headline)) {
			foreach ($headline as $field) {
				// Deal with foreign keys so the the custom headline isn't just id numbers.
				if (eregi('_id$', $field)) {
					$model_name = eregi_replace('_id$', '', $field);
					if ($model_instance = $this->getLink($field, $model_name)) {
						$new_headline = $model_instance->cms_headline;
						$str .= ($str?" $separator ":'') . $new_headline;
					}
				} else {
					$str .= ($str?" $separator ":'') . $this->$field;
				}
			}
		} else if ($headline) {
			$str .= $this->$headline;
		}
		return $str;
	}

	function get($v=null) {
		if (!isset($this->_query)) {
			// TODO: raise an error
			return false;
		}
		// $v is the primary key
		if (empty($v)) {
			return false;
		}
		$pk = $this->primaryKey();
		$this->$pk = $v;
		return $this->find(null, true);
	}

	function find($options = array(), $autofetch = false) {
		if (!isset($this->_query)) {
			// TODO: raise an error
			return false;
		}
		$db = &$this->_db;
		$query = $this->_query;
		$first_only = isset($options['first']) && $options['first'];
		if ($first_only) {
			$query['limit'] = 1;
			$query['offset'] = 0;
			if (isset($options['limit'])) unset($options['limit']);
			if (isset($options['offset'])) unset($options['offset']);
		}
		if (is_array($options)) {
			foreach ($query as $key=>$val) {
				$query[$key] = isset($options[$key]) && $options[$key]?$options[$key]:$query[$key];
			}
		} else if (is_int($options) || is_string($options)) {
			// Should I be doing this?
			$pk = $this->primaryKey();
			$this->$pk = (int) $options;
		}
		$query = $this->_buildConditions($query, $this->table());
		$sql = 'SELECT' .
			' ' . $query['select'] .
			' FROM ' . $this->tableName(true) .
			($query['join']?' ' . $query['join']:'') .
			($query['include']?' ' . $query['include']:'') .
			($query['conditions']?' WHERE ' . $query['conditions']:'') .
			($query['group_by']?' GROUP BY ' . $query['group_by']:'') .
			($query['having']?' HAVING ' . $query['having']:'') .
			($query['order_by']?' ORDER BY ' . $query['order_by']:'');
		if (!empty($query['limit']) && strlen($query['limit'] . $query['offset'])) {
			$db->setLimit($query['limit'], $query['offset']);
		}
		$this->debug('SQL: ' . $sql, N_DEBUGTYPE_SQL);
		$this->_query($sql);
		if (NModel::isError($this->_result)) {
			// TODO: raise an error here? or return it?
			return false;
		}
		if ($autofetch) {
			$this->fetch();
		}
		return $this->_numrows;
	}

	function fetch() {
		if (empty($this->_numrows)) {
			return false;
		}
		if (NModel::isError($this->_result)) {
			// an error should have been return by the find(). Even if it was ignored, just return false
			return false;
		}
		$arr = $this->_result->fetchRow(MDB2_FETCHMODE_ASSOC);
		if ($arr === null) {
			// this is likely the end of the data
			return false;
		}

		$table = $this->table();
		foreach ($arr as $k=>$v) {
			$this->$k = $v;
		}
		$this->_current_row++;
		if (isset($this->_query)) {
			unset($this->_query);
		}
		return true;
	}

	function fetchAll($to_array = false) {
		if (empty($this->_numrows)) {
			return array();
		}
		if (PEAR::isError($this->_result)) {
			return false;
		}
		$ret = array();
		while ($this->fetch()) {
			$ret[] = $to_array?$this->toArray():clone($this);
		}
		return $ret;
	}

	function toArray() {
		$fields = $this->fields();
		$ret = array();
		foreach ($fields as $field) {
			$ret[$field] = $this->$field;
			if (isset($this->{'_' . $field})) {
				$ret['_' . $field] = $this->{'_' . $field}->toArray();
			}
		}
		return $ret;
	}

	/**
	 * offsetExists - ArrayAccess method
	 * if $this->key is set, array_key_exists('key', $this) will be true
	 */
	public function offsetExists($offset) {
		return method_exists($this, $offset) || property_exists($this, $offset);
	}

	/**
	 * offsetGet - ArrayAccess method
	 * @param  string $offset the array key, eg: $this['key']
	 * @return mixed  the value stored at $this->key
	 * @throws Exception If key doesn't exist
	 */
	public function offsetGet($offset) {
		if (property_exists($this, $offset))
			return $this->$offset;
		elseif (method_exists($this, $offset))
			return $this->$offset();
		else
			$this->debug("Attempted access to undefined offset: $offset" , N_DEBUGTYPE_INFO);
	}

	/**
	 * offsetSet - ArrayAccess method
	 * @param  string $offset The name of the property
	 * @param  mixed  $value
	 */
	public function offsetSet($offset, $value) {
		$this->$offset = $value;
	}

	/**
	 * offsetUnset - ArrayAccess method
	 * @param  string $offset The key to unset
	 */
	public function offsetUnset($offset) {
		if (property_exists($this, $offset))
				unset($this->$offset);
	}

	/**
	 * Magic Method for undefined property
	 * Provides support for Uniform Access Principle
	 * $this->method_name will call $this->method_name()
	 * if the property doesn't exist
	 */
	public function __get($name){
		if (method_exists($this, $name))
			return $this->$name();
	}


	function save($dao = false, $convertTimeToUTC = true) {
		$pk = $this->primaryKey();
		return isset($this->$pk) && isset($this->$pk)?$this->update($dao,$convertTimeToUTC):$this->insert($dao);
	}

	/**
		* insert creates the sql for a database insert and executes it
		*
		* @access public
		* @param object $dao
		* @return int
	 */
	function insert($dao=false) {
		$pk = $this->primaryKey();

		$db = &$this->_db;
		$dsn = $db->getDSN('array');
		$dbtype = $dsn['phptype'];
		$fields = $this->table();
		$fields_str = '';
		$val_str = '';
		$this->beforeCreate();
		foreach ($fields as $k=>$def) {
			// if it's null or doesn't exist, then skip it
			if (!isset($this->$k)) {
				continue;
			}
			// don't write to the primary key
			if ($k == $pk) {
				continue;
			}
			// can't insert into a mysql timestamp
			if ($def & N_DAO_MYSQLTIMESTAMP) {
				continue;
			}
			// if it's started, put a comma
			$fields_str .= $fields_str?', ':'';
			$val_str .= $val_str?', ':'';
			// auto-add $k  since we know it will be used at this point
			$fields_str .= $k;
			//  	Since an insert does an update anyways - this isn't needed on insert.
			//if ($def & N_DAO_TIME && !preg_match('/^cms_/', $k)) {
			//	$this->$k = $this->convertTimeToUTC($k, $this->$k);
			//}
			// handle null values (if 'null' string)
			if ('null' === strtolower($this->$k) && !($def & N_DAO_NOTNULL)) {
				$val_str .= 'NULL';
				continue;
			}
			// add the value as a string
			if ($def & N_DAO_STR) {
				$val_str .= $db->quote($this->$k, 'text');
				continue;
			}
			// add the value as an ints
			if (is_numeric($this->$k)) {
				$val_str .= $this->$k;
				continue;
			}
			// add the value as a string by default
			$val_str .= $db->quote($this->$k);
		}
		if ($fields_str) {
			$sql = 'INSERT INTO ' . $this->tableName(true) . " ({$fields_str}) VALUES ({$val_str})";
			$this->debug('SQL: ' . $sql, N_DEBUGTYPE_SQL);
			$res = &$this->exec($sql);

			if (PEAR::isError($res)) {
				// TODO: raise error (insert error)
				return false;
			}
			if ($res < 1) {
				return 0;
			}

			// get the id and set/return it
			if ($pk) {
				if ($this->$pk = $this->_db->lastInsertID($this->tableName(true), $pk)) {
					$this->afterCreate($this->$pk);
					return $this->$pk;
				}
			}
			return true;
		}
		// TODO: raise error (no values passed)
		return false;
	}

	function update($dao = false, $convertTimeToUTC = true) {
		$pk = $this->primaryKey();
		if (empty($this->$pk)) {
			// TODO: raise error
			return false;
		}

		$db = &$this->_db;
		$fields = $this->table();
		$set_str = '';
		$this->beforeUpdate();
		foreach ($fields as $k=>$def) {
			// if it's null or doesn't exist, then skip it
			if (!isset($this->$k)) {
				continue;
			}
			// don't write to the primary key
			if ($k == $pk) {
				continue;
			}
			// can't insert into a mysql timestamp
			if ($def & N_DAO_MYSQLTIMESTAMP) {
				continue;
			}
			// if it's started, put a comma
			$set_str .= $set_str?', ':' ';
			// convert from user-entered time to the server's time zone
			if ($def & N_DAO_TIME && !preg_match('/^cms_/', $k) && $convertTimeToUTC) {
				$this->$k = $this->convertTimeToUTC($k, $this->$k);
			}
			// handle null values (if 'null' string)
			if ('null' === strtolower($this->$k) && !($def & N_DAO_NOTNULL)) {
				$set_str .= $k . '=NULL';
				continue;
			}
			// add the value as a string
			if ($def & N_DAO_STR) {
				$set_str .= $k . '=' . $db->quote($this->$k);
				continue;
			}
			// add the value as an int
			if (is_numeric($this->$k)) {
				$set_str .= $k . '=' . $this->$k;
				continue;
			}
			// add the value as a string by default
			$set_str .= $k . '=' . $db->quote($this->$k);
		}
		$sql = 'UPDATE ' . $this->tableName(true) . ' SET' . $set_str . ' WHERE ' . $pk . '=' . $this->$pk;
		$this->debug('SQL: ' . $sql, N_DEBUGTYPE_SQL);
		$res = &$this->exec($sql);
		if (PEAR::isError($res)) {
			// TODO: raise error
			return false;
		} else {
			$this->afterUpdate($res);
		}
		return $res;
	}

	function delete() {
		$pk = $this->primaryKey();
		$before_delete_pk = $this->$pk;
		if (empty($this->$pk)) {
			// TODO: raise error
			return false;
		}
		$fields = $this->fields();
		if ($this->_delete_uploads) { $this->deleteUploadFolder(); }
		if (in_array('cms_deleted', $fields)) {
			$this->cms_deleted = 1;
			$this->beforeDelete();
			$updated_id = $this->update();
			$this->afterDelete($before_delete_pk);
			return $updated_id;
		}
		// otherwise, actually delete it
		$this->beforeDelete();
		$sql = 'DELETE FROM ' . $this->tableName(true) . ' WHERE ' . $pk . '=' . $this->$pk;
		$this->debug('SQL: ' . $sql, N_DEBUGTYPE_SQL);
		$res = &$this->exec($sql);
		if (PEAR::isError($res)) {
			// TODO: raise error
			return false;
		} else {
			$this->afterDelete($before_delete_pk, 1);
		}
		return $res;
	}

	function deleteUploadFolder(){
		require_once 'lib/n_filesystem.php';
		$pk = $this->primaryKey();
		if (!$this->$pk) return;
		$upload_dir = UPLOAD_DIR."/{$this->tableName(true)}/{$this->$pk}";
		NFilesystem::deletePathRecursive($upload_dir);
	}

	function &getLink($field, $model) {
		$model = &NModel::factory($model);
		if (isset($this->$field) && $model && $model->get($this->$field)) {
			return $model;
		}
		unset($model);
		$ret = false;
		return $ret;
	}

	function reset() {
		$table = $this->table();
		foreach ($table as $key=>$def) {
			$this->$key = null;
		}
		$this->_query = $this->_query_orig;
		$this->_current_row = 0;
		return true;
	}

	function quote($val) {
		return $this->_db->quote($val);
	}

	function query($sql) {
		return $res = &$this->_db->query($sql);
	}

	function exec($sql) {
		return $res = &$this->_db->exec($sql);
	}

	function loadQuery($sql) {
		$this->reset();
		$this->_query($sql);
	}

	function _query($sql) {
		$db = &$this->_db;

		// let the db backend handle the transactions
		// we'll just provide keyworkds
		if (strtoupper($sql) == 'BEGIN') {
			$db->autoCommit(false);
			return true;
		}
		if (strtoupper($sql) == 'COMMIT') {
			$db->commit();
			$db->autoCommit(true);
			return true;
		}
		if (strtoupper($sql) == 'ROLLBACK') {
			$db->rollback();
			$db->autoCommit(true);
			return true;
		}
		// actually do the query
		$res = &$this->query($sql);
		$this->_last_query = $sql;

		if (MDB2::isError($res)) {
			// TODO: what to do with the error?
			return false;
		}
		switch (strtolower(substr(trim($sql),0,6))) {
			case 'insert':
			case 'update':
			case 'delete':
			return $db->affectedRows();
		}
		if (is_object($res)) {
			$this->_result = &$res;
		}
		$this->_numrows = 0;
		if (method_exists($res, 'numrows')) {
			$db->expectError(MDB2_ERROR_UNSUPPORTED);
			$this->_numrows = $res->numrows();
			if (MDB2::isError($this->_numrows)) {
				$this->_numrows = 1;
			}
			$db->popExpect();
		}
		unset($this->_query);
	}

	function _buildConditions($query, $keys, $filter=array()) {
		foreach ($keys as $key=>$def) {
			// if it's not in the filter, don't include it
			if (!empty($filter) && !in_array($key, $filter)) {
				continue;
			}
			// if the field is cms_deleted and it's null, then set to 0 (non-deleted);
			if ($key == 'cms_deleted' && !isset($this->$key)) {
				$this->$key = 0;
			}
			// if it's null, don't include it.
			if (!isset($this->$key)) {
				continue;
			}
			// it will definitely get added at this point, so check the condition
			$query['conditions'] .= $query['conditions']?' AND':'';
			// if it's "null" and it's a null field in the db
			if ((strtolower($this->$key) === 'null') && !($def & N_DAO_NOTNULL)) {
				$query['conditions'] .= ' ' . $this->tableName(true) . ".$key IS NULL";
				continue;
			}
			// if it's a string field in the db, then quote it
			if ($def & N_DAO_STR) {
				$query['conditions'] .= ' ' . $this->tableName(true) . ".$key" . '=' . $this->quote(($def & N_DAO_BOOL?(bool)$this->$key:$this->$key));
				continue;
			}
			// if it's a number, then just add it directly
			if (is_numeric($this->$key)) {
				$query['conditions'] .= ' ' . $this->tableName(true) . ".$key" . '=' . $this->$key;
				continue;
			}
			// it shouldn't get to this point, but in case it does, cast it to an int...
			$query['conditions'] .= ' ' . $this->tableName(true) . ".$key" . '=' . (int)$this->$key;
		}
		return $query;
	}

	function _buildQuery() {
		$this->_query = array();
		$this->_query['select'] = $this->_select;
		$this->_query['conditions'] = $this->_conditions;
		$this->_query['group_by'] = $this->_group_by;
		$this->_query['order_by'] = $this->_order_by;
		$this->_query['having'] = $this->_having;
		$this->_query['limit'] = $this->_limit;
		$this->_query['offset'] = $this->_offset;
		$this->_query['join'] = $this->_join;
		$this->_query['include'] = $this->_include;

	}

	// This is a default method to happen on the model data before every create.
	function beforeCreate() {
		// Call this from the model instance.
	}

	// This is a default method to happen on the model data after every create.
	function afterCreate($result_id=null) {
		// Call this from the model instance.
	}

	// This is a default method to happen on the model data before every update.
	function beforeUpdate() {
		// Call this from the model instance.
	}

	// This is a default method to happen on the model data after every update.
	function afterUpdate($result_id=null) {
		// Call this from the model instance.
	}

	// This is a default method to happen on the model data before every delete.
	function beforeDelete() {
		// Call this from the model instance.
	}

	// This is a default method to happen on the model data after every delete.
	function afterDelete($result_id=null, $destroyed=0) {
		// Call this from the model instance.
	}

	/**
	 * Run a pre-upload hook before uploading a file
	 *
	 * This method may be overridden to handle all file fields. To handle a
	 * specific field, for example 'large_image', define a method on your model
	 * like this:
	 *
	 * function beforeUpload_large_image($filepath) {
	 *     // process file
	 *     return $filepath;
	 * }
	 */
	function beforeUpload($field, $filepath) {
		$hook = "beforeUpload_$field";
		if (method_exists($this, $hook)) {
			$filepath = $this->$hook($filepath);
		}
		return $filepath;
	}

	// TODO: id shouldn't be assumed - need to set up some key finding code. Also needs key descriptions in the _config
	function primaryKey($key=null) {
		if (is_string($key) && $key) {
			$this->primary_key = $key;
		}
		return $this->primary_key;
	}

	function table() {
		$this->_loadConfig();
		return $this->_config;
	}

	function fields() {
		return $this->_fields;
	}

	function lastQuery() {
		return $this->_last_query;
	}

	function currentRow() {
		$this->_current_row;
	}

	function numRows() {
		return $this->_numrows;
	}

	function now() {
		include_once 'n_date.php';
		// can put formats for different dbs here
		return NDate::now();
	}

	function convertTimeToUTC($field, $value) {
		// make sure the value is there and doesn't equal "null"
		// "null" is a special-case which gets changed to NULL in the sql
		if ($value && $value != 'null') {
			$table = $this->table();
			$def = $table[$field];
			// can put DB-specific date formatting here...
			$format = '%Y-%m-%d %H:%M:%S';
			if (N_DAO_TIME & $def && !(N_DAO_DATE & $def)) {
				// we can get away with strtotime() on time values (no date)
				$value = date('Y-m-d H:i:s', strtotime($value));
				$format = '%H:%M:%S';
			}
			include_once 'n_date.php';
			$rvalue = NDate::convertTimeToUTC($value, $format);
			if (!$rvalue) {
				// if it's false then pass it back or string nullify it (which is handled in insert/update)
				$value = N_DAO_NOTNULL & $def?$value:'null';
			} else {
				$value = $rvalue;
			}
		}
		return $value;
	}

	function _loadConfig() {
		if (!empty($this->_config)) return;
		if (ENVIRONMENT == 'production') {
			// build the cache dir if it's not there
			$cache_dir = CACHE_DIR . '/ntercache/db/';
			include_once 'n_filesystem.php';
			$dir_built = file_exists($cache_dir)?true:NFileSystem::buildDirs($cache_dir);
			if ($dir_built) {
				include_once 'Cache/Lite.php';
				$cache = new Cache_Lite(array('cacheDir'=>$cache_dir, 'automaticSerialization'=>true, 'lifeTime'=>60*60*24*7));
				if ($config = $cache->get($this->tableName(), 'tableconfig')) {
					$this->_config = $config;
					$this->debug('Loaded cached table config for: ' . $this->tableName());
					return;
				}
			}
		}
		$defs = $this->tableInfo($this->tableName(true));
		if (PEAR::isError($defs)) {
			// TODO: raise error about table not existing.
			$this->debug($defs->getMessage() . "\n" . $defs->getUserInfo(), N_DEBUGTYPE_INFO, PEAR_LOG_ERR);
			return;
		}
		$db = &$this->_db;
		$db->loadModule('Datatype');
		$fields = array();
		foreach ($defs as $def) {
			if (is_array($def))
				$fields[$def['name']] = (object) $def;
		}
		foreach($fields as $field=>$def) {
			$config = $this->getFieldType($field);
			if ($config) {
				$this->_config[$field] = $config;
			}
		}
		$this->debug('Created table config for: ' . $this->tableName());
		if (ENVIRONMENT == 'production') {
			$cache->save($this->_config, $this->tableName(), 'tableconfig');
			$this->debug('Cached table config for: ' . $this->tableName());
		}
	}

	function getFieldType($field) {
		if (is_object($field)) {
			return false;
		}
		if (is_string($field)) {
			$this->_db->loadModule('Reverse');
			$field_defs = $this->_db->reverse->getTableFieldDefinition($this->tableName(), $field);
			if (PEAR::isError($field_defs)) {
				// TODO: throw an error here
				return false;
			}
		}
		$type_map = array(
						'text'      => N_DAO_STR,
						'boolean'   => N_DAO_BOOL,
						'integer'   => N_DAO_INT,
						'decimal'   => N_DAO_FLOAT,
						'float'     => N_DAO_FLOAT,
						'date'      => N_DAO_DATE,
						'time'      => N_DAO_TIME,
						'timestamp' => N_DAO_DATE + N_DAO_TIME,
						'clob'      => N_DAO_TXT,
						'blob'      => N_DAO_BLOB
						);
		$n_type = 0;
		$dsn = $this->_db->getDSN('array');
		$notnull_set = false;
		foreach ($field_defs as $field_def) {
			$type = $field_def['mdb2type'];
			$length = isset($field_def['length'])?$field_def['length']:null;
			$unsigned = isset($field_def['unsigned'])?$field_def['unsigned']:null;
			$n_type += array_key_exists($type, $type_map)?$type_map[$type]:0;
			$n_type += ($field_def['notnull'] && !($n_type & N_DAO_NOTNULL))?N_DAO_NOTNULL:0;
		}
		return $n_type;
	}

	function tableInfo($result, $mode = null) {
		$this->_db->loadModule('Reverse');
		$res = $this->_db->reverse->tableInfo($result, $mode);
		return $res;
	}

	/**
	 * Return a textual error message for a NModel error code
	 *
	 * @param integer error code
	 *
	 * @return string error message, or false if the error code was
	 * not recognized
	 */
	function errorMessage($value) {
		static $errorMessages;
		if (!isset($errorMessages)) {
			$errorMessages = array(
				N_DAO_ERROR => 'unknown error',
				N_DAO_OBJECT_NOT_FOUND => 'the object could not be found',
				N_DAO_OBJECT_EXISTS => 'an object by that name already exists',
				N_DAO_FILE_NOT_FOUND => 'the file could not be found',
				N_DAO_CLASS_NOT_FOUND => 'the class could not be found',
				N_DAO_CLASS_ERROR => 'unkown class error',
				N_DAO_RECORD_EXISTS => 'an info object by that name already exists',
				N_DAO_RECORD_NOT_FOUND => 'the class record could not be found'
			);
		}
		if (NModel::isError($value)) {
			$value = $value->getCode();
		}
		return isset($errorMessages[$value]) ? $errorMessages[$value] : $errorMessages[IO_ERROR];
	}

	function isError($value) {
		return (is_object($value) &&
				(get_class($value) == 'NModelError' ||
				 is_subclass_of($value, 'NModelError')));
	}

	// utility functions
	function debug($message, $debug_type = N_DEBUGTYPE_INFO, $log_level = PEAR_LOG_DEBUG, $ident=false) {
		if (!$ident) {
			$ident = (isset($this) && is_a($this, __CLASS__))?get_class($this):__CLASS__;
		}
		NDebug::debug($message, $debug_type, $log_level, $ident);
	}
}


class NModelError extends PEAR_Error {
	/**
	* NModelError constructor.
	*
	* @param mixed      NModel error code, or string with error message.
	* @param integer    what "error mode" to operate in
	* @param integer    what error level to use for $mode & PEAR_ERROR_TRIGGER
	* @param mixed      additional debug info, such as the last query
	*
	* @access public
	*
	* @see PEAR_Error
	*/
	function Model_Error($code = N_DAO_ERROR, $mode = PEAR_ERROR_RETURN, $level = E_USER_NOTICE, $debuginfo = null) {
		if (is_int($code)) {
			$this->PEAR_Error('N_DAO Error: ' . NModel::errorMessage($code), $code, $mode, $level, $debuginfo);
		} else {
			$this->PEAR_Error("N_DAO Error: $code", N_DAO_ERROR, $mode, $level, $debuginfo);
		}
	}
}

if (version_compare( phpversion(), "5") < 0) {
	overload('NModel');
}
