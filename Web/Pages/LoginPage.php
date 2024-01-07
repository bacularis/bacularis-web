<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
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

use Prado\Prado;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * User login page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class LoginPage extends BaculumWebPage
{
	/**
	 * Reload URL is used to refresh page after logout with Basic auth.
	 */
	public $reload_url = '';

	public $mfa = false;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getModule('web_config')->isAuthMethodBasic()) {
			$fake_pwd = $this->getModule('crypto')->getRandomString();
			// must be different than currently logged in Basic user
			$user = ($_SERVER['PHP_AUTH_USER'] ?? '') . '1';

			// do a login try with different user and password to logout current user
			$this->reload_url = $this->getPage()->getFullLoginUrl($user, $fake_pwd);
		}
	}

	public function onPreLoad($param)
	{
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
			} elseif (($web_config->isAuthMethodLdap() || $web_config->isAuthMethodLocal()) && !$authorized) {
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
	public function login($sender, $param)
	{
		$username = $this->Username->Text;
		$password = $this->Password->Text;

		if ($this->getModule('web_config')->isAuthMethodBasic() && !empty($_SERVER['PHP_AUTH_USER'])) {
			// For basic auth take username from web server.
			$username = $_SERVER['PHP_AUTH_USER'];
		}

		$valid = $this->getModule('users')->validateUser($username, $password);
		if ($valid === true) {
			// Pre-login successful
			$user = $this->getModule('user_config')->getUserConfig($username);
			if (count($user) > 0 && key_exists('mfa', $user) && $user['mfa'] === 'totp') {
				// The user uses 2FA, go to second step/factor
				$this->mfa = true;
			} else {
				// Login try
				$success = $this->getModule('auth')->login($username, $password);
				if ($success === true) {
					// Log in successful
					$this->getModule('audit')->audit(
						AuditLog::TYPE_INFO,
						AuditLog::CATEGORY_SECURITY,
						"Log in successful. User: $username"
					);
					$this->goToDefaultPage();
				} else {
					// Log in error
					$this->getModule('audit')->audit(
						AuditLog::TYPE_WARNING,
						AuditLog::CATEGORY_SECURITY,
						"Log in failed. User: $username"
					);
					sleep(BaculumWebPage::LOGIN_FAILED_DELAY);
					$this->Msg->Display = 'Fixed';
				}
			}
		} else {
			// Log in error
			$this->getModule('audit')->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_SECURITY,
				"Log in failed. User: $username"
			);
			sleep(BaculumWebPage::LOGIN_FAILED_DELAY);
			$this->Msg->Display = 'Fixed';
		}
	}

	/**
	 * Log in with 2FA.
	 * This action happens after successful user/password login.
	 *
	 */
	public function login2FA()
	{
		$username = $this->Username->Text;
		$password = $this->Password->Text;

		if ($this->getModule('web_config')->isAuthMethodBasic() && !empty($_SERVER['PHP_AUTH_USER'])) {
			// For basic auth take username from web server.
			$username = $_SERVER['PHP_AUTH_USER'];
		}

		$user = $this->getModule('user_config')->getUserConfig($username);
		if (count($user) === 0 || !key_exists('mfa', $user) || !key_exists('totp_secret', $user)) {
			return false;
		}
		$this->mfa = true;
		$secret = $this->getModule('base32')->decode($user['totp_secret']);
		$token = $this->Auth2FAToken->Text;
		if ($this->getModule('totp')->validateToken($secret, $token) === true) {
			// 2FA successful, do login to app
			$success = $this->getModule('auth')->login($username, $password);
			if ($success === true) {
				// Log in successful
				$def_page = $this->getDefaultPage();
				$url = $this->Service->constructUrl($def_page);
				$this->getCallbackClient()->callClientFunction(
					'direct_to_def_page',
					$url
				);
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_SECURITY,
					"2FA auth successful . User: $username"
				);
			} else {
				// Log in error after successful 2FA
				$this->getModule('audit')->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_SECURITY,
					"2FA auth failed . User: $username"
				);
				sleep(BaculumWebPage::LOGIN_FAILED_DELAY);
				$emsg = Prado::localize('Invalid username or password');
				$this->getCallbackClient()->update('login_2fa_error', $emsg);
				$this->getCallbackClient()->show('login_2fa_error');
			}
		} else {
			// Invalid token
			$emsg = Prado::localize('Invalid authentication code. Please try again.');
			$this->getCallbackClient()->update('login_2fa_error', $emsg);
			$this->getCallbackClient()->show('login_2fa_error');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_SECURITY,
				"2FA auth failed . User: $username"
			);
		}
	}

	/**
	 * Logout button event handler.
	 * It is used for logout button visible after unsuccessfull authorization.
	 *
	 * @param TLinkButton $sender sender object
	 * @param mixed $param event parameter (in this case null)
	 */
	public function logout($sender, $param)
	{
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
