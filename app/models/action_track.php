<?php
require_once 'n_model.php';
/**
 * Action Track - A simple model to log the last item an nterchange user is editing
 * to help to avoid multiple people editing the same thing.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Action Track Model
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @since	   3.1.14
 * @link       http://www.nterchange.com/
 */
class ActionTrack extends NModel {
	var $time_limit = 300;

	function __construct() {
		$this->__table = 'action_track';
		$this->_order_by = 'timestamp DESC';
		parent::__construct();
	}

	/**
	 * cleanURL - Clean up the REQUEST_URI as passed.
	 * 		Removes /APP_DIR and the leading slash.
	 *
	 * @param	string	REQUEST_URI
	 * @return 	string	Cleaned URL
	 **/
	function cleanURL($url) {
		$url = str_replace('/' . APP_DIR, '', $url);
		$url = preg_replace('|^/|', '', $url);
		return $url;
	}

	/**
	 * setAssetValues - Set the values for the class based on the cleaned up URL.
	 *
	 * @param	string	A cleaned up URL
	 * @return 	boolean
	 **/
	function setAssetValues($url) {
		$pieces = explode('/', $url);
		$this->asset_name = $pieces[0];
		$this->action = $pieces[1];
		$this->asset_id = $pieces[2];
		return true;
	}

	/**
	 * trackCurrentEdit - Log that a user is editing a record in this->__table.
	 *
	 * @param	int		User id who is editing a record.
	 * @param	string	Name of the asset they are editing.
	 * @param	int		Id of the asset they are editing
	 * @return 	boolean
	 **/
	function trackCurrentEdit($user_id, $asset_name, $asset_id) {
		$this->user_id = $user_id;
		$this->asset_name = $asset_name;
		$this->action = 'edit';
		$this->asset_id = $asset_id;
		$this->timestamp = time();
		if ($this->save()) {
			$this->removeOldEdits($user_id, $this->timestamp);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * completeCurrentEdit - This is called after a record has been updated
	 * in app_controller:edit. It removes the record from $this->__table.
	 *
	 * @param	int		User id who is editing a record.
	 * @param	string	Name of the asset they are editing.
	 * @param	int		Id of the asset they are editing
	 * @return void
	 **/
	function completeCurrentEdit($user_id, $asset_name, $asset_id) {
		$this->user_id = $user_id;
		$this->asset_name = $asset_name;
		$this->action = 'edit';
		$this->asset_id = $asset_id;
		if ($this->find()) {
			while ($this->fetch()) {
				$this->delete();
			}
		}
	}

	/**
	 * removeOldEdits - This is called after a trackCurrentEdit saves an entry.
	 * It's to make sure that there's only one entry by that user at any time.
	 *
	 * @param	int		User id who has edited a record.
	 * @param	int		Timestamp from time()
	 * @return void
	 **/
	function removeOldEdits($user_id, $last_modified) {
		$this->reset();
		$this->user_id = $user_id;
		if ($this->find()) {
			while ($this->fetch()) {
				if ($this->timestamp < $last_modified) {
					$this->delete();
				}
			}
		}
	}

	/**
	 * checkAssetEditStatus - This is called from app_controller:edit and returns false if:
	 * 	1. Nobody is editing the record.
	 *	2. An edit was longer than $this->time_limit.
	 * It returns true if there somebody is currently editing the same record.
	 *
	 * @param	string	Name of the asset they are editing.
	 * @param	int		Id of the asset they are editing
	 * @return 	boolean
	 **/
	function checkAssetEditStatus($asset_name, $asset_id) {
		$time_now = time();
		$this->asset_name = $asset_name;
		$this->asset_id = $asset_id;
		if ($this->find()) {
			while ($this->fetch()) {
				// Check time limit to see if it's past.
				$item = $this->toArray();
				if ($limit = $this->timestampWithinLimit($item['timestamp'])) {
					return true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}

	/**
	 * timestampWithinLimit - Check to see whether or not a timestamp is
	 * 	within $this->time_limit away from the current time.
	 * Returns true if it is, false if it's not.
	 *
	 * @param	int		timestamp as returned from time()
	 * @return 	boolean
	 **/
	function timestampWithinLimit($timestamp) {
		$time_now = time();
		$time_diff = $time_now - $timestamp;
		if ($time_diff < $this->time_limit) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * getActiveEdits - This grabs any currently active edits as long as they're within
	 *	$this->time_limit - used for display by the action_track helper.
	 *	Returns an array of items.
	 *
	 * @return 	array 	An array of currently active edits.
	 **/
	function getActiveEdits() {
		$items = array();
		if ($this->find()) {
			while ($this->fetch()) {
				$item = $this->toArray();
				// Check to see if it's within the time_limit;
				if ($limit = $this->timestampWithinLimit($item['timestamp'])) {
					$items[] = $item;
				}
			}
		}
		return $items;
	}
}
?>
