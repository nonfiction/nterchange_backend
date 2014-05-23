<?php
require_once 'n_object.php';
/**
 * NServer
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Server Information
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class NServer extends Object {
	/**
	 * Returns the REQUEST_URI from the server environment, or, failing that,
	 * constructs a new one, using the PHP_SELF constant and other variables.
	 *
	 * @return string URI
	 */
	static function setUri() {
		if (NServer::env('REQUEST_URI')) {
			$uri = NServer::env('REQUEST_URI');
		} else {
			if (NServer::env('argv')) {
				$uri = NServer::env('argv');
				$uri = NServer::env('PHP_SELF') .'/'. $uri[0];
			} else {
				$uri = NServer::env('PHP_SELF') .'/'. NServer::env('QUERY_STRING');
			}
		}
		return $uri;
	}

	static function env($key) {
		if (isset($_SERVER[$key])) {
			return $_SERVER[$key];
		} else if (isset($_ENV[$key])) {
			return $_ENV[$key];
		} else if (getenv($key) !== false) {
			return getenv($key);
		}
		if ($key == 'DOCUMENT_ROOT') {
			$offset = 0;
			if (!strpos(NServer::env('SCRIPT_NAME'), '.php')) {
				$offset = 4;
			}
			return substr(NServer::env('SCRIPT_FILENAME'), 0, strlen(NServer::env('SCRIPT_FILENAME')) - (strlen(NServer::env('SCRIPT_NAME')) + $offset));
		}
		if ($key == 'PHP_SELF') {
			return r(NServer::env('DOCUMENT_ROOT'), '', NServer::env('SCRIPT_FILENAME'));
		}
		return null;
	}
}
?>
