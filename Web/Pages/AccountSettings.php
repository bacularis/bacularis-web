<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
use Bacularis\Common\Modules\Protocol\WebAuthn\Base as WebAuthnBase;
use Bacularis\Common\Modules\Protocol\WebAuthn\Register as WebAuthnRegister;
use Bacularis\Web\Modules\WebConfig;
use Bacularis\Web\Modules\WebUserConfig;
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
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		self::$user = $user_config->getUserConfig($org_id, $user_id);

		if (isset($this->web_config['security']['auth_method']) && $this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_BASIC) {
			$this->AuthTOTP2FAConfigure->getAttributes()->add('onclick', 'return false;');
			$this->AuthTOTP2FAConfigure->setStyle('cursor: not-allowed;');
			$this->Auth2FADisabledMsg->Display = 'Dynamic';
		}

		if ($this->IsPostBack || $this->IsCallBack) {
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

		if ($this->isTOTP2FAConfigured()) {
			$this->EnableTOTP2FA->Display = 'None';
			$this->ConfigureTOTP2FA->Display = 'None';
			$this->ConfigureReadyTOTP2FA->Display = 'Dynamic';
			$this->DisableTOTP2FA->Display = 'Dynamic';
		} else {
			$this->DisableTOTP2FA->Display = 'None';
			$this->EnableTOTP2FA->Display = 'Dynamic';
			$this->ConfigureTOTP2FA->Display = 'Dynamic';
			$this->ConfigureReadyTOTP2FA->Display = 'None';
		}

		if (count(self::$user) == 0) {
			Logging::log(
				Logging::CATEGORY_SECURITY,
				"Attempt to get non-existing user '$user_id' access data."
			);
		}

		$this->Username->Text = $user_id;

		$this->TagManager->setUsername($org_id, $user_id);
		$this->loadTwoFactorMethod();
	}

	/**
	 * TOTP 2FA configured status.
	 *
	 * @return true if TOTP 2FA is configured, false otherwise
	 */
	public function isTOTP2FAConfigured(): bool
	{
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$user = $user_config->getUserConfig($org_id, $user_id);
		return (key_exists('totp_secret', $user)
			&& !empty($user['totp_secret']));
	}

	/**
	 * FIDO U2F configured status.
	 *
	 * @return true if FIDO U2F is configured, false otherwise
	 */
	public function isFIDOU2FConfigured(): bool
	{
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$user = $user_config->getUserConfig($org_id, $user_id);
		return (key_exists('fidou2f_credentials', $user)
			&& is_array($user['fidou2f_credentials'])
			&& count($user['fidou2f_credentials']) > 0);
	}

	/**
	 * Set up two-factor authentication checkbox.
	 */
	private function loadTwoFactorMethod()
	{
		$this->TwoFactorMethod->SelectedValue = self::$user['mfa'] ?? '';
		if (!$this->IsCallBack) {
			return;
		}
		$cb = $this->getCallbackClient();

		// TOTP 2FA item setting
		$val = WebUserConfig::MFA_TYPE_TOTP;
		$text = 'TOTP 2FA';
		$enabled = $this->isTOTP2FAConfigured();
		$cb->callClientFunction(
			'oAccountSettingsMFA.update_mfa_selection',
			[$val, $text, $enabled]
		);

		// FIDO U2F item setting
		$val = WebUserConfig::MFA_TYPE_FIDOU2F;
		$text = 'FIDO U2F';
		$enabled = $this->isFIDOU2FConfigured();
		$cb->callClientFunction(
			'oAccountSettingsMFA.update_mfa_selection',
			[$val, $text, $enabled]
		);
	}

	/**
	 * Save two-factor authentication method.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function saveTwoFactorMethod($sender, $param)
	{
		$mfa = $this->TwoFactorMethod->SelectedValue;
		$config = ['mfa' => $mfa];
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$user_config->updateUserConfig($org_id, $user_id, $config);
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

		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$ret = $user_config->setUserConfig($org_id, $user_id, self::$user);
		if ($ret !== true) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while saving user config for user '$user_id'."
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
					$user_id,
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
					$user_id,
					$this->UserPassword->Text,
					false,
					null,
					$opts
				);
			}
		}
	}

	/**
	 * Show window to configure TOTP 2FA.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function showTOTP2FAWindow($sender, $param)
	{
		$totp_params = $this->getTotpParams();
		$this->getCallbackClient()->callClientFunction(
			'oAccountSettingsTOTP2FA.generate_qrcode',
			[$totp_params['url']]
		);
		$this->getCallbackClient()->callClientFunction(
			'oAccountSettingsTOTP2FA.set_code',
			[$totp_params['secret']]
		);
		$this->AuthTOTP2FASecret->Value = $totp_params['secret'];
	}

	/**
	 * Enable TOTP two-factor authentication (TOTP 2FA).
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function enableTOTP2FA($sender, $param)
	{
		$cb = $this->getCallbackClient();
		$secret = $this->getModule('base32')->decode($this->AuthTOTP2FASecret->Value);
		$token = $this->AuthTOTP2FAToken->Text;
		$valid = $this->getModule('totp')->validateToken($secret, $token);
		if ($valid === true) {
			// token valid
			$org_id = $this->User->getOrganization();
			$user_id = $this->User->getUsername();
			if (!key_exists('mfa', self::$user) || self::$user['mfa'] == WebUserConfig::MFA_TYPE_NONE) {
				self::$user['mfa'] = WebUserConfig::MFA_TYPE_TOTP;
			}
			self::$user['totp_secret'] = $this->AuthTOTP2FASecret->Value;
			$user_config = $this->getModule('user_config');
			$user_config->setUserConfig($org_id, $user_id, self::$user);

			$this->EnableTOTP2FA->Display = 'None';
			$this->DisableTOTP2FA->Display = 'Dynamic';
			$cb->slideUp($this->ConfigureTOTP2FA);
			$cb->slideDown($this->ConfigureReadyTOTP2FA);
			$cb->hide('account_settings_add_2fa_error');

			$this->loadTwoFactorMethod();
		} else {
			// token invalid
			$emsg = Prado::localize('Invalid authentication code. Please try again.');
			$cb->update('account_settings_add_2fa_error', $emsg);
			$cb->show('account_settings_add_2fa_error');
		}
	}

	/**
	 * Disable two-factor authentication (2FA).
	 *
	 * @param TActiveCheckBox $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function disableTOTP2FA($sender, $param)
	{
		$cb = $this->getCallbackClient();
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		if (self::$user['mfa'] == WebUserConfig::MFA_TYPE_TOTP) {
			self::$user['mfa'] = WebUserConfig::MFA_TYPE_NONE;
		}
		unset(self::$user['totp_secret']);
		$user_config = $this->getModule('user_config');
		$user_config->setUserConfig($org_id, $user_id, self::$user);

		$this->DisableTOTP2FA->Display = 'None';
		$this->EnableTOTP2FA->Display = 'Dynamic';

		$cb->slideUp($this->ConfigureReadyTOTP2FA);
		$cb->slideDown($this->ConfigureTOTP2FA);
		$cb->hide('account_settings_add_2fa_error');

		$this->loadTwoFactorMethod();
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

	/**
	 * Load table with U2F keys.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function loadU2FKeys($sender, $param): void
	{
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$config = $user_config->getUserConfig($org_id, $user_id);
		$fidou2f_creds = $config['fidou2f_credentials'] ?? [];
		$creds = [];
		foreach ($fidou2f_creds as $credential_id => $value) {
			parse_str($value, $props);
			$creds[] = [
				'key_name' => ($props['name'] ?? ''),
				'last_used' => ($props['last_used'] ?? ''),
				'added' => ($props['added'] ?? ''),
				'credential_id' => $credential_id
			];
		}
		$cb = $this->getCallbackClient();
		$cb->callClientFunction(
			'oAccountSettingsFIDOU2F.update',
			[$creds]
		);
	}

	/**
	 * Edit U2F key properties.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function editU2FKey($sender, $param): void
	{
		$credential_id = $param->getCallbackParameter();
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$config = $user_config->getUserConfig($org_id, $user_id);
		if (key_exists('fidou2f_credentials', $config) && key_exists($credential_id, $config['fidou2f_credentials'])) {
			parse_str($config['fidou2f_credentials'][$credential_id], $props);
			$cb = $this->getCallbackClient();
			$cb->callClientFunction(
				'oAccountSettingsFIDOU2F.edit_key_cb',
				[$props['name']]
			);
		}
	}

	/**
	 * Save U2F key properties.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function saveU2FKey($sender, $param)
	{
		$credential_id = $this->FIDOU2FKeylId->Value;
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$config = $user_config->getUserConfig($org_id, $user_id);
		if (!key_exists('fidou2f_credentials', $config) || !key_exists($credential_id, $config['fidou2f_credentials'])) {
			return;
		}

		// Update key properties
		parse_str($config['fidou2f_credentials'][$credential_id], $props);
		$props['name'] = $this->FIDOU2FKeyName->Text;
		$data = [
			'fidou2f_credentials' => [
				$credential_id => http_build_query($props)
			]
		];
		$user_config->updateUserConfig($org_id, $user_id, $data);

		$this->loadU2FKeys($sender, $param);
		$cb = $this->getCallbackClient();
		$cb->callClientFunction(
			'oAccountSettingsFIDOU2F.show_key_window',
			[false]
		);
	}

	/**
	 * Get new U2F key parameters (challenge, credentialid...).
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function getNewU2FKeyParams($sender, $param): void
	{
		$u2f_register = $this->getModule('u2f_register');

		// Get user
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$user = $user_config->getUserConfig($org_id, $user_id);

		// Origin info
		$url = $this->getRequest()->getUrl();
		$origin = $url->getHost();

		// Get data required for registration
		$params = $u2f_register->getRegistrationData($user, $origin);

		// Set session data
		$sess = $this->getApplication()->getSession();
		$sess->open();
		$sess->add('fidou2f_challenge', $params['publicKey']['challenge']);
		$sess->close();

		$cb = $this->getCallbackClient();
		$cb->callClientFunction(
			'oAccountSettingsFIDOU2F.add_key_cb',
			[$params]
		);
	}

	/**
	 * Create new U2F credentials.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function createU2FCreds($sender, $param)
	{
		$data_obj = $param->getCallbackParameter();
		$data_json = json_encode($data_obj);
		$data_arr = json_decode($data_json, true);
		if (!$this->validateU2FCreds($data_arr)) {
			// Validation error. Stop.
			return false;
		}
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$u2f_register = $this->getModule('u2f_register');
		$credential_id = $u2f_register->createCredential(
			$org_id,
			$user_id,
			$data_arr
		);

		if ($credential_id) {
			$cb = $this->getCallbackClient();
			$cb->callClientFunction(
				'oAccountSettingsFIDOU2F.edit_key',
				[$credential_id]
			);
		}

		$this->loadU2FKeys($sender, $param);
		$this->loadTwoFactorMethod();
	}

	/**
	 * Remove U2F credential.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameters
	 */
	public function removeU2FCreds($sender, $param)
	{
		$credential_id = $param->getCallbackParameter();
		$org_id = $this->User->getOrganization();
		$user_id = $this->User->getUsername();
		$user_config = $this->getModule('user_config');
		$config = $user_config->getUserConfig($org_id, $user_id);
		if (key_exists('fidou2f_credentials', $config) && key_exists($credential_id, $config['fidou2f_credentials'])) {
			unset($config['fidou2f_credentials'][$credential_id]);
			if (count($config['fidou2f_credentials']) == 0) {
				unset($config['fidou2f_credentials']);
				$config['mfa'] = WebUserConfig::MFA_TYPE_NONE;
			}

			$user_config->setUserConfig($org_id, $user_id, $config);
			self::$user = $config;
		}

		$this->loadTwoFactorMethod();
		$this->loadU2FKeys($sender, $param);
	}

	/**
	 * Validate U2F credentials.
	 *
	 * @param array $data registration data
	 * @return bool true on success, false otherwise
	 */
	private function validateU2FCreds(array $data): bool
	{
		// Prepare origin info
		$url = $this->getRequest()->getUrl();
		$origin = $url->getScheme() . '://' . $url->getHost();
		$port = $url->getPort();
		if (!in_array($port, [80, 443])) {
			$origin .= ':' . $port;
		}

		// Relying party identifier to validate
		$rp_id = $url->getHost();

		// Remembered challenge
		$sess = $this->getApplication()->getSession();
		$challenge = $sess->itemAt('fidou2f_challenge');
		$sess->remove('fidou2f_challenge');
		$sess->close();

		$u2f_register = $this->getModule('u2f_register');
		$result = $u2f_register->validateRegistration(
			$data,
			$origin,
			$rp_id,
			$challenge
		);

		if (!$result['valid']) {
			$cb = $this->getCallbackClient();
			$cb->callClientFunction(
				'oFIDOU2F.error',
				[$result['error']]
			);
		}
		return $result['valid'];
	}
}
