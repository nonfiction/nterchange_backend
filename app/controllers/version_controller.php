<?php
require_once 'nterchange_controller.php';
require_once 'n_filesystem.php';
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
 * @category   Content Versions
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class VersionController extends nterchangeController {
	function __construct() {
		$this->name = 'version';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_NORIGHTS;
		$this->public_actions = array('view', 'reinstate');
		$this->login_required = true;
		parent::__construct();
	}

	function &getDefaultModel() {
		return $this->loadModel('cms_nterchange_versions');
	}

	function view($parameter) {
		if ($model = &$this->getDefaultModel() && $model->get($parameter)) {
			$this->set('asset', unserialize($model->version));
		}
	}

	/**
	 * reinstate - Pull an old version of a content asset back and insert it as the current version.
	 *
	 * @param  int Version id you want reinstated.
	 * @return     void
	 **/
	function reinstate($parameter) {
		// pull the version and save the content into the asset record
		if ($model = &$this->getDefaultModel() && $model->get($parameter)) {
			$version = unserialize($model->version);
			$asset_controller = &NController::factory($model->asset);
			$asset_controller->_auth = &$this->_auth;
			$asset_model = &$asset_controller->getDefaultModel();
			if ($asset_controller && $asset_model && $asset_model->get($model->asset_id)) {
				foreach ($version as $k=>$v) {
					if (!preg_match('/^cms_/', $k) && $k != 'id') {
						$asset_model->$k = $v;
					}
				}
				// insert a new version as part of the process
				$asset_controller->insertVersion();
				// save the new record
				$asset_model->save();
			}
			unset($asset_model);
			$this->flash->set('notice', 'The version has been reinstated.');
			$referer = isset($this->params['_referer'])?$this->params['_referer']:false;
			if (!$referer) {
				include_once 'view/helpers/url_helper.php';
				$referer = urlHelper::urlFor($this, array('controller'=>$model->asset, 'action'=>'edit', 'id'=>$model->asset_id));
			}
			header('Location:' . $referer);
			exit;
		}
	}

	/**
	 * fileDelete - Delete a file from an old version - but only if it's not available
	 * in the current version.
	 *
	 * @param  array   An unserialized array from cms_nterchange_versions->version
	 * @param  string  The name of an asset
	 * @param  int     The id of the asset.
	 * @return         boolean
	 * @todo           Make this work with n_mirror.
	 **/
	function fileDelete($array, $asset, $asset_id) {
		$current_version = $this->getCurrentVersion($asset, $asset_id);
		if (!$current_version) return false;
		foreach ($array as $field) {
			$ereg = '^' . UPLOAD_DIR;
			if (eregi($ereg, $field)) {
				// Check to see whether or not the file can be deleted.
				// If it was uploaded in a previous version, but is still available in the
				// current version, we don't want to delete it.
				if (in_array($field, $current_version)) {
					NDebug::debug('Cannot delete ' . $field . ' as it is in the current version.' , N_DEBUGTYPE_INFO);
				} else {
					$ret = NFilesystem::deleteFile($field);
				}
			}
		}
		return $ret;
	}

	/**
	 * deleteEmptyFolders - Delete folders without anything in them anymore.
	 *
	 * @param	string  The name of the asset.
	 * @param	int     The id of that asset.
	 * @return        void
	 * @todo          Make this work with n_mirror.
	 **/
	function deleteEmptyFolders($asset_name, $asset_id) {
		$folder = DOCUMENT_ROOT . UPLOAD_DIR . '/' . $asset_name . '/' . $asset_id;
		if (is_dir($folder)) {
			if ($handle = opendir($folder)) {
			    while (false !== ($file = readdir($handle))) {
			        if ($file != "." && $file != "..") {
			            if (is_dir($folder . '/' . $file)) {
							$full_path = $folder . '/' . $file;
							// Check to see if it's empty.
							if (count(glob("$full_path/*")) === 0) {
								// Then delete if this is so.
								NFilesystem::deleteFolder($full_path);
							} else {
								NDebug::debug("$full_path is not empty and will not be deleted." , N_DEBUGTYPE_INFO);
							}

						}
			        }
			    }
			    closedir($handle);
			}
		}
	}

	/**
	 * getCurrentVersion - Get the current version of the $asset referenced by $asset_id
	 *
	 * @param  string The name of the asset.
	 * @param  int    The id of that asset.
	 * @return array  All the content in that asset.
	 **/
	function getCurrentVersion($asset, $asset_id) {
		$asset_object = NModel::factory($asset);
		$asset_object->id = $asset_id;
		if ($asset_object->find()) {
			while ($asset_object->fetch()) {
				$arr = $asset_object->toArray();
				return $arr;
			}
		} else {
			return false;
		}
	}

	/**
	 * deleteAll - Delete all old versions of a particular asset.
	 * This comprises all files and empty folders as well.
	 *
	 * @param  int  The id of this particular version.
	 * @return void
	 **/
	function deleteAll($parameter) {
		if ($model = &$this->getDefaultModel() && $model->get($parameter)) {
			$info = $model->toArray();
			$model->reset();
			// Let's get all the versions for that asset and asset_id.
			$model->asset_id = $info['asset_id'];
			$model->asset = $info['asset'];
			$this->debug("Deleting versions for {$model->asset} : {$model->asset_id}");
			if ($model->find()) {
				while ($model->fetch()) {
					$arr = $model->toArray();
					$content = unserialize($arr['version']);
					$success = $this->fileDelete($content, $info['asset'], $info['asset_id']);
					$model->delete();
				}
			}
			$this->deleteEmptyFolders($info['asset'], $info['asset_id']);
			if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
				$audit_trail = &NController::factory('audit_trail');
				$audit_trail->insert(array('asset'=>$info['asset'], 'asset_id'=>$info['asset_id'], 'action_taken'=>AUDIT_ACTION_DELETE_ASSET_VERSIONS));
				unset($audit_trail);
			}
			header('Location:' . $_GET['_referer']);
		}
	}

	/**
	 * deleteEverything - Delete all old versions of everything. Used just before
	 * website launch or when somebody just wants to clean up.
	 * NOTE: Not linked to from anywhere or really heavily tested. Use with caution.
	 *
	 * @return void
	 **/
	function deleteEverything($parameter) {
		if ($model = &$this->getDefaultModel()) {
			if ($model->find()) {
				while ($model->fetch()) {
					$arr = $model->toArray();
					$content = unserialize($arr['version']);
					$success = $this->fileDelete($content, $arr['asset'], $arr['asset_id']);
					$this->deleteEmptyFolders($arr['asset'], $arr['asset_id']);
					$model->delete();
				}
				// Let's empty out the entire table as well.
				$sql = 'TRUNCATE cms_nterchange_versions';
				$db = NDB::connect();
				$res = $db->query($sql);
			} else {
				NDebug::debug('There were no old versions of anything to delete.' , N_DEBUGTYPE_INFO);
			}
			header('Location:/nterchange/');
		}
	}
}
?>
