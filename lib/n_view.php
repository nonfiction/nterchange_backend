<?php
require_once 'view/helper.php';
require_once 'n_debug.php';
/**
 * NView
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Templating
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class NView extends Smarty {
	// variables inherited from the controller
	var $controller = null;
	var $name = null;
	var $action = null;
	var $flash = null;
	var $page_title = null;
	var $_view_assigns = array();
	var $view_cache_name = null;

	var $helper = null;
	var $url = '';

	function NView(&$controller) {
		if (is_object($controller)) {
			$this->controller = &$controller;
			$this->name = $controller->name;
			$this->action = $this->controller->action;
			$this->flash = &$this->controller->flash;
			$this->page_title = $this->controller->page_title;
			$this->_view_assigns = &$this->controller->_view_assigns;
		} else {
			$this->name = $controller;
		}

		// Smarty setup
		$this->Smarty();
		if (is_object($controller)) {
			$this->template_dir = $controller->base_view_dir . '/app/views';
		}
		if (is_dir(BASE_DIR . '/vendor/SmartyPaginate/plugins')) {
			$this->plugins_dir[] = BASE_DIR . '/vendor/SmartyPaginate/plugins';
		}
		if (defined('SMARTY_CACHE_HANDLER')) {
			$this->cache_handler_func = SMARTY_CACHE_HANDLER . '_cache_handler';
		}
		$this->compile_dir = CACHE_DIR . '/templates_c';
		$this->cache_dir = CACHE_DIR . '/smarty_cache';
		$this->helper = new ViewHelper($this);
		register_shutdown_function(array(&$this, '__destruct'));

		$this->register_outputfilter(array(&$this->helper, 'convertInternalLink'));
		$this->register_function('call', array(&$this, 'call'));
		$this->register_function('assign_encoded', array(&$this, 'assignURLEncoded'));
		$this->register_block('dynamic', array(&$this, 'dynamic'), false);
		$this->register_block('form', array(&$this, 'block_form'), false);
		$this->register_block('table', array(&$this, 'block_table'), false);
		$this->register_function('tableize', array(&$this, 'function_tableize'), false);
		// Shows more debug information is development is the active mode.
		// For more information in your templates add {debug} anywhere.
		if (defined('SMARTY_DEBUG') && SMARTY_DEBUG) {
			$this->debugging = true;
		}
	}

	function &singleton(&$controller) {
		static $instances = array();
		// if $controller is not an object, just use the string
		$name = is_object($controller)?$controller->name:$controller;
		if (!isset($instances[$name])) {
			$instances[$name] = new NView($controller);
		}
		if (is_object($controller)) {
			$instances[$name]->_view_assigns = &$controller->_view_assigns;
			$instances[$name]->caching = $controller->view_caching?2:0;
			$instances[$name]->cache_lifetime = $controller->view_cache_lifetime;
			$instances[$name]->view_cache_name = $controller->view_cache_name;
			$instances[$name]->template_dir = $controller->base_view_dir . '/app/views';
		}
		return $instances[$name];
	}

	function render($options=array(), $added_assigns=array()) {
		if (is_string($options)) {
			$options = array('action'=>$options);
		}
		if (isset($options['status'])) {
			require_once 'HTTP/Header.php';
			$http = new HTTP_Header;
			$http->sendStatusCode($options['status']);
			unset($http);
		}
		if (isset($options['nothing']) && $options['nothing']) {
			return '';
		}
		if (isset($options['text'])) {
			$out = $options['text'];
			if ($options['layout']) {
				$this->renderLayout($options['layout'], $out);
			}
			return $out;
		}
		$return = isset($options['return'])?(bool) $options['return']:false;

		// non-default action might be passed
		$action = isset($options['action'])?$options['action']:$this->action;

		$filename = null;

		// look for specific file
		if (isset($options['file'])) {
			$filename = $options['file'];
		}
		if (!isset($filename) || !is_file($filename)) {
			// get the default filename
			$filename = $this->_getViewFileName(Inflector::underscore($action));
		}
		if (!$filename && !isset($options['layout'])) {
			// TODO: raise an error here - no file exists for the action/file specified
			return;
		}
		$out = '';
		if ($filename) {
			$this->assign($this->_view_assigns);
			if (!$this->isCached($options)) {
				$this->assign('_EXTERNAL_CACHE_', defined('EXTERNAL_CACHE') && constant('EXTERNAL_CACHE')?EXTERNAL_CACHE:false);
				$this->assign($added_assigns);
			}
			$out = $this->fetch($filename, $this->view_cache_name);
		}

		// check for a layout and render if one was passed
		if (isset($options['layout']) && (!$this->controller || (!isset($this->controller->params['bare']) || !$this->controller->params['bare']))) {
			$out = $this->renderLayout($options['layout'], $out, null, $return);
		}

		// return $out or print it
		if ($return) {
			return $out;
		}
		print $out;
	}

	function renderLayout($layout, $main_content, $sidebar_content=null, $return=false) {
		$filename = $this->_getLayoutFileName($layout);
		if (!$this->isCachedLayout($layout)) {
			// assign everything else just in case...
			$this->assign($this->_view_assigns);
			if (!$main_content) {
				$main_content = $this->get_template_vars('MAIN_CONTENT');
			}
			if (!$sidebar_content) {
				$sidebar_content = $this->get_template_vars('SIDEBAR_CONTENT');
			}
			$flash = array();
			if (is_object($this->flash)) {
				foreach ($this->flash->flashes as $key=>$val) {
					$flash[$key] = $val;
				}
			}
			// assign anything passed explicitly
			$this->assign(array('MAIN_CONTENT'=>$main_content, 'SIDEBAR_CONTENT'=>$sidebar_content, '_TITLE_'=>$this->page_title, '_FLASH_'=>$flash));
		}
		$out = $this->fetch($filename, $this->view_cache_name);
		if ($return) return $out;
		print $out;
	}

	function isCached($options=array()) {
		if (is_string($options)) {
			$options = array('action'=>$options);
		}
		// non-default action might be passed
		$action = isset($options['action'])?$options['action']:$this->action;
		// look for specific file
		if (isset($options['file'])) {
			$filename = $options['file'];
		}
		if (!isset($filename) || !is_file($filename)) {
			// get the default filename
			$filename = $this->_getViewFileName($action);
		}
		if (!$filename && !isset($options['layout'])) {
			// TODO: raise an error here - no file exists for the action/file specified
			return false;
		}
		return $this->is_cached($filename, $this->view_cache_name);
	}
	function clearCache($options=array()) {
		if ($this->isCached($options)) {
			if (is_string($options)) {
				$options = array('action'=>$options);
			}
			// non-default action might be passed
			$action = isset($options['action'])?$options['action']:$this->action;
			// look for specific file
			if (isset($options['file'])) {
				$filename = $options['file'];
			}
			if (!isset($filename) || !is_file($filename)) {
				// get the default filename
				$filename = $this->_getViewFileName($action);
			}
			if (!$filename && !isset($options['layout'])) {
				// TODO: raise an error here - no file exists for the action/file specified
				return false;
			}
			return $this->clear_cache($filename, $this->view_cache_name);
		}
		return false;
	}

	function isCachedLayout($layout) {
		$filename = $this->_getLayoutFileName($layout);
		if (!$filename) {
			return false;
		}
		return $this->is_cached($filename, $this->view_cache_name);
	}
	function clearLayoutCache($layout) {
		if ($this->isCachedLayout($layout)) {
			$filename = $this->_getLayoutFileName($layout);
			if (!$filename) {
				return false;
			}
			return $this->clear_cache($filename, $this->view_cache_name);
		}
		return true;
	}

	function call($params, &$view) {
		$controller = isset($params['controller'])?$params['controller']:false;
		$action = isset($params['action'])?$params['action']:false;
		if (!$controller || !$action) {
			return;
		}
		unset($params['controller']);
		unset($params['action']);
		include_once 'controller/inflector.php';
		$ctrl = &NController::factory($controller);
		if ($controller) {
			$method = Inflector::camelize($action);
			if (is_callable(array($ctrl, $method))) {
				// pass all currently variables along
				// $ctrl->set($view->get_template_vars());
				return $ctrl->$method($params, $view);
			}
		}
		return '';
	}

	function assignURLEncoded($params, &$view) {
		$var = isset($params['var'])?$params['var']:false;
		$value = isset($params['value'])?$params['value']:false;
		if (!$var || !$value) {
			return;
		}
		unset($params['var']);
		unset($params['value']);
		$view->assign($var, urlencode($value));
	}

	function dynamic($param, $content, &$view) {
		return $content;
	}

	function block_form($params, $content, &$smarty, &$repeat){
		$form = array_key_exists('for', $params) ? $params['for'] : false;
		if ( !$content ){
			$elements = array();
			if (!array_key_exists('sections', $form)) { return; }
			foreach ($form['sections'][0]['elements'] as $elem) $elements[$elem['name']] = $elem;
			foreach ($form['errors'] as $k=>$err) $elements[$k]['error'] = $err;
			foreach ($elements as $k=>$v) $smarty->assign($k, $v);
		} else {
			return "<form ".$form['attributes'].">".$content.$form['javascript']."</form>";
		}
	}

	function block_table($params, $content, &$smarty, &$repeat){
		if (!isset($this->block_count)) $this->block_count =  0;
		else $this->block_count += 1;

		if ($this->block_count == 0){ $smarty->assign('table_header', true); }
		else { $smarty->assign('table_header', false); }
		$arr = array_key_exists('for', $params) ? $params['for'] : false;
		$class = array_key_exists('class', $params) ? $params['class'] : "";
		$smarty->assign('row', $arr[$this->block_count]);
		$repeat = (array_key_exists($this->block_count, $arr));
		$header = ($this->block_count == 1) ? "<table class='$class'>\n":"";
		$footer = ($repeat) ? "": "</table>";
		return $header.$content.$footer;
	}

	function function_tableize($params, &$view){
		require_once('lib/controller/inflector.php');
		$arr = array_key_exists('collection', $params) ? $params['collection'] : false;
		$trim_arg = array_key_exists('trim', $params) ? $params['trim'] : false;
		$trim_length = ($trim_arg) ? intval($trim_arg) : false;
		$keys = array_keys($arr[0]);
		$html = "<table>\n";

		$html .= "\t<tr>\n";
		foreach($keys as $key){
			$key = (substr($key, 0, 4) == "cms_") ? substr($key, 4) : $key;
			$key = Inflector::humanize($key);
			$html .= "\t\t<th>$key</th>\n";
		}
		$html .= "\t</tr>\n";

		foreach ($arr as $row){
			$html .= "\t<tr>\n";
			foreach($keys as $key){
				if (is_array($row[$key])) {
					$value = print_r($row[$key], true);
				} else {
					$value = strip_tags($row[$key]);
				}
				if ($trim_length && strlen($value) > $trim_length) $value = substr($value, 0, $trim_length)."..";
				$html .= "\t\t<td>$value</td>\n";
			}
			$html .= "\t<tr>\n";
		}
		$html .= "</table>\n";
		return $html;
	}


	function _getViewFileName($action) {
		$filename = null;
		if (file_exists($this->template_dir . '/' . $this->name . '/' . $action . '.html')) {
			$filename = $this->template_dir . '/' . $this->name . '/' . $action . '.html';
		} else if (file_exists(BASE_DIR  . '/app/views/' . $this->name . '/' . $action . '.html')) {
			$filename = BASE_DIR . '/app/views/' . $this->name . '/' . $action . '.html';
		} else if (file_exists(BASE_DIR . '/app/views/templates/' . $action . '.html')) {
			$this->template_dir = BASE_DIR . '/app/views/';
			$filename = $this->template_dir . '/templates/' . $action . '.html';
		} else if (file_exists(BASE_DIR . '/app/views/templates/errors/' . $action . '.html')) {
			$this->template_dir = BASE_DIR . '/app/views/';
			$filename = $this->template_dir . '/templates/errors/' . $action . '.html';
		// Look in the front end if all else fails.
		} else if (file_exists(ASSET_DIR . '/views/' . $this->name . '/' . $action . '.html')) {
			$filename = ASSET_DIR . '/views/' . $this->name . '/' . $action . '.html';
		}
		return $filename;
	}

	function _getLayoutFileName($layout) {
		$filename = null;
		if (file_exists($this->template_dir . '/layouts/' . $layout. '.html')) {
			$filename = $this->template_dir . '/layouts/' . $layout. '.html';
		} else if (file_exists(BASE_DIR . '/app/views/layouts/' . $layout . '.html')) {
			$filename = BASE_DIR . '/app/views/layouts/' . $layout . '.html';
		}
		return $filename;
	}

	function __destruct() {
		unset($this->helper);
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
