<?php
require_once 'n_model.php';
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
 * @category   Redirect Model
 * @author     Tim Glen <tim@nonfiction.ca>
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class Redirect extends NModel {
	function __construct() {
		$this->__table = 'redirect';
		$this->form_ignore_fields[] = 'count';
		$this->not_connectable = true;
		$this->form_rules[] = array('url', 'This is not a unique url', 'callback', array(&$this, 'validate_url'));
		parent::__construct();
	}
	
	/**
	 * validate_url - Each URL must be unique. Having duplicates is a problem.
	 * 		This is called because it's set in $this->form_rules.
	 *
	 * @param	string	The value of the $url field.
	 * @return 	boolean
	 **/
	function validate_url($value) {
		$this->url = $value;
		if ($this->find()) {
			return false;
		} else {
			return true;
		}
	}

}
?>