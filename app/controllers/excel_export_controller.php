<?php
require_once 'admin_controller.php';
/**
 * Excel Export for Assets - Automatically allowing an Excel export of any asset.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Excel Export for Assets
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class ExcelExportController extends AdminController {
	var $default_foreign_keys = array('cms_modified_by_user'=>array('cms_auth', 'real_name'));
	var $default_field_exclusions = array('id'=>true, 'cms_active'=>true, 'cms_draft'=>true, 'cms_deleted'=>true, 'cms_headline'=>true);

	function __construct() {
		$this->name = 'excel_export';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_EDITOR;
		$this->login_required = true;
		$this->page_title = 'Excel Export';
		parent::__construct();
	}

	function export($model_name) {
		if (isset($model_name)) {
			$model = NModel::factory($model_name);
			// Foreign Key Lookup Support
			if (isset($model->excel_export)) {
				$model_foreign_keys = $model->excel_export;
				// Default standard foreign keys get added and merged here.
				$foreign_keys = array_merge($this->default_foreign_keys, $model_foreign_keys);
			} else {
				$foreign_keys = $this->default_foreign_keys;
			}
			// Field Inclusion and Exclusion Support
			if (isset($model->excel_exclude_fields)) {
				$model_excel_inclusions = $model->excel_exclude_fields;
				$field_exclusions = array_merge($this->default_field_exclusions, $model_excel_inclusions);
			} else {
				$field_exclusions = $this->default_field_exclusions;
			}
			// If $_GET['search'] is set, only export those items.
			$search = isset($_GET['search'])?$_GET['search']:null;
			$search_field = isset($_GET['search_field'])?$_GET['search_field']:null;
			if (isset($search) && $search != null) {
				if (!$search_field && $search_field != null) {
					$acon = NController::factory('asset');
					$search_field = isset($model->search_field)?$model->search_field:$acon->search_field;
					unset($acon);
				}
			}
			$options = $search?array('conditions'=>"$search_field LIKE '%$search%'"):array();
			// Can set options in the model about items exported to the Excel.
			// Only export items that meet a certain criteria - not everything in the list.
			// For example: $this->viewlist_options = array('conditions'=>"cms_modified_by_user = '4'");
			if (isset($model->viewlist_options)) {
				foreach ($model->viewlist_options as $key => $val) {
					if (isset($options[$key])) {
						$options[$key] .= ' AND ' . $val;
					} else {
						$options[$key] = "$val";
					}
				}
			}

			if ($model->find($options)) {
				$fields = $model->fields();
				// Add additional custom fields here from the model file.
				if (isset($model->excel_extra_fields)) {
					foreach ($model->excel_extra_fields as $key => $value) {
						$fields[] = $key;
					}
				}
				require_once 'Spreadsheet/Excel/Writer.php';

				// Creating a workbook
				$workbook = new Spreadsheet_Excel_Writer();
				$worksheet =& $workbook->addWorksheet(ucwords(str_replace('_', ' ', $model_name)));
				$worksheet->setColumn(2, 4, 20);
				$worksheet->setColumn(7, 7, 15);
				$worksheet->setColumn(10, 28, 20);

				// Make the title line look a little different
				$title =& $workbook->addFormat();
				$title->setBold();
				$title->setAlign('center');
				$title->setBottom(2);

				// Let's add the field names to the title line.
				// Leave out a few.
				$x = 0;
				$worksheet->setRow(0, 18.75);
				foreach ($fields as $field) {
					$exclude_this = array_key_exists($field, $field_exclusions);
					if ($exclude_this && $field_exclusions[$field] == true) {
						// do nothing
					} else {
						$worksheet->write(0, $x, ucwords(str_replace('_', ' ', $field)), $title);
						$x++;
					}
				}

				// Now here comes the data.
				$y = 1;
				while ($model->fetch()) {
					$item = $model->toArray();
					// For reference while we're working with things.
					$original_item = array();
					$original_item = $item;
					$x = 0;
					$worksheet->setRow($y, 18.75);
					foreach ($fields as $field) {
						$exclude_this = array_key_exists($field, $field_exclusions);
						if ($exclude_this && $field_exclusions[$field] == true) {
							// do nothing
						} else {
							// Look for foreign keys and replace if assigned.
							foreach($foreign_keys as $foreign_key => $foreign_key_value) {
								if ($field == $foreign_key) {
									$fk_model_name = $foreign_key_value[0];
									$fk_model_headline = $foreign_key_value[1];
									$fk_model = NModel::factory($fk_model_name);
									if ($fk_model && ($fk_model->get($item[$field]))) {
										$item[$field] = $fk_model->{$fk_model_headline};
									}
									unset($fk_model);
								}
							}

							//Look for bitmask fields and replace with string value instead of numeric total
							if (is_array($model->bitmask_fields) && count($model->bitmask_fields)) {
								$bitmask_keys = array_keys($model->bitmask_fields);
								if (in_array($field, $bitmask_keys)) {
									$bitmask_total = $item[$field];
									$value_str = '';
									$i = 0;
									foreach($model->bitmask_fields[$field] as $bit=>$val) {
										if($bit & $bitmask_total) {
											if($i > 0) {
												$value_str .= ', ';
											}
											$value_str .= $val;
											$i ++;
										}
									}
									$item[$field] = $value_str;
								}
							}

							// Any extra fields get dealt with here.
							if (isset($model->excel_extra_fields)) {
								foreach ($model->excel_extra_fields as $key => $value) {
									if ($field == $key) {
										$extra_name = $value[0];
										$extra_attribute = $value[1];
										$extra_key = $value[2];
										$extra_info = NModel::factory($extra_name);
										if (method_exists($extra_info, $extra_attribute)) {
											$item[$field] = $extra_info->$extra_attribute($original_item["$extra_key"]);
										} else {
											$extra_info->get($original_item["$extra_key"]);
											$item[$field] = $extra_info->$extra_attribute;
										}
										unset($extra_info);
									}
								}
							}
							// If it's an uploaded file, put the address in the conf.php before it so that it
							// turns into a link in Excel.
							if (eregi(UPLOAD_DIR, $item[$field])) {
								$item[$field] = PUBLIC_SITE . ereg_replace("^/", "", $item[$field]);
							}
							$worksheet->write($y, $x, $this->convert_characters($item[$field]));
							$x++;
						}
					}
					$y++;
					unset($original_item);
					unset($item);
				}
				// sending HTTP headers
				$xls_filename = $model_name . '_entries.xls';
				$workbook->send($xls_filename);
				$workbook->close();
			}
		}
	}

	function convert_characters($text) {
		$dict  = array(chr(138) => 'Š', chr(141) => '', chr(142) => 'Ž', chr(145) => "'", chr(146) => "'",
										chr(147) => '"', chr(148) => '"', chr(150) => '-', chr(151) => '-', chr(154) => 'š',
										chr(157) => '', chr(158) => 'ž', chr(160) => ' ', chr(161) => '¡', chr(173) => '­',
										chr(188) => '¼', chr(189) => '½', chr(190) => '¾', chr(191) => '¿', chr(192) => 'À',
										chr(193) => 'Á', chr(194) => 'Â', chr(195) => 'Ã', chr(196) => 'Ä', chr(197) => 'Å',
										chr(198) => 'Æ', chr(199) => 'Ç', chr(200) => 'È', chr(201) => 'É', chr(202) => 'Ê',
										chr(203) => 'Ë', chr(204) => 'Ì', chr(205) => 'Í', chr(206) => 'Î', chr(207) => 'Ï',
										chr(209) => 'Ñ', chr(210) => 'Ò', chr(211) => 'Ó', chr(212) => 'Ô', chr(213) => 'Õ',
										chr(214) => 'Ö', chr(216) => 'Ø', chr(217) => 'Ù', chr(218) => 'Ú', chr(219) => 'Û',
										chr(220) => 'Ü', chr(221) => 'Ý', chr(223) => 'ß', chr(224) => 'à', chr(225) => 'á',
										chr(226) => 'â', chr(227) => 'ã', chr(228) => 'ä', chr(229) => 'å', chr(230) => 'æ',
										chr(231) => 'ç', chr(232) => 'è', chr(233) => 'é', chr(234) => 'ê', chr(235) => 'ë',
										chr(236) => 'ì', chr(237) => 'í', chr(238) => 'î', chr(239) => 'ï', chr(241) => 'ñ',
										chr(242) => 'ò', chr(243) => 'ó', chr(244) => 'ô', chr(245) => 'õ', chr(246) => 'ö',
										chr(248) => 'ø', chr(249) => 'ù', chr(250) => 'ú', chr(251) => 'û', chr(252) => 'ü',
										chr(253) => 'ý', chr(255) => 'ÿ',

										'&#138;' => 'Š', '&#141;' => '', '&#142;' => 'Ž', '&#145;' => "'", '&#146;' => "'",
										'&#147;' => '"', '&#148;' => '"', '&#150;' => '-', '&#151;' => '-', '&#154;' => 'š',
										'&#157;' => '', '&#158;' => 'ž', '&#160;' => ' ', '&#161;' => '¡', '&#173;' => '­',
										'&#188;' => '¼', '&#189;' => '½', '&#190;' => '¾', '&#191;' => '¿', '&#192;' => 'À',
										'&#193;' => 'Á', '&#194;' => 'Â', '&#195;' => 'Ã', '&#196;' => 'Ä', '&#197;' => 'Å',
										'&#198;' => 'Æ', '&#199;' => 'Ç', '&#200;' => 'È', '&#201;' => 'É', '&#202;' => 'Ê',
										'&#203;' => 'Ë', '&#204;' => 'Ì', '&#205;' => 'Í', '&#206;' => 'Î', '&#207;' => 'Ï',
										'&#209;' => 'Ñ', '&#210;' => 'Ò', '&#211;' => 'Ó', '&#212;' => 'Ô', '&#213;' => 'Õ',
										'&#214;' => 'Ö', '&#216;' => 'Ø', '&#217;' => 'Ù', '&#218;' => 'Ú', '&#219;' => 'Û',
										'&#220;' => 'Ü', '&#221;' => 'Ý', '&#223;' => 'ß', '&#224;' => 'à', '&#225;' => 'á',
										'&#226;' => 'â', '&#227;' => 'ã', '&#228;' => 'ä', '&#229;' => 'å', '&#230;' => 'æ',
										'&#231;' => 'ç', '&#232;' => 'è', '&#233;' => 'é', '&#234;' => 'ê', '&#235;' => 'ë',
										'&#236;' => 'ì', '&#237;' => 'í', '&#238;' => 'î', '&#239;' => 'ï', '&#241;' => 'ñ',
										'&#242;' => 'ò', '&#243;' => 'ó', '&#244;' => 'ô', '&#245;' => 'õ', '&#246;' => 'ö',
										'&#248;' => 'ø', '&#249;' => 'ù', '&#250;' => 'ú', '&#251;' => 'û', '&#252;' => 'ü',
										'&#253;' => 'ý', '&#255;' => 'ÿ',

										'Å ' => 'Š', 'Å’' => 'Œ', 'Å½' => 'Ž', 'Å¡' => 'š', 'Å“' => 'œ', 'Å¾' => 'ž', 'Å¸' => 'Ÿ',
										'Â¥' => '¥', 'Âµ' => 'µ', 'Ã€' => 'À', 'Ã' => 'Á', 'Ã‚' => 'Â', 'Ãƒ' => 'Ã', 'Ã„' => 'Ä',
										'Ã…' => 'Å', 'Ã†' => 'Æ', 'Ã‡' => 'Ç', 'Ãˆ' => 'È', 'Ã‰' => 'É', 'ÃŠ' => 'Ê', 'Ã‹' => 'Ë',
										'ÃŒ' => 'Ì', 'Ã' => 'Í', 'ÃŽ' => 'Î', 'Ã' => 'Ï', 'Ã' => 'Ð', 'Ã‘' => 'Ñ', 'Ã’' => 'Ò',
										'Ã“' => 'Ó', 'Ã”' => 'Ô', 'Ã•' => 'Õ', 'Ã–' => 'Ö', 'Ã˜' => 'Ø', 'Ã™' => 'Ù', 'Ãš' => 'Ú',
										'Ã›' => 'Û', 'Ãœ' => 'Ü', 'Ã' => 'Ý', 'ÃŸ' => 'ß', 'Ã ' => 'à', 'Ã¡' => 'á', 'Ã¢' => 'â',
										'Ã£' => 'ã', 'Ã¤' => 'ä', 'Ã¥' => 'å', 'Ã¦' => 'æ', 'Ã§' => 'ç', 'Ã¨' => 'è', 'Ã©' => 'é',
										'Ãª' => 'ê', 'Ã«' => 'ë', 'Ã¬' => 'ì', 'Ã­' => 'í', 'Ã®' => 'î', 'Ã¯' => 'ï', 'Ã°' => 'ð',
										'Ã±' => 'ñ', 'Ã²' => 'ò', 'Ã³' => 'ó', 'Ã´' => 'ô', 'Ãµ' => 'õ', 'Ã¶' => 'ö', 'Ã¸' => 'ø',
										'Ã¹' => 'ù', 'Ãº' => 'ú', 'Ã»' => 'û', 'Ã¼' => 'ü', 'Ã½' => 'ý', 'Ã¿' => 'ÿ', "Â¿" => '¿',
										'Â¼' => '¼', 'Â½' => '½', 'Â¾' => '¾', 'Å ' => 'Š', 'Â' => '', 'Â' => '', 'Â¡' => '¡', 'Â­' => '-',
										'â€œ' => '"', 'â€' => '"', 'â€“' => '-', "\n" => ' ', "\r" => ' ', 'â€™' => "'", 'â€' => '"',

										'&iquest;' => '¿', '&AElig;' => 'Æ', '&Aacute;' => 'Á', '&Acirc;' => 'Â', '&Agrave;' => 'À',
										'&Aring;' => 'Å', '&Atilde;' => 'Ã', '&Auml;' => 'Ä', '&Ccedil;' => 'Ç', '&ETH;' => 'Ð',
										'&Eacute;' => 'É', '&Ecirc;' => 'Ê', '&Egrave;' => 'È', '&Euml;' => 'Ë', '&Iacute;' => 'Í',
										'&Icirc;' => 'Î', '&Igrave;' => 'Ì', '&Iuml;' => 'Ï', '&Ntilde;' => 'Ñ', '&Oacute;' => 'Ó',
										'&Ocirc;' => 'Ô', '&Ograve;' => 'Ò', '&Oslash;' => 'Ø', '&Otilde;' => 'Õ', '&Ouml;' => 'Ö',
										'&Uacute;' => 'Ú', '&Ucirc;' => 'Û', '&Ugrave;' => 'Ù', '&Uuml;' => 'Ü', '&Yacute;' => 'Ý',
										'&aacute;' => 'á', '&acirc;' => 'â', '&aelig;' => 'æ', '&agrave;' => 'à', '&aring;' => 'å',
										'&atilde;' => 'ã', '&auml;' => 'ä', '&ccedil;' => 'ç', '&eacute;' => 'é', '&ecirc;' => 'ê',
										'&egrave;' => 'è', '&eth;' => 'ð', '&euml;' => 'ë', '&frac12;' => '½', '&frac14;' => '¼',
										'&frac34;' => '¾', '&iacute;' => 'í', '&icirc;' => 'î', '&iexcl;' => '¡', '&igrave;' => 'ì',
										'&iquest;' => '¿', '&iuml;' => 'ï', '&mdash;' => '—', '&micro;' => 'µ', '&ndash;' => '–',
										'&ntilde;' => 'ñ', '&oacute;' => 'ó', '&ocirc;' => 'ô', '&ograve;' => 'ò', '&oslash;' => 'ø',
										'&otilde;' => 'õ', '&ouml;' => 'ö', '&quot;' => '"', '&shy;' => '­', '&szlig;' => 'ß',
										'&uacute;' => 'ú', '&ucirc;' => 'û', '&ugrave;' => 'ù', '&uuml;' => 'ü', '&yacute;' => 'ý',
										'&yen;' => '¥', '&yuml;' => 'ÿ', '&#8212;' => '-', "\n" => ' ', "\r" => ' ');

		return strtr($text, $dict);
	}

}
?>