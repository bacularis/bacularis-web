<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Prado\Prado;
use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\WebConfig;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * User account settings class.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class AccountSettings extends BaculumWebPage
{
	/**
	 * Stores user account information.
	 */
	private static $user;

	public function onInit($param)
	{
		parent::onInit($param);
		$username = $this->User->getUsername();
		self::$user = $this->getModule('user_config')->getUserConfig($username);

		if (isset($this->web_config['security']['auth_method']) && $this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_BASIC) {
			$this->Auth2FAEnable->Enabled = false;
			$this->Auth2FADisabledMsg->Display = 'Dynamic';
		}

		if ($this->IsPostBack || $this->IsCallback) {
			return;
		}
		$this->UserLongName->Text = self::$user['long_name'];
		$this->UserDescription->Text = self::$user['description'];
		$this->UserEmail->Text = self::$user['email'];

		if (isset($this->web_config['security']['auth_method']) && $this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_LDAP) {
			$this->UserPasswordBox->Display = 'None';
			$this->RequirePasswordValidator->Visible = false;
			$this->RegexPasswordValidator->Visible = false;
			$this->ComparePasswordValidator->Visible = false;
		}

		if (key_exists('mfa', self::$user) && self::$user['mfa'] === 'totp') {
			$this->Auth2FAEnable->Checked = true;
		}

		if (count(self::$user) == 0) {
			Logging::log(
				Logging::CATEGORY_SECURITY,
				"Attempt to get non-existing user '$username' access data."
			);
		}

		$this->Username->Text = $username;
	}

	/**
	 * Save general user information.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function saveGeneral($sender, $param)
	{
		self::$user['long_name'] = $this->UserLongName->Text;
		self::$user['description'] = $this->UserDescription->Text;
		self::$user['email'] = $this->UserEmail->Text;

		// Save user config

		$username = $this->User->getUsername();
		$ret = $this->getModule('user_config')->setUserConfig($username, self::$user);
		if ($ret !== true) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while saving user config for user '$username'."
			);
			return;
		}

		// Save password

		if (empty($this->UserPassword->Text)) {
			return;
		}
		$is_local = $this->getModule('web_config')->isAuthMethodLocal();
		$is_basic = $this->getModule('web_config')->isAuthMethodBasic();
		$allow_manage_users = (isset($this->web_config['auth_basic']['allow_manage_users']) &&
			$this->web_config['auth_basic']['allow_manage_users'] == 1);
		// Check if auth method allows changing password
		if (($is_basic && $allow_manage_users) || $is_local) {
			// there is possible to change password
			$basic = $this->getModule('basic_webuser');
			if ($this->getModule('web_config')->isAuthMethodLocal()) {
				// Save local user password
				$basic->setUsersConfig(
					$username,
					$this->UserPassword->Text
				);
			} elseif ($this->getModule('web_config')->isAuthMethodBasic() &&
				isset($this->web_config['auth_basic']['user_file'])) {
				// Save Basic user password

				$opts = [];
				if (isset($this->web_config['auth_basic']['hash_alg'])) {
					$opts['hash_alg'] = $this->web_config['auth_basic']['hash_alg'];
				}

				$basic->setUsersConfig(
					$username,
					$this->UserPassword->Text,
					false,
					null,
					$opts
				);
			}
		}
	}

	/**
	 * Show window to configure 2FA.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function show2FAWindow($sender, $param)
	{
		$totp_params = $this->getTotpParams();
		$this->getCallbackClient()->callClientFunction(
			'oAccountSettings2FA.generate_qrcode',
			[$totp_params['url']]
		);
		$this->getCallbackClient()->callClientFunction(
			'oAccountSettings2FA.set_code',
			[$totp_params['secret']]
		);
		$this->Auth2FASecret->Value = $totp_params['secret'];
	}

	/**
	 * Enable two-factor authentication (2FA).
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function enable2FA($sender, $param)
	{
		$secret = $this->getModule('base32')->decode($this->Auth2FASecret->Value);
		$token = $this->Auth2FAToken->Text;
		$valid = $this->getModule('totp')->validateToken($secret, $token);
		$this->Auth2FAEnable->Checked = false;
		if ($valid === true) {
			// token valid
			$username = $this->User->getUsername();
			self::$user['mfa'] = 'totp';
			self::$user['totp_secret'] = $this->Auth2FASecret->Value;
			$this->getModule('user_config')->setUserConfig($username, self::$user);
			$this->getCallbackClient()->callClientFunction(
				'oAccountSettings2FA.show',
				[false]
			);
			$this->Auth2FAEnable->Checked = true;
		} else {
			// token invalid
			$emsg = Prado::localize('Invalid authentication code. Please try again.');
			$this->getCallbackClient()->update('account_settings_add_2fa_error', $emsg);
			$this->getCallbackClient()->show('account_settings_add_2fa_error');
		}
	}

	/**
	 * Disable two-factor authentication (2FA).
	 *
	 * @param TActiveCheckBox $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function disable2FA($sender, $param)
	{
		$username = $this->User->getUsername();
		unset(self::$user['mfa']);
		unset(self::$user['totp_secret']);
		$this->getModule('user_config')->setUserConfig($username, self::$user);
		$this->Auth2FAEnable->Checked = false;
	}

	/**
	 * Generate and get TOTP 2FA secret and URL for generating QR code.
	 *
	 * @return array URL to generate QR code and TOTP secret
	 */
	private function getTotpParams()
	{
		$secret = $this->getModule('base32')->generateRandomString(20);
		$url = sprintf(
			'otpauth://totp/%s?secret=%s',
			'Bacularis',
			$secret
		);
		return ['url' => $url, 'secret' => $secret];
	}
}
