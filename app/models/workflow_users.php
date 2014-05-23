<?php
require_once 'n_model.php';
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
 * @category   Workflow Users Model
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class WorkflowUsers extends NModel {
	function __construct() {
		$this->__table = 'workflow_users';
		$this->_order_by = 'workflow_group_id, cms_created DESC';
		parent::__construct();
		// load the workgroup constants
		include_once BASE_DIR . '/app/controllers/workflow_group_controller.php';
		$this->form_elements['workflow_group_id'] = array('foreignkey', 'workflow_group_id', 'Workflow Group', array('model'=>'workflow_group', 'headline'=>'workflow_title', 'addEmptyOption'=>true));
		$this->form_elements['user_id'] = array('foreignkey', 'user_id', 'User', array('model'=>'cms_auth', 'headline'=>'real_name', 'addEmptyOption'=>true));
		$this->form_elements['role'] = array('select', 'role', 'Role', array(''=>'Select...', WORKFLOW_ROLE_AUTHOR=>'Author', WORKFLOW_ROLE_EDITOR=>'Editor', WORKFLOW_ROLE_APPROVER=>'Approver'));
		$this->form_required_fields = array('workflow_group_id', 'user_id', 'role');
	}
}
?>