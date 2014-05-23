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
 * @category   Site Admin
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class SiteAdminController extends nterchangeController {
	function __construct() {
		if (get_class($this) == __CLASS__) {
			$this->name = 'site_admin';
			// login required
			$this->login_required = true;
			// set user level allowed to access the actions with required login
			$this->user_level_required = N_USER_NORIGHTS;
			// set up view caching values
			$this->view_cache_name = 'nterchange_site_admin';
			$this->view_caching = true;
			$this->view_cache_lifetime = -1; // forever
		}
		parent::__construct();
	}

	function index() {
		$this->nterchange = true;
		$this->auto_render = false;
		// preset layout values
		$main_content = null;
		$sidebar = null;
		$this->view_cache_name = 'nterchange_site_admin_' . $this->_auth->currentUserID();
		// check if it's currently being built, if so, wait so you can use the cached version
		// this protects against multiple people building the site admin at once
		$buildfile = CACHE_DIR . '/ntercache/siteadminbuild';
		// if the menu is being built, then wait quarter second and try again.
		$wait = 0;
		while (1==1) {
			if (!file_exists($buildfile) || $this->isCachedLayout('default') || time() - filemtime($buildfile) > 8) {
				if ($wait > 0 && defined('LOG_CACHE') && LOG_CACHE == true) $this->debug('Client waited for ' . number_format($wait) . ' microseconds for someone else to write ' . $this->view_cache_name);
				break;
			}
			$wait += 250000;
			usleep(250000);
		}
		if (!$this->isCachedLayout('default')) {
			@touch($buildfile);
			$this->set('user_level', $this->_auth->getAuthData('user_level'));
			$this->page_title = 'Site Admin';
			$this->set(array('asset'=>$this->name, 'sitemap_list'=>$this->siteAdminList()));
			$main_content = $this->render(array('return'=>true, 'action'=>'site_admin'));
			@unlink($buildfile);
		}
		// set caching back to true before rendering as it was set to false in siteAdminList()
		$this->view_caching = true;
		$this->renderLayout('default', $main_content, $sidebar);
	}

	function siteAdminList($id=null) {
		// set view caching to false so as to not cache every item
		$this->view_caching = false;
		$page_ctrl = &NController::singleton('page');
		$model = &$page_ctrl->getDefaultModel();
		$model->reset();
		$pk = $model->primaryKey();
		$html = '';
		$model->parent_id = $id?(int) $id:'null';
		if ($model->find()) {
			$this->set('reorder', ($id==0?false:true));
			$this->set('parent_id', $id);
			$html .= $this->render(array('action'=>'site_admin_list_start', 'return'=>true));
			$i = 0;
			$assigns['_referer'] = urlencode(NServer::env('REQUEST_URI'));
			$pages = &$model->fetchAll();
			foreach ($pages as $page) {
				$page_edit = false;
				$surfedit = false;
				switch ($this->_auth->getAuthData('user_level')) {
					case N_USER_EDITOR:
						$surfedit = true;
						break;
					case N_USER_ADMIN:
					case N_USER_ROOT:
						$page_edit = true;
						$surfedit = true;
						break;
				}
				if (SITE_WORKFLOW) {
					$assigns['workflow'] = '';
					$workflow = &NController::singleton('workflow');
					if ($workflow_group_model = &$workflow->getWorkflowGroup($page)) {
						$user_rights = $workflow->getWorkflowUserRights($page);
						if ($user_rights & WORKFLOW_RIGHT_EDIT) {
							$surfedit = true;
						}
						$assigns['workflow'] = $workflow_group_model->workflow_title;
					}
				}
				$assigns['id'] = $page->$pk;
				$assigns['title'] = $page->title;
				$assigns['active'] = $page->active;
				$assigns['visible'] = $page->visible;
				$assigns['page_edit'] = $page_edit;
				$assigns['surfedit'] = $surfedit;
				$assigns['odd_or_even'] = $i%2 == 0?'even':'odd';
				$this->set($assigns);
				$html .= $this->render(array('action'=>'sitemap_list_item', 'return'=>true));
				$i++;
				$html .= $this->siteAdminList($page->$pk);
			}
			unset($pages);
			$html .= $this->render(array('action'=>'site_admin_list_end', 'return'=>true));
		}
		unset($model, $page_ctrl);
		return $html;
	}

	/**
	 * clearAllCache
	 *
	 * front-end method for clearing caches (see clearCache for the actual work)
	 */
	function clearAllCache() {
		$this->page_title = 'Clear all caches';
		$this->auto_render = false;
		$this->clearCache();
		$this->render(array('layout'=>'default'));
	}

	/**
	 * clearCache
	 *
	 * clears the view and database caches as well as CDN cache if applicable
	 */
	function clearCache() {
		$this->view_cache_name = 'clear_all_cache';
		$this->view_caching = false;
		$view = &NView::singleton($this);
		$view->clear_all_cache();
		// Remove cache folder contents
		$this->rmDirFiles(CACHE_DIR . '/templates_c');
		$this->rmDirFiles(CACHE_DIR . '/smarty_cache');
		$this->rmDirFiles(CACHE_DIR . '/ntercache');
		$this->rmDirFiles(CACHE_DIR . '/ntercache/db');
		$this->rmDirFiles(CACHE_DIR . '/ntercache/code_caller');
		$this->rmDirFiles(CACHE_DIR . '/magpie');

		// Clear CDN cache if needed.
		if (defined('CDN_CLEAR') && CDN_CLEAR) {
			if (defined('CDN_TYPE')) {
				$this->debug('Clearing the CDN cache');
				$cdn_file = realpath(ROOT_DIR . '/lib/cdn/' . CDN_TYPE . '.php');
				if (file_exists($cdn_file)) {
					$this->debug('CDN ' . CDN_TYPE . ' exists.');
					include($cdn_file);
				}
			}
		}
	}

	function rmDirFiles($dir) {
		if (!$dir) return;
		$dir = preg_replace('/\/$/', '', $dir);
		if (!file_exists($dir) || !is_dir($dir)) return;
		if (!$dh = @opendir($dir)) return;
		while (false !== ($file = readdir($dh))) {
			if (preg_match('/^\./', $file)) {
				continue;
			}
			@unlink($dir . '/' . $file);
		}
		closedir($dh);
		return true;
	}

	function deleteCache() {
		$auth_model = &NModel::factory('cms_auth');
		if ($auth_model->find()) {
			$pk = $auth_model->primaryKey();
			while ($auth_model->fetch()) {
				// remove the site admin cache
				$this->view_cache_name = 'nterchange_site_admin_' . $auth_model->$pk;
				$view = &NView::singleton($this);
				$view->clearLayoutCache('default');
			}
		}
		unset($auth_model);
		unset($view);
	}
}
?>
