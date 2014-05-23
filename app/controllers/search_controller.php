<?php
require_once 'n_filesystem.php';
require_once 'n_server.php';
require_once 'app_controller.php';
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
 * @category   Search (w/mnogosearch)
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class SearchController extends AppController {
	function __construct() {
		$this->name = 'search';
		$this->versioning = true;
		$this->base_view_dir = ROOT_DIR;
		parent::__construct();
	}

	function search() {
		include_once 'HTTP/Header.php';
		$header = new HTTP_Header;
		$search = htmlentities($this->getParam('search'));
		$html = '';
		$this->set('request_uri', NServer::env('PHP_SELF'));
		$this->set('search', $search);
		$html .= $this->render(array('action'=>'search_form', 'return'=>true));
		if ($search && function_exists('udm_alloc_agent')) {
			$current_page = $this->getParam('page')?(int) $this->getParam('page'):0;

			// instantiate the search agent
			$udm_agent = udm_alloc_agent(DB_DSN_SEARCH);
			$page_size = 10;

			// pull in the stopwords and remove them from the query
			$stopfile = defined('MNOGO_STOPFILE')?constant('MNOGO_STOPFILE'):'/usr/local/mnogosearch/etc/stopwords/en.huge.sl';
			$tmp = file($stopfile);
			$stopwords = array();
			for($i=0;$i<count($tmp);$i++) {
				$tmp[$i] = trim($tmp[$i]);
				if (!$tmp[$i] || preg_match('/^(#|Language:|Charset:)/', $tmp[$i])) {
					continue;
				}
				$stopwords[] = $tmp[$i];
			}
			unset($tmp);
			$stopped_words = array();
			foreach ($stopwords as $stopword) {
				if (($tmp_search = preg_replace("/\b$stopword\b/", '', $search)) != $search) {
					$stopped_words[] = $stopword;
					$search = str_replace('  ', ' ', $tmp_search);
				}
			}

			// set some values
			// paging values
			udm_set_agent_param($udm_agent, UDM_PARAM_PAGE_SIZE, $page_size);
			udm_set_agent_param($udm_agent, UDM_PARAM_PAGE_NUM, $current_page);
			udm_set_agent_param($udm_agent, UDM_PARAM_QUERY, $search);
			udm_set_agent_param($udm_agent, UDM_PARAM_STOPFILE, $stopfile);

			// perform the search
			$res = udm_find($udm_agent, $search);
			// get search result values
			$total_rows = udm_get_res_param($res, UDM_PARAM_FOUND);
			$total_rows = $total_rows > 0?$total_rows-1:$total_rows;
			$page_rows = udm_get_res_param($res, UDM_PARAM_NUM_ROWS);
			$first_doc = udm_get_res_param($res, UDM_PARAM_FIRST_DOC);
			$last_doc = udm_get_res_param($res, UDM_PARAM_LAST_DOC);
			$total_pages = ceil($total_rows/$page_size);

			// set general template values
			$this->set('rows', $total_rows);
			$template_pages = array();
			for ($i=0;$i<$total_pages;$i++) {
				$template_pages[] = $i+1;
			}
			$search_url = NServer::env('PHP_SELF') . '?search=' . $search . '&amp;page=';
			$this->set('stopped_words', $stopped_words);
			$this->set('search_url', $search_url);
			$this->set('current_page', $current_page);
			$this->set('pages', $template_pages);
			$this->set('previous_page', ($current_page>0)?$current_page-1:-1);
			$this->set('next_page', ($current_page+1<$total_pages)?$current_page+1:-1);

			// gather the results and pass them to the template
			$items = array();
			for ($i=0;$i<$page_rows;$i++) {
				$item['title'] = udm_get_res_field($res, $i, UDM_FIELD_TITLE);
				$item['url'] = udm_get_res_field($res, $i, UDM_FIELD_URL);
				$item['text'] = udm_get_res_field($res, $i, UDM_FIELD_TEXT);
				$item['size'] = udm_get_res_field($res, $i, UDM_FIELD_SIZE);
				$item['filesize'] = udm_get_res_field($res, $i, UDM_FIELD_SIZE);
				if ($item['filesize']) $item['filesize'] = NFilesystem::filesize_format($item['filesize']);
				$item['rating'] = udm_get_res_field($res, $i, UDM_FIELD_RATING);
				$item['title'] = $item['title']?$this->cleanupItem(htmlspecialchars($item['title'])):basename($item['url']);
				$item['text'] = $this->cleanupItem($item['text']);
				$items[] = $item;
			}
			$this->set('items', $items);
			$html .= $this->render(array('action'=>'found_items', 'return'=>true));
			udm_free_res($res);
			udm_free_agent($udm_agent);
		} else {
			$html .= '<p>You have not entered any search queries - please enter one in the form above.</p>';
		}
		return $html;
	}

	function search404() {
		if ($this->getParam('search')) {
			return $this->search();
		}
		$uri = NServer::env('PHP_SELF');
		$words = explode('/', $uri);
		// remove any empty elements
		foreach ($words as $i=>$val) {
			if(empty($val)){
				unset($words[$i]);
			}
		}
		$this->setParam('search', urldecode(implode(' ', $words)));
		return $this->search();
	}

	function cleanupItem($var) {
		include_once 'model/value_cast.php';
		$var = ValueCast::toLatinISO($var);
		// some weird mnogosearch characters to replace
		$var = preg_replace('/\\x02/', '<b>', $var);
		$var = preg_replace('/\\x03/', '</b>', $var);
		return $var;
	}
}
?>
