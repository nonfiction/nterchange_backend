<?php
/**
 * AuditTrailController Test
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    AuditTrailController Test
 * @author      Andy VanEe <andy@nonfiction.ca>
 * @copyright   2011 nonfiction studios inc.
 * @license     http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version     SVN: $Id$
 * @link        http://www.nterchange.com/
 * @since
 */

class AuditTrailControllerTest extends PHPUnit_Framework_TestCase {
  function setUp() {}

  function tearDown() {}

  function test_default_date_start_end(){

    $date = AuditTrailController::dateStartEnd();

    $this->assertEquals($date['used'], date('Y-m-d'), 'Empty args should use today');
    $this->assertEquals($date['month'], false, 'Default should be single day');

    $today_start = NDate::convertTimeToUTC(date('Y-m-d') . ' 00:00:00', '%Y-%m-%d %H:%M:%S');
    $this->assertEquals($date['start'], $today_start, 'Default start should be UTC version of day start');
    $today_end = NDate::convertTimeToUTC(date('Y-m-d') . ' 23:59:59', '%Y-%m-%d %H:%M:%S');
    $this->assertEquals($date['end'], $today_end, 'Default end should be UTC version of day end');
  }

  function test_monthly_date_start_end(){
    $day = false;
    $month = 10;
    $year = 2000;

    $month_arg = array('Y'=>$year, 'F'=>$month, 'd'=>$day);
    $date = AuditTrailController::dateStartEnd($month_arg);

    $this->assertEquals($date['used'], "$year-$month-1", 'Month used should match first day of month given eg: 2000-10-01');
    $this->assertEquals($date['month'], true, 'With day set to false, should set the month flag to true');

    $month_start = NDate::convertTimeToUTC(date('Y-m-d', strtotime("$year-$month-1")) . ' 00:00:00', '%Y-%m-%d %H:%M:%S');
    $this->assertEquals($date['start'], $month_start, 'Month start should be UTC version of first day of month start');
    $month_len = date('t', strtotime("$year-$month-1"));
    $month_end = NDate::convertTimeToUTC(date('Y-m-d', strtotime("$year-$month-$month_len")) . ' 23:59:59', '%Y-%m-%d %H:%M:%S');
    $this->assertEquals($date['end'], $month_end, 'Month end should be UTC version of last day of month end');
  }

}
?>
