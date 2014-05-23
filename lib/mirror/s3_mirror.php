<?php
require_once 'conf/conf.php';
require_once 'vendor/s3.class.php';
/**
 * S3Mirror is a class to mirror your site's uploads through Amazon's S3 service.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   S3 File Upload Distribution
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.1.16
 */

class S3Mirror extends Object {
	
	function __construct() {

	}
	
	function connect() {
		
	}
	
	function disconnect() {
		
	}
	
	function putFile($filename) {
		$s3svc = new S3();
		// Removing the first slash is important - otherwise the URL is different.
		$aws_filename = eregi_replace('^/', '', $filename);
		$filename = $_SERVER['DOCUMENT_ROOT'] . $filename;
		$mime_type = NFilesystem::getMimeType($filename);

		// Read the file into memory.
		$fh = fopen( $filename, 'rb' );
		$contents = fread( $fh, filesize( $filename ) );
		fclose( $fh );
		
		$s3svc->putBucket( MIRROR_S3_BUCKET );
		$out = $s3svc->putObject( $aws_filename, $contents, MIRROR_S3_BUCKET, 'public-read', $mime_type );
		
		// Now the file is accessable at:
		//		http://MIRROR_S3_BUCKET.s3.amazonaws.com/put/the/filename/here.jpg 	OR
		// 		http://s3.amazonaws.com/MIRROR_S3_BUCKET/put/the/filename/here.jpg

		unset($s3svc);
	}
	
	function deleteFile($filename) {
		$s3svc = new S3();
		// Removing the first slash is important - otherwise the URL is different.
		$aws_filename = eregi_replace('^/', '', $filename);
		$s3svc->deleteObject($aws_filename, MIRROR_S3_BUCKET);
		unset($s3svc);
	}

	function synchronizeDirectory() {
		
	}

}

?>