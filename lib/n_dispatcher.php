<?php
require_once 'n_object.php';
require_once 'n_controller.php';
require_once 'controller/inflector.php';
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
 * @category   Dispatch
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NDispatcher extends Object {
	var $url = '';
	var $params = array();
	var $app_dir = false;
	var $controller = null;
	var $action     = null;
	var $parameter  = null;

	function __construct($url = '') {
		if (defined('GZIP_COMPRESSION') && GZIP_COMPRESSION) ob_start("ob_gzhandler");
		$this->_parseURL($url);
		$this->setParams($url);
		parent::__construct($url);
	}

	/**
	  * Set controller, action, [parameter, app_dir] for future calls to dispatch().
	  * @see    dispatch
	  * @access public
	  * @return null
	  */
	function setParams(){
		// since we got this far, we need to rewrite $_SERVER['PHP_SELF'] to a pared down $_SERVER['REQUEST_URI']
		$query_string = array_key_exists('QUERY_STRING', $_SERVER) ? $_SERVER['QUERY_STRING'] : '';
		$_SERVER['PHP_SELF'] = str_replace('?' . $query_string, '', $_SERVER['REQUEST_URI']);
		NDebug::debug('' . $_SERVER['REMOTE_ADDR'] . ' requested ' . $_SERVER['PHP_SELF'] , N_DEBUGTYPE_PAGE);

		// normalize the url
		$url = $this->url;
		$url = preg_replace("|index\..*$|i", "", $url);
		$url = preg_replace("|\." . DEFAULT_PAGE_EXTENSION . "$|i", "", $url);
		$url = preg_replace("|/$|", "", $url);
		$url = preg_replace('|^/|', '', $url);
		// explode into an array
		$parts = explode('/', $url);
		// check if it's an nterchange specific controller
		if (isset($parts[0]) && $parts[0] == APP_DIR) {
			$this->app_dir = true;
			$app = array_shift($parts);
			if (empty($parts)) {
				$loc = '/' . APP_DIR . '/dashboard';
				$qs = '';
				foreach ($_GET as $k=>$v) {
					$qs .= ($qs?'&':'?') . $k . '=' . urlencode($v);
				}
				$loc .= $qs;
				header('Location:' . $loc);
				exit;
			}
		}
		// set the controller, method and parameter and invoke
		$this->controller = isset($parts[0])?$parts[0]:null;
		$this->action = isset($parts[1])?$parts[1]:null;
		$this->parameter = isset($parts[2])?$parts[2]:null;
	}

	/**
	  * Instantiates and invokes the controller if it's available.
	  * @see	_invoke
	  * @access	public
	  * @return	null
	  */
	function dispatch() {
		$this->_invoke($this->controller, $this->action, $this->parameter);
	}

	/**
	  * Instantiates and invokes the controller if it's available.
	  *
	  * @access private
	  * @param string $controller
	  * @param string $action				the action to be performed
	  * @param string $parameter
	  * @return null
	  *
	  */
	function _invoke($controller, $action, $parameter=null) {
		if (!$this->app_dir) {
			$controller = 'page';
		}
		if (!NController::exists($controller)) {
			$this->error($controller, $action);
		}
		$ctrl = &NController::factory($controller);
		if (!$action && method_exists($ctrl, 'index')) {
			$action = 'index';
		}
		if (!$this->app_dir && !in_array($action, $ctrl->public_actions)) {
			$action = 'index';
		}
		$method = Inflector::camelize($action);
		$ctrl->action = $action;
		if ($ctrl->login_required === true || (is_array($ctrl->login_required) && (in_array($action, $ctrl->login_required) || in_array($method, $ctrl->login_required)))) {
			include_once 'n_auth.php';
			$ctrl->_auth = new NAuth();
		}
		if (!$ctrl->checkUserLevel()) {
			header('Location:/' . APP_DIR . '/');
			exit;
		}

		// do the method
		if (!$this->app_dir && $controller == 'page') {
			$model = &$ctrl->getDefaultModel();
			// /_page8 redirection support (BC fix)
			if (preg_match('|^/_page(\d+)|', $this->url, $matches)) {
				$parameter = $matches[1];
				if ($page_info = $model->getInfo($parameter)) {
					header('Location:' . $ctrl->getHref($page_info) . ($this->params?'?' . $this->paramsToString():''));
					exit;
				}
			}
			if ($action != 'menus') {
				$parameter = $ctrl->models['page']->URLToID($this->url);
			}
		}
		if (method_exists($ctrl, $method)) {
			$ctrl->$method($parameter);
			if ($ctrl->auto_render) {
				$ctrl->render();
			}
		} else {
			$this->error($ctrl, $method);
		}
		unset($ctrl);
	}

	function paramsToString() {
		$str = '';
		foreach ($this->params as $k=>$v) {
			$str .= ($str?'&':'') . "$k=$v";
		}
		return $str;
	}

	/**
	  *
	  * @param string	$url
	  * @access	private
	  * @return	null
	  */
	function _parseURL($url='') {
		if ($url == '/' || preg_replace('/\?.*/', '', $url) == '/index.php') {
			$url = isset($_GET['url'])?$_GET['url']:$url;
			if (count($_GET > 1)) {
				$get = '';
				foreach ($_GET as $key=>$val) {
					if ($key != 'url')
						$get .= ($get!=''?'&':'') . "$key=$val";
				}
				$url .= "?$get";
			}
		} else {
			$url = $this->_normalizeURL($url);
		}
		require_once 'Net/URL.php';
		$ourl = new Net_URL($url);
		$this->url = $ourl->path;
		$this->params = $ourl->querystring;
		if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			foreach ($_POST as $key=>$val) {
				$this->params[$key] = $val;
			}
		}
		unset($ourl);
	}

	function _normalizeURL($url) {
		include_once 'n_server.php';
		preg_match('|([^\?$]+)[\?$]?([^$]*)|', $url, $matches);
		if (!empty($matches[2])) {
			$get = '';
			foreach ($_GET as $key=>$val) {
				$get .= ($get?'&':'') . $key . '=' . (is_string($val)?urlencode($val):$val);
			}
			$url = $matches[1] . ($get?'?' . $get:'');
		}
		return $url;
	}

	function error(&$controller, $action=null) {
		if (is_string($controller)) {
			$values = array('_NOTICE_'=>'<h1>ERROR</h1>The ' . (is_string($controller)?$controller:$controller->name) . ' controller does not exist.');
		} else {
			$values = array('_NOTICE_'=>'<h1>ERROR</h1>The ' . $controller->name . ' controller does not contain the ' . $action . ' action.');
		}
		$view = &NView::singleton($controller);
		$view->assign($values);
		$view->render(array('layout'=>'plain'));
		exit;
	}

	/**
		* Prints out the error 404 page
		*
		* @access public
		* @param	string	$url	the url of the page that caused the 404 error
		* @return null
		*/
	function error404() {
		print <<<EOF
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL {$_SERVER['REQUEST_URI']} was not found on this server.</p>
<hr />
{$_SERVER['SERVER_SIGNATURE']}
</body></html>
EOF;
	}
}
?>
