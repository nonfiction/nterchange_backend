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
 * @category   Auth Model
 * @author     Tim Glen <tim@nonfiction.ca>
 * @author     Darron Froese <darron@nonfiction.ca>
 * @copyright  2005-2007 nonfiction studios inc.
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    SVN: $Id$
 * @link       http://www.nterchange.com/
 */
class CmsAuth extends NModel {
	function __construct() {
		$this->__table = 'cms_auth';
		$this->_order_by = 'real_name';
		parent::__construct();
		// form stuff
		$this->form_header = 'real_name';
		$this->display_fields = array('real_name', 'username', 'user_level', 'status');
		$this->form_required_fields[] = 'real_name';
		$this->form_required_fields[] = 'email';
		$this->form_required_fields[] = 'username';
		$this->form_rules[] = array('email', 'The email does not appear to be the correct format', 'email');
		$this->form_rules[] = array('username', 'This is not a unique username', 'callback', array(&$this, 'uniqueUsername'));
		// secure password rules, 8 characters long, upper and lower case characters
		// $this->form_rules[] = array('password', 'The password must be at least 8 characters long and contain upper and lower case characters and a number.', 'minlength', 8, 'client');
		// $this->form_rules[] = array('password', 'The password must be at least 8 characters long and contain upper and lower case characters and a number.', 'regex', '/[A-Z]/', 'client');
		// $this->form_rules[] = array('password', 'The password must be at least 8 characters long and contain upper and lower case characters and a number.', 'regex', '/[a-z]/', 'client');
		// $this->form_rules[] = array('password', 'The password must be at least 8 characters long and contain upper and lower case characters and a number.', 'regex', '/[0-9]/', 'client');
		// password field
		$this->form_elements['password'] = array('password');
		// hide the status field for now
		$this->form_elements['status'] = array('hidden', 'status');
		// Ignore the feed_token field for now
		$this->form_ignore_fields[] = 'feed_token';
		// Ignore the confirmation_token field for now
		$this->form_ignore_fields[] = 'confirmation_token';
		// user level - a non-root can't set someone else to be root
		$user_lvl = array(N_USER_NORIGHTS=>'non-privileged', N_USER_EDITOR=>'user', N_USER_ADMIN=>'admin');
		$this->form_elements['user_level'] = array('select', 'user_level', 'User Level', $user_lvl);
		$this->setHeadline('real_name');
	}

	/**
	 * uniqueUsername - Make sure that the username is unique.
	 *
	 * @param	string	The username to check.
	 * @return 	boolean
	 **/
	function uniqueUsername($value) {
		$id = $this->{$this->primaryKey()};
		$model = &NModel::factory($this->__table);
		if ($model) {
			$conditions = $id?$model->primaryKey() . '!=' . $id:'';
			$model->username = $value;
			if ($model->find(array('conditions'=>$conditions))) {
				unset($model);
				return false;
			}
		}
		unset($model);
		return true;
	}
	
	/**
	 * getFeedToken - Returns the feed token for an id.
	 *
	 * @param	int		User_id
	 * @return 	string	The feed token for that user.
	 **/
	function getFeedToken($id) {
		$this->id = $id;
		if ($this->find()) {
			while ($this->fetch()) {
				return $this->feed_token;
			}
		}
	}
	
	/**
	 * resetPassword - Reset the users' password and email it to them.
	 *
	 * @param	string	An email address.
	 * @return 	boolean
	 * @todo 	Audit trail this method.
	 **/
	function resetPassword($email) {
		// Make sure to clear out the model - after searching a few times already.
		$this->reset();
		$this->email = $email;
		if ($this->find()) {
			while ($this->fetch()) {
				$password = $this->_createPassword();
		        $this->password = md5($password);
		        $this->save();
				$users = NController::factory('users');
		        $users->passwordEmail($this->toArray(), $password);
				return true;
			}
		} else {
			return false;
		}
	}
	
	/**
	 * _createPassword - Create a password using PEAR's Text_Password generator.
	 *
	 * @return 	string	The new password.
	 **/
	function _createPassword() {
		include_once 'Text/Password.php';
		$password = Text_Password::create(rand(8, 10), 'unpronounceable');
		return $password;
	}
	
	/**
	 * setConfirmationToken - Generate and set a confirmation token for a particular user.
	 * 		Returns true if successful, false if not.
	 *
	 * @param	string	Email address for that user.
	 * @return 	boolean
	 **/
	function setConfirmationToken($email) {
		// Generate the token and put it into the database.
		$random = rand(1,1000000) . time() . $_SERVER['REMOTE_ADDR'] . rand(1,1000000);
		$confirmation_token = md5($random);
		$this->email = $email;
		if ($this->find()) {
			while ($this->fetch()) {
				$this->confirmation_token = $confirmation_token;
				if ($this->save()) {
					return true;
				} else {
					return false;
				}
			}
		}
	}
	
	/**
	 * getConfirmationToken - Gets a confirmation token from the database. 
	 * 		Returns false if it's null or if there's no match.
	 *
	 * @param	string	Email address for that user.
	 * @return 	string	The confirmation token.
	 **/
	function getConfirmationToken($email) {
		$this->email = $email;
		if ($this->find()) {
			while ($this->fetch()) {
				if (!is_null($this->confirmation_token)) {
					return $this->confirmation_token;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}
	
	
}
?>