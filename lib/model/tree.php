<?php
require_once 'n_model.php';
require_once 'n_cache.php';
/**
 * Tree Class reads from the db to gather the site architecture
 *
 * This class is a framework for providing menu building functionality.
 * It can get the root node, get children, get ancestors, get parents
 * as well as accurately parse a url into a database table id.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tree
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 * @since      File available since Release 3.0
 */
class ModelTree extends NModel {
	/**
	 * the root_node of the site architecture
	 *
	 * @access private
	 * @var string
	 */
	var $root_node = false;

	/**
	 * nodes array
	 *
	 * @access private
	 * @var array
	 */
	var $nodes = array();

	/**
	 * Class Constructor
	 *
	 * the constructor of the class. sets the table and field variables
	 * as well as the root_node
	 *
	 * @access	public
	 * @author	Tim Glen <tim@nonfiction.ca>
	 * @param 	int $id the table id to retrieve
	 * @param 	string $orderby table fields by which to order the result
	 * @return 	null
	 */
	function __construct() {
		parent::__construct();
		if ((defined('TREE_CACHING') && TREE_CACHING == false) || false == ($nodes = NCache::getMenu())) {
			$pk = $this->primaryKey();
			$model = clone($this);
			$model->reset();
			if ($model->find()) {
				while ($model->fetch()) {
					$this->nodes[$model->$pk] = $model->toArray();
				}
			}
			unset($model);
			if (defined('TREE_CACHING') && TREE_CACHING != false) {
				NCache::createMenu($this->nodes);
			}
		} else {
			$this->nodes =& $nodes;
		}
	}

	function getCache() {
		return NCache::getMenu();
	}

	function createCache($con) {
		return NCache::createMenu($con);
	}

	function removeCache() {
		return NCache::removeMenu();
	}

	/**
	 * getRootNode finds the root_node of the architecture
	 *
	 * @access	public
	 * @author	Tim Glen <tim@nonfiction.ca>
	 * @return 	int table id of the root_node
	 * @see		getParent()
	 */
	function getRootNode() {
		if (!$this->root_node) {
			$model = clone($this);
			$model->reset();
			$table = $model->table();
			$parent_id = $table['parent_id'];
			$conditions = 'parent_id' . ($parent_id & N_DAO_NOTNULL?'=0':' is NULL');
			if ($model->find(array('select'=>'id', 'conditions'=>$conditions), true)) {
				$this->root_node = (int) $model->{$model->primaryKey()};
			}
			unset($model);
		}
		return $this->root_node;
	}

	/**
	 * isRootNode checks if an id is the root_node
	 *
	 * isRootNode
	 *
	 * @access	public
	 * @author	Tim Glen <tim@nonfiction.ca>
	 * @param 	int $id the table id to test.
	 * @return 	boolean
	 * @see		getParent()
	 */
	function isRootNode($id) {
		return ($id == $this->getRootNode())?true:false;
	}

	function getInfo($id) {
		if ($id) {
			return (isset($this->nodes[(int)$id]))?$this->nodes[(int)$id]:false;
		}
		return false;
	}

	function getParent($id, $active=true, $visible=false) {
		if ($id) {
			if ($active && $this->nodes[$id]['active'] == 0) {
				return false;
			} else if ($visible && $this->nodes[$id]['visible'] == 0) {
				return false;
			} else {
				return (isset($this->nodes[(int)$id]['parent_id']))?$this->nodes[(int)$id]['parent_id']:false;
			}
		}
		return false;
	}

	function getAncestors($id, $active=true, $visible=false, $ancestors=array()) {
		$parent_id = $this->getParent($id, $active, $visible);
		if ($parent_id != 0 && $parent_id != $id) {
			$parent = $this->getInfo($parent_id);
			if ($parent && (!$active || ($active && $parent['active'] != 0)) && (!$visible || ($visible && $parent['visible'] != 0))) {
				$ancestors[] = $parent;
			}
			$ancestors = $this->getAncestors($parent_id, $active, $visible, $ancestors);
		}
		return $ancestors;
	}

	function getChildren($id, $active=true, $visible=true) {
		$id = $id?(int) $id:null;
		$fields = $this->fields();
		$sql = 'SELECT ' . $this->primaryKey() . ' FROM ' . $this->__table;
		$table = $this->table();
		$parent_id = $table['parent_id'];
		$sql .= ' WHERE parent_id' . ($id?"=$id":($parent_id & N_DAO_NOTNULL?'=0':' is NULL'));
		if ($active) {
			$sql .= ' AND active != 0';
		}
		if ($visible) {
			$sql .= ' AND visible != 0';
		}
		if (in_array('cms_deleted', $fields)) {
			$sql .= ' AND cms_deleted=0';
		}
		$sql .= ' ORDER BY sort_order, ' . $this->primaryKey();
		$res = &$this->query($sql);
		if (PEAR::isError($res)) return false;
		$children = array();
		while ($row = $res->fetchRow(MDB2_FETCHMODE_ORDERED)) {
 			$children[] = $this->getInfo($row[0]);
		}
		unset($res);
		return $children;
	}

	function getAllChildren($id, $active=true, $visible=true) {
		$children = array();
		if (!$id) $id = 0;
		$children = $this->getChildren($id, $active, $visible);
		foreach ($children as $child) {
			if ($this->isBranch($child['id'], $active, $visible)) {
				$subchildren = $this->getAllChildren($child['id'], $active, $visible, $children);
				$children = array_merge($children, $subchildren);
			}
		}
		return $children;
	}

	function getSiblings($id, $active=true, $visible=true) {
		$pid = $this->getParent($id, $active, $visible);
		$siblings = $this->getChildren($pid, $active, $visible);

		return $siblings;
	}

	function isBranch($id, $active=true, $visible=false) {
		if (count($this->getChildren($id, $active, $visible)) > 0) {
			return true;
		} else {
			return false;
		}
	}

	function getNavAsList($page_id = false, $treat_title=false, $title_recurse=true) {
		$menu = new Menu();
		if ($page_id == false) {
			$page_id = $menu->getRootNode();
		}
		if (CURRENT_SITE == 'admin') {
			$checkactive = false;
			$checkvisible = false;
		} else {
			$checkactive = true;
			$checkvisible = true;
		}
		$main_nav = $menu->getChildren($page_id, $checkactive, $checkvisible);
		$html = '';
		foreach($main_nav as $nav) {
			$html .= "<div>";
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $nav['id']) $html .= '<b>';
			$html .= Menu::getLink($nav, $treat_title);
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $nav['id']) $html .= '</b>';
			$html .= "</div>\n";
			if ($menu->isBranch($nav['id'], $checkactive, $checkactive)) {
				$sub_treat_title = ($title_recurse == true)?$treat_title:false;
				$html .= Menu::getChildList($menu, $nav['id'], $sub_treat_title);
			}
		}
		return $html;
	}

	function getChildList(&$menu, $page_id, $treat_title=false) {
		if (CURRENT_SITE == 'admin') {
			$checkactive = false;
			$checkvisible = false;
		} else {
			$checkactive = true;
			$checkvisible = true;
		}
		$children = $menu->getChildren($page_id, $checkactive, $checkvisible);
		$html = '';
		$submenu = array();
		foreach ($children as $child) {
			$html .= "\t<li>";
			$info = $menu->getInfo($child['id']);
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $info['id']) $html .= '<b>';
			$html .= Menu::getLink($info, $treat_title);
			if (isset($GLOBALS['page_id']) && $GLOBALS['page_id'] == $info['id']) $html .= '</b>';
			if ($menu->isBranch($child['id'], $checkactive, $checkactive)) {
				$html .= Menu::getChildList($menu, $child['id'], $treat_title);
			}
			$html .= "</li>\n";
		}
		if ($html) {
			$html = "<ul>\n" . $html . "</ul>\n\n";
		}
		return $html;
	}

	function getDirectories($url) {
		$url = parse_url($url);
		$path = $url['path'];
		$path = preg_replace('/^\//', '', $path);
		$path = preg_replace('/\/$/', '', $path);
		$extensionpos = strrpos($path, '.');
		if ($extensionpos) {
		  $path = substr($path, 0, $extensionpos);
		}
		$path = str_replace('.' . DEFAULT_PAGE_EXTENSION, '', $path);
		$path = preg_replace('/[\/]?index$/', '', $path);
		$directories = explode('/', $path);

		return $directories;
	}

	function IdToURL($id, $active=true, $visible=false, $url = '') {
		if ($id != $this->getRootNode()) {
			$info = $this->getInfo($id);
			$filename = ($this->isBranch($id, $active, $visible))?$info['filename'] . '/':$info['filename'] . '.' . DEFAULT_PAGE_EXTENSION;
			$url = $filename . $url;
			$pid = $this->getParent($id, $active, $visible);
			if ($pid && $this->getRootNode() != $pid) return $this->idToURL($pid, $active, $visible, $url);
		}
		return '/' . $url;
	}

	function buildPath($id, $parent_id=0, $path='') {
		if ($id != $this->getRootNode()) {
			if ($path == '') {
				$res = &$this->_db->query('SELECT id, filename, parent_id FROM page WHERE id=' . $id);
				$row = $res->fetchRow(MDB2_FETCHMODE_ASSOC);
				$res->free();
				$path .= '/' . $row['filename'];
				$parent_id = $row['parent_id']?(int) $row['parent_id']:'NULL';
			}
			$res = &$this->_db->query('SELECT id, parent_id, filename FROM page where id=' . $parent_id);
			$info = $res->fetchRow(MDB2_FETCHMODE_ASSOC);
			$res->free();
			if ($info['id'] != $this->getRootNode()) {
				$path = '/' . $info['filename'] . $path;
				$path = $this->buildPath($info['id'], $info['parent_id'], $path);
			} else {
				return $path;
			}
		}
		return $path;
	}

	function checkURL($url) {
		if ($page = $this->URLToId($url)) {
			return array('id', $page);
		}
	}

	function URLToId($url) {
		$directories = $this->getDirectories($url);
		$id = $this->getChildId($directories);

		return $id;
	}

	function getChildId($directories, $parent_id = 0, $count = 0) {
		if (!$parent_id)
			$parent_id = $this->getRootNode();
		if ($count < sizeof($directories) && $directories[$count]) {
			$children = $this->getChildren($parent_id, false, false);
			foreach ($children as $id=>$child) {
				if ($child['filename'] == $directories[$count] && $parent_id == $this->getParent($child['id'])) {
					return $this->getChildId($directories, $child['id'], $count + 1);
				}
			}
			return false;
		} else {
			return $parent_id;
		}
	}
}
?>
