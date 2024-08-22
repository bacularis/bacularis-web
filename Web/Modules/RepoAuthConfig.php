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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\ConfigFileModule;

/**
 * Manage package repository configuration.
 * Module is responsible for get/set authentication data for the binary package repositories.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class RepoAuthConfig extends ConfigFileModule
{
	/**
	 * Allowed characters pattern for repository authentication name.
	 */
	public const REPO_AUTH_NAME_PATTERN = '[a-zA-Z0-9\.\-_\s]+';

	/**
	 * Repository authentication file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.repo_auth';

	/**
	 * Repository authentication file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Get (read) repository authentication.
	 *
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null)
	{
		$config = $this->readConfig(
			self::CONFIG_FILE_PATH,
			self::CONFIG_FILE_FORMAT
		);
		if ($this->validateConfig($config) === true) {
			if (!is_null($section)) {
				$config = key_exists($section, $config) ? $config[$section] : [];
			}
		} else {
			$config = [];
		}
		return $config;
	}

	/**
	 * Set (save) Repository authentication.
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config)
	{
		$result = false;
		if ($this->validateConfig($config) === true) {
			$result = $this->writeConfig(
				$config,
				self::CONFIG_FILE_PATH,
				self::CONFIG_FILE_FORMAT
			);
		}
		return $result;
	}

	/**
	 * Get single repo auth.
	 *
	 * @param string $name repo auth name
	 * @return array repo auth config
	 */
	public function getRepoAuthConfig($name)
	{
		$repo_auth_config = [];
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			$repo_auth_config = $config[$name];
		}
		return $repo_auth_config;
	}

	/**
	 * Set single repo auth config.
	 *
	 * @param string $name repo auth name
	 * @param array $repo_auth_config repo auth configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setRepoAuthConfig($name, array $repo_auth_config)
	{
		$config = $this->getConfig();
		if (key_exists('default', $repo_auth_config) && $repo_auth_config['default'] == 1) {
			// New default is coming. Remove previous default value;
			foreach ($config as $section => $value) {
				if ($value['default'] == 1) {
					$config[$section]['default'] = '0';
				}
			}
		}
		$config[$name] = $repo_auth_config;
		return $this->setConfig($config);
	}

	/**
	 * Remove single repo auth config.
	 *
	 * @param string $name repo auth name
	 * @return bool true if config removed successfully, otherwise false
	 */
	public function removeRepoAuthConfig($name)
	{
		$config = $this->getConfig();
		if (key_exists($name, $config)) {
			unset($config[$name]);
		}
		return $this->setConfig($config);
	}

	public function getDefaultRepoAuthConfig()
	{
		$config = $this->getConfig();
		$default = [];
		foreach ($config as $value) {
			if ($value['default'] == 1) {
				$default = $value;
			}
		}
		return $default;
	}

	/**
	 * Get repository authentication file path in filesystem.
	 *
	 * @return string config path of empty string if file does not exist
	 */
	public function getConfigPath()
	{
		$path = $this->getConfigRealPath(self::CONFIG_FILE_PATH);
		if (!file_exists($path)) {
			$path = '';
		}
		return $path;
	}

	/**
	 * Validate repository authentication.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in read/write config methods.
	 *
	 * @param array $config config
	 * @return bool true if config valid, otherwise false
	 */
	private function validateConfig(array $config = [])
	{
		return $this->isConfigValid(
			[],
			$config,
			self::CONFIG_FILE_FORMAT,
			self::CONFIG_FILE_PATH
		);
	}
}
