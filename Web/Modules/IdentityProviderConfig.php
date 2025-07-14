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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\ConfigFileModule;
use Bacularis\Common\Modules\PKCE;

/**
 * Identity provider configuration module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class IdentityProviderConfig extends ConfigFileModule
{
	/**
	 * Config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.identity_providers';

	/**
	 * Config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the identity provider name.
	 */
	public const NAME_PATTERN = '(?!^\d+$)[\w\-]{1,160}';

	/**
	 * Supported identity provider types.
	 */
	public const IDP_TYPE_OIDC = 'oidc';
	public const IDP_TYPE_OIDC_DESC = 'SSO - OpenID Connect';
	public const IDP_TYPE_OIDC_GOOGLE = 'google';
	public const IDP_TYPE_OIDC_GOOGLE_DESC = 'Google - Social Login';

	/**
	 * Default OpenID Connect scope.
	 */
	public const OIDC_DEF_SCOPE = 'openid email profile';

	/**
	 * Source of the OpenID Connect user attributes
	 */
	public const OIDC_USER_ATTR_SOURCE_ID_TOKEN = 'id_token';
	public const OIDC_USER_ATTR_SOURCE_USERINFO_ENDPOINT = 'userinfo';

	/**
	 * Redirect URI pattern for OpenID connect.
	 */
	public const OIDC_REDIRECT_URI_PATTERN = '%protocol://%host/web/oidc/%name/redirect';

	/**
	 * Attribute synchronization policies.
	 */
	public const ATTR_SYNC_POLICY_NO_SYNC = 'no_sync';
	public const ATTR_SYNC_POLICY_EACH_LOGIN = 'each_login';

	/**
	 * Stores config.
	 */
	private $config;

	/**
	 * Get config.
	 *
	 * @return array configuration
	 */
	public function getConfig(): array
	{
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			if (is_array($this->config)) {
				foreach ($this->config as $key => $value) {
					$value['name'] = $key;
					$this->config[$key] = $value;
				}
			}
		}
		return $this->config;
	}

	/**
	 * Set config.
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config): bool
	{
		$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		if ($result === true) {
			$this->config = null;
		}
		return $result;
	}

	/**
	 * Get config.
	 *
	 * @param string $name identity provider name
	 * @return array identity provider config
	 */
	public function getIdentityProviderConfig(string $name): array
	{
		$org_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$org_config = $config[$name];
			$org_config['name'] = $name;
		}
		return $org_config;
	}

	/**
	 * Set single identity provider config.
	 *
	 * @param string $name name
	 * @param array $org_config identity provider configuration
	 * @return bool true if identity provider saved successfully, otherwise false
	 */
	public function setIdentityProviderConfig(string $name, array $org_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $org_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single identity provider config.
	 *
	 * @param string $name identity provider name
	 * @return bool true if identity provider removed successfully, otherwise false
	 */
	public function removeIdentityProviderConfig(string $name): bool
	{
		$ret = false;
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			unset($config[$name]);
			$ret = $this->setConfig($config);
		}
		return $ret;
	}

	/**
	 * Remove identity providers config.
	 *
	 * @param array $names identity provider names
	 * @return bool true if identity providers removed successfully, otherwise false
	 */
	public function removeIdentityProvidersConfig(array $names): bool
	{
		$ret = true;
		for ($i = 0; $i < count($names); $i++) {
			$ret = $this->removeIdentityProviderConfig($names[$i]);
			if (!$ret) {
				break;
			}
		}
		return $ret;
	}

	/**
	 * Check if identity provider config exists.
	 *
	 * @param string $name identity provider name
	 * @return bool true if identity provider config exists, otherwise false
	 */
	public function identityProviderConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}

	/**
	 * Get identity provider description by type.
	 *
	 * @param string $idp_type identity provider type
	 * @return string identity provider description or empty string if idp not found
	 */
	public static function getIDPDescByType(string $idp_type)
	{
		$idp_desc = '';
		switch ($idp_type) {
			case self::IDP_TYPE_OIDC: {
				$idp_desc = self::IDP_TYPE_OIDC_DESC;
				break;
			}
			case self::IDP_TYPE_OIDC_GOOGLE: {
				$idp_desc = self::IDP_TYPE_OIDC_GOOGLE_DESC;
				break;
			}
		}
		return $idp_desc;
	}

	/**
	 * Get redirect URI.
	 *
	 * @param string $host hostname/IP address
	 * @param string $name identity provider configuration name
	 * @return string redirect URI ready to use
	 */
	public static function getRedirectURI(string $name): string
	{
		$protocol = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];
		$from = ['%protocol', '%host', '%name'];
		$to = [$protocol, $host, $name];
		return str_replace(
			$from,
			$to,
			self::OIDC_REDIRECT_URI_PATTERN
		);
	}

	public static function getOIDCOptions(): array
	{
		$config = [];
		$config['oidc_redirect_uri'] = '';
		$config['oidc_use_discovery_endpoint'] = '1';
		$config['oidc_discovery_endpoint'] = '';
		$config['oidc_authorization_endpoint'] = '';
		$config['oidc_token_endpoint'] = '';
		$config['oidc_end_session_endpoint'] = '';
		$config['oidc_userinfo_endpoint'] = '';
		$config['oidc_issuer'] = '';
		$config['oidc_validate_sig'] = '1';
		$config['oidc_public_key_string'] = '';
		$config['oidc_public_key_id'] = '';
		$config['oidc_use_jwks_endpoint'] = '1';
		$config['oidc_jwks_uri'] = '';
		$config['oidc_use_pkce'] = '1';
		$config['oidc_pkce_method'] = PKCE::CODE_CHALLENGE_METHOD_S256;
		$config['oidc_client_id'] = '';
		$config['oidc_client_secret'] = '';
		$config['oidc_scope'] = self::OIDC_DEF_SCOPE;
		$config['oidc_prompt'] = '';
		$config['oidc_user_attr_source'] = self::OIDC_USER_ATTR_SOURCE_ID_TOKEN;
		$config['oidc_user_attr'] = '';
		$config['oidc_long_name_attr'] = '';
		$config['oidc_email_attr'] = '';
		$config['oidc_desc_attr'] = '';
		$config['oidc_attr_sync_policy'] = self::ATTR_SYNC_POLICY_NO_SYNC;
		return $config;
	}

	public static function getIdPIconCSSByType(string $idp_type)
	{
		$icon = '';
		switch ($idp_type) {
			case self::IDP_TYPE_OIDC: $icon = 'fa-brands fa-openid'; break;
			case self::IDP_TYPE_OIDC_GOOGLE: $icon = 'fa-brands fa-google'; break;
			default: $icon = 'fa-solid fa-key'; break;
		}
		return $icon;
	}
}
