<?php
require_once 'admin_controller.php';
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
 * @category   Page Template Administration
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class PageTemplateController extends AdminController {
	function __construct() {
		$this->name = 'page_template';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_ADMIN;
		$this->login_required = true;
		$this->page_title = 'Templates';
		parent::__construct();
	}

	function &getDefaultModel() {
		$model = &$this->loadModel('page_template');
		return $model;
	}
	
	function doesPageTemplateExist($filename){
		$full_path_filename = ASSET_DIR . '/views/page/' . $filename . '.' . DEFAULT_PAGE_EXTENSION;
		if (file_exists($full_path_filename)) {
			return true;
		} else {
			return false;
		}
	}
	
	function postGenerateForm(&$form) {
		$form->removeElement('__header__');
		$form->addRule('template_filename', 'We need to have a filename.', 'required', null, 'client');
		$form->addRule('template_name', 'We need to have a name for this template.', 'required', null, 'client');
		$form->addRule('template_filename', 'Letters, numbers, dashes and underscores - without a suffix, spaces or punctuation.', 'regex', '/^[a-zA-Z0-9_-]+$/', 'client');
		//'Code contains numbers only', 'regex', '/^\d+$/'
	}
}
?>