<?php
include_once 'n_server.php';
/**
 * 	Some basic filesystem functionality, methods can all be called statically
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Filesystem
 * @author     Tim Glen <tim@nonfiction.ca>
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NFilesystem {
	/**
	 * cleanFileName - Clean up and standardize any filename passed to it.
	 *
	 * @param	string	A raw filename ready to be cleaned.
	 * @return 	string	A cleaned up filename.
	 **/
	function cleanFileName($str) {
		$badchars = array('`','~','!','@','#','$','%','^','&','*','(',')','=','+',';',':','\'','\\','"',',','<','>','/','?');
		// spaces-to-dashes, multiple-space-to-single, remove badchars
		return str_replace(' ', '-', trim(preg_replace('/ +/', ' ', strtolower(str_replace($badchars, '', $str)))));
	}

	/**
	 * buildDirs - Recursively build a directory structure.
	 *
	 * @param	string	Path that needs to be created.
	 * @param	string	Folder permissions in octal format.
	 * @return 	boolean
	 **/
	function buildDirs($dir, $mode = 0755) {
		if (is_dir($dir) || @mkdir($dir, $mode)) return true;
		if (!NFilesystem::buildDirs(dirname($dir), $mode)) return false;
		return @mkdir($dir, $mode);
	}

	/**
	 * deleteFile - Delete a file - locally and remotely if required.
	 *
	 * @param	string	A filename - NOT fully qualified.
	 * @return 	boolean
	 **/
	function deleteFile($filename) {
		$full_path = DOCUMENT_ROOT . $filename;
		if (file_exists($full_path)) {
			if (unlink($full_path)) {
				NDebug::debug("$full_path was deleted." , N_DEBUGTYPE_INFO);
				// Delete the file from the mirror server.
				if (defined('MIRROR_SITE') && MIRROR_SITE) {
					require_once 'n_mirror.php';
					$mirror = NMirror::getInstance();
					$mirror->connect();
					$mirror->deleteFile($filename);
					$mirror->disconnect();
					unset($mirror);
				}
				return true;
			} else {
				NDebug::debug("$full_path was NOT deleted." , N_DEBUGTYPE_INFO);
				return false;
			}
		} else {
			NDebug::debug("$full_path was not found or already deleted." , N_DEBUGTYPE_INFO);
			return false;
		}
	}

	/**
	 * deleteFolder - Deletes a folder
	 *
	 * @param	string	The full path to the folder.
	 **/
	function deleteFolder($folder) {
		if (rmdir($folder)) {
			NDebug::debug("Deleted $folder." , N_DEBUGTYPE_INFO);
		} else {
			NDebug::debug("Could NOT delete $folder." , N_DEBUGTYPE_INFO);
		}
	}

	/**
	 * deletePathRecursive - Deletes a file/folder and all it's contents
	 *
	 * @param   string   The file or folder to delete
	 * @return  boolean  True if successfully deleted
	 */
	function deletePathRecursive($path){
		$path = str_replace(DOCUMENT_ROOT, '', $path);
		$path = DOCUMENT_ROOT.$path;
		if (is_dir($path)){
			if (substr($path, -1) != '/') $path = $path.'/';
			$files = glob($path . '*', GLOB_MARK);
			$empty = true;
			foreach ($files as $file) {
				if (!self::deletePathRecursive($file)) { $empty = false; }
			}
			if ($empty) { return rmdir($path); }
			else        { return false; }
		}
		if (is_file($path)) { return unlink($path); }
		// Not a file or a directory?
		NDebug::debug("Unable to remove: ".$path , N_DEBUGTYPE_INFO);
		return false;
	}

	/**
	 * download_icon - Called from a Smarty template to return a string with an icon
	 * 					image link.
	 *
	 * @param	array	A path to a filename.
	 * @return	string	An img tag with a particular image for the filetype passed.
	 **/
	function download_icon($params) {
		$extension = NFilesystem::getExtension($params['file']);
		$icon = '/images/icons/' . $extension . '.png';
		$icon_path = DOCUMENT_ROOT . $icon;
		if (file_exists($icon_path)) {
			return AssetTagHelper::imageTag($icon, 'Download');
		} else {
			return AssetTagHelper::imageTag('/images/icons/default.png', 'Download');
		}
	}

	/**
	 * getExtension - returns the extension for a particular fully qualified filename.
	 *
	 * @param	string	A fully qualified filename.
	 * @return 	string	The file extension.
	 **/
	function getExtension($filename) {
		preg_match("/\.[\w]{1,6}$/i", $filename, $matches);
		$extension = array_pop($matches);
		$extension = str_replace('.', '', $extension);
		return $extension;
	}

	/**
	 * getMimeType - Get the MIME type for a fully qualified filename.
	 *		Uses the PECL fileinfo extension if available.
	 *
	 * @param	string	A fully qualified filename.
	 * @return 	string	The MIME type for that file.
	 **/
	function getMimeType($filename) {
		// Use the fileinfo pecl extension if it's available.
		if (function_exists('finfo_open')) {
			$handle = finfo_open(FILEINFO_MIME);
			$mime_type = finfo_file($handle, $filename);
			return $mime_type;
		} else {
			$extension = NFilesystem::getExtension($filename);
			switch($extension) {
				case "jpg":
					return "image/jpeg";
				case "jpeg":
					return "image/jpeg";
				case "gif":
					return "image/gif";
				case "png":
					return "image/png";
				case "pdf":
					return "application/pdf";
				case "txt":
					return "text/plain";
				case "doc":
					return "application/msword";
				case "xls":
					return "application/vnd.ms-excel";
				case "ppt":
					return "application/vnd.ms-powerpoint";
				case "css":
					return "text/css";
				case "js":
					return "application/x-javascript";
				case "html":
					return "text/html";
				case "xhtml":
					return "application/xhtml+xml";
				case "zip":
					return "application/zip";
				default:
					return "application/octet-stream";
			}
		}
	}

	/**
	 * filesize_format - Called from a Smarty template to return a human readable
	 *		representation of the size of the file.
	 *
	 * @param	int		Size of the file in bytes.
	 * @return 	string	Size of the file in human readable format.
	 **/
	function filesize_format($bytes) {
		if (0 >= $bytes) {
			return $bytes;
		}
		$names = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
		$values = array(1, 1024, pow(1024,2), pow(1024,3), pow(1024,4), pow(1024,5), pow(1024,6), pow(1024,7), pow(1024,8));
		$i = floor(log($bytes)/6.9314718055994530941723212145818); //log(1024) = 6.9314718055994530941723212145818
		return number_format($bytes/$values[$i]) . ' ' . $names[$i];
	}

	/**
	 * download_time - Called from a Smarty template to return the number of seconds
	 *		the file will take to download.
	 *
	 * @param	int		Size of the file in bytes.
	 * @param	int		Speed in K to estimate download time.
	 * @return 	int		Seconds (estimated) it will take to download the file.
	 **/
	function download_time($bytes, $speed_in_k = 56) {
		if (0 >= $bytes) {
			return $bytes;
		}
		$k = round($bytes/1024, 0);
		$seconds = round($k/($speed_in_k/10));
		return $seconds;
	}
}
?>
