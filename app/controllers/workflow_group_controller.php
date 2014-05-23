<?php
require_once 'nterchange_controller.php';

/**
 * Workflow defines
 *
 **/
define('WORKFLOW_RIGHT_EDIT', 1);
define('WORKFLOW_RIGHT_APPROVE', 2);
define('WORKFLOW_RIGHT_PUBLISH', 4);

define('WORKFLOW_ROLE_AUTHOR', 1);
define('WORKFLOW_ROLE_EDITOR', 2);
define('WORKFLOW_ROLE_APPROVER', 3);

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
 * @category   Workflow Group
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class WorkflowGroupController extends nterchangeController {
	function __construct() {
		$this->name = 'workflow_group';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_ADMIN;
		// this whole controller requires login if called directly
		$this->login_required = true;
		$this->base_dir = APP_DIR;
		$this->page_title = 'Workflow Groups';
		parent::__construct();
	}

	function index($parameter) {
		$this->redirectTo('viewlist', $parameter);
	}

	// specialized list for workflow (integrated ajax for workflow users)
	function viewlist($parameter=null) {
		$this->auto_render = false;
		$wusers_ctrl = &NController::singleton('workflow_users');
		include_once 'controller/form.php';

		$this->base_dir = APP_DIR;
		$model = &$this->loadModel($this->name);
		$pk = $model->primaryKey();
		if ($model) {
			if ($parameter) $model->{$model->primaryKey()} = $parameter;
			$model->find();
			$rows = $model->fetchAll(true);
			$wusers_model = &$wusers_ctrl->loadModel($wusers_ctrl->name);
			$workflows = '';
			// put the _headline variable in for the viewlist template
			foreach ($rows as $key=>$workflow_group) {
				$cform = new ControllerForm($wusers_ctrl, $wusers_model);
				$form = &$cform->getForm('add_person_form_' . $workflow_group[$pk]);
				$form->removeElement('workflow_group_id');
				$form->setDefaults(array('workflow_group_id'=>$workflow_group[$pk]));
				$form->addElement('hidden', 'workflow_group_id', $workflow_group[$pk]);
				$form->addElement('hidden', '_referer', urlencode($_SERVER['REQUEST_URI']));
				$cform->makeRemoteForm(array('url'=>array('controller'=>'workflow_users', 'action'=>'add_user'), 'loading'=>'workflowManager.addUserLoading(request, ' . $workflow_group[$pk] . ')', 'complete'=>'workflowManager.onAddUser(request, ' . $workflow_group[$pk] . ')'));
				if ($form->validate()) {
					$fields = $wusers_model->fields();
					if (in_array('cms_created', $fields)) {
						$wusers_model->cms_created = $model->now();
					}
					if (in_array('cms_modified', $fields)) {
						$wusers_model->cms_modified = $model->now();
					}
					// set the user id if it's applicable and available
					if (in_array('cms_modified_by_user', $fields)) {
						$wusers_model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
					}
					$success = $form->process(array($cform, 'processForm'));
				}
				$headline = $model->getHeadline();
				if (!empty($headline)) {
					if (is_array($headline)) {
						$workflow_group['_headline'] = '';
						foreach ($headline as $row) {
							$workflow_group['_headline'] .= $workflow_group['_headline']?' - ':'';
							$workflow_group['_headline'] .= $workflow_group[$row];
						}
					} else {
						$workflow_group['_headline'] = $workflow_group[$headline];
					}
				} else {
					$workflow_group['_headline'] = $workflow_group['cms_headline'];
				}
				$workflow_group['_users'] = array();
				$wusers_model->reset();
				$wusers_model->workflow_group_id = $workflow_group[$pk];
				if ($wusers_model->find()) {
					while ($wusers_model->fetch()) {
						$user_model = &$wusers_model->getLink('user_id', 'cms_auth');
						if ($user_model) {
							$user = $wusers_model->toArray();
							$user['real_name'] = $user_model->real_name;
							$workflow_group['_users'][] = $user;
						}
					}
				}
				$workflow_group['asset'] = $this->name;
				$workflow_group['add_user_form'] = $form->toHTML();
				$this->set($workflow_group);
				$workflows .= $this->render(array('action'=>'workflow_group', 'return'=>true));
				unset($form);
				unset($cform);
			}
			$this->set(array('asset'=>$this->name, 'workflows'=>$workflows));
			$main_content = $this->render(array('return'=>true));
			$sidebar_content = $this->render(array('action'=>'workflow_description', 'return'=>true));
		}
		$this->renderLayout('default', $main_content, $sidebar_content);
	}

	function delete($parameter) {
		if (empty($parameter)) {
			$this->redirectTo('viewlist');
		}
		// delete the workflow group
		$model = &$this->getDefaultModel();
		if (!$model) $this->redirectTo('viewlist');
		$model->get($parameter);
		$model->delete($parameter);
		// cascade delete the workflow_users records
		$workflow_users = &$this->loadModel('workflow_users');
		$workflow_users->workflow_group_id = $parameter;
		if ($workflow_users->find()) {
			while ($workflow_users->fetch()) {
				$workflow_users->delete();
			}
		}
		if (isset($_GET['_referer']) && $_GET['_referer']) {
			header('Location:' . urldecode($_GET['_referer']));
			exit;
		}
		$this->flash->set('notice', Inflector::humanize($this->name) . ' record deleted.');
		$this->redirectTo('viewlist');
	}
}
?>
