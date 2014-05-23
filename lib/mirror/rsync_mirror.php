<?php
/**
 * RsyncMirror is a class to mirror your site's uploads through Rsync.
 *
 * If you want to use SSH, you must use a public/private keypair.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Rsync File Upload Distribution
 * @author     	Darron Froese <darron@nonfiction.ca>
 * @copyright  	2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.1.16
 * @todo 		This doesn't work yet.
 * @todo 		Need to create folder path in order for this to work. Patches welcome.
 */

class RsyncMirror extends Object {


	function __construct() {
		$this->rsync_binary = '/usr/bin/rsync ';

		// Set SSH specific options as well as dealing with a public_key if available.
		$this->private_key = CONF_DIR . "/ssh_private_key";

		if (defined('MIRROR_RSYNC_SSH') && MIRROR_RSYNC_SSH) {
			$this->options = '-v -e "ssh';
			if (file_exists($this->private_key)) {
				$this->options .= ' -i ' . $this->private_key . '" ';
			} else {
				// Do nothing, needs a public key to work properly.
			}
		}

		// I think we need this - otherwise we can't be sure the permissions
		// are good enough to read the file by the web server.
		// $this->options .= '--chmod=a+rx -t -p ';
		$this->options .= '-t -p -r ';
		$this->exclude = '--exclude "CVS" --exclude ".svn" ';
		$this->ssh_connection_details = MIRROR_USERNAME . '@' . MIRROR_HOSTNAME . ':' . MIRROR_REMOTE_DIR;
		// TODO: Rsync connection details.

	}

	function connect() {

	}

	function disconnect() {

	}

	function putFile($filename) {
		$full_path_filename = $_SERVER['DOCUMENT_ROOT'] . $filename;
		if (defined('MIRROR_RSYNC_SSH') && MIRROR_RSYNC_SSH) {
			$command = $this->rsync_binary . $this->options . $this->exclude . $full_path_filename . ' ' . $this->ssh_connection_details . eregi_replace('^/', '', $filename);
			// TODO: This doesn't work yet - can't create all the directories from the top down.
			exec($command, $output, $return_var);
		} else {
			// Set the password as an environment variable.
			$this->password = MIRROR_PASSWORD;
			putenv ("RSYNC_PASSWORD=$this->password");

			// OR if you're using RSYNC without ssh, set the password in this file.
			$this->password_file = CONF_DIR . "/rsync_password";

			if (file_exists($this->password_file)) {
				$this->options .= '--password-file=' . $this->password_file . ' ';
			}
			// Do an rsync specific command here.
		}
	}

	function deleteFile($filename) {

	}

	function synchronizeDirectory($directory=null) {
		if (!$directory) {
			$directory = $_SERVER['DOCUMENT_ROOT'];
		}
		// TODO: Synchronize the entire root directory.
	}

}

?>
