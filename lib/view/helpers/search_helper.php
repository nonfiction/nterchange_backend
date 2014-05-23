<?php
include_once 'tag_helper.php';
require_once 'n_model.php';
/**
 * Search Helpers
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Search Helpers
 * @author     	Darron Froese <darron@nonfiction.ca>
 * @copyright  	2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.1
 */
class SearchHelper extends TagHelper {
	var $admin_only_fields = array('id', 'cms_active', 'cms_deleted', 'cms_draft', 'cms_created', 'cms_modified', 'cms_modified_by_user');

	function searchFieldListSelect($params) {
		$asset = $params['asset']?$params['asset']:null;
		$searched_field = $params['searched_field']?$params['searched_field']:null;
		if (isset($searched_field)) $searched_field = str_replace(" ", "_", strtolower($searched_field));
		$model = NModel::factory($asset);
		$fields = $model->fields();
		// Remove a bunch of fields if you're not an admin - makes it a little bit simpler.
		$auth = new NAuth();
		$current_user_level = $auth->getAuthData('user_level');
		unset($auth);
		// Preload for the search_field default.
		$acon = NController::factory('asset');
		$select = 'Search Field: <select name="search_field">';
		foreach ($fields as $field){
			if ($current_user_level < N_USER_ADMIN) {
				if (in_array($field, $this->admin_only_fields)) continue;
			}
			$select .= '<option value="' . $field . '"';
			if (isset($searched_field) && $searched_field == $field) {
				$select .= ' selected="selected"';
			} elseif (isset($model->search_field) && $field == $model->search_field && !$searched_field) {
				$select .= ' selected="selected"';
			} elseif (!isset($model->search_field) && $field == $acon->search_field && !$searched_field) {
				$select .= ' selected="selected"';
			}
			$select .= '>' . ucwords(str_replace('_', ' ', $field)) .'</option>';
		}
		$select .= '</select>';
		unset($model);
		unset($acon);
		print $select;
	}
}
?>
