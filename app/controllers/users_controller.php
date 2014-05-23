<?php
require_once 'admin_controller.php';
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
 * @category   Users Administration
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class UsersController extends AdminController {
	function __construct() {
		$this->name = 'users';
		// set user level allowed to access the actions with required login
		$this->user_level_required = N_USER_NORIGHTS;
		$this->login_required = true;
		$this->page_title = 'Users';
		parent::__construct();
	}

	function index($parameter) {
		$current_user_level = $this->_auth->getAuthData('user_level');
		if ($current_user_level < N_USER_ADMIN) {
			$user_id = $this->_auth->getAuthData('id');
			$this->redirectTo('edit', $user_id);
		}
		$this->redirectTo('viewlist', $parameter);
	}

	function viewlist($parameter) {
		$current_user_level = $this->_auth->getAuthData('user_level');
		if ($current_user_level < N_USER_ADMIN) {
			$this->redirectTo('index', $parameter);
		}
		parent::viewlist($parameter);
	}

	function show($parameter) {
		$this->checkUserId($parameter);
		parent::show($parameter);
	}

	function edit($parameter) {
		$this->checkUserId($parameter);
		parent::edit($parameter);
	}

	function delete($parameter) {
		$current_user_level = $this->_auth->getAuthData('user_level');
		if ($current_user_level < N_USER_ADMIN) {
			$this->redirectTo('index', $parameter);
		}
		parent::delete($parameter);
	}

	function checkUserId($parameter) {
		$current_user_level = $this->_auth->getAuthData('user_level');
		$current_user_id = $this->_auth->getAuthData('id');
		if ($current_user_level < N_USER_ADMIN && $current_user_id != $parameter) {
			$this->redirectTo('index', $parameter);
		}
		return true;
	}

	function &getDefaultModel() {
		$model = &$this->loadModel('cms_auth');
		return $model;
	}

	function preGenerateForm() {
		// user level - a non-root can't set someone else to be root
		$model = &$this->getDefaultModel();
		$current_user_level = $this->_auth->getAuthData('user_level');
		$user_lvl = &$model->form_elements['user_level'];
		if ($current_user_level >= N_USER_ROOT) {
			$user_lvl[3][N_USER_ROOT] = 'root';
		}
		if (!$model->{$model->primaryKey()}) {
			$model->form_required_fields[] = 'password';
		}
		parent::preGenerateForm();
	}

	function postGenerateForm(&$form) {
		$model = &$this->getDefaultModel();
		$current_user_level = $this->_auth->getAuthData('user_level');
		// empty the password field manually
		$password = &$form->getElement('password');
		$password->setValue('');
		// turn status on by default
		// $status = &$form->getElement('status');
		// $status->setChecked(true);
		// put in confirmation password field
		$form->insertElementBefore(NQuickForm::createElement('password', 'confirm_password', 'Confirm Password'), 'user_level');
		if ($model->{$model->primaryKey()}) {
			$password->setLabel('Current Password');
			$form->insertElementBefore(NQuickForm::createElement('password', 'new_password', 'New Password'), 'confirm_password');
			$form->addRule(array('new_password', 'confirm_password'), 'The passwords do not match', 'compare');
			$form->addRule('new_password', 'The new password must be at least 8 characters long and contain upper and lower case characters and a number.', 'minlength', 8, 'client');
			$form->addRule('new_password', 'The new password must be at least 8 characters long and contain upper and lower case characters and a number.', 'regex', '/[A-Z]/', 'client');
			$form->addRule('new_password', 'The new password must be at least 8 characters long and contain upper and lower case characters and a number.', 'regex', '/[a-z]/', 'client');
			$form->addRule('new_password', 'The new password must be at least 8 characters long and contain upper and lower case characters and a number.', 'regex', '/[0-9]/', 'client');
			$password = &$form->removeElement('password');
			if ($current_user_level < N_USER_ADMIN) {
				$form->insertElementBefore($password, 'new_password');
				$password->setValue('');
				$form->addFormRule(array(&$this, 'validateEdit'));
				$form->removeElement('user_level');
			}
		} else {
			$form->addRule('password', 'That is not the correct password', 'callback', array(&$this, 'checkPassword'));
			$form->addRule(array('password', 'confirm_password'), 'The passwords do not match', 'compare');
		}
		parent::postGenerateForm($form);
	}

	function preProcessForm(&$values) {
		$current_user_level = $this->_auth->getAuthData('user_level');
		if ($current_user_level >= N_USER_ADMIN && isset($values['new_password']) && $values['new_password']) {
			$values['password'] = md5($values['new_password']);
		} else {
			if (isset($values['password']) && isset($values['new_password']) && $values['new_password']) {
				$values['password'] = md5($values['new_password']);
			} else if (isset($values['password']) && isset($values['new_password']) && !$values['new_password']) {
				$values['password'] = null;
			} else if (!$values['id']) {
				$values['password'] = md5($values['password']);
			}
		}
		parent::preProcessForm($values);
	}

	function validateEdit($values) {
		$model = &$this->getDefaultModel();
		$errors = array();
		if (isset($values['new_password'])) {
			if ($values['new_password'] && !$values['password']) {
				$errors['password'] = 'You must type in the current password in order to change it.';
			} else if ($values['new_password'] && $values['password']) {
				if ($model->password != md5($values['password'])) {
					$errors['password'] = 'That is not the correct password';
				}
			}
		}
		return empty($errors)?true:$errors;
	}

	function loadSubnav($parameter) {
		$current_user_level = $this->_auth->getAuthData('user_level');
		if ($current_user_level >= N_USER_ADMIN) {
			parent::loadSubnav($parameter);
		}
	}
	
	/**
	 * passwordEmail - Email the passed password back to the user in $model_array['email']
	 *
	 * @param 	array 	cms_user array
	 * @param	string 	The password for the user.
	 * @return 	void
	 **/
	function passwordEmail($model_array, $password) {
		$this->set($model_array);
		$this->set('password', $password);
		$this->set('public_site', PUBLIC_SITE);
		
		// set up and send the email
		$email_message = $this->render(array('action'=>'password_email', 'return'=>true));
		$email_from = 'website@' . $_SERVER['SERVER_NAME'];
		$email_to = $model_array['email'];
		$email_subject = SITE_NAME . ' - Forgotten Password';
		mail($email_to, $email_subject, $email_message);
	}
	
	/**
	 * sendConfirmationEmail - Sends a confirmation email for a password reset.
	 *
	 * @param 	string	The email address to send the email to.
	 * @return 	void
	 **/
	function sendConfirmationEmail($email) {
		$cms_auth = NModel::factory('cms_auth');
		if ($confirmation_token = $cms_auth->getConfirmationToken($email)) {
			$this->set('confirmation_token', $confirmation_token);
			$this->set('ip', $_SERVER['REMOTE_ADDR']);
			$this->set('public_site', PUBLIC_SITE);
			
			// set up and send the email
			$email_message = $this->render(array('action'=>'confirmation_email', 'return'=>true));
			$email_from = 'website@' . $_SERVER['SERVER_NAME'];
			$email_to = $email;
			$email_subject = SITE_NAME . ' - Confirm Password Reset';
			mail($email_to, $email_subject, $email_message);
		}
	}

}
?>