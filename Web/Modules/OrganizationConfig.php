<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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
 * Organization configuration module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class OrganizationConfig extends ConfigFileModule
{
	/**
	 * Config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.organizations';

	/**
	 * Config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Allowed characters pattern for the organization name.
	 */
	public const NAME_PATTERN = '(?!^\d+$)[\w\-]{1,160}';

	/**
	 * Supported in organization auth types.
	 */
	public const AUTH_TYPE_AUTH_METHOD = 'auth_method';
	public const AUTH_TYPE_IDP = 'idp';

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
	 * @param string $name organization name
	 * @param bool $with_idp_config if true, in results is added idp config
	 * @return array organization config
	 */
	public function getOrganizationConfig(string $name, bool $with_idp_config = false): array
	{
		$org_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$org_config = $config[$name];
			$org_config['name'] = $name;
			if ($with_idp_config) {
				$idp_config = $this->getModule('idp_config');
				$org_config['idp'] = $idp_config->getIdentityProviderConfig(
					$org_config['identity_provider']
				);
			}
		}
		return $org_config;
	}

	/**
	 * Set single organization config.
	 *
	 * @param string $name name
	 * @param array $org_config organization configuration
	 * @return bool true if organization saved successfully, otherwise false
	 */
	public function setOrganizationConfig(string $name, array $org_config): bool
	{
		$config = $this->getConfig();
		$config[$name] = $org_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single organization config.
	 *
	 * @param string $name organization name
	 * @return bool true if organization removed successfully, otherwise false
	 */
	public function removeOrganizationConfig(string $name): bool
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
	 * Remove organizations config.
	 *
	 * @param array $names organization names
	 * @return bool true if organizations removed successfully, otherwise false
	 */
	public function removeOrganizationsConfig(array $names): bool
	{
		$ret = true;
		for ($i = 0; $i < count($names); $i++) {
			$ret = $this->removeOrganizationConfig($names[$i]);
			if (!$ret) {
				break;
			}
		}
		return $ret;
	}

	/**
	 * Check if organization config exists.
	 *
	 * @param string $name organization name
	 * @return bool true if organization config exists, otherwise false
	 */
	public function organizationConfigExists(string $name): bool
	{
		$config = $this->getConfig();
		return key_exists($name, $config);
	}
}
