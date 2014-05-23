<?php
require_once 'app/controllers/asset_controller.php';
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
 * @category   HTTP Redirect
 * @author     Tim Glen <tim@nonfiction.ca>
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class RedirectController extends AssetController {
	function __construct() {
		$this->name = 'redirect';
		$this->versioning = true;
		$this->base_view_dir = ROOT_DIR;
		$this->redirect_delay = 3;
		parent::__construct();
	}
	
	/**
	 * checkRedirect - Look in the database to see if there's a redirect to follow.
	 *		If there is - hit renderRedirect.
	 *
	 * @return void
	 **/
	function checkRedirect() {
		include_once 'n_server.php';
		$current_url = NServer::env('REQUEST_URI');
		// Check DB for direct match.
		$model = &$this->getDefaultModel();
		$model->reset();
		$model->url = $current_url;
		$model->regex = 0;
		$model->find();
		while ($model->fetch()) {
			$this->renderRedirect($model->toArray());
		}
		// Let's look at the regex matches.
		$model->reset();
		$model->regex = 1;
		$model->find();
		$urls = &$model->fetchAll();
		foreach ($urls as $url) {
			if ($url->regex != 0 && eregi($url->url, $current_url)) {
				$this->renderRedirect($url->toArray());
			}
		}
	}
	
	/**
	 * renderRedirect - Render a page that automatically redirects to the required location.
	 * 		Doing it this way allows your regular website statistics to generate reports.
	 *		If there isn't a template to use, then just redirect and count the old way.
	 *
	 * @param	array 	A redirect model object converted toArray();
	 * @return 	void
	 **/
	function renderRedirect($array) {
		if (file_exists(ASSET_DIR . '/views/redirect/default.html')) {
			// Do the new style page load and render.
			$contents['_SITE_NAME_'] = htmlentities(SITE_NAME);
			$contents['_EXTERNAL_CACHE_'] = defined('EXTERNAL_CACHE') && constant('EXTERNAL_CACHE')?EXTERNAL_CACHE:false;
			$this->set('title', 'Redirecting...');
			$this->set('redirect_delay', $this->redirect_delay);
			$this->set('header', '<meta name="robots" content="NOINDEX, FOLLOW" />');
			$this->set($contents);
			$this->set($array);
			$this->render(array('action'=>'default'));
			die;
		} else {
			// Do the old redirect style and count.
      // $url = &$this->getDefaultModel();
      // $url->id = $array['id'];
      // $url->get();
      // $url->count += 1;
      // $url->update();
			if ($address = $array['redirect']) {
				header ("Location: $address");
				die;
			}
		}
	}
}
?>