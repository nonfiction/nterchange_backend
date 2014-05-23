<?php
require_once 'admin_controller.php';
require_once 'n_download.php';
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
 * @category   CSV Export for Assets
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CsvExportController extends AdminController {
	var $default_foreign_keys = array('cms_modified_by_user'=>array('cms_auth', 'real_name'));
	var $default_field_exclusions = array('id'=>true, 'cms_active'=>true, 'cms_draft'=>true, 'cms_deleted'=>true, 'cms_headline'=>true);

	function __construct() {
		$this->name = 'csv_export';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_EDITOR;
		$this->login_required = true;
		$this->page_title = 'CSV Export';
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
				// Creating a workbook
				$filename = $_SERVER['DOCUMENT_ROOT'] . UPLOAD_DIR . '/' . rand(1,1000) . '-file.csv';
				$fp = fopen($filename, 'w');
				
				// Creating a workbook and sending it directly out to a browser.
				//$fp = fopen('php://output', 'w');
				
				// Let's add the field names to the title line.
				// Leave out a few.
				$x = 0;
				foreach ($fields as $field) {
					$exclude_this = array_key_exists($field, $field_exclusions);
					if ($exclude_this && $field_exclusions[$field] == true) {
						// do nothing
					} else {
						$good_fields[] = $field;
					}
				}
				//$field_string = implode(',', $good_fields);
				fputcsv($fp, $good_fields);

				// Now here comes the data.
				$y = 1;
				while ($model->fetch()) {
					$data_fields = array();
					$item = $model->toArray();
					// For reference while we're working with things.
					$original_item = array();
					$original_item = $item;
					$x = 0;
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
							$fixed_item = $this->convert_characters($item[$field]);
							$data_fields[] = $fixed_item;
						}
					}
					//$data_string = implode(',', $data_fields);
					fputcsv($fp, $data_fields);
					unset($original_item);
					unset($item);
					unset($data_fields);
				}
				// Close the file.
				fclose($fp);
				$download = new NDownload;
				$download->serveFile($filename);
				unlink($filename);
			}
		}
	}

	function convert_characters($text) {
		$dict  = array(chr(138) => 'ä', chr(141) => 'ç', chr(142) => 'é', chr(145) => "'", chr(146) => "'",
										chr(147) => '"', chr(148) => '"', chr(150) => '-', chr(151) => '-', chr(154) => 'ö',
										chr(157) => 'ù', chr(158) => 'û', chr(160) => ' ', chr(161) => '°', chr(173) => '≠',
										chr(188) => 'º', chr(189) => 'Ω', chr(190) => 'æ', chr(191) => 'ø', chr(192) => '¿',
										chr(193) => '¡', chr(194) => '¬', chr(195) => '√', chr(196) => 'ƒ', chr(197) => '≈',
										chr(198) => '∆', chr(199) => '«', chr(200) => '»', chr(201) => '…', chr(202) => ' ',
										chr(203) => 'À', chr(204) => 'Ã', chr(205) => 'Õ', chr(206) => 'Œ', chr(207) => 'œ',
										chr(209) => '—', chr(210) => '“', chr(211) => '”', chr(212) => '‘', chr(213) => '’',
										chr(214) => '÷', chr(216) => 'ÿ', chr(217) => 'Ÿ', chr(218) => '⁄', chr(219) => '€',
										chr(220) => '‹', chr(221) => '›', chr(223) => 'ﬂ', chr(224) => '‡', chr(225) => '·',
										chr(226) => '‚', chr(227) => '„', chr(228) => '‰', chr(229) => 'Â', chr(230) => 'Ê',
										chr(231) => 'Á', chr(232) => 'Ë', chr(233) => 'È', chr(234) => 'Í', chr(235) => 'Î',
										chr(236) => 'Ï', chr(237) => 'Ì', chr(238) => 'Ó', chr(239) => 'Ô', chr(241) => 'Ò',
										chr(242) => 'Ú', chr(243) => 'Û', chr(244) => 'Ù', chr(245) => 'ı', chr(246) => 'ˆ',
										chr(248) => '¯', chr(249) => '˘', chr(250) => '˙', chr(251) => '˚', chr(252) => '¸',
										chr(253) => '˝', chr(255) => 'ˇ',

										'&#138;' => 'ä', '&#141;' => 'ç', '&#142;' => 'é', '&#145;' => "'", '&#146;' => "'",
										'&#147;' => '"', '&#148;' => '"', '&#150;' => '-', '&#151;' => '-', '&#154;' => 'ö',
										'&#157;' => 'ù', '&#158;' => 'û', '&#160;' => ' ', '&#161;' => '°', '&#173;' => '≠',
										'&#188;' => 'º', '&#189;' => 'Ω', '&#190;' => 'æ', '&#191;' => 'ø', '&#192;' => '¿',
										'&#193;' => '¡', '&#194;' => '¬', '&#195;' => '√', '&#196;' => 'ƒ', '&#197;' => '≈',
										'&#198;' => '∆', '&#199;' => '«', '&#200;' => '»', '&#201;' => '…', '&#202;' => ' ',
										'&#203;' => 'À', '&#204;' => 'Ã', '&#205;' => 'Õ', '&#206;' => 'Œ', '&#207;' => 'œ',
										'&#209;' => '—', '&#210;' => '“', '&#211;' => '”', '&#212;' => '‘', '&#213;' => '’',
										'&#214;' => '÷', '&#216;' => 'ÿ', '&#217;' => 'Ÿ', '&#218;' => '⁄', '&#219;' => '€',
										'&#220;' => '‹', '&#221;' => '›', '&#223;' => 'ﬂ', '&#224;' => '‡', '&#225;' => '·',
										'&#226;' => '‚', '&#227;' => '„', '&#228;' => '‰', '&#229;' => 'Â', '&#230;' => 'Ê',
										'&#231;' => 'Á', '&#232;' => 'Ë', '&#233;' => 'È', '&#234;' => 'Í', '&#235;' => 'Î',
										'&#236;' => 'Ï', '&#237;' => 'Ì', '&#238;' => 'Ó', '&#239;' => 'Ô', '&#241;' => 'Ò',
										'&#242;' => 'Ú', '&#243;' => 'Û', '&#244;' => 'Ù', '&#245;' => 'ı', '&#246;' => 'ˆ',
										'&#248;' => '¯', '&#249;' => '˘', '&#250;' => '˙', '&#251;' => '˚', '&#252;' => '¸',
										'&#253;' => '˝', '&#255;' => 'ˇ',

										'≈ ' => 'ä', '≈í' => 'å', '≈Ω' => 'é', '≈°' => 'ö', '≈ì' => 'ú', '≈æ' => 'û', '≈∏' => 'ü',
										'¬•' => '•', '¬µ' => 'µ', '√Ä' => '¿', '√Å' => '¡', '√Ç' => '¬', '√É' => '√', '√Ñ' => 'ƒ',
										'√Ö' => '≈', '√Ü' => '∆', '√á' => '«', '√à' => '»', '√â' => '…', '√ä' => ' ', '√ã' => 'À',
										'√å' => 'Ã', '√ç' => 'Õ', '√é' => 'Œ', '√è' => 'œ', '√ê' => '–', '√ë' => '—', '√í' => '“',
										'√ì' => '”', '√î' => '‘', '√ï' => '’', '√ñ' => '÷', '√ò' => 'ÿ', '√ô' => 'Ÿ', '√ö' => '⁄',
										'√õ' => '€', '√ú' => '‹', '√ù' => '›', '√ü' => 'ﬂ', '√ ' => '‡', '√°' => '·', '√¢' => '‚',
										'√£' => '„', '√§' => '‰', '√•' => 'Â', '√¶' => 'Ê', '√ß' => 'Á', '√®' => 'Ë', '√©' => 'È',
										'√™' => 'Í', '√´' => 'Î', '√¨' => 'Ï', '√≠' => 'Ì', '√Æ' => 'Ó', '√Ø' => 'Ô', '√∞' => '',
										'√±' => 'Ò', '√≤' => 'Ú', '√≥' => 'Û', '√¥' => 'Ù', '√µ' => 'ı', '√∂' => 'ˆ', '√∏' => '¯',
										'√π' => '˘', '√∫' => '˙', '√ª' => '˚', '√º' => '¸', '√Ω' => '˝', '√ø' => 'ˇ', "¬ø" => 'ø',
										'¬º' => 'º', '¬Ω' => 'Ω', '¬æ' => 'æ', '≈ ' => 'ä', '¬ç' => 'ç', '¬ù' => 'ù', '¬°' => '°', '¬≠' => '-',
										'‚Äú' => '"', '‚Äù' => '"', '‚Äì' => '-', "\n" => ' ', "\r" => ' ', '‚Äô' => "'", '‚Ä' => '"',

										'&iquest;' => 'ø', '&AElig;' => '∆', '&Aacute;' => '¡', '&Acirc;' => '¬', '&Agrave;' => '¿',
										'&Aring;' => '≈', '&Atilde;' => '√', '&Auml;' => 'ƒ', '&Ccedil;' => '«', '&ETH;' => '–',
										'&Eacute;' => '…', '&Ecirc;' => ' ', '&Egrave;' => '»', '&Euml;' => 'À', '&Iacute;' => 'Õ',
										'&Icirc;' => 'Œ', '&Igrave;' => 'Ã', '&Iuml;' => 'œ', '&Ntilde;' => '—', '&Oacute;' => '”',
										'&Ocirc;' => '‘', '&Ograve;' => '“', '&Oslash;' => 'ÿ', '&Otilde;' => '’', '&Ouml;' => '÷',
										'&Uacute;' => '⁄', '&Ucirc;' => '€', '&Ugrave;' => 'Ÿ', '&Uuml;' => '‹', '&Yacute;' => '›',
										'&aacute;' => '·', '&acirc;' => '‚', '&aelig;' => 'Ê', '&agrave;' => '‡', '&aring;' => 'Â',
										'&atilde;' => '„', '&auml;' => '‰', '&ccedil;' => 'Á', '&eacute;' => 'È', '&ecirc;' => 'Í',
										'&egrave;' => 'Ë', '&eth;' => '', '&euml;' => 'Î', '&frac12;' => 'Ω', '&frac14;' => 'º',
										'&frac34;' => 'æ', '&iacute;' => 'Ì', '&icirc;' => 'Ó', '&iexcl;' => '°', '&igrave;' => 'Ï',
										'&iquest;' => 'ø', '&iuml;' => 'Ô', '&mdash;' => 'ó', '&micro;' => 'µ', '&ndash;' => 'ñ',
										'&ntilde;' => 'Ò', '&oacute;' => 'Û', '&ocirc;' => 'Ù', '&ograve;' => 'Ú', '&oslash;' => '¯',
										'&otilde;' => 'ı', '&ouml;' => 'ˆ', '&quot;' => '"', '&shy;' => '≠', '&szlig;' => 'ﬂ',
										'&uacute;' => '˙', '&ucirc;' => '˚', '&ugrave;' => '˘', '&uuml;' => '¸', '&yacute;' => '˝',
										'&yen;' => '•', '&yuml;' => 'ˇ', '&#8212;' => '-', "\n" => ' ', "\r" => ' ');

		return strtr($text, $dict);
	}

}
?>