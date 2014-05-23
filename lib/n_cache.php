<?php
/**
 * require n_log.php and PEAR:Cache_Lite
 */
require_once 'n_debug.php';
require_once 'Cache/Lite.php';

/**
 * NCache is a cache controller for specific application caching needs
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Caching
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NCache {
	function createMenu($content) {
		$cache = new Cache_Lite(array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>3600*24*7));
		return $cache->save(serialize($content), 'menunodes', 'default');
	}
	function getMenu() {
		$cache = new Cache_Lite(array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>3600*24*7));
		$nodes = $cache->get('menunodes', 'default');
		if (!$nodes) return;
		return unserialize($nodes);
	}
	function removeMenu() {
		$cache = new Cache_Lite(array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>3600*24*7));
		return $cache->remove('menunodes', 'default');
	}

	function createTreeAsSelect($content) {
		$cache = new Cache_Lite(array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>3600*24*7));
		return $cache->save(serialize($content), 'treeasselect', 'default');
	}
	function getTreeAsSelect() {
		$cache = new Cache_Lite(array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>3600*24*7));
		$nodes = $cache->get('treeasselect', 'default');
		if (!$nodes) return;
		return unserialize($nodes);
	}
	function removeTreeAsSelect() {
		$cache = new Cache_Lite(array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>3600*24*7));
		return $cache->remove('treeasselect', 'default');
	}

	function removeJavascript($id=0) {
		$page = &NController::singleton('page');
		$page->base_view_dir = ROOT_DIR;
		$page->view_caching = (bool) PAGE_CACHING;
		$page->view_cache_lifetime = JS_CACHE_LIFETIME;
		$view_options = array('action'=>'blank');
		// REMOVE JAVASCRIPT CACHES
		$javascript_caches = array('javascript', 'javascript_secure', 'javascript_qualified', 'admin_javascript', 'admin_edit_javascript');
		foreach ($javascript_caches as $javascript_cache) {
			$page->view_cache_name = $javascript_cache;
			$view = &NView::singleton($page);
			$title = ucfirst(str_replace('_', ' ', $javascript_cache));
			if ($view->isCached($view_options)) {
				$cache_cleared = $view->clearCache($view_options);
				if ($cache_cleared) {
					NDebug::debug($title . ' cache removed due to page edit on Page ID ' . $id . '.', N_DEBUGTYPE_CACHE);
				} else {
					NDebug::debug($title . ' cache failed attempted removal due to page edit on Page ID ' . $id . '.', N_DEBUGTYPE_CACHE);
				}
			} else {
				NDebug::debug($title . ' cache failed attempted removal due to page edit on Page ID ' . $id . ' but cache does not exist.', N_DEBUGTYPE_CACHE);
			}
		}
		unset($view);
	}
}
?>
