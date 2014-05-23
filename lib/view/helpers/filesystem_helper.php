<?php
require_once 'view/helper.php';
require_once 'n_filesystem.php';
/**
 * Filesystem Helper
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Filesystem Helper
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class FilesystemHelper {
	function filesizeFormat($file_path) {
		if ($file_path = FilesystemHelper::_findFilePath($file_path)) {
			return NFilesystem::filesize_format(filesize($file_path));
		}
		return false;
	}

	function downloadTime($file_path) {
		if ($file_path = FilesystemHelper::_findFilePath($file_path)) {
			return NFilesystem::download_time(filesize($file_path));
		}
		return false;
	}

	function _findFilePath($file_path) {
		if (false == ($test_path = realpath($file_path))) {
			$file_path = realpath(NServer::env('DOCUMENT_ROOT') . $file_path);
		}
		return $file_path;
	}
	
	/*
	 * Helper to grab a download icon out of /images/icons if it exists.
	 * 
	 * Returns an image tag.
	 */
	function downloadIcon($file_path) {
		return NFilesystem::download_icon($file_path);
	}
}
?>
