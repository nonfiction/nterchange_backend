<?php
require_once 'tag_helper.php';
/**
 * URL Helper
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	URL Helper
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class urlHelper extends TagHelper {
	function urlFor(&$controller, $params) {
		if (is_a($controller, 'NView')) {
			$controller = &$controller->controller;
		}
		if (is_string($params)) {
			$href = $params;
			// load controller, action & id parameters
			if (preg_match('|(?:(controller):([^;]+);)?(?:(action):([^;]+);)?(?:(id):([^;]+);)?|', $href, $matches)) {
				$params = array();
				for ($i=1;$i<count($matches);$i+=2) {
					if (empty($matches[$i])) continue;
					$params[$matches[$i]] = $matches[$i+1];
				}
				if (!count($params)) {
					return $href;
				}
				// catch any other parameters and add them in
				$nomatch = array('controller', 'action', 'id');
				preg_match_all('|([^:]+):([^;]+);|', $href, $matches);
				for ($i=0;$i<count($matches[0]);$i++) {
					if (!in_array($matches[1][$i], $nomatch)) {
						$params[$matches[1][$i]] = $matches[2][$i];
					}
				}
			} else {
				// it's not parseable into an array, so assume it's a fully formed href.
				return $href;
			}
		}
		$href = '';
		$href .= !empty($controller->base_dir)?'/' . $controller->base_dir:'';
		if (isset($params['controller'])) {
			$href .= '/' . $params['controller'];
			unset($params['controller']);
		} else if (is_object($controller)) {
			$href .= '/' . $controller->name;
		}
		if (isset($params['action'])) {
			$href .= '/' . $params['action'];
			if (isset($params['id'])) {
				$href .= '/' . $params['id'];
				unset($params['id']);
			}
			unset($params['action']);
		}
		$referer = false;
		if (isset($params['referer']) && $params['referer']) {
			$referer = urlencode($_SERVER['REQUEST_URI']);
			unset($params['referer']);
		}
		if (!empty($params)) {
			foreach ($params as $key=>$val) {
				if (!$val) continue;
				$href .= (preg_match('|\?|', $href)?'&amp;':'?') . $key . '=' . (is_string($val)?urlencode($val):$val);
			}
		}
		if ($referer) {
			$href .= (preg_match('|\?|', $href)?'&amp;':'?') . '_referer=' . $referer;
		}
		return $href;
	}
	
	function urlForFunc($params, &$view) {
		$url = $this->urlFor($view->controller, $params);
		return $url;
	}
	
	function linkTo(&$controller, $text, $href, $html_options = null) {
		$href = urlHelper::urlFor($controller, $href);
		if (!$href) return $text?$text:'';
		if (isset($html_options['confirm'])) {
			$confirm = $html_options['confirm'];
			$confirm = htmlspecialchars($confirm, ENT_NOQUOTES);
			$confirm = str_replace("'", "\'", $confirm);
			$confirm = str_replace('"', '&quot;', $confirm);
			$html_options['onclick'] = "return confirm('{$confirm}');";
			unset($html_options['confirm']);
		}
		if (isset($html_options['referer']) && $html_options['referer']) {
			$href .= (preg_match('|\?|', $href)?'&amp;':'?') . '_referer=' . urlencode($_SERVER['REQUEST_URI']);
		}
		$html_options['href'] = $href;
		return TagHelper::contentTag('a', $text, $html_options);
	}
	
	function linkToFunc($params, &$view) {
		if (!isset($params['href']) || !$params['href']) return isset($params['text'])?$params['text']:'';
		$href = $params['href'];
		unset($params['href']);
		$text = $params['text'];
		unset($params['text']);
		return $this->linkTo($view->controller, $text, $href, $params);
	}

	function convertInternalLink($tpl_output, &$view) {
		if ($view->controller->name != 'page') {
			return $tpl_output;
		}
		$controller = &$view->controller;
		// fix all links
		preg_match_all("/(href|action)=(\\\\)?[\"\']([\/]?_page([^\"\'\?\#]+)([\"\'\?])?)/i", $tpl_output, $matches);
		$page_model = &$controller->getDefaultModel();
		$urls = array();
		for ($x=0;$x<count($matches[4]);$x++) {
			$page_id = (int) $matches[4][$x];
			if ($controller->nterchange == true) {
				$path = '/' . ADMIN_SITE . $controller->name . '/' . ($controller->edit == false?'preview/':'surftoedit/') . $page_id;
				if ($matches[5][$x] == '?') $path .= '?';
			} else {
				$path = $page_model->IdToURL($page_id, false, false);
				if ($matches[5][$x] == '?') $path .= '?';
			}
			if ($matches[5][$x] == '"') $path .= '"';
			else if ($matches[5][$x] == '\'') $path .= '\'';
			if ($path) $urls[] = $path;
		}
		for ($x=0;$x<count($matches[3]);$x++) {
			$tpl_output = str_replace($matches[3][$x], $urls[$x], $tpl_output);
		}
		// fix PHP-based redirects
		preg_match_all("/Location\:([\/]?_page(\d+)(\?)?)/i", $tpl_output, $matches);
		$urls = array();
		for ($x=0;$x<count($matches[2]);$x++) {
			$page_id = $matches[2][$x];
			if ($controller->nterchange == true) {
				$path = '/' . ADMIN_SITE . $controller->name . '/' . ($controller->edit == false?'preview/':'surftoedit/') . $page_id;
				if ($matches[3][$x] == '?') $path .= '&';
			} else {
				$path = $page_model->IdToURL($page_id, false, false);
				if ($matches[3][$x] == '?') $path .= '?';
			}
			if ($path) $urls[] = $path;
		}
		for ($x=0;$x<count($matches[1]);$x++) {
			$tpl_output = str_replace($matches[1][$x], $urls[$x], $tpl_output);
		}
		return $tpl_output;
	}
}
?>