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

namespace Bacularis\Web\Portlets;

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\PKCE;
use Bacularis\Web\Modules\IdentityProviderConfig;

/**
 * Authentication identity providers control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AuthenticationIdentityProviders extends Security
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load identity provider list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setIdPList($sender, $param)
	{
		$idp_config = $this->getModule('idp_config');
		$idps = $idp_config->getConfig();

		$vals = array_values($idps);
		for ($i = 0; $i < count($vals); $i++) {
			$vals[$i]['idp_type'] = IdentityProviderConfig::getIDPDescByType($vals[$i]['type']);
		}

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oIdPs.load_idp_list_cb', [
			$vals
		]);
	}

	/**
	 * Load data in identity provider modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadIdPWindow($sender, $param)
	{
		$name = $param->getCallbackParameter() ?? '';

		$idp = $this->getModule('idp_config');
		$config = $idp->getIdentityProviderConfig($name);
		$cb = $this->getPage()->getCallbackClient();

		if (count($config) > 0) {
			$this->IdPName->Text = $name;
			$this->IdPFullName->Text = $config['full_name'];
			$this->IdPDescription->Text = $config['description'];
			$this->IdPType->SelectedValue = $config['type'];
			$this->IdPEnabled->Checked = ($config['enabled'] == 1);
			if ($config['type'] === IdentityProviderConfig::IDP_TYPE_OIDC) {
				$this->loadIdPOIDCSettings($config);
			}
			$cb->callClientFunction(
				'oIdPUserSecurity.show_idp_settings',
				[$config['type'], true]
			);
		} else {
			$this->loadIdPOIDCDefaultSettings();
		}
		$cb->callClientFunction(
			'oIdPUserSecurity.load_settings'
		);
	}

	private function loadIdPOIDCSettings(array $config)
	{
		$this->IdPOIDCRedirectUri->Text = $config['oidc_redirect_uri'] ?? '';
		$this->IdPOIDCUseDiscoveryEndpoint->Checked = (isset($config['oidc_use_discovery_endpoint']) && $config['oidc_use_discovery_endpoint'] == 1);
		$this->IdPOIDCDiscoveryEndpoint->Text = $config['oidc_discovery_endpoint'] ?? '';
		$this->IdPOIDCAuthorizationEndpoint->Text = $config['oidc_authorization_endpoint'] ?? '';
		$this->IdPOIDCTokenEndpoint->Text = $config['oidc_token_endpoint'] ?? '';
		$this->IdPOIDCLogoutEndpoint->Text = $config['oidc_end_session_endpoint'] ?? '';
		$this->IdPOIDCUserInfoEndpoint->Text = $config['oidc_userinfo_endpoint'] ?? '';
		$this->IdPOIDCIssuer->Text = $config['oidc_issuer'] ?? '';
		$this->IdPOIDCValidateSignatures->Checked = (isset($config['oidc_validate_sig']) && $config['oidc_validate_sig'] == 1);
		$this->IdPOIDCUseJWKSEndpoint->Checked = (isset($config['oidc_use_jwks_endpoint']) && $config['oidc_use_jwks_endpoint'] == 1);
		$this->IdPOIDCPublicKeyString->Text = str_replace("\\n", "\r\n", $config['oidc_public_key_string'] ?? '');
		$this->IdPOIDCPublicKeyID->Text = $config['oidc_public_key_id'] ?? '';
		$this->IdPOIDCJWKSEndpoint->Text = $config['oidc_jwks_uri'] ?? '';
		$this->IdPOIDCUsePKCE->Checked = (isset($config['oidc_use_pkce']) && $config['oidc_use_pkce'] == 1);
		$this->IdPOIDCPKCEMethod->SelectedValue = $config['oidc_pkce_method'] ?? '';
		$this->IdPOIDCClientID->Text = $config['oidc_client_id'] ?? '';
		$this->IdPOIDCClientSecret->Text = $config['oidc_client_secret'] ?? '';
		$this->IdPOIDCScope->Text = $config['oidc_scope'] ?? '';
		$this->IdPOIDCUserAttrSource->Text = $config['oidc_user_attr_source'] ?? '';
		$this->IdPOIDCUserNameAttr->Text = $config['oidc_user_attr'] ?? '';
		$this->IdPOIDCLongNameAttr->Text = $config['oidc_long_name_attr'] ?? '';
		$this->IdPOIDCEmailAttr->Text = $config['oidc_email_attr'] ?? '';
		$this->IdPOIDCDescriptionAttr->Text = $config['oidc_desc_attr'] ?? '';
		if ($config['oidc_attr_sync_policy'] == IdentityProviderConfig::ATTR_SYNC_POLICY_NO_SYNC) {
			$this->IdPOIDCAttrSyncPolicyNoSync->Checked = true;
		} elseif ($config['oidc_attr_sync_policy'] == IdentityProviderConfig::ATTR_SYNC_POLICY_EACH_LOGIN) {
			$this->IdPOIDCAttrSyncPolicyEachLogin->Checked = true;
		}
	}

	private function loadIdPOIDCDefaultSettings()
	{
		$this->IdPOIDCScope->Text = IdentityProviderConfig::OIDC_DEF_SCOPE;
	}

	/**
	 * Save identity provider.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveIdP($sender, $param)
	{
		$idp_config = $this->getModule('idp_config');
		$idp_name = trim($this->IdPName->Text);
		$idp_exists = $idp_config->identityProviderConfigExists($idp_name);
		$cfg_idp = [];
		$cfg_idp['name'] = $idp_name;
		$cfg_idp['full_name'] = $this->IdPFullName->Text;
		$cfg_idp['description'] = $this->IdPDescription->Text;
		$cfg_idp['type'] = $this->IdPType->SelectedValue;
		$cfg_idp['enabled'] = $this->IdPEnabled->Checked ? '1': '0';

		// OpenID Connect parameters
		$cfg_idp['oidc_redirect_uri'] = $this->IdPOIDCRedirectUri->Text;
		$cfg_idp['oidc_use_discovery_endpoint'] = $this->IdPOIDCUseDiscoveryEndpoint->Checked ? '1' : '0';
		$cfg_idp['oidc_discovery_endpoint'] = $this->IdPOIDCDiscoveryEndpoint->Text;
		$cfg_idp['oidc_authorization_endpoint'] = $this->IdPOIDCAuthorizationEndpoint->Text;
		$cfg_idp['oidc_token_endpoint'] = $this->IdPOIDCTokenEndpoint->Text;
		$cfg_idp['oidc_end_session_endpoint'] = $this->IdPOIDCLogoutEndpoint->Text;
		$cfg_idp['oidc_userinfo_endpoint'] = $this->IdPOIDCUserInfoEndpoint->Text;
		$cfg_idp['oidc_issuer'] = $this->IdPOIDCIssuer->Text;
		$cfg_idp['oidc_validate_sig'] = $this->IdPOIDCValidateSignatures->Checked ? '1' : '0';
		$cfg_idp['oidc_public_key_string'] = $this->preparePublicKey($this->IdPOIDCPublicKeyString->Text);
		$cfg_idp['oidc_public_key_id'] = trim($this->IdPOIDCPublicKeyID->Text);
		$cfg_idp['oidc_use_jwks_endpoint'] = $this->IdPOIDCUseJWKSEndpoint->Checked ? '1' : '0';
		$cfg_idp['oidc_jwks_uri'] = $this->IdPOIDCJWKSEndpoint->Text;
		$cfg_idp['oidc_use_pkce'] = $this->IdPOIDCUsePKCE->Checked ? '1' : '0';
		$cfg_idp['oidc_pkce_method'] = $this->IdPOIDCPKCEMethod->Text;
		$cfg_idp['oidc_client_id'] = $this->IdPOIDCClientID->Text;
		$cfg_idp['oidc_client_secret'] = $this->IdPOIDCClientSecret->Text;
		$cfg_idp['oidc_scope'] = $this->IdPOIDCScope->Text;
		$cfg_idp['oidc_user_attr_source'] = $this->IdPOIDCUserAttrSource->SelectedValue;
		$cfg_idp['oidc_user_attr'] = $this->IdPOIDCUserNameAttr->Text;
		$cfg_idp['oidc_long_name_attr'] = $this->IdPOIDCLongNameAttr->Text;
		$cfg_idp['oidc_email_attr'] = $this->IdPOIDCEmailAttr->Text;
		$cfg_idp['oidc_desc_attr'] = $this->IdPOIDCDescriptionAttr->Text;
		if ($this->IdPOIDCAttrSyncPolicyNoSync->Checked) {
			// No sync policy
			$cfg_idp['oidc_attr_sync_policy'] = IdentityProviderConfig::ATTR_SYNC_POLICY_NO_SYNC;
		} elseif ($this->IdPOIDCAttrSyncPolicyEachLogin->Checked) {
			// Each login policy
			$cfg_idp['oidc_attr_sync_policy'] = IdentityProviderConfig::ATTR_SYNC_POLICY_EACH_LOGIN;
		} else {
			// Default policy
			$cfg_idp['oidc_attr_sync_policy'] = IdentityProviderConfig::ATTR_SYNC_POLICY_NO_SYNC;
		}

		$idp_win_type = $this->IdPWindowType->Value;
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide('idp_window_idp_exists');
		if ($idp_win_type === self::TYPE_ADD_WINDOW) {
			if ($idp_exists) {
				$cb->show('idp_window_idp_exists');
				return;
			}
		}

		$config[$idp_name] = $cfg_idp;
		$result = $idp_config->setIdentityProviderConfig($idp_name, $cfg_idp);
		$cb->hide('idp_window');

		if ($result === true) {
			if (!$idp_exists) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Create identity provider. Name: $idp_name"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Save identity provider. Name: $idp_name"
				);
			}
		}

		// Refresh identity provider list
		$this->setIdPList($sender, $param);
	}

	/**
	 * Small helper for preparing public key to save.
	 *
	 * @param string $pubkey public key string
	 */
	private static function preparePublicKey(string $pubkey)
	{
		if (empty($pubkey)) {
			return '';
		}
		$key = trim($pubkey);
		$key = str_replace("\r", '', $key);
		$key_parts = explode(PHP_EOL, $key);
		if (count($key_parts) > 1 && preg_match('/^---[\s\w\-]+---/', $key_parts[0]) === 1) {
			array_shift($key_parts);
			$last_idx = count($key_parts) - 1;
			if ($last_idx > 0 && preg_match('/^---[\s\w\-]+---/', $key_parts[$last_idx]) === 1) {
				array_pop($key_parts);
			}
		}
		$key = implode('', $key_parts);
		$key = "-----BEGIN PUBLIC KEY-----\\n$key\\n-----END PUBLIC KEY-----";
		return $key;
	}

	/**
	 * Remove identity provider action.
	 * Here is possible to remove one identity provider or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeIdPs($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$idp_config = $this->getModule('idp_config');
		$result = $idp_config->removeIdentityProvidersConfig($names);
		if ($result === true) {
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove identity providers. Name: {$names[$i]}"
				);
			}
		}

		// Refresh identity provider list
		$this->setIdPList($sender, $param);
	}
}
