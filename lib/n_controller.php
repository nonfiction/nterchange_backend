<?php
/**
 * require object, view, model, app_controller
 */
require_once 'n_object.php';
require_once 'n_view.php';
require_once 'n_model.php';
require_once 'n_flash.php';
require_once 'n_date.php';
require_once 'n_debug.php';

/**
 * Autoload controller helpers
 */
$dh = @opendir(dirname(realpath(__FILE__)) . '/controller');
if ($dh) {
	while (false !== ($file = readdir($dh))) {
		if (!preg_match('/^\./', $file)) {
			require_once 'controller/' . $file;
		}
	}
	closedir($dh);
}

/**
 * NController is extended by AppController
 *
 * AppController extends NController and is in turn extended by all
 * Application Controllers. Anything defined here is inherited into
 * the entire application.
 *
 * This contains the most basic necessities for controllers in any application
 *
 * Sample:
 * $controller = &NController::factory('controller_name');
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Controller
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NController extends Object {
	/**
	 * Container for a NAuth object
	 *
	 * @var object
	 * @access private
	 */
	var $_auth = null;
	/**
	 * Container for an NFlash object
	 *
	 * @var object
	 * @access public
	 */
	var $flash = null;
	/**
	 * Controller name
	 *
	 * Ought to be an_underscored_name
	 *
	 * @var string
	 * @access public
	 */
	var $name = null;
	/**
	 * Array of models used in the controller
	 *
	 * @var array
	 * @access public
	 */
	var $models = array();
	/**
	 * Current action/method being called
	 *
	 * @var string
	 * @access public
	 */
	var $action = null;
	/**
	 * Url currently being called
	 *
	 * @var string
	 * @access public
	 */
	var $url = null;
	/**
	 * $_GET|$_POST variables currently loaded
	 *
	 * @var string
	 * @access public
	 */
	var $params = null;
	/**
	 * Base dir for the controller
	 *
	 * Where the controller is to be found relative to app/controllers/
	 *
	 * @var string
	 * @access public
	 */
	var $base_dir = null;

	// view settings
	/**
	 * Controller title to be displayed in the view
	 *
	 * @var string
	 * @access public
	 */
	var $page_title = null;
	/**
	 * Whether to render immediately or not
	 *
	 * @var boolean
	 * @access public
	 */
	var $auto_render = true;
	/**
	 * Base dir for the controllers views
	 *
	 * Where the views to be found relative to app/views/
	 *
	 * @var string
	 * @access public
	 */
	var $base_view_dir = null;
	/**
	 * Contains all variables to be passed to the view
	 *
	 * @see NController::set(), NController::setAppend(), NController::setPrepend()
	 * @var array
	 * @access private
	 */
	var $_view_assigns = array();
	/**
	 * View caching
	 *
	 * @var boolean
	 * @access public
	 */
	var $view_caching = false;
	/**
	 * View cache lifetime
	 *
	 * @var int
	 * @access public
	 */
	var $view_cache_lifetime = -1; // -1 == forever
	/**
	 * View cache name
	 *
	 * @var string
	 * @access public
	 */
	var $view_cache_name = null;
	/**
	 * List of fields to display in the view
	 *
	 * @var array
	 * @access public
	 */
	var $display_fields = array();

	// notice is passed to the VIEW for layouts
	/**
	 * Special variable ot be sent to view Layouts
	 *
	 * @var string
	 * @access public
	 */
	var $notice = '';

	// filters
	// TODO: implement filters
	var $_before_filters = array();
	var $_after_filters = array();

	function __construct() {
		$this->viewPath = Inflector::underscore($this->name);
		$this->modelClass = $this->name;
		$this->modelKey  = Inflector::underscore($this->modelClass);
		if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			$this->params = array_merge($_GET, $_POST);
			foreach ($this->params as $key=>$param) {
				if (is_string($param)) {
					$this->params[$key] = urldecode($param);
				}
			}
		} else {
			$this->params = $_GET;
		}
		$this->page_title = $this->page_title?$this->page_title:Inflector::humanize($this->name);
		$this->flash = &NFlash::singleton();
		parent::__construct();
	}

	function __destruct() {
		unset($this->_auth);
		foreach ($this->models as $model) {
			unset($model);
		}
	}

	/**
	 * Factory pattern to return the named controller
	 *
	 * @param string $controller - should be an underscored word
	 * @param array $params - paramaters to pass to the controller factory
	 * @return object
	 */
	static function &factory($controller, $params = null) {
		$ret = false;
		$file = NController::getIncludePath($controller);
		if (!$file) {
			NController::debug("Controller file not found for '$controller'", N_DEBUGTYPE_ASSET, PEAR_LOG_ERR);
			return $ret;
		}
		include_once $file;
		$class = NController::getClassName($controller);
		if (class_exists($class)) {
			$ret = new $class($params);
		}
		return $ret;
	}

	/**
	 * Singleton pattern to return the same controller from wherever it is called
	 *
	 * @param string $controller - should be an underscored word
	 * @param array $params - paramaters to pass to the controller factory
	 * @see NController::factory
	 * @return object
	 */
	static function &singleton($controller, $params = null) {
		static $controllers;
		if (!isset($controllers)) $controllers = array();
		$key = md5($controller);
		if (!isset($controllers[$key])) {
			$controllers[$key] = &NController::factory($controller, $params);
		}
		return $controllers[$key];
	}

	/**
	 * Checks to see if a controller exists
	 *
	 * @param string $controller - should be an underscored word
	 * @return string
	 */
	static function exists($controller) {
		if (!$controller) return false;
		$path = NController::getIncludePath($controller);
		if (!$path) return false;
		include_once $path;
		$class = NController::getClassName($controller);
		return class_exists($class);
	}

	/**
	 * Finds the path where the controller lives
	 *
	 * @param string $controller - should be an underscored word
	 * @return string
	 */
	static function getIncludePath($controller) {
		$file = false;
		$path = '%s/app/controllers/%s_controller.php';
		if (file_exists(sprintf($path, ROOT_DIR, $controller))) {
			return sprintf($path, ROOT_DIR, $controller);
		} else if (file_exists(sprintf($path, BASE_DIR, $controller))) {
			return sprintf($path, BASE_DIR, $controller);
		} else if (preg_match('/^' . APP_DIR . '/', $controller)) {
			// TODO:: fix this piece of hackery. We need to load the controllers based on Routes rather than manually checking for 'nterchange'
			if (APP_DIR == $controller) {
				return sprintf($path, BASE_DIR, 'nterchange/' . $controller);
			} else {
				$controller_file = preg_replace('/^' . APP_DIR . '_/', '', $controller);
				if (file_exists(sprintf($path, BASE_DIR, 'nterchange/' . $controller_file))) {
					return sprintf($path, BASE_DIR, 'nterchange/' . $controller_file);
				}
			}
			return false;
		}
		return false;
	}

	/**
	 * Changes the controller name to our standard Controller Class naming convention
	 *
	 * @param string $controller - should be an underscored word
	 * @return string
	 */
	static function getClassName($controller) {
		return Inflector::camelize($controller) . 'Controller';
	}

	/**
	 * Redirects the browser to another action in the same controller
	 *
	 * @param string|array $action - should be an underscored word (same as url) or an array of controller/action
	 * @param unknown_type $parameter - optional parameter which is likely an int
	 * @return null
	 */
	function redirectTo($action, $parameter=null, $additional_params=array()) {
		include_once 'view/helpers/url_helper.php';
		$url_params = array();
		if (is_array($action)) {
			$url_params['controller'] = $action[0];
			$url_params['action'] = isset($action[1])?$action[1]:($parameter?'index':'');
		} else {
			$url_params['action'] = $action;
			// If there's a starting slash - assume it's a direct URL.
			if (eregi('^/', $url_params['action'])) {
				header('Location:' . $url_params['action']);
				exit;
			}
		}
		$url_params['id'] = $parameter;
		$url = urlHelper::urlFor($this, array_merge($url_params, array_merge($_GET, $additional_params)));
		$url = html_entity_decode($url);
		header('Location:' . $url);
		exit;
	}

	function getParam($param) {
		if (isset($this->params[$param])) {
			return $this->params[$param];
		}
		return false;
	}

	function setParam($param, $value) {
		$this->params[$param] = $value;
		return;
	}

	/**
	 * Gets an instance of the view object & checks the cache based on the
	 * options passed.
	 *
	 * @param array $options
	 * @return mixed
	 */
	function isCached($options=array()) {
		// check for a view cache
		$view = &NView::singleton($this);
		return $view->isCached($options);
	}

	/**
	 * Gets an instance of the view object & checks the cache of the
	 * passed layout
	 *
	 * @param array $options
	 * @return mixed
	 */
	function isCachedLayout($layout) {
		// check for a view cache
		$view = &NView::singleton($this);
		return $view->isCachedLayout($layout);
	}

	/**
	 * Empty stub method - really meant to be used in a particular controller.
	 * This is called just before things are actually rendered - so you can set
	 * additional variables and/or pull in additional data that's not normally
	 * in a view without resorting to helper files. Put your own beforeRender
	 * method in a particular controller and do your own magic there.
	 *
	 * @param null
	 * @return null
	 */
	function beforeRender() {
		// Nothing doing here - all the magic happens elsewhere.
	}

	/**
	 * Gets an instance of the view object & prepares it for rendering the output, then
	 * asks the view to actually do the job.
	 *
	 * @param array $options
	 * @return mixed
	 */
	function render($options=array()) {
		$view = &NView::singleton($this);
		$this->beforeRender();
		return $view->render($options);
	}

	/**
	 * Gets an instance of the view object & prepares it for rendering a layout, then
	 * asks the view to actually do the job.
	 *
	 * @param string $layout - the layout file to use
	 * @param string $main_content
	 * @param string $sidebar_content
	 * @param boolean $return - whether to return the render or print it immediately
	 * @return mixed
	 */
	function renderLayout($layout, $main_content, $sidebar_content=null, $return=false) {
		$view = &NView::singleton($this);
		return $view->renderLayout($layout, $main_content, $sidebar_content, $return);
	}

	/**
	 * Unsets a variable or multiple variables to be used in the View layer
	 *
	 * @param mixed $var - can be a string or an array
	 * @param mixed $val - if included, is used as the value of $var
	 * @return null
	 */
	function deset($var) {
		if ($var == '*') {
			$this->_view_assigns = array();
			return;
		}
		if (is_string($var)) {
			if (isset($this->_view_assigns[$var])) unset($this->_view_assigns[$var]);
		} else if (is_array($var)) {
			foreach ($var as $key) {
				if (isset($this->_view_assigns[$key])) unset($this->_view_assigns[$key]);
			}
		}
	}

	/**
	 * Sets a variable or multiple variables to be used in the View layer
	 *
	 * @param mixed $var - can be a string or an array
	 * @param mixed $val - if included, is used as the value of $var
	 * @return null
	 */
	function set($var, $val=null) {
		if (is_string($var) && !is_null($val)) {
			$this->_view_assigns[$var] = $val;
		} else if (is_array($var)) {
			foreach ($var as $key=>$val) {
				$this->_view_assigns[$key] = $val;
			}
		}
	}

	/**
	 * Appends to a previously set variable or multiple variables to be used in the View layer
	 *
	 * @param mixed $var - can be a string or an array
	 * @param mixed $val - if included, is used as the value of $var
	 * @return null
	 */
	function setAppend($var, $val=null) {
		if (is_string($var) && $val) {
			if (!isset($this->_view_assigns[$var])) $this->_view_assigns[$var] = null;
			if (is_array($val)) {
				$this->_view_assigns[$var] = array_merge($this->_view_assigns[$var], $val);
			} else {
				$this->_view_assigns[$var] .= $val;
			}
		} else if (is_array($var)) {
			foreach ($var as $key=>$val) {
				if (!isset($this->_view_assigns[$key])) $this->_view_assigns[$key] = null;
				if (is_array($val)) {
					$this->_view_assigns[$key] = array_merge($this->_view_assigns[$key], $val);
				} else {
					$this->_view_assigns[$key] .= $val;
				}
			}
		}
	}

	/**
	 * Prepends to a previously set variable or multiple variables to be used in the View layer
	 *
	 * @param mixed $var - can be a string or an array
	 * @param mixed $val - if included, is used as the value of $var
	 * @return null
	 */
	function setPrepend($var, $val=null) {
		if (is_string($var) && $val) {
			if (!isset($this->_view_assigns[$var])) $this->_view_assigns[$var] = '';
			$this->_view_assigns[$var] = $val . $this->_view_assigns[$var];
		} else if (is_array($var)) {
			foreach ($var as $key=>$val) {
				if (!isset($this->_view_assigns[$key])) $this->_view_assigns[$key] = '';
				$this->_view_assigns[$key] = $val . $this->_view_assigns[$key];
			}
		}
	}

	// model functions
	/**
	 * Returns a reference to the model with the same name as the controller, if it exists
	 *
	 * @see NController:loadModel();
	 * @return object
	 */
	function &getDefaultModel() {
		$model = &$this->loadModel($this->name);
		return $model;
	}

	/**
	 * Loads references to the model(s) that is/are passed into the
	 * $this->models array
	 *
	 * @see NController:loadModel();
	 * @return array Hash of all loaded models
	 */
	function &loadModels($models) {
		if (is_string($models)) {
			$this->loadModel($models);
		} else if (is_array($models)) {
			foreach ($models as $model) {
				$this->loadModel($model);
			}
		}
		return $this->models;
	}

	/**
	 * Loads a reference to the model that is passed into the $this->models
	 * array and returns it
	 *
	 * @see NModel:factory();
	 * @return object
	 */
	function &loadModel($model) {
		if (!isset($this->models[$model])) {
			$this->models[$model] = &NModel::factory($model);
			if ($this->models[$model] == false) {
				unset($this->models[$model]);
				$ret = false;
				return $ret;
			}
		}
		return $this->models[$model];
	}


	// Form-specific methods - to be optionally overridden in subclasses
	function preGenerateForm() {
	}
	function postGenerateForm(&$form) {
		$model = &$this->loadModel($this->name);
		if (isset($model->bitmask_fields) && is_array($model->bitmask_fields) && count($model->bitmask_fields)) {
			foreach($model->bitmask_fields as $field=>$bitmask_array ) {
				if (isset($model->{$model->primaryKey()})) {
					$form_group = &$form->getElement($field);
					$form_elements = &$form_group->getElements();
					foreach ($form_elements as $key=>$form_element) {
						$bit = $form_element->getName();
						$form_elements[$key]->setChecked($bit & $model->$field?true:false);
					}
				}
			}
		}
	}
	function preProcessForm(&$values) {
		$model = &$this->loadModel($this->name);
		if (isset($model->bitmask_fields) && is_array($model->bitmask_fields) && count($model->bitmask_fields)) {
			foreach($model->bitmask_fields as $field=>$bitmask_array ) {
				if (isset($values[$field]) && is_array($values[$field])) {
					$bitmask_total = 0;
					foreach ($values[$field] as $bit=>$foo) {
						$bitmask_total+=$bit;
					}
					$values[$field] = $bitmask_total;
				} else {
					$values[$field] = 0;
				}
			}
		}
	}
	function postProcessForm(&$values) {
	}

	// Filter functionality
	// TODO: build in filter functionality
	function beforeFilter($call, $params=null) {
		if (is_callable($call)) {
			$this->_before_filters[] = array($call, $params);
			return true;
		}
		return false;
	}

	function prependBeforeFilter($call, $params=null) {
		if (is_callable($call)) {
			array_unshift($this->_before_filters, array($call, $params));
			return true;
		}
		return false;
	}

	function afterFilter($call, $params=null) {
		if (is_callable($call)) {
			$this->_after_filters[] = array($call, $params);
			return true;
		}
		return false;
	}

	function prependAfterFilter($call, $params=null) {
		if (is_callable($call)) {
			array_unshift($this->_after_filters, array($call, $params));
			return true;
		}
		return false;
	}

	// Methods to flatten or push nested objects to a static array
	function collectionToArray($collection){
		$arr = array();
		foreach ($collection as $object) {
			$arr[] = $object->toArray();
		}
		return $arr;
	}

	// Aliased method for collectionToArray
	function flatten($collection){
		return $this->collectionToArray($collection);
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
