<?php
require_once 'n_model.php';
require_once 'model/tree.php';
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
 * @category   Page Model
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class Page extends ModelTree {
	var $_order_by = 'page.sort_order, page.id';

	function __construct() {
		$this->__table = 'page';
		parent::__construct();
		$this->setHeadline('title');
		// form & validation
		$this->form_header = 'title';
		$this->form_field_labels['filename'] = 'Filename<br /><small class="highlight">(nonfiction use)</small>';
		// always ignore these as they are updated programatically
		$this->form_ignore_fields = array('path', 'sort_order', 'static_content');
		if (!defined('SITE_DISCLAIMER') || !SITE_DISCLAIMER || !defined('SITE_DISCLAIMER_ID') || !SITE_DISCLAIMER_ID) {
			$this->form_ignore_fields[] = 'disclaimer_required';
			$this->form_ignore_fields[] = 'disclaimer_recursive';
		}
		// always require the title
		$this->form_required_fields = array('title');
		// cache lifetime options
		$this->form_elements['cache_lifetime'] = array('select', 'cache_lifetime', 'Cache Lifetime', array(0=>'no caching', 5*60=>'5 minutes', 15*60=>'15 minutes', 60*60=>'1 hour', 60*60*8=>'8 hours', 60*60*24=>'1 day', 60*60*24*7=>'1 week', '-1'=>'indefinite'));
		// cache lifetime options
		$this->form_elements['client_cache_lifetime'] = array('select', 'client_cache_lifetime', 'Client Cache Lifetime', array(0=>'no caching', 5*60=>'5 minutes', 15*60=>'15 minutes', 60*60=>'1 hour', 60*60*8=>'8 hours', 60*60*24=>'1 day', 60*60*24*7=>'1 week', '-1'=>'indefinite'));

		// this is a foreign key
		$this->form_elements['page_template_id'] = array('foreignkey', 'page_template_id', 'Template', array('model'=>'page_template', 'headline'=>'template_name'));
		// Ignore printable - not being used in nterchange 3 at all.
		$this->form_ignore_fields[] = 'printable';
		
		// Ignore permissions field - not being used in nterchange 3 at the moment.
		$this->form_ignore_fields[] = 'permissions_id';
		
		// check for workflow being used
		if (SITE_WORKFLOW) {
			$this->form_elements['workflow_group_id'] = array('foreignkey', 'workflow_group_id', 'Workflow Group', array('model'=>'workflow_group', 'headline'=>'workflow_title', 'addEmptyOption'=>true));
			$this->form_field_labels['workflow_recursive'] = 'Cascade Workflow';
		} else {
			$this->form_ignore_fields[] = 'workflow_group_id';
			$this->form_ignore_fields[] = 'workflow_recursive';
		}
		if (!SECURE_SITE) {
			$this->form_ignore_fields[] = 'secure_page';
		}
		$this->form_rules[] = array('parent_id', 'You cannot make a page a child of itself.', 'callback', array(&$this, 'checkChildOfItself'));

		// cache lifetime should be indefinite by default
		$this->form_field_defaults['cache_lifetime'] = '-1';
		$this->form_field_defaults['client_cache_lifetime'] = '3600';
		$this->form_field_defaults['visible'] = 1;
		$this->form_field_defaults['active'] = 1;
	}

	/**
	 * checkChildOfItself - A page cannot be a child of itself. That would be
	 * illogical and cause that page to disappear. Returns true if OK and false
	 * if you're setting something to be a child of itself.
	 *
	 * @param	int		The page_id of a page.
	 * @return 	boolean
	 **/
	function checkChildOfItself($parent_id) {
		$parent_id = (int) $parent_id;
		$pk = $this->primaryKey();
		$page = &NController::singleton('page');
		$page_id = (int) $page->getParam($pk);
		if (!$page_id) {
			return true;
		}
		if ($page_id == $parent_id) {
			return false;
		}
		$all_children = $this->getAllChildren($page_id, false, false);
		foreach ($all_children as $child) {
			if ($parent_id == $child[$pk]) {
				return false;
			}
		}
		return true;
	}
	
	function afterCreate() {
		// Delete Smarty Cache
		$this->deleteSmartyCache();
	}
	
	function afterUpdate() {
		// Delete Smarty Cache
		$this->deleteSmartyCache();
	}
	
	function afterDelete($page_id) {
		// After a page is deleted, make sure to remove all linked 
		// content from the page_content table. Cleans things up and
		// helps with some possible workflow trauma.
		$page_content = NModel::factory('page_content');
		$page_content->deleteOrphanedPageContent($page_id);
		// Delete Smarty Cache
		$this->deleteSmartyCache();
	}
	
	/**
	 * deleteSmartyCache - Delete the entire cache when you make a page change.
	 * If you're including the navigation in the page as ul/li's - this keeps the
	 * navigation always consistent.
	 *
	 * @return void
	 **/
	function deleteSmartyCache() {
		// Only delete the entire cache if the NAV_IN_PAGE is true and we're in production.
		if (defined('NAV_IN_PAGE') && NAV_IN_PAGE && (ENVIRONMENT == 'production') && !isset($this->smarty_cache_cleared)) {
			NDebug::debug('We are clearing the smarty caches because of a page edit.' , N_DEBUGTYPE_INFO);
			$view = &NView::singleton($this);
			$view->clear_all_cache();
			$site_admin = NController::factory('site_admin');
			$site_admin->rmDirFiles(CACHE_DIR . '/smarty_cache');
			$site_admin->rmDirFiles(CACHE_DIR . '/templates_c');
			$this->smarty_cache_cleared = true;
		}
	}
}
?>