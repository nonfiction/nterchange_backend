<?php
require_once 'conf/conf.php';
require_once 'n_model.php';
require_once 'n_filesystem.php';
/**
 * n_download.php - Option to create persistent URLs for uploaded files.
 *
 * For better mime type information: fileinfo PECL Extension
 *    Install: pecl install fileinfo
 *  http://pecl.php.net/package/Fileinfo
 * 
 * Example: /upload/media_element/media_file/1
 * 	This will serve the real URL which doesn't visibly with each uploaded version.
 *  This way, uploading of new versions of a file doesn't have
 *   to result in a broken URL from a remote link.
 *  This will have to be enabled in the conf.php and by using a view
 *   helper function named 'persistent_url':
 *
 *	<a href="{persistent_url file=`$media_file` id=`$id` field=media_file}">
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Persistent Download URLs for uploaded files.
 * @author     Darron Froese
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.1.13
 */
class NDownload extends Object {
	var $model;
	var $field;
	var $asset_id;

	/**
	 * cleanUrl - Clean up the passed url by removing UPLOAD_DIR and the leading /
	 *
	 * @param	string	The initial full URL.
	 * @return 	string
	 **/
	function cleanUrl($url) {
		// Clean up the URL a bit.
		$url = str_replace(UPLOAD_DIR, '', $url);
		$url = eregi_replace('^/', '', $url);
		return $url;
	}
	
	/**
	 * getAssetAttributes - Split the URL by /'s and return an array.
	 *
	 * @param	string	The cleaned url.
	 * @return 	array
	 **/
	function getAssetAttributes($url) {
		$url_parts = explode('/', $url);
		return $url_parts;
	}
	
	/**
	 * setAssetAttributes - Setting some class attributes: model, field and asset_id
	 *
	 * @param	array	An array from getAssetAttributes
	 **/
	function setAssetAttributes($url_parts) {
		$this->model = $url_parts[0];
		$this->field = $url_parts[1];
		$this->asset_id = $url_parts[2];
	}
	
	/**
	 * getAssetModelName - Returns the asset model name.
	 *
	 * @return 	string	The name of the model set in setAssetAttributes
	 **/
	function getAssetModelName() {
		return $this->model;
	}
	
	/**
	 * getFilePath - Returns the full path to the filename 
	 *					referenced in model/field/asset_id
	 *
	 * @return 	string	The filename from the db plus DOCUMENT_ROOT.
	 **/
	function getFilePath() {
		if (is_numeric($this->asset_id)) {
			$object = NModel::factory($this->model);
			$object->id = $this->asset_id;
			if ($object->find()) {
				while ($object->fetch()) {
					$filename = $object->{$this->field};
				}
				return DOCUMENT_ROOT . $filename;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * getFileName - Strips off of the path and just returns the filename.
	 *
	 * @param	string	A fully qualified filename.
	 * @return 	string 	Returns the filename only - not the path.
	 **/
	function getFileName($path) {
		$filename = basename($path);
		return $filename;
	}
	
	/**
	 * serveFile - Actually serves the file to the browser.
	 *
	 * @param	string	A fully qualified filename that needs to be sent to a browser.
	 **/
	function serveFile($file_path) {
		// Find out the mime type of that file.
		$mime_type = NFilesystem::getMimeType($file_path);
		// Need the filename for later on.
		$filename = $this->getFileName($file_path);
		// Serve the file out like normal.
		$fp = fopen($file_path, 'rb');
		// Send correct headers.
		header("Content-Type: $mime_type");
		header("Content-Length: " . filesize($file_path));
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		fpassthru($fp);
	}

}

?>