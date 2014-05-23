<?php
require_once 'nterchange_controller.php';
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
 * @category   Content Administration
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class ContentController extends nterchangeController {
	var $user_level_required = N_USER_NORIGHTS;
	var $name = 'content';
	var $page_title = 'Content';
	
	function __construct() {
		if (is_array($this->login_required)) {
			$this->login_required[] = 'list_assets';
		}
		parent::__construct();
	}
	
	function index($parameter) {
		$this->redirectTo('list_assets', $parameter);
	}
	
	function listAssets($parameter) {
		$this->auto_render = false;
		$asset_model = &$this->getDefaultModel();
		if ($asset_model->find()) {
			$assets = array();
			while ($asset_model->fetch()) {
				$assets[] = $asset_model->toArray();
			}
			$this->set(array('assets'=>$assets));
		}
		$this->render(array('layout'=>'default'));
	}

	function &getDefaultModel() {
		if (get_class($this) == __CLASS__) {
			$ret = &$this->loadModel('cms_asset_info');
		} else {
			$ret = &parent::getDefaultModel();
		}
		return $ret;
	}
}
?>