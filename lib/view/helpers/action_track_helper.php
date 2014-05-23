<?php
require_once 'view/helper.php';
require_once 'app/models/action_track.php';
/**
 * Action Track Helper
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Action Track Helper
 * @author     	Darron Froese <darron@nonfiction.ca>
 * @copyright  	2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.1.14
 * @todo 		If more than 60 seconds, turn to minutes.
 */
class ActionTrackHelper {
	function assetEditStatus($params) {
		$action_track = NModel::factory('action_track');
		$url = $action_track->cleanUrl($_SERVER['REQUEST_URI']);
		$action_track->setAssetValues($url);
		$status = $action_track->getActiveEdits();
		$this->displayEditStatus($status);
	}

	function displayEditStatus($items) {
		$time_now = time();
		foreach ($items as $item) {
			$time_diff = $time_now - $item['timestamp'];

			// Grab information about the person editing.
			$user_info = NModel::factory('cms_auth');
			$user_info->id = $item['user_id'];
			if ($user_info->find()) {
				while ($user_info->fetch()) {
					$name = $user_info->real_name;
					$email = $user_info->email;
				}
			}
			unset($cms_auth);

			// Check to see if you're the one editing.
			$auth = new NAuth();
			$current_user_id = $auth->currentUserID();
			unset($auth);

			// Output the item.
			if ($current_user_id == $item['user_id']) {
				print '<div id="actiontrack">You have been editing this record for ' . $time_diff . ' seconds.</div>';
			} else {
				print '<div id="actiontrack"><a href="mailto:' . $email . '">' . $name . '</a> was editing this item ' . $time_diff . ' seconds ago.</div>';
			}
		}
	}
}
?>
