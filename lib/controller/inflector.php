<?php
require_once 'n_object.php';
/**
 * Inflector class grammatically changes tense, pluralization and camelization etc.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Inflector
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class Inflector extends Object {
	function __construct () {
		parent::__construct();
	}

	/**
	  * Returns given $lower_case_and_underscored_word as a camelCased word.
	  *
	  * @param string $lower_case_and_underscored_word Word to camelize
	  * @return string Camelized word. likeThis.
	  */
	static function camelize($lower_case_and_underscored_word) {
		return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
	}

	/**
	  * Returns an underscore-syntaxed ($like_this_dear_reader) version of the $camel_cased_word.
	  *
	  * @param string $camel_cased_word Camel-cased word to be "underscorized"
	  * @return string Underscore-syntaxed version of the $camel_cased_word
	  */
	static function underscore($camel_cased_word) {
		$camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2', $camel_cased_word);
		return strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2', $camel_cased_word));
	}

	/**
	  * Returns a human-readable string from $lower_case_and_underscored_word,
	  * by replacing underscores with a space, and by upper-casing the initial characters.
	  *
	  * @param string $lower_case_and_underscored_word String to be made more readable
	  * @return string Human-readable string
	  */
	static function humanize($lower_case_and_underscored_word) {
		return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
	}

	/**
	  * Returns corresponding table name for given $class_name. ("posts" for the model class "Post").
	  *
	  * @param string $class_name Name of class to get database table name for
	  * @return string Name of the database table for given class
	  */
	static function tableize($class_name) {
		return Inflector::underscore($class_name);
	}

	/**
	  * Returns Cake model class name ("Post" for the database table "posts".) for given database table.
	  *
	  * @param string $tableName Name of database table to get class name for
	  * @return string
	  */
	static function classify($tableName) {
		return Inflector::camelize($tableName);
	}

	/**
	  * Returns $class_name in underscored form, with "_id" tacked on at the end.
	  * This is for use in dealing with foreign keys in the database.
	  *
	  * @param string $class_name
	  * @return string
	  */
	static function foreignKey($class_name) {
		return Inflector::underscore($class_name) . "_id";
	}
}

?>
