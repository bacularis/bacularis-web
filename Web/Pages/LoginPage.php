<?php
/*
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

Prado::using('Application.Web.Class.BaculumWebPage');

/**
 * User login page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class LoginPage extends BaculumWebPage {

	/**
	 * Reload URL is used to refresh page after logout with Basic auth.
	 */
	public $reload_url = '';

	public function onInit($param) {
		parent::onInit($param);
		if ($this->getModule('web_config')->isAuthMethodBasic()) {
			$fake_pwd = $this->getModule('crypto')->getRandomString();
			// must be different than currently logged in Basic user
			$user = (isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '') . '1';

			// do a login try with different user and password to logout current user
			$this->reload_url = $this->getPage()->getFullLoginUrl($user, $fake_pwd);
		}
	}

	public function onPreLoad($param) {
		parent::onPreLoad($param);
		$users = $this->getModule('users');

		$authorized = $users->isAuthorized();

		if ($this->User->getIsGuest() === false) {
			// for authenticated users

			$web_config = $this->getModule('web_config');
			if ($web_config->isAuthMethodBasic()) {
				/**
				 * Using default page here is because it is required for case if user doesn't
				 * have access to default service page. Then it is directed to first free page
				 * available for him.
				 */
				if ($authorized || ($this->User->Enabled && $users->getAuthorizedFlag() === $this->Service->DefaultPage)) {
					// Basic user authenticated and authorized
					$this->goToDefaultPage();
				} else {
					// Basic - user authenticated but not authorized or no access by default
					$this->LoginForm->Display = 'None';
					$this->AuthorizationError->Display = 'Dynamic';
				}
			} else if (($web_config->isAuthMethodLdap() || $web_config->isAuthMethodLocal()) && !$authorized) {
				// Ldap and Local - user authenticated but not authorized
				$this->LoginForm->Display = 'None';
				$this->AuthorizationError->Display = 'Dynamic';
			}
		}
	}

	/**
	 * Login using login page form.
	 *
	 * @param TLinkButton $sender sender object
	 * @param mixed $param event parameter (in this case null)
	 */
	public function login($sender, $param) {
		$username = $this->Username->Text;
		$password = $this->Password->Text;

		if ($this->getModule('web_config')->isAuthMethodBasic() && !empty($_SERVER['PHP_AUTH_USER'])) {
			// For basic auth take username from web server.
			$username = $_SERVER['PHP_AUTH_USER'];
		}

		$success = $this->getModule('auth')->login($username, $password);
		if ($success === true) {
			$this->goToDefaultPage();
		} else {
			$this->Msg->Display = 'Fixed';
		}
	}

	/**
	 * Logout button event handler.
	 * It is used for logout button visible after unsuccessfull authorization.
	 *
	 * @param TLinkButton $sender sender object
	 * @param mixed $param event parameter (in this case null)
	 */
	public function logout($sender, $param) {
		$this->getModule('auth')->logout();
		if ($this->getModule('web_config')->isAuthMethodBasic()) {
			/**
			 * This status code 401 is necessary to stop comming AJAX requests
			 * and to bring the login prompt on.
			 */
			$this->Response->setStatusCode(401);
		} else {
			$this->goToDefaultPage();
		}
	}
}
?>
