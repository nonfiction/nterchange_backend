<?php
require_once 'workflow_group_controller.php';
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
 * @category   Workflow Users
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class WorkflowUsersController extends AppController {
	function __construct() {
		$this->name = 'workflow_users';
		// this whole controller requires login if called directly
		$this->login_required = true;
		$this->base_view_dir = BASE_DIR;
		$this->base_dir = APP_DIR;
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_ADMIN;
		parent::__construct();
	}

	function preGenerateForm() {
		parent::preGenerateForm();
	}

	function addUser() {
		$model = &$this->getDefaultModel();
		if (isset($_POST['_referer'])) unset($_POST['_referer']);
		if ($this->insert()) {
			require_once 'vendor/JSON.php';
			$json = new Services_JSON();
			if ($html = $this->loadUser($model->{$model->primaryKey()})) {
				$data = array('id'=>$model->{$model->primaryKey()}, 'data'=>$html);
				print 'result = ' . $json->encode($data);
			}
		}
	}

	function deleteUser($parameter) {
		if (empty($parameter)) {
			print 'success=false;';
			return;
		}
		// load the model layer with info
		$model = &$this->getDefaultModel();
		if (!$model) {
			$this->render(array('nothing'=>true));
			return;
		}
		$model->get($parameter);
		$model->delete($parameter);
		$this->render(array('nothing'=>true));
	}

	function loadUser($parameter) {
		$this->auto_render = false;
		$model = &$this->loadModel($this->name);
		if ($model && $model->get($parameter)) {
			$user_model = &$model->getLink('user_id', 'cms_auth');
			if ($user_model) {
				$user = $model->toArray();
				$user['real_name'] = $user_model->real_name;
			}
			$this->set($user);
			return $this->render(array('action'=>'workflow_user', 'return'=>true));
		}
		$this->render(array('nothing'=>true));
	}

	function listUser($parameter) {
		print $this->loadUser($parameter);
	}
}
?>
