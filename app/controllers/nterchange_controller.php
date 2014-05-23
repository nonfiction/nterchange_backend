<?php
require_once 'app_controller.php';
require_once 'n_server.php';

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
 * @category   nterchange
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class nterchangeController extends AppController {
	var $base_dir = APP_DIR;
	function __construct() {
		if (defined('ADMIN_URL') && constant('ADMIN_URL') != false && is_string(ADMIN_URL) && preg_match('|^/' . APP_DIR .  '/|', $_SERVER['REQUEST_URI'])) {
			if (!preg_match('|http[s]?://' . $_SERVER['SERVER_NAME'] . '|', ADMIN_URL)) {
				$loc = preg_replace('|/$|', '', ADMIN_URL) . $_SERVER['REQUEST_URI'];
				// if you don't want a 404 and want to redirect instead, comment out this line
				die(NDispatcher::error404());
				header('Location:' . $loc);
				exit;
			}
		}
		if (is_null($this->name))
			$this->name = 'nterchange';
		if (is_null($this->base_view_dir)) 
			$this->base_view_dir = BASE_DIR;
		if (!defined('IN_NTERCHANGE')) define('IN_NTERCHANGE', preg_match('|^/' . APP_DIR . '|',  NServer::env('REQUEST_URI'))?true:false);
		parent::__construct();
	}
	
	function navigation($current_section) {
		if (!isset($this->_auth)) return;
		$current_user_level = $this->_auth->getAuthData('user_level');
		// need to loop through other constructors and see 
		// if they belong in the navigation tabs
		$navigation = array();
		$navigation[] = array('title'=>'Dashboard', 'controller'=>'dashboard', 'class'=>'');
		$navigation[] = array('title'=>'Site Admin', 'controller'=>'site_admin', 'class'=>'');
		if ($current_user_level >= N_USER_ADMIN) {
			$navigation[] = array('title'=>'Content', 'controller'=>'content', 'class'=>'');
		}
		if (SITE_WORKFLOW) {
			$navigation[] = array('title'=>'Workflow', 'controller'=>'workflow_group', 'class'=>'');
		}
		$navigation[] = array('title'=>'Settings', 'controller'=>'settings', 'class'=>'right');
		$navigation[] = array('title'=>'Admin', 'controller'=>'admin', 'class'=>'right');
		if ($current_user_level < N_USER_ADMIN) {
			$navigation[] = array('title'=>'User', 'controller'=>'users', 'class'=>'right');
		}
		foreach ($navigation as $k=>$nav) {
			$ctrl = &NController::factory($nav['controller']);
			$ctrl->_auth = &$this->_auth;
			if (!$ctrl || !$ctrl->checkUserLevel()) {
				unset($navigation[$k]);
				continue;
			}
			if ($this->name == $ctrl->name || is_a($this, get_class($ctrl))) {
				$navigation[$k]['class'] .= ($navigation[$k]['class']?' ':'') . 'current';
			}
		}
		return $navigation;
	}
}
?>