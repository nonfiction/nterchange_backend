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
 * @category   Page Template Container Administration
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class PageTemplateContainersController extends AdminController {
	function __construct() {
		$this->name = 'page_template_containers';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_ADMIN;
		$this->login_required = true;
		parent::__construct();
	}

	function viewlist($page_template_id) {
		$this->loadSubnav($page_template_id);
		$this->auto_render = false;
		$html = '';
		if (!$page_template_id) {
			// This is a bit of a hack.
			header ('Location: /nterchange/page_template/viewlist');
		}
		$model = $this->getDefaultModel($this->name);
		$model->page_template_id = $page_template_id;
		// Let's get more information about the page_template.
		if ($page_template = $model->getLink('page_template_id', 'page_template')) {
			$this->set('page_template_name', $page_template->template_name);
			$this->set('page_template_filename', $page_template->template_filename);
		}
		$this->set('page_template_id', $page_template_id);
		if ($model->find()) {
			while ($model->fetch()) {
				$arr = $model->toArray();
				$arr['_headline'] = isset($arr['cms_headline']) && $arr['cms_headline']?$arr['cms_headline']:$model->makeHeadline();
				$models[] = $arr;
				unset($arr);
			}
			$html .= $this->set('rows', $models);
		} else {
			$this->set('notice', 'There are no containers for that template.');
		}
		$html .= $this->set(array('asset'=>$this->name, 'asset_name'=>$this->page_title?$this->page_title:Inflector::humanize($this->name)));
		$html .= $this->render(array('layout'=>'default'));
		return $html;
	}
	
	function create($parameter=null, $layout=true) {
		$this->page_template_id = $parameter;
		$this->loadSubnav($parameter);
		parent::create($parameter);
	}
	
	function edit($parameter) {
		$this->page_template_id = $this->getPTIFromId($parameter);
		$this->set('page_template_id', $this->page_template_id);
		$this->loadSubnav($parameter);
		parent::edit($parameter);
	}
	
	// Get page_template_id from page_template_container_id.
	function getPTIFromId($id) {
		$model = &NModel::factory($this->name);
		$model->id = $id;
		if($model->find()) {
			while ($model->fetch()) {
				$result = $model->toArray();
			}
			$page_template_id = $result['page_template_id'];
		}
		unset($model);
		return $page_template_id;
	}
	
	function postGenerateForm(&$form) {
		$form->removeElement('__header__');
		$form->addRule('container_var', 'We need to have a variable for this container.', 'required', null, 'client');
		$form->addRule('container_name', 'We need to have a name for this container.', 'required', null, 'client');
		$form->addRule('container_var', 'Uppercase letters, numbers and underscores - without spaces or punctuation.', 'regex', '/^[A-Z0-9_]+$/', 'client');
		// Set the page_template in the menu as passed by $parameter.
		$template_group = &$form->getElement('page_template_id');
		$template_group->setSelected($this->page_template_id);
		// Not sure I should do this - but it seems to help with confusion.
		$template_group->freeze();
	}
	
	function &getDefaultModel() {
		$model = &$this->loadModel('page_template_containers');
		return $model;
	}
	
}
?>