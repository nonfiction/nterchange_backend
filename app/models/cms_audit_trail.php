<?php
require_once 'n_model.php';
require_once 'app/controllers/audit_trail_controller.php';
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
 * @category   Audit Trail Model
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsAuditTrail extends NModel {
	function __construct() {
		$this->__table = 'cms_audit_trail';
		$this->name = 'cms_audit_trail';
		$this->_order_by = 'cms_created ASC';
		// This is the fake user we use when the website removes timed content from the site.
		// It's to keep audit trail working.
		$this->website_user_id = 99999;
		$this->website_user_name = 'Website Robot';
		$this->website_user_email = 'website@' . $_SERVER['SERVER_NAME'];
		parent::__construct();
	}

	/**
	 * insert_audit_trail - This is only for timed_remove so that we don't 
	 * 	lose the audit_trail.
	 * Refactor: Duplication of audit_trail_controller->insert();
	 *
	 * @param	array 	Required params - asset, asset_id, action_taken
	 * @return 	void
	 **/
	function insert_audit_trail($params=array()) {
		if (empty($params)) return false;
		$required_params = array('asset', 'asset_id', 'action_taken');
		foreach ($required_params as $param) {
			if (!isset($params[$param])) return false;
		}
		$model = &NModel::factory($this->name);
		// apply fields in the model
		$fields = $model->fields();
		foreach ($fields as $field) {
			$model->$field = isset($params[$field])?$params[$field]:null;
		}
		$model->user_id = $this->website_user_id;
		$model->ip = NServer::env('REMOTE_ADDR');
		if (in_array('cms_created', $fields)) {
			$model->cms_created = $model->now();
		}
		if (in_array('cms_modified', $fields)) {
			$model->cms_modified = $model->now();
		}
		// set the user id if it's applicable and available
		if (in_array('cms_modified_by_user', $fields)) {
			$model->cms_modified_by_user = $this->website_user_id;
		}
		$model->insert();
	}

}
?>