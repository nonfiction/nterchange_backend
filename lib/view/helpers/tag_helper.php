<?php
require_once 'view/helper.php';
/**
 * Tag Helper
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Tag Helper
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class TagHelper {
	function tag($name, $options=array(), $open=false) {
		return "<$name" . TagHelper::_tag_options($options) . ($open?'>':' />');
	}

	function contentTag($name, $content, $options=array()) {
		return "<$name" . TagHelper::_tag_options($options) . '>' . htmlspecialchars($content, ENT_QUOTES) . "</$name>";
	}

	function _tag_options($options) {
		$cleaned_options = array();
		foreach ($options as $k=>$v) {
			if (!empty($v))
				$cleaned_options[$k] = $v;
		}
		$str = '';
		foreach ($cleaned_options as $k=>$v) {
			$str .= " $k=\"$v\"";
		}
		return $str;
	}

	function closeTags($string) {
		// match opened tags
		if(preg_match_all('/<([a-z\:\-]+)[ >]/', $string, $start_tags)) {
			$start_tags = $start_tags[1];
			// match closed tags
			if(preg_match_all('/<\/([a-z]+)>/', $string, $end_tags)) {
				$complete_tags = array();
				$end_tags = $end_tags[1];
				foreach($start_tags as $key => $val) {
					$posb = array_search($val, $end_tags);
					if(is_integer($posb)) {
						unset($end_tags[$posb]);
					} else {
						$complete_tags[] = $val;
					}
				}
			} else {
				$complete_tags = $start_tags;
			}
			$complete_tags = array_reverse($complete_tags);
			for($i = 0; $i < count($complete_tags); $i++) {
				$string .= '</' . $complete_tags[$i] . '>';
			}
		}
		return $string;
	}
}
?>