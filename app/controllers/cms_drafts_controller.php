<?php
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
 * @category   Draft Content
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsDraftsController extends AppController {
	function __construct() {
		$this->name = 'cms_drafts';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_NORIGHTS;
		$this->login_required = true;
		parent::__construct();
	}

	function delete($parameter) {
		if (empty($parameter)) {
			$this->redirectTo(array('dashboard'));
		}
		// load the model layer with info
		$model = &NModel::factory($this->name);
		if (!$model) $this->redirectTo(array('dashboard'));
		if ($model->get($parameter)) {
			// if the content record is flagged with cms_draft=1, then the content has never been published and should be deleted altogether
			$content_model = &NModel::factory($model->asset);
			if ($content_model && $content_model->get($model->asset_id) && $content_model->cms_draft == 1) {
				$content_model->delete();
			}
			unset($content_model);
			if (defined('SITE_AUDIT_TRAIL') && SITE_AUDIT_TRAIL) {
				// audit trail before delete so we don't lose the values
				$audit_trail = &NController::factory('audit_trail');
				$audit_trail->insert(array('asset'=>$this->name, 'asset_id'=>$model->{$model->primaryKey()}, 'action_taken'=>AUDIT_ACTION_DRAFT_DELETE));
				unset($audit_trail);
			}
			$model->delete();
			if (isset($this->params['_referer']) && $this->params['_referer']) {
				header('Location:' . urldecode($this->params['_referer']));
				exit;
			}
			$this->postProcessForm($model->toArray());
			$this->flash->set('notice', 'Draft deleted.');
		}
		$this->redirectTo(array('dashboard'));
	}
}
?>