<?php
/**
 * Audit Trail defines that are used by everything.
 *
 **/
define('AUDIT_ACTION_INSERT', 1);
define('AUDIT_ACTION_UPDATE', 2);
define('AUDIT_ACTION_DELETE', 3);
define('AUDIT_ACTION_CONTENT_ADDEXISTING', 4);
define('AUDIT_ACTION_CONTENT_ADDNEW', 5);
define('AUDIT_ACTION_CONTENT_REMOVE', 6);
define('AUDIT_ACTION_WORKFLOW_START', 7);
define('AUDIT_ACTION_WORKFLOW_DELETE', 8);
define('AUDIT_ACTION_WORKFLOW_SUBMIT', 9);
define('AUDIT_ACTION_WORKFLOW_APPROVE', 10);
define('AUDIT_ACTION_WORKFLOW_APPROVEPUBLISH', 11);
define('AUDIT_ACTION_WORKFLOW_APPROVEREMOVE', 12);
define('AUDIT_ACTION_WORKFLOW_DECLINE', 13);
define('AUDIT_ACTION_DRAFT_SAVE', 14);
define('AUDIT_ACTION_DRAFT_DELETE', 15);
define('AUDIT_ACTION_DELETE_ASSET_VERSIONS', 16);
define('AUDIT_ACTION_LOGIN', 17);

include_once 'admin_controller.php';

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
 * @category   Audit Trail
 * @author     Tim Glen <tim@nonfiction.ca>
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class AuditTrailController extends AdminController {
	function __construct() {
		$this->name = 'audit_trail';
		parent::__construct();
	}

	function index() {
		$this->redirectTo('viewlist');
	}

	/**
	 * viewlist - Shows a list of audit trail records from the current date (by default).
	 * 		Can browse around records from different dates using the form
	 *		at the top of the page.
	 *
	 * @return void
	 **/
	function viewlist($parameter) {
		include_once 'n_date.php';
		include_once 'n_quickform.php';
		require_once 'HTML/QuickForm/Renderer/Array.php';

		$this->_auth = new NAuth;
		$this->auto_render = false;

		// set up the search form
		$form = new NQuickForm('audit_search', 'get');

		if ($date_params = $this->getParam('date')) {
		  $date = $this->dateStartEnd($date_params);
		} else {
		  $date = $this->dateStartEnd();
		}

    $el = &$form->addElement('date', 'date', 'Date', array('addEmptyOption'=>true, 'format'=>'F d Y', 'maxYear' => 2015));
		$el->setValue($date['used']);

		$form->addElement('submit', null, 'Search');

		$renderer = new HTML_QuickForm_Renderer_Array(true, true);
		$form->accept($renderer);
		$this->set('audit_search', $renderer->toArray());

		$model = &$this->getDefaultModel();

		if ($model->find(array('conditions'=>'cms_created BETWEEN ' . $model->quote($date['start']) . ' AND ' . $model->quote($date['end']), 'order_by'=>'cms_created DESC'))) {
			$html = '';

			if ($date['month']) {
			  $html .= "<p>Showing Monthly Results for: ".date("F, Y", strtotime($date['used']));
			}
			while ($model->fetch()) {
				// Actually turn the id's into something readable.
				$info = $this->humanizeAuditTrailRecord($model);
				$this->set($info);
				$html .= $this->render(array('action'=>'audit_trail_record', 'return'=>true));
			}

			$this->set('audit_trail', $html);
			$this->set('result_count', $model->numRows());
		} else {
			$this->set('result_count', 'no');
			$this->set('audit_trail', '<p>There were no results found for the specified date.</p>');
		}
		// Exposes an RSS feed link to Admin or higher users.
		if (defined('RSS_AUDIT_TRAIL') && RSS_AUDIT_TRAIL) {
			NDebug::debug('We are checking to see if we can display the RSS feed.' , N_DEBUGTYPE_INFO);
			$this->checkRSSFeed();
		}
		$this->set('date', $date['used']);
		$this->loadSubnav($parameter);
		$this->render(array('layout'=>'default'));
	}

	function dateStartEnd($params=false){
		$y = (isset($params['Y']) && $params['Y']) ? $params['Y'] : false;
		$m = (isset($params['F']) && $params['F']) ? $params['F'] : false;
		$d = (isset($params['d']) && $params['d']) ? $params['d'] : false;

		$date = array();
		$date['month'] = false;

		if ($y && $d && $m){
			// Fully qualified
			$date_arg = date('Y-m-d', strtotime("$y-$m-$d"));
			$date['start'] = NDate::convertTimeToUTC($date_arg . ' 00:00:00', '%Y-%m-%d %H:%M:%S');
			$date['end']   = NDate::convertTimeToUTC($date_arg . ' 23:59:59', '%Y-%m-%d %H:%M:%S');
			$date['used']  = $date_arg;
			return($date);
		}

		if ((! $params['d']) && ( $y && $params['F'] )) {
			// One Month
			$date_arg = date('Y-m-d', strtotime("$y-$m-1"));
			$days_in_month = date('t', strtotime($date_arg));
			$month_end = date('Y-m-d', strtotime("$y-$m-$days_in_month"));
			$date['start'] = NDate::convertTimeToUTC($date_arg . ' 00:00:00', '%Y-%m-%d %H:%M:%S');
			$date['end']   = NDate::convertTimeToUTC($month_end . ' 23:59:59', '%Y-%m-%d %H:%M:%S');
			$date['used']  = "$y-$m-1";
			$date['month'] = true;
			return $date;
		}

		// Default to one day: today
		$date['start'] = NDate::convertTimeToUTC(date('Y-m-d') . ' 00:00:00', '%Y-%m-%d %H:%M:%S');
		$date['end'] = NDate::convertTimeToUTC(date('Y-m-d') . ' 23:59:59', '%Y-%m-%d %H:%M:%S');
		$date['used']  = date('Y-m-d');
		return($date);

	}

	function page($parameter) {
		$page_id = $parameter;
		$this->_auth = new NAuth;
		$this->auto_render = false;
		// set up the search form
		include_once 'n_quickform.php';
		// search for the date
		/* @var $model cmsAuditTrail */
		$model = &$this->getDefaultModel();
		$model->page_id = $page_id;
		if ($model->find(array('order_by'=>'cms_created DESC'))) {
			$html = '';
			while ($model->fetch()) {
				// Actually turn the id's into something readable.
				$info = $this->humanizeAuditTrailRecord($model);
				$this->set($info);
				$html .= $this->render(array('action'=>'page_audit_trail_record', 'return'=>true));
			}
			$this->set('audit_trail', $html);
			$this->set('result_count', $model->numRows());
		} else {
			$this->set('result_count', 'no');
			$this->set('audit_trail', '<p>There were no results found for the specified page.</p>');
		}
		// Exposes an RSS feed link to Admin or higher users.
		if (defined('RSS_AUDIT_TRAIL') && RSS_AUDIT_TRAIL) {
			NDebug::debug('We are checking to see if we can display the RSS feed.' , N_DEBUGTYPE_INFO);
			$this->checkRSSFeed();
		}
		$this->loadSubnav($parameter);
		$this->render(array('layout'=>'default'));
	}

	/**
	 * humanizeAuditTrailRecord - Turns the id's in the cms_audit_trail table into human readable
	 *		English.
	 *
	 * @param 	object	An object which contains an audit trail record.
	 * @return 	array 	An array of human readable information about that audit trail record.
	 **/
	function humanizeAuditTrailRecord($model) {
		$this->convertDateTimesToClient($model);
		$info = array('user'=>false, 'asset'=>false, 'workflow'=>false, 'workflow_group'=>false, 'page'=>false, 'page_content'=>false);
		if ($model->user_id) {
			if ($model->user_id == $model->website_user_id) {
				// This is a hack for timed_removal of content - see the cms_audit_trail model for info.
				$info['user'] = array('id'=>$model->website_user_id, 'real_name'=>$model->website_user_name, 'email'=>$model->website_user_email);
			} else {
				$info['user'] = $this->getAuditInfo('users', $model->user_id);
			}
		}
		if ($model->workflow_id) {
			$info['workflow'] = $this->getAuditInfo('workflow', $model->workflow_id);
		}
		if ($model->asset && $model->asset != 'page' && $model->asset_id) {
			$info['asset'] = $this->getAuditInfo($model->asset, $model->asset_id);
			$info['asset_type'] = $model->asset;
			$info['asset_name'] = Inflector::humanize($model->asset);
		} else if ($info['workflow']) {
			$info['asset'] = $this->getAuditInfo($info['workflow']['asset'], $info['workflow']['asset_id']);
			$info['asset_type'] = $info['workflow']['asset'];
			$info['asset_name'] = Inflector::humanize($info['workflow']['asset']);
		}
		if ($model->workflow_group_id) {
			$info['workflow_group'] = $this->getAuditInfo('workflow_group', $model->workflow_group_id);
		} else if ($info['workflow']) {
			// if there's no workflow_group_id right in the audit trail, try the workflow['workflow_group_id']
			$info['workflow_group'] = $this->getAuditInfo('workflow_group', $info['workflow']['workflow_group_id']);
		}
		if ($model->page_content_id) {
			$info['page_content'] = $this->getAuditInfo('page_content', $model->page_content_id);
		} else if ($info['workflow']) {
			// if there's no page_content_id right in the audit trail, try the workflow['page_content_id']
			$info['page_content'] = $this->getAuditInfo('page_content', $info['workflow']['page_content_id']);
		}
		if ($model->page_id || $model->asset == 'page') {
			$info['page'] = $model->asset == 'page'?$this->getAuditInfo('page', $model->asset_id):$this->getAuditInfo('page', $model->page_id);
		} else if ($info['page_content']) {
			// if there's no page_id right in the audit trail, try the page_content['page_id']
			$info['page'] = $this->getAuditInfo('page', $info['page_content']['page_id']);
		} else if ($info['workflow']) {
			// if there's no page_id or page_content_id right in the audit trail, try the workflow['page_id']
			$info['page'] = $this->getAuditInfo('page', $info['workflow']['page_id']);
		}
		$info['action_taken'] = $this->actionToText($model->action_taken);
		$info['ip'] = $model->ip;
		$info['created'] = $model->cms_created;
		return $info;
	}

	/**
	 * checkRSSFeed - Checks the level of the user and exposes a link to an audit trail RSS feed
	 * 		to that user if they're an admin level or higher.
	 *
	 * @return void
	 **/
	function checkRSSFeed() {
		// Check the user level - this only shows up for admins or higher.
		$auth = new NAuth();
		$current_user_level = $auth->getAuthData('user_level');
		$user_id = $auth->currentUserID();
		if ($current_user_level >= N_USER_ADMIN) {
			// Get their feed token if they have it.
			$cms_user = NModel::factory('cms_auth');
			$feed_token = $cms_user->getFeedToken($user_id);
			unset($cms_user);

			// If they don't have one, we should help them to generate it.
			if (!isset($feed_token)) {
				$rss = '<p><a href="/nterchange/rss/generate_feed_token?redirect=' . urlencode('/nterchange/audit_trail/viewlist') . '">Click here to generate a private RSS feed</a></p>';
			} else {
				$rss = '<p><a href="/nterchange/rss/audit_trail?token=' . $feed_token . '">Private RSS Feed of Audit Trail Activity</a> - <a href="/nterchange/audit_trail/generate_feed_token">Regenerate Token</a></p>';
			}

			// Then show the link so that they can put it into their feed reader.
			$this->set('rss_feed', $rss);
		}
		unset($auth);
	}

	/**
	 * getAuditInfo - gets additional audit trail information by looking up foreign keys.
	 *
	 * @param	string	Name of a particular model.
	 * @param	int		Id of a record in that particular model.
	 * @return 	array 	Information related to $model_name and $id.
	 **/
	function getAuditInfo($model_name, $id) {
		// checks for deleted and non-deleted records using special field
		$info = false;
		$fctrl = &NController::singleton($model_name);
		if ($fctrl && $fmodel = &$fctrl->getDefaultModel()) {
			$fmodel->reset();
			if (!$fmodel->get($id)) {
				$fmodel->reset();
				$fields = $fmodel->fields();
				if (in_array('cms_deleted', $fields)) {
					$fmodel->cms_deleted = 1;
				}
				$fmodel->get($id);
			}
			$info = $fmodel->{$fmodel->primaryKey()}?$fmodel->toArray():false;
			if ($info && (!isset($info['_headline']) || !$info['_headline'])) {
				$info['_headline'] = $fmodel->makeHeadline();
			}
			unset($fmodel);
		}
		return $info;
	}

	/**
	 * insert - Actually insert a cms_audit_trail record. Must be logged in to nterchange
	 * 		for this to succeed.
	 * NOTE: If you need to log an audit trail record without being logged in (eg. timed content removal)
	 * there is an alternate method in the cms_audit_trail model.
	 *
	 * @param	array 	Required params - asset, asset_id, action_taken
	 * @return 	void
	 **/
	function insert($params=array()) {
		$this->_auth = new NAuth;
		if (empty($params)) return false;
		$required_params = array('asset', 'asset_id', 'action_taken');
		foreach ($required_params as $param) {
			if (!isset($params[$param])) return false;
		}
		$model = &$this->getDefaultModel();
		// apply fields in the model
		$fields = $model->fields();
		foreach ($fields as $field) {
			$model->$field = isset($params[$field])?$params[$field]:null;
		}
		$model->user_id = $this->_auth->currentUserID();
		$model->ip = NServer::env('REMOTE_ADDR');
		if (in_array('cms_created', $fields)) {
			$model->cms_created = $model->now();
		}
		if (in_array('cms_modified', $fields)) {
			$model->cms_modified = $model->now();
		}
		// set the user id if it's applicable and available
		if (in_array('cms_modified_by_user', $fields)) {
			$model->cms_modified_by_user = $this->_auth->currentUserID();
		}
		$model->insert();
	}

	/**
	 * actionToText - Convert an action_id to it's human readable explanation.
	 *
	 * @param	int		The id of the action.
	 * @return 	string 	What that id actually means.
	 **/
	function actionToText($action) {
		$txt = '';
		switch ($action) {
			case AUDIT_ACTION_INSERT:
				$txt = 'Inserted';
				break;
			case AUDIT_ACTION_UPDATE:
				$txt = 'Updated';
				break;
			case AUDIT_ACTION_DELETE:
				$txt = 'Deleted';
				break;
			case AUDIT_ACTION_CONTENT_ADDEXISTING:
				$txt = 'Added Existing Content';
				break;
			case AUDIT_ACTION_CONTENT_ADDNEW:
				$txt = 'Added New Content to a page';
				break;
			case AUDIT_ACTION_CONTENT_REMOVE:
				$txt = 'Removed Content from a page';
				break;
			case AUDIT_ACTION_WORKFLOW_START:
				$txt = 'Submitted Workflow';
				break;
			case AUDIT_ACTION_WORKFLOW_SUBMIT:
				$txt = 'Submitted Workflow';
				break;
			case AUDIT_ACTION_WORKFLOW_APPROVE:
				$txt = 'Approved Workflow';
				break;
			case AUDIT_ACTION_WORKFLOW_APPROVEPUBLISH:
				$txt = 'Approved & Published Workflow';
				break;
			case AUDIT_ACTION_WORKFLOW_APPROVEREMOVE:
				$txt = 'Approved & Removed Workflow Content';
				break;
			case AUDIT_ACTION_WORKFLOW_DECLINE:
				$txt = 'Declined Workflow';
				break;
			case AUDIT_ACTION_DRAFT_SAVE:
				$txt = 'Saved a Draft';
				break;
			case AUDIT_ACTION_DRAFT_DELETE:
				$txt = 'Deleted a Draft';
				break;
			case AUDIT_ACTION_DELETE_ASSET_VERSIONS:
				$txt = 'Removed all old content versions';
				break;
			case AUDIT_ACTION_LOGIN:
				$txt = 'Logged into nterchange';
				break;
		}
		return $txt;
	}

	// override all the crud functions so that no one can edit or manually create an audit trail
	// insert is already overridden above with a custom method
	function create() {
		$this->redirectTo('viewlist');
	}
	function edit() {
		$this->redirectTo('viewlist');
	}
	function update() {
		$this->redirectTo('viewlist');
	}
	function delete() {
		$this->redirectTo('viewlist');
	}

	function &getDefaultModel() {
		$model = &$this->loadModel('cms_audit_trail');
		return $model;
	}
}
?>
