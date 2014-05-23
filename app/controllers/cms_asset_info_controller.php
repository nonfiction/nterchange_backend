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
 * @category   Asset Administration
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsAssetInfoController extends AdminController {
	function __construct() {
		$this->name = 'cms_asset_info';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_ADMIN;
		$this->page_title = 'Assets';
		$this->login_required = true;
		parent::__construct();
	}
	
	/**
	 * AssetList - Return an array of all assets in cms_asset_info table unless
	 * 		$connectable is set - then use $model->not_connectable to leave those 
	 *		assets out.
	 *
	 * @param 	boolean	Whether you want only connectable assets.
	 * @return 	array 	An array with all of the possible assets.
	 **/
	function AssetList ($connectable=false) {
		// Grab the list of available assets.
		$assets = &NModel::factory($this->name);
		if ($assets->find()) {
			while ($assets->fetch()) {
				$asset_list[] = $assets->toArray();
			}
			unset($assets);
			foreach ($asset_list as $asset) {
				$model = &NModel::factory($asset['asset']);
				// If you don't want any non_connectable assets in the array.
				if ($model && $connectable && !isset($model->not_connectable)) {
					$final_assets[] = $asset;
				// Or if you just want them all.
				} elseif($model && !$connectable) {
					$final_assets[] = $asset;
				}
				unset($model);
			}
			return $final_assets;
		}		
	}
	
	/**
	 * doesAssetModelFileExist - Check for the existance of an asset's model file.
	 *		Check in the frontend and backend.
	 *
	 * @param	string	The name of the asset
	 * @return 	boolean	Does it exist or not?
	 **/
	function doesAssetModelFileExist($asset){
		$full_path_filename = ASSET_DIR . '/models/' . $asset . '.php';
		if (file_exists($full_path_filename)) {
			return true;
		} else {
			// Check in nterchange backend proper.
			$full_path_filename = BASE_DIR . '/app/models/' . $asset . '.php';
			if (file_exists($full_path_filename)) {
				return true;
			}
			return false;
		}
	}

	/**
	 * doesAssetControllerFileExist - Check for the existance of an asset's controller file.
	 *		Check in the frontend and backend.
	 *
	 * @param	string	The name of the asset
	 * @return 	boolean	Does it exist or not?
	 **/
	function doesAssetControllerFileExist($asset){
		$full_path_filename = ASSET_DIR . '/controllers/' . $asset . '_controller.php';
		if (file_exists($full_path_filename)) {
			return true;
		} else {
			// Check in nterchange backend proper.
			$full_path_filename = BASE_DIR . '/app/controllers/' . $asset . '_controller.php';
			if (file_exists($full_path_filename)) {
				return true;
			}
			return false;
		}		
	}
	
	/**
	 * doesAssetDatabaseTableExist - Check for the existance of an asset's database table.
	 *
	 * @param	string	The name of the asset
	 * @return 	boolean	Does it exist or not?
	 **/
	function doesAssetDatabaseTableExist($asset){
		$model = &NModel::factory($asset);
		if ($model) {
			if ($model->_fields) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function &getDefaultModel() {
		$model = &$this->loadModel($this->name);
		return $model;
	}
	
	/**
	 * postGenerateForm - Just setting some validation rules for the forms.
	 *
	 * @return void
	 **/
	function postGenerateForm(&$form) {
		$form->removeElement('__header__');
		$form->addRule('asset', 'We need to have an asset.', 'required', null, 'client');
		$form->addRule('asset', 'Letters, numbers, dashes and underscores - without a suffix, spaces or punctuation.', 'regex', '/^[a-zA-Z0-9_-]+$/', 'client');
		$form->addRule('asset_name', 'We need to have a name for this asset.', 'required', null, 'client');
	}
}
?>