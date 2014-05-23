<?php
/**
 * require PEAR:Auth and n_db.php
 */
require_once 'Auth.php';
require_once 'n_db.php';

/**
 * Some NAuth defines.
 **/
define('N_USER_NORIGHTS', 0);
define('N_USER_EDITOR', 1);
define('N_USER_ADMIN', 2);
define('N_USER_ROOT', 3);

/**
 * The 'NAuth' class is a PEAR:Auth extension to provide authentication and 
 * login support where needed in the application
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Authorization
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NAuth extends Auth {
	function __construct() {
		$db = &NDB::connect();
		$options['table'] = 'cms_auth';
		$options['usernamecol'] = 'username';
		$options['passwordcol'] = 'password';
		$options['dsn'] = &$db;
		$options['db_fields'] = 'id, real_name, user_level, status';
		$options['cryptType'] = 'md5';
		$options['db_options'] = array();
		
		parent::Auth('MDB2', $options, array($this, 'showLogin'));
		$this->setLoginCallback($this, 'loginCallBack');
		$this->setLogoutCallback($this, 'loginCallBack');
		if (!$this->checkAuth()) {
			$this->start();
		}
		if (isset($_GET['logout']) && $_GET['logout']) {
			$this->logout();
			$this->start();
			exit;
		}
	}
	function NAuth() {
		$this->__construct();
	}

	function showLogin($username, $status, &$auth) {
		$path = '/' . APP_DIR . '/login';
		if (!preg_match('|^' . $path. '|', $_SERVER['REQUEST_URI'])) {
			$referer = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
			$qs = '';
			foreach ($_GET as $k=>$v) {
				if ($k != 'logout')
					$qs .= ($qs?'&':'?') . "$k=$v";
			}
			$referer .= $qs;
			header('Location:' . $path . '?_referer=' . urlencode($referer));
			exit;
		}
	}

	function loginCallBack($username, &$auth) {
		
	}

	function logoutCallBack($username, &$auth) {
		
	}

	function statusMessage($status) {
		// AUTH_IDLED, AUTH_EXPIRED, AUTH_WRONG_LOGIN, AUTH_METHOD_NOT_SUPPORTED, AUTH_SECURITY_BREACH
		switch ($status) {
			case AUTH_IDLED:
				$status_msg = 'You were idle for too long and your session was automatically reset.';
				break;
			case AUTH_EXPIRED:
				$status_msg = 'Your session has expired.';
				break;
			case AUTH_WRONG_LOGIN:
				$status_msg = 'Either your username or password were incorrect. Please check your information and try again.';
				break;
			default:
				$status_msg = 'Something went wrong. Please login again.';
				break;
		}
		return $status_msg;
	}

	function currentUserID() {
		$user_id = (int) $this->getAuthData('id');
		return $user_id?$user_id:0;
	}
}
?>