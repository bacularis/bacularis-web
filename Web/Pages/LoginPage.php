<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
use Bacularis\Common\Modules\Protocol\WebAuthn\Authenticate as WebAuthnAuth;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\OrganizationConfig;
use Bacularis\Web\Modules\IdentityProviderConfig;
use Bacularis\Web\Modules\WebUserConfig;
use Bacularis\Web\Modules\WebConfig;

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

	public $mfa = '';

	public $fidou2f_authdata = [];

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
		$this->setMessage();
	}

	public function onPreLoad($param)
	{
		parent::onPreLoad($param);
		$users = $this->getModule('users');

		$authorized = $users->isAuthorized();

		$web_config = $this->getModule('web_config');

		if ($this->User->getIsGuest() === false) {
			// for authenticated users

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
		} else {
			$org_config = $this->getModule('org_config');
			$config = $org_config->getConfig();
			$orgs = [];
			foreach ($config as $org_id => $conf) {
				if ($conf['enabled'] != 1) {
					continue;
				}
				$orgs[] = [
					'name' => $conf['name'],
					'full_name' => $conf['full_name'],
					'auth_type' => $conf['auth_type'],
					'color' => $conf['login_btn_color']
				];
			}
			$this->OrgRepeater->DataSource = $orgs;
			$this->OrgRepeater->dataBind();
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
		$org_id = $this->Organization->Value;
		$user_id = $this->Username->Text;
		$password = $this->Password->Text;

		if ($this->getModule('web_config')->isAuthMethodBasic() && !empty($_SERVER['PHP_AUTH_USER'])) {
			// For basic auth take user_id from web server.
			$user_id = $_SERVER['PHP_AUTH_USER'];
		}

		/**
		 * Open session to be able to log in.
		 */
		$sess = $this->getApplication()->getSession();
		$sess->open();

		$user_config = $this->getModule('user_config');
		$user = $user_config->getUserConfig($org_id, $user_id);
		if (count($user) == 0) {
			// Log in organization error
			$this->getModule('audit')->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_SECURITY,
				"Log in failed. Org: '{$org_id}' User: '{$user_id}'"
			);
			sleep(BaculumWebPage::LOGIN_FAILED_DELAY);
			$this->Msg->Text = Prado::localize('Invalid username or password');
			$this->Msg->Display = 'Fixed';
			return;
		}
		$org_user = WebUserConfig::getOrgUser($org_id, $user_id);

		$users = $this->getModule('users');
		$valid = $users->validateUser($org_user, $password);
		if ($valid === true) {
			// Pre-login successful
			if (count($user) > 0 && key_exists('mfa', $user) && !empty($user['mfa']) && $user['mfa'] !== WebUserConfig::MFA_TYPE_NONE) {
				// The user uses 2FA, go to second step/factor
				$this->setMFA($user);
			} else {
				// Login try
				$auth = $this->getModule('auth');
				$success = $auth->login($org_user, $password);
				if ($success === true) {
					// Log in successful
					$this->getModule('audit')->audit(
						AuditLog::TYPE_INFO,
						AuditLog::CATEGORY_SECURITY,
						"Log in successful. Org: '{$org_id}' User: '{$user_id}'"
					);
					$this->goToDefaultPage();
				} else {
					// Log in error
					$this->getModule('audit')->audit(
						AuditLog::TYPE_WARNING,
						AuditLog::CATEGORY_SECURITY,
						"Log in failed. Org: '{$org_id}' User: '{$user_id}'"
					);
					sleep(BaculumWebPage::LOGIN_FAILED_DELAY);
					$this->Msg->Text = Prado::localize('Invalid username or password');
					$this->Msg->Display = 'Fixed';
				}
			}
		} else {
			// Log in error
			$this->getModule('audit')->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_SECURITY,
				"Log in failed. Org: '{$org_id}' User: '{$user_id}'"
			);
			sleep(BaculumWebPage::LOGIN_FAILED_DELAY);
			$this->Msg->Text = Prado::localize('Invalid username or password');
			$this->Msg->Display = 'Fixed';
		}
	}

	/**
	 * Log in with TOTP 2FA.
	 * This action happens after successful user/password login.
	 */
	public function loginTOTP2FA()
	{
		$org_id = $this->Organization->Value;
		$user_id = $this->Username->Text;
		$password = $this->Password->Text;

		if ($this->getModule('web_config')->isAuthMethodBasic() && !empty($_SERVER['PHP_AUTH_USER'])) {
			// For basic auth take username from web server.
			$user_id = $_SERVER['PHP_AUTH_USER'];
		}

		$user_config = $this->getModule('user_config');
		$user = $user_config->getUserConfig($org_id, $user_id);
		if (count($user) === 0 || !key_exists('mfa', $user) || $user['mfa'] !== WebUserConfig::MFA_TYPE_TOTP || !key_exists('totp_secret', $user)) {
			return false;
		}

		/**
		 * Open session to be able to log in.
		 */
		$sess = $this->getApplication()->getSession();
		$sess->open();

		$this->setMFA($user);
		$secret = $this->getModule('base32')->decode($user['totp_secret']);
		$token = $this->AuthTOTP2FAToken->Text;
		if ($this->getModule('totp')->validateToken($secret, $token) === true) {
			// 2FA successful, do login to app
			$org_user = WebUserConfig::getOrgUser($org_id, $user_id);
			$auth = $this->getModule('auth');
			$success = $auth->login($org_user, $password);
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
					"TOTP 2FA auth successful. Org: '{$org_id}' User: '{$user_id}'"
				);
			} else {
				// Log in error after successful 2FA
				$this->getModule('audit')->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_SECURITY,
					"TOTP 2FA auth failed. Org: '{$org_id}' User: '{$user_id}'"
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
				"TOTP 2FA auth failed. Org: '{$org_id}' User: '{$user_id}'"
			);
		}
	}

	/**
	 * Log in with FIDO U2F.
	 * This action happens after successful user/password login.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function loginFIDOU2F($sender, $param)
	{
		$cb = $this->getCallbackClient();
		$org_id = $this->Organization->Value;
		$user_id = $this->Username->Text;
		$password = $this->Password->Text;
		$assertion = $param->getCallbackParameter();
		$data = json_decode(json_encode($assertion), true);
		$u2f_authenticate = $this->getModule('u2f_authenticate');

		$user_config = $this->getModule('user_config');
		$user = $user_config->getUserConfig($org_id, $user_id);
		if (count($user) === 0 || !key_exists('mfa', $user) || $user['mfa'] !== WebUserConfig::MFA_TYPE_FIDOU2F || !key_exists('fidou2f_credentials', $user)) {
			return false;
		}

		// Validate auth data
		$validation = $u2f_authenticate->validateAuth($data);
		if ($validation['valid'] === false) {
			$cb->callClientFunction(
				'oFIDOU2F.error',
				[$validation['error']]
			);
			return;
		}
		$result = $u2f_authenticate->authenticate(
			$data,
			$org_id,
			$user_id
		);
		if ($result === true) {
			$this->getApplication()->getSession()->open();
			$auth = $this->getModule('auth');
			$org_user = WebUserConfig::getOrgUser($org_id, $user_id);
			$success = $auth->login($org_user, $password);
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
					"FIDO U2F auth successful. Org: '{$org_id}' User: '{$user_id}'"
				);
			} else {
				// Log in error after successful 2FA
				$this->getModule('audit')->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_SECURITY,
					"FIDO U2F auth failed. Org: '{$org_id}' User: '{$user_id}'"
				);
				sleep(BaculumWebPage::LOGIN_FAILED_DELAY);
				$emsg = Prado::localize('Invalid username or password');
				$this->getCallbackClient()->update('login_2fa_error', $emsg);
				$this->getCallbackClient()->show('login_2fa_error');
			}
		} else {
			// Invalid authentication
			$emsg = 'Invalid authentication. Please check authenticator and try again.';
			$this->getCallbackClient()->update('login_2fa_error', $emsg);
			$this->getCallbackClient()->show('login_2fa_error');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_SECURITY,
				"FIDO U2F auth failed. Org: '{$org_id}' User: '{$user_id}'"
			);
		}
	}

	/**
	 * Log in with external identity provider.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function orgLogin($sender, $param)
	{
		$props = $param->getCommandParameter();
		$org_name = $props['name'] ?? '';
		$auth_type = $props['auth_type'] ?? '';

		$org_config = $this->getModule('org_config');
		$org = $org_config->getOrganizationConfig($org_name, true);
		if ($org['enabled'] != 1) {
			return;
		}
		if ($auth_type == OrganizationConfig::AUTH_TYPE_AUTH_METHOD) {
			$this->OrganizationBox->Display = 'Dynamic';
			$this->Organization->Value = $org['name'];
			$this->OrganizationName->Text = $org['full_name'];
		} elseif ($auth_type == OrganizationConfig::AUTH_TYPE_IDP) {
			if ($org_name) {
				$sess = $this->getApplication()->getSession();
				$sess->open();
				$sess->add('login_org', $org_name);
				if ($org['idp']['enabled'] != 1) {
					return;
				}
				$mod = null;
				switch ($org['idp']['type']) {
					case IdentityProviderConfig::IDP_TYPE_OIDC: {
						$mod = $this->getModule('oidc');
						break;
					}
					case IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE: {
						$mod = $this->getModule('oidc_google');
						break;
					}
				}
				if ($mod) {
					$mod->authorize($org['identity_provider']);
				}
			}
		}
	}

	/**
	 * Set muti-factor authentication property.
	 *
	 * @param string $user user account details
	 */
	private function setMFA(array $user): void
	{
		// multi-factor authentication type ('totp', 'fidou2f')
		$this->mfa = $user['mfa'];

		if ($this->isFIDOU2F()) {
			$origin = $this->getRequest()->getUrl()->getHost();
			$this->fidou2f_authdata = WebAuthnAuth::getAuthData($user, $origin);
		}
	}

	/**
	 * Get information if TOTP 2FA authentication is enabled.
	 *
	 * @return bool true if TOTP 2FA is configured, false otherwise
	 */
	public function isTOTP2FA(): bool
	{
		return ($this->mfa === WebUserConfig::MFA_TYPE_TOTP);
	}

	/**
	 * Get information if FIDO U2F authentication is enabled.
	 *
	 * @return bool true if FIDO U2F is configured, false otherwise
	 */
	public function isFIDOU2F(): bool
	{
		return ($this->mfa === WebUserConfig::MFA_TYPE_FIDOU2F);
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
		/**
		 * Open session to be able to log out.
		 */
		$sess = $this->getApplication()->getSession();
		$sess->open();

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

	/**
	 * Show error message.
	 */
	private function setMessage(): void
	{
		if ($this->Request->contains('error')) {
			$this->Msg->Text = urldecode($this->Request['error']);
			$this->Msg->Display = 'Fixed';
		}
	}
}
