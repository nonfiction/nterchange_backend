<?php
require_once 'app/controllers/asset_controller.php';
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
 * @category   	Version Check Controller
 * @author     	Darron Froese <darron@nonfiction.ca>
 * @copyright  	2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since		3.1.12
 */
class VersionCheckController extends AssetController {
	var $check_version_url = 'http://versioncheck.nterchange.com/versioncheck';
	var $check_version_interval = 86400;
	var $cache_name = 'version_check';
	var $cache_group = 'vcheck';

	function __construct() {
		$this->name = 'version_check';
		$this->base_view_dir = ROOT_DIR;
		parent::__construct();
	}

	/**
	 * versionCheck - Get the most current version of nterchange and cache the result.
	 *
	 * @return 	array 	Information about the newest version of nterchange.
	 **/
	function versionCheck() {
		require_once 'Cache/Lite.php';
		$options = array('cacheDir'=>CACHE_DIR . '/ntercache/', 'lifeTime'=>$this->check_version_interval);
		$cache = new Cache_Lite($options);
		$yaml = $cache->get($this->cache_name, $this->cache_group);
		if (empty($yaml)) {
			include_once 'HTTP/Request.php';
			$req = new HTTP_Request($this->check_version_url);
			if (!PEAR::isError($req->sendRequest())) {
				$yaml = $req->getResponseBody();
				$cached = $cache->save($yaml, $this->cache_name, $this->cache_group);
				if ($cached == true) {
					NDebug::debug('Version check - data is from the web and is now cached.' , N_DEBUGTYPE_INFO);
				} else {
					NDebug::debug('Version check - data is from the web and is NOT cached.' , N_DEBUGTYPE_INFO);
				}
			}
		} else {
			NDebug::debug('Version check - data is from the cache.' , N_DEBUGTYPE_INFO);
		}
		require_once 'vendor/spyc.php';
		$newest_version_info = @Spyc::YAMLLoad($yaml);
		return $newest_version_info;
	}

	/**
	 * compareVersions - Compares the current nterchange version to the newest version.
	 *
	 * @param	string	Current version of nterchange.
	 * @param	string	New version of nterchange.
	 * @return 	boolean
	 **/
	function compareVersions($current, $new) {
		$comparison = version_compare($current, $new);
		if ($comparison == -1) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * dashboardVersionCheck - This runs for ADMIN users or higher and lets them know
	 *		if there is an upgrade available for nterchange. Called from the dashboard
	 *		helper and displays on the dashboard.
	 *
	 * @return void
	 **/
	function dashboardVersionCheck() {
		// Check the user level - this only shows up for admins or higher.
		$auth = new NAuth();
		$current_user_level = $auth->getAuthData('user_level');
		unset($auth);
		if ($current_user_level >= N_USER_ADMIN) {
			$newest = $this->versionCheck();
			if (is_array($newest)) {
				$upgrade = $this->compareVersions(NTERCHANGE_VERSION, $newest['version']);
				if ($upgrade == true) {
					$this->set('upgrade', $newest);
					$this->set('nterchange_version', NTERCHANGE_VERSION);
				} else {
					$this->set('uptodate', true);
				}
				$this->render(array('action'=>'dashboard_version_check', 'return'=>false));
			} else {
				NDebug::debug('There was an error with the version check.' , N_DEBUGTYPE_INFO);
			}
		}
	}

}
?>
