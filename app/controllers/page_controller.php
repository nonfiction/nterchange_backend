<?php
require_once 'n_date.php';
require_once 'site_admin_controller.php';
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
 * @category   Page
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class PageController extends SiteAdminController {
	// ONLY IN THIS CONTROLLER
	var $nterchange = false;
	var $edit = false;

	// permissions settings
	var $page_edit_allowed = false;
	var $content_edit_allowed = false;

	var $page_last_modified = 0;
	var $view_cache_lifetimes = array();

	// special settings for page
	var $public_actions = array('index', 'menus');

	function __construct() {
		$this->name = 'page';
		// more logins for this controller
		$this->login_required[] = 'surftoedit';
		$this->login_required[] = 'preview';
		$this->login_required[] = 'site_admin';
		$this->login_required[] = 'content';
		$this->login_required[] = 'children';
		$this->login_required[] = 'reorder_content';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_ADMIN;
		parent::__construct();
	}

	function index($parameter) {
		$this->page($parameter);
	}

	function create($parameter) {
		$this->nterchange = true;
		parent::create($parameter);
	}

	function edit($parameter) {
		if (!$this->checkUserLevel()) {
			$this->redirectTo(array('site_admin'));
		}
		$this->nterchange = true;
		$this->loadSubnav($parameter);
		$model = &$this->getDefaultModel();
		$model->get($parameter);
		$this->page_title = 'Page - ' . $model->title;
		$this->set('page_title', $model->title);
		$this->loadSidebar();
		$model->reset();
		parent::edit($parameter);
	}

	/**
	 * delete - Very bad things can happen if certain pages are deleted,
	 * here we check the id and throw a nasty error if that happens.
	 */
	function delete($parameter) {
		$protected_pages = NConfig::$protectedPages;

		if (in_array($parameter, $protected_pages)) {
			$this->flash->set('notice', 'This content is protected and cannot be deleted.');
			$this->redirectTo(array('page', "edit/$parameter"));
		} else {
			parent::delete($parameter);
		}
	}

	function content($parameter) {
		if (!$this->checkUserLevel()) {
			$this->redirectTo(array('site_admin'));
		}
		$this->nterchange = true;
		$this->auto_render = false;
		$this->page_title = 'Page Content';
		$this->loadSubnav($parameter);
		$model = &$this->loadModel($this->name);
		$page_content = &NController::factory('page_content');
		$page_content_model = &$page_content->loadModel('page_content');
		$pk = $page_content_model->primaryKey();
		$page_content->set('page_id', $parameter);
		$content_html = '';
		if ($model->get($parameter)) {
			$this->page_title .= ' - ' . $model->title;
			$content = array();
			$template_containers = $this->templateContainers($model->page_template_id);
			if (!is_array($template_containers)) return null;
			foreach ($template_containers as $container) {
				if (empty($container)) continue;
				$page_content_model->reset();
				$page_content_model->getContainerContent($model->{$model->primaryKey()}, $container['id'], $this->nterchange);
				$this->set(array('container'=>$container));
				$content_html .= $this->render(array('action'=>'content_container_title', 'return'=>true));
				$page_content->set('template_container_id', $container['id']);
				$page_content->set('no_reorder', true);
				$content_html .= $page_content->render(array('action'=>'asset_add', 'return'=>true));
				if ($page_content_model->numRows()) {
					$contents = array();
					while ($page_content_model->fetch()) {
						$asset_ctrl = &NController::factory($page_content_model->content_asset);
						if ($asset_ctrl && $asset_model = &$asset_ctrl->loadModel($asset_ctrl->name) && $asset_model->get($page_content_model->content_asset_id)) {
							$content = $asset_model->toArray();
							$content['_asset'] = $asset_ctrl->name;
							$content['_asset_name'] = Inflector::humanize($asset_ctrl->name);
							$content['page_content_id'] = $page_content_model->$pk;
							$contents[] = $content;
						}
						unset($asset_ctrl);
					}
					if (empty($contents)) continue;
					$this->set('template_container_id', $container['id']);
					$this->set('contents', $contents);
					$this->set(array('reorder_link'=>count($contents) > 1?true:false));
					$content_html .= $this->render(array('action'=>'content_container', 'return'=>true));
				}
			}
			$this->set('page_title', $model->title);
			$this->loadSidebar();
		}
		$this->set('MAIN_CONTENT', $content_html);
		$this->render(array('layout'=>'default'));
	}

	function children($parameter) {
		if (!$this->checkUserLevel()) {
			$this->redirectTo(array('site_admin'));
		}
		$this->nterchange = true;
		$this->auto_render = false;
		// set view caching to false
		$this->view_caching = false;
		$this->loadSubnav($parameter);
		$model = &$this->getDefaultModel();
		if ($model->get($parameter)) {
			$this->page_title = 'Page Children - ' . $model->title;
			$children = $model->getChildren($parameter, false, false);
			$this->set('page_title', $model->title);
			$this->set('parent_id', $parameter);
			$this->set('pages', $children);
			$this->loadSidebar();
		}
		$this->render(array('layout'=>'default', 'action'=>'children_container'));
	}

	function loadSidebar() {
		$model = &$this->getDefaultModel();
		$surfedit = false;
		switch ($this->_auth->getAuthData('user_level')) {
			case N_USER_EDITOR:
				$surfedit = true;
				break;
			case N_USER_ADMIN:
			case N_USER_ROOT:
				$surfedit = true;
				break;
		}
		if (SITE_WORKFLOW) {
			$assigns['workflow'] = '';
			$workflow = &NController::singleton('workflow');
			if ($workflow_group_model = &$workflow->getWorkflowGroup($model)) {
				$user_rights = $workflow->getWorkflowUserRights($model);
				if ($user_rights & WORKFLOW_RIGHT_EDIT) {
					$surfedit = true;
				}
			}
		}
		$this->set('surfedit', $surfedit);
		$this->set('page_id', $model->{$model->primaryKey()}?$model->{$model->primaryKey()}:null);
		$this->set('action', $this->action);
		$this->set(array('children'=>$this->getChildren(), 'breadcrumbs'=>$this->getBreadcrumbs(false)));
		$this->set('SIDEBAR_CONTENT', $this->render(array('action'=>'sidebar', 'return'=>true)));
	}

	function reorder($parameter) {
		NDebug::debug('Reordering children of page id ' . $parameter, N_DEBUGTYPE_INFO);
		if (!$this->checkUserLevel()) {
			$this->redirectTo(array('site_admin'));
		}
		$id = isset($this->params['id'])?(int) $this->params['id']:false;
		if (!$id) {
			return;
		}
		$before = isset($this->params['before'])?(int) $this->params['before']:false;
		if ($model = &$this->getDefaultModel() && $model->get($id)) {
			$pk = $model->primaryKey();
			$page_model = &NModel::factory($this->name);
			$page_model->parent_id = $parameter;
			$pages = array();
			if ($page_model->find()) {
				$before_found = false;
				$item = null;
				while ($page_model->fetch()) {
					if ($page_model->$pk == $id) {
						// pull the "id" record out of the array
						$item = clone($page_model);
					} else {
						$pages[] = clone($page_model);
					}
				}
				if (!$before) { // if there is no before, then the item goes last
					$pages[] = $item;
				} else { // loop through until you find "before" and then splice the "id" in front of it
					foreach ($pages as $key=>$page) {
						if ($page->$pk == $before) {
							array_splice($pages, $key, 1, array($item, $page));
							break;
						}
					}
				}
				$i = 0;
				foreach ($pages as $key=>$page) {
					$page->sort_order = $i;
					$page->save();
					$i++;
				}
			}
			$site_admin = &NController::singleton('site_admin');
			if ($model->getParent($id) == $model->getRootNode()) {
				// we need to clear all caches
				@ob_start();
				$site_admin->clearAllCache();
				@ob_end_clean();
			} else {
				$site_admin->deleteCache();
				include_once 'n_cache.php';
				NCache::removeJavascript();
			}
			unset($site_admin);
			unset($pages);
			unset($page_model);
			unset($model);
		}
	}

	function checkUserLevel() {
		switch ($this->action) {
			case 'surftoedit':
			case 'preview':
				$this->user_level_required = N_USER_NORIGHTS;
				break;
			case 'content':
				$this->user_level_required = N_USER_EDITOR;
				break;
			default:
				$this->user_level_required = N_USER_ADMIN;
		}
		return parent::checkUserLevel();
	}

	function viewlist() {
		if ($this->flash->exists('notice')) {
			$this->flash->keep('notice');
		}
		$this->redirectTo(array('site_admin'));
	}

	function page($parameter) {
		if (!defined('IN_SURFTOEDIT')) define('IN_SURFTOEDIT', $this->nterchange && $this->edit);
		$this->base_view_dir = ROOT_DIR;
		if (!$parameter) {
			$this->do404();
			return;
		}
		$this->auto_render = false;
		// load the model
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		if (!$model->get($parameter)) { // get the page info
			// if the page doesn't exist, then 404
			$this->do404();
			return;
		}

		if (!$this->nterchange && $model->external_url && preg_match('/^(http[s]?)|(\/)/', $model->external_url)) {
			header('Location:' . $model->external_url);
			return;
		}

		// check if a disclaimer is required
		if (defined('SITE_DISCLAIMER') && constant('SITE_DISCLAIMER') && !$this->nterchange && $disclaimer = &NController::factory('disclaimer')) {
			$disclaimer->checkDisclaimer($parameter);
		}

		// find the action
		$action = $this->getTemplate($model->page_template_id);
		$action = $action?$action:'default';
		// set up caching
		if (!$this->nterchange && defined('PAGE_CACHING') && PAGE_CACHING == true && $model->cache_lifetime != 0) {
			// set the view cache values
			$this->view_cache_name = 'page' . $parameter . (NServer::env('QUERY_STRING')?':' . md5(NServer::env('QUERY_STRING')):'');
			$this->view_caching = true;
			$this->view_cache_lifetime = $model->cache_lifetime;
			$this->view_cache_lifetimes[] = $model->cache_lifetime;
			$this->view_client_cache_lifetime = isset($model->client_cache_lifetime)?$model->client_cache_lifetime:'3600';
			header('Expires:' . gmdate('D, d M Y H:i:s \G\M\T', time() + $this->view_client_cache_lifetime));
			header('Cache-Control:max-age='.$this->view_client_cache_lifetime.', must-revalidate');
		} else {
			header('Cache-Control:max-age=0, must-revalidate');
		}
		// set the page fields
		$this->set($model->toArray());
		// load the page, checking if it's cached if we're not in nterchange
		if ($this->nterchange || $this->getParam('mode') == 'print' || !$this->isCached(array('action'=>$action))) {
			if (defined('PAGE_CACHING') && PAGE_CACHING == false) {
				$this->debug('Cache not created for page for Page ID ' . $model->$pk . ' because PAGE_CACHING is set to false.', N_DEBUGTYPE_CACHE);
			} else if ($model->cache_lifetime == 0) {
				$this->debug('Cache not created for page for Page ID ' . $model->$pk . ' because caching is turned off for that page.', N_DEBUGTYPE_CACHE);
			} else {
				if (!$this->nterchange) $this->debug('Created cached page for Page ID ' . $model->$pk . '.', N_DEBUGTYPE_CACHE);
			}
			$this->page_last_modified = strtotime($model->cms_modified);

			// load up the manual content (site name, breadcrumbs, children, nav, etc.)
			$contents['_SITE_NAME_'] = htmlentities(SITE_NAME);
			$contents['_EXTERNAL_CACHE_'] = defined('EXTERNAL_CACHE') && constant('EXTERNAL_CACHE')?EXTERNAL_CACHE:false;
			$contents['_PAGE_EDIT_'] = '';
			if ($this->checkUserLevel()) {
				$this->page_edit_allowed = true;
				$this->content_edit_allowed = true;
			}
			if ($this->nterchange && $this->edit && SITE_WORKFLOW) {
				// set up the user's rights on the page
				$workflow = &NController::factory('workflow');
				if ($workflow_group_model = &$workflow->getWorkflowGroup($model) && $users = $workflow->getWorkflowUsers($workflow_group_model->{$workflow_group_model->primaryKey()})) {
					$contents['_PAGE_EDIT_'] = '<div id="workflow">This page is owned by the &quot;' .  $workflow_group_model->workflow_title . '&quot; Workflow Group</div>' . "\n";
					$current_user = $this->_auth->currentUserID();
					$edit = false;
					foreach ($users as $user) {
						if ($current_user == $user->user_id) {
							$edit = true;
						}
					}
					$this->content_edit_allowed = $edit;
					$assigns['workflow'] = $workflow_group_model->workflow_title;
					$user_rights = $workflow->getWorkflowUserRights($model);
					$this->content_edit_allowed = $user_rights & WORKFLOW_RIGHT_EDIT?true:false;
				} else {
					switch ($this->_auth->getAuthData('user_level')) {
						case N_USER_NORIGHTS:
							$this->page_edit_allowed = false;
							$this->content_edit_allowed = false;
							break;
						case N_USER_EDITOR:
							$this->page_edit_allowed = false;
							$this->content_edit_allowed = true;
							break;
					}
				}
				unset($workflow);
			}
			if ($this->edit && $this->page_edit_allowed) {
				// $contents['_PAGE_EDIT_'] .= '<div><a href="/nterchange/page/edit/' . $parameter . '?_referer=' . urlencode($_SERVER['REQUEST_URI']) . '" title="Edit Page - &quot;' . $model->title . '&quot;"><img src="/nterchange/images/edit.gif" alt="Edit Page" width="18" height="9" border="0" /></a></div>' . "\n\n";
        $contents['_PAGE_EDIT_'] .= $this->render(array('action'=>'surftoedit', 'return'=>true));
			}
			$contents['HOME_LINK'] = $this->getHref($model->getInfo($model->getRootNode()));
			$contents['HOME_CHILDREN'] = $this->getHomeChildren();
			$contents['BREADCRUMBS'] = $this->getBreadcrumbs();
			$contents['CHILDREN'] = $this->getChildren();
			// get ancestor
			$ancestor = $this->getAncestor();
			if ($ancestor && count($ancestor)) {
				$contents['ancestor'] = $ancestor['filename'];
				$contents['ancestor_id'] = $ancestor[$pk];
			}

			if ($this->nterchange) {
				// include_once 'view/helpers/asset_tag_helper.php';
				// $contents['header']  = AssetTagHelper::stylesheetLinkTagFunc(array('href'=>'surftoedit'), $this);
        $contents['header']  = ''; 
        $contents['header'] .= "\n  <!-- Surf-to-Edit -->\n  "; 
        $contents['header'] .= '<link href="/nterchange/assets/stylesheets/surftoedit.css" rel="stylesheet">';
        $contents['header'] .= "\n  "; 
        $contents['header'] .= '<script src="/nterchange/assets/javascripts/surftoedit.js"></script>';
			}
			if ($this->nterchange) {
				$contents['admin_dir'] = 1;
			}
			if ($this->nterchange && $this->edit) {
				$contents['page_edit'] = 1;
			}
			// set the variables so far
			$this->set($contents);
			// load the content into those vars using custom views
			$this->set($this->getContent());
			// last-modified
			$this->set('last_modified', $this->page_last_modified);
		}
		if (!$this->nterchange && defined('PAGE_CACHING') && PAGE_CACHING == true && $model->cache_lifetime != 0) {
			$this->view_caching = true;
		}
		if (!$this->nterchange && defined('PAGE_CACHING') && PAGE_CACHING == true) {
			foreach ($this->view_cache_lifetimes as $cache_lifetime) {
				if ($this->view_cache_lifetime == -1 || $this->view_cache_lifetime > $cache_lifetime) {
					$this->view_cache_lifetime = $cache_lifetime;
				}
			}
		}
		if (SITE_PRINTABLE && isset($model->printable) && $model->printable && $this->getParam('mode') == 'print') {
			$this->view_caching = false;
			$this->render(array('action'=>'print'));
		} else {
			$this->render(array('action'=>$action));
		}
	}

	function loadSubnav($parameter) {
		$subnav = array();
		$subnav[] = array('title'=>'Edit Page', 'action'=>'edit', 'id'=>$parameter, 'class'=>'');
		$subnav[] = array('title'=>'Content', 'action'=>'content', 'id'=>$parameter, 'class'=>'');
		$subnav[] = array('title'=>'Children', 'action'=>'children', 'id'=>$parameter, 'class'=>'');
		foreach ($subnav as $k=>$nav) {
			if ($nav['action'] == $this->action) {
				$subnav[$k]['class'] = 'current';
			}
		}
		$this->set('subnav', $subnav);
	}

	function surfToEdit($parameter) {
		$this->edit = true;
		$this->nterchange = true;
		$this->page($parameter);
	}

	function preview($parameter) {
		$this->edit = false;
		$this->nterchange = true;
		$this->page($parameter);
	}

	function do404() {
		if ($redirect = &NController::factory('redirect')) {
			$redirect->checkRedirect();
		}
		$model = &$this->getDefaultModel();
		$model->reset();
		header("HTTP/1.1 404 Not Found");
		$this->page(4);
	}

	function getHomeChildren($parent_id = null) {
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		$page_info = $model->getInfo($model->$pk);
		$parent_id = $parent_id?$parent_id:$model->getRootNode();
		$children = $model->getChildren($parent_id, true, true);
		foreach ($children as $key=>$child) {
			$children[$key]['href'] = $this->getHref($child);
		}
		return $children;
	}

	function getBreadcrumbs($no_home=true) {
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		$breadcrumbs = array_reverse($model->getAncestors($model->$pk, false, false));
		if ($no_home) {
			array_shift($breadcrumbs); // shift home out of the array
		}
		foreach ($breadcrumbs as $key=>$breadcrumb) {
			$breadcrumbs[$key]['href'] = $this->getHref($breadcrumb);
		}
		return $breadcrumbs;
	}

	function getChildren() {
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		$page_info = $model->getInfo($model->$pk);
		if ($children = $model->getChildren($model->$pk, !$this->nterchange, !$this->nterchange)) {
			foreach ($children as $key=>$child) {
				$children[$key]['href'] = $this->getHref($child);
			}
		}
		return $children;
	}

	function getAncestor() {
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		$ancestors = $model->getAncestors($model->$pk, false, false);
		// pop off the home page
		array_pop($ancestors);
		$ancestor = count($ancestors)?array_pop($ancestors):$model->toArray();
		return $ancestor;
	}

	function getAncestors(){
        	$model = &$this->getDefaultModel();
        	$pk = $model->primaryKey();
        	$ancestors = $model->getAncestors($model->$pk, false, false);
        	return $ancestors;
	}

	function reorderContent($parameter) {
		$this->auto_render = false;
		$template_container_id = isset($this->params['template_container_id'])?(int) $this->params['template_container_id']:false;
		if (!$template_container_id) return;
		$this->page_title = 'Reorder Page Content';
		$model = &$this->loadModel($this->name);
		$page_content = &NController::factory('page_content');
		$page_content_model = &$page_content->loadModel('page_content');
		$pk = $page_content_model->primaryKey();
		$page_content->set('page_id', $parameter);
		$content_html = '';
		if ($model->get($parameter)) {
			$content = array();
			$page_content_model->reset();
			$page_content_model->getContainerContent($model->{$model->primaryKey()}, $template_container_id);
			$page_content->set('template_container_id', $template_container_id);
			if ($page_content_model->numRows()) {
				$contents = array();
				while ($page_content_model->fetch()) {
					$asset_ctrl = &NController::factory($page_content_model->content_asset);
					if ($asset_ctrl && $asset_model = &$asset_ctrl->loadModel($asset_ctrl->name) && $asset_model->get($page_content_model->content_asset_id)) {
						$content = $asset_model->toArray();
						$content['_asset'] = $asset_ctrl->name;
						$content['_asset_name'] = Inflector::humanize($asset_ctrl->name);
						$content['page_content_id'] = $page_content_model->$pk;
						$contents[] = $content;
					}
					unset($asset_ctrl);
				}
				$this->set('template_container_id', $template_container_id);
				$this->set('contents', $contents);
				$this->set(array('reorder_link'=>count($contents) > 1?true:false));
				$this->set('no_edit', true);
				$content_html .= $this->render(array('action'=>'content_container', 'return'=>true));
			}
		}
		$content_html .= $this->render(array('action'=>'reorder_close', 'return'=>true));
		$this->set('MAIN_CONTENT', $content_html);
		$this->render(array('layout'=>'simple'));
	}

	function menus($parent_id = null) {
		$this->nterchange = (bool) preg_match('|^/' . APP_DIR . '|', $_SERVER['REQUEST_URI']);
		if ($this->nterchange && isset($this->params['edit'])) {
			$this->edit = true;
		}
		if ($this->nterchange) {
			$this->_auth = new NAuth();
		}
		$model = &$this->getDefaultModel();
		$parent_id = $parent_id?(int) $parent_id:$model->getRootNode();
		$check_visible = $check_active = ($this->nterchange?false:true);
		header('Content-type:text/javascript');

		// set up the values for the view layer
		$this->base_view_dir = ROOT_DIR;
		// don't allow caching for "submenus"
		if ($parent_id != $model->getRootNode()) {
			$view_caching = false;
		} else {
			$view_caching = (bool) PAGE_CACHING;
		}
		$this->view_caching = $view_caching;
		$this->view_cache_lifetime = JS_CACHE_LIFETIME;
		if (!$this->nterchange && $this->view_cache_lifetime) {
			header('Expires:' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
			header('Cache-Control:max-age=3600, must-revalidate');
		}

		// SET UP CACHING
		$qualified = $this->getParam('qualify')?true:false;
		if ($this->nterchange && $this->edit) {
			$this->view_cache_name = 'admin_edit_javascript';
		} else if ($this->nterchange) {
			$this->view_cache_name = 'admin_javascript';
		} else if (CURRENT_SITE == 'secure') {
			$this->view_cache_name = 'javascript_secure';
		} else if ($qualified) {
			$this->view_cache_name = 'javascript_qualified';
		} else {
			$this->view_cache_name = 'javascript';
		}
		// instantiate the view now that we have a view_cache_name
		$view = &NView::singleton($this);
		$view_options = array('action'=>'blank');

		// check if it's currently being built, if so, wait so you can use the cached version
		// this protects against multiple people building the js menus at once
		$buildfile = CACHE_DIR . '/ntercache/menubuild';
		// if the menu is being built, then wait quarter second and try again.
		$wait = 0;
		while (1==1) {
			if (!file_exists($buildfile) || $this->isCached($view_options) || time() - filemtime($buildfile) > 8) {
				if ($wait > 0) $this->debug('Client waited for ' . number_format($wait) . ' microseconds for someone else to write ' . $this->view_cache_name, N_DEBUGTYPE_CACHE);
				break;
			}
			$wait += 250000;
			usleep(250000);
		}
		// build the menus
		if (!$this->isCached($view_options)) {
			@touch($buildfile);
			if ($this->view_caching != false) {
				$this->debug('Creating cache for ' . $this->view_cache_name . '.' , N_DEBUGTYPE_CACHE);
			}
			$full_url = $qualified?preg_replace('/\/$/', '', PUBLIC_SITE):'';
			$subdir = $this->getParam('subdir')?$this->getParam('subdir') . '/':'';
			$main_nav = $model->getChildren($parent_id, true, true);
			$i = 0;
			$js = '';
			$html = '';
			$preload = '';
			$img_types = array('gif', 'png', 'jpg');
			$external_cache = defined('EXTERNAL_CACHE')?constant('EXTERNAL_CACHE'):'';
			foreach($main_nav as $nav) {
				$width = false;
				foreach ($img_types as $img_type) {
					if (file_exists(DOCUMENT_ROOT . '/images/nav/' . $subdir . $nav['filename'] . '.' . $img_type)) {
						if (file_exists(DOCUMENT_ROOT . '/images/nav/' . $subdir . $nav['filename'] . '_over.' . $img_type)) {
							if ($preload) $preload .= ', ';
							$preload .= '\'' . $external_cache . '/images/nav/' . $subdir . $nav['filename'] . '_over.' . $img_type . '\'';
						}
						$size = getimagesize(DOCUMENT_ROOT . '/images/nav/' . $subdir . $nav['filename'] . '.' . $img_type);
						if ($size)
						$width = $size[0];
						if ($model->isBranch($nav['id'], $check_active, $check_visible)) {
							$html .= $this->getMenuHTML($nav['id'], $i, $i, $width);
							$js .= $this->getMenuIDs($nav['id'], $i);
						}
						$i++;
					}
				}
			}
			$preload = "preloadImages(" . $preload . ");\n";
			$js = ereg_replace('^, ', '', $js);
			$js = 'var menus = new Array(' . $js . ");\n";
			$html = "document.write('" . $html . "');\n";
			$assigns = array('CONTENT'=>$preload . $js . $html);
			$this->set($assigns);
			print "// non-cached file\n\n";
			@unlink($buildfile);
		} else {
			$this->debug('Served cached script for ' . $this->view_cache_name . '.', N_DEBUGTYPE_CACHE);
			print "// cached file\n\n";
		}
		$this->auto_render = false;
		$this->view_caching = $view_caching;
		$this->render($view_options);
	}

	function getMenuHTML($id, $menuid, $cssid, $width=false, $ancestors = array()) {
		$qualified = $this->getParam('qualify')?true:false;
		$full_url = $qualified?preg_replace('/\/$/', '', PUBLIC_SITE):'';
		$this->view_caching = false;
		$model = &$this->getDefaultModel();
		$check_visible = $check_active = ($this->nterchange?false:true);
		// get children of this page
		$children = $model->getChildren($id, $check_active, $check_visible);
		$html = '';
		$submenus = array();
		$i = 0;
		if ($children) {
			foreach ($children as $child) {
				// set mouseover ids for the template
				$mouseovers = array();
				if (is_array($ancestors)) {
					foreach ($ancestors as $key=>$ancestor) {
						$mouseovers[] = $ancestor['menuid'];
					}
				}
				$mouseovers[] = $menuid;
				$branch = false;
				$submenu_id = false;
				if ($model->isBranch($child['id'], $check_active, $check_visible)) {
					$branch = true;
					// add submenus for recursion
					$submenus[] = array('id'=>$child['id'], 'submenuid'=>$i);
					$submenu_id = $i;
					$mouseovers[] = $menuid . '_' . $i;
				}
				if (!$child['external_url'] || (!preg_match('/^http[s]?:\/\//', $child['external_url']) && !preg_match('/^javascript:/', $child['external_url']))) {
					$href = $full_url . $this->getHref($child);
				} else {
					$href = $this->getHref($child);
				}
				$this->set('full_url', $full_url);
				$this->set(array('menu_id'=>$menuid, 'submenu_id'=>$submenu_id, 'page'=>$child, 'href'=>$href, 'mouseovers'=>$mouseovers, 'branch'=>$branch));
				$html .= $this->render(array('action'=>'menu_item', 'return'=>true));
				$i++;
			}
		}
		$this->set(array('menu_id'=>$menuid, 'width'=>$width, 'js'=>$html));
		$html = $this->render(array('action'=>'menu', 'return'=>true));
		// prep the html for js inclusion
		$html = str_replace(array("'", "\n", "\r"), array("\\'", '\\n', '\\n'), $html);
		if (count($submenus) > 0) {
			$ancestors[] = array('id'=>$id, 'menuid'=>$menuid);
			foreach ($submenus as $submenu) {
				$html .= $this->getMenuHTML($submenu['id'], $menuid . '_' . $submenu['submenuid'], $cssid, $width, $ancestors);
			}
		}
		return $html;
	}

	function getMenuIDs($id, $menuid) {
		$model = &$this->getDefaultModel();
		$check_visible = $check_active = ($this->nterchange?false:true);
		$children = $model->getChildren($id, $check_active, $check_visible);
		$submenus = array();
		$i = 0;
		$js = '';
		$js .= ', \'' . $menuid . '\'';
		foreach ($children as $child) {
			if ($model->isBranch($child['id'], $check_active, $check_visible)) {
				$submenus[] = array('id'=>$child['id'], 'submenuid'=>$i);
			}
			$i++;
		}
		if (count($submenus) > 0) {
			$ancestors[] = array('id'=>$id, 'menuid'=>$menuid);
			foreach ($submenus as $submenu) {
				$js .= $this->getMenuIDs($submenu['id'], $menuid . '_' . $submenu['submenuid'], $ancestors);
			}
		}
		return $js;
	}

	function getContent() {
		// don't cache any content
		$this->view_caching = false;
		$page_model = &$this->getDefaultModel();
		$pk = $page_model->primaryKey();
		$content = array();
		$template_containers = $this->templateContainers($page_model->page_template_id);
		$page_content = &NController::singleton('page_content');
		if (!is_array($template_containers)) return null;
		$page_content->set('page_id', $page_model->$pk);
		foreach ($template_containers as $container) {
			if (empty($container)) continue;
			if (!isset($content[$container['container_var']])) {
				$content[$container['container_var']] = '';
				$content[$container['container_var'] . '_EDIT_START'] = '';
				if ($this->nterchange && $this->edit && $this->content_edit_allowed) {
					$page_content->set('template_container_id', $container[$pk]);
					$page_content->set('template_container_name', $container['container_name']);
					$content[$container['container_var'] . '_EDIT_START'] .= $page_content->render(array('action'=>'asset_add', 'return'=>true));
				}
			}
			$content[$container['container_var']] .= $this->getContainerContent($page_model->id, $container['id']);
		}
		unset($page_content);
		return $content;
	}

	function getContainerContent($page_id, $container_id, $page_content_id=null) {
		$page_model = &$this->getDefaultModel();
		$this->auto_render = false;
		$page_id = (int)$page_id;
		$container_id = (int)$container_id;
		if (!$page_id || !$container_id) return null;
		// instantiate the page content controller
		// TODO: put some methods into the page_content controller to do some of this.
		$page_content = &NController::factory('page_content');
		$page_content_model = &$page_content->getDefaultModel();
		$page_content_pk = $page_content_model->primaryKey();

		$asset_ctrl = &NController::singleton('cms_asset_template');
		if (SITE_WORKFLOW && $this->nterchange) {
			// get the users rights and bit compare them below
			$workflow = &NController::factory('workflow');
			$user_rights = $workflow->getWorkflowUserRights($page_model);
		}
		// load up the content
		$content = '';
		// set the time using a trusted source
		$now = new Date(gmdate('Y-m-d H:i:s'));
		$now->setTZbyID('UTC');
		if ($page_content_model->getContainerContent($page_id, $container_id, $this->nterchange, $page_content_id)) {
			$page_content->set('page_id', $page_id);
			while ($page_content_model->fetch()) {
				$page_content->set('page_content_id', $page_content_model->$page_content_pk);
				$timed_start_obj = $page_content_model->timed_start && $page_content_model->timed_start != '0000-00-00 00:00:00'?new Date($page_content_model->timed_start):false;
				$timed_end_obj = $page_content_model->timed_end && $page_content_model->timed_end != '0000-00-00 00:00:00'?new Date($page_content_model->timed_end):false;
				if ($timed_start_obj) {
					$timed_start_obj->setTZbyID('UTC');
				}
				if ($timed_end_obj) {
					$timed_end_obj->setTZbyID('UTC');
				}
				// set cache lifetimes for the page
				if ($timed_start_obj) {
					$time_diff = $timed_start_obj->getDate(DATE_FORMAT_UNIXTIME) - $now->getDate(DATE_FORMAT_UNIXTIME);
          if ($time_diff > 0) {
            $this->view_cache_lifetimes[] = $time_diff;
          }
				}
				if ($timed_end_obj) {
					$time_diff = $timed_end_obj->getDate(DATE_FORMAT_UNIXTIME) - $now->getDate(DATE_FORMAT_UNIXTIME);
          if ($time_diff > 0) {
            $this->view_cache_lifetimes[] = $time_diff;
          }
				}
				if ($timed_end_obj && $timed_end_obj->before($now)) {
					$timed_end_active = true;
				}
				// if the timed end is in the past then kill it and continue.
				if ($timed_end_obj && $now->after($timed_end_obj)) {
					// remove the content, which also kills the page cache
					$page_content_controller = &NController::factory('page_content');
					$page_content_controller->_auth = &$this->_auth;
					$page_content_controller->removeContent($page_content_model->$page_content_pk, false, true);
					unset($page_content_controller);
					continue;
				} else if ($this->nterchange || !$timed_start_obj || ($timed_start_obj && $timed_start_obj->before($now))) {
					$content_controller = &NController::factory($page_content_model->content_asset);
					if ($content_controller && is_object($content_controller)) {
						$content_model = &$content_controller->getDefaultModel();
						$fields = $content_model->fields();
						$pk = $content_model->primaryKey();
						// if we're on the public site, don't grab workflow or draft inserts
						$conditions = array();
						if ($this->nterchange && in_array('cms_draft', $fields)) {
							$conditions = '(cms_draft = 0 OR (cms_draft=1 AND cms_modified_by_user=' . $this->_auth->currentUserId() . '))';
						} else {
							$content_model->cms_draft = 0;
						}
						$content_model->$pk = $page_content_model->content_asset_id;
						if ($content_model->find(array('conditions'=>$conditions), true)) {
							// last modified
							if (strtotime($content_model->cms_modified) > $this->page_last_modified) {
								$this->page_last_modified = strtotime($content_model->cms_modified);
							}
							$template = $asset_ctrl->getAssetTemplate($page_content_model->content_asset, $page_content_model->page_template_container_id);
							if (SITE_DRAFTS && $this->nterchange) {
								$is_draft = false;
								$user_owned = false;
								$user_id = $this->_auth->currentUserId();
								$draft_model = &NModel::factory('cms_drafts');
								$draft_model->asset = $content_controller->name;
								$draft_model->asset_id = $content_model->$pk;
								if ($draft_model->find(null, true)) {
									$is_draft = true;
									// fill the local model with the draft info
									$current_user_id = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
									if ($current_user_id == $draft_model->cms_modified_by_user) {
										$draft_content = unserialize($draft_model->draft);
										foreach ($draft_content as $field=>$val) {
											$content_model->$field = $val;
										}
										$user_owned = true;
										$draft_msg = 'You have saved';
									} else {
										$user_model = &$this->loadModel('cms_auth');
										$user_model->get($draft_model->cms_modified_by_user);
										$draft_msg = $user_model->real_name . ' has saved';
										unset($user_model);
									}
								}
								unset($draft_model);
							}
							if (SITE_WORKFLOW && $this->nterchange) {
								if ($workflow_group_model = &$workflow->getWorkflowGroup($page_model)) {
									if ($current_workflow = &$workflow->getWorkflow($page_content_model->{$page_content_model->primaryKey()}, $workflow_group_model->{$workflow_group_model->primaryKey()}, $content_controller)) {
										$current_user_id = isset($this->_auth) && is_object($this->_auth)?$this->_auth->currentUserID():0;
										$content_edit_allowed = $this->content_edit_allowed;
										$this->content_edit_allowed = !$current_workflow->submitted && $current_user_id == $current_workflow->cms_modified_by_user?true:false;
										$workflow_draft = unserialize($current_workflow->draft);
										foreach ($workflow_draft as $field=>$val) {
											$content_model->$field = $val;
										}
									}
								}
							}
              $values = $content_model->toArray();
              $values['_EDIT_START_'] = '';
              $values['_EDIT_END_'] = '';
              if ($this->nterchange && $this->edit) { $values['_SURFTOEDIT_'] = true; }
							if ($this->edit) {
								if ($this->content_edit_allowed) {
									// $values['_EDIT_START_'] .= '<div class="pagecontent" id="pagecontent' . $page_content_model->$page_content_pk . '">' . "\n";
									$page_content->set(array('asset'=>$content_controller->name, 'asset_id'=>$content_model->$pk));
									$values['_EDIT_START_'] .= $page_content->render(array('action'=>'asset_edit', 'return'=>true));
								}
								$page_content->set(array('asset'=>$content_controller->name, 'asset_id'=>$content_model->$pk, 'page_content_id'=>$page_content_model->$page_content_pk, 'page_id'=>$page_id));
								$values['_EDIT_START_'] .= '<div class="editable-region">' . "\n";
								if (SITE_WORKFLOW && isset($current_workflow) && $current_workflow) {
									if ($this->content_edit_allowed) {
										$values['_EDIT_START_'] .= '<div class="workflow">The following content is waiting to be submitted to workflow in the <a href="' . urlHelper::urlFor($dashboard = &NController::factory('dashboard'), null) . '">dashboard</a>.</div>' . "\n";
									} else {
										$values['_EDIT_START_'] .= '<div class="workflow">The following content is currently in workflow and cannot be edited.</div>' . "\n";
									}
								}
								$values['_EDIT_END_'] .= "</div>\n";
								if ($this->content_edit_allowed) {
									if (SITE_DRAFTS && $is_draft) {
										$values['_EDIT_START_'] .= '<div class="draft">' . $draft_msg . ' the following content as a draft.</div>' . "\n";
									}
									$values['_EDIT_END_'] .= "</div>\n";
								}
							}
							if ($this->nterchange && (($timed_start_obj && $timed_start_obj->after($now)) || ($timed_end_obj && $timed_end_obj->after($now)))) {
								$format = '%a, %b %e, %Y @ %I:%M:%S %p';
								$values['_EDIT_START_'] .= '<div class="timedcontent">';
								$values['_EDIT_START_'] .= 'The following content is currently' .  ($timed_start_obj && $timed_start_obj->after($now)?' NOT':'') . ' visible (it is now ' . NDate::convertTimeToClient($now, $format) . ')';
								if ($timed_start_obj && $timed_start_obj->after($now)) $values['_EDIT_START_'] .= '<br />It will appear: ' . NDate::convertTimeToClient($timed_start_obj, $format);
								if ($timed_end_obj && $timed_end_obj->after($now)) $values['_EDIT_START_'] .= '<br />It will be removed: ' . NDate::convertTimeToClient($timed_end_obj, $format);
								$values['_EDIT_START_'] .= '</div>';
							}
							if (isset($content_edit_allowed)) {
								$this->content_edit_allowed = $content_edit_allowed;
								unset($content_edit_allowed);
							}

              // Remove extra whitespace/newlines
              $values['_EDIT_START_'] = trim(preg_replace('/\s+/', ' ', $values['_EDIT_START_']));
              $values['_EDIT_END_']   = trim(preg_replace('/\s+/', ' ', $values['_EDIT_END_']));

							$page_content_controller = &NController::factory('page_content');
							$page_content_controller->_auth = &$this->_auth;
							$page_content_controller->set(array(
                'id'             => $page_content_model->id,
                'content_order'  => $page_content_model->content_order,
                'col_xs'         => $page_content_model->col_xs,
                'col_sm'         => $page_content_model->col_sm,
                'col_md'         => $page_content_model->col_md,
                'col_lg'         => $page_content_model->col_lg,
                'row_xs'         => $page_content_model->row_xs,
                'row_sm'         => $page_content_model->row_sm,
                'row_md'         => $page_content_model->row_md,
                'row_lg'         => $page_content_model->row_lg,
                'pull_xs'        => $page_content_model->pull_xs,
                'pull_sm'        => $page_content_model->pull_sm,
                'pull_md'        => $page_content_model->pull_md,
                'pull_lg'        => $page_content_model->pull_lg,
                'gutter_xs'      => $page_content_model->gutter_xs,
                'gutter_sm'      => $page_content_model->gutter_sm,
                'gutter_md'      => $page_content_model->gutter_md,
                'gutter_lg'      => $page_content_model->gutter_lg,
                'asset_id'       => (($content_model->id) ? $content_model->id : '1'),
                'asset_headline' => (($content_model->cms_headline) ? $content_model->cms_headline : 'headline'),
                'asset'          => strtolower(get_class($content_model))
							));
              if (isset($values['_SURFTOEDIT_'])) {
                $page_content_controller->set(array('_SURFTOEDIT_' => true));
              }

              $grid_start         = $page_content_controller->render(array('action'=>'grid_start', 'return'=>true));
              $grid_end           = $page_content_controller->render(array('action'=>'grid_end', 'return'=>true));
              $grid_content_start = $page_content_controller->render(array('action'=>'grid_content_start', 'return'=>true));
              $grid_content_end   = $page_content_controller->render(array('action'=>'grid_content_end', 'return'=>true));
							unset($page_content_controller);

              // Remove extra whitespace/newlines
              $grid_start         = trim(preg_replace('/\s+/', ' ', $grid_start));
              $grid_end           = trim(preg_replace('/\s+/', ' ', $grid_end));
              $grid_content_start = trim(preg_replace('/\s+/', ' ', $grid_content_start));
              $grid_content_end   = trim(preg_replace('/\s+/', ' ', $grid_content_end));

              // Combine edit_start/end with grid_start/end
              $values['_GRID_START_'] = "\n" . $grid_start . $values['_EDIT_START_'] . $grid_content_start. "\n";
              $values['_GRID_END_']   = "\n" . $grid_content_end . $values['_EDIT_END_'] . $grid_end . "\n";

              // Render the content
							$content_controller->set($values);
							$content .= $content_controller->render(array('action'=>$template, 'return'=>true));
						}
						unset($content_model);
						unset($content_controller);
					}
				}
			}
		}

		// free up some memory
		unset($page_content_model);
		unset($page_content);
		// return the content
		return $content;
	}

	function getTemplate($template_id) {
		$template_id = (int) $template_id;
		$model = &$this->getDefaultModel();
		if (!$template_id) {
			return false;
		}
		$tc_model = &NModel::factory('page_template');
		$layout = null;
		if ($tc_model->get($template_id)) {
			$layout = $tc_model->template_filename;
		}
		unset($model);
		return $layout;
	}

	function templateContainers($page_template_id, $id=0) {
		// TODO: put some methods into the page_content controller to do some of this.
		$controller_name = 'page_template_containers';
		$tc = &NController::singleton($controller_name);
		$model = &$tc->getDefaultModel();
		$model->reset();
		$options = array();
		$options['conditions'] = 'page_template_id=' . (int)$page_template_id;
		if ($id) {
			$model->id = (int) $id;
		}
		$containers = array();
		if ($model->find($options)) {
			while ($model->fetch()) {
				$containers[] = $model->toArray();
			}
		}
		unset($model);
		unset($tc);
		return $containers;
	}

	function preGenerateForm() {
		$model = &$this->loadModel($this->name);
		// the parent_id select field
		if ($model->{$model->primaryKey()} != $model->getRootNode()) {
			$model->form_elements['parent_id'] = &$this->getTreeAsSelect('parent_id', 'Parent Page');
		} else {
			$model->form_ignore_fields[] = 'parent_id';
		}
	}

	function postGenerateForm(&$form) {
		// grab the model
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();
		// change the header
		$el = &$form->getElement('__header__');
		if ($model->$pk) {
			$el->setValue($model->title . ' - page' . $model->$pk);
		} else {
			$el->setValue('Create a page');
		}
		// if parent_id has been passed, then use it
		if ($parent_id = (int) $this->getParam('parent_id')) {
			$form->setDefaults(array('parent_id'=>$parent_id));
		}
		// put in some rich stuff for template changing
		$page_template_value = $form->getElementValue('page_template_id');
		$page_template_value = $page_template_value[0];
		$tpl = &$form->getElement('page_template_id');
		$tpl->updateAttributes(array('onchange'=>'if(this.options[this.selectedIndex].value != ' . $page_template_value . '){Element.show(\'mv_content_check\');}else{Element.hide(\'mv_content_check\');}'));
		$mv_content = &NQuickForm::createElement('checkbox', 'mv_content');
		$mv_content_html = &NQuickForm::createElement('html', '<tr id="mv_content_check" style="display:none;"><td>&nbsp;</td><td><div class="highlight">' . $mv_content->toHTML() . ' Attempt to move content?</div></td></tr>' . "\n");
		$form->insertElementBefore($mv_content_html, 'visible');
		// add section headers
		if ($model->{$model->primaryKey()} == $model->getRootNode()) {
			$form->removeElement('parent_id');
		}
		if ($form->elementExists('permissions_id')) {
			$form->insertElementBefore($form->createElement('header', null, 'Public Page Permissions'), 'permissions_id');
		}
		if ($form->elementExists('external_url')) {
			$form->insertElementBefore($form->createElement('header', null, 'External Link'), 'external_url');
		}
		if ($form->elementExists('workflow_group_id')) {
			$form->insertElementBefore($form->createElement('header', null, 'Workflow'), 'workflow_group_id');
		}
		if ($form->elementExists('disclaimer_required')) {
			$form->insertElementBefore($form->createElement('header', null, 'Legal Disclaimer'), 'disclaimer_required');
		}
		if ($form->elementExists('meta_keywords')) {
			$form->insertElementBefore($form->createElement('header', null, 'Meta Information'), 'meta_keywords');
		}
		if ($form->elementExists('notify_date')) {
			$form->insertElementBefore($form->createElement('header', null, 'Update Notification'), 'notify_date');
		}
		parent::postGenerateForm($form);
	}

	function preProcessForm(&$values) {
		if (empty($values['filename'])) {
			include_once 'n_filesystem.php';
			$values['filename'] = NFilesystem::cleanFileName($values['title']);
		}
		parent::preProcessForm($values);
	}

	function postProcessForm(&$values) {
		$model = &$this->getDefaultModel();
		$pk = $model->primaryKey();

		// fix the paths for navigation
		// if (on insert or if changed path on update)
		$this->fixPaths($model->$pk);

		// move all the old content to the current template
		// into matching template containers
		if (isset($values['mv_content'])) {
			$page_content = &NController::singleton('page_content');
			$page_content->changeTemplate($model->$pk, $model->page_template_id);
		}

		// delete general caches
		include_once 'n_cache.php';
		NCache::removeMenu();
		NCache::removeTreeAsSelect();
		NCache::removeJavascript($model->$pk);

		// REMOVE PAGE CACHE
		$this->deletePageCache($model->$pk);

		// REMOVE PARENT PAGE CACHE (for child links, etc);
		if ($this->action == 'create') {
			// load a new one
			$new_model = &NModel::factory($this->name);
			$parent_id = $new_model->getParent($model->$pk);
			unset($new_model);
		} else {
			// user the existing model to find the parent
			$parent_id = $model->getParent($model->$pk);
		}
		$this->deletePageCache($parent_id);

		// remove the site admin cache
		$site_admin = &NController::singleton('site_admin');
		$site_admin->deleteCache();
		unset($site_admin);

		if ($this->action == 'delete') {
			$this->flash->set('notice', 'Your page has been deleted.');
		} else {
			$this->flash->set('notice', 'Your page has been saved.');
		}
		parent::postProcessForm($values);
	}

	function getPageContent($page_id, $container_id=0, $checkactive=true) {
		if (!$page_id) {
			// Pear Error goes here.
		}
		$res = $this->getPages($page_id);
		$page_info = $res->fetchRow();
		$res->free();
		$res = InputOutput::getPageTemplateContainers($page_info['page_template_id']);
		$template_containers = array();
		while ($row = $this->prepareOutputContent($res->fetchRow())) {
			$template_containers[] = array('id'=>$row['id'], 'var'=>$row['container_var']);
		}
		$page_content = array();
		foreach($template_containers as $template_container) {
			if ($container_id == 0 || $container_id == $template_container['id']) {
				$sql = "SELECT * FROM page p, page_content pc WHERE p.id = $page_id AND pc.page_id = p.id";
				$sql .= " AND pc.page_template_container_id = " . $template_container['id'];
				$sql .= " ORDER BY";
				$sql .= " pc.page_template_container_id,";
				$sql .= " content_order, pc.id";
				$res = $this->db->query($sql);
				while ($row = $this->prepareOutputContent($res->fetchRow())) {
					if ($row['timed_end'] != '0000-00-00 00:00:00' && strtotime($row['timed_end']) < time()) {
						$this->deletePageContent($row['id']);
					} else {
						$io = InputOutput::singleton($row['content_object']);
						$cres = $io->getRecords($row['content_object_id'], $checkactive);
						if (!DB::isError($cres)) {
							$crow = $this->prepareOutputContent($cres->fetchRow());
							if ($crow) $page_content[$template_container['var']][] = array('id'=>$crow['id'], 'page_template_container_id'=>$row['page_template_container_id'], 'object'=>$io->getObject(), 'object_name'=>$io->getObjectName(), 'headline'=>$crow['cms_headline'], 'page_content_id'=>$row['id'], 'timed_start'=>$row['timed_start'], 'timed_end'=>$row['timed_end']);
						}
					}
				}
			}
		}

		return $page_content;
	}

	function changeContentOrder($page_id, $content) {
		foreach($content as $key=>$id) {
			$ordernum = $key + 1;
			$sql = 'UPDATE page_content SET content_order=' . $ordernum . ' WHERE page_id=' . $page_id . ' AND id=' . $id;
			$this->db->query($sql);
		}
		$this->deletePageCache($page_id);
		return true;
	}

	function getLink($page_info, $treat_title=false) {
		$model = $this->getDefaultModel();
		$link = '<a href="' . PageController::getHref($page_info) . '"' . PageController::getTarget($page_info) . '>';
		$title = $page_info['title'];
		if (is_callable($treat_title)) {
			$title = call_user_func($treat_title, $title);
		}
		if ($page_info['active'] == 0) {
			$title = '[' . $title . ']';
		} else if ($page_info['visible'] == 0) {
			$title = '(' . $title . ')';
		}
		$link .= $title;
		$link .= '</a>';
		return $link;
	}
	function getHref($page_info, $branch=null) {
		$model = &$this->getDefaultModel();
		if (defined('IN_NTERCHANGE') && IN_NTERCHANGE && defined('IN_SURFTOEDIT') && IN_SURFTOEDIT) {
			$href = '/' . APP_DIR . '/page/surftoedit/' . $page_info[$model->primaryKey()];
		} else if (defined('IN_NTERCHANGE') && IN_NTERCHANGE) {
			$href = '/' . APP_DIR . '/page/preview/' . $page_info[$model->primaryKey()];
		} else {
			if ($page_info['external_url']) {
				$href = $page_info['external_url'];
			} else {
				$href = $page_info['path'];
				if ($branch === null) {
					if ($model->isBranch($page_info[$model->primaryKey()], true, true)) {
						$href .= '/';
					} else {
						$href .= '.' . DEFAULT_PAGE_EXTENSION;
					}
				} else if ($branch == false) {
					$href .= '.' . DEFAULT_PAGE_EXTENSION;
				} else {
					$href .= '/';
				}
				$href = ((defined('SECURE_SITE') && SECURE_SITE != false && $page_info['secure_page'] != 0)?preg_replace('|/$|', '', SECURE_SITE):((CURRENT_SITE == 'secure')?preg_replace('|/$|', '', PUBLIC_SITE):'')) . $href;
			}
		}
		return $href;
	}
	function getTarget($page_info) {
		if ($this->nterchange && $page_info['external_url'] != '' && $page_info['external_url_popout'] != 0) {
			return ' target="_blank"';
		}
		return '';
	}

	function deletePageCache($id) {
		if (!empty($id)) {
			// load the model
			$model = &NModel::singleton($this->name);
			$model->reset();
			if ($model->get($id)) {
				$pk = $model->primaryKey();
				// find the action
				$action = $this->getTemplate($model->page_template_id);
				$action = $action?$action:'default';
				$action = $this->getTemplate($model->page_template_id);
				$action = $action?$action:'default';
				// set up caching values
				$this->base_view_dir = ROOT_DIR;
				$this->view_caching = true;
				$this->view_cache_lifetime = $model->cache_lifetime;
				$this->view_cache_name = 'page' . $id;
				$view = &NView::singleton($this);
				if ($this->isCached($action)) {
					$cleared = $view->clearCache($action);
					$this->debug('Page cache for Page ID ' . $id . ($cleared?' removed':' failed attempted removal') . '.', N_DEBUGTYPE_CACHE);
				} else {
					$this->debug('Page cache for Page ID ' . $id . ' failed attempted removal since cache does not exist.', N_DEBUGTYPE_CACHE);
				}
				// Check the smarty_cache folder for additional caches from query string pages.
				$query_string_caches = CACHE_DIR . '/smarty_cache/page' . $id . '%*';
				if (count(glob($query_string_caches)) != 0) {
					$files = glob($query_string_caches);
					foreach ($files as $file) {
						unlink($file);
					}
					NDebug::debug('Deleted query string cache files for page id ' . $id , N_DEBUGTYPE_INFO);
				}
			}
			unset($model);
			unset($view);
		}
		return;
	}

	function fixPaths($id=0) {
		$model = &NModel::singleton($this->name);
		$model->reset();
		if (!$id) $id = $model->getRootNode();
		$fields = $model->fields();
		if ($model->get($id)) {
			// update the current page
			$model->path = $model->buildPath($id);
			if (in_array('cms_modified_by_user', $fields)) {
				$model->cms_modified_by_user = isset($this->_auth)?(int) $this->_auth->currentUserID():0;
			}
			$model->update();
			// update all the children
			$all_children = $model->getAllChildren($id, false, false);
			foreach ($all_children as $child) {
				$model->reset();
				$model->get($child[$model->primaryKey()]);
				$model->path = $model->buildPath($child['id']);
				if (in_array('cms_modified_by_user', $fields)) {
					$model->cms_modified_by_user = isset($this->_auth)?(int) $this->_auth->currentUserID():0;
				}
				$model->update();
			}
		}
		unset($model);
	}

	function &getTreeAsSelect($name, $label) {
		include_once 'n_cache.php';
		include_once 'n_quickform.php';
		if (!$options = NCache::getTreeAsSelect()) {
			$options = $this->getOptions(false);
			if ($options) {
				NCache::createTreeAsSelect($options);
			}
		}
		return NQuickForm::createElement('select', $name, $label, $options);
	}

	function getOptions($options=false, $id=0, $level=0) {
		$spaces = str_repeat('- ', $level);
		$model = &$this->getDefaultModel();
		$children = $model->getChildren($id, !$this->nterchange, !$this->nterchange);
		if (!$options) $options = array();
		if (is_array($children)) {
			for ($x=0;$x<sizeof($children);$x++) {
				$wrap = array('', '');
				if ($children[$x]['active'] == 0) {
					$wrap = array('[', ']');
				} else if ($children[$x]['visible'] == 0) {
					$wrap = array('(', ')');
				}
				$title = $spaces . sprintf('%s%s%s', $wrap[0], $children[$x]['title'], $wrap[1]);
				$options[$children[$x]['id']] = $title;
				$options = $this->getOptions($options, $children[$x]['id'], $level+1);
			}
			return $options;
		}
	}

	function evalHTML($tpl_output) {
		$pos = 0;
		// Loop through to find the php code in html...
		while (($pos = strpos($tpl_output, '<?php' )) !== false) {
			// Find the end of the php code..
			$pos2 = strpos($tpl_output, '?' . '>', $pos + 5);
			// Eval outputs directly to the buffer. Catch / Clean it
			ob_start();
			eval(substr($tpl_output, $pos + 5, $pos2 - $pos - 5));
			$value = ob_get_contents();
			ob_end_clean();
			// Grab that chunk!
			$tpl_output = substr($tpl_output, 0, $pos) . $value . substr($tpl_output, $pos2 + 2);
		}
		return $tpl_output;
	}

	function render($options=array()) {
		$return = isset($options['return'])?$options['return']:false;
		$options['return'] = true;
		$page = parent::render($options);
		$page = $this->evalHTML($page);
		if ($return) return $page;
		print $page;
	}

	function sitemap($params=array()) {
		$model = &$this->getDefaultModel();
		$page_id = isset($params['page_id'])?$params['page_id']:$model->getRootNode();
		$treat_title = isset($params['treat_title'])?$params['treat_title']:null;
		$title_recurse = isset($params['treat_title_recurse'])?(bool) $params['treat_title']:false;
		if ($this->nterchange) {
			$checkactive = false;
			$checkvisible = false;
		} else {
			$checkactive = true;
			$checkvisible = true;
		}
		$main_nav = $model->getChildren($page_id, $checkactive, $checkvisible);
		$html = '<div id="sitemap">';

		foreach($main_nav as $nav) {
			$html .= "<ul><li>";
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $nav['id']) $html .= '<b>';
			$html .= $this->getLink($nav, $treat_title);
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $nav['id']) $html .= '</b>';

			if ($model->isBranch($nav['id'], $checkactive, $checkactive)) {
				$sub_treat_title = ($title_recurse == true)?$treat_title:false;
				$html .= $this->getChildList($nav['id'], $sub_treat_title);
			}
			$html .= "</li></ul>\n";
		}
		$html .="</div>";
		return $html;
	}

	function getChildList($page_id, $treat_title=false) {
		$model = &$this->getDefaultModel();
		if ($this->nterchange) {
			$checkactive = false;
			$checkvisible = false;
		} else {
			$checkactive = true;
			$checkvisible = true;
		}
		$children = $model->getChildren($page_id, $checkactive, $checkvisible);
		$html = '';
		$submenu = array();
		foreach ($children as $child) {
			$html .= "\t<li>";
			$info = $model->getInfo($child['id']);
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $info['id']) $html .= '<b>';
			$html .= $this->getLink($info, $treat_title);
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $info['id']) $html .= '</b>';
			if ($model->isBranch($child['id'], $checkactive, $checkactive)) {
				$html .= $this->getChildList($child['id'], $treat_title);
			}
			$html .= "</li>\n";
		}
		if ($html) {
			$html = "<ul>\n" . $html . "</ul>\n\n";
		}
		return $html;
	}
}
