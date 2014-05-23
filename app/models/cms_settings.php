<?php
require_once 'n_model.php';

/**
 * Settings default defines.
 **/
define('SETTINGS_EDITOR', 1);
define('SETTINGS_EDITOR_DEFAULT', true);
$GLOBALS['USER_SETTINGS'] = array(SETTINGS_EDITOR=>SETTINGS_EDITOR_DEFAULT);

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
 * @category   CMS Settings Model
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsSettings extends NModel {
	function __construct() {
		$this->__table = 'cms_settings';
		$this->setHeadline('setting_var');
		$this->_order_by = 'cms_settings.setting';
		$this->form_elements['setting'] = array('select', 'setting', 'Setting', array(SETTINGS_EDITOR=>'WYSIWYG Editor'));
		$this->form_ignore_fields[] = 'user_id';
		parent::__construct();
	}

	/**
	 * settingToText - Returns English description of setting.
	 *
	 * @param	int		Setting define id
	 * @return 	string	English description of that string
	 **/
	function settingToText($setting) {
		$ret = '';
		switch($setting) {
			case SETTINGS_EDITOR:
				$ret = 'WYSIWYG Editor';
				break;
		}
		return $ret;
	}

	function fetch() {
		$ret = parent::fetch();
		if ($ret) {
			$this->setting_var = $this->settingToText($this->setting);
		}
		return $ret;
	}

	function toArray() {
		$ret = parent::toArray();
		if (is_array($ret) && count($ret) && isset($this->setting_var)) {
			$ret['setting_var'] = $this->setting_var;
		}
		return $ret;
	}

	/**
	 * getSetting - Get a user's setting from the database - or use the defaults.
	 *
	 * @param	int		The id of the particular setting.
	 * @return 	boolean
	 **/
	function getSetting($setting) {
		$auth = new NAuth();
		$this->user_id = $auth->currentUserID();
		$this->setting = $setting;
		if ($this->find(null, true)) {
			$ret = (bool) $this->value;
		} else {
			$user_settings = $GLOBALS['USER_SETTINGS'];
			$ret = isset($user_settings[$setting])?$user_settings[$setting]:true;
		}
		$this->reset();
		return $ret;
	}
}
?>
