<?php
/**
 * form.php is the main form class file
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Forms
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class ControllerForm {
	var $form = null;
	var $controller = null;
	var $model = null;

	// options
	var $model_name = '';
	var $date_format = 'Y-m-d';
	var $time_format = 'H:i:s';
	var $rule_required_message = '%s is a required field';

	function ControllerForm(&$controller, &$model, $options=null) {
		include_once 'n_quickform.php';
		$this->controller = &$controller;
		$this->model = &$model;
		$this->_loadOptions($options);
	}

	function &getForm($form_name='', $method='POST', $action='', $target='_self', $attributes=null) {
		if ($form_name == '') {
			$form_name = $this->controller->name . '_form';
		}
		if ($action == '') {
			$action = $_SERVER['REQUEST_URI'];
		}
		$this->controller->preGenerateForm();
		$this->form = new NQuickForm($form_name, $method, $action, $target, $attributes);
		$header_label = $this->controller->page_title?$this->controller->page_title:Inflector::humanize($this->controller->name);
		if ($this->model->form_header) {
			if (is_array($this->model->form_header)) {
				foreach ($this->model->form_header as $field) {
					if (!isset($this->model->$field))
					continue;
					$header_label .= ' - ' . $this->model->$field;
				}
			} else if (isset($this->model->{$this->model->form_header}) && !is_array($this->model->{$this->model->form_header})) {
				$header_label .= ' - ' . $this->model->{$this->model->form_header};
			}
		}
		$el = $this->form->addElement('header', '__header__', $header_label);
    $el->setLabel(array($el->_label, "{$header_label} header"));
		$table = $this->model->table();
		foreach ($table as $field=>$def) {
			$this->loadField($field, $this->form);
		}
		$defaults = $this->model->form_field_defaults;
		$defaults = $this->model->toArray();
		foreach ($this->model->form_field_defaults as $field=>$default) {
			if (!isset($defaults[$field])) {
				$defaults[$field] = $default;
			}
		}
		$this->form->setDefaults($defaults);
		if (!$this->model->getHeadline() && !PEAR::isError($this->form->getElement('cms_headline'))) {
			$this->form->addRule('cms_headline', sprintf($this->rule_required_message, 'Headline'), 'required');
		}
		$el = $this->form->addElement('submit', '__submit__', 'Save');
    $el->setLabel(array("", "submit"));
		if (SITE_DRAFTS && isset($table['cms_draft'])) {
			// $submit = &$this->form->removeElement('__submit__');
			$submit_draft = &$this->form->createElement('submit', '__submit_draft__', 'Save as Draft');
			$el = $this->form->addElement($submit_draft);
      $el->setLabel(array("", "submit"));
			// $this->form->addGroup(array($submit, $submit_draft), null, '', '&nbsp;', null, false);
		}
		$values = $this->form->getSubmitValues();
		if (isset($_GET['_referer']) || isset($values['_referer'])) {
			$referer = isset($_GET['_referer'])?$_GET['_referer']:$values['_referer'];
			$this->form->addElement('hidden', '_referer', urlencode($referer));
		}
		$this->setFormRules();
		$this->controller->postGenerateForm($this->form);

		return $this->form;
	}

	function &loadField($field, &$form) {
		return ControllerForm::addElement($field, $form, $this->model);
	}

	function &addElement($field, &$form, &$model) {
		if (!$model || !$form) {
			$ret = null;
			return $ret;
		}
		if (in_array($field, $model->form_ignore_fields)) {
			$ret = null;
			return $ret;
		}
		if (is_array($model->form_display_fields) && count($model->form_display_fields) > 0 && !in_array($field, $model->form_display_fields)) {
			$ret = null;
			return $ret;
		}
		if (is_array($model->bitmask_fields) && count($model->bitmask_fields)) {
			$bitmask_keys = array_keys($model->bitmask_fields);
			if(in_array($field, $bitmask_keys)) {
				$checkbox = array();
				foreach ($model->bitmask_fields[$field] as $bit=>$label) {
					$checkbox[] = &NQuickForm::createElement('checkbox', $bit, null, $label);
				}
				return $form->addElement('group', $field, ControllerForm::getFieldLabel($field, $model), $checkbox, '<br />');
			}
		}
		$table = $model->table();
		$def = isset($table[$field])?$table[$field]:false;
		if (!$def) {
			$ret = null;
			return $ret;
		}
		if (isset($model->form_elements[$field])) {
			$field_def = &$model->form_elements[$field];
			if (is_object($field_def) && is_a($field_def, 'HTML_QuickForm_Element')) {
				return $form->addElement($field_def);
			} else if (is_string($field_def)) {
				$field_def = array($field_def);
			}
			if (is_array($field_def) && count($field_def) && count($field_def) < 3) {
				// minimum length to have field type, name and label
				for ($i=1;$i<3;$i++) {
					if (!isset($field_def[$i])) {
						if ($i == 1) {
							// set the field name
							$field_def[$i] = $field;
						} else if ($field_def[0] != 'hidden' && $i == 2) {
							// set the field label
							$field_def[$i] = ControllerForm::getFieldLabel($field, $model);
						}
					}
				}
			}
			$el = &call_user_func_array(array($form, 'createElement'), $field_def);
			if (is_object($el) && is_a($el, 'HTML_QuickForm_Element')) {
				$el->updateAttributes(ControllerForm::getFieldAttributes($field));
				return $form->addElement($el);
			}
			$ret = null;
			return $ret;
		}
		if (preg_match('|^cms_|', $field) && $field != 'cms_headline') {
			$ret = null;
			return $ret;
		}

		if ($field == 'id') {
			return $form->addElement('hidden', 'id');
		}
		$element_label = ControllerForm::getFieldLabel($field, $model);
		if ($field == 'cms_headline') $element_label = 'Headline';
		$attributes = array();
		switch (true) {
			case $def & N_DAO_DATE && $def & N_DAO_TIME:
				// $elementName = null, $elementLabel = null, $options = array(), $attributes = null
				$options = array('language'=>'en', 'format'=>'Y-m-d H:i', 'minYear'=>2000, 'maxYear'=>date('Y')+5);
				$options = ControllerForm::getFieldOptions($field, $options, $model);
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('date', $field, $element_label, $options, $attributes);
        $el->setLabel(array($el->_label, "{$field} date"));
				break;
			case $def & N_DAO_DATE:
				$options = array('language'=>'en', 'format'=>'Y-m-d', 'minYear'=>2000, 'maxYear'=>date('Y')+5);
				$options = ControllerForm::getFieldOptions($field, $options, $model);
				$attributes = array();
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('date', $field, $element_label, $options, $attributes);
        $el->setLabel(array($el->_label, "{$field} date"));
				break;
			case $def & N_DAO_TIME:
				$options = array('language'=>'en', 'format'=>'H:i:s');
				$options = ControllerForm::getFieldOptions($field, $options, $model);
				$attributes = array();
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('date', $field, $element_label, $options, $attributes);
        $el->setLabel(array($el->_label, "{$field} date"));
				break;
			case $def & N_DAO_BOOL:
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('checkbox', $field, $element_label, null, $attributes);
        $el->setLabel(array($el->_label, "{$field} checkbox"));
				break;
			case $def & N_DAO_INT:
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('text', $field, $element_label, $attributes);
        $el->setLabel(array($el->_label, "{$field} text"));
				break;
			case $def & N_DAO_FLOAT:
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('text', $field, $element_label, $attributes);
        $el->setLabel(array($el->_label, "{$field} text"));
				break;
			case $def & N_DAO_TXT:
				$attributes = array('rows'=>15, 'cols'=>50);
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('textarea', $field, $element_label, $attributes);
        $el->setLabel(array($el->_label, "{$field} textarea"));
				break;
			case $def & N_DAO_BLOB:
				// do nothing here since binary fields shouldn't be displayed
				break;
			case $def & N_DAO_STR:
				$attributes = ControllerForm::getFieldAttributes($field, $attributes, $model);
				$el = &$form->addElement('text', $field, $element_label, $attributes);
        $el->setLabel(array($el->_label, "{$field} text"));
				break;
		}
		return $el;
	}

	function setFormRules() {
		$form = &$this->form;
		if (is_array($this->model->form_required_fields)) {
			foreach ($this->model->form_required_fields as $field) {
				$el = &$form->getElement($field);
				if (!PEAR::isError($el)) {
					$el_type = $el->getType();
					$el_label = $el->getLabel();
					if ($el_type == 'file' || $el_type == 'cms_file') {
						if ($el_type == 'cms_file') {
							if (!isset($form->_defaultValues[$el->getName()]) || !$form->_defaultValues[$el->getName()]) {
								$form->addRule($field, sprintf($this->rule_required_message, $this->getFieldLabel($field)), 'uploadedfile');
							}
							$form->addFormRule(array($el, '_ruleCheckRemove'));
						} else if ($el_type == 'file') {
							$form->addRule($field, sprintf($this->rule_required_message, $this->getFieldLabel($field)), 'uploadedfile');
						}
					} else if ($el_type == 'group' || is_a($el, 'HTML_QuickForm_group')) {
						$form->addGroupRule($field, sprintf($this->rule_required_message, $el_label), 'required', null, 0, 'client');
					} else if ($el_type == 'fckeditor') {
						$form->addRule($field, sprintf($this->rule_required_message, $el_label), 'required');
					} else {
						$form->addRule($field, sprintf($this->rule_required_message, $el_label), 'required', null, 'client');
					}
				}
			}
		}
		if (is_array($this->model->form_rules)) {
			foreach ($this->model->form_rules as $rule) {
				$el = &$form->getElement($rule[0]);
				if (!PEAR::isError($el)) {
					$el_type = $el->getType();
					if ($el_type == 'group') {
						call_user_func_array(array(&$form, 'addGroupRule'), $rule);
					} else {
						call_user_func_array(array(&$form, 'addRule'), $rule);
					}
				}
			}
		}
	}

	function makeRemoteForm($options=array()) {
		if (!$this->form) {
			// TODO: raise error since we can't update a non-existent form. Must call getForm first.
			return;
		}
		include_once 'view/helpers/javascript_helper.php';
		$form = &$this->form;
		if ($validation = $form->getAttribute('onsubmit')) {
			// try { var myValidator = validate_add_person_form_10; } catch(e) { return true; } return myValidator(this);
			$validation = 'var validated=false; try {var myValidator = validate_' . $form->getAttribute('name') . '; } catch(e) { validated=true; } if (myValidator) { validated=myValidator(this); } ';
			$options['condition'] = 'validated';
		}
		if (isset($options['url']) && is_array($options['url']) && isset($options['url']['controller'])) {
			$controller = &NController::singleton($options['url']['controller']);
		} else {
			$controller = &$this->controller;
		}
		$options['form'] = true;
		$function = JavascriptHelper::remoteFunction($controller, $options);
		$function .= '; return false;';
		$form->updateAttributes(array('onsubmit'=>$validation . $function));

	}

	function processForm($values) {
		$this->controller->preProcessForm($values);
		$model = &$this->model;
		$pk = $model->primaryKey();
		if (!$pk) {
			// TODO: raise error - can't store data if there's no primary key
			return false;
		}
		$table = $model->table();
		$fields = $model->fields();
		$action = 'update';
		if (!isset($model->$pk) || !strlen($model->$pk)) {
			$action = 'insert';
		}
		// setup for file uploads
		$cms_files = array();
		$files = array();
		if (isset($_FILES) && is_array($_FILES)) {
			$form = &$this->form;
			foreach ($_FILES as $field=>$value) {
				$el = &$form->getElement($field);
				if ($el->getType() == 'file' || $el->getType() == 'cms_file') {
					$values[$field] = null;
					if ($el->getType() == 'cms_file') {
						$cms_files[$field] = &$el;
					}
					if (isset($_FILES[$field]['tmp_name']) && is_uploaded_file($_FILES[$field]['tmp_name'])) {
						$files[$field] = array('type'=>$el->getType(), 'upload_dir'=>(isset($el->_options['upload_dir'])?$el->_options['upload_dir']:UPLOAD_DIR), 'value'=>$value);
					} else {
						$values[$field] = isset($this->_do->$field)?$this->_do->$field:'';
					}
				}
			}
		}
		foreach ($cms_files as $field=>$el) {
			if (isset($values[$field.'__remove']) && $values[$field.'__remove']) {
				$values[$field] = '';
				if (isset($files[$field])) {
					unset($files[$field]); // so it doesn't get processed and uploaded after the fact
				}
			} else if (isset($values[$field.'__current']) && $values[$field.'__current']) {
				$values[$field] = $values[$field.'__current'];
			}
		}
		// pull in any boolean fields that weren't passed and should therefore be 0
		foreach ($table as $field=>$def) {
			if ($def & N_DAO_BOOL && !preg_match('/^cms_/', $field)) {
				// if the field is being ignored, then leave the value as what it is
				if (in_array($field, $model->form_ignore_fields)) {
					continue;
				}
				$values[$field] = isset($values[$field])?1:0;
			}
		}
		// deal with general values
		foreach ($values as $field=>$val) {
			if (!in_array($field, $fields)) {
				continue;
			}
			$def = $table[$field];
			switch (true) {
				case ($def & N_DAO_DATE || $def & N_DAO_TIME) && is_array($val):
					$val = NDate::arrayToDate($val);
					if (!($def & N_DAO_NOTNULL)) {
						if ($def & N_DAO_DATE || $def & N_DAO_TIME) {
							if (!NDate::validDateTime($val)) {
								$val = 'null';
							}
						}
					}
					break;
			}
			$model->$field = $val;
		}
		// set the autoheadline if it exists and wasn't set manually
		if (in_array('cms_headline', $fields) && !$values['cms_headline'] && $model->getHeadline()) {
			$model->cms_headline = $model->makeHeadline('-');
		}
		if ($action == 'update') {
			$this->processFiles($values, $files);
			foreach ($files as $field=>$val) {
				if (!in_array($field, $fields)) {
					continue;
				}
				$model->$field = $values[$field];
			}
			$page_content_id = $this->controller->getParam('page_content_id')?$this->controller->getParam('page_content_id'):false;
			if ($page_content_id) {
				$page_content = &NController::singleton('page_content');
				$page_content_model = &$page_content->getDefaultModel();
				$page_content_model->get($page_content_id);
			}
			// set up timed contnt values if they are there
			if (isset($values['timed_start'])) {
				$values['timed_start'] = NDate::arrayToDate($values['timed_start']);
				if (!NDate::validDateTime($values['timed_start'])) {
					$values['timed_start'] = 'null';
				}
			}
			if (isset($values['timed_end'])) {
				$values['timed_end'] = NDate::arrayToDate($values['timed_end']);
				if (!NDate::validDateTime($values['timed_end'])) {
					$values['timed_end'] = 'null';
				}
			}
			// check for workflow
			if (SITE_WORKFLOW && isset($values['__submit_workflow__'])) {
				$page_content = &NController::factory('page_content');
				if (!$page_content_id) {
					// then find the page we're attached to
					$page_content_model = &$page_content->getDefaultModel();
					$page_content_model->content_asset = $this->controller->name;
					$page_content_model->content_asset_id = $model->$pk;
					if ($page_content_model->find(null, true)) {
						$page_content_id = $page_content_model->{$page_content_model->primaryKey()};
					}
				}
				if ($page_content_id) {
					$page_model = $page_content_model->getLink('page_id', 'page');
					$page_id = $page_model->{$page_model->primaryKey()};
					// remove the draft and update the content
					// delete the draft record
					$this->controller->deleteDraft();
					// Pull a fresh copy of the asset model and set the draft to 0
					// so we don't update with the new content yet
					$asset_model = &NModel::factory($this->controller->name);
					$asset_model->get($model->$pk);
					$asset_model->cms_draft = 0;
					$asset_model->update();
				}
				unset($page_content);
				// save the workflow
				$workflow = &NController::factory('workflow');
				$workflow_group_model = $workflow->getWorkflowGroup($page_model);
				// set values for saveWorkflow()
				$workflow_values = array();
				$workflow_values['page_content_id'] = $page_content_model->{$page_content_model->primaryKey()};
				$workflow_values['workflow_group_id'] = $workflow_group_model->{$workflow_group_model->primaryKey()};
				// add timed content
				$workflow_values['timed_start'] = $values['timed_start'];
				$workflow_values['timed_end'] = $values['timed_end'];
				// unset the timed content values so they don't get pushed into page_content
				unset($values['timed_start'], $values['timed_end']);
				$ret = $workflow->saveWorkflow($workflow_values, WORKFLOW_ACTION_EDIT, $this->controller);
			} else if ($page_content_id) {
				$page_content_model->timed_start= $values['timed_start'];
				$page_content_model->timed_end=   $values['timed_end'];
				$page_content_model->col_xs=      $values['col_xs'];
				$page_content_model->col_sm=      $values['col_sm'];
				$page_content_model->col_md=      $values['col_md'];
				$page_content_model->col_lg=      $values['col_lg'];
				$page_content_model->row_xs=      $values['row_xs'];
				$page_content_model->row_sm=      $values['row_sm'];
				$page_content_model->row_md=      $values['row_md'];
				$page_content_model->row_lg=      $values['row_lg'];
				$page_content_model->pull_xs=     $values['pull_xs'];
				$page_content_model->pull_sm=     $values['pull_sm'];
				$page_content_model->pull_md=     $values['pull_md'];
				$page_content_model->pull_lg=     $values['pull_lg'];
				$page_content_model->gutter_xs=   $values['gutter_xs'];
				$page_content_model->gutter_sm=   $values['gutter_sm'];
				$page_content_model->gutter_md=   $values['gutter_md'];
				$page_content_model->gutter_lg=   $values['gutter_lg'];
				$page_content_model->update();
			}
			// check for drafts
			if (SITE_DRAFTS) {
				if (isset($table['cms_draft']) && isset($values['__submit_draft__'])) {
					$ret = $this->controller->saveDraft();
					// update the headline immediately if it exists
					if (isset($table['cms_headline'])) {
						$tmp_model = &NModel::factory($this->controller->name);
						if ($tmp_model && $tmp_model->get($values[$pk])) {
							$tmp_model->cms_headline = $values['cms_headline'];
							$tmp_model->update();
						}
						unset($tmp_model);
					}
				}
			}
			if (isset($values['__submit__'])) {
				if ($this->controller->versioning == true) {
          if (!isset($values['__skip_versioning__'])) {
            $this->controller->debug('Inserting new version for ' . $model->tableName() . ': ' . $model->$pk, 'VERSION');
            $version_id = $this->controller->insertVersion();
          }
				}
				// if it's being saved normally (no draft), make sure it's not marked as a draft
				$model->cms_draft = 0;
				$ret = $model->update();
				if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL && isset($this->controller->_auth)) {
					// audit trail
					$audit_trail = &NController::factory('audit_trail');
					$audit_trail_values = array();
					$audit_trail_values['asset'] = $this->controller->name;
					$audit_trail_values['asset_id'] = $model->$pk;
					$audit_trail_values['action_taken'] = AUDIT_ACTION_UPDATE;
					if (isset($page_content_id)) {
						$audit_trail_values['page_content_id'] = $page_content_id;
					}
					if (isset($page_id)) {
						$audit_trail_values['page_id'] = $page_id;
					}
					$audit_trail->insert($audit_trail_values);
					unset($audit_trail);
				}
				if (SITE_DRAFTS) {
					// delete any draft records
					$this->controller->deleteDraft();
				}
				// remove all linked page caches
				$page = &NController::factory('page');
				$page_content_model = &NModel::factory('page_content');
				$page_content_model->content_asset = $this->controller->name;
				$page_content_model->content_asset_id = $values[$pk];
				if ($page_content_model->find()) {
					while ($page_content_model->fetch()) {
						$page->deletePageCache($page_content_model->page_id);
					}
				}
				unset($page);
				unset($page_content_model);
			}
		} else {
			$draft = false;
			if (SITE_DRAFTS && isset($table['cms_draft']) && isset($values['__submit_draft__'])) {
				$model->cms_draft = 1;
			}
			$id = $model->insert();
			$this->processFiles($values, $files);
			foreach ($files as $field=>$val) {
				if (!in_array($field, $fields)) {
					continue;
				}
				$model->$field = $values[$field];
			}
			if (SITE_DRAFTS && isset($table['cms_draft']) && isset($values['__submit_draft__'])) {
				// set draft to true after the draft is saved
				$draft = $this->controller->saveDraft();
			}
			$model->update();
			if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL && isset($this->controller->_auth)) {
				// audit trail
				$audit_trail = &NController::factory('audit_trail');
				$audit_trail_values = array();
				$audit_trail_values['asset'] = $this->controller->name;
				$audit_trail_values['asset_id'] = $model->$pk;
				$audit_trail_values['action_taken'] = AUDIT_ACTION_INSERT;
				if (isset($page_content_id)) {
					$audit_trail_values['page_content_id'] = $page_content_id;
				}
				if (isset($page_id)) {
					$audit_trail_values['page_id'] = $page_id;
				}
				$audit_trail->insert($audit_trail_values);
				unset($audit_trail);
			}
			$ret = $id;
		}
		if ($ret) {
			$this->controller->postProcessForm($values);
		}
		if (isset($values['_referer'])) {
			header('Location:' . urldecode($values['_referer']));
			exit;
		}
		return $ret;
	}

	function processFiles(&$values, $files_array) {
		include_once 'n_filesystem.php';
		$form = &$this->form;
		$model = &$this->model;
		$pk = $model->primaryKey();

		foreach ($files_array as $field=>$vals) {
			$tmp_file = $vals['value']['tmp_name'];

			if (!is_uploaded_file($tmp_file)) {
				$values[$field] = '';
				continue;
			}

			$path = array();

			if ($vals['type'] == 'cms_file') {
				$path[] = $this->controller->name;
				$path[] =  $model->$pk;
			}

			$path[] = substr(md5(microtime()), 20);
			$path[] = NFilesystem::cleanFileName($vals['value']['name']);
			$filename = implode('/', $path);

			$tmp_file = $model->beforeUpload($field, $tmp_file);

			$newfile = NUpload::moveUpload($tmp_file, $filename);

			$values[$field] = $newfile ? $newfile : '';
		}
	}

	function validDateTime($value, $def) {
		if (!$def) return true;
		if (($def & N_DAO_DATE && $def & N_DAO_TIME && preg_match('/0000-00-00 00:00(?::00)?/', $value)) ||
			($def & N_DAO_DATE && $value == '0000-00-00') ||
			($def & N_DAO_TIME && preg_match('/00:00(?::00)?/', $value))) {
			return true;
		}
		return false;
	}

	function exportValues($element_list = null) {
		$vals = $this->form->exportValues($element_list);
		$fields = $this->model->table();
		foreach ($vals as $field=>$val) {
			if (!isset($fields[$field])) {
				continue;
			}
			$def = $fields[$field];
			switch (true) {
				case $def & N_DAO_DATE:
				case $def & N_DAO_TIME:
					if (is_array($vals[$field]))
						$vals[$field] = NDate::arrayToDate($vals[$field]);
			}
		}
		return $vals;
	}
	function getFieldLabel($field, $model=null) {
		if (!$model && isset($this)) {
			$model = $this->model;
		}
		if (!$model) return;
		return isset($model->form_field_labels[$field])?$model->form_field_labels[$field]:Inflector::humanize($field);
	}

	function getFieldOptions($field, $options=array(), $model=null) {
		if (!$model && isset($this)) {
			$model = $this->model;
		}
		if (!$model) return;
		$opts = isset($model->form_field_options[$field])?$model->form_field_options[$field]:array();
		return array_merge($options, $opts);
	}

	function getFieldAttributes($field, $attributes=array(), $model=null) {
		if (!$model && isset($this)) {
			$model = $this->model;
		}
		if (!$model) return;
		$atts = isset($model->form_field_attributes[$field])?$model->form_field_attributes[$field]:array();
		return array_merge($attributes, $atts);
	}

	function _loadOptions($options) {
		if (is_array($options)) {
			$this->model_name = isset($options['model_name'])?$options['model_name']:$this->controller->name;
		} else {
			$this->model_name = empty($options)?$this->controller->name:$options;
		}
	}
}

require_once 'HTML/QuickForm/select.php';
class HTML_QuickForm_foreignkey extends HTML_QuickForm_select {
	function HTML_QuickForm_foreignkey($elementName=null, $elementLabel=null, $options=null, $attributes=null) {
		if (!isset($options['model']) || !isset($options['headline']))
			return;
		$add_empty_option = isset($options['addEmptyOption'])?(bool) $options['addEmptyOption']:false;
		$model = $options['model'];
		$headline = $options['headline'];
		$separator = isset($options['separator'])?$options['separator']:' - ';
		$options = array();
		$model = &NModel::factory($model);
		if ($model && $model->find()) {
			if ($add_empty_option)
				$options[''] = 'Select...';
			$pk = $model->primaryKey();
			while ($model->fetch()) {
				if (is_array($headline)) {
					$label = '';
					foreach ($headline as $field) {
						$label .= ($label?$separator:'') . $model->$field;
					}
				} else {
					$label = $model->$headline;
				}
				$options[$model->$pk] = $label;
			}
		}
		if (empty($options)) {
			$options[0] = 'N/A';
		}
		$this->HTML_QuickForm_select($elementName, $elementLabel, $options, $attributes);
		$this->_type = 'foreignkey';
	}
}

require_once 'HTML/QuickForm/textarea.php';
class HTML_QuickForm_fckeditor extends HTML_QuickForm_textarea {
	var $_editor = null;
	var $_editor_config = array(
		'CustomConfigurationsPath' => null,
		'Debug' => null,
		'SkinPath' => null,
		'PluginsPath' => null,
		'AutoDetectLanguage' => null,
		'DefaultLanguage' => null,
		'EnableXHTML' => null,
		'EnableSourceXHTML' => null,
		'GeckoUseSPAN' => null,
		'StartupFocus' => null,
		'ForcePasteAsPlainText' => null,
		'ForceSimpleAmpersand' => null,
		'TabSpaces' => null,
		'UseBROnCarriageReturn' => null,
		'LinkShowTargets' => null,
		'LinkTargets' => null,
		'LinkDefaultTarget' => null,
		'ToolbarStartExpanded' => null,
		'ToolbarCanCollapse' =>null,
		'StylesXmlPath' => null
		);
	function HTML_QuickForm_fckeditor($elementName=null, $elementLabel=null, $attributes=null, $options=null) {
		include_once BASE_DIR . '/nterchange/javascripts/fckeditor/fckeditor.php';
		$this->_editor = new FCKEditor($elementName);
		$settings_model = &NModel::factory('cms_settings');
		$editor_set = $settings_model->getSetting(SETTINGS_EDITOR);
		unset($settings_model);
		if ($editor_set == false || !$this->_editor->IsCompatible()) {
			HTML_QuickForm_textarea::HTML_QuickForm_textarea($elementName, $elementLabel, $attributes);
			// if the browser isn't compatible, then remove _editor parameter and set up as a textarea
			unset($this->_editor);
			$this->updateAttributes(array('rows'=>25, 'cols'=>60, 'style'=>'width:470px;height:500px;'));
		} else {
			HTML_QuickForm_element::HTML_QuickForm_element($elementName, $elementLabel, $attributes);
			$this->_type = 'fckeditor';
			$this->_persistantFreeze = true;
			// set base options and paths
			$this->_editor->BasePath = '/nterchange/javascripts/fckeditor/';
			$this->_editor->Width = '470px';
			$this->_editor->Height = '500px';
			if (file_exists(DOCUMENT_ROOT . '/includes/fckstyles.xml')) {
				$this->_editor_config['StylesXmlPath'] = '/includes/fckstyles.xml';
			}
			if (file_exists(DOCUMENT_ROOT . '/includes/fcktemplates.xml')) {
				$this->_editor_config['TemplatesXmlPath'] = '/includes/fcktemplates.xml';
			}
			// overwrite any set $_editor_options with the passed $options, only allowing the ones that already exist
			if (is_array($options)) {
				foreach ($options as $option=>$val) {
					if (in_array($option, array_keys($this->_editor_config))) {
						$this->_editor_config[$option] = $val;
					}
				}
			}
			// load any $_editor_options that are not null into the $_editor->Config array
			foreach ($this->_editor_config as $option=>$val) {
				if (!is_null($val)) {
					$this->_editor->Config[$option] = $val;
				}
			}
			// point at nterchange custom configuration file
			if (file_exists(DOCUMENT_ROOT . '/javascripts/fckconfig.js')) {
				$this->_editor->Config['CustomConfigurationsPath'] = '/javascripts/fckconfig.js?' . filemtime(DOCUMENT_ROOT . '/javascripts/fckconfig.js');
			} else {
				$this->_editor->Config['CustomConfigurationsPath'] = $this->_editor->BasePath . 'n_config.js?' . filemtime(BASE_DIR . '/nterchange/javascripts/fckeditor/n_config.js');
			}
			// set the toolbar to our configured toolbar
			$this->_editor->ToolbarSet = 'nonfiction';
		}
	}

	function setName($name) {
		$this->updateAttributes(array('name'=>$name));
		if (isset($this->_editor)) {
			$this->_editor->InstanceName = $name;
		}
	}

	function getName() {
		return $this->getAttribute('name');
	}

	function setValue($value) {
		if (isset($this->_editor)) {
			$this->_editor->Value = $value;
		} else {
			parent::setValue($value);
		}
	}

	function getValue() {
		if (isset($this->_editor)) {
			return $this->_editor->Value;
		} else {
			return parent::getValue();
		}
	}

	function setWidth($width) {
		if (isset($this->_editor)) {
			$this->_editor->Width = $width;
		}
	}

	function setHeight($height) {
		if (isset($this->_editor)) {
			$this->_editor->Height = $height;
		}
	}

	function toHtml() {
		if (isset($this->_editor)) {
			if ($this->_flagFrozen) {
				return $this->getFrozenHtml();
			} else {
				return $this->_getTabs() . $this->_editor->CreateHtml();
			}
		} else {
			return parent::toHtml();
		}
	}

	function getFrozenHtml() {
		$value = $this->getValue();
		$html = $value ."\n";
		return $html . $this->_getPersistantData();
	}
}
