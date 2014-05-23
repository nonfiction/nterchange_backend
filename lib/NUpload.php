<?php

class NUploadException extends Exception {}

/**
 * Interface for handling static file uploads
 *
 * A subclass of NUpload must be attached as the upload handler before use. See
 * the files at NUpload/*.php for available handlers. A handler for local files
 * is as simple as this: NUpload::connect('NUpload_S3')
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Filesystem
 * @author     Andy VanEe <andy@nonfiction.ca>
 * @copyright  2013 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.2
 */
class NUpload {
  /**
   * $handler - handle to the concrete upload handler class
   * @var subclass of NUpload
   */
  private static $handler;

  /**
   * $prefix - used in building the path to the uploads
   * generally the format will be ROOT.$prefix.$filename
   * @var string
   */
  public static $prefix = 'upload';

  /**
   * NUpload::connect - Connect NUpload to the specified upload handler
   * @param string $handler the class name of the upload handler
   * @return null
   */
  public static function connect($handler) {
    if (getenv('UPLOAD_PREFIX')) {
      self::$prefix = getenv('UPLOAD_PREFIX');
    }

    if (!class_exists($handler)) {
      throw new Exception("Upload handler class doesn't exist", 1);
    }

    $handler = new $handler;

    if (!($handler instanceof NUpload)) {
      throw new Exception("Upload handler is not instance of NUpload: $handler", 1);
    }

    self::$handler = $handler;

    if (method_exists($handler, 'init')) {
      call_user_func(array(self::$handler, "init"), null);
    }

  }

  /**
   * NUpload::moveUpload - move a local file to the desired path
   *
   * @param  string $src     Full path to the source file
   * @param  string $target  Relative path to the destination (be sure it's sanitized)
   * @return string URL to the file, fully qualified if not saved to the host server
   */
  public static function moveUpload($src, $target) {
    if (!self::$handler) self::noInstanceError();
    self::debug("Moving file: $src to $target");
    return call_user_func(array(self::$handler, "moveUpload"), $src, $target);
  }

  /**
   * NUpload::deleteUpload - remove an uploaded file
   *
   * @param  string $url  The url of the file, subclasses must handle stripping
   * prefixes or prepending the document root.
   * @return boolean  false only if file exists but cannot be deleted
   */
  public static function deleteUpload($url) {
    if (!self::$handler) self::noInstanceError();
    return call_user_func(array(self::$handler, "deleteUpload"), $src, $target);
  }

  public static function debug(
    $message, $t=N_DEBUGTYPE_INFO, $p=PEAR_LOG_DEBUG, $z = false) {
    NDebug::debug($message, $t, $p, $z);
  }

  private static function noInstanceError(){
    throw new NUploadException("NUpload instance not connected", 1);
  }
}
