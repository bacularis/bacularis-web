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
 * Manage SSH configuration.
 * Module is responsible for get/set SSH config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Config
 */
class SSHConfig extends ConfigFileModule
{
	/**
	 * Allowed characters pattern for SSH config name.
	 */
	public const SSH_CONFIG_NAME_PATTERN = '[a-zA-Z0-9\.\-_\*\?]+';

	/**
	 * SSH config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.ssh';

	/**
	 * SSH config file format
	 */
	public const CONFIG_FILE_FORMAT = 'section';

	public function init($config)
	{
		$this->getModule('config_section')->setSectionName('Host');
	}

	/**
	 * Get (read) SSH config.
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
	 * Set (save) SSH config.
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
	 * Get single SSH config.
	 *
	 * @param string $host
	 * @return array user config
	 */
	public function getHostConfig($host)
	{
		$host_config = [];
		$config = $this->getConfig();
		if (key_exists($host, $config)) {
			$host_config = $config[$host];
		}
		return $host_config;
	}

	/**
	 * Set single host config.
	 *
	 * @param string $host host name
	 * @param array $host_config host configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setHostConfig($host, array $host_config)
	{
		$config = $this->getConfig();
		$config[$host] = $host_config;
		return $this->setConfig($config);
	}

	/**
	 * Get SSH config file path in filesystem.
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
	 * Validate SSH config.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in read/write config methods.
	 *
	 * @access private
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
