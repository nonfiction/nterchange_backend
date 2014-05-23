<?php
require_once 'n_auth.php';
require_once 'n_quickform.php';
require_once 'nterchange_controller.php';

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
 * @category   nterchange Login
 * @author     Tim Glen <tim@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class LoginController extends AppController {
	function __construct() {
		$this->base_dir = APP_DIR;
		$this->name = 'login';
		$this->page_title = 'Login';
		parent::__construct();
	}

	function index() {
		$this->login();
	}

	function login() {
		NDebug::debug('Redirecting ' . $_SERVER['REMOTE_ADDR'] . ' to login to nterchange.', N_DEBUGTYPE_AUTH);
		$auth = new NAuth();
		$auth->start();

		$username = $auth->username;
		$status = $auth->status;

		$form = new NQuickForm('login_form', 'post', preg_replace('/logout=1[\&]?/', '', $_SERVER['REQUEST_URI']));
		$form->setDefaults(array('username'=>$username));
		if (isset($_GET['logout']) && $_GET['logout'] == 1) {
			$form->addElement('cmsalert', 'logout_header', 'You have signed out. Sign back in to continue.');
		} else {
			if ($status < 0 &&!empty($username)) {
				$form->addElement('cmserror', 'login_status', $auth->statusMessage($status));
			} else {
				$form->addElement('cmsalert', 'login_status', 'Please sign in and you will be sent right along.');
			}
		}
		$form->addElement('text', 'username', 'Username', array('maxlength'=>32, 'style'=>'width:300px;'));
		$form->addElement('password', 'password', 'Password', array('maxlength'=>32, 'style'=>'width:150px;'));
		// $form->addElement('checkbox', 'remember', null, 'Remember me for 2 weeks.');
		$form->addElement('submit', 'login', 'Sign In');
		$referer = isset($_GET['_referer'])?urlencode($_GET['_referer']):urlencode('/' . $this->base_dir);
		$form->addElement('hidden', '_referer', $referer);

		if ($auth->checkAuth()) {
			NDebug::debug('Logged ' . $_POST['username'] . ' from ' . $_SERVER['REMOTE_ADDR'] . ' in to nterchange.', N_DEBUGTYPE_AUTH);
			// Log this in the audit trail.
			$user_id = $auth->currentUserID();
			$audit_trail = &NController::factory('audit_trail');
			$audit_trail->insert(array('asset'=>'users', 'asset_id'=>$user_id, 'action_taken'=>AUDIT_ACTION_LOGIN));
			unset($audit_trail);
			// Redirect to the page requested.
			header('Location:' . urldecode($referer));
			exit;
		}

		$content = $form->toHTML();
		$this->set(array('MAIN_CONTENT'=>$content, 'username'=>$username, 'status'=>$status));
		$this->auto_render = false;
		$this->render(array('layout'=>'login'));
	}

	/**
	 * forgot - I forgot my password and need to reset it. Takes an email address and
	 * 		sends a confirmation email with a random token to that address.
	 *
	 * @return void
	 **/
	function forgot() {
		$form = new NQuickForm('reset_password', 'post');
		$form->addElement('text', 'email', 'Email Address', array('maxlength'=>32, 'style'=>'width:300px;'));
		$form->addElement('submit', 'reset_password', 'Reset Password');
		$form->addRule('email', 'You need to enter an email address.', 'required', null, 'client');
		$form->addRule('email', 'The email does not appear to be the correct format', 'email', null, 'client');
		if ($form->validate()) {
			$vals = $form->exportValues();
			if (isset($vals['email'])) {
				$cms_auth = NModel::factory('cms_auth');
				// Set the token - then send the email.
				if ($result = $cms_auth->setConfirmationToken($vals['email'])) {
					// Send the confirmation email.
					$user = NController::factory('users');
					$user->sendConfirmationEmail($vals['email']);
				}
			}
			// TODO: Put this into the template and out of here.
			if ($result == true) {
				$content = '<p><b>We have sent you a confirmation - please check your email and follow the instructions.</b></p>';
			} else {
				$content = '<p><b>There was a problem - please <a href="javascript:history.go(-1);">click back and enter your email address again.</a></b></p>';
			}
			$this->set(array('MAIN_CONTENT'=>$content, 'forgot'=>'true'));
		} else {
			$content = $form->toHTML();
			$this->set(array('MAIN_CONTENT'=>$content, 'forgot'=>'true'));
		}
		$this->auto_render = false;
		$this->render(array('layout'=>'login'));
	}

	/**
	 * confirmPasswordReset - Takes a token passed in the get string, verifies it and resets
	 * 		the corresponding password for that particular email address.
	 *
	 * @return void
	 **/
	function confirmPasswordReset() {
		// Verify the token
		$passed_token = $_GET['token'];
		$cms_auth = NModel::factory('cms_auth');
		$cms_auth->confirmation_token = $passed_token;
		// If it checks out, then send out the new password.
		if ($cms_auth->find()) {
			while ($cms_auth->fetch()) {
				// If it's there - grab the email.
				$email = $cms_auth->email;
				// Set the confirmation_token to NULL
				$cms_auth->confirmation_token = 'NULL';
				$cms_auth->save();
				// Reset it and send it out.
				$cms_auth->resetPassword($email);
				$content = 'The password was reset and emailed.';
			}
		} else {
			$content = 'There was a problem - please try again.';
		}
		$this->set(array('MAIN_CONTENT'=>$content));
		$this->auto_render = false;
		$this->render(array('layout'=>'login'));
	}

}
?>
