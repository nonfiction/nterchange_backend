<?php
/**
 * NFlash provides a session-based temporary cache for controllers
 *
 * Sample:
 * $controller = &NController::factory('controller');
 * $controller->flash->set('key', 'value');
 * $controller->flash->get('key');
 * $controller->now('key');
 * $controller->keep('key');
 *
 * NOTE: You cannot use NFlash outside of nterchange at this moment.
 *			It's limited to nterchange in the contructor.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   NFlash
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NFlash extends Object {
	var $keeps = array();
	var $nows = array();
	var $flashes = array();
	var $flashcounts = array();

	static function &singleton() {
		static $flash;
		if (!isset($flash)) $flash = new NFlash;
		return $flash;
	}

	function set($key, $val) {
		$this->flashes[$key] = $val;
		$this->flashcounts[$key] = 0;
	}

	function get($key) {
		return isset($this->flashes[$key])?$this->flashes[$key]:false;
	}

	function now($key) {
		$this->nows[] = $key;
	}

	function keep($key) {
		$this->keeps[] = $key;
	}

	function exists($key) {
		return isset($this->flashes[$key])?true:false;
	}

	function __construct() {
    if ((defined('IN_NTERCHANGE') && IN_NTERCHANGE) || (defined('GLOBAL_SESSION_COOKIE') && GLOBAL_SESSION_COOKIE == true)) {
      @session_start();
    } elseif (defined('GLOBAL_SESSION_COOKIE') && GLOBAL_SESSION_COOKIE == false) {
      // Do nothing.
    } else {
      // Default behaviour
      @session_start();
    }

		// load any flashes from the last page
		if ((!isset($_SESSION['_flashinit']) || !$_SESSION['_flashinit']) && isset($_SESSION['_flash'])) {
			foreach ($_SESSION['_flash'] as $key=>$val) {
				$_SESSION['_flashcount'][$key]++;
				$this->flashes[$key] = $val;
				$this->flashcounts[$key] = $_SESSION['_flashcount'][$key];
			}
		}

		$_SESSION['_flashinit'] = 1;

		parent::__construct();
		register_shutdown_function(array(&$this, '__destruct'));
	}
	function __destruct() {
		// kill any un"kept" flashes
		if (isset($_SESSION['_flash'])) {
			foreach ($_SESSION['_flash'] as $key=>$val) {
				if ($_SESSION['_flashcount'][$key] > 0 && !in_array($key, $this->keeps)) {
					unset($_SESSION['_flash'][$key]);
					unset($_SESSION['_flashcount'][$key]);
					unset($this->flashes[$key]);
					unset($this->flashcounts[$key]);
				} else if (in_array($key, $this->keeps)) {
					$this->flashcounts[$key] = 0;
					$_SESSION['_flashcount'][$key] = 0;
				}
			}
		}
		// load any un"nowed" flashes
		foreach ($this->flashes as $key=>$val) {
			if (!in_array($key, $this->nows)) {
				$_SESSION['_flash'][$key] = $val;
				$_SESSION['_flashcount'][$key] = 0;
			}
		}
		$_SESSION['_flashinit'] = 0;
	}
}
?>
