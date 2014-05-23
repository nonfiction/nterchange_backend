<?php
require_once 'mirror/ftp_mirror.php';
require_once 'mirror/s3_mirror.php';
require_once 'mirror/rsync_mirror.php';
/**
 * NMirror is a base class to define how to mirror uploaded files
 *   transparently.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   File Upload Distribution
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.1.16
 */
class NMirror extends Object {
	
	function __construct() {

	}
	
	/**
	 * getInstance - Returns an instance object of a Mirror child class.
	 *		Depends on what is set for MIRROR_TYPE in the conf/conf.php
	 *
	 * @return 	object	
	 **/
	function getInstance() {
		if (defined('MIRROR_TYPE') && MIRROR_TYPE == 'ftp') {
			return new FtpMirror();
		} elseif (defined('MIRROR_TYPE') && MIRROR_TYPE == 's3') {
			return new S3Mirror();
		} elseif (defined('MIRROR_TYPE') && MIRROR_TYPE == 'rsync') {
			return new RsyncMirror();
		} else {
			return false;
		}	
	}
	
	/**
	 * connect - connect to the remote server
	 *
	 * @return void
	 **/
	function connect() {
		
	}

	/**
	 * disconnect - disconnect from the remote server
	 *
	 * @return void
	 **/	
	function disconnect() {
		
	}
	
	/**
	 * putFile - Put a file on the remote server.
	 *
	 * @return void
	 **/
	function putFile() {
		
	}
	
	/**
	 * deleteFile - Delete a file from the remote server.
	 *
	 * @return void
	 **/
	function deleteFile() {
		
	}
	
	/**
	 * synchronizeDirectory - Synchronize an entire directory from local to remote.
	 *
	 * @return void
	 **/
	function synchronizeDirectory() {
		
	}

}

?>