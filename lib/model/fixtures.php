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
 * @category   Fixtures
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class Fixtures {
	var $loaded_fixtures = array();
	var $default_file_filter = '/\.ya?ml$/';
	var $all_loaded_fixtures = array();

	var $connection = null;
	var $table_name = null;
	var $class_name = null;
	var $file_filter = null;
	var $fixture_path = array();

	function Fixtures($connection, $table_name, $class_name, $fixture_path, $file_filter = null) {
		$this->connection = &$connection;
		$this->table_name = $table_name;
		$this->fixture_path = $fixture_path;
		$this->file_filter = $file_filter?$file_filter:$this->default_file_filter;
		$this->class_name = $class_name?$class_name:Inflector::camelize($table_name);

		$this->_readFixtureFiles();
	}

	function createFixtures($fixture_path, $fixture_table_names, $fixture_class_names) {
		$fixtures = array();
		$connection = &NDB::connect();
		$i = 0;
		foreach ($fixture_table_names as $table_name) {
			$fixtures[$table_name] = new Fixtures($connection, $table_name, $fixture_class_names[$i]?$fixture_class_names[$i]:null, $fixture_path . $table_name);
			$i++;
		}
		// $this->all_loaded_fixtures = array_merge($this->all_loaded_fixtures, $fixtures_map);
		foreach (array_reverse($fixtures) as $f) {
			$f->deleteExistingFixtures();
		}
		foreach ($fixtures as $f) {
			$f->insertFixtures();
		}
		return count($fixtures) > 1?$fixtures:$fixtures[0];
	}

	function deleteExistingFixtures($reset_pk = true) {
		$this->connection->exec("DELETE FROM $this->table_name");
	}

	function insertFixtures() {
		foreach ($this->loaded_fixtures as $fixture) {
			$this->connection->exec("INSERT INTO {$this->table_name} (" . $fixture->keyList() . ") VALUES (" . $fixture->valueList() . ")");
		}
	}

	function _readFixtureFiles() {
		if (file_exists($yaml_file_path = $this->_yamlPath())) {
			$yaml_string = file_get_contents($yaml_file_path);
			include_once 'vendor/spyc.php';
			if ($yaml = Spyc::YAMLLoad($yaml_string)) {
				foreach ($yaml as $name=>$data) {
					$this->loaded_fixtures[$name] = new Fixture($data, $this->class_name);
				}
			}
		} else if (file_exists($csv_file = $this->_csvPath())) {
			// not supported yet
			return false;
		}
	}

	function _yamlPath() {
		return $this->fixture_path . '.yml';
	}

	function _csvPath() {
		return $this->fixture_path . '.csv';
	}
}

class Fixture {
	var $fixture = array();
	var $class_name = null;

	function Fixture($fixture, $class_name) {
		if (is_array($fixture)) {
			$this->fixture = $fixture;
		} else {
			return false;
		}
		$this->class_name = $class_name;
	}

	function find($class_name) {
		return class_exists($class_name);
	}

	function toHash() {
		return $this->fixture;
	}

	function keyList() {
		return implode(', ', array_map(create_function('$k', '$db=&NDB::connect();return $db->quoteIdentifier($k);'), array_keys($this->fixture)));
	}

	function valueList() {
		return implode(', ', array_map(create_function('$k', '$db=&NDB::connect();return $db->quote($k);'), $this->fixture));
	}
}
?>
