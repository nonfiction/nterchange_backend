<?php
require_once 'app/controllers/audit_trail_controller.php';
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
 * @category   Page Content
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class PageContentController extends nterchangeController {
  function __construct() {
    $this->name = 'page_content';
    parent::__construct();
    $this->login_required = true;
    $this->public_actions = array('select_content_type', 'add_new_content', 'add_existing_content', 'remove_content', 'reorder');
    // set user level allowed to access the actions with required login
    $this->user_level_required = N_USER_NORIGHTS;
    $this->base_view_dir = ROOT_DIR;
  }

  function selectContentType($parameter) {
    $page_model = &$this->loadModel('page');
    $page_model->get($parameter);
    // instantiate form,
    include_once 'n_quickform.php';
    $form = new NQuickForm('select_content_type');
    $values = $form->getSubmitValues();

    if(isset($values['template_container_id'])){
        $template_container_id = $values['template_container_id'];
    } else {
      if(isset($this->params['template_container_id'])){
        $template_container_id = $this->params['template_container_id'];
      } else {
        $template_container_id = false;
      }
    }
    $form->addElement('header', null, 'Do you wish to add new or existing content to the &quot;' . $page_model->title . '&quot; page?');
    // next action
    $options = array('addnewcontent'=>'New Content', 'addexistingcontent'=>'Existing Content');
    $form->addElement('select', '_action', 'Select', $options);
    // assets for this container
    $model = &$this->loadModel('cms_asset_template');
    $pk = $model->primaryKey();
    $model->page_template_container_id = $template_container_id;
    $options = array();
    if ($model->find(array('join'=>'INNER JOIN cms_asset_info ON cms_asset_info.asset=' . $model->tableName() . '.asset'))) {
      while ($model->fetch()) {
        $options[$model->asset] = $model->asset_name;
      }
    }
    $form->addElement('select', 'asset', 'Asset', $options);
    // hidden values
    $form->addElement('hidden', 'page_id', $parameter);
    $form->addElement('hidden', 'template_container_id', $template_container_id);
    if (isset($this->params['_referer'])) {
      $form->addElement('hidden', '_referer', urlencode($this->params['_referer']));
    }
    // finish up
    $form->addElement('submit', '__submit__', 'go');
    // rules
    $form->addRule('asset', '', 'required');
    $form->addRule('template_container_id', '', 'required');
    // validation
    if ($form->validate()) {
      $values = $form->exportValues();
      $params = array('asset'=>$this->params['asset'], 'template_container_id'=>$this->params['template_container_id']);
      if (isset($this->params['_referer'])) {
        $params['_referer'] = $this->params['_referer'];
      }
      $this->redirectTo($values['_action'], $parameter, $params);
    }
    unset($page_model);
    $this->auto_render = false;
    $this->page_title = 'Select Content Type';
    $this->set('form', $form->toHTML());
    $this->render(array('action'=>'form', 'layout'=>'plain'));
  }

  function addNewContent($parameter, $load_model_content=false) {
    $page_model = &$this->loadModel('page');
    $page_model->get($parameter);
    $template_container_id = isset($this->params['template_container_id'])?$this->params['template_container_id']:false;
    $asset = isset($this->params['asset'])?$this->params['asset']:false;
    $asset_controller = &NController::singleton($asset);
    $asset_controller->_auth = new NAuth();

    // load the model layer with info
    $asset_model = &$asset_controller->getDefaultModel();

    // create the form
    include_once 'controller/form.php';
    $cform = new ControllerForm($asset_controller, $asset_model);
    $form = &$cform->getForm();
    // check for workflow
    if (SITE_WORKFLOW) {
      // get the users rights and bit compare them below
      $workflow = &NController::factory('workflow');
      $user_rights = $workflow->getWorkflowUserRights($page_model);
      $workflow_group = &$workflow->getWorkflowGroup($page_model);
      if ($workflow_group && !($user_rights & WORKFLOW_RIGHT_EDIT)) {
        // they don't belong here - go to the dashboard
        header('Location:/' . APP_DIR . '/dashboard');
      } else if ($user_rights & WORKFLOW_RIGHT_EDIT) {
        $form->insertElementBefore(NQuickForm::createElement('submit', '__submit_workflow__', 'Start Workflow'), '__submit__');
        $form->removeElement('__submit__');
      }
      unset($workflow);
    }
    // timed content
    $form->addElement('header', null, 'Make it timed content?');
    $timed_options = array('format'=>'Y-m-d H:i', 'minYear'=>date('Y'), 'maxYear'=>date('Y')+4, 'addEmptyOption'=>true);
    $form->addElement('date', 'timed_start', 'Timed Start', $timed_options);
    $form->addElement('date', 'timed_end', 'Timed End', $timed_options);
    $form->addElement('submit', '__submit_timed__', 'Add Scheduled Content');
    // page_content values
    $form->addElement('hidden', 'template_container_id', $template_container_id);
    $form->addElement('hidden', 'asset', $asset);
    if (isset($this->params['_referer'])) {
      $form->addElement('hidden', '_referer', urlencode($this->params['_referer']));
    }
    // assign the info and render
    $asset_controller->base_dir = APP_DIR;
    $assigns = array();
    $table = $asset_model->table();
    $fields = $asset_model->fields();
    if ($form->validate()) {
      $values = $form->getSubmitValues();
      if (in_array('cms_created', $fields)) {
        $asset_model->cms_created = $asset_model->now();
      }
      if (in_array('cms_modified', $fields)) {
        $asset_model->cms_modified = $asset_model->now();
      }
      // set the user id if it's applicable and available
      if (in_array('cms_modified_by_user', $fields)) {
        $asset_model->cms_modified_by_user = isset($asset_controller->_auth)?$asset_controller->_auth->currentUserId():0;
      }
      $referer = isset($values['_referer'])?$values['_referer']:false;
      if ($referer) {
        // cheat and remove the referer from the form oject so it doesn't redirect
        if (isset($form->_submitValues['_referer'])) unset($form->_submitValues['_referer']);
      }
      $success = $form->process(array($cform, 'processForm'));
      $asset_id = $asset_model->{$asset_model->primaryKey()};
      if ($success) {
        $values = $form->exportValues();
        $model = &$this->loadModel($this->name);
        $workflow_active = false;
        $workflow_required = false;
        if (SITE_WORKFLOW) {
          $workflow = &NController::factory('workflow');
          if ($workflow_group_model = $workflow->getWorkflowGroup($page_model)) {
            $workflow_required = true;
          }
          if ($workflow_required && isset($values['__submit_workflow__'])) {
            $workflow_active = true;
          }
        }
        $model->page_id = $parameter;
        $model->page_template_container_id = $values['template_container_id'];
        $model->content_asset = $values['asset'];
        $model->content_asset_id = $asset_id;
        // prep the timed content values if they exist
        if (isset($values['timed_start'])) {
          $values['timed_start'] = NDate::arrayToDate($values['timed_start']);
          $values['timed_start'] = NDate::convertTimeToUTC($values['timed_start']);
        }
        if (isset($values['timed_end'])) {
          $values['timed_end'] = NDate::arrayToDate($values['timed_end']);
          $values['timed_end'] = NDate::convertTimeToUTC($values['timed_end']);
        }
        //
        if (!$workflow_active) {
          if (isset($values['timed_start'])) $model->timed_start = $values['timed_start'];
          if (isset($values['timed_end'])) $model->timed_end = $values['timed_end'];
        }
        if ($workflow_required) {
          $model->cms_workflow = 1;
        }
        $model->cms_created = $model->now();
        $model->cms_modified = $model->now();
        $model->cms_modified_by_user = $this->_auth->currentUserID();
        if ($workflow_active) {
          // set page_content cms_workflow to 1 so it can't show up
          $model->cms_workflow = 1;
        }
        $model->insert();
        // do the workflow stuff if appropriate
        if ($workflow_active) {
          $page_content_id = $model->{$model->primaryKey()};
          $workflow_values = array();
          $workflow_values['page_content_id'] = $page_content_id;
          $workflow_values['workflow_group_id'] = $workflow_group_model->{$workflow_group_model->primaryKey()};
          // add timed content
          if (isset($values['timed_start'])) $workflow_values['timed_start'] = $values['timed_start'];
          if (isset($values['timed_end'])) $workflow_values['timed_end'] = $values['timed_end'];
          $workflow->saveWorkflow($workflow_values, WORKFLOW_ACTION_ADDNEW, $asset_controller);
          // set page_content cms_workflow to 1 so it can't show up
        }
        // delete the page cache
        $page = &NController::singleton('page');
        $page->deletePageCache($model->page_id);
        if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
          // audit trail
          $audit_trail = &NController::factory('audit_trail');
          $audit_trail->insert(array('asset'=>$asset_controller->name, 'asset_id'=>$asset_model->{$asset_model->primaryKey()}, 'action_taken'=>AUDIT_ACTION_CONTENT_ADDNEW, 'page_content_id'=>$model->{$model->primaryKey()}, 'page_id'=>$model->page_id));
          unset($audit_trail);
        }
        unset($page);
        unset($model);
      }
      if (isset($this->params['_referer']) && $this->params['_referer']) {
        $referer = urldecode($this->params['_referer']);
      } else {
        include_once 'view/helpers/url_helper.php';
        $referer = urlHelper::urlFor($this, array('controller'=>'page', 'action'=>'surftoedit', 'id'=>$parameter));
      }
      header('Location:' . $referer);
      exit;
    } else {
      if ($asset_model) {
        $this->set(array('form'=>$form->toHTML(), 'asset'=>$asset_controller->name, 'asset_name'=>Inflector::humanize($asset_controller->name)));
      }
    }
    $this->auto_render = false;
    $this->page_title = 'Add New Content to &quot;' . $page_model->title . '&quot;';
    $this->render(array('action'=>'form', 'layout'=>'default'));
  }

  function addExistingContent($parameter) {
    $page_model = &$this->loadModel('page');
    $page_model->get($parameter);
    $template_container_id = isset($this->params['template_container_id'])?$this->params['template_container_id']:false;
    $asset = isset($this->params['asset'])?$this->params['asset']:false;
    // instantiate form
    include_once 'n_quickform.php';
    $form = new NQuickForm();
    $values = $form->getSubmitValues();
    $form->addElement('header', null, 'Add &quot;' . Inflector::humanize($asset) . '&quot; content to the &quot;' . $page_model->title . '&quot; page');
    $asset_controller = &NController::factory($asset);
    $asset_model = &NModel::factory($asset);
    $pk = $asset_model->primaryKey();
    $records = array();
    if ($asset_model->find()) {
      while ($asset_model->fetch()) {
        $records[$asset_model->$pk] = $asset_model->cms_headline;
      }
    }
    unset($asset_model);
    // add asset select
    $options = defined('SITE_WORKFLOW') && SITE_WORKFLOW?array():array('size'=>10, 'multiple'=>'multiple');
    $form->addElement('select', 'asset_id', Inflector::humanize($asset), $records, $options);
    // hidden fields
    $form->addElement('hidden', 'asset', $asset);
    $form->addElement('hidden', 'template_container_id', $template_container_id);
    if (isset($this->params['_referer'])) {
      $form->addElement('hidden', '_referer', urlencode($this->params['_referer']));
    }
    // finish up
    $form->addElement('submit', '__submit__', 'Add Content');
    // rules
    defined('SITE_WORKFLOW') && SITE_WORKFLOW?$form->addRule('asset_id', 'You must select a record.', 'required'):$form->addGroupRule('asset_id', 'You must select a record.', 'required');
    $form->addRule('asset', '', 'required');
    $form->addRule('template_container_id', '', 'required');
    // check for workflow
    $user_rights = 0;
    if (SITE_WORKFLOW) {
      // get the users rights and bit compare them below
      $workflow = &NController::factory('workflow');
      $user_rights = $workflow->getWorkflowUserRights($page_model);
      if ($workflow_group_model = &$workflow->getWorkflowGroup($page_model)) {
        if (!($user_rights & WORKFLOW_RIGHT_EDIT)) {
          // they don't belong here - go to the dashboard
          header('Location:/' . APP_DIR . '/dashboard');
        } else if ($user_rights & WORKFLOW_RIGHT_EDIT) {
          $form->insertElementBefore(NQuickForm::createElement('submit', '__submit_workflow__', 'Start Workflow'), '__submit__');
          $form->removeElement('__submit__');
        }
      }
      unset($workflow);
    }
    $form->addElement('header', null, 'Make it timed content?');
    $timed_options = array('format'=>'Y-m-d H:i', 'minYear'=>date('Y'), 'maxYear'=>date('Y')+4, 'addEmptyOption'=>true);
    $form->addElement('date', 'timed_start', 'Timed Start', $timed_options);
    $form->addElement('date', 'timed_end', 'Timed End', $timed_options);
    if (!$user_rights) {
      $form->addElement('submit', '__submit_timed__', 'Add Scheduled Content');
    } else {
      $form->addElement('submit', '__submit_workflow__', 'Start Workflow with Scheduled Content');
    }
    if ($form->validate()) {
      $values = $form->exportValues();
      $model = &$this->loadModel($this->name);
      $workflow_active = false;
      if (SITE_WORKFLOW) {
        $workflow = &NController::factory('workflow');
        // check if this content is on any other page.
        // if it is, if either pages are part of a workflow group, we need to copy the content (go to addnewcontent with notice)
        // if neither do, then go ahead
        $asset_model = &$asset_controller->loadModel($asset_controller->name);
        $asset_model->get($values['asset_id']);
        $other_page = &$this->getContentPage($asset_controller);
        if ($other_page) {
          $owned_content = false;
          if ($workflow_group_model = &$workflow->getWorkflowGroup($page_model)) {
            $owned_content = true;
          } else {
            if ($workflow_group_model = &$workflow->getWorkflowGroup($other_page)) {
              $owned_content = true;
            }
          }
          // if the content is already connected somewhere and one of the pages belongs to a workflow_group, then addNewContent with preloaded content
          if ($owned_content) {
            if (isset($values['__submit__'])) unset($values['__submit__']);
            if (isset($values['__submit_workflow__'])) unset($values['__submit_workflow__']);
            $this->redirectTo('copy_existing_content', $parameter, $values);
            exit;
          }
        }
        if (isset($values['__submit_workflow__']) && $values['__submit_workflow__']) {
          $workflow = &NController::factory('workflow');
          if ($workflow_group_model = $workflow->getWorkflowGroup($page_model)) {
            $workflow_active = true;
          }
        }
      }
      $model->page_id = $parameter;
      if (SITE_WORKFLOW && isset($values['__submit_workflow__']) && $values['__submit_workflow__']) {
        $model->cms_workflow = 1;
      }
      $model->page_template_container_id = $values['template_container_id'];
      $model->content_asset = $values['asset'];
      // set the timed values
      $timed_start = null;
      $timed_end = null;
      include_once 'n_date.php';
      if (isset($values['timed_start'])) {
        $timed_start = NDate::arrayToDate($values['timed_start']);
        $timed_start = NDate::convertTimeToUTC($timed_start);
        unset($values['timed_start']);
      }
      if (isset($values['timed_end'])) {
        $timed_end = NDate::arrayToDate($values['timed_end']);
        $timed_end = NDate::convertTimeToUTC($timed_end);
        unset($values['timed_end']);
      }
      if (!$workflow_active) {
        $table = $model->table();
        $def = $table['timed_start'];
        if (NDate::validDateTime($timed_start, $def)) {
          $model->timed_start = $timed_start;
        } else {
          $model->timed_start = N_DAO_NOTNULL & $def?$timed_start:'null';
        }
        $def = $table['timed_end'];
        if (NDate::validDateTime($timed_end, $def)) {
          $model->timed_end = $timed_end;
        } else {
          $model->timed_end = N_DAO_NOTNULL & $def?$timed_end:'null';
        }
      }
      $model->cms_created = $model->now();
      $model->cms_modified = $model->now();
      $model->cms_modified_by_user = $this->_auth->currentUserID();
      if (!is_array($values['asset_id'])) {
        $values['asset_id'] = array($values['asset_id']);
      }
      foreach ($values['asset_id'] as $asset_id) {
        $model->content_asset_id = $asset_id;
        $model->insert();
        if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
          // audit trail
          $audit_trail = &NController::factory('audit_trail');
          $audit_trail->insert(array('asset'=>$asset_controller->name, 'asset_id'=>$asset_id, 'action_taken'=>AUDIT_ACTION_CONTENT_ADDEXISTING, 'page_content_id'=>$model->{$model->primaryKey()}, 'page_id'=>$model->page_id));
          unset($audit_trail);
        }
      }
      if ($workflow_active) {
        $asset_controller = &NController::factory($values['asset']);
        $asset_controller->_auth = new NAuth();
        $asset_model = &$asset_controller->getDefaultModel();
        $asset_model->get($values['asset_id'][0]);
        $workflow_values = array();
        $workflow_values['page_content_id'] = $model->{$model->primaryKey()};
        $workflow_values['workflow_group_id'] = $workflow_group_model->{$workflow_group_model->primaryKey()};
        // add timed content
        $workflow_values['timed_start'] = $timed_start;
        $workflow_values['timed_end'] = $timed_end;
        $workflow->saveWorkflow($workflow_values, WORKFLOW_ACTION_ADDEXISTING, $asset_controller);
      }
      // delete the page cache
      $page = &NController::singleton('page');
      $page->deletePageCache($model->page_id);
      unset($page);
      // set up the referer
      if (isset($this->params['_referer']) && $this->params['_referer']) {
        $referer = urldecode($this->params['_referer']);
      } else {
        include_once 'view/helpers/url_helper.php';
        $referer = urlHelper::urlFor($this, array('controller'=>'page', 'action'=>'surftoedit', 'id'=>$parameter));
      }
      header('Location:' . $referer);
      exit;
    }
    $this->auto_render = false;
    $this->page_title = 'Add Existing Content to &quot;' . $page_model->title . '&quot;';
    $this->set(array('title'=>'Select Content', 'form'=>$form->toHTML()));
    $this->render(array('action'=>'form', 'layout'=>'plain'));
    unset($page_model);
  }

  function copyExistingContent($parameter) {
    $params = $this->params;
    if (!isset($params['asset']) || !isset($params['asset_id']) || !isset($params['template_container_id'])) {
      header('Location:/' . APP_DIR . '/');
    }
    $asset_controller = &NController::singleton($params['asset']);
    $asset_model = &$asset_controller->getDefaultModel();
    $asset_model->get($params['asset_id']);
    $asset_model->{$asset_model->primaryKey()} = null;
    $asset_model->cms_headline = null;
    $this->flash->set('notice', 'The content you chose already belongs to another Workflow Group. <br />We have made a copy of the content for you.<br />Please enter a new headline to connect the content to your page.');
    $this->flash->now('notice');
    $this->addNewContent($parameter, true);
  }

  function removeContent($parameter, $redirect=true, $timed_remove=false) {
    $model = &$this->getDefaultModel();
    $referer = (isset($this->params['_referer']) && $this->params['_referer'])?$this->params['_referer']:false;
    if ($model->get($parameter)) {
      // check for workflow
      // if it's a timed remove, the timed portion went through workflow, so it's okay
      if (SITE_WORKFLOW && $timed_remove == false) {
        // get the users rights and bit compare them below
        $workflow = &NController::factory('workflow');
        $page_model = &$model->getLink('page_id', 'page');
        $user_rights = $workflow->getWorkflowUserRights($page_model);
        if ($workflow_group_model = &$workflow->getWorkflowGroup($page_model)) {
          if (!($user_rights & WORKFLOW_RIGHT_EDIT)) {
            // they don't belong here - go to the dashboard
            header('Location:/' . APP_DIR . '/dashboard');
            exit;
          }
          $asset_controller = &NController::factory($model->content_asset);
          $asset_controller->_auth = new NAuth();
          $asset_model = &$asset_controller->getDefaultModel();
          $asset_model->get($model->content_asset_id);
          // workflow values for saveWorkflow
          $workflow_values = array();
          $workflow_values['page_content_id'] = $model->{$model->primaryKey()};
          $workflow_values['workflow_group_id'] = $workflow_group_model->{$workflow_group_model->primaryKey()};
          // save the workflow
          $workflow->saveWorkflow($workflow_values, WORKFLOW_ACTION_REMOVE, $asset_controller);
          if ($redirect) {
            include_once 'view/helpers/url_helper.php';
            $referer = isset($this->params['referer'])?urldecode($this->params['referer']):urlHelper::urlFor($this, array('controller'=>'page', 'action'=>'surftoedit', 'id'=>$page_model->{$page_model->primaryKey()}));
            header('Location:' . $referer);
            exit;
          }
        }
        unset($workflow);
      }
      include_once 'view/helpers/url_helper.php';
      $page_id = $model->page_id;
      if (!$referer) {
        $referer = urlHelper::urlFor($this, array('controller'=>'page', 'action'=>'surftoedit', 'id'=>$page_id));
      }
      // delete the page cache
      $page = &NController::singleton('page');
      $page->deletePageCache($model->page_id);
      unset($page);
      $audit_trail_array = array('asset'=>$model->content_asset, 'asset_id'=>$model->content_asset_id, 'action_taken'=>AUDIT_ACTION_CONTENT_REMOVE, 'page_content_id'=>$model->{$model->primaryKey()}, 'page_id'=>$model->page_id);
      if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
        // audit trail just before the delete or we lose the info
        if ($timed_remove == false) {
          $audit_trail = &NController::factory('audit_trail');
          $audit_trail->insert($audit_trail_array);
        // Bit of an ugly hack, but I didn't want to mess with the controller.
        // The model doesn't require authentication, so we can force it through when
        // we're removing timed_content auto-magically.
        } elseif ($timed_remove == true) {
          $audit_trail = &NModel::factory('cms_audit_trail');
          $audit_trail->insert_audit_trail($audit_trail_array);
        }
        unset($audit_trail);
      }
      unset($audit_trail_array);
      // delete the page_content record
      $deleted = $model->delete();
      // if delete was successful and there is an unsubmitted workflow, then cascade delete the workflow
      if ($timed_remove == false && $deleted && SITE_WORKFLOW && $workflow_model = &$this->loadModel('workflow')) {
        $workflow_model->page_id = $page_id;
        $workflow_model->asset = $model->content_asset;
        $workflow_model->asset_id = $model->content_asset_id;
        $workflow_model->submitted = 0;
        $workflow_model->parent_workflow = 0;
        $workflow_model->cms_modified_by_user = $this->_auth->currentUserID();
        if ($workflow_model->find()) {
          while ($workflow_model->fetch()) {
            $workflow_model->delete();
          }
        }
        unset($workflow_model);
      }
      unset($model);
    }
    if ($redirect) {
      header('Location:' . $referer);
      exit;
    }
  }


  function show($parameter, $layout=true) {
    $this->auto_render = false;
    $this->base_dir = APP_DIR;
    $model = &$this->getDefaultModel();
    if ($model && $model->get($parameter)) {
      $this->convertDateTimesToClient($model);
      $page_controller = &NController::singleton('page');

      if ($this->checkUserLevel()) {
        $page_controller->edit = true;
        $page_controller->page_edit_allowed = true;
        $page_controller->content_edit_allowed = true;
      }

      $html = $page_controller->getContainerContent($model->page_id, $model->page_template_container_id, $model->id);
    } else {
      $html = 'The specified record could not be found.';
    }
    print trim($html);
  }


  function reorder() {
    $id = isset($this->params['id'])?(int) $this->params['id']:false;
    if (!$id) {
      return;
    }
    $before = isset($this->params['before'])?(int) $this->params['before']:false;
    if ($model = &$this->getDefaultModel() && $model->get($id)) {
      $pk = $model->primaryKey();
      $page_content_model = &NModel::factory($this->name);
      $page_content_model->page_id = $model->page_id;
      $page_contents = array();
      if ($page_content_model->find()) {
        $before_found = false;
        $item = null;
        while ($page_content_model->fetch()) {
          if ($page_content_model->$pk == $id) {
            // pull the "id" record out of the array
            $item = clone($page_content_model);
          } else {
            $page_contents[] = clone($page_content_model);
          }
        }
        if (!$before) { // if there is no before, then the item goes last
          $page_contents[] = $item;
        } else { // loop through until you find "before" and then splice the "id" in front of it
          foreach ($page_contents as $key=>$page_content) {
            if ($page_content->$pk == $before) {
              array_splice($page_contents, $key, 1, array($item, $page_content));
              break;
            }
          }
        }
        $i = 0;
        foreach ($page_contents as $key=>$page_content) {
          $page_content->content_order = $i;
          $page_content->save(false,false); // second false prevents converting time to UTC (unnecessary for a reorder)
          $i++;
        }
      }
      $page = &NController::singleton('page');
      $page->deletePageCache($model->page_id);
      unset($page);
      unset($page_contents);
      unset($page_content_model);
      unset($model);
    }
  }

  function &getContentPage(&$asset_controller) {
    if (!$asset_controller) return false;
    $asset_model = &$asset_controller->loadModel($asset_controller->name);
    if (!$asset_model) return false;
    $pk = $asset_model->primaryKey();
    $model = &NModel::factory($this->name);
    $model->content_asset = $asset_controller->name;
    $model->content_asset_id = $asset_model->$pk;
    if ($model->find(null, true)) {
      $page_model = &$model->getLink('page_id', 'page');
      unset($model);
      return $page_model;
    }
    unset($model);
    $model = false;
    return $model;
  }

  function changeTemplate($page_id, $to_template_id) {
    $page_id = (int)$page_id;
    $to_template_id = (int)$to_template_id;
    if (!$page_id || !$to_template_id) {
      // TODO: add a log error
      return false;
    }

    // load the container_vars
    $tc_model = &NModel::singleton('page_template_containers');
    $tc_pk = $tc_model->primaryKey();
    $tc_model->reset();
    $tc_model->page_template_id = $to_template_id;
    $containers = array();
    if ($tc_model->find()) {
      while ($tc_model->fetch()) {
        $containers[$tc_model->$tc_pk] = $tc_model->container_var;
      }
    }

    // grab records attached to the old template
    $model = &$this->getDefaultModel();
    $model->reset();
    $model->page_id = $page_id;
    $conditions = '';
    foreach ($containers as $container_id=>$container_var) {
      $conditions .= ($conditions?' AND ':'') . 'page_template_container_id != ' . $container_id;
    }
    $old_contents = $model->find(array('conditions'=>$conditions))?$model->fetchAll():array();
    if (empty($old_contents)) return true;

    // loop through the old content. If the new template has the same
    // container_var and isn't already connected to that template_container,
    // then update the record
    foreach ($old_contents as $content) {
      // grab the old template_container for the content
      $tc_model->reset();
      if ($tc_model->get($content->page_template_container_id)) {
        if ($new_container_id = array_search($tc_model->container_var, $containers)) {
          // check if the same content is already connected to the new template_container
          $model->reset();
          $model->page_id = $page_id;
          $model->content_asset = $content->content_asset;
          $model->content_asset_id = $content->content_asset_id;
          $model->page_template_container_id = $new_container_id;
          if ($model->find()) {
            continue;
          }
          // if not, update the content to the new container_id
          $content->page_template_container_id = $new_container_id;
          $content->update();
        }
      }
    }
    return true;
  }

  /*
  / Grab all of the content_asset_ids for a particular asset_name on a
  / particular page in a particular container.
  / Used to create RSS feeds from a blog page, also to show last
  / few records connected to a page on another.
  */
  function getAssetContainerPageItems ($page_template_container_id, $page_id, $asset_name) {
    $model = &NModel::factory('page_content');
    $model->page_id = $page_id;
    $model->content_asset = $asset_name;
    $model->page_template_container_id = $page_template_container_id;
    if ($model->find()) {
      $records = &$model->fetchAll(true);
      $records = $this->_removeTimedContent($records);
      unset($model);
      $model = &NModel::factory($asset_name);
      foreach ($records as $record) {
        $model->reset();
        if ($model->get($record['content_asset_id'])) {
          $assets[] = $model->toArray();
        }
      }
    }
    return $assets;
  }

  // Used with getAssetContainerPageItems to remove any timed content from the
  // $assets array.
  function _removeTimedContent ($records) {
    $cleaned_records = array();
    $time = date("Y-m-d G:i:s", time());
    $time = NDate::convertTimeToUTC($time);
    foreach ($records as $record) {
      // If they're both null - just add the record and skip the rest.
      if(is_null($record['timed_start']) && is_null($record['timed_end']) || ($record['timed_start'] == '0000-00-00 00:00:00')) {
        $cleaned_records[] = $record;
        continue;
      }
      if (!is_null($record['timed_start']) && ($record['timed_start'] > $time)) continue;
      if (!is_null($record['timed_end']) && ($record['timed_end'] < $time)) continue;
      $cleaned_records[] = $record;
    }
    return $cleaned_records;
  }

  // See if an asset is being used in a particular container.
  // Return an int.
  function checkAssetContainerUsage($asset, $container_id) {
    $count = 0;
    $model = &NModel::factory($this->name);
    $model->content_asset = $asset;
    $model->page_template_container_id = $container_id;
    if ($model->find()) {
      while ($model->fetch()) {
        $count++;
      }
    }
    unset ($model);
    return $count;
  }
}
?>
