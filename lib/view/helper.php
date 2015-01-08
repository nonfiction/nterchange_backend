<?php
/**
 * View Helpers
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	View Helpers
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class ViewHelper {
	function ViewHelper(&$view) {
		include_once 'helpers/tag_helper.php';
		include_once 'helpers/asset_tag_helper.php';
		include_once 'helpers/url_helper.php';
		include_once 'helpers/javascript_helper.php';
		include_once 'helpers/date_helper.php';
		include_once 'helpers/filesystem_helper.php';
		include_once 'helpers/search_helper.php';
		include_once 'helpers/action_track_helper.php';
		$asset_tag = new AssetTagHelper;
		$tag = new TagHelper;
		$url = new urlHelper;
		$js = new JavascriptHelper;
		$date = new DateHelper;
		$search = new SearchHelper;
		$filesystem = new FilesystemHelper;
		$action_track = new ActionTrackHelper;
		$view->register_modifier('humanize', array('Inflector', 'humanize'));
		$view->register_modifier('camelize', array('Inflector', 'camelize'));
		$view->register_modifier('image_tag', array(&$asset_tag, 'imageTag'));
		$view->register_function('image_tag', array(&$asset_tag, 'imageTagFunc'));
		$view->register_function('image_size', array(&$asset_tag, 'imageSizeFunc'));
		$view->register_function('image_width', array(&$asset_tag, 'imageWidthFunc'));
		$view->register_function('image_height', array(&$asset_tag, 'imageHeightFunc'));
		$view->register_function('css_img_size', array(&$asset_tag, 'cssImageSizeFunc'));
		$view->register_function('stylesheet_link_tag', array(&$asset_tag, 'stylesheetLinkTagFunc'));
		$view->register_function('javascript_include_tag', array(&$asset_tag, 'javascriptIncludeTagFunc'));
		$view->register_function('persistent_url', array(&$asset_tag, 'persistentUrl'));
		$view->register_function('link_to', array(&$url, 'linkToFunc'));
		$view->register_function('url_for', array(&$url, 'urlForFunc'));
		$view->register_function('link_to_remote', array(&$js, 'linkToRemoteFunc'));
		$view->register_modifier('date_format_local', array(&$date, 'dateFormatLocal'));
		$view->register_modifier('close_tags', array(&$tag, 'closeTags'));
		$view->register_modifier('filesize_format', array(&$filesystem, 'filesizeFormat'));
		$view->register_modifier('download_time', array(&$filesystem, 'downloadTime'));
		$view->register_function('download_icon', array(&$filesystem, 'downloadIcon'));
		$view->register_function('search_field_list_select', array(&$search, 'searchFieldListSelect'));
		$view->register_function('asset_edit_status', array(&$action_track, 'assetEditStatus'));
		// now do the auto-load thing
		$this->_autoLoadHelpers($view);
	}

	function convertInternalLink($tpl_output, &$smarty) {
		return urlHelper::convertInternalLink($tpl_output, $smarty);
	}

	function _autoLoadHelpers(&$view) {
		$controller = &$view->controller;
		$helper_class = Inflector::camelize($controller->name) . 'Helper';
		if ($path = $this->_getIncludePath($controller->name)) {
			include_once $path;
			$controller->debug('Loaded helper file at ' . $path . " for $controller->name.", N_DEBUGTYPE_ASSET);
			if (!class_exists($helper_class)) {
				$controller->debug('Loaded helper file but could not find  class for "' . $controller->name . '" controller', N_DEBUGTYPE_ASSET, PEAR_LOG_NOTICE);
				return false;
			}
			$helper = new $helper_class;
			$this->_loadHelperPlugins($view, $helper);
		}
	}

	/**
	 * Finds the path where the controller lives
	 *
	 * @param string $controller - should be an underscored word
	 * @return string
	 */
	function _getIncludePath($controller) {
		$file = false;
		$path = '%s/app/helpers/%s_helper.php';
		if (file_exists(sprintf($path, ROOT_DIR, $controller))) {
			return sprintf($path, ROOT_DIR, $controller);
		} else if (file_exists(sprintf($path, BASE_DIR, $controller))) {
			return sprintf($path, BASE_DIR, $controller);
		} else if (preg_match('/^' . APP_DIR . '/', $controller)) {
			if (APP_DIR == $controller) {
				return sprintf($path, BASE_DIR, 'nterchange/' . $controller);
			} else {
				$helper_file = preg_replace('/^' . APP_DIR . '_/', '', $controller);
				if (file_exists(sprintf($path, BASE_DIR, 'nterchange/' . $helper_file))) {
					return sprintf($path, BASE_DIR, 'nterchange/' . $helper_file);
				}
			}
		}
		$this->debug('Could not find helper file for "' . $controller . '" controller', N_DEBUGTYPE_ASSET, PEAR_LOG_NOTICE);
		return false;
	}

	function _loadHelperPlugins(&$view, &$helper) {
		$plugin_types = array('function', 'modifier', 'block', 'compiler', 'prefilter', 'postfilter', 'outputfilter', 'resource', 'insert');
		$methods = get_class_methods(get_class($helper));
		foreach ($methods as $method) {
			$matches = array();
			preg_match('/^([^_]+)/', $method, $matches);
			$plugin_type = isset($matches[1]) && $matches[1]?strtolower($matches[1]):false;
			if (!$plugin_type) { // this isn't a plugin function
				continue;
			}
			if (in_array($plugin_type, $plugin_types)) {
				$load_method = '_load' . ucwords($plugin_type);
				$this->$load_method($view, $helper, $method);
			}
		}
	}

	function _createPluginName($method, $plugin_type) {
		return preg_replace('/^' . $plugin_type . '_/', '', $method);
	}

	function _isPluginCacheable(&$helper, $method) {
		return isset($helper->cacheable) && in_array($method, $helper->cacheable)?$helper->cacheable[$method]:null;
	}

	function _loadFunction(&$view, &$helper, $method) {
		$view->register_function($this->_createPluginName($method, 'function'), array(&$helper, $method), $this->_isPluginCacheable($helper, $method));
	}
	function _loadModifier(&$view, &$helper, $method) {
		$view->register_modifier($this->_createPluginName($method, 'modifier'), array(&$helper, $method));
	}
	function _loadBlock(&$view, &$helper, $method) {
		$view->register_block($this->_createPluginName($method, 'block'), array(&$helper, $method), $this->_isPluginCacheable($helper, $method));
	}
	function _loadCompiler(&$view, &$helper, $method) {
		$view->register_compiler_function($this->_createPluginName($method, 'compiler'), array(&$helper, $method), $this->_isPluginCacheable($helper, $method));
	}
	function _loadPrefilter(&$view, &$helper, $method) {
		$view->register_prefilter(array(&$helper, $method));
	}
	function _loadPostfilter(&$view, &$helper, $method) {
		$view->register_postfilter(array(&$helper, $method));
	}
	function _loadOutputfilter(&$view, &$helper, $method) {
		$view->register_outputfilter(array(&$helper, $method));
	}

	// utility functions
	function debug($message, $debug_type = N_DEBUGTYPE_INFO, $log_level = PEAR_LOG_DEBUG, $ident=false) {
		if (!$ident) {
			$ident = (isset($this) && is_a($this, __CLASS__))?get_class($this):__CLASS__;
		}
		NDebug::debug($message, $debug_type, $log_level, $ident);
	}
}
?>
