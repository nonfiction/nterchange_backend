<?php
require_once 'n_object.php';
require_once 'Log.php';

/**
 * Standard NDebug defines.
 **/
define('N_DEBUGTYPE_INFO',    1);
define('N_DEBUGTYPE_ASSET',   2);
define('N_DEBUGTYPE_PAGE',    4);
define('N_DEBUGTYPE_CACHE',   8);
define('N_DEBUGTYPE_MENU',   16);
define('N_DEBUGTYPE_AUTH',   32);
define('N_DEBUGTYPE_SQL',    64);
define('N_DEBUGTYPE_ALL',   127);

/**
 * NDebug provides an integrated debugging interface which
 * logs to file any calls within the current debugging
 * range (0 = Emergency, 7 = Debug)
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Debugging
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NDebug extends Object {

	static function debug($message, $debug_type = N_DEBUGTYPE_INFO, $log_level = PEAR_LOG_DEBUG, $ident=false) {
		$debug_level = defined('DEBUG_LEVEL')?constant('DEBUG_LEVEL'):0;
		if (empty($debug_level) || (is_numeric($debug_level) &&  $debug_level < $log_level)) {
			return;
		}
		$debug_type_setting = defined('DEBUG_TYPE')?constant('DEBUG_TYPE'):0;
		if (empty($debug_type_setting) || !is_numeric($debug_type_setting) || !($debug_type_setting & $debug_type)) {
			return;
		}
		// make message intelligible
		if (!is_string($message)) {
			$message = print_r($message,true);
		}
		$filename = NDebug::getFilename($debug_type);
		if ($ident == false) $ident = ucwords(APP_NAME);
		$log = Log::singleton('file', NDebug::getDir() . $filename, $ident);
		$log->log($message, $log_level);
		unset($log);
	}

	static function getFilename($debug_type) {
		$filename = date('Y-m-d');
		if ($debug_type == N_DEBUGTYPE_SQL) {
			$filename = 'sql_' . $filename;
		}
		return $filename;
	}

	static function getDir() {
		$dir = defined('CACHE_DIR')?CACHE_DIR . '/logs/':'/tmp/';
		return $dir;
	}
}
?>
