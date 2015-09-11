<?php
require_once 'nterchange_controller.php';
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
 * @category   Dashboard
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class DashboardController extends nterchangeController {
	function __construct() {
		$this->name = 'dashboard';
		$this->default_action = 'show';
		$this->page_title = 'Your Dashboard';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_NORIGHTS;
		$this->login_required = true;
		$this->base_view_dir = ROOT_DIR;
		parent::__construct();
	}

	function index($parameter) {
		$this->auto_render = false;
		$sidebar_content = $this->render(array('action'=>'description', 'return'=>true));
		if (SITE_DRAFTS) {
			$draft_model = &NModel::factory('cms_drafts');
			if ($draft_model) {
				$draft_model->cms_modified_by_user = $this->_auth->currentUserId();
				if ($draft_model->find()) {
					while ($draft_model->fetch()) {
						$asset_ctrl = &NController::factory($draft_model->asset);
						$asset_model = &$draft_model->getLink('asset_id', $draft_model->asset);
						if ($asset_model) {
							$this->set(array('draft'=>$draft_model->toArray(), 'asset_name'=>$asset_ctrl->page_title?$asset_ctrl->page_title:Inflector::humanize($asset_ctrl->name), 'asset'=>$draft_model->asset));
							$this->set($asset_model->toArray());
							$this->setAppend('drafts', $this->render(array('action'=>'draft_record', 'return'=>true)));
						}
						unset($asset_ctrl);
						unset($asset_model);
					}
				} else {
					$this->set('drafts', $this->render(array('action'=>'no_drafts', 'return'=>true)));
				}
			}
		}
		// load all workflow output into this variable to be assigned later
		$workflow_html = '';
		if (SITE_WORKFLOW) {
			$sidebar_content .= $this->render(array('action'=>'workflow_description', 'return'=>true));
			$user_id = $this->_auth->currentUserId();
			// If user is an admin, and has any unsubmitted workflow in groups they don't belong to, display them first
			if ($this->_auth->getAuthData('user_level') >= N_USER_ADMIN) {
				$workflow = &NController::factory('workflow');
				$workflow_model = &NModel::factory('workflow');
				$workflow_model_pk = $workflow_model->primaryKey();
				$workflow_model->cms_modified_by_user = $user_id;
				$workflow_model->submitted = 0;
				if ($workflow_model->find(array('order_by'=>'page_id'))) {
					$admin_workflow_html = '';
					$this->set('workflow_section', 'Unsubmitted Admin Workflows');
					$workflow_html .= $this->render(array('action'=>'workflow_section', 'return'=>true));
					$page_id = 0;
					$page_count = 0;
					$page_workflows = array();
					while ($workflow_model->fetch()) {
						$workflow_users_model = &NModel::factory('workflow_users');
						$workflow_users_model->workflow_group_id = $workflow_model->workflow_group_id;
						$workflow_users_model->user_id = $workflow_model->cms_modified_by_user;
						if ($workflow_users_model->find()) {
							unset($workflow_users_model);
							continue;
						}
						unset($workflow_users_model);
						$unsubmitted[] = $workflow_model->$workflow_model_pk;
						$page_content_model = &NModel::factory('page_content');
						$page_content_model->get($workflow_model->page_content_id);
						$page_model = &$page_content_model->getLink('page_id', 'page');
						$asset_controller = &NController::factory($workflow_model->asset);
						$asset_model = &$asset_controller->getDefaultModel();
						$asset_model->get($workflow_model->asset_id);
						$this->convertDateTimesToClient($asset_model);
						$action = $workflow->actionToString($workflow_model->action);
						$cascade_delete = $page_content_model->cms_workflow?true:false;
						// set the page title for the following pages
						$this->set('page_title', '');
						if ($workflow_model->page_id == $page_id) {
							$page_count++;
						} else {
							$this->set('page_title', $page_model->title);
 							$admin_workflow_html .= $this->workflowPageSubmit($page_workflows);
							$page_id = $workflow_model->page_id;
							$page_count = 0;
							$page_workflows = array();
						}
						$page_workflows[] = $workflow_model->$workflow_model_pk;
						$user = &$workflow_model->getLink('cms_modified_by_user', 'cms_auth');
						$this->set(array('process'=>'submit', 'cascade_delete'=>$cascade_delete, 'approved'=>$workflow_model->approved, 'action'=>$action, 'workflow'=>$workflow_model->toArray(), 'page'=>$page_model->toArray(), 'asset'=>$asset_controller, 'row'=>$asset_model->toArray(), 'user'=>($user?$user->toArray():false)));
						$admin_workflow_html .= $this->render(array('action'=>'workflow_record', 'return'=>true));
					}
					$admin_workflow_html .= $this->workflowPageSubmit($page_workflows);
					if ($admin_workflow_html) {
						$this->set(array('workflow_title'=>'Admin Workflows'));
						$workflow_html .= $this->render(array('action'=>'workflow', 'return'=>true)) . $admin_workflow_html;
						unset($admin_workflow_html);
					}
				}
				unset($workflow_model);
				unset($workflow);
			}
			$workflow_users = &$this->loadModel('workflow_users');
			$workflow_users->user_id = $user_id;
			if ($workflow_users->find()) {
				while ($workflow_users->fetch()) {
					// instantiate workflow group object
					$workflow_group = &$workflow_users->getLink('workflow_group_id', 'workflow_group');
					// render current workflow group
					$this->set($workflow_group->toArray());
					$workflow_html .= $this->render(array('action'=>'workflow', 'return'=>true));
					// instantiate workflow objects
					$workflow = &NController::factory('workflow');
					$workflow_model = &$workflow->getDefaultModel();
					$workflow_model_pk = $workflow_model->primaryKey();
					// find unsubmitted workflows that belong to this user
					$workflow_model->submitted = 0;
					$workflow_model->completed = 0;
					$workflow_model->workflow_group_id = $workflow_group->{$workflow_group->primaryKey()};
					$workflow_model->cms_modified_by_user = $user_id;
					$unsubmitted = array();
					if ($workflow_model->find(array('order_by'=>'page_id, asset, asset_id, id'))) {
						$this->set('workflow_section', 'Unsubmitted Workflows');
						$workflow_html .= $this->render(array('action'=>'workflow_section', 'return'=>true));
						$page_id = 0;
						$page_count = 0;
						$page_workflows = array();
						while ($workflow_model->fetch()) {
							$unsubmitted[] = $workflow_model->$workflow_model_pk;
							$page_content_model = &$workflow_model->getLink('page_content_id', 'page_content');
							if (!$page_content_model) continue;
							$page_model = &$page_content_model->getLink('page_id', 'page');
							$asset_controller = &NController::factory($workflow_model->asset);
							$asset_model = &$asset_controller->getDefaultModel();
							$asset_model->get($workflow_model->asset_id);
							$this->convertDateTimesToClient($asset_model);
							$action = $workflow->actionToString($workflow_model->action);
							// set the page title for the following pages
							$this->set('page_title', '');
							if ($workflow_model->page_id == $page_id) {
								$page_count++;
							} else {
								$this->set('page_title', $page_model->title);
								$workflow_html .= $this->workflowPageSubmit($page_workflows);
								$page_id = $workflow_model->page_id;
								$page_count = 0;
								$page_workflows = array();
							}
							$page_workflows[] = $workflow_model->$workflow_model_pk;
							$user = &$workflow_model->getLink('cms_modified_by_user', 'cms_auth');
							$this->convertDateTimesToClient($workflow_model);
							$this->set(array('process'=>'submit', 'list_only'=>false, 'approved'=>$workflow_model->approved, 'action'=>$action, 'workflow'=>$workflow_model->toArray(), 'page'=>$page_model->toArray(), 'asset'=>$asset_controller, 'row'=>$asset_model->toArray(), 'user'=>($user?$user->toArray():false)));
							$workflow_html .= $this->render(array('action'=>'workflow_record', 'return'=>true));
						}
						$workflow_html .= $this->workflowPageSubmit($page_workflows);
					}
					// find in process workflows, resetting the model object first
					$workflow_model->reset();
					$workflow_model->workflow_group_id = $workflow_group->{$workflow_group->primaryKey()};
					$workflow_model->completed = 0;
					$conditions = '';
					foreach ($unsubmitted as $id) {
						$conditions .= ($conditions?' AND ':'') . "$workflow_model_pk!=$id";
					}
					$this->set('workflow_section', 'Workflows in Process');
					$workflow_html .= $this->render(array('action'=>'workflow_section', 'return'=>true));
					$workflow_html_content = '';
					if ($workflow_model->find(array('conditions'=>$conditions, 'order_by'=>'page_id, asset, asset_id, id'))) {
						$workflow_models = array();
						while ($workflow_model->fetch()) {
							$workflow_models[] = clone($workflow_model);
						}
						$i = 0;
						$current_asset = '';
						foreach ($workflow_models as $w_model) {
							if ($w_model->submitted == 0) {
								continue;
							}
							if ($current_asset != $w_model->asset . $w_model->asset_id) {
								$current_asset = $w_model->asset . $w_model->asset_id;
								if (!$page_content_model = &$w_model->getLink('page_content_id', 'page_content')) continue;
								if (!$page_model = &$page_content_model->getLink('page_id', 'page')) continue;
								$user_def = $workflow->getWorkflowUser($w_model->workflow_group_id);
								if ($user_def) {
									$user_role = $user_def->role;
									$user_id = $user_def->user_id;
								}
								$user_rights = $workflow->getWorkflowUserRights($page_model);
								$i = 0;
							}
							$asset_controller = &NController::factory($w_model->asset);
							$asset_model = &$asset_controller->getDefaultModel();
							$asset_model->get($w_model->asset_id);
							$this->convertDateTimesToClient($asset_model);
							$action = $workflow->actionToString($w_model->action);
							$all_workflow_users = $workflow->getWorkflowUsers($workflow_model->workflow_group_id);
							if (count($all_workflow_users) < 2) {
								$i++;
							}
							if ($i == 0) {
								if ($user_rights == WORKFLOW_RIGHT_EDIT) {
									$process = 'In Process - ' . ($w_model->approved?'Approved':'Unapproved');
								} else if ($user_rights & WORKFLOW_RIGHT_EDIT) {
									// this is someone with editing rights and more. Could be the same user that submitted it.
									$process = ($w_model->approved?'In Process - Approved':'editapprove');
								} else {
									// This is someone up the line. Let them know something's coming, but they don't need to know what yet.
									if ($w_model->approved) {
										$process = 'Approved';
									} else {
										$process = 'A workflow has been started. You will be notified if/when you need to take action.';
									}
								}
							} else if ($i == 1) {
								if ($user_rights == WORKFLOW_RIGHT_EDIT) {
									$process = 'In Process - ' . ($w_model->approved?'Approved':'Unapproved');
								} else if ($user_rights & WORKFLOW_RIGHT_APPROVE && $user_rights & WORKFLOW_RIGHT_PUBLISH) {
									// this is someone with Approval rights. Could be the same user that submitted it
									$process = 'approve';
								} else {
									$process = 'In Process - ' . ($w_model->approved?'Approved':'Unapproved');
								}
							}
							$user = &$w_model->getLink('cms_modified_by_user', 'cms_auth');
							$this->convertDateTimesToClient($w_model);
							$this->set(array('process'=>$process, 'list_only'=>false, 'approved'=>$w_model->approved, 'action'=>$action, 'workflow'=>$w_model->toArray(), 'page'=>$page_model->toArray(), 'asset'=>$asset_controller, 'row'=>$asset_model->toArray(), 'user'=>($user?$user->toArray():false)));
							$workflow_html_content .= $this->render(array('action'=>'workflow_record', 'return'=>true));
							$i++;
						}
					}
					$workflow_html .= $workflow_html_content?$workflow_html_content:$this->render(array('action'=>'workflow_norecords', 'return'=>true));
					// find completed workflows, resetting the model object first
					$workflow_model->reset();
					$workflow_model->workflow_group_id = $workflow_group->{$workflow_group->primaryKey()};
					$workflow_model->completed = 1;
					$workflow_model->parent_workflow = 0;
					// bad timg - shouldn't do this here
					$workflow_html .= '<div style="background:#EEE;border:1px solid #AAA;padding:4px;">' . "\n";
					$this->set('workflow_section', 'Completed Workflows');
					$workflow_html .= $this->render(array('action'=>'workflow_section', 'return'=>true));
					if ($workflow_model->find(array('conditions'=>$conditions, 'order_by'=>'cms_created DESC', 'limit'=>5))) {
						$workflow_models = array();
						while ($workflow_model->fetch()) {
							$page_model = &NModel::factory('page');
							$page_model->{$page_model->primaryKey()} = $workflow_model->page_id;
							// if the page is not deleted, this works
							if (!$page_model->find(null, true)) {
								// otherwise, specify a deleted page and try again
								$page_model->reset();
								$page_model->{$page_model->primaryKey()} = $workflow_model->page_id;
								$page_model->cms_deleted = 1;
								$page_model->find(null, true);
							}
							$page_values = $page_model?$page_model->toArray():false;
							$asset_controller = &NController::factory($workflow_model->asset);
							$asset_model = &$asset_controller->getDefaultModel();
							if (!$asset_model->get($workflow_model->asset_id)) {
								$asset_model->reset();
								$asset_model->cms_deleted = 1;
								$asset_model->get($workflow_model->asset_id);
							}
							$this->convertDateTimesToClient($asset_model);
							$action = $workflow->actionToString($workflow_model->action);
							$user = &$workflow_model->getLink('cms_modified_by_user', 'cms_auth');
							$this->convertDateTimesToClient($workflow_model);
							$values = array('process'=>null, 'list_only'=>true, 'approved'=>$workflow_model->approved, 'action'=>$action, 'workflow'=>$workflow_model->toArray(), 'asset'=>$asset_controller, 'row'=>$asset_model->toArray(), 'page'=>$page_values, 'user'=>($user?$user->toArray():false));
							$this->set($values);
							$workflow_html .= $this->render(array('action'=>'workflow_record', 'return'=>true));
						}
					}
					$workflow_html .= '</div>' . "\n";
				}
			} else {
				$workflow_html .= $this->render(array('action'=>'no_workflows', 'return'=>true));
			}
			$this->set('workflow', $workflow_html);
		}
		$this->set('SIDEBAR_CONTENT', $sidebar_content);
		$this->setAppend('SIDEBAR_CONTENT', $this->render(array('action'=>'nterchange_training', 'return'=>true)));
		$this->setAppend('SIDEBAR_CONTENT', $this->render(array('action'=>'dashboard_client_sidebar_content', 'return'=>true)));
		$this->render(array('layout'=>'default'));
	}

	function workflowPageSubmit(&$page_workflows) {
		$this->set('page_workflows', false);
		if (count($page_workflows)) {
			$this->set('page_workflows', $page_workflows);
			return $this->render(array('action'=>'workflow_page_submit', 'return'=>true));
		}
		return '';
	}
	
	function dashboardClientContent() {
		$this->render(array('action'=>'dashboard_client_content', 'return'=>false));
	}
}
?>
