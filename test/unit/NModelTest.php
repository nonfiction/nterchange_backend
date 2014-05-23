<?php
/**
 * NModel Test
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category    NModel Test
 * @author      Tim Glen <tim@nonfiction.ca>
 * @copyright   2006-2007 nonfiction studios inc.
 * @license     http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version     SVN: $Id$
 * @link        http://www.nterchange.com/
 * @since       File available since Release 3.0
 */

class NModelTest extends PHPUnit_Framework_TestCase {
  var $db;        // db connection for helper methods
  var $model;     // model property to hold $model for testing

  function setUp() {
    $this->db = NDB::connect();
    $this->model = &NModel::factory('test_sample');
    $this->seed_test_sample();
  }

  function tearDown() {
    $this->clear_test_sample();
    unset($this->model);
  }

  function test_factory_should_return_object_for_valid_table() {
    $this->assertTrue(is_a($this->model, 'NModel'));
  }

  function test_factory_should_return_false_for_invalid_table() {
    $model = &NModel::singleton('foobarbaz');
    $this->assertFalse($model);
  }

  function test_model_table() {
    $t = $this->model->table();
    $this->assertEquals($t['id'],
                        N_DAO_INT + N_DAO_NOTNULL,
                        'Model->table[id] should have type N_DAO_INT + N_DAO_NOTNULL');
    $this->assertEquals($t['cms_headline'],
                        N_DAO_STR + N_DAO_NOTNULL,
                        'Model->table[cms_headline] should have type N_DAO_STR + N_DAO_NOTNULL');
    $this->assertEquals($t['the_int'],
                        N_DAO_INT + N_DAO_NOTNULL,
                        'Model->table[int] should have type N_DAO_INT + N_DAO_NOTNULL');
  }

  function test_getFieldType_should_return_proper_types() {
    $model = &NModel::singleton('test_sample');

    $varchar = $model->getFieldType('the_varchar');
    $this->assertTrue(($varchar == N_DAO_STR || $varchar == N_DAO_STR + N_DAO_NOTNULL));

    $text = $model->getFieldType('the_text');
    $this->assertTrue(($text == N_DAO_STR + N_DAO_TXT || $text == N_DAO_STR + N_DAO_TXT + N_DAO_NOTNULL));

    $blob = $model->getFieldType('the_blob');
    $this->assertTrue(($blob == N_DAO_BLOB || $blob == N_DAO_BLOB + N_DAO_NOTNULL));

    $tinyint = $model->getFieldType('the_tinyint');
    $this->assertTrue(($tinyint == N_DAO_INT + N_DAO_BOOL || $tinyint == N_DAO_INT + N_DAO_BOOL + N_DAO_NOTNULL));

    $int = $model->getFieldType('the_int');
    $this->assertTrue(($int == N_DAO_INT || $int == N_DAO_INT + N_DAO_NOTNULL));

    $float = $model->getFieldType('the_float');
    $this->assertTrue(($float == N_DAO_FLOAT || $float == N_DAO_FLOAT + N_DAO_NOTNULL));

    $datetime = $model->getFieldType('the_datetime');
    $this->assertTrue(($datetime == N_DAO_DATE + N_DAO_TIME || $datetime == N_DAO_DATE + N_DAO_TIME + N_DAO_NOTNULL));

    $date = $model->getFieldType('the_date');
    $this->assertTrue(($date == N_DAO_DATE || $date == N_DAO_DATE + N_DAO_NOTNULL));

    $time = $model->getFieldType('the_time');
    $this->assertTrue(($time == N_DAO_TIME || $time == N_DAO_TIME + N_DAO_NOTNULL));

    $year = $model->getFieldType('the_year');
    $this->assertTrue(($year == N_DAO_INT + N_DAO_DATE || $year == N_DAO_INT + N_DAO_DATE + N_DAO_NOTNULL));
  }

  function test_tableInfo_returns_array() {
    $info = $this->model->tableInfo($this->model->tableName());
    $this->assertTrue(is_array($info));
  }

  function test_find(){
    $this->clear_test_sample();
    $this->assertEquals($this->model->find(), 0, "Test table should be empty");
    $this->seed_test_sample();
    $this->model->reset();
    $this->assertEquals($this->model->find(), 2, "Seed adds two records, find returns count");
  }

  function test_return_of_find_and_save(){
    $this->model->cms_headline        = "Test Row Three";
    $this->model->the_text                = "Text content";
    $this->assertEquals($this->model->save(true), 3, "NModel::save returns id of new record");
    $this->model->reset();
    $this->assertEquals($this->model->find(), 3, "Two seed entries + one insert = 3");

    $records = $this->model->fetchAll(true);
    $this->assertEquals($records[2]['cms_headline'], "Test Row Three", "cms_headline saved properly");
  }

  function test_array_access(){
    $this->model->find(1);
    $this->model->fetch();
    $this->assertEquals($this->model['cms_headline'], "Test Row One");
  }

  function test_array_access_set(){
    $this->model->find();
    $this->model->fetch();
    $this->assertEquals('Test Row One', $this->model->cms_headline);
    $this->model['cms_headline'] = 'New Headline';
    $this->assertEquals(1, $this->model->save());
    $this->model->reset();
    $this->model->find();
    $this->model->fetch();
    $this->assertEquals('New Headline', $this->model->cms_headline);
  }

  function test_magic_methods(){
    $test_method_output = 'Test Row One This is text content';
    $this->model->find();
    $this->model->fetch();
    $this->assertEquals($test_method_output, $this->model->test_method());
    $this->assertEquals($test_method_output, $this->model->test_method);
  }

  function clear_test_sample(){
    $this->db->exec('TRUNCATE TABLE test_sample;');
  }

  function seed_test_sample(){
    $seed_file = BASE_DIR . '/test/fixtures/test_sample.sql';
    NDB::seed($this->db, $seed_file);
  }
}
?>
