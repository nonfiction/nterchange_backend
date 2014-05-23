<?php
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
 * @category   Template Asset Administration Helpers
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsAssetTemplateHelper{
	var $file_warning = '<span class="notfound">&nbsp;File not found&nbsp;</span>';
	
	function function_check_asset_template($params) {
		$filename = $params['filename'];
		$asset = $params['asset'];
		$template_check = &NController::factory('cms_asset_template');
		$result = $template_check->doesAssetTemplateExist($filename, $asset);
		if (!$result) print $this->file_warning;
		unset($template_check);
	}
	
	function function_check_template($params) {
		$filename = $params['filename'];
		$template_check = &NController::factory('page_template');
		$result = $template_check->doesPageTemplateExist($filename);
		if (!$result) print $this->file_warning;
		unset($template_check);
	}
	
	function function_check_asset_template_use($params) {
		$asset = $params['asset'];
		$container_id = $params['container_id'];
		$check = &NController::factory('page_content');
		$result = $check->checkAssetContainerUsage($asset, $container_id);
		if ($result > 0) print '&nbsp;(' . $result . ' uses)';
	}
}
?>