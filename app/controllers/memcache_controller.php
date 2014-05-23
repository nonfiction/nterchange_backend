<?php
require_once 'app_controller.php';
/**
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Memcache Smarty Cache
 * @author     	Darron Froese <darron@nonfiction.ca>
 * @copyright  	2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since		Version 3.1.17
 * @todo 		Expose this if you're actually running a memcached server.
 */
class MemcacheController extends AppController {
	function __construct() {
		$this->login_required = true;
		parent::__construct();
	}
	
	/**
	 * index - Just show the memcache server's statistics.
	 *
	 * @return void
	 **/
	function index() {
		$this->auto_render = true;
		$memcache_obj = new Memcache;
		$memcache_obj->addServer(MEMCACHED_SERVER, MEMCACHED_SERVER_PORT);
		$stats = $memcache_obj->getExtendedStats();
		varDump($stats);
	}
}
?>