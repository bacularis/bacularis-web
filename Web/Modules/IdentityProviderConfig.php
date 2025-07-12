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

	/**
	 * Default OpenID Connect scope.
	 */
	public const OIDC_DEF_SCOPE = 'openid email';

	/**
	 * Source of the OpenID Connect user attributes
	 */
	public const OIDC_USER_ATTR_SOURCE_ID_TOKEN = 'id_token';
	public const OIDC_USER_ATTR_SOURCE_USERINFO_ENDPOINT = 'userinfo';

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
		}
		return $idp_desc;
	}
}
