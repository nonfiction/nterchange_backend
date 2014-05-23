<?php
include_once 'content_controller.php';
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
 * @category   Asset Administration
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class AssetController extends ContentController {
	var $paging = 25;
	var $search_field = 'cms_headline';

	function __construct() {
		parent::__construct();
	}

	function index($parameter) {
		$this->redirectTo('viewlist', $parameter);
	}

	private function get_search_field(&$model, &$options){
		// Search $model->search_field to limit shown assets.

		$search = isset($_GET['search']) ? $_GET['search'] : false;
		$options['is_search'] = $search ? true : false;

		if (!$options['is_search']) return;

		$search_field = isset($_GET['search_field']) ? $_GET['search_field'] : null;
		if (!$search_field){
			$search_field = isset($model->search_field) ? $model->search_field : $this->search_field;
		}

		if ($options['is_search'] && $search_field){
			$this->set('search_field', Inflector::humanize($search_field));
			$options['conditions'] = "$search_field LIKE '%$search%'";
		}
	}

	function viewlist($parameter=null, $layout=true, $model=false) {
		$options = array();
		$assigns = array();
		$model = $model ? $model : $this->getDefaultModel();
		$this->page_title = Inflector::humanize($this->name);
		$this->auto_render = false;
		$this->base_dir = APP_DIR;
		$assigns['TITLE'] = Inflector::humanize($this->name);

		if ($model){
			$this->get_search_field($model, $options);
			$this->get_viewlist_options($model, $options);
			$this->get_sort_options($model, $options);
			$this->set_pagination($model);

			$model->find($options);

			$page_content = &NController::singleton('page_content');
			$page_content_model = &$page_content->getDefaultModel();

			$pk = $model->primaryKey();
			$models = array();
			$headline = $model->getHeadline()?$model->getHeadline():'cms_headline';
			$i = 0;
			while ($model->fetch()) {
				$arr = $model->toArray();
				$arr['_headline'] = isset($arr['cms_headline']) && $arr['cms_headline']?$arr['cms_headline']:$model->makeHeadline();
				$arr['_remove_delete'] = $page_content_model->isWorkflowContent($this->name, $arr[$pk])?1:0;
				// Remove delete for models that have specified this.
				$arr['_remove_delete'] = (isset($model->remove_delete) && ($model->remove_delete == true))?1:0;
				$models[] = $arr;
				unset($arr);
			}
			// Override standard paging limit if chosen in the model file.
			$paging = isset($model->paging)?$model->paging:$this->paging;
			// If paging is not disabled in the model AND the records are > than the paging size AND not searching.
			if (($paging > 0) && count($models) > $paging && !$options['is_search']) {
				SmartyPaginate::connect($this->name);
				SmartyPaginate::setLimit($paging, $this->name);
				SmartyPaginate::setTotal(count($models), $this->name);
				$view = &NView::singleton($this);
				SmartyPaginate::assign($view, 'paginate', $this->name);
				// TODO: Could be more efficient and only get records it needs to.
				$models = array_slice($models, SmartyPaginate::getCurrentIndex($this->name), SmartyPaginate::getLimit($this->name));
				$this->set('paging', true);
			}
			$this->set(array('rows'=>$models, 'asset'=>$this->name, 'asset_name'=>$this->page_title));
			unset($models);
		}
		$this->render(array('layout'=>'default'));
	}

	private function get_viewlist_options(&$model, &$options){
		// Can set options in the model about items displayed in the viewlist.
		// Only show items that meet a certain criteria - not everything in the list.
		// For example: $this->viewlist_options = array('conditions'=>"cms_modified_by_user = '4'");
		if (isset($model->viewlist_options)) {
			foreach ($model->viewlist_options as $key => $val) {
				if (isset($options[$key])) {
					$options[$key] .= ' AND ' . $val;
				} else {
					$options[$key] = "$val";
				}
			}
		}

		if (isset($model->viewlist_fields)) {
			$this->set('viewlist_fields', $model->viewlist_fields);
		}

	}

	private function get_sort_options(&$model, &$options){
		$sort_by = isset($_GET['sort'])?$_GET['sort']:null;
		if($sort_by) {
			$sort_by_array = explode('_', $sort_by);
			$sort_order = $sort_by_array[count($sort_by_array)-1];
			$sort_array = array();
			if(strtolower($sort_order) == 'asc') {
				$sort_field = str_replace('_asc', '', $sort_by);
				$sort_array['field'] = $sort_field;
				$sort_array['arrow_asc'] = true;
				$sort_array['link'] = $sort_field.'_desc';
				$options['order_by'] = $sort_field. ' ASC';
			} else {
				$sort_field = str_replace('_desc', '', $sort_by);
				$sort_array['field'] = $sort_field;
				$sort_array['arrow_desc'] = true;
				$sort_array['link'] = $sort_field.'_asc';
				$options['order_by'] = $sort_field. ' DESC';
			}
			$this->set('sort_array', $sort_array);
		}
	}

	private function set_pagination(&$model){

	}

	function show($parameter) {
		$this->page_title = Inflector::humanize($this->name);
		$this->loadSidebar($parameter);
		parent::show($parameter);
	}

	function edit($parameter) {
		$this->page_title = Inflector::humanize($this->name);
		$this->loadSidebar($parameter);
		parent::edit($parameter);
	}

	function create($parameter=null) {
		$this->page_title = Inflector::humanize($this->name);
		parent::create($parameter);
	}

	function delete($parameter) {
		$this->page_title = Inflector::humanize($this->name);
		if (SITE_WORKFLOW) {
			// need to test for workflow first
			$pc_model = &NModel::factory('page_content');
			/* @var $pc_model PageContent */
			$in_workflow = $pc_model->isWorkflowContent($this->name, $parameter);
			// if it's in a workflowed page
			if ($in_workflow) {
				$this->flash->set('notice', 'The record cannot be deleted until it is removed from the workflow page it belongs to.');
				include_once 'view/helpers/url_helper.php';
				$referer = isset($this->params['_referer'])?urldecode($this->params['_referer']):false;
				if ($referer) {
					header('Location:' . $referer);
					exit;
				}
				$this->redirectTo('viewlist');
			}
		}
		parent::delete($parameter);
	}

	function loadSidebar($parameter) {
		$this->set('SIDEBAR_TITLE', $this->page_title . ' Info');
		$page_model = &NModel::singleton('page');
		$page_content_model = &NModel::factory('page_content');
		$page_content_model->content_asset = $this->name;
		$page_content_model->content_asset_id = $parameter;
		$this->setAppend('SIDEBAR_CONTENT', $this->render(array('action'=>'sidebar_edit_status', 'return'=>true)));
		if ($page_content_model->find()) {
			$pages = array();
			while ($page_content_model->fetch()) {
				$page_model->reset();
				if ($page_model->get($page_content_model->page_id)) {
					$pages[] = $page_model->toArray();
				}
			}
			$page_model->reset();
			unset($page_model);
			$this->set('pages', $pages);
			$this->setAppend('SIDEBAR_CONTENT', $this->render(array('action'=>'sidebar_page_content', 'return'=>true)));
		}
		unset($page_content_model);
		if ($this->versioning) {
			$user_model = $this->loadModel('cms_auth');
			$model = $this->getDefaultModel();
			$model = clone($model);
			if (!$model->{$model->primaryKey()}) {
				$model->get($parameter);
				$this->convertDateTimesToClient($model);
			}
			$model_data = $model->toArray();
			if ($user_model->get($model_data['cms_modified_by_user'])) {
				$model_data['user'] = $user_model->toArray();
			}
			$this->set($model_data);
			$user_model->reset();
			$version_model = $this->loadModel('cms_nterchange_versions');
			$user_model = $this->loadModel('cms_auth');
			$version_model->asset = $this->name;
			$version_model->asset_id = $parameter;
			if ($version_model->find(array('order_by'=>'cms_modified DESC'))) {
				$versions = array();
				while ($version_model->fetch()) {
					$version = $version_model->toArray();
					if ($version_data = @unserialize($version['version'])) {
						if ($user_model->get($version_data['cms_modified_by_user'])) {
							$version['user'] = $user_model->toArray();
						}
						$user_model->reset();
						$versions[] = $version;
					}
				}
				$this->set('versions', $versions);
			}
			$this->setAppend('SIDEBAR_CONTENT', $this->render(array('action'=>'sidebar_versions', 'return'=>true)));
			unset($version_model);
		}
	}

	/**
	 * Called before the form is generated
	 *
	 * Sets up a default rule for content tables so there are no
	 * identical headlines
	 *
	 * @see NController::preGenerateForm();
	 * @access public
	 * @return null
	 */
	function preGenerateForm() {
		$model = &$this->getDefaultModel();
		$fields = $model->fields();
		if (in_array('cms_headline', $fields)) {
			$model->form_rules[] = array('cms_headline', 'This headline is already being used', 'callback', array(&$this, 'uniqueHeadline'));
		}
		parent::preGenerateForm();
	}

	function getAssetContent($params) {
		$id = isset($params['id'])?$params['id']:false;
		$template = isset($params['template'])?$params['template']:'default';
		$model = &$this->getDefaultModel($this->name);
		if($id && $model && $model->get($id)) {
			$this->set($model->toArray());
			unset($model);
			return $this->render(array('action'=>$template, 'return'=>true));
		} else {
			return false;
		}
	}
	/*
	 * Returns a controller form for the asset
	 */
	function getForm(){
		require_once 'controller/form.php';
		$model = $this->getDefaultModel();
		$cform = new ControllerForm($this, $model);
		return $cform->getForm();

	}
}
?>
