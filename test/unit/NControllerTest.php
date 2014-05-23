<?php
/**
 * NController Test
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    NController Test
 * @author      Tim Glen <tim@nonfiction.ca>
 * @copyright   2006-2007 nonfiction studios inc.
 * @license     http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version     SVN: $Id$
 * @link        http://www.nterchange.com/
 * @since       File available since Release 3.0
 */

class NControllerTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    $_SERVER['REQUEST_METHOD'] = 'GET';
  }

  function tearDown() {}

  function test_controller_exists(){
    $c_exists = &NController::exists('page');
    $this->assertTrue($c_exists, 'Page Controller exists()');

    $c = &NController::singleton('page');
    $this->assertInstanceOf('PageController', $c);

    $c2 = &NController::singleton('page');
    $this->assertSame($c, $c2, "NController::singleton() returns a reference to the same object");
    unset($c2);

    $class_name = $c->getClassName('page');
    $this->assertEquals($class_name, "PageController", "getClassName returns proper name");

  }
}
