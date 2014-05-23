<?php
require_once 'conf/conf.php';
/**
 * FtpMirror is a class to mirror your site's uploads through FTP.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	FTP File Upload Distribution
 * @author     	Darron Froese <darron@nonfiction.ca>
 * @copyright  	2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.1.16
 * @todo 		Delete folders.
 */

class FtpMirror extends Object {
	
	function __construct() {

	}
	
	function connect() {
		$this->connection = ftp_connect(MIRROR_HOSTNAME);
		$this->login_result = ftp_login($this->connection, MIRROR_USERNAME, MIRROR_PASSWORD);
		// Set it to a passive FTP connection.
		ftp_pasv($this->connection, true);
		// Check the connection.
		if ((!$this->connection) || (!$this->login_result)) {
			NDebug::debug('FTP Mirror connection has failed.' , N_DEBUGTYPE_INFO);
			exit;
		} else {
			NDebug::debug('FTP Mirror connection was successful.' , N_DEBUGTYPE_INFO);
		}
	}
	
	function disconnect() {
		if (ftp_quit($this->connection)) {
			NDebug::debug('FTP Mirror disconnected.' , N_DEBUGTYPE_INFO);
		}
	}
	
	function putFile($filename) {
		if ((!$this->connection) || (!$this->login_result)) {
			$this->connect();
		}
		$directories = dirname($filename);
		$file = basename($filename);
		$dir_array = explode('/', $directories);
		$empty = array_shift($dir_array);
		
		// Change into MIRROR_REMOTE_DIR.
		ftp_chdir($this->connection, MIRROR_REMOTE_DIR);
		
		// Create any folders that are needed.
		foreach ($dir_array as $dir) {
			// If it doesn't exist, create it.
			// Then chdir to it.
			if (@ftp_chdir($this->connection, $dir)) {
				// Do nothing.
			} else {
				if (ftp_mkdir($this->connection, $dir)) {
					ftp_chmod($this->connection, 0775, $dir);
					ftp_chdir($this->connection, $dir);
				} else {
					NDebug::debug('Cannot create a folder via ftp.' , N_DEBUGTYPE_INFO);
				}
			}
		}
		
		// Put the file into the folder.
		$full_path = $_SERVER['DOCUMENT_ROOT'] . $filename;
		if (ftp_put($this->connection, $file, $full_path, FTP_BINARY)) {
			ftp_chmod($this->connection, 0775, $file);
			NDebug::debug("FTP Mirror: $filename was uploaded successfully" , N_DEBUGTYPE_INFO);
		} else {
			NDebug::debug("FTP Mirror: $filename was NOT uploaded successfully" , N_DEBUGTYPE_INFO);
		}
	}
	
	function deleteFile($filename) {
		if ((!$this->connection) || (!$this->login_result)) {
			$this->connect();
		}
		// Take off the leading /
		$filename = eregi_replace('^/', '', $filename);
		
		// Change into MIRROR_REMOTE_DIR.
		ftp_chdir($this->connection, MIRROR_REMOTE_DIR);
		
		if (ftp_delete($this->connection, $filename)) {
			NDebug::debug("FTP Mirror: $filename WAS deleted successfully" , N_DEBUGTYPE_INFO);
		} else {
			NDebug::debug("FTP Mirror: $filename was NOT deleted successfully" , N_DEBUGTYPE_INFO);
		}
	}

	function synchronizeDirectory() {
		
	}

}

?>