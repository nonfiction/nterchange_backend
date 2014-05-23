<?php
/**
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Value Cast
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class ValueCast extends NModel {
	function castBoolean($val) {
		return (bool) $val;
	}

	function castInt($val) {
		return (int) $val;
	}

	function castReal($val) {
		return (real) $val;
	}

	function castStr($val) {
		return (string) $val;
	}

	function castDate($val) {
		if ($val == is_int($val)) { // assume it's a timestamp
			// do nothing
		} else if (is_string($val)) {
			$val = strtotime($val);
		} else {
			$val = null;
		}
		return date('Y-m-d', $val);
	}
	function castDateTime($val) {
		if (is_int($val)) { // assume it's a timestamp
			// do nothing
		} else if (is_string($val)) {
			$val = strtotime($val);
		} else {
			$val = null;
		}
		return date('Y-m-d H:i:s', $val);
	}
	function castTime($val) {
		return $val;
	}
	function castTimestamp($val) {
		if (is_int($val)) { // assume it's a timestamp
			// do nothing
		} else if (is_string($val)) {
			$val = strtotime($val);
		}
		return $val;
	}

	function prepBoolean($val, &$db) {
		return $db->quoteSmart(ValueCast::castBoolean($val)?1:0);
	}

	function prepInt($val, &$db) {
		return $db->quoteSmart(ValueCast::castInt($val));
	}

	function prepReal($val, &$db) {
		return $db->quoteSmart(ValueCast::castFloat($val));
	}

	function prepStr($val, &$db) {
		// convert all utf-8 values to iso8859-1
		$val = ValueCast::toLatinISO($val);
		return $db->quoteSmart(ValueCast::castStr($val));
	}
	
	function prepTimestamp($val, &$db) {
		return ValueCast::castTimestamp($val);
	}
	function prepDate($val, &$db) {
		return $db->quoteSmart(ValueCast::castDate($val));
	}
	function prepDateTime($val, &$db) {
		return $db->quoteSmart(ValueCast::castDateTime($val));
	}
	function prepTime($val, &$db) {
		return $db->quoteSmart(ValueCast::castTime($val));
	}

	function prepVals($vals, &$io) {
		if (is_object($io) && is_a($io, 'InputOutput')) {
			$table = $io->getObjectTable();
			$db = &$io->db;
		} else 	if (is_string($io)) {
			$table = $io;
			include_once 'n_db.php';
			$db = &NDB::connect();
		}
		$table_info = $db->tableInfo($table);
		foreach ($vals as $field=>$val) {
			$vals[$field] = ValueCast::prepVal($field, $val, $io, $table_info);
		}
		return $vals;
	}
	
	function prepVal($field, $val, $config) {
		// cast val
		switch (true) {
			case $def & N_DAO_DATE && $def & N_DAO_TIME:
				$options = array('language'=>'en', 'format'=>'Y-m-d H:i', 'minYear'=>2000, 'maxYear'=>date('Y')+5);
				$options = $this->getFieldOptions($field, $options);
				$attributes = array();
				$attributes = $this->getFieldAttributes($field, $attributes);
				$form->addElement('date', $field, $element_label, $options, $attributes);
				break;
			case $def & N_DAO_DATE:
				$options = array('language'=>'en', 'format'=>'Y-m-d', 'minYear'=>2000, 'maxYear'=>date('Y')+5);
				$options = $this->getFieldOptions($field, $options);
				$attributes = array();
				$attributes = $this->getFieldAttributes($field, $attributes);
				$form->addElement('date', $field, $element_label, $options, $attributes);
				break;
			case $def & N_DAO_TIME:
				$options = array('language'=>'en', 'format'=>'H:i:s');
				$options = $this->getFieldOptions($field, $options);
				$attributes = array();
				$attributes = $this->getFieldAttributes($field, $attributes);
				$form->addElement('date', $field, $element_label, $options, $attributes);
				break;
			case $def & N_DAO_INT:
				$attributes = array();
				$attributes = $this->getFieldAttributes($field, $attributes);
				$form->addElement('text', $field, $element_label, $attributes);
				break;
			case $def & N_DAO_FLOAT:
				$attributes = array();
				$attributes = $this->getFieldAttributes($field, $attributes);
				$form->addElement('text', $field, $element_label, $attributes);
				break;
			case $def & N_DAO_BOOL:
				break;
			case $def & N_DAO_TXT:
				$attributes = array('rows'=>15, 'cols'=>50);
				$attributes = $this->getFieldAttributes($field, $attributes);
				$form->addElement('textarea', $field, $element_label, $attributes);
				break;
			case $def & N_DAO_BLOB:
				// do nothing here since binary fields shouldn't be displayed
				break;
			case $def & N_DAO_STR:
				$attributes = array();
				$attributes = $this->getFieldAttributes($field, $attributes);
				$form->addElement('text', $field, $element_label, $attributes);
				break;
		}

		// map form field types to db field types
		switch ($field_type) {
			case 'string':
			case 'varchar':
			case 'bpchar':
			case 'longchar':
				$val = ValueCast::prepStr($val, $db);
				break;
			case 'counter':
			case 'int':
			case 'integer':
				$val = ValueCast::prepInt($val, $db);
				break;
			case 'bool':
			case 'bit':
				$val = ValueCast::prepBoolean($val, $db);
				break;
			case 'real':
			case 'numeric':
			case 'float4':
			case 'float8':
				$val = ValueCast::prepReal($val, $db);
				break;
			case 'timestamp':
				$val = ValueCast::prepTimestamp($val, $db);
				break;
			case 'date':
				$val = ValueCast::prepDate($val, $db);
				break;
			case 'datetime':
				$val = ValueCast::prepDateTime($val, $db);
				break;
			case 'time':
				$val = ValueCast::prepTime($val, $db);
				break;
			case 'year':
				$val = ValueCast::prepInt($val, $db);
				break;
			case 'blob':
			case 'text':
			case 'longbinary':
				$val = ValueCast::prepStr($val, $db);
				break;
			default:
				$val = ValueCast::prepStr($val, $db);
		}
		return $val;
	}

	// utf-8 handling since PHP doesn't have it :(
	function toLatinISO($str) {
		return ValueCast::unicodeToIsoEntity(ValueCast::utf8ToUnicode($str));
	}

	function utf8ToUnicode($str) {
		$ret = array();
		$values = array();
		$lookingFor = 1;
		for ($i=0;$i<strlen($str);$i++) {
			$ord = ord($str[$i]);
			if ($ord < 128) $ret[] = $ord;
			else {
				if (count($values) == 0) $lookingFor = ($ord < 224)?2:3;
				$values[] = $ord;
				if (count($values) == $lookingFor) {
					$number = ($lookingFor == 3)?(($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64):(($values[0] % 32) * 64) + ($values[1] % 64);
					$ret[] = $number;
					$values = array();
					$lookingFor = 1;
				}
			}
		}
		return $ret;
	}

	function unicodeToIsoEntity($unicode) {
		$ret = '';
		if (!is_array($unicode)) {
			return $ret;
		}
		foreach($unicode as $char) {
			$ret .= ($char > 127)?'&#' . $char . ';':chr($char);
		}
		return $ret;
	}
}
?>
