<?php
require_once 'tag_helper.php';
require_once 'n_download.php';
/**
 * AssetTagHelper
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Asset Tag Helpers
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class AssetTagHelper extends TagHelper {
	/*
	function autoDiscoveryLinkTag($link='', $type='rss', $options=array(), $tag_options=array()) {
		$t_options = array();
		$t_options['rel'] = isset($tag_options['rel'])?$tag_options['rel']:'alternate';
		$t_options['type'] = 'application/' . (isset($tag_options['type'])?:'') . '+xml';
		$t_options['title'] = isset($tag_options['title'])?$tag_options['rel']:'alternate';
		$t_options['href'] = ;
		return TagHelper::tag($link, $t_options);
	}
	*/

	function imageTag($src, $alt='', $options=array()) {
		if (is_file($_SERVER['DOCUMENT_ROOT'] . $src)) {
			$file = $_SERVER['DOCUMENT_ROOT'] . $src;
		} else if (is_file(BASE_DIR . $src)) {
			$file = BASE_DIR . $src;
		} else {
			return '';
		}
		if (is_string($options)) {
			require_once 'vendor/spyc.php';
			$val = @Spyc::YAMLLoad($options);
			if (!empty($val)) { // it's a YAML array, so load it into options['update']
				$options = $val;
			}
		}
		$code = '';
		if (is_array($options)) {
			$options['style'] = 'border:0;' . (isset($options['style'])?$options['style']:'');
			foreach ($options as $key=>$val) {
				$code .= " $key=\"$val\"";
			}
		} else {
			$code = ' style="border:0;" ' . $options;
		}
		$size = getImageSize($file);
		$src = (defined('EXTERNAL_CACHE') && constant('EXTERNAL_CACHE')?EXTERNAL_CACHE:'') . $src;
		return '<img src="' . $src . '" alt="' . $alt . '" width="' . $size[0] . '" height="' . $size[1] . '"' . $code . ' />';
	}
	function imageTagFunc($params, &$view) {
		if (!isset($params['src'])) return;
		$src = $params['src'];
		unset($params['src']);
		$alt = '';
		if (isset($params['alt'])) {
			$alt = $params['alt'];
			unset($params['alt']);
		}
		return AssetTagHelper::imageTag($src, $alt, $params);
	}

	function lastModifiedIncludeTime($filename) {
		if (file_exists($filename)) {
			$timestamp = filemtime($filename);
		}
		return $timestamp;
	}

	function stylesheetLinkTagFunc($params, &$view) {
		if (!isset($params['href'])) return;
		$href = $params['href'];
		$link_string = '';

		// Setup some defaults
		$rel = isset($params['rel'])?$params['rel']:'stylesheet';
		$rev = isset($params['rev'])?$params['rev']:'';
		$type = isset($params['type'])?$params['type']:'text/css';
		$media = isset($params['media'])?$params['media']:'screen';
		$title = isset($params['title'])?$params['title']:'';

		// Did we get a comma delimited string? If so make an array.
		if(stristr($href,',')){$href = explode(',',$href);}
		if(stristr($href,', ')){$href = explode(', ',$href);}

		// Did we get an array of files
		if(is_array($href)){

				$link_string = '';
				foreach ($href as $sheet) {
					$link_string .= '<link href="/stylesheets/' . $sheet . '.css" rel="' .$rel . '"' . ($rev?" rev=\"$rev\"":'') . ' type="' . $type . '"' . ($media?' media="' . $media . '"':'') . " />\n";
				}

 		}else{
			// Do the normal stylesheet parsing
			$href .= preg_match('/\.css/', $href)?'':'.css';
			if (file_exists(DOCUMENT_ROOT. '/includes/' . $href)) {
				$href = '/includes/' . $href;
				$full_path = DOCUMENT_ROOT . $href;
			} else if (file_exists(DOCUMENT_ROOT. '/stylesheets/' . $href)) {
				$href = '/stylesheets/' . $href;
				$full_path = DOCUMENT_ROOT . $href;
			} else if (file_exists(BASE_DIR . '/nterchange/stylesheets/' . $href)) {
				$href = '/nterchange/stylesheets/' . $href;
				$full_path = BASE_DIR . $href;
			}
			$timestamp = AssetTagHelper::lastModifiedIncludeTime($full_path);
			if (isset($timestamp) && $timestamp > 0) $href .= '?' . $timestamp;

			$link_string .= '<link href="' . $href . '.css" rel="' .$rel . '"' . ($rev?" rev=\"$rev\"":'') . ' type="' . $type . '"' . ($media?' media="' . $media . '"':'') . ' />';
		}

		return $link_string;

	}



	function javascriptIncludeTagFunc($params, &$view) {
		if (!isset($params['src'])) return;
		$src = $params['src'];

		// Set some default options
		$language = isset($params['language'])?$params['language']:'';
		$charset = isset($params['charset'])?$params['charset']:'';
		$type = isset($params['type'])?$params['type']:'text/javascript';
		$timestamp = AssetTagHelper::lastModifiedIncludeTime($full_path);
		if (isset($timestamp) && $timestamp > 0) $src .= '?' . $timestamp;

		// Did we get a comma delimited string? If so make an array.
		if(stristr($src,',')){$src = explode(',',$src);}

		// Did we get an array of files
		if(is_array($src)){
 			// Are we in production, if so combine and compress the files
			if(defined('ENVIRONMENT') && ENVIRONMENT == 'production'){
				$src = implode(".js,",$src);
 				$include_string = '<script src="/js_combined/' . $src. '.js"' . ' type="' . $type . '"' . ($language?" language=\"$language\"":'') . ($charset?" charset=\"$charset\"":'') . '></script>';
			}else{
				$include_string = '';
				foreach ($src as $script) {
					$include_string .= '<script src="/javascripts/' . $script. '.js"' . ' type="' . $type . '"' . ($language?" language=\"$language\"":'') . ($charset?" charset=\"$charset\"":'') . "></script>\n";
				}
			}
 		}else{
			// Do the normal JS parsing
 			$src .= preg_match('/\.js/', $src)?'':'.js';
 			if (file_exists(DOCUMENT_ROOT. '/includes/' . $src)) {
 				$src = '/includes/' . $src;
 				$full_path = DOCUMENT_ROOT . $src;
 			} else if (file_exists(DOCUMENT_ROOT. '/javascripts/' . $src)) {
 				$src = '/javascripts/' . $src;
 				$full_path = DOCUMENT_ROOT . $src;
 			} else if (file_exists(BASE_DIR . '/nterchange/javascripts/' . $src)) {
 				$src = '/nterchange/javascripts/' . $src;
 				$full_path = BASE_DIR . $src;
 			} else {
 				return;
 			}
 			$include_string = '<script src="' . $src. '"' . ' type="' . $type . '"' . ($language?" language=\"$language\"":'') . ($charset?" charset=\"$charset\"":'') . '></script>';
 		}
		return $include_string;
	}

	function imageSize($src, $options = array()) {
		$phpfile = preg_match('/\.php/', $src);
		$file = $phpfile?preg_replace('/\.php\?.*$/', '.php', $src):$src;
		if (is_file($_SERVER['DOCUMENT_ROOT'] . $file)) {
			$file = $_SERVER['DOCUMENT_ROOT'] . $file;
		} else if (is_file(BASE_DIR . $file)) {
			$file = BASE_DIR . $file;
		} else {
			return '';
		}

		if (!$phpfile) {
			$size = @getImageSize($file);
		} else {
			// normalize the query string in the src (if any)
			if (preg_match('/\?(.*)$/', $src, $matches) && isset($matches[1]) && $matches[1]) {
				if (preg_match_all('/([^=]+)=([^\&$]+)/', $matches[1], $submatches)) {
					$qs = '';
					foreach ($submatches[1] as $i=>$key) {
						$key = preg_replace('/^\&/', '', $key);
						$val = $submatches[2][$i];
						$qs .= ($qs?'&':'') . $key . '=' . urlencode($val);
					}
					$src = preg_replace('/\?[^$]+/', '?' . $qs, $src);
				}
			}
			$size = AssetTagHelper::otfImageSize($src);
		}
		return ' width="' . $size[0] . '" height="' . $size[1] . '"';
	}

	function imageWidth($src, $options = array()) {
		$phpfile = preg_match('/\.php/', $src);
		$file = $phpfile?preg_replace('/\.php\?.*$/', '.php', $src):$src;
		if (is_file($_SERVER['DOCUMENT_ROOT'] . $file)) {
			$file = $_SERVER['DOCUMENT_ROOT'] . $file;
		} else if (is_file(BASE_DIR . $file)) {
			$file = BASE_DIR . $file;
		} else {
			return '';
		}

		if (!$phpfile) {
			$size = @getImageSize($file);
		} else {
			// normalize the query string in the src (if any)
			if (preg_match('/\?(.*)$/', $src, $matches) && isset($matches[1]) && $matches[1]) {
				if (preg_match_all('/([^=]+)=([^\&$]+)/', $matches[1], $submatches)) {
					$qs = '';
					foreach ($submatches[1] as $i=>$key) {
						$key = preg_replace('/^\&/', '', $key);
						$val = $submatches[2][$i];
						$qs .= ($qs?'&':'') . $key . '=' . urlencode($val);
					}
					$src = preg_replace('/\?[^$]+/', '?' . $qs, $src);
				}
			}
			$size = AssetTagHelper::otfImageSize($src);
		}
		return $size[0];
	}

	function imageHeight($src, $options = array()) {
		$phpfile = preg_match('/\.php/', $src);
		$file = $phpfile?preg_replace('/\.php\?.*$/', '.php', $src):$src;
		if (is_file($_SERVER['DOCUMENT_ROOT'] . $file)) {
			$file = $_SERVER['DOCUMENT_ROOT'] . $file;
		} else if (is_file(BASE_DIR . $file)) {
			$file = BASE_DIR . $file;
		} else {
			return '';
		}

		if (!$phpfile) {
			$size = @getImageSize($file);
		} else {
			// normalize the query string in the src (if any)
			if (preg_match('/\?(.*)$/', $src, $matches) && isset($matches[1]) && $matches[1]) {
				if (preg_match_all('/([^=]+)=([^\&$]+)/', $matches[1], $submatches)) {
					$qs = '';
					foreach ($submatches[1] as $i=>$key) {
						$key = preg_replace('/^\&/', '', $key);
						$val = $submatches[2][$i];
						$qs .= ($qs?'&':'') . $key . '=' . urlencode($val);
					}
					$src = preg_replace('/\?[^$]+/', '?' . $qs, $src);
				}
			}
			$size = AssetTagHelper::otfImageSize($src);
		}
		return $size[1];
	}

	function cssImageSize($src, $options = array()) {
		$phpfile = preg_match('/\.php/', $src);
		$file = $phpfile?preg_replace('/\.php\?.*$/', '.php', $src):$src;
		if (is_file($_SERVER['DOCUMENT_ROOT'] . $file)) {
			$file = $_SERVER['DOCUMENT_ROOT'] . $file;
		} else if (is_file(BASE_DIR . $file)) {
			$file = BASE_DIR . $file;
		} else {
			return '';
		}

		if (!$phpfile) {
			$size = @getImageSize($file);
		} else {
			// normalize the query string in the src (if any)
			if (preg_match('/\?(.*)$/', $src, $matches) && isset($matches[1]) && $matches[1]) {
				if (preg_match_all('/([^=]+)=([^\&$]+)/', $matches[1], $submatches)) {
					$qs = '';
					foreach ($submatches[1] as $i=>$key) {
						$key = preg_replace('/^\&/', '', $key);
						$val = $submatches[2][$i];
						$qs .= ($qs?'&':'') . $key . '=' . urlencode($val);
					}
					$src = preg_replace('/\?[^$]+/', '?' . $qs, $src);
				}
			}
			$size = AssetTagHelper::otfImageSize($src);
		}
		$img_width = $size[0];
		$img_height = $size[1];
		if($options['pad_width'] && isset($options['pad_width'])){
			$img_width += $options['pad_width'];
		}
		if($options['pad_height'] && isset($options['pad_height'])){
			$img_height += $options['pad_height'];
		}
		return ' style="width:' . $img_width . 'px;height:' . $img_height . 'px;"';
	}

	function otfImageSize($src, $i=0) {
		// grab the file, write a file, get the size
		include_once 'HTTP/Request.php';
		$req = new HTTP_Request(preg_replace('/\/$/', '', PUBLIC_SITE) . $src);
		$size = false;
		if (!PEAR::isError($req->sendRequest())) {
			$tmpnam = tempnam(CACHE_DIR . '/ntercache', 'otfimage');
			$fp = fopen($tmpnam, 'w');
			fwrite($fp, $req->getResponseBody());
			fclose($fp);
			$size = @getImageSize($tmpnam);
			unlink($tmpnam);
		}
		if (!$size && $i<3) {
			$size = AssetTagHelper::otfImageSize($src, $i+1);
		}
		return $size;
	}

	function imageSizeFunc($params, &$view) {
		if (!isset($params['src'])) return;
		$src = $params['src'];
		unset($params['src']);
		return AssetTagHelper::imageSize($src, $params);
	}

	function imageWidthFunc($params, &$view) {
		if (!isset($params['src'])) return;
		$src = $params['src'];
		unset($params['src']);
		return AssetTagHelper::imageWidth($src, $params);
	}

	function imageHeightFunc($params, &$view) {
		if (!isset($params['src'])) return;
		$src = $params['src'];
		unset($params['src']);
		return AssetTagHelper::imageHeight($src, $params);
	}


	function cssImageSizeFunc($params, &$view) {
		if (!isset($params['src'])) return;
		$src = $params['src'];
		unset($params['src']);
		return AssetTagHelper::cssImageSize($src, $params);
	}

	function persistentUrl($params) {
		if (defined('PERSISTENT_UPLOAD_URLS') && PERSISTENT_UPLOAD_URLS) {
			$url_path = NDownload::cleanUrl($params['file']);
			$parts = NDownload::getAssetAttributes($url_path);
			NDownload::setAssetAttributes($parts);
			$model_name = NDownload::getAssetModelName();
			$url = UPLOAD_DIR . '/' . $model_name . '/' . $params['field'] . '/' . $params['id'];
			return $url;
		} else {
			return $params['file'];
		}
	}
}
?>
