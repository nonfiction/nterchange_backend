<?php
include_once 'nterchange_controller.php';
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
 * @category   nterchange Administration
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class AdminController extends nterchangeController {
	var $user_level_required = N_USER_ROOT;
	var $name = 'admin';

	function __construct() {
		parent::__construct();
	}

	function index() {
		$this->redirectTo(array('users', 'viewlist'));
	}

	function viewlist($parameter) {
		$this->loadSubnav($parameter);
		parent::viewlist($parameter);
	}
	function create($parameter) {
		$this->loadSubnav($parameter);
		parent::create($parameter);
	}
	function edit($parameter) {
		$this->loadSubnav($parameter);
		parent::edit($parameter);
	}

	function &getDefaultModel() {
		return false;
	}

	function loadSidebar($parameter) {
		$this->set('SIDEBAR_TITLE', $this->page_title . ' Info');
		$this->setAppend('SIDEBAR_CONTENT', $this->render(array('action'=>'sidebar_content', 'return'=>true)));
	}

	function loadSubnav($parameter) {
		$subnav = array();
		$subnav[] = array('title'=>'Users', 'controller'=>'users', 'action'=>'viewlist', 'id'=>$parameter, 'class'=>'');
		$subnav[] = array('title'=>'Audit Trail', 'controller'=>'audit_trail', 'action'=>'viewlist', 'id'=>$parameter, 'class'=>'');
		$subnav[] = array('title'=>'Templates', 'controller'=>'page_template', 'action'=>'viewlist', 'id'=>$parameter, 'class'=>'');
		$subnav[] = array('title'=>'Assets', 'controller'=>'cms_asset_info', 'action'=>'viewlist', 'id'=>$parameter, 'class'=>'');
		foreach ($subnav as $k=>$nav) {
			if ($nav['controller'] == $this->name && $nav['action'] == $this->action) {
				$subnav[$k]['class'] = 'current';
			}
		}
		$this->set('subnav', $subnav);
	}
}
?>
