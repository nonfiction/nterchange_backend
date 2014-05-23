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
 * @category   nterchange settings Administration
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class SettingsController extends nterchangeController {
	function __construct() {
		$this->name = 'settings';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_NORIGHTS;
		// $this->public_actions = array();
		$this->login_required = true;
		parent::__construct();
	}

	function &getDefaultModel() {
		return $this->loadModel('cms_settings');
	}

	function index() {
		$this->redirectTo('viewlist');
		$this->auto_render = false;
		$model = &$this->getDefaultModel();

		$this->render(array('layout'=>'default'));
	}

	function viewlist() {
		$this->auto_render = false;
		include_once 'n_quickform.php';
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		$setting_forms = array();
		$user_settings = $GLOBALS['USER_SETTINGS'];
		foreach ($user_settings as $setting=>$default) {
			$model->reset();
			$model->user_id = (int) $this->_auth->currentUserId();
			$model->setting = $setting;
			$form = new NQuickForm('setting_' . $setting);
			$form->addElement('header', null, $model->settingToText($setting));
			$description = $this->getSettingDescription($setting);
			if (!$description) {
				$description = 'Setting';
			}
			$form->addElement('hidden', 'setting', $setting);
			$checkbox = &$form->addElement('checkbox', 'value', $description, null, array('id'=>'qf_' . $model->setting));
			if ($model->find(null, true)) {
				// set the form action to edit
				$form->updateAttributes(array('action'=>'/' . APP_DIR . '/' . $this->name . '/edit/' . $model->$pk));
				$form->addElement('hidden', $pk, $model->$pk);
				// check the box according to the value
				$checkbox->setChecked((bool) $model->value);
			} else {
				$form->updateAttributes(array('action'=>'/' . APP_DIR . '/' . $this->name . '/create'));
				$checkbox->setChecked((bool) $default);
			}
			$form->addElement('hidden', '_referer', urlencode(NServer::env('REQUEST_URI')));
			$form->addElement('submit', '__submit__', 'Submit');
			$form->addRule('setting', null, 'required');
			$setting_forms[] = &$form;
		}
		$this->set('settings', $setting_forms);
		$this->render(array('layout'=>'default'));
	}

	function show($parameter) {
		$model = &$this->getDefaultModel();
		$model->user_id = (int) $this->_auth->currentUserId();
		return parent::show($parameter);
	}

	function edit($parameter) {
		$model = &$this->getDefaultModel();
		$model->user_id = (int) $this->_auth->currentUserId();
		$this->flash->set('notice', 'Your preference has been saved.');
		return parent::edit($parameter);
	}

	function create($parameter) {
		$model = &$this->getDefaultModel();
		$model->user_id = (int) $this->_auth->currentUserId();
		$this->flash->set('notice', 'Your preference has been saved.');
		return parent::create($parameter);
	}

	function getSettingDescription($setting) {
		switch ($setting) {
			case SETTINGS_EDITOR:
				$ret = '<abbr title="What You See Is What You Get">WYSIWYG</abbr> Editor<br />Choose whether you want the editor to be active for you or not.';
				break;
		}
		return $ret;
	}
}
?>
