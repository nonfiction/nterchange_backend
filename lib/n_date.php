<?php
require_once 'Date.php';
/**
 * NDate provides an interface to useful date calculations (client to
 * server time and vice versa)
 *
 * Sample:
 * NDate::convertTimetoServer($date_str);
 * NDate::convertTimetoClient($date_str);
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Date Utilities
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class NDate extends Object {
	function &getClientTZ() {
		// if SITE_TIME_ZONE is present, use it, otherwise, best guess as to server's local time zone
		if (defined('SITE_TIME_ZONE') && Date_TimeZone::isValidID(SITE_TIME_ZONE)) {
			$client_tz = new Date_TimeZone(SITE_TIME_ZONE);
		} else {
			$client_tz = &Date_TimeZone::getDefault();
		}
		return $client_tz;
	}

	function convertTimeToClient($value, $format='') {
		if (is_object($value) && is_a($value, 'Date')) {
			$dateobj = &$value;
		} else if (is_string($value)) {
			// if the field is date/time, not_null and is "empty", then nullify it
			if ((preg_match('/^00:00(?::00)?/', $value)) || preg_match('/^0000-00-00 00:00(?::00)?/', $value)) {
				$value = null;
			}
		}
		if ($value) {
			if (!$format) {
				switch (1==1) {
					case preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d(?::\d\d)?$/', $value):
						$format = '%Y-%m-%d %H:%M:%S';
						break;
					case preg_match('/^\d\d\d\d-\d\d-\d\d$/', $value):
						$format = '%Y-%m-%d';
						break;
					case preg_match('/^\d\d:\d\d(?::\d\d)?$/', $value):
						$format = '%H:%M:%S';
						break;
					default:
						$format = '%Y-%m-%d %H:%M:%S';
				}
			}
			if (!isset($dateobj)) {
				$dateobj = new Date($value);
				$dateobj->setTZbyID('UTC');
			}
			$dateobj->convertTZ(NDate::getClientTZ());
			if (is_int($format)) {
				$value = $dateobj->format($format);
			} else {
				$value = $dateobj->format($format);
			}
			unset($dateobj);
		}
		return $value;
	}

	function convertTimeToUTC($value, $format='') {
		// if the field is date/time and is "empty", then it's false
		if ((preg_match('/^00:00(?::00)?/', $value)) || preg_match('/^0000-00-00 00:00(?::00)?/', $value)) {
			$value = false;
		}
		// no SITE_TIME_ZONE is specified
		if ($value) {
			$utc_tz = new Date_TimeZone('UTC');
			$client_tz = &NDate::getClientTZ();
			// if no format is specified, then it's a best guess
			if (!$format) {
				switch (1==1) {
					case preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d(?::\d\d)?$/', $value):
						$format = '%Y-%m-%d %H:%M:%S';
						break;
					case preg_match('/^\d\d\d\d-\d\d-\d\d$/', $value):
						$format = '%Y-%m-%d';
						break;
					case preg_match('/^\d\d:\d\d(?::\d\d)?$/', $value):
						$format = '%H:%M:%S';
						break;
					default:
						$format = '%Y-%m-%d %H:%M:%S';
				}
			}
			$dateobj = new Date($value);
			$dateobj->setTZ($client_tz);
			$dateobj->convertTZ($utc_tz);
			// if it's an int, then assume it's a DATE_FORMAT constant
			if (is_int($format)) {
				$value = $dateobj->getDate($format);
			} else {
				$value = $dateobj->format($format);
			}
			unset($server_tz);
			unset($client_tz);
			unset($dateobj);
		}
		return $value;
	}

	function validDateTime($value) {
		if ((preg_match('/^00:00(?::00)?/', $value)) || preg_match('/^0000-00-00$/', $value) || preg_match('/^0000-00-00 00:00(?::00)?/', $value)) {
			return false;
		} else if ((preg_match('/^\d\d:\d\d(?::\d\d)?/', $value)) || preg_match('/^\d\d\d\d-\d\d-\d\d$/', $value) || preg_match('/^\d\d\d\d-\d\d-\d\d \d\d:\d\d(?::\d\d)?/', $value)) {
			return true;
		}
		return false;
	}

	function now($format = DATE_FORMAT_ISO) {
		// this will force to the server time regardless of whether date() is returning UTC time or not
		$dateobj = new Date(gmdate('Y-m-d H:i:s'));
		$dateobj->setTZbyID('UTC');
		if (is_int($format)) {
			$date = $dateobj->getDate($format);
		} else if (is_string($format)) {
			$date = $dateobj->format($format);
		}
		unset($dateobj);
		return $date;
	}

	function dateToArray($date) {
		if (empty($date)) {
			$date = array();
		} elseif (is_scalar($date)) {
			if (!is_numeric($date)) {
				$value = strtotime($date);
			}
			// fill all possible values
			$arr = explode('-', date('w-d-n-Y-h-H-i-s-a-A-W', (int)$date));
			$date = array(
			'D' => $arr[0],
			'l' => $arr[0],
			'd' => $arr[1],
			'M' => $arr[2],
			'm' => $arr[2],
			'F' => $arr[2],
			'Y' => $arr[3],
			'y' => $arr[3],
			'h' => $arr[4],
			'g' => $arr[4],
			'H' => $arr[5],
			'i' => $arr[6],
			's' => $arr[7],
			'a' => $arr[8],
			'A' => $arr[9],
			'W' => $arr[10]
			);
		}
		return $date;
	}

	function arrayToDate($date_input, $timestamp = false) {
		// possible year values
		if (isset($date_input['Y'])) {
			$year = $date_input['Y'];
		} else if (isset($date_input['y'])) {
			$year = $date_input['y'];
		}
		// possible month values
		if (isset($date_input['F'])) {
			$month = $date_input['F'];
		} else if (isset($date_input['m'])) {
			$month = $date_input['m'];
		} else if (isset($date_input['M'])) {
			$month = $date_input['M'];
		} else if (isset($date_input['n'])) {
			$month = $date_input['n'];
		}
		// possible day values
		if (isset($date_input['d'])) {
			$day = $date_input['d'];
		} else if (isset($date_input['j'])) {
			$day = $date_input['j'];
		}
		// possible hour values
		if (isset($date_input['g'])) {
			$hour = $date_input['g'];
		} else if (isset($date_input['h'])) {
			$hour = $date_input['h'];
		} else if (isset($date_input['G'])) {
			$hour = $date_input['G'];
		} else if (isset($date_input['H'])) {
			$hour = $date_input['H'];
		}
		// possible am/pm values
		if (isset($date_input['a'])) {
			$ampm = $date_input['a'];
		} else if (isset($date_input['A'])) {
			$ampm = $date_input['A'];
		}
		// instantiate date object
		$datestr = '';
		if (isset($year) || isset($month) || isset($day)) {
			if (isset($year) && !empty($year)) {
				if (strlen($year) < 2) {
					$year = '0' . $year;
				}
				if (strlen($year) < 4) {
					$year = substr($year, 0, 2) . $year;
				}
			} else {
				$year = '0000';
			}
			if ($year != '0000' && isset($month) && !empty($month)) {
				if (strlen($month) < 2) {
					$month = '0' . $month;
				}
			} else {
				$month = '00';
			}
			if ($year != '0000' && $month != '00' && isset($day) && !empty($day)) {
				if (strlen($day) < 2) {
					$day = '0' . $day;
				}
			} else {
				$day = '00';
			}
			$datestr .= "$year-$month-$day";
		}
		if (isset($hour) || isset($date_input['i']) || isset($date_input['s'])) {
			// set the hour
			if (isset($hour) && !empty($hour)) {
				if (strlen($hour) < 2) {
					$hour = '0' . $hour;
				}
			} else {
				$hour = '00';
			}
			if (isset($ampm)) {
				if (strtolower($ampm) == 'pm' && strlen($hour) == 1) {
					$hour += 12;
				}
			}
			// set the minutes
			if (isset($date_input['i']) && !empty($date_input['i'])) {
				if (strlen($date_input['i']) < 2) {
					$date_input['i'] = '0' . $date_input['i'];
				}
			} else {
				$date_input['i'] = '00';
			}
			$datestr .= ($datestr != ''?' ':'') . "$hour:{$date_input['i']}";
			// set the seconds
			if (isset($date_input['s']) && !empty($date_input['s'])) {
				$datestr .= ':' . (strlen($date_input['s']) < 2?'0':'') . $date_input['s'];
			} else {
				$datestr .= ':00';
			}
		}
		// feed it into the date object
		$dateobj = new Date($datestr);
		// set the time zone
		$dateobj->setTZ(NDate::getClientTZ());
		// pull the string back out
		$datestr = $dateobj->getDate();
		unset($dateobj);
		return $datestr;
	}
}
?>
