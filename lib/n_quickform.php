<?php
require_once 'HTML/QuickForm.php';
/**
 * NQuickform
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Form Creation
 * @author     	Tim Glen <tim@nonfiction.ca>
 * @copyright  	2005-2007 nonfiction studios inc.
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: $Id$
 * @link       	http://www.nterchange.com/
 * @since      	File available since Release 3.0
 */
class NQuickForm extends HTML_QuickForm {
	function NQuickForm($formName='', $method='POST', $action='', $target='_self', $attributes=null) {
		$this->HTML_QuickForm($formName, $method, $action, $target, $attributes);
		$renderer =& $this->defaultRenderer();
		$renderer->setHeaderTemplate("\n\t<tr class='{label_2}'>\n\t\t<td nowrap=\"nowrap\" align=\"left\" valign=\"top\" colspan=\"2\" class=\"header\"><div class=\"header\">{header}</div></td>\n\t</tr>");
		$renderer->setElementTemplate("\n\t<tr class='{label_2}'>\n\t\t<td align=\"right\" valign=\"top\"><!-- BEGIN required --><span class=\"formerror\">*</span><!-- END required --><b>{label}</b></td>\n\t\t<td nowrap=\"nowrap\" valign=\"top\" align=\"left\"><!-- BEGIN error --><div class=\"formerror\">{error}</div><!-- END error -->\t{element}</td>\n\t</tr>");
		if (CURRENT_SITE == 'admin') {
			$this->setMaxFileSize(1024*1024*20);
			if (strtolower($method) == 'get') {
				if (isset($_GET['page_id']) && $_GET['page_id']) {
					$this->setDefaults(array('page_id'=>$_GET['page_id']));
					$this->addElement('hidden', 'page_id');
				}
				if (isset($_GET['edit']) && $_GET['edit']) {
					$this->setDefaults(array('edit'=>$_GET['edit']));
					$this->addElement('hidden', 'edit');
				}
			}
		}
	}

	function getEmailMsg() {
		$msg = '';
		$msg .= '  HTTP User Agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\n";
		$msg .= '  Website: http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . "\n\n";
		$vals = $this->getSubmitValues();
		foreach ($this->_elementIndex as $element_name=>$order) {
			$msg .= '  ';
			$elem = &$this->getElement($element_name);
			$type = $elem->getType();
			$label = $elem->getLabel();
			$label = is_array($label)?$label[0]:$label;
			$val = isset($vals[$element_name])?$vals[$element_name]:null;
			if (!$val) continue;
			switch($type) {
				case 'hidden':
					break;
				case 'html':
					break;
				case 'submit':
					break;
				case 'header':
					$msg .= "\n  ======================================================================\n";
					$msg .= '  ' . strip_tags($elem->_text) . "\n";
					$msg .= "  ======================================================================\n\n";
					break;
				case 'group':
					$group_type = $elem->getGroupType();
					if ($group_type == 'radio') {
						$msg .= $label?strip_tags($label) . ':':'';
						foreach ($elem->_elements as $subelem) {
							$subval = $subelem->getValue();
							if ($subval == $val) {
								$msg .= strip_tags($subelem->_text) . "\n";
								break;
							}
						}
					} else {
						$msg .= $label?strip_tags($label) . "\n":'';
						foreach ($elem->_elements as $subelem) {
							$type = $subelem->getType();
							if ($type == 'checkbox') {
								if (isset($val[$subelem->getName()])) {
									$msg .= ($label?'    ':'') . ($subelem->_text?$subelem->_text:$subelem->getValue()) . "\n";
								}
							} else {
								$msg .= ($label?'    ':'') . ($subelem->_text?$subelem->_text:$subelem->getValue()) . "\n";
							}
						}
					}
					break;
				case 'select':
					$val = is_array($val)?implode(', ', $val):$val;
					$msg .= $label . ": " . $val . "\n";
					break;
				case 'date':
					$vars = array('w', 'd', 'n', 'Y', 'h', 'H', 'i', 's', 'a', 'A');
					$vars = array('D', 'l', 'd', 'M', 'm', 'F', 'Y', 'y', 'h', 'H', 'i', 's', 'a', 'A');
					$tmp = '';
					for ($i = 0, $length = strlen($elem->_options['format']); $i < $length; $i++) {
						$sign = $elem->_options['format']{$i};
						$tmp .= in_array($sign, $vars)?$val[$sign]:$sign;
					}
					$val = date($elem->_options['format'], strtotime($tmp));
					$msg .= $label . ": " . $tmp . "\n";
					break;
				case 'textarea':
					$msg .= $label . ":\n    " . $val . "\n";
					break;
				default:
					$msg .= $label . ": " . $val . "\n";
					break;
			}
		}
		return wordwrap($msg);
	}

	function getInsertSQL($table) {
		include_once 'n_db.php';
		$db = &NDB::connect();
		$tablefields = $db->tableInfo($table);

		$vals = $this->getSubmitValues();
		$fields = '';
		$values = '';
		foreach ($tablefields as $tablefield) {
			$field_name = $tablefield['name'];
			$field_type = $tablefield['type'];
			$field_len = $tablefield['len'];
			if (isset($vals[$field_name])) {
				if ($fields != '') $fields .= ',';
				$fields .= $db->quoteIdentifier($field_name);
				if ($values != '') $values .= ',';

				$elem = &$this->getElement($field_name);
				$type = $elem->getType();
				$val = $vals[$field_name];
				$val = !get_magic_quotes_gpc()?addslashes($val):$val;
				switch($type) {
					case 'submit':
						break;
					case 'header':
						break;
					case 'group':
						$tmp = '';
						$group_type = $elem->getGroupType();
						if ($group_type == 'radio') {
							foreach ($elem->_elements as $subelem) {
								$subval = $subelem->getValue();
								if ($subval == $val) {
									if ($tmp != '') $tmp .= ', ';
									$tmp .= strip_tags($subelem->_text);
									break;
								}
							}
						} else {
							foreach ($elem->_elements as $subelem) {
								$type = $subelem->getType();
								if ($type == 'checkbox') {
									if (isset($val[$subelem->getName()])) {
										if ($tmp != '') $tmp .= ', ';
										$tmp .= ($subelem->_text?$subelem->_text:$subelem->getValue());
									}
								} else {
									if ($tmp != '') $tmp .= ', ';
									$tmp .= ($label?'    ':'') . ($subelem->_text?$subelem->_text:$subelem->getValue());
								}
							}
						}
						$values .= $db->quoteSmart($tmp);
						break;
					case 'select':
						$val = is_array($val)?implode(', ', $val):$val;
						$values .= $db->quoteSmart($val);
						break;
					case 'date':
						$vars = array('w', 'd', 'n', 'Y', 'h', 'H', 'i', 's', 'a', 'A');
						$vars = array('D', 'l', 'd', 'M', 'm', 'F', 'Y', 'y', 'h', 'H', 'i', 's', 'a', 'A');
						$tmp = '';
						for ($i = 0, $length = strlen($elem->_options['format']); $i < $length; $i++) {
							$sign = $elem->_options['format']{$i};
							$tmp .= in_array($sign, $vars)?$val[$sign]:$sign;
						}
						if ($field_type == 'date') {
							$format = 'Y-m-d';
						} else if ($field_type == 'datetime') {
							$format = 'Y-m-d H:i:s';
						} else if ($field_type == 'time') {
							$format = 'H:i:s';
						} else {
							$format = $elem->_options['format'];
						}
						$val = date($format, strtotime($tmp));
						$values .= $db->quoteSmart($tmp);
						break;
					default:
						$values .= $db->quoteSmart($val);
						break;
				}
			}
		}
		if ($fields != '' && $values != '') {
			foreach ($tablefields as $tablefield) {
				if ($tablefield['name'] == 'ip' && !isset($vals[$tablefield['ip']])) {
					$fields .= ',' . $db->quoteIdentifier('ip');
					$values .= ',' . $db->quoteSmart($_SERVER['REMOTE_ADDR']);
				}
				if ($tablefield['name'] == 'inserted' && !isset($vals[$tablefield['name']])) {
					$fields .= ',' . $db->quoteIdentifier('inserted');
					$values .= ',NOW()';
				}
			}
		}
		$sql = 'INSERT INTO ' . $db->quoteIdentifier($table) . " ($fields) VALUES ($values)";

		return $sql;
	}
}

require_once 'HTML/QuickForm/static.php';
/**
 * A pseudo-element used for adding alerts to form  
 *
 * @author Tim Glen <tim@nonfiction.c>
 * @access public
 */
class HTML_QuickForm_cmsalert extends HTML_QuickForm_static
{
	function HTML_QuickForm_cmsalert($elementName = null, $text = null) {
		$this->HTML_QuickForm_static($elementName, null, $text);
		$this->_type = 'cmserror';
	}

	function toHTML() {
		return $this->_getTabs() . "\n\t<tr>\n\t\t<td nowrap=\"nowrap\" align=\"left\" valign=\"top\" colspan=\"2\"><div class=\"alertinfo\">" . $this->_text . "</div></td>\n\t</tr>";
	}
}
/**
 * A pseudo-element used for adding errors to form  
 *
 * @author Tim Glen <tim@nonfiction.c>
 * @access public
 */
class HTML_QuickForm_cmserror extends HTML_QuickForm_static
{
	function HTML_QuickForm_cmserror($elementName = null, $text = null) {
		$this->HTML_QuickForm_static($elementName, null, $text);
		$this->_type = 'cmserror';
	}

	function toHTML() {
		return $this->_getTabs() . "\n\t<tr>\n\t\t<td nowrap=\"nowrap\" align=\"left\" valign=\"top\" colspan=\"2\"><div class=\"alerterror\">" . $this->_text . "</div></td>\n\t</tr>";
	}
}

require_once 'HTML/QuickForm/group.php';
require_once 'HTML/QuickForm/file.php';
require_once 'HTML/QuickForm/link.php';
require_once 'HTML/QuickForm/hidden.php';
require_once 'HTML/QuickForm/checkbox.php';
require_once 'HTML/QuickForm/CAPTCHA.php';
class HTML_QuickForm_CMS_file extends HTML_QuickForm_group
{

	var $file = '';
	var $_options = array('upload_dir'=>'');

	/**
	* These complement separators, they are appended to the resultant HTML
	* @access   private
	* @var	  array
	*/
	var $_value = null;
	var $_elements = array();
	var $_wrap = array('', '');
	var $_separator = '<br />';

	var $elementName = '';
	var $elementLabel = '';


	// }}}
	// {{{ constructor

	/**
	* Class constructor
	* 
	* @access   public
	* @param	string  Element's name
	* @param	mixed   Label(s) for an element
	* @param	array   Options to control the element's display
	* @param	mixed   Either a typical HTML attribute string or an associative array
	*/
	function HTML_QuickForm_CMS_file($elementName = null, $elementLabel = null, $options = array(), $attributes = null) {
		$this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
		$this->_persistantFreeze = true;
		$this->_appendName = false;
		$this->_type = 'cms_file';
		// set the options, do not bother setting bogus ones
		if (is_string($options)) {
			$this->file = $options;
		} else if (is_array($options)) {
			if (isset($options['file'])) {
				$this->file = $options['file'];
				unset($options['file']);
			}
			foreach ($options as $name => $value) {
				if (isset($this->_options[$name])) {
					if (is_array($value)) {
						$this->_options[$name] = @array_merge($this->_options[$name], $value);
					} else {
						$this->_options[$name] = $value;
					}
				}
			}
			$this->_options['upload_dir'] = empty($this->_options['upload_dir'])?UPLOAD_DIR:preg_replace('|/$|', '', $this->_options['upload_dir']);
		}
	}

	// }}}
	// {{{ _createElements()

	function _createElements() {
		if ($this->getValue()) {
			include_once 'n_filesystem.php';
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . $this->getValue())) {
				$filesize = NFilesystem::filesize_format(filesize($_SERVER['DOCUMENT_ROOT'] . $this->getValue()));
				$this->_elements[] =& new HTML_QuickForm_hidden($this->getName() . '__current', $this->getValue());
				$this->_elements[] =& new HTML_QuickForm_link(null, 'Current File', $this->getValue(), $this->getValue() . ($filesize?' (' . $filesize . ')':''), array('target'=>'_blank'));
				$this->_elements[] =& new HTML_QuickForm_checkbox($this->getName() . '__remove', null, 'Remove File', $this->getAttributes());
			} else {
				$this->setValue(false);
			}
		}
		$this->_elements[] =& new HTML_QuickForm_file($this->getName(), $this->getLabel(), $this->_options, $this->getAttributes());
	}

	// }}}
	// {{{ setValue()

	function setValue($value) {
		$this->_value = $value;
	}

	// }}}
	// {{{ getValue()

	function getValue() {
		return $this->_value;
	}

	// }}}
	// {{{ toHtml()

	function toHtml() {
		include_once('HTML/QuickForm/Renderer/Default.php');
		$renderer =& new HTML_QuickForm_Renderer_Default();
		$renderer->setElementTemplate($this->_wrap[0] . '{element}' . $this->_wrap[1]);
		// $renderer->setGroupElementTemplate('{element}', $this->getName());
		parent::accept($renderer);
		return $renderer->toHtml();
	}

	// }}}
	// {{{ accept()

	function accept(&$renderer, $required = false, $error = null) {
		$renderer->renderElement($this, $required, $error);
	}

	// }}}
	// {{{ onQuickFormEvent()

	/**
     * Called by HTML_QuickForm whenever form event is made on this element
     *
     * @param     string    Name of event
     * @param     mixed     event arguments
     * @param     object    calling object
     * @since     1.0
     * @access    public
     * @return    bool
     */
	function onQuickFormEvent($event, $arg, &$caller)
	{
		switch ($event) {
			case 'updateValue':
				if ($caller->getAttribute('method') == 'get') {
					return PEAR::raiseError('Cannot add a file upload field to a GET method form');
				}
				HTML_QuickForm_element::onQuickFormEvent($event, $arg, $caller);
				$caller->updateAttributes(array('enctype' => 'multipart/form-data'));
				$caller->setMaxFileSize();
				break;
			case 'addElement':
				$this->onQuickFormEvent('createElement', $arg, $caller);
				return $this->onQuickFormEvent('updateValue', null, $caller);
				break;
			case 'createElement':
				$className = get_class($this);
				$this->$className($arg[0], $arg[1], $arg[2]);
				break;
		}
		return true;
	} // end func onQuickFormEvent

	// }}}
	// {{{ _findValue()

	/**
	* Tries to find the element value from the values array
	* 
	* Needs to be redefined here as $_FILES is populated differently from 
	* other arrays when element name is of the form foo[bar]
	* 
	* @access	private
	* @return	mixed
	*/
	function _findValue(&$values) {
		$elementName = $this->getName();
		if (empty($_FILES) && isset($values[$elementName]) && $values[$elementName]) {
			return $values[$elementName];
		}
		if (empty($_FILES)) {
			return null;
		}
		if (isset($_FILES[$this->getName()])) {
			if ($_FILES[$this->getName()]['error']) {
				// return $caller->_defaultValues[$this->getName()];
			}
			return $_FILES[$elementName];
		} elseif (false !== ($pos = strpos($elementName, '['))) {
			$base  = substr($elementName, 0, $pos);
			$idx   = "['" . str_replace(array(']', '['), array('', "']['"), substr($elementName, $pos + 1, -1)) . "']";
			$props = array('name', 'type', 'size', 'tmp_name', 'error');
			$code  = "if (!isset(\$_FILES['{$base}']['name']{$idx})) {\n" .
			"	return null;\n" .
			"} else {\n" .
			"	\$value = array();\n";
			foreach ($props as $prop) {
				$code .= "	\$value['{$prop}'] = \$_FILES['{$base}']['{$prop}']{$idx};\n";
			}
			return eval($code . "	return \$value;\n}\n");
			// } else if (isset($cakll)) {

		} else {
			return null;
		}
	}

	// }}}

	// {{{ moveUploadedFile()

	/**
     * Moves an uploaded file into the destination 
     * 
     * @param    string  Destination directory path
     * @param    string  New file name
     * @access   public
     */
	function moveUploadedFile($dest, $fileName = '')
	{
		if ($dest != ''  && substr($dest, -1) != '/') {
			$dest .= '/';
		}
		$fileName = ($fileName != '') ? $fileName : basename($this->_value['name']);
		if (move_uploaded_file($this->_value['tmp_name'], $dest . $fileName)) {
			return true;
		} else {
			return false;
		}
	} // end func moveUploadedFile

	// }}}
	// {{{ isUploadedFile()

	/**
     * Checks if the element contains an uploaded file
     *
     * @access    public
     * @return    bool      true if file has been uploaded, false otherwise
     */
	function isUploadedFile()
	{
		return $this->_ruleIsUploadedFile($this->_value);
	} // end func isUploadedFile

	// }}}
	// {{{ _ruleIsUploadedFile()

	/**
     * Checks if the given element contains an uploaded file
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @access    private
     * @return    bool      true if file has been uploaded, false otherwise
     */
	function _ruleIsUploadedFile($elementValue)
	{
		if ((isset($elementValue['error']) && $elementValue['error'] == 0) ||
		(!empty($elementValue['tmp_name']) && $elementValue['tmp_name'] != 'none')) {
			return is_uploaded_file($elementValue['tmp_name']);
		} else {
			return false;
		}
	} // end func _ruleIsUploadedFile

	// }}}
	// {{{ _ruleCheckMaxFileSize()

	/**
     * Checks that the file does not exceed the max file size
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @param     int       Max file size
     * @access    private
     * @return    bool      true if filesize is lower than maxsize, false otherwise
     */
	function _ruleCheckMaxFileSize($elementValue, $maxSize)
	{
		if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
			return true;
		}
		return ($maxSize >= @filesize($elementValue['tmp_name']));
	} // end func _ruleCheckMaxFileSize

	// }}}
	// {{{ _ruleCheckMimeType()

	/**
     * Checks if the given element contains an uploaded file of the right mime type
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @param     mixed     Mime Type (can be an array of allowed types)
     * @access    private
     * @return    bool      true if mimetype is correct, false otherwise
     */
	function _ruleCheckMimeType($elementValue, $mimeType)
	{
		if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
			return true;
		}
		if (is_array($mimeType)) {
			return in_array($elementValue['type'], $mimeType);
		}
		return $elementValue['type'] == $mimeType;
	} // end func _ruleCheckMimeType

	// }}}
	// {{{ _ruleCheckFileName()

	/**
     * Checks if the given element contains an uploaded file of the filename regex
     *
     * @param     array     Uploaded file info (from $_FILES)
     * @param     string    Regular expression
     * @access    private
     * @return    bool      true if name matches regex, false otherwise
     */
	function _ruleCheckFileName($elementValue, $regex)
	{
		if (!HTML_QuickForm_file::_ruleIsUploadedFile($elementValue)) {
			return true;
		}
		return preg_match($regex, $elementValue['name']);
	} // end func _ruleCheckFileName

	// }}}
	// {{{ _ruleCheckRemove()

	/**
     * Checks if the removed element should be uploaded
     *
     * @param     values    All values
     * @access    private
     * @return    bool      true if remove, array of errors otherwise
     */
	function _ruleCheckRemove($values)
	{
		$errors = array();
		if (isset($values['media_file__remove'])) {
			$errors['media_file'] = 'Media File is a required field';
		}
		return empty($errors)?true:$errors;
	} // end func _ruleCheckRemove

	// }}}

}

NQuickForm::registerElementType('cmserror', 'n_quickform.php', 'HTML_QuickForm_cmserror');
NQuickForm::registerElementType('cmsalert', 'n_quickform.php', 'HTML_QuickForm_cmsalert');
NQuickForm::registerElementType('cms_file', 'controller/form.php', 'HTML_QuickForm_CMS_file');
NQuickForm::registerElementType('foreignkey', 'controller/form.php', 'HTML_QuickForm_foreignkey');
NQuickForm::registerElementType('fckeditor', 'controller/form.php', 'HTML_QuickForm_fckeditor');
//NQuickForm::registerElementType('CAPTCHA_Image', 'n_quickform.php', 'HTML_QuickForm_captcha');
// NQuickForm::registerElementType('nterchange_date', 'HTML/QuickForm/nterchange_date.php', 'HTML_QuickForm_nterchange_date');
?>
