<?php
require_once 'MDB2.php';
/**
 * The 'NDB' class is a wrapper for creating and destroying DB connections
 * Might add other capabilities in the future but probably not.
 * These methods should be called statically.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   DB Wrapper
 * @author     Tim Glen <tim@nonfiction.ca>, Andy VanEe <andy@nonfiction.ca>
 * @copyright  2003-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NDB {

  static $type = 'mysql';
  static $username = 'root';
  static $password = false;
  static $server = 'localhost';
  static $db = 'nterchange';

  static function serverDSN() {
    $type = getenv('DB_TYPE');
    $username = getenv('DB_SERVER_USERNAME');
    $password = getenv('DB_SERVER_PASSWORD');
    $server = getenv('DB_SERVER');
    $type     = $type     ? $type     : self::$type;
    $username = $username ? $username : self::$username;
    $password = $password ? $password : self::$password;
    $password = $password ? ':'.$password : '';
    $server   = $server   ? $server   : self::$server;

    return sprintf("%s://%s%s@%s", $type, $username, $password, $server);
  }

  static function dsn() {
    $db = getenv('DB_DATABASE');
    $db = $db ? $db : self::$db;
    return self::serverDSN().'/'.$db;
  }

  static function &connect($dsn=null) {
    static $instances;
    if (!isset($instances)) $instances = array();

    $dsn = $dsn ? $dsn : self::dsn();

    // SET instances key
    $key = md5($dsn);

    if (!isset($instances[$key])) {
      $db = MDB2::factory($dsn, array('portability'=>MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL, 'debug'=>0));
      if (MDB2::isError($db)) {
        $db->addUserInfo('line ' . __LINE__ . ' with dsn=' . $dsn);
        return $db;
      }
      $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
      $instances[$key] =& $db;
    }

    return $instances[$key];
  }

  function &disconnect(&$db) {
    return $db->disconnect();
  }


  /**
    * seed loads and executes sql from a file
    *
    * @access public
    * @param MDB2::connection &$connection
    * @param string $filename
    * @return string empty on success, error message on failure
   */

  static function seed(&$connection, $filename){
    if (! file_exists($filename)) {
      return("NDB::seed() error: Cannot find file '$filename' \n");
    }

    $raw_contents = file_get_contents($filename);

    // Remove comments
    $comment_patterns = array('/\/\*.*(\n)*.*(\*\/)?/', //C comments
                              '/\s*--.*\n/', //inline comments start with --
                              '/\s*#.*\n/', //inline comments start with #
                              );
    $contents = preg_replace($comment_patterns, "\n", $raw_contents);

    // Retrieve sql statements
    $statements = explode(";\n", $contents);
    $statements = preg_replace("/\s/", ' ', $statements);

    foreach ($statements as $query) {
      if (trim($query) != '') {
        $res = $connection->exec($query);
        if (PEAR::isError($res)) {
            die($res->getMessage());
        }
      }
    }
  }
}
?>
