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

use Bacularis\Web\Modules\IdentityProviderConfig;
use Bacularis\Web\Modules\IIdentityProviderForm;
use Bacularis\Web\Modules\OIDC;
use Bacularis\Common\Modules\PKCE;

/**
 * Options for identity providers OIDC.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class IdentityProviderOIDC extends Portlets implements IIdentityProviderForm
{
	private $idp_type;

	public function setIdPType($control)
	{
		$this->idp_type = $control;
	}

	public function getIdPType()
	{
		return $this->idp_type;
	}

	/**
	 * Load settings from configuration to form.
	 *
	 * @param array $config identity provider config
	 */
	public function loadSettings(array $config): void
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
		if ($config['oidc_attr_sync_policy'] == OIDC::ATTR_SYNC_POLICY_NO_SYNC) {
			$this->IdPOIDCAttrSyncPolicyNoSync->Checked = true;
		} elseif ($config['oidc_attr_sync_policy'] == OIDC::ATTR_SYNC_POLICY_EACH_LOGIN) {
			$this->IdPOIDCAttrSyncPolicyEachLogin->Checked = true;
		}
	}

	/**
	 * Load default settings to form.
	 */
	public function loadDefaultSettings(): void
	{
		$this->IdPOIDCScope->Text = OIDC::DEF_SCOPE;
	}

	/**
	 * Get settings from form to save in config.
	 *
	 * @return array configuration to save
	 */
	public function getSettings(): array
	{
		$config = OIDC::getDefaultOptions();
		$config['oidc_redirect_uri'] = $this->IdPOIDCRedirectUri->Text;
		$config['oidc_use_discovery_endpoint'] = $this->IdPOIDCUseDiscoveryEndpoint->Checked ? '1' : '0';
		$config['oidc_discovery_endpoint'] = $this->IdPOIDCDiscoveryEndpoint->Text;
		$config['oidc_authorization_endpoint'] = $this->IdPOIDCAuthorizationEndpoint->Text;
		$config['oidc_token_endpoint'] = $this->IdPOIDCTokenEndpoint->Text;
		$config['oidc_end_session_endpoint'] = $this->IdPOIDCLogoutEndpoint->Text;
		$config['oidc_userinfo_endpoint'] = $this->IdPOIDCUserInfoEndpoint->Text;
		$config['oidc_issuer'] = $this->IdPOIDCIssuer->Text;
		$config['oidc_validate_sig'] = $this->IdPOIDCValidateSignatures->Checked ? '1' : '0';
		$config['oidc_public_key_string'] = $this->preparePublicKey($this->IdPOIDCPublicKeyString->Text);
		$config['oidc_public_key_id'] = trim($this->IdPOIDCPublicKeyID->Text);
		$config['oidc_use_jwks_endpoint'] = $this->IdPOIDCUseJWKSEndpoint->Checked ? '1' : '0';
		$config['oidc_jwks_uri'] = $this->IdPOIDCJWKSEndpoint->Text;
		$config['oidc_use_pkce'] = $this->IdPOIDCUsePKCE->Checked ? '1' : '0';
		$config['oidc_pkce_method'] = $this->IdPOIDCPKCEMethod->Text;
		$config['oidc_client_id'] = $this->IdPOIDCClientID->Text;
		$config['oidc_client_secret'] = $this->IdPOIDCClientSecret->Text;
		$config['oidc_scope'] = $this->IdPOIDCScope->Text;
		$config['oidc_prompt'] = '';
		$config['oidc_user_attr_source'] = $this->IdPOIDCUserAttrSource->SelectedValue;
		$config['oidc_user_attr'] = $this->IdPOIDCUserNameAttr->Text;
		$config['oidc_long_name_attr'] = $this->IdPOIDCLongNameAttr->Text;
		$config['oidc_email_attr'] = $this->IdPOIDCEmailAttr->Text;
		$config['oidc_desc_attr'] = $this->IdPOIDCDescriptionAttr->Text;
		if ($this->IdPOIDCAttrSyncPolicyNoSync->Checked) {
			// No sync policy
			$config['oidc_attr_sync_policy'] = OIDC::ATTR_SYNC_POLICY_NO_SYNC;
		} elseif ($this->IdPOIDCAttrSyncPolicyEachLogin->Checked) {
			// Each login policy
			$config['oidc_attr_sync_policy'] = OIDC::ATTR_SYNC_POLICY_EACH_LOGIN;
		} else {
			// Default policy
			$config['oidc_attr_sync_policy'] = OIDC::ATTR_SYNC_POLICY_NO_SYNC;
		}
		return $config;
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

}
