<?php

class NDispatcherTest extends PHPUnit_Framework_TestCase {
  var $db = null; // db connection for helper methods
  var $model = null; // model property to hold $model for testing
  var $text_data = null; //test data loaded from an external file

  function setUp(){
    $_SERVER['REQUEST_URI'] = '/page/edit/1';
    $_SERVER['PATH_INFO'] = '/page/edit/1';
    $_SERVER['QUERY_STRING'] = '';
    $_SERVER['REMOTE_ADDR'] = '';
    $_SERVER['REQUEST_METHOD'] = 'GET';
  }

  function tearDown(){}

  function test_dispatcher_setup(){
    $uri = NServer::setUri();
    $dispatcher = &new NDispatcher($uri);
    $dispatcher->setParams($uri);
    $this->assertEquals($dispatcher->controller, "page", "Controller set from URI");
    $this->assertEquals($dispatcher->action, "edit", "Action set from URI");
    $this->assertEquals($dispatcher->parameter, "1", "Parameter set from URI");

    $_SERVER['REQUEST_URI'] = '/bogus/action/999999';
    $_SERVER['PATH_INFO'] = '/bogus/action/999999';
    $uri = NServer::setUri();
    $dispatcher = &new NDispatcher($uri);
    $dispatcher->setParams($uri);
    $this->assertEquals($dispatcher->controller, "bogus", "Bogus Controller allowed by dispatcher");
    $this->assertEquals($dispatcher->action, "action", "Bogus Action allowed by dispatcher");
    $this->assertEquals($dispatcher->parameter, "999999", "Bogus Parameter allowed by dispatcher");
  }
}
?>
