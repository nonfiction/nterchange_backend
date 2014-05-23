<?php
require_once 'tag_helper.php';
require_once 'url_helper.php';
/**
 * JavascriptHelper
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	JavaScript Helper
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class JavascriptHelper extends TagHelper {
	var $callbacks = array('uninitialized', 'loading', 'loaded', 'interactive', 'success', 'failure', 'complete');
	var $ajax_options = array('before', 'after', 'condition', 'confirm', 'url', 'asynchronous', 'method', 'insertion', 'position', 'form', 'with', 'update', 'script');
	var $javascript_path = '/javascripts';
	
	function linkToFunction(&$controller, $name, $function, $html_options=array()) {
		$options = array_merge(array('href'=>'#', 'onclick'=>"$function;return false;"), $html_options);
		return TagHelper::contentTag('a', $name, $options);
	}
	
	function linkToFunctionFunc($params, &$view) {
		$controller = &$view->controller;
		$name = isset($params['name'])?$params['name']:'';
		if (isset($params['name'])) unset($params['name']);
		$function = isset($params['function'])?$params['function']:'';
		if (isset($params['function'])) unset($params['function']);
		return JavascriptHelper::linkToFunction($controller, $name, $function, $params);
	}
	
	function linkToRemote(&$controller, $name, $options = array(), $html_options = array()) {
		return JavascriptHelper::linkToFunction($controller, $name, JavascriptHelper::remoteFunction($controller, $options), $html_options);
	}

	function linkToRemoteFunc($params, &$view) {
		$controller = &$view->controller;
		$name = isset($params['name'])?$params['name']:'';
		if (isset($params['name'])) unset($params['name']);
		$options = array();
		$class_props = get_class_vars(__CLASS__);
		foreach ($class_props['callbacks'] as $callback) {
			if (isset($params[$callback])) {
				$options[$callback] = $params[$callback];
				unset($params[$callback]);
			}
		}
		foreach ($class_props['ajax_options'] as $ajax_option) {
			if (isset($params[$ajax_option])) {
				$options[$ajax_option] = $params[$ajax_option];
				unset($params[$ajax_option]);
			}
		}
		
		return JavascriptHelper::linkToRemote($controller, $name, $options, $params);
	}

	function remoteFunction(&$controller, $options) {
		$javascript_options = JavascriptHelper::optionsForAjax($options);
		
		$update = '';
		if (isset($options['update']) && is_string($options['update'])) {
			require_once 'vendor/spyc.php';
			$val = @Spyc::YAMLLoad($options['update']);
			if (!empty($val)) { // it's a YAML array, so load it into options['update']
				$options['update'] = $val;
			}
		}
		if (isset($options['update']) && is_array($options['update'])) {
			$update = array();
			if (isset($options['update']['success'])) {
				$update[] = "success:'{$options['update']['success']}'";
			}
			if (isset($options['update']['failure'])) {
				$update[] = "failure:'{$options['update']['failure']}'";
			}
			$update = implode(',', $update);
		} else if (isset($options['update'])) {
			$update = $options['update'];
		}
		
		$function = isset($options['update'])?"new Ajax.Updater('$update', ":'new Ajax.Request(';
		$function .= "'" . UrlHelper::urlFor($controller, $options['url']) . "'";
		$function .= ', ' . $javascript_options . ')';
		
		$function = (isset($options['before'])?"{$options['before']}; ":'') . $function;
		$function .= (isset($options['after'])?"; {$options['after']};":'');
		$function = isset($options['condition'])?'if(' . $options['condition'] . '){' . $function . '}':$function;
		$function = isset($options['confirm'])?'if(confirm' . $options['condition'] . '){' . $function . '}':$function;
		
		return $function;
	}

	function optionsforJavascript($options) {
		$js_options = '';
		foreach ($options as $k=>$v) {
			if (is_bool($v)) {
				$v = $v?'true':'false';
			}
			$js_options[] = $k . ':' . (string)$v;
		}
		return '{' . implode(', ', $js_options) . '}';
	}

	function optionsForAjax($options) {
		$js_options = JavascriptHelper::buildCallbacks($options);
		$js_options['asynchronous'] = isset($options['type']) && $options['type'] != 'synchronous'?false:true;
		if (isset($options['method'])) $js_options['method'] = JavascriptHelper::methodOptionToS($options['method']);
		if (isset($options['position'])) $js_options['insertion'] = "Insertion.{$options['position']}";
		if (isset($options['script'])) $js_options['evalScripts'] = $options['script']?'true':'false';
		
		if (isset($options['form'])) {
			$js_options['parameters'] = 'Form.serialize(this)';
		} else if (isset($options['with'])) {
			$js_options['parameters'] = $options['with'];
		}

		return JavascriptHelper::optionsForJavascript($js_options);
	}

	function buildCallbacks($options) {
		$callbacks = array();
		$class_props = get_class_vars(__CLASS__);
		$default_callbacks = $class_props['callbacks'];
		foreach ($options as $callback=>$code) {
			if (in_array($callback, $default_callbacks)) {
				$name = 'on' . ucfirst($callback);
				$callbacks[$name] = 'function(request){' . $code . '}';
			}
		}
		return $callbacks;	
	}
	
	function escapeJavascript($js) {
		return preg_replace(array('/\r\n|\n|\r/', '/([\"\'])/'), array('\\n', "\\$1"), $js);
	}
}
/*
<a href="#" onclick="new Ajax.Updater('products', '/admin/add_item', {asynchronous:true, evalScripts:true, insertion:Insertion.Top, onComplete:function(request){item_added()}, onLoading:function(request){item_loading()}, parameters:Form.serialize(this)}); return false;">hi</a>
<form action="/admin/add_item" method="post" onsubmit="new Ajax.Updater('products', '/admin/add_item', {asynchronous:true, evalScripts:true, insertion:Insertion.Top, onComplete:function(request){item_added()}, onLoading:function(request){item_loading()}, parameters:Form.serialize(this)}); return false;">
*/
?>