<?php
require_once 'app/controllers/audit_trail_controller.php';
require_once 'workflow_group_controller.php';

/**
 * Workflow defines.
 *
 **/
define('WORKFLOW_ACTION_EDIT', 1);
define('WORKFLOW_ACTION_DELETE', 2);
define('WORKFLOW_ACTION_ADDNEW', 3);
define('WORKFLOW_ACTION_ADDEXISTING', 4);
define('WORKFLOW_ACTION_REMOVE', 5);

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
 * @category   Workflow
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class WorkflowController extends nterchangeController {
	var $workflow_users = array();

	function __construct() {
		$this->name = 'workflow';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_NORIGHTS;
		// this whole controller requires login if called directly
		$this->login_required = true;
		$this->base_dir = APP_DIR;
		parent::__construct();
	}

	function index() {
		$this->redirectTo(array('dashboard'));
	}

	function viewlist() {
		$this->redirectTo(array('dashboard'));
	}

	function process($workflow_id) {
		$this->auto_render = false;
		$model = &$this->getDefaultModel();
		$model->completed = 0;
		// get the workflow info
		if (!$model->get($workflow_id)) {
			$this->redirectTo('index');
		}
		// push to the client's time zone
		$this->convertDateTimesToClient($model);
		// load the workflow group
		$workflow_group = &$model->getLink('workflow_group_id', 'workflow_group');
		// get the asset info for the email
		$asset_ctrl = &NController::factory($model->asset);
		$asset_model = &$asset_ctrl->getDefaultModel();
		if(!$asset_ctrl || !$asset_model->get($model->asset_id)) {
			$this->redirectTo('index');
		}
		$asset_name = $asset_ctrl->page_title?$asset_ctrl->page_title:Inflector::humanize($asset_ctrl->name);
		// load the workflow draft into the asset_model for the form
		$workflow_values = unserialize($model->draft);
		foreach ($workflow_values as $field=>$val) {
			$asset_model->$field = $val;
		}
		// check for the content being linked to any other pages
		$page_content_model = &NModel::factory('page_content');
		$page_contents = &$page_content_model->getActivePageContent($asset_ctrl->name, $asset_model->{$asset_model->primaryKey()});
		$pages = false;
		if ($model->action == WORKFLOW_ACTION_EDIT && $page_contents && count($page_contents) > 1) {
			$pages = array();
			$tmp_page_model = clone($page_model);
			foreach ($page_contents as $page_content) {
				if (!isset($pages[$tmp_page_model->{$tmp_page_model->primaryKey()}])) {
					$tmp_page_model->reset();
					$tmp_page_model->get($page_content->page_id);
					$pages[$tmp_page_model->{$tmp_page_model->primaryKey()}] = $tmp_page_model->toArray();
				}
			}
			if (count($pages) == 1) {
				$pages = false;
			}
			unset($tmp_page_model);
		}
		unset($page_contents);
		$this->set('pages', $pages);
		unset($pages);
		// get the page info for the email
		$page_content_model = &$model->getLink('page_content_id', 'page_content');
		$page = &NController::singleton('page');
		$page_model = $page->getDefaultModel();
		if (!$page_model->get($page_content_model->page_id)) {
			$this->redirectTo('index');
		}
		// set up urls
		$public_site = preg_replace('|/$|', '', PUBLIC_SITE);
		$admin_site = preg_replace('|/$|', '', (defined('ADMIN_URL') && ADMIN_URL?ADMIN_URL:PUBLIC_SITE));
		$live_url = $public_site . $page->getHref($page_model->toArray());
		$page->nterchange = true;
		$preview_url = $admin_site . $page->getHref($page_model->toArray());
		// user rights
		$user_rights = &$this->getWorkflowUserRights($page_model);
		if ($user_rights & WORKFLOW_RIGHT_EDIT && !($user_rights & WORKFLOW_RIGHT_APPROVE)) {
			// only an author, can't approve anything
			$this->redirectTo('index');
		}
		$description = '';
		switch ($model->action) {
			case WORKFLOW_ACTION_EDIT:
				$description = 'This content is being edited on the page.';
				break;
			case WORKFLOW_ACTION_DELETE:
				$description = 'This content is being deleted.';
				break;
			case WORKFLOW_ACTION_ADDNEW:
				$description = 'This is new content being added to the page.';
				break;
			case WORKFLOW_ACTION_ADDEXISTING:
				$description = 'This is existing content being added to the page.';
				break;
			case WORKFLOW_ACTION_REMOVE:
				$description = 'The content is being removed from the page.';
				break;
		}
		$this->set('description', $description);
		// set up the form
		$cform = new ControllerForm($asset_ctrl, $asset_model);
		$form = &$cform->getForm();
		if (SITE_DRAFTS) {
			// remove the submit draft button since we're in workflow
			$form->removeElement('__submit_draft__');
		}
		// remove the submit button since we're in workflow
		$form->removeElement('__submit__');
		// get the workflow form
		$wcform = new ControllerForm($this, $model);
		$wform = &$wcform->getForm();
		$submit = &$wform->getElement('__submit__');
		$submit->setName('__submit_workflow__');
		if ($user_rights & WORKFLOW_RIGHT_PUBLISH) {
			$submit->setValue('Submit & Publish');
		} else {
			$submit->setValue('Submit');
		}
		// add workflow form to the existing form
		foreach ($wform->_elements as $el) {
			$form->addElement($el);
		}
		$form->addFormRule(array(&$this, 'validateWorkflowProcess'));
		/*
		if (!($user_rights & WORKFLOW_RIGHT_EDIT)) {
			$fields = $asset_model->fields();
			foreach ($fields as $i=>$field) {
				if ($form->elementExists($field)) {
					$el = &$form->getElement($field);
					$el->freeze();
				}
			}
		}
		*/
		if ($model->action == WORKFLOW_ACTION_REMOVE) {
			$fields = $asset_model->fields();
			$form->removeElement('timed_start');
			$form->removeElement('timed_end');
			$form->freeze($fields);
		}

		// act on the submission
		if ($form->validate()) {
			$values = $form->getSubmitValues();
			$model->comments = $values['comments'];
			$fields = $asset_model->fields();
			$draft_values = array();
			foreach ($fields as $field) {
				if (isset($values[$field]))
					$draft_values[$field] = $values[$field];
			}
			$auth = new NAuth();
			$user_id = $auth->currentUserID();
			unset($auth);

			if ($values['workflow_approve'] == 0) {
				// if not approved, then email the original user and set the workflow unsubmitted
				if ($parent_workflow = $model->parent_workflow) {
					$model->delete();
					$model->reset();
					$model->get($parent_workflow);
					$model->approved = 0;
				} else {
					$model->submitted = 0;
				}
				if (isset($draft_values)) {
					$model->draft = serialize($draft_values);
				}
				$model->comments = $values['comments'];
				include_once 'n_date.php';
				$model->cms_created = NDate::convertTimeToUTC($model->cms_created, '%Y-%m-%d %H:%M:%S');
				$model->cms_modified = NDate::convertTimeToUTC($model->cms_modified , '%Y-%m-%d %H:%M:%S');
				$model->update();
				$current_user = &NModel::factory('cms_auth');
				$current_user->get($user_id);
				$user_model = &NModel::factory('cms_auth');
				$user_model->get($model->cms_modified_by_user);
				include_once 'Mail.php';
				$mail = &Mail::factory('mail', "-f{$current_user->email}");
				$headers['From'] = "{$current_user->real_name} <{$current_user->email}>";
				$headers['Subject'] = 'Website: "' . $workflow_group->workflow_title . '" Workflow Group has content that was declined';
				$msg = '';
				$msg .= "The workflow for the \"{$asset_model->cms_headline}\" {$asset_name} record on the {$page_model->title} page was declined.\n\n";
				$msg .= "COMMENTS:\n{$model->comments}\n\n";
				$msg .= "You can view the current live page at:\n$live_url\n\n";
				$msg .= "You can preview the page at:\n$preview_url\n\n";
				$msg .= "To Edit & Resubmit your changes, please go to Your Dashboard:\n" . $admin_site . '/' . APP_DIR . "/dashboard\n\n";
				$this->set('public_site', $public_site);
				$this->set('admin_site', $admin_site);
				// gather the users
				$recipients = array();
				$email = "{$user_model->real_name} <{$user_model->email}>";
				$recipients[] = $email;
				$mail->send($recipients, $headers, $msg);
				unset($mail);
				if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
					// audit trail
					$audit_trail = &NController::factory('audit_trail');
					$audit_trail->insert(array('asset'=>$model->asset, 'asset_id'=>$model->asset_id, 'action_taken'=>AUDIT_ACTION_WORKFLOW_DECLINE, 'workflow_id'=>$model->{$model->primaryKey()}, 'workflow_group_id'=>$model->workflow_group_id, 'page_id'=>$model->page_id, 'page_content_id'=>$model->page_content_id));
					unset($audit_trail);
				}
				$this->render(array('layout'=>'plain', 'action'=>'disapproved'));
				exit;
			} else {
				// if approved, then set any comments, set the workflow approved, insert a new one
				// either submit it (which emails it to the next users)
				// or publish it (if there are no next users)
				if ($user_rights & WORKFLOW_RIGHT_PUBLISH) {
					// publish by pulling the draft, updating the original asset and marking page_content.cms_workflow=0
					if (isset($draft_values)) {
						foreach ($draft_values as $field=>$val) {
							$asset_model->$field = $val;
						}
					}
					$asset_model->cms_modified = $asset_model->now();
					$asset_model->cms_modified_by_user = $user_id;
					$asset_model->cms_draft = 0;
					$asset_model->update();
					// kill a draft if one exists
					$workflow_model = &NModel::factory('workflow');
					$workflow_model->parent_workflow = 0;
					$workflow_model->asset = $model->asset;
					$workflow_model->asset_id = $model->asset_id;
					$workflow_model->workflow_group_id = $model->workflow_group_id;
					if ($workflow_model->find(null, true)) {
						// delete drafts related to this workflow
						$draft_model = &NModel::factory('cms_drafts');
						$draft_model->asset = $model->asset;
						$draft_model->asset_id = $model->asset_id;
						$draft_model->cms_modified_by_user = $workflow_model->cms_modified_by_user;
						if ($draft_model->find()) {
							while ($draft_model->fetch()) {
								$draft_model->delete();
							}
						}
					}
					if ($workflow_model->action == WORKFLOW_ACTION_REMOVE) {
						// delete the page_content to detach
						if ($page_content_model->{$page_content_model->primaryKey()}) {
							$page_content_model->delete();
						}
						unset($page_content_model);
						$page->deletePageCache($model->page_id);
						if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
							// audit trail
							$audit_trail = &NController::factory('audit_trail');
							$audit_trail->insert(array('asset'=>$model->asset, 'asset_id'=>$model->asset_id, 'action_taken'=>AUDIT_ACTION_WORKFLOW_APPROVEREMOVE, 'workflow_id'=>$model->{$model->primaryKey()}, 'workflow_group_id'=>$model->workflow_group_id, 'page_id'=>$model->page_id, 'page_content_id'=>$model->page_content_id));
							unset($audit_trail);
						}
						$this->completeWorkflow($workflow_id);
						$this->set('workflow_group', $workflow_group->toArray());
						$this->set('asset_name', $asset_name);
						$this->set('asset', $asset_model->toArray());
						$this->set('page', $page_model->toArray());
						$this->render(array('action'=>'removed', 'layout'=>'default'));
						exit;
					} else {
						// update the page_content row
						if ($page_content_model->{$page_content_model->primaryKey()}) {
							$timed_start = NDate::arrayToDate($values['timed_start']);
							$timed_end = NDate::arrayToDate($values['timed_end']);
							if (!NDate::validDateTime($timed_start)) {
								$timed_start = 'null';
							}
							if (!NDate::validDateTime($timed_end)) {
								$timed_end = 'null';
							}
							// set the timed content in page_content
							$page_content_model->timed_start = $timed_start;
							$page_content_model->timed_end = $timed_end;
							// set page_content cms_workflow to 0, allowing the content to show up
							$page_content_model->cms_workflow = 0;
							$page_content_model->update();
						}
						unset($page_content_model);
						$this->completeWorkflow($workflow_id);
						$page->deletePageCache($page_model->{$page_model->primaryKey()});
						if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
							// audit trail
							$audit_trail = &NController::factory('audit_trail');
							$audit_trail->insert(array('asset'=>$model->asset, 'asset_id'=>$model->asset_id, 'action_taken'=>AUDIT_ACTION_WORKFLOW_APPROVEPUBLISH, 'workflow_id'=>$model->{$model->primaryKey()}, 'workflow_group_id'=>$model->workflow_group_id, 'page_id'=>$model->page_id, 'page_content_id'=>$model->page_content_id));
							unset($audit_trail);
						}
						$this->set('workflow_group', $workflow_group->toArray());
						$this->set('asset_name', $asset_name);
						$this->set('asset', $asset_model->toArray());
						$this->set('page', $page_model->toArray());
						$this->render(array('action'=>'published', 'layout'=>'default'));
						exit;
					}
				} else {
					$model->approved = 1;
					$model->comments = $values['comments'];
					$timed_start = isset($values['timed_start'])?NDate::arrayToDate($values['timed_start']):null;
					$timed_end = isset($values['timed_end'])?NDate::arrayToDate($values['timed_end']):null;
					if (!NDate::validDateTime($timed_start)) {
						$timed_start = 'null';
					}
					if (!NDate::validDateTime($timed_end)) {
						$timed_end = 'null';
					}
					$model->timed_start = $timed_start;
					$model->timed_end = $timed_end;
					include_once 'n_date.php';
					$model->cms_created = NDate::convertTimeToUTC($model->cms_created, '%Y-%m-%d %H:%M:%S');
					$model->cms_modified = NDate::convertTimeToUTC($model->cms_modified , '%Y-%m-%d %H:%M:%S');
					$model->update();
					$parent_workflow = $model->{$model->primaryKey()};
					$new_workflow = &NModel::factory($this->name);
					$new_workflow->page_id = $model->page_id;
					$new_workflow->page_content_id = $model->page_content_id;
					$new_workflow->workflow_group_id = $model->workflow_group_id;
					$new_workflow->asset = $model->asset;
					$new_workflow->asset_id = $model->asset_id;
					$new_workflow->action = $model->action;
					$new_workflow->draft = serialize($draft_values);
					$new_workflow->comments = $values['comments'];
					$new_workflow->timed_start = $timed_start;
					$new_workflow->timed_end = $timed_end;
					$new_workflow->parent_workflow = $parent_workflow;
					$new_workflow->cms_created = $new_workflow->now();
					$new_workflow->cms_modified = $new_workflow->now();
					$new_workflow->cms_modified_by_user = $user_id;
					$new_workflow->insert();
					if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
						// audit trail
						$audit_trail = &NController::factory('audit_trail');
						$audit_trail->insert(array('asset'=>$model->asset, 'asset_id'=>$model->asset_id, 'action_taken'=>AUDIT_ACTION_WORKFLOW_APPROVE, 'workflow_id'=>$model->{$model->primaryKey()}, 'workflow_group_id'=>$model->workflow_group_id, 'page_id'=>$model->page_id, 'page_content_id'=>$model->page_content_id));
						unset($audit_trail);
					}
					$this->flash->set('notice', 'Your workflow step has been submitted and notifications have been sent.');
					$this->redirectTo('submit', $new_workflow->{$new_workflow->primaryKey()});
				}
			}
		}
		unset($page_content_model);
		// set up the view
		$this->set('workflow_group', $workflow_group->toArray());
		$this->set('asset_name', $asset_name);
		$this->set('asset', $asset_model->toArray());
		$this->set('page', $page_model->toArray());
		$this->set('form', $form->toHTML());
		$this->render(array('layout'=>'plain', 'action'=>'process'));
	}

	function validateWorkflowProcess($values) {
		$errors = array();
		if ($values['workflow_approve'] == 0 && !$values['comments']) {
			$errors['comments'] = 'You must supply comments if you do not approve the workflow';
		}
		return count($errors)?$errors:true;
	}

	function completeWorkflow($workflow_id) {
		if (!$workflow_id) return;
		$model = &NModel::factory($this->name);
		if ($model->get($workflow_id)) {
			$parent_workflow = $model->parent_workflow;
			$model->approved = 1;
			$model->completed = 1;
			$model->update();
			$this->completeWorkflow($parent_workflow);
		}
		unset($model);
		return true;
	}

	function submit($workflow_id) {
		if (false !== strpos($workflow_id, ';')) {
			$workflow_id = explode(';', $workflow_id);
		} else {
			$workflow_id = array($workflow_id);
		}
		$this->auto_render = false;
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		// get the workflow info
		$workflows = array();
		foreach ($workflow_id as $id) {
			$model->reset();
			$model->submitted = 0;
			if ($model->get($id)) {
				$this->convertDateTimesToClient($model);
				$workflows[] = clone($model);
			}
		}
		if (empty($workflows)) {
			$this->redirectTo('index');
		}
		$html = '';
		foreach ($workflows as $model) {
			// push the times back so they can get pushed forward again on update()
			// get the asset info for the email
			$asset_ctrl = &NController::factory($model->asset);
			$asset_model = &NModel::factory($model->asset);
			if(!$asset_ctrl || !$asset_model->get($model->asset_id)) {
				$this->redirectTo('index');
			}
			$asset_name = $asset_ctrl->page_title?$asset_ctrl->page_title:Inflector::humanize($asset_ctrl->name);
			// get the page info for the email
			$page_content_model = $workflows[0]->getLink('page_content_id', 'page_content');
			$page = &NController::singleton('page');
			$page_model = &$page->getDefaultModel();
			$page_model->reset();
			if (!$page_model->get($page_content_model->page_id)) {
				$this->redirectTo('index');
			}
			$live_url = preg_replace('/\/$/', '', PUBLIC_SITE) . $page->getHref($page_model->toArray());
			$page->nterchange = true;
			$preview_url = preg_replace('/\/$/', '', (defined('ADMIN_URL') && ADMIN_URL?ADMIN_URL:PUBLIC_SITE)) . $page->getHref($page_model->toArray());
			$dashboard_url = preg_replace('/\/$/', '', (defined('ADMIN_URL') && ADMIN_URL?ADMIN_URL:PUBLIC_SITE)) . '/' . APP_DIR . '/dashboard';
			$auth = new NAuth();
			$user_model = &NModel::factory('cms_auth');
			$user_model->get($auth->currentUserId());
			unset($auth);
			$workflow_group = &$workflows[0]->getLink('workflow_group_id', 'workflow_group');
			// get the users
			$user_rights = $this->getWorkflowUserRights($page_model, $user_model->{$user_model->primaryKey()});
			$users = $this->getNotifyUsers($model->$pk, $user_rights);

/*
varDump('###################');
varDump($user_rights);
foreach ($users as $user) {
	varDump($user->toArray());
}
exit;
*/

			include_once 'Mail.php';
			$mail = &Mail::factory('mail', "-f{$user_model->email}");
			$headers['From'] = "{$user_model->real_name} <{$user_model->email}>";
			$headers['Subject'] = 'Website: "' . $workflow_group->workflow_title . '" Workflow Group has content waiting for your approval';
			// $headers['To'] = '';
			$msg = '';
			$msg .= "The workflow for the \"{$asset_model->cms_headline}\" {$asset_name} record on the {$page_model->title} page is awaiting your approval.\n\n";
			if ($model->comments) {
				$msg .= "COMMENTS:\n{$model->comments}\n\n";
			}
			$msg .= "You can view the current live page at:\n$live_url\n\n";
			$msg .= "You can preview the page at:\n$preview_url\n\n";
			$msg .= "To Approve/Decline the changes, please go to Your Dashboard:\n" . $dashboard_url;
			// gather the users
			$user_array = array();
			$recipients = array();
			foreach ($users as $user) {
				if ($user->{$user->primaryKey()} == $user_model->{$user_model->primaryKey()}) continue;
				$email = "{$user->real_name} <{$user->email}>";
				$recipients[] = $email;
				// $headers['To'] .= ($headers['To']?', ':'') . $email;
				$user_array[] = $user->toArray();
			}
			if (!empty($recipients)) {
				$mail->send($recipients, $headers, $msg);
			}
			unset($mail);
			// update the workflow and set submitted to true
			$model->submitted = 1;
			include_once 'n_date.php';
			$model->cms_created = NDate::convertTimeToUTC($model->cms_created, '%Y-%m-%d %H:%M:%S');
			$model->cms_modified = NDate::convertTimeToUTC($model->cms_modified , '%Y-%m-%d %H:%M:%S');
			$model->update();
			if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
				// audit trail
				$audit_trail = &NController::factory('audit_trail');
				$audit_trail->insert(array('asset'=>$model->asset, 'asset_id'=>$model->asset_id, 'action_taken'=>AUDIT_ACTION_WORKFLOW_SUBMIT, 'workflow_id'=>$model->{$model->primaryKey()}, 'workflow_group_id'=>$model->workflow_group_id, 'page_id'=>$model->page_id, 'page_content_id'=>$model->page_content_id));
				unset($audit_trail);
			}
			// set up the view
			$this->set('asset_name', $asset_name);
			$this->set('asset', $asset_model->toArray());
			$this->set('workflow_group', $workflow_group->toArray());
			$this->set('page', $page_model->toArray());
			$this->set('users', $user_array);
			$html .= $this->render(array('action'=>'send', 'return'=>true));
		}
		$this->set('MAIN_CONTENT', $html);
		$this->render(array('layout'=>'default'));
	}

	function actionToString($action) {
		switch ($action) {
			case WORKFLOW_ACTION_ADDEXISTING:
				$action = 'added';
				break;
			case WORKFLOW_ACTION_ADDNEW:
				$action = 'created and added';
				break;
			case WORKFLOW_ACTION_REMOVE:
				$action = 'removed';
				break;
			case WORKFLOW_ACTION_EDIT:
				$action = 'edited';
				break;
			case WORKFLOW_ACTION_DELETE:
				$action = 'deleted';
				break;
		}
		return $action;
	}

	function getNotifyUsers($workflow_id, $user_rights) {
		$model = &NModel::factory($this->name);
		$pk = $model->primaryKey();
		if ($model->get($workflow_id)) {
			$page_model = &$model->getLink('page_id', 'page');
			$model_count = clone($model);
			$model_count->reset();
			$parent_workflow = $model->parent_workflow?$model->parent_workflow:$workflow_id;
			$workflow_steps = array();
			if ($model_count->find(array('conditions'=>$pk . '=' . $parent_workflow . ' OR parent_workflow=' . $parent_workflow))) {
				$workflow_steps = &$model_count->fetchAll();
			}
			// we need to figure out who to email to...
			$notify_users = array();
			if ($page_model = &$model->getLink('page_id', 'page')) {
				$user_model = &NModel::factory('cms_auth');
				$pk = $user_model->primaryKey();
				//
				$users = array();
				$workflow_user = &NModel::factory('workflow_users');
				$workflow_user->workflow_group_id = $model->workflow_group_id;
				$auth = new NAuth();
				if ($workflow_user->find()) {
					$author = false;
					$editor = false;
					$approver = false;
					while ($workflow_user->fetch()) {
						if ($user_model->get($workflow_user->user_id)) {
							switch ($workflow_user->role) {
								case WORKFLOW_ROLE_AUTHOR:
									$author = true;
									break;
								case WORKFLOW_ROLE_EDITOR:
									$editor = true;
									break;
								case WORKFLOW_ROLE_APPROVER:
									$approver = true;
									break;
							}
							$users[] = clone($user_model);
						}
						$user_model->reset();
					}
					foreach ($users as $user) {
						$notify_user_rights = $this->getWorkflowUserRights($page_model, $user->{$user->primaryKey()});
						switch (1) {
							case count($workflow_steps) == 1:
								$rights_needed = WORKFLOW_RIGHT_EDIT + WORKFLOW_RIGHT_APPROVE;
								if ($notify_user_rights >= $rights_needed) {
									if ($author && $editor && $approver && !($notify_user_rights & WORKFLOW_RIGHT_PUBLISH)) {
										$notify_users[] = $user;
									} else if ((!$author || !$editor || !$approver) && $notify_user_rights & WORKFLOW_RIGHT_APPROVE) {
										$notify_users[] = $user;
									}
								}
								break;
							case count($workflow_steps) == 2 && $workflow_steps[1]->approved:
								$rights_needed = WORKFLOW_RIGHT_APPROVE + WORKFLOW_RIGHT_PUBLISH;
								if ($notify_user_rights >= $rights_needed) {
									$notify_users[] = $user;
								}
								break;
						}
					}
					unset($users);
				}
				if (empty($notify_users) && $user_model->get($auth->currentUserId()) && $user_model->user_level >= N_USER_ADMIN) {
					$notify_users[] = clone($user_model);
				}
				unset($user_model);
				unset($page_content_model);
				unset($page_model);
				return $notify_users;
			}
		}
		return false;
	}

	function &getWorkflowGroup(&$page_model) {
		if (!$page_model) return false;
		$workflow_group_id = 0;
		if ($page_model->workflow_group_id) {
			// look no further, this is all you need
			$workflow_group_id = $page_model->workflow_group_id;
		} else {
			// find the first ancestor with a workflow_group_id that is also recursive
			$ancestors = $page_model->getAncestors($page_model->{$page_model->primaryKey()}, false, false);
			foreach ($ancestors as $ancestor) {
				if ($ancestor['workflow_group_id'] && $ancestor['workflow_recursive']) {
					$workflow_group_id = $ancestor['workflow_group_id'];
					break;
				}
			}
		}
		if ($workflow_group_id) {
			$model = &NModel::factory('workflow_group');
			if ($model && $model->get($workflow_group_id)) {
				return $model;
			}
		}
		$model = false;
		return $model;
	}

	function getWorkflowUserRights(&$page_model, $user_id=null) {
		$model = &NModel::factory('workflow_users');
		if (!$model) {
			return false;
		}
		$auth = new NAuth;
		if (!$user_id) {
			$user_id = $auth->currentUserID();
		}
		$user_model = &$this->loadModel('cms_auth');
		$user_model->get($user_id);
		$rights = 0;
		$workflow_group = &$this->getWorkflowGroup($page_model);
		if ($workflow_group) {
			$workflow_group_id = $workflow_group->{$workflow_group->primaryKey()};
			$model->workflow_group_id = $workflow_group_id;
			$model->user_id = $user_id;
			if ($model->find(null, true)) {
				$workflow_user_model = clone($model);
				$current_role = $workflow_user_model->role;
				$rights = 0;
				$model->reset();
				$model->workflow_group_id = $workflow_group_id;
				if ($model->find()) {
					$author = false;
					$editor = false;
					$approver = false;
					while ($model->fetch()) {
						switch ($model->role) {
							case WORKFLOW_ROLE_AUTHOR:
								$author = true;
								break;
							case WORKFLOW_ROLE_EDITOR:
								$editor = true;
								break;
							case WORKFLOW_ROLE_APPROVER:
								$approver = true;
								break;
						}
					}
					switch (1) {
						case $current_role == WORKFLOW_ROLE_AUTHOR:
							$rights = WORKFLOW_RIGHT_EDIT;
							if (!$editor) {
								$rights += WORKFLOW_RIGHT_APPROVE;
							}
							if (!$editor && !$approver) {
								$rights += WORKFLOW_RIGHT_PUBLISH;
							}
							break;
						case $current_role == WORKFLOW_ROLE_EDITOR:
							$rights = WORKFLOW_RIGHT_EDIT + WORKFLOW_RIGHT_APPROVE;
							if (!$approver) {
								$rights += WORKFLOW_RIGHT_PUBLISH;
							}
							break;
						case $current_role == WORKFLOW_ROLE_APPROVER:
							$rights = WORKFLOW_RIGHT_APPROVE + WORKFLOW_RIGHT_PUBLISH;
							break;
					}
				}
			}
			if ($user_model->user_level >= N_USER_ADMIN && !($rights & WORKFLOW_RIGHT_EDIT)) {
				$rights += WORKFLOW_RIGHT_EDIT;
			}
		}
		unset($model);
		return $rights;
	}

	function getWorkflowUsers($workflow_group_id) {
		$model = &NModel::factory('workflow_users');
		if ($model) {
			$model->workflow_group_id = $workflow_group_id;
			if ($model->find()) {
				return $model->fetchAll();
			}
		}
		return false;
	}

	function getWorkflowUser($workflow_group_id) {
		$model = &$this->loadModel('workflow_users');
		if ($model) {
			$auth = new NAuth();
			$current_user = $auth->currentUserID();
			$model->workflow_group_id = $workflow_group_id;
			$model->user_id = $current_user;
			if ($model->find(null, true)) {
				return $model;
			}
		}
		return false;
	}

	function findContentWorkflowGroup(&$asset_controller) {
		if (!$asset_controller) return false;
		$asset_model = &$asset_controller->getDefaultModel();
		$pk = $asset_model->primaryKey();
		if (!$asset_model->$pk) return false;
		$page_content_model = &$asset_controller->loadModel('page_content');
		if ($page_content_model->find(array('conditions'=>'content_asset=\'' . $asset_controller->name . '\' AND content_asset_id=' . $asset_model->$pk))) {
			$page_model = &$this->loadModel('page');
			while ($page_content_model->fetch()) {
				// reset before every loop so the get call will work
				$page_model->reset();
				if ($page_model->get($page_content_model->page_id)) {
					if ($workflow_group_model = $this->getWorkflowGroup($page_model)) {
						unset($page_model);
						unset($page_content_model);
						return $workflow_group_model;
					}
				}
			}
			unset($page_model);
		}
		unset($page_content_model);
		return false;
	}

	function findContentWorkflow($workflow_group_id, &$asset_controller) {
		if (!$workflow_group_id || !$asset_controller) return false;
		$asset_model = &$asset_controller->getDefaultModel();
		$pk = $asset_model->primaryKey();
		if (!$asset_model->$pk) return false;
		$page_content_model = &$asset_controller->loadModel('page_content');
		$page_content_model->reset();
		if ($page_content_model->find(array('conditions'=>'content_asset=\'' . $asset_controller->name . '\' AND content_asset_id=' . $asset_model->$pk))) {
			while ($page_content_model->fetch()) {
				$page_model = &$page_content_model->getLink('page_id', 'page');
				if ($page_model) {
					if ($workflow = &$this->getWorkflow($page_content_model->{$page_content_model->primaryKey()}, $workflow_group_id, $asset_controller)) {
						unset($page_content_model);
						return $workflow;
					}
				}
				unset($page_model);
			}
		}
		unset($page_content_model);
		return false;
	}

	function saveWorkflow($values, $action, &$asset_controller) {
		if (!isset($values['page_content_id']) || !$values['page_content_id'] || !isset($values['workflow_group_id']) || !$values['workflow_group_id']) {
			 return false;
		}
		// instantiate workflow model
		$model = &$this->getDefaultModel();
		$table = $model->table();
		// load values
		$page_content_id = $values['page_content_id'];
		$page_content_model = &NModel::factory('page_content');
		$page_content_model->get($page_content_id);
		$page_id = (int) $page_content_model->page_id;
		unset($page_content_model);
		$workflow_group_id = $values['workflow_group_id'];
		// timed content
		$timed_start = isset($values['timed_start']) && $values['timed_start']?$values['timed_start']:null;
		$timed_end = isset($values['timed_end']) && $values['timed_end']?$values['timed_end']:null;
		if ($timed_start == 'null') $timed_start = null;
		if ($timed_end == 'null') $timed_end = null;
		if ($timed_start && is_array($timed_start)) {
			$def = $table['timed_start'];
			$timed_start = NDate::arrayToDate($timed_start);
			if (!($def & N_DAO_NOTNULL)) {
				if (!NDate::validDateTime($timed_start)) {
					$timed_start = false;
				}
			}
		}
		if ($timed_end && is_array($timed_end)) {
			$def = $table['timed_end'];
			$timed_end = NDate::arrayToDate($timed_end);
			if (!($def & N_DAO_NOTNULL)) {
				if (!NDate::validDateTime($timed_end)) {
					$timed_end = false;
				}
			}
		}
		// load asset
		if (!$asset_controller) return false;
		$asset_model = &$asset_controller->getDefaultModel();
		if (!$asset_model) return false;
		$pk = $asset_model->primaryKey();
		$fields = $asset_model->fields();
		$values = array();
		foreach ($fields as $field) {
			if ($field != $pk && !preg_match('|^cms_|', $field)) // don't save any of the meta content
			$values[$field] = $asset_model->$field;
		}
		$model->reset();
		$model->page_id = $page_id;
		$model->page_content_id = $page_content_id;
		$model->workflow_group_id = $workflow_group_id;
		$model->asset = $asset_controller->name;
		$model->asset_id = $asset_model->$pk;
		$model->submitted = 0;
		$model->completed = 0;
		if ($timed_start) $model->timed_start = $timed_start;
		if ($timed_end) $model->timed_end = $timed_end;
		$model->cms_modified_by_user = $asset_controller->_auth->currentUserID();
		if ($model->find(null, true)) {
			$model->cms_modified = $model->now();
			$model->draft = serialize($values);
			$ret = $model->update();
		} else {
			$model->action = (int) $action;
			$model->draft = serialize($values);
			$model->cms_created = $model->now();
			$model->cms_modified = $model->now();
			$ret = $model->insert();
		}
		if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
			$audit_trail = &NController::factory('audit_trail');
			$audit_trail->insert(array('asset'=>$asset_controller->name, 'asset_id'=>$asset_model->$pk, 'action_taken'=>AUDIT_ACTION_WORKFLOW_START, 'workflow_id'=>$model->{$model->primaryKey()}, 'workflow_group_id'=>$workflow_group_id, 'page_id'=>$page_id, 'page_content_id'=>$page_content_id));
			unset($audit_trail);
		}
		unset($auth);
		unset($model);
		return $ret;
	}

	function getWorkflow($page_content_id, $workflow_group_id, &$asset_controller, $completed=0) {
		if (!$asset_controller || !$asset_model = &$asset_controller->getDefaultModel()) return false;
		$pk = $asset_model->primaryKey();
		$model = &NModel::factory($this->name);
		$model->page_content_id = $page_content_id;
		$model->workflow_group_id = $workflow_group_id;
		$model->asset = $asset_controller->name;
		$model->asset_id = $asset_model->$pk;
		$model->completed = (int) $completed;
		if ($model->find(array('order_by'=>'id DESC'), true)) {
			return $model;
		}
		unset($model);
		return false;
	}

	function isWorkflow($page_content_id, $workflow_group_id, &$asset_controller) {
		if (!$asset_controller || !$asset_model = &$asset_controller->getDefaultModel()) return false;
		$pk = $asset_model->primaryKey();
		$model = &$this->loadModel($this->name);
		$model->page_content_id = $page_content_id;
		$model->workflow_group_id = $workflow_group_id;
		$model->asset = $asset_controller->name;
		$model->asset_id = $asset_model->$pk;
		$ret = (bool) $model->find();
		unset($model);
		return $ret;
	}

	function delete($parameter) {
		$model = &$this->getDefaultModel();
		if ($model->get($parameter)) {
			if ($page_content_model = &$model->getLink('page_content_id', 'page_content') && $page_content_model->cms_workflow == 1) {
				$page_content_model->delete();
			}
		}
		$model->reset();
		if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
			// audit trail
			$audit_trail = &NController::factory('audit_trail');
			$audit_trail->insert(array('asset'=>$model->asset, 'asset_id'=>$model->asset_id, 'action_taken'=>AUDIT_ACTION_WORKFLOW_DELETE, 'workflow_id'=>$model->{$model->primaryKey()}, 'workflow_group_id'=>$model->workflow_group_id, 'page_id'=>$model->page_id, 'page_content_id'=>$model->page_content_id));
			unset($audit_trail);
		}
		parent::delete($parameter);
	}
}
?>
