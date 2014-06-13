<?php
/**
 * require Authentication class, Form (crud) class, and nterchange controller
 */
require_once 'n_auth.php';
require_once 'n_controller.php';
require_once 'controller/form.php';
require_once 'app/models/action_track.php';

/**
 * AppController is extended by all Application Controllers
 *
 * AppController extends NController and is in turn extended by all
 * Application Controllers. Anything defined here is inherited into
 * the entire application
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Application Controller
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class AppController extends NController {
	/**
	 * Sets of authentication for the controller
	 *
	 * Can either be an array of actions that are password-protected or
	 * boolean for all or none.
	 *
	 * @var mixed
	 * @access public
	 */
	var $login_required = array();

	/**
	 * Sets level of user required to access login_required actions
	 *
	 * Can be one of N_USER_NORIGHTS, N_USER_EDITOR,
	 * N_USER_ADMIN or N_USER_ROOT, which are defined in n_auth.php
	 *
	 * @var mixed
	 * @access public
	 */
	var $user_level_required = N_USER_EDITOR;

	/**
	 * Versioning for controller
	 *
	 * Whether versions should be kept for records
	 *
	 * @todo This needs to get moved to the NModel class
	 * @var boolean
	 * @access public
	 */
	var $versioning = false;

	/**
	 * Constructor
	 *
	 * The constructor should be called all the way up the inheritance tree.
	 * Here, the CRUD methods are all set as $login_required and the default
	 * $base_view_dir is set if it hasn't been already.
	 *
	 * It is called implicitly on object instantiation.
	 *
	 * @access private
	 * @return null
	 */
	function __construct() {
		if (is_null($this->base_view_dir))
			$this->base_view_dir = ROOT_DIR;
		if (is_array($this->login_required)) {
			$this->login_required = array_merge(array('viewlist', 'show', 'create', 'insert', 'edit', 'update', 'delete'), $this->login_required);
		}
		parent::__construct();
	}

	/**
	 * Checks if the _auth->user_level is high enough
	 *
	 * This method checks if _auth is set and, if so, if the current
	 * user_level is high enough
	 *
	 * @access public
	 * @return boolean
	 */
	function checkUserLevel() {
		if (isset($this->_auth)) {
			if ($this->_auth->getAuthData('user_level') < $this->user_level_required) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Sets some app-specific variables and calls parent::render
	 *
	 * This method auto-loads the nterchange navigation if the options
	 * include a 'layout'
	 *
	 * @see NController::render, NView::render
	 * @access public
	 * @return null
	 */
	function render($options=array()) {
		if (is_array($options) && isset($options['layout'])) {
			$this->set('navigation', nterchangeController::navigation($this->name));
			if (isset($this->_auth) && is_object($this->_auth)) {
				$this->set('login_name', $this->_auth->getAuthData('real_name'));
			}
			$this->set('nterchange_version', NTERCHANGE_VERSION);
		}
		return parent::render($options);
	}

	/**
	 * Sets some app-specific variables and calls parent::renderLayout
	 *
	 * This method auto-loads the nterchange navigation
	 *
	 * @see NController::renderLayout, NView::renderLayout
	 * @access public
	 * @return null
	 */
	function renderLayout($layout, $main_content, $sidebar_content=null, $return=false) {
		$this->set('navigation', nterchangeController::navigation($this->name));
		if (isset($this->_auth) && is_object($this->_auth)) {
			$this->set('login_name', $this->_auth->getAuthData('real_name'));
		}
		$this->set('nterchange_version', NTERCHANGE_VERSION);
		return parent::renderLayout($layout, $main_content, $sidebar_content, $return);
	}

	// CRUD FUNCTIONALITY
	/**
	 * Shows a list of records associated with the controllers default model
	 *
	 * Instantiates the model, loops through all associated records and prints
	 * the headlines for each, including links to create/edit/delete
	 *
	 * @param $parameter null Placeholder for a default passed $parameter. Ignored.
	 * @param $layout Default true. Whether to render in a layout.
	 * @access public
	 * @return null
	 */
	function viewlist($parameter=null, $layout=true) {
		$this->auto_render = false;
		$this->base_dir = APP_DIR;
		$assigns = array();
		$assigns['TITLE'] = Inflector::humanize($this->name);
		$model = &$this->getDefaultModel($this->name);
		if ($model) {
			$model->find();
			$pk = $model->primaryKey();
			$models = array();
			$i = 0;
			while ($model->fetch()) {
				$arr = $model->toArray();
				$arr['_headline'] = isset($arr['cms_headline']) && $arr['cms_headline']?$arr['cms_headline']:$model->makeHeadline();
				$models[] = $arr;
				unset($arr);
			}
			$this->set(array('rows'=>$models, 'asset'=>$this->name, 'asset_name'=>$this->page_title?$this->page_title:Inflector::humanize($this->name)));
			unset($models);
		}
		$this->render(array('layout'=>'default'));
	}

	/**
	 * Prints a single record
	 *
	 * Fetches the record by the id of $parameter, loops through the fields
	 * and displays the appropriate values
	 *
	 * @param $parameter int the id of the record to show
	 * @param $layout Default true. Whether to render in a layout.
	 * @access public
	 * @return null
	 */
	function show($parameter, $layout=true) {
		$this->auto_render = false;
		$this->base_dir = APP_DIR;
		$assigns = array();
		$assigns['TITLE'] = Inflector::humanize($this->name);
		$model = &$this->getDefaultModel();
		$headline = '';
		if ($model && $model->get($parameter)) {
			$this->convertDateTimesToClient($model);
			if (SITE_DRAFTS) {
				$draft_model = &$this->loadModel('cms_drafts');
				$draft_model->asset = $this->name;
				$draft_model->asset_id = $parameter;
				if ($draft_model->find()) {
					// fill the local model with the draft info
					$draft_model->fetch();
					$current_user_id = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
					if ($current_user_id == $draft_model->cms_modified_by_user) {
						$content = unserialize($draft_model->draft);
						foreach ($content as $field=>$val) {
							$model->$field = $val;
						}
						$this->flash->set('notice', 'You have saved the record as a draft.');
					} else {
						$user_model = &$this->loadModel('cms_auth');
						$user_model->get($draft_model->cms_modified_by_user);
						$this->flash->set('notice', 'This record has been saved as a draft by &quot;' . $user_model->real_name . '&quot;.');
						unset($user_model);
					}
				}
			}
			$pk = $model->primaryKey();
			$row = $model->toArray();
			foreach ($row as $key=>$val) {
				if ($key == 'cms_headline')
					$headline = $val;
				if (preg_match('/^cms_/', $key))
					unset($row[$key]);
				if ($key == $pk)
					unset($row[$key]);
				if (is_array($model->bitmask_fields) && count($model->bitmask_fields)) {
					$bitmask_keys = array_keys($model->bitmask_fields);
					if (in_array($key, $bitmask_keys)) {
						$bitmask_total = $row[$key];
						$value_str = '';
						$i = 0;
						foreach($model->bitmask_fields[$key] as $bit=>$val) {
							if($bit & $bitmask_total) {
								if($i > 0) {
									$value_str .= ', ';
								}
								$value_str .= $val;
								$i ++;
							}
						}
						$row[$key] = $value_str;
					}
				}
				// Let's show any uploads as live links as well.
				if (isset($row[$key])) {
					if (preg_match('|^'.UPLOAD_DIR.'|', $row[$key])) {
						$row[$key] = '<a href="' . $row[$key] . '" target="_blank">' . $row[$key] . '</a>';
					}
				}
			}
			if (is_array($this->display_fields) && count($this->display_fields)) {
				foreach ($row as $field=>$val) {
					if (!in_array($field, $this->display_fields)) {
						unset($row[$field]);
					}
				}
			}
			$this->set(array('headline'=>$headline, 'row'=>$row, 'asset'=>$this->name, 'asset_id'=>$model->$pk, 'asset_name'=>$this->page_title?$this->page_title:Inflector::humanize($this->name)));
		} else {
			$this->flash->set('notice', 'The specified record could not be found.');
			$this->flash->now('notice');
		}
		$this->render(array('layout'=>'default'));
	}

	/**
	 * Displays a Create form for the controller's default model
	 *
	 * Instantiates the model, fetches the form and displays it.
	 * Also takes care of validation prior to passing the values to insert()
	 *
	 * @see AppController::insert();
	 * @param $parameter null Placeholder for a default passed $parameter. Ignored.
	 * @param $layout Default true. Whether to render in a layout.
	 * @access public
	 * @return null
	 */
	function create($parameter=null, $layout=true) {
		$this->auto_render = false;
		// load the model layer with info
		$model = &$this->getDefaultModel();
		if ($model) {
			// create the form
			$cform = new ControllerForm($this, $model);
			$form = &$cform->getForm();

			// assign the info and render
			$this->base_dir = APP_DIR;
			$assigns = array();
			$assigns['TITLE'] = Inflector::humanize($this->name);
			$fields = $model->fields();
			if ($form->validate() && $this->insert(true)) {
				$this->flash->set('notice', 'Your record has been saved.');
				$pk = $model->primaryKey();
				$this->redirectTo('show', $model->$pk);
			} else if ($model) {
				$this->set(array('form'=>$form->toHTML(), 'asset'=>$this->name, 'asset_name'=>$this->page_title?$this->page_title:Inflector::humanize($this->name)));
			}
		} else {
			$this->flash->set('notice', 'The specified table could not be found.');
			$this->flash->now('notice');
		}
		$this->render(array('layout'=>'default'));
	}

	/**
	 * Inserts the current model's values into a new record
	 *
	 * Grabs the default model, validates the values (if they haven't
	 * been yet), and calls the model's insert() method
	 *
	 * @see NModel::insert();
	 * @param $validated boolean Whether the values are prevalidated or not.
	 *                           Defaults to false.
	 * @access public
	 * @return int The new id of the record
	 */
	function insert($validated=false) {
		$model = &$this->getDefaultModel();
		if (!$model) {
			return false;
		}
		$pk = $model->primaryKey();
		$fields = $model->fields();
		// create the form
		$cform = new ControllerForm($this, $model);
		$form = &$cform->getForm();
		if (!$validated && !$form->validate()) {
			return false;
		}
		if (in_array('cms_created', $fields)) {
			$model->cms_created = $model->now();
		}
		if (in_array('cms_modified', $fields)) {
			$model->cms_modified = $model->now();
		}
		// set the user id if it's applicable and available
		if (in_array('cms_modified_by_user', $fields)) {
			$model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
		}
		return $form->process(array($cform, 'processForm'));
	}

	/**
	 * Displays an Edit form for the controller's default model
	 *
	 * Instantiates the model, fetches the form and displays it.
	 * Also takes care of validation prior to passing the values to update()
	 *
	 * @see AppController::insert();
	 * @param $parameter int The id of the record to be edited
	 * @param $layout Default true. Whether to render in a layout.
	 * @access public
	 * @return null
	 */
	function edit($parameter, $layout=true) {
		$this->auto_render = false;
		// Track the edit - this way we can keep track of the last edits of each person.
		$current_user_id = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
		/*$action_track = NModel::factory('action_track');
		$status = $action_track->checkAssetEditStatus($this->name, $parameter);
		if ($status == false) $track = $action_track->trackCurrentEdit($current_user_id, $this->name, $parameter);
		unset($action_track);*/
		// load the model layer with info
		$model = &$this->getDefaultModel();
		if ($model && $model->get($parameter)) {
			$this->convertDateTimesToClient($model);
			$pk = $model->primaryKey();
			if (SITE_DRAFTS) {
				$draft_model = &$this->loadModel('cms_drafts');
				$draft_model->asset = $this->name;
				$draft_model->asset_id = $parameter;
				if ($draft_model->find()) {
					// fill the local model with the draft info
					$draft_model->fetch();
					$current_user_id = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
					if ($current_user_id == $draft_model->cms_modified_by_user) {
						$content = unserialize($draft_model->draft);
						foreach ($content as $field=>$val) {
							$model->$field = $val;
						}
						$this->flash->set('notice', 'You are currently editing your draft of this record.');
						$this->flash->now('notice');
					} else {
						$user_model = &$this->loadModel('cms_auth');
						$user_model->get($draft_model->cms_modified_by_user);
						$this->flash->set('notice', 'This record has been saved as a draft by &quot;' . $user_model->real_name . '&quot;.');
						$this->flash->now('notice');
						unset($user_model);
					}
				}
			}
			// create the form
			$cform = new ControllerForm($this, $model);
			$form = &$cform->getForm();

			// is a page_content_id passed?
			$page_content_id = $this->getParam('page_content_id')?(int) $this->getParam('page_content_id'):false;
			if ($page_content_id) {
				$page_content = &NController::factory('page_content');
				$page_content_model = &$page_content->getDefaultModel();
				$page_content_model->get($page_content_id);
				$page_content->convertDateTimesToClient($page_content_model);
				$page_model = $page_content_model->getLink('page_id', 'page');
			}

			// check if this content belongs to a different workflow group or is currently in process
			$owned_content = false;
			if (SITE_WORKFLOW) {
				$workflow = &NController::factory('workflow');
				$workflow_group_model = false;
				$workflow_model = false;
				$user_rights = 0;
				if ($page_content_id) {
					$workflow_model = &$workflow->getDefaultModel();
					$workflow_model->page_content_id = $page_content_id;
					$workflow_model->asset = $this->name;
					$workflow_model->asset_id = $model->$pk;
					$workflow_model->completed = 0;
					if ($workflow_model->find(null, true)) {
						$owned_content = true;
					}
					$workflow_group_model = &$workflow->getWorkflowGroup($page_model);
					$user_rights = $workflow->getWorkflowUserRights($page_model);
				} else if ($workflow_group_model = &$workflow->findContentWorkflowGroup($this)) {
					if ($workflow_model = &$workflow->findContentWorkflow($workflow_group_model->{$workflow_group_model->primaryKey()}, $this)) {
						$page_model = &$this->loadModel('page');
						if ($page_model->get($workflow_model->page_id)) {
							$owned_content = true;
						}
						$user_rights = $workflow->getWorkflowUserRights($page_model);
					} else {
						$page_content = &NController::factory('page_content');
						$page_model = $page_content->getContentPage($this);
						if ($page_model) {
							$owned_content = true;
						}
						$user_rights = $workflow->getWorkflowUserRights($page_model);
					}
				}
				if (!$owned_content || ($owned_content && $user_rights & WORKFLOW_RIGHT_EDIT)) {
					if ($workflow_model && $workflow_model->{$workflow_model->primaryKey()}) {
						$form->removeElement('__submit_draft__');
						$form->insertElementBefore(NQuickForm::createElement('submit', '__submit_workflow__', 'Start Workflow'), '__submit__');
						$form->removeElement('__submit__');
						$workflow_draft = unserialize($workflow_model->draft);
						$form->setDefaults($workflow_draft);
					} else if ($user_rights & WORKFLOW_RIGHT_EDIT) {
						$form->insertElementBefore(NQuickForm::createElement('submit', '__submit_workflow__', 'Start Workflow'), '__submit__');
						$form->removeElement('__submit__');
					}
				} else if ($owned_content) {
					$this->flash->set('notice', 'The record you are attempting to edit belongs to the &quot;' . $workflow_group_model->workflow_title . '&quot; Workflow Group');
					$this->flash->now('notice');
					$this->set('MAIN_CONTENT', '<p>Please go to the dashboard to continue.</p>');
					$this->render(array('layout'=>'default'));
					exit;
				}
			}
			// if page_content_id or (it's workflow owned and this user has editing rights)
			if ($page_content_id || ($owned_content && $user_rights & WORKFLOW_RIGHT_EDIT)) {
				// add timed content
				if ($owned_content && $user_rights & WORKFLOW_RIGHT_EDIT && $workflow_model) {
					$form->setDefaults(array('timed_start'=>$workflow_model->timed_start, 'timed_end'=>$workflow_model->timed_end));
				} else if ($page_content_id) {
          $form->setDefaults(array(
            'timed_start'      => $page_content_model->timed_start,
            'timed_end'        => $page_content_model->timed_end,
            'col_xs'           => $page_content_model->col_xs,
            'col_sm'           => $page_content_model->col_sm,
            'col_md'           => $page_content_model->col_md,
            'col_lg'           => $page_content_model->col_lg,
            'row_xs'           => $page_content_model->row_xs,
            'row_sm'           => $page_content_model->row_sm,
            'row_md'           => $page_content_model->row_md,
            'row_lg'           => $page_content_model->row_lg,
            'offset_col_xs'    => $page_content_model->offset_col_xs,
            'offset_col_sm'    => $page_content_model->offset_col_sm,
            'offset_col_md'    => $page_content_model->offset_col_md,
            'offset_col_lg'    => $page_content_model->offset_col_lg,
            'offset_row_xs'    => $page_content_model->offset_row_xs,
            'offset_row_sm'    => $page_content_model->offset_row_sm,
            'offset_row_md'    => $page_content_model->offset_row_md,
            'offset_row_lg'    => $page_content_model->offset_row_lg,
            'pull_xs'          => $page_content_model->pull_xs,
            'pull_sm'          => $page_content_model->pull_sm,
            'pull_md'          => $page_content_model->pull_md,
            'pull_lg'          => $page_content_model->pull_lg,
            'gutter_xs'        => $page_content_model->gutter_xs,
            'gutter_sm'        => $page_content_model->gutter_sm,
            'gutter_md'        => $page_content_model->gutter_md,
            'gutter_lg'        => $page_content_model->gutter_lg
          ));
				}
				$page_content_model = &NModel::factory('page_content');
        $submit = $form->elementExists('__submit_workflow__')?'__submit_workflow__':'__submit__';

        // Timed Content
				$el = &ControllerForm::addElement('timed_start', $form, $page_content_model);
				if ($el) $form->insertElementBefore($form->removeElement('timed_start'), $submit);

				$el = &ControllerForm::addElement('timed_end', $form, $page_content_model);
				if ($el) $form->insertElementBefore($form->removeElement('timed_end'), $submit); 

        // TODO: Move this style/behaviour out of inline once we update the backend with bootstrap
        $style = "color: #fff; float: right;";
        $onclick = "if (typeof Prototype !== 'undefined') { $$('tr.grid').invoke('toggle');return false; }";
        $onload = "if (typeof Prototype !== 'undefined') { Event.observe(window, 'load', function(){ $$('tr.grid').invoke('toggle'); }); }";

		    $form->addElement('header', 'grid_id', "Grid <a href='#' style='{$style}' onclick=\"{$onclick}\">[Toggle]</a><script>{$onload}</script>");
				if ($el) $form->insertElementBefore($form->removeElement('grid_id'), $submit); 

        // Add col_xs, col_md, etc
        $grid = array(
          'col_xs', 'offset_col_xs', 'row_xs', 'offset_row_xs', 'pull_xs', 'gutter_xs', 
          'col_sm', 'offset_col_sm', 'row_sm', 'offset_row_sm', 'pull_sm', 'gutter_sm', 
          'col_md', 'offset_col_md', 'row_md', 'offset_row_md', 'pull_md', 'gutter_md', 
          'col_lg', 'offset_col_lg', 'row_lg', 'offset_row_lg', 'pull_lg', 'gutter_lg'
        );
        foreach($grid as $field) {
          $el = &ControllerForm::addElement($field, $form, $page_content_model);
          if ($el) {
            $el->setLabel(array($el->_label, "{$field} grid"));
            $form->insertElementBefore($form->removeElement($field), $submit);
          }
        }
			}
			// assign the info and render
			$this->base_dir = APP_DIR;
			$assigns = array();
			$fields = $model->fields();
			if ($form->validate() && $this->update(true)) {
				// If it validates and updates, then clear out the action track.
				/*$action_track = NModel::factory('action_track');
				$action_track->completeCurrentEdit($current_user_id, $this->name, $parameter);
				unset($action_track);*/
				$this->flash->set('notice', 'Your record has been saved.');
				$this->redirectTo('show', $parameter);
			} else if ($model) {
				$this->set($model->toArray());
				$this->set(array('form'=>$form->toHTML(), 'asset'=>$this->name, 'asset_name'=>$this->page_title?$this->page_title:Inflector::humanize($this->name)));
			}
		} else {
			$this->flash->set('notice', 'The specified record could not be found.');
			$this->flash->now('notice');
		}
    if ($this->getParam('layout')=='false') { $layout=false; }
		$this->render(($layout?array('layout'=>'default'):null));
	}

	/**
	 * Updates the current model's values
	 *
	 * Grabs the default model, validates the values (if they haven't
	 * been yet), and calls the model's update() method
	 *
	 * @see NModel::update();
	 * @param $validated boolean Whether the values are prevalidated or not.
	 *                           Defaults to false.
	 * @access public
	 * @return int The number of records affected (should be 1)
	 */
	function update($validated=false) {
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		$fields = $model->fields();
		if (!$model || !isset($this->params[$pk]) || $model->get($this->params[$pk])) {
			return false;
		}
		// create the form (this also does the validation)
		$cform = new ControllerForm($this, $model);
		$form = &$cform->getForm();
		if (!$validated && !$form->validate()) {
			return false;
		}
		if (in_array('cms_modified', $fields)) {
			$model->cms_modified= $model->now();
		}
		// set the user id if it's applicable and available
		if (in_array('cms_modified_by_user', $fields)) {
			$model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
		}
		return $form->process(array($cform, 'processForm'));
	}

	/**
	 * Delete the current model's record
	 *
	 * Grabs the default model, loads the records and calls the
	 * model's delete() method if an id is present (ie. a record is loaded)
	 *
	 * @see NModel::insert();
	 * @param $parameter int The id of the record to be deleted
	 * @access public
	 * @return null
	 */
	function delete($parameter) {
		if (empty($parameter)) {
			$this->redirectTo('viewlist');
		}
		// load the model layer with info
		$model = &$this->getDefaultModel();
		if (!$model) $this->redirectTo('viewlist');
		if ($model->get($parameter)) {
			if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
				// audit trail before delete so we don't lose the values
				$audit_trail = &NController::factory('audit_trail');
				$audit_trail->insert(array('asset'=>$this->name, 'asset_id'=>$model->{$model->primaryKey()}, 'action_taken'=>AUDIT_ACTION_DELETE));
				unset($audit_trail);
			}
			$model->delete();
			if (isset($this->params['_referer']) && $this->params['_referer']) {
				header('Location:' . urldecode($this->params['_referer']));
				exit;
			}
			$this->flash->set('notice', Inflector::humanize($this->name) . ' record deleted.');
			$this->postProcessForm($model->toArray());
		}
		$this->redirectTo('viewlist');
	}

	function convertDateTimesToClient(&$model) {
		$table = $model->table();
		foreach ($table as $field=>$def) {
			if ($def & N_DAO_TIME) {
				switch (true) {
					case $def & N_DAO_DATE && $def & N_DAO_TIME:
						$format = '%Y-%m-%d %H:%M:%S';
						break;
					default:
						$format = '%H:%M:%S';
				}
				$model->$field = NDate::convertTimeToClient($model->$field, $format);
			}
		}
	}

	/**
	 * Rule used by form to check for unique cms_headline field
	 *
	 * @access public
	 * @return boolean True if cms_headline is unique, false otherwise
	 */
	function uniqueHeadline($value) {
		$model = &$this->getDefaultModel();
		$id = $model->{$model->primaryKey()};
		$model = &NModel::factory($this->name);
		if ($model) {
			$conditions = $id?$model->primaryKey() . '!=' . $id:'';
			$model->cms_headline = $value;
			if ($model->find(array('conditions'=>$conditions))) {
				unset($model);
				return false;
			}
		}
		unset($model);
		return true;
	}

	// nterchange drafts
	/**
	 * Saves a draft of the record
	 *
	 * Either updates an existing draft or inserts a new one.
	 *
	 * @access public
	 * @return boolean true on success, false on failure
	 */
	function saveDraft() {
		if (!SITE_DRAFTS) return false;
		$model = &$this->getDefaultModel();
		if (!$model) return false;
		$pk = $model->primaryKey();
		$fields = $model->fields();
		$values = array();
		foreach ($fields as $field) {
			if ($field != $pk && !preg_match('|^cms_|', $field)) // don't save any of the meta content
				$values[$field] = $model->$field;
		}
		$version_id = 0;
		$version_model = &$this->loadModel('cms_nterchange_versions');
		if ($version_model) {
			$version_model->asset_id = $model->$pk;
			if ($version_model->find(array('first'=>true, 'order_by'=>'cms_created DESC'), true)) {
				$version_id = (int) $version_model->{$version_model->primaryKey()};
			}
			unset($version_model);
		}
		$draft_model = &NModel::factory('cms_drafts');
		if ($draft_model) {
			$draft_model->asset = $this->name;
			$draft_model->asset_id = $model->$pk;
			$draft_model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
			$update = $draft_model->find(null, true);
			$draft_model->version_id = $version_id;
			$draft_model->draft = serialize($values);
			$draft_model->cms_modified = $draft_model->now();
			if ($update) {
				$ret = $draft_model->update();
			} else {
				$draft_model->cms_created = $draft_model->now();
				$ret = $draft_model->insert();
			}
			unset($draft_model);
			if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
				// audit trail
				$audit_trail = &NController::factory('audit_trail');
				$audit_trail->insert(array('asset'=>$this->name, 'asset_id'=>$model->$pk, 'action_taken'=>AUDIT_ACTION_DRAFT_SAVE));
				unset($audit_trail);
			}
			return $ret;
		}
		return false;
	}

	/**
	 * Retrieves a copy of a draft for the loaded default model
	 *
	 * Checks for the currently logged in user and fetches the draft
	 * that matches with their id, and the id and type of asset
	 *
	 * Takes the draft, loads it into the default model and returns
	 * an array of the draft values
	 *
	 * @access public
	 * @return mixed Draft $values array on success, false on failure
	 */
	function loadDraft() {
		if (!SITE_DRAFTS) return false;
		$model = &$this->getDefaultModel();
		if (!$model) return false;
		$pk = $model->primaryKey();
		$fields = $model->fields();
		$draft_model = &$this->loadModel('cms_drafts');
		if ($draft_model) {
			$draft_model->asset = $this->name;
			$draft_model->asset_id = $model->$pk;
			$draft_model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
			if ($draft_model->find(null, true)) {
				$values = unserialize($draft_model->draft);
				foreach ($values as $field=>$val) {
					if (in_array($field, $fields))
						$model->$field = $val;
				}
				unset($draft_model);
				return $values;
			}
		}
		return false;
	}

	/**
	 * Checks if a draft exists
	 *
	 * Checks for the currently logged in user and checks the existence of a
	 * draft that matches with their id, and the id and type of asset
	 *
	 * @access public
	 * @return boolean true if a draft exists, false otherwise
	 */
	function isDraft() {
		if (!SITE_DRAFTS) return false;
		$model = &$this->getDefaultModel();
		if (!$model) return false;
		$pk = $model->primaryKey();
		$draft_model = &$this->loadModel('cms_drafts');
		if ($draft_model) {
			$draft_model->asset = $this->name;
			$draft_model->asset_id = $model->$pk;
			$draft_model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
			if ($draft_model->find(null, true)) {
				unset($draft_model);
				return true;
			}
			unset($draft_model);
		}
		return false;
	}

	/**
	 * Deletes a draft
	 *
	 * Checks for the currently logged in user and deletes a draft that
	 * matches with their id, and the id and type of asset.
	 *
	 * @access public
	 * @return boolean true if a draft exists, false otherwise
	 */
	function deleteDraft() {
		if (!SITE_DRAFTS) return false;
		$model = &$this->getDefaultModel();
		if (!$model) return false;
		$pk = $model->primaryKey();
		$draft_model = &NModel::factory('cms_drafts');
		if ($draft_model) {
			$draft_model->reset();
			$draft_model->asset = $this->name;
			$draft_model->asset_id = $model->$pk;
			$draft_model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
			if ($draft_model->find()) {
				while ($draft_model->fetch()) {
					$draft_model->delete();
				}
				unset($draft_model);
				if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
					// audit trail
					$audit_trail = &NController::factory('audit_trail');
					$audit_trail->insert(array('asset'=>$this->name, 'asset_id'=>$model->$pk, 'action_taken'=>AUDIT_ACTION_DRAFT_DELETE));
					unset($audit_trail);
				}
				return true;
			}
			unset($draft_model);
		}
		return false;
	}

	// nterchange versioning
	/**
	 * Retrieves versions for the current record
	 *
	 * Retrieves a single or multiple versions for an asset of a certain id.
	 * Can also retrieve a specific version id.
	 *
	 * Sample:
	 * $controller->getVersions($id); // array of all versions
	 * $controller->getVersions($id, true); // single version array
	 * $controller->getVersions($id, false|true, $version_id); // single old version if the version id matches the asset/id
	 *
	 * @param $id int The id of the record to get the versions for
	 * @param $latest_only boolean Get all versions or just the latest
	 * @param $version_id int Only get this version id, if it applies (used for
	 *                        reinstating old versions, etc)
	 * @access public
	 * @return mixed array on success, false on failure
	 */
	function getVersions($id, $latest_only = false, $version_id = 0) {
		// sanity check
		$id = (int) $id;
		if (!$id) return false;
		$sql = 'SELECT * FROM cms_nterchange_versions';
		$sql .= ' WHERE object_id=' . $id;
		$sql .= ' AND object=' . $this->db->quoteSmart($this->getObject());
		if ($version_id && settype($version_id, 'integer')) {
			$sql .= ' AND id=' . $version_id;
		}
		$sql .= ' ORDER BY cms_created DESC';
		if ($latest_only) {
			$sql = $this->db->modifyLimitQuery($sql, 0, 1);
		}
		$res = $this->db->query($sql);
		if (DB::isError($res)) {
			$ret = false;
		} else {
			if ($latest_only) {
				$row = $this->db->getRow($sql, null, DB_FETCHMODE_ASSOC);
				$ret = unserialize($row['version']);
				$ret['cms_version_id'] = $row['id'];
			} else {
				$ret = array();
				while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
					$version = unserialize($row['version']);
					$version['cms_version_id'] = $row['id'];
					$ret[] = $version;
				}
			}
		}
		return $ret;
	}

	/**
	 * Inserts a new versions for the current record
	 *
	 * Serializes the records contents and insert them into the
	 * cms_nterchange_versions table, if it exists
	 *
	 * @access public
	 * @see ControllerForm::processForm()
	 * @return int id of new version
	 */
	function insertVersion() {
		$model = &$this->getDefaultModel();
		if (!$model) return false;
		$pk = $model->primaryKey();
		if (!isset($model->$pk) || empty($model->$pk)) return false;
		$version_model = &$this->loadModel('cms_nterchange_versions');
		if (!$version_model) {
			// raise error: no versioning model available
			return false;
		}
		if (!$this->_versionDiff($model)) {
			$this->debug('Version not changed for ' . $model->tableName() . ': ' . $model->$pk, 'VERSION');
			return false;
		}
		$this->debug($model->tableName() . ' ' . $model->$pk, 'VERSION insert');
		// load the current info from the db (this happens before the update)
		$old_model = &NModel::factory($this->name);
		$old_model->get($model->$pk);
		// convert to client time to auto-convert back to server on update
		$this->convertDateTimesToClient($old_model);
		// insert the current info into the version
		$version_model->asset = $this->name;
		$version_model->asset_id = $old_model->$pk;
		$version_model->version = serialize($old_model->toArray());
		$version_model->cms_created = $old_model->cms_created;
		$version_model->cms_modified = $old_model->cms_modified;
		$version_model->cms_modified_by_user = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
		$ret = $version_model->insert();
		unset($version_model);
		unset($old_model);
		return $ret;
	}

	/**
	 * Checks the current live object record against the old database record
	 *
	 * @access private
	 * @see AppController::insertVersion()
	 * @return boolean true if the new version has changed, false otherwise
	 */
	function _versionDiff(&$model) {
		$old_version = &NModel::factory($this->name);
		$old_version->get($model->{$model->primaryKey()});
		$fields = $old_version->table();
		$diff = false;
		foreach ($fields as $field=>$def) {
			if ($field != 'cms_created' && $field != 'cms_modified' && $field != 'cms_modified_by_user') { // ignore metadata
				if ($old_version->$field != $model->$field) {
					$diff = true;
					break;
				}
			}
		}
		$diff = ($diff == false && $old_version->cms_headline==$model->cms_headline)?false:true;
		unset($old_version);
		return $diff;
	}

	/**
	 * Class destructor
	 *
	 * @access private
	 * @return null
	 */
	function __destruct() {
		unset($this->_auth);
		foreach ($this->models as $model) {
			unset($model);
		}
	}
}
?>
