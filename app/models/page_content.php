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
 * @category   Page Content Model
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class PageContent extends NModel {
	function __construct() {
		$this->__table = 'page_content';
		$this->_order_by = 'page_content.content_order, page_content.id';

		$this->form_field_options['timed_start'] = array('addEmptyOption'=>true);
		$this->form_field_options['timed_end'] = array('addEmptyOption'=>true);

		$this->form_elements['col_xs'] = array('select', 'col_xs', 'Width (xs)', $this->grid_options('col_xs'));
		$this->form_elements['col_sm'] = array('select', 'col_sm', 'Width (sm)', $this->grid_options('col'));
		$this->form_elements['col_md'] = array('select', 'col_md', 'Width (md)', $this->grid_options('col'));
		$this->form_elements['col_lg'] = array('select', 'col_lg', 'Width (lg)', $this->grid_options('col'));

		$this->form_elements['row_xs'] = array('select', 'row_xs', 'Height (xs)', $this->grid_options('row_xs'));
		$this->form_elements['row_sm'] = array('select', 'row_sm', 'Height (sm)', $this->grid_options('row'));
		$this->form_elements['row_md'] = array('select', 'row_md', 'Height (md)', $this->grid_options('row'));
		$this->form_elements['row_lg'] = array('select', 'row_lg', 'Height (lg)', $this->grid_options('row'));

		$this->form_elements['offset_col_xs'] = array('select', 'offset_col_xs', 'Offset&nbsp;Width&nbsp;(xs)', $this->grid_options('offset_col_xs'));
		$this->form_elements['offset_col_sm'] = array('select', 'offset_col_sm', 'Offset&nbsp;Width&nbsp;(sm)', $this->grid_options('offset_col'));
		$this->form_elements['offset_col_md'] = array('select', 'offset_col_md', 'Offset&nbsp;Width&nbsp;(md)', $this->grid_options('offset_col'));
		$this->form_elements['offset_col_lg'] = array('select', 'offset_col_lg', 'Offset&nbsp;Width&nbsp;(lg)', $this->grid_options('offset_col'));

		$this->form_elements['offset_row_xs'] = array('select', 'offset_row_xs', 'Offset&nbsp;Height&nbsp;(xs)', $this->grid_options('offset_row_xs'));
		$this->form_elements['offset_row_sm'] = array('select', 'offset_row_sm', 'Offset&nbsp;Height&nbsp;(sm)', $this->grid_options('offset_row'));
		$this->form_elements['offset_row_md'] = array('select', 'offset_row_md', 'Offset&nbsp;Height&nbsp;(md)', $this->grid_options('offset_row'));
		$this->form_elements['offset_row_lg'] = array('select', 'offset_row_lg', 'Offset&nbsp;Height&nbsp;(lg)', $this->grid_options('offset_row'));

		$this->form_elements['pull_xs'] = array('select', 'pull_xs', 'Pull (xs)', $this->grid_options('pull_xs'));
		$this->form_elements['pull_sm'] = array('select', 'pull_sm', 'Pull (sm)', $this->grid_options('pull'));
		$this->form_elements['pull_md'] = array('select', 'pull_md', 'Pull (md)', $this->grid_options('pull'));
		$this->form_elements['pull_lg'] = array('select', 'pull_lg', 'Pull (lg)', $this->grid_options('pull'));

		$this->form_elements['gutter_xs'] = array('text', 'gutter_xs', 'Gutter (xs)');
		$this->form_elements['gutter_sm'] = array('text', 'gutter_sm', 'Gutter (sm)');
		$this->form_elements['gutter_md'] = array('text', 'gutter_md', 'Gutter (md)');
		$this->form_elements['gutter_lg'] = array('text', 'gutter_lg', 'Gutter (lg)');

		parent::__construct();
	}

	function getContainerContent($page_id, $container_id, $admin=false, $page_content_id=null) {
		$options = array('conditions'=>"page_id=$page_id AND page_template_container_id=$container_id");
		if ($admin == false) {
			$options['conditions'] .= ' AND cms_workflow=0';
		}
		if ($page_content_id) {
			$options['conditions'] .= " AND id=$page_content_id";
		}
		return $this->find($options);
	}

	function isWorkflowContent($asset, $asset_id) {
		if (SITE_WORKFLOW) {
			$this->reset();
			$page_model = &NModel::singleton('page');
			$page_model->reset();
			$join = 'INNER JOIN ' . $page_model->tableName() . ' ON ';
			$join .= $this->tableName() . '.page_id=' . $page_model->tableName() . '.' . $page_model->primaryKey();
			$this->content_asset = $asset;
			$this->content_asset_id = $asset_id;
			if ($this->find(array('join'=>$join, 'conditions'=>$page_model->tableName() . '.workflow_group_id != 0'))) {
				return true;
			}
		}
		return false;
	}

	function &getActivePageContent($asset, $asset_id) {
		if (!$asset || !$asset_id) {
			$ret = false;
			return $ret;
		}
		$page = &NModel::singleton('page');
		$this->reset();
		$this->content_asset = (string) $asset;
		$this->content_asset_id = (int) $asset_id;
		$page_contents = false;
		if ($this->find()) {
			$page_contents = array();
			while ($this->fetch()) {
				$page->reset();
				// Only get active pages.
				$page->cms_deleted = 0;
				if (!$page->get($this->page_id)) {
					continue;
				}
				$page_contents[] = clone($this);
			}
		}
		return $page_contents;
	}

	/**
	 * deleteOrphanedPageContent - Remove all content from the page_content table for 
	 * 	a particular page_id. This is called from the page model when a page is deleted.
	 *
	 * @param	int		The id of a page that has been deleted.
	 * @return 	void
	 **/
	function deleteOrphanedPageContent($page_id) {
		$this->page_id = $page_id;
		if ($this->find()) {
			while ($this->fetch()) {
				$this->delete();
			}
		}
	}

  function to_percent($col) {
    $percent = round(($col/12)*100);
    return "{$percent}%";
  }

  function grid_options($name) {
    $col = array(
      'inherit' => 'Inherit', 
      '12'      => '12 cols', 
      '11'      => '11 cols', 
      '10'      => '10 cols', 
      '9'       => '9 cols',
      '8'       => '8 cols',
      '7'       => '7 cols',
      '6'       => '6 cols',
      '5'       => '5 cols',
      '4'       => '4 cols',
      '3'       => '3 cols',
      '2'       => '2 cols',
      '1'       => '1 col', 
      'auto'    => 'Auto');
    $col_xs = array(
      '12'      => '12 cols', 
      '11'      => '11 cols', 
      '10'      => '10 cols', 
      '9'       => '9 cols',
      '8'       => '8 cols',
      '7'       => '7 cols',
      '6'       => '6 cols',
      '5'       => '5 cols',
      '4'       => '4 cols',
      '3'       => '3 cols',
      '2'       => '2 cols',
      '1'       => '1 col',
      'auto'    => 'Auto');

    $row = array('inherit'=>'Inherit', 'auto'=>'Auto', 
       '1'=> '1 row',   '2'=> '2 rows',  '3'=> '3 rows',  '4'=> '4 rows',  '5'=> '5 rows',  '6'=> '6 rows',  '7'=> '7 rows',  '8'=> '8 rows',  '9'=> '9 rows', '10'=>'10 rows', 
      '11'=>'11 rows', '12'=>'12 rows', '13'=>'13 rows', '14'=>'14 rows', '15'=>'15 rows', '16'=>'16 rows', '17'=>'17 rows', '18'=>'18 rows', '19'=>'19 rows', '20'=>'20 rows', 
      '21'=>'21 rows', '22'=>'22 rows', '23'=>'23 rows', '24'=>'24 rows', '25'=>'25 rows', '26'=>'26 rows', '27'=>'27 rows', '28'=>'28 rows', '29'=>'29 rows', '30'=>'30 rows', 
      '31'=>'31 rows', '32'=>'32 rows', '33'=>'33 rows', '34'=>'34 rows', '35'=>'35 rows', '36'=>'36 rows', '37'=>'37 rows', '38'=>'38 rows', '39'=>'39 rows', '40'=>'40 rows', 
      '41'=>'41 rows', '42'=>'42 rows', '43'=>'43 rows', '44'=>'44 rows', '45'=>'45 rows', '46'=>'46 rows', '47'=>'47 rows', '48'=>'48 rows', '49'=>'49 rows', '50'=>'50 rows'); 
    $row_xs = array('auto'=>'Auto', 
       '1'=> '1 row',   '2'=> '2 rows',  '3'=> '3 rows',  '4'=> '4 rows',  '5'=> '5 rows',  '6'=> '6 rows',  '7'=> '7 rows',  '8'=> '8 rows',  '9'=> '9 rows', '10'=>'10 rows', 
      '11'=>'11 rows', '12'=>'12 rows', '13'=>'13 rows', '14'=>'14 rows', '15'=>'15 rows', '16'=>'16 rows', '17'=>'17 rows', '18'=>'18 rows', '19'=>'19 rows', '20'=>'20 rows', 
      '21'=>'21 rows', '22'=>'22 rows', '23'=>'23 rows', '24'=>'24 rows', '25'=>'25 rows', '26'=>'26 rows', '27'=>'27 rows', '28'=>'28 rows', '29'=>'29 rows', '30'=>'30 rows', 
      '31'=>'31 rows', '32'=>'32 rows', '33'=>'33 rows', '34'=>'34 rows', '35'=>'35 rows', '36'=>'36 rows', '37'=>'37 rows', '38'=>'38 rows', '39'=>'39 rows', '40'=>'40 rows', 
      '41'=>'41 rows', '42'=>'42 rows', '43'=>'43 rows', '44'=>'44 rows', '45'=>'45 rows', '46'=>'46 rows', '47'=>'47 rows', '48'=>'48 rows', '49'=>'49 rows', '50'=>'50 rows'); 

    $offset_col = array(
      'inherit' => 'Inherit', 
      '0'       => '0 col', 
      '1'       => '1 col', 
      '2'       => '2 cols',
      '3'       => '3 cols',
      '4'       => '4 cols',
      '5'       => '5 cols',
      '6'       => '6 cols',
      '7'       => '7 cols',
      '8'       => '8 cols',
      '9'       => '9 cols',
      '10'      => '10 cols', 
      '11'      => '11 cols', 
      '12'      => '12 cols');
    $offset_col_xs = array(
      '0'       => '0 col', 
      '1'       => '1 col', 
      '2'       => '2 cols',
      '3'       => '3 cols',
      '4'       => '4 cols',
      '5'       => '5 cols',
      '6'       => '6 cols',
      '7'       => '7 cols',
      '8'       => '8 cols',
      '9'       => '9 cols',
      '10'      => '10 cols', 
      '11'      => '11 cols', 
      '12'      => '12 cols');

    $offset_row = array('inherit'=>'Inherit', '0'=>'0 rows', 
      '-25'=>'-25 rows', '-24'=>'-24 rows', '-23'=>'-23 rows', '-22'=>'-22 rows', '-21'=>'-20 rows', 
      '-19'=>'-19 rows', '-18'=>'-18 rows', '-17'=>'-17 rows', '-16'=>'-16 rows', '-16'=>'-16 rows', 
      '-15'=>'-15 rows', '-14'=>'-14 rows', '-13'=>'-13 rows', '-12'=>'-12 rows', '-11'=>'-11 rows', 
      '-10'=>'-10 rows',  '-9'=> '-9 rows',  '-8'=> '-8 rows',  '-7'=> '-7 rows',  '-6'=> '-6 rows', 
       '-5'=> '-5 rows',  '-4'=> '-4 rows',  '-3'=> '-3 rows',  '-2'=> '-2 rows',  '-1'=> '-1 row', 
        '1'=>  '1 row',    '2'=>  '2 rows',   '3'=>  '3 rows',   '4'=>  '4 rows',   '5'=>  '5 rows', 
        '6'=>  '6 rows',   '7'=>  '7 rows',   '8'=>  '8 rows',   '9'=>  '9 rows',  '10'=> '10 rows', 
       '11'=> '11 rows',  '12'=> '12 rows',  '13'=> '13 rows',  '14'=> '14 rows',  '15'=> '15 rows', 
       '16'=> '16 rows',  '17'=> '17 rows',  '18'=> '18 rows',  '19'=> '19 rows',  '20'=> '20 rows', 
       '21'=> '21 rows',  '22'=> '22 rows',  '23'=> '23 rows',  '24'=> '24 rows',  '25'=> '25 rows'); 

    $offset_row_xs = array('0'=>'0 rows', 
      '-25'=>'-25 rows', '-24'=>'-24 rows', '-23'=>'-23 rows', '-22'=>'-22 rows', '-21'=>'-20 rows', 
      '-19'=>'-19 rows', '-18'=>'-18 rows', '-17'=>'-17 rows', '-16'=>'-16 rows', '-16'=>'-16 rows', 
      '-15'=>'-15 rows', '-14'=>'-14 rows', '-13'=>'-13 rows', '-12'=>'-12 rows', '-11'=>'-11 rows', 
      '-10'=>'-10 rows',  '-9'=> '-9 rows',  '-8'=> '-8 rows',  '-7'=> '-7 rows',  '-6'=> '-6 rows', 
       '-5'=> '-5 rows',  '-4'=> '-4 rows',  '-3'=> '-3 rows',  '-2'=> '-2 rows',  '-1'=> '-1 row', 
        '1'=>  '1 row',    '2'=>  '2 rows',   '3'=>  '3 rows',   '4'=>  '4 rows',   '5'=>  '5 rows', 
        '6'=>  '6 rows',   '7'=>  '7 rows',   '8'=>  '8 rows',   '9'=>  '9 rows',  '10'=> '10 rows', 
       '11'=> '11 rows',  '12'=> '12 rows',  '13'=> '13 rows',  '14'=> '14 rows',  '15'=> '15 rows', 
       '16'=> '16 rows',  '17'=> '17 rows',  '18'=> '18 rows',  '19'=> '19 rows',  '20'=> '20 rows', 
       '21'=> '21 rows',  '22'=> '22 rows',  '23'=> '23 rows',  '24'=> '24 rows',  '25'=> '25 rows'); 

    $pull = array(
      'inherit' => 'Inherit', 
      'none'    => 'None', 
      'right'   => 'Right', 
      'left'    => 'Left');
    $pull_xs = array(
      'none'    => 'None', 
      'right'   => 'Right', 
      'left'    => 'Left');

    switch ($name) {
      case 'col':            return $col;
      case 'col_xs':         return $col_xs;
      case 'row':            return $row;
      case 'row_xs':         return $row_xs;
      case 'offset_col':     return $offset_col;
      case 'offset_col_xs':  return $offset_col_xs;
      case 'offset_row':     return $offset_row;
      case 'offset_row_xs':  return $offset_row_xs;
      case 'pull':           return $pull;
      case 'pull_xs':        return $pull_xs;
    }
  }
}
?>
