<?php
/**
 * NObject
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Object
 * @author     	Tim Glen <tim@nonfiction.ca>, Andy VanEe <andy@nonfiction.ca>
 * @copyright  	2005-2014 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
if (substr(phpversion(),0,1) == 4) {
	class Object {
		function Object() {
			$args = func_get_args();
			// register_shutdown_function(array(&$this, '__destruct'));
			call_user_func_array(array(&$this, '__construct'), $args);
		}

		function __construct() {
		}

		function __destruct() {
		}

		function toString() {
			return get_class($this);
		}
	}
} else {
	class Object {
		function __construct(){}

		function toString() {
			return get_class($this);
		}
	}
}
