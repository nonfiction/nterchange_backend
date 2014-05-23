<?php
/**
 * NImage is some Image Manipulation methods.
 * There are many possible dependancies:
 * 	1. ImageMagick and the PHP PECL Extension for Image Resizing. PHP 5.2.0+ Only
 *
 * 	Other additional dependancies to come as this gets extended.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Image Manipulation
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.1.16
 */
class NImage extends Object {
	// List of image formats we can convert to jpeg. These are all tested and work.
	// NOTE: PDF can do some odd things (sizes and cropping) - but it does convert.
	var $bad_formats = array ('PSD', 'TIFF', 'BMP', 'PS', 'PCD', 'WMF', 'PDF');
	var $convert_binary = '/usr/bin/convert';
	var $identify_binary = '/usr/bin/identify';
	
	function __construct() {
		if (class_exists('Imagick')) {
			// Yay.
		} else {
			die('You need Image Magick to continue');
		}
	}

	/**
	 * imageResize - Resize an image proportionally in place with Image Magick's thumbnailImage method.
	 *		Resaves with the same filename and overwrites the original file.
	 *
	 * @param	string	The name of the file - NOT fully qualified.
	 * @param	int		The width of the final image.
	 * @param	int		The height of the final image.
	 * @return void
	 **/
	function imageResize($file, $width=0, $height=0) {
		$filename = $_SERVER['DOCUMENT_ROOT'] . $file;
		$image = new Imagick();
		$image->readImage("$filename");
		$image->thumbnailImage($width,$height);
		$image->writeImage("$filename");
		$image->destroy();
	}

	/**
	 * checkImageFormat - Converts CMYK images to RGB format and converts $this->bad_formats
	 *		to $converted_format if required.
	 *
	 * @param	string	The format to convert to.
	 * @param	string	The file to be checked - NOT fully qualified.
	 * @param	string	The name of the asset.
	 * @param	string	The field in the database where this file is referenced.
	 * @param	int		The id of this particular asset record.
	 * @todo 	Should we notify the user if they've uploaded a file in the bad format?
	 * @todo 	We need to make this type of thing happen for multiple files and only save it to the DB once.
	 * @return void
	 **/
	function checkImageFormat($converted_format, $file, $asset=null, $field=null, $id=null) {
		$format = $this->getImageFormat($file);
		// Check to see if it's a CMYK image.
		if ($cmyk = $this->isImageCMYK($file)) {
			$this->convertCMYKToRGB($file);
		}
		// Check to see if it's a bad format - if it is, convert to $converted_format.
		// and save the changed filename to the database.
		if ($bad_format = $this->isImageBadFormat($format)) {
			$filename = $this->setImageFormat($file, $converted_format);
			$filename = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filename);
			// Gotta save it to the DB now since the name has changed.
			$this->saveUpdatedImage($filename, $asset, $field, $id);
		}
	}
	
	/**
	 * saveUpdatedImage - Saves an image's new filename back to the database.
	 *
	 * @param	string	The file to be checked - NOT fully qualified.
	 * @param	string	The name of the asset.
	 * @param	string	The field in the database where this file is referenced.
	 * @param	int		The id of this particular asset record.
	 * @return void
	 **/
	function saveUpdatedImage($filename, $asset, $field, $id) {
		$upload_model = NModel::factory($asset);
		$upload_model->id = $id;
		if ($upload_model->find()) {
			while ($upload_model->fetch()) {
				$upload_model->{$field} = $filename;
				$upload_model->save();
			}
		}
	}

	/**
	 * getImageFormat - Returns the Image Format from a file via Imagick.
	 *
	 * @param	string	The file we want the format for - NOT fully qualified.
	 * @return 	string	The format of the file.
	 **/
	function getImageFormat($file) {
		$filename = $_SERVER['DOCUMENT_ROOT'] . $file;
		$image = new Imagick();
		$image->readImage("$filename");
		$format = $image->getImageFormat();
		$image->destroy();
		return $format;
	}
	
	/**
	 * isImageBadFormat - Is the format of this image in $this->bad_formats?
	 *
	 * @param	string	A format from a particular file.
	 * @return 	boolean
	 **/
	function isImageBadFormat($format) {
		if (in_array($format, $this->bad_formats)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * compressJPEGImage - Compress a JPEG image - Level is from 0-100.
	 *		Resaves with the same filename and overwrites the original file.
	 *
	 * @param	string	A JPEG image to be compressed - NOT fully qualified.
	 * @param	int		The level to be compressed. 0 is least compressed - 100 is most.
	 * @return void
	 **/
	function compressJPEGImage($file, $level) {
		$filename = $_SERVER['DOCUMENT_ROOT'] . $file;
		$image = new Imagick();
		$image->readImage("$filename");
		$image->setCompressionQuality($level);
		$image->writeImage("$filename");
		$image->destroy();
	}

	/**
	 * setImageFormat - Sets an image to a particular image format.
	 * 		Resaves with a NEW filename to reflect the new format.
	 *
	 * @param	string	The filename - NOT fully qualified.
	 * @param	string	The new format to convert the image to.
	 * @return 	string	The new filename.
	 **/
	function setImageFormat($file, $format) {
		$filename = $_SERVER['DOCUMENT_ROOT'] . $file;
		$image = new Imagick();
		$image->readImage("$filename");
		$image->setImageFormat($format);
		$new_filename = $filename . "." . strtolower($format);
		$image->setFilename($new_filename);
		$image->writeImage("$new_filename");
		$image->destroy();
		return $new_filename;
	}
	
	/**
	 * isImageCMYK - Determines whether or not an image is in CMYK format.
	 *
	 * @param	string	The filename - NOT fully qualified.
	 * @return 	boolean
	 * @todo 	There's gotta be a better way to do this - but I can't get getImageColorspace to return anything.
	 **/
	function isImageCMYK($filename) {
		$filename = $_SERVER['DOCUMENT_ROOT'] . $filename;
		$identify_command = $this->identify_binary . ' -format "%r" ' . $filename;
		exec($identify_command, $identify_output_array);
		$identify_output = implode(' ', $identify_output_array);
		if (eregi('CMYK', $identify_output)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * convertCMYKToRGB - Convert a CMYK image to RGB.
	 *		Resaves with the same filename and overwrites the original file.
	 *
	 * @param	string	The filename - NOT fully qualified.
	 * @return 	void
	 * @todo 	There's gotta be a better way to do this - but the normal way doesn't appear to work.
	 * @todo 	Apparently "profiles" are the proper way to do this that produces the best result. Refactor when they actually work.
	 **/
	function convertCMYKToRGB($filename) {
		$filename = $_SERVER['DOCUMENT_ROOT'] . $filename;
		$directory = dirname($filename);
		$tmp_file = $directory . '/convert.jpg';
		$convert_command = $this->convert_binary . ' -colorspace RGB ' . $filename . " $tmp_file";
		exec($convert_command, $convert_output_array);
		copy($tmp_file, $filename);
		unlink($tmp_file);
	}
}

?>