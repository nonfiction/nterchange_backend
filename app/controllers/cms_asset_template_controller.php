<?php
require_once 'admin_controller.php';
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
 * @category   Asset Template Administration
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsAssetTemplateController extends AdminController {
	function __construct() {
		$this->name = 'cms_asset_template';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_ADMIN;
		$this->login_required = true;
		$this->page_title = 'Asset / Container Templates';
		parent::__construct();
	}
	
	function getAssetTemplate($asset, $container_id) {
		$model = &NModel::factory($this->name);
		$model->asset = (string)$asset;
		$model->page_template_container_id = (int)$container_id;
		$ret = '';
		if ($model->find(null, true)) {
			$ret = $model->template_filename;
		}
		unset($model);
		return $ret;
	}

	function viewlist($page_template_container) {
		$this->loadSubnav($page_template_container);
		$this->auto_render = false;
		$html = '';
		if (!$page_template_container) {
			// This is a bit of a hack.
			header ('Location: /nterchange/page_template/viewlist');
		}
		$model = $this->getDefaultModel($this->name);
		$model->page_template_container_id = $page_template_container;
		$this->set('page_template_container_id', $page_template_container);
		// Let's get the Container Name.
		if ($page_template_container = $model->getLink('page_template_container_id', 'page_template_containers')) {
			$this->set('page_template_container_name', $page_template_container->container_name);
			// With the name, let's get the template name and filename too.
			$page_template = &NModel::factory('page_template');
			$page_template->id = $page_template_container->page_template_id;
			$this->set('page_template_id', $page_template_container->page_template_id);
			if ($page_template->find()) {
				while ($page_template->fetch()) {
					$page_template_tmp = $page_template->toArray();
					$this->set('page_template_filename', $page_template_tmp['template_filename']);
					$this->set('page_template_name', $page_template_tmp['template_name']);
				}
			}
		}
		if ($model->find()) {
			while ($model->fetch()) {
				$arr = $model->toArray();
				$arr['_headline'] = isset($arr['cms_headline']) && $arr['cms_headline']?$arr['cms_headline']:$model->makeHeadline();
				$models[] = $arr;
				unset($arr);
				$html .= $this->set('rows', $models);
			}
			$html .= $this->set(array('rows'=>$models, 'asset'=>$this->name, 'asset_name'=>$this->page_title?$this->page_title:Inflector::humanize($this->name)));
		} else {
			$this->set('notice', 'There are no assets associated with that container.');
		}
		$html .= $this->set(array('asset'=>$this->name, 'asset_name'=>$this->page_title?$this->page_title:Inflector::humanize($this->name)));
		$html .= $this->render(array('layout'=>'default'));
		return $html;
	}

	function create($parameter=null, $layout=true) {
		$this->page_template_container_id = $parameter;
		$this->loadSubnav($parameter);
		parent::create($parameter);
	}
	
	function edit($parameter) {
		$this->page_template_container_id = $this->getPTCIFromId($parameter);
		$this->set('page_template_container_id', $this->page_template_container_id);
		$this->loadSubnav($parameter);
		parent::edit($parameter);
	}
	
	// Get page_template_container_id from cms_asset_template_id
	function getPTCIFromId($id) {
		$model = &NModel::factory($this->name);
		$model->id = $id;
		if($model->find()) {
			while ($model->fetch()) {
				$result = $model->toArray();
			}
			$page_template_container_id = $result['page_template_container_id'];
			// Set the asset name for postGenerateForm.
			$this->passed_asset = $result['asset'];
		}
		unset($model);
		return $page_template_container_id;
	}
	
	function postGenerateForm(&$form) {
		$form->removeElement('__header__');
		// Set the container in the menu as passed by $parameter
		$container_group = &$form->getElement('page_template_container_id');
		$container_group->setSelected($this->page_template_container_id);
		// Not sure I should do this - but it seems to help with confusion.
		$container_group->freeze();
		
		// Grab the asset list and create an array for QuickForm.
		$assets = &NController::factory('cms_asset_info');
		$array_of_assets = $assets->AssetList(true);
		foreach ($array_of_assets as $asset) {
			$select_array[$asset['asset']] = $asset['asset_name'];
		}
		// Add the element in place of the current asset form item.
		$form->removeElement('asset');
		$new_select = &$form->addElement('select', 'asset', 'Asset:', $select_array);
		$form->insertElementBefore($form->removeElement('asset', false), 'template_filename');
		// Set the asset if passed by edit.
		if (isset($this->passed_asset)) {
			$new_select->setSelected($this->passed_asset);
		}
		$form->addRule('template_filename', 'We need to have a template filename.', 'required', null, 'client');
		$form->addRule('template_filename', 'Letters, numbers, dashes and underscores - without a suffix, spaces or punctuation.', 'regex', '/^[a-zA-Z0-9_-]+$/', 'client');
	}

	function doesAssetTemplateExist($filename, $asset){
		$full_path_filename = ASSET_DIR . '/views/' . $asset . '/' . $filename . '.' . DEFAULT_PAGE_EXTENSION;
		if (file_exists($full_path_filename)) {
			return true;
		} else {
			// Check in nterchange backend proper.
			$full_path_filename = BASE_DIR . '/app/views/' . $asset . '/' . $filename . '.' . DEFAULT_PAGE_EXTENSION;
			if (file_exists($full_path_filename)) {
				return true;
			}
			return false;
		}
	}
	
	function &getDefaultModel() {
		$model = &$this->loadModel('cms_asset_template');
		return $model;
	}
}
?>