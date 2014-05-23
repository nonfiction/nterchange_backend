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
      '12'      => $this->to_percent(12), 
      '11'      => $this->to_percent(11), 
      '10'      => $this->to_percent(10), 
      '9'       => $this->to_percent(9),
      '8'       => $this->to_percent(8),
      '7'       => $this->to_percent(7),
      '6'       => $this->to_percent(6),
      '5'       => $this->to_percent(5),
      '4'       => $this->to_percent(4),
      '3'       => $this->to_percent(3),
      '2'       => $this->to_percent(2),
      '1'       => $this->to_percent(1), 
      'auto'    => 'Auto');
    $col_xs = array(
      '12'      => $this->to_percent(12), 
      '11'      => $this->to_percent(11), 
      '10'      => $this->to_percent(10), 
      '9'       => $this->to_percent(9),
      '8'       => $this->to_percent(8),
      '7'       => $this->to_percent(7),
      '6'       => $this->to_percent(6),
      '5'       => $this->to_percent(5),
      '4'       => $this->to_percent(4),
      '3'       => $this->to_percent(3),
      '2'       => $this->to_percent(2),
      '1'       => $this->to_percent(1),
      'auto'    => 'Auto');

    $row = array('inherit'=>'Inherit', 'auto'=>'Auto', 
       '1'=> '1em',  '2'=> '2em',  '3'=> '3em',  '4'=> '4em',  '5'=> '5em',  '6'=> '6em',  '7'=> '7em',  '8'=> '8em',  '9'=> '9em', '10'=>'10em', 
      '11'=>'11em', '12'=>'12em', '13'=>'13em', '14'=>'14em', '15'=>'15em', '16'=>'16em', '17'=>'17em', '18'=>'18em', '19'=>'19em', '20'=>'20em', 
      '21'=>'21em', '22'=>'22em', '23'=>'23em', '24'=>'24em', '25'=>'25em', '26'=>'26em', '27'=>'27em', '28'=>'28em', '29'=>'29em', '30'=>'30em', 
      '31'=>'31em', '32'=>'32em', '33'=>'33em', '34'=>'34em', '35'=>'35em', '36'=>'36em', '37'=>'37em', '38'=>'38em', '39'=>'39em', '40'=>'40em', 
      '41'=>'41em', '42'=>'42em', '43'=>'43em', '44'=>'44em', '45'=>'45em', '46'=>'46em', '47'=>'47em', '48'=>'48em', '49'=>'49em', '50'=>'50em'); 
    $row_xs = array('auto'=>'Auto', 
       '1'=> '1em',  '2'=> '2em',  '3'=> '3em',  '4'=> '4em',  '5'=> '5em',  '6'=> '6em',  '7'=> '7em',  '8'=> '8em',  '9'=> '9em', '10'=>'10em', 
      '11'=>'11em', '12'=>'12em', '13'=>'13em', '14'=>'14em', '15'=>'15em', '16'=>'16em', '17'=>'17em', '18'=>'18em', '19'=>'19em', '20'=>'20em', 
      '21'=>'21em', '22'=>'22em', '23'=>'23em', '24'=>'24em', '25'=>'25em', '26'=>'26em', '27'=>'27em', '28'=>'28em', '29'=>'29em', '30'=>'30em', 
      '31'=>'31em', '32'=>'32em', '33'=>'33em', '34'=>'34em', '35'=>'35em', '36'=>'36em', '37'=>'37em', '38'=>'38em', '39'=>'39em', '40'=>'40em', 
      '41'=>'41em', '42'=>'42em', '43'=>'43em', '44'=>'44em', '45'=>'45em', '46'=>'46em', '47'=>'47em', '48'=>'48em', '49'=>'49em', '50'=>'50em'); 

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
      case 'col':       return $col;
      case 'col_xs':    return $col_xs;
      case 'row':       return $row;
      case 'row_xs':    return $row_xs;
      case 'pull':      return $pull;
      case 'pull_xs':   return $pull_xs;
    }
  }
}
?>
