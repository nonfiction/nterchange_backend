<?php
include_once 'asset_controller.php';
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
 * @category	Backend RSS Feeds
 * @author		Darron Froese <darron@nonfiction.ca>
 * @copyright	2007 nonfiction studios inc.
 * @license		http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version		SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since		Version 3.1.17
 */
class RSSController extends AssetController {
	function __construct() {
		$this->name = 'rss';
		$this->page_title = 'RSS Feeds';
		$this->records = 50;
		$this->set('_SITE_NAME_', SITE_NAME);
		parent::__construct();
	}

	/**
	 * generateFeedToken - Generate a feed_token for a logged in user.
	 *		Puts it into the database and returns to the passed url.
	 *
	 * @return void
	 **/
	function generateFeedToken() {
		$redirect_url = isset($_GET['redirect'])?$_GET['redirect']:'/nterchange';
		$random = $_SERVER['REMOTE_ADDR'] . rand(0,1000000) . time();
		$tmp_feed_token = md5($random);
		$auth = new NAuth();
		$user_id = $auth->currentUserID();
		unset($auth);
		$cms_user = NModel::factory('cms_auth');
		$cms_user->id = $user_id;
		if ($cms_user->find()) {
			while ($cms_user->fetch()) {
				$cms_user->feed_token = $tmp_feed_token;
				$cms_user->save();
				header("Location:$redirect_url");
			}
		}
	}

	/**
	 * checkToken - Check whether or not the token is present in the DB.
	 *
	 * @param	string	The offered token.
	 * @return 	boolean
	 **/
	function checkToken($token) {
		// Check the token to the ones in the database.
		$cms_auth = NModel::factory('cms_auth');
		$cms_auth->feed_token = $token;
		if ($cms_auth->find()) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * getToken - Get a token as passed on the $_GET string.
	 *
	 * @return 	string	The $_GET['token'] passed
	 **/
	function getToken() {
		return $_GET['token'];
	}

	/**
	 * auditTrail - Create an RSS feed of audit trail records.
	 *		Shows $this->records many records.
	 *
	 * @return void
	 **/
	function auditTrail() {
		if (defined('RSS_AUDIT_TRAIL') && RSS_AUDIT_TRAIL) {
			$this->auto_render = false;
			$count = 0;
			$token = $this->getToken();
			// It's got to be 32 characters - this keeps people from trying token=
			if ($length = strlen($token) < 32) die;
			if ($allowed = $this->checkToken($token)) {
				// Grab the last 50 results
				$audit_trail = NModel::factory('cms_audit_trail');
				$options['order_by'] = 'cms_created DESC';
				if ($audit_trail->find($options)) {
					while ($audit_trail->fetch()) {
						$audit_trail_controller = NController::factory('audit_trail');
						$record = $audit_trail_controller->humanizeAuditTrailRecord($audit_trail);
						//varDump($record);
						$records[] = $record;
						$count++;
						if ($count >= $this->records) break;
					}
				}
				$this->set('records', $records);
				$this->render(array('action'=>'audit_trail'));
			} else {
				print "Unauthorized access";
			}
		}
	}

}
?>
