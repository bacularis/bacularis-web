<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2019 Kern Sibbald
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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\ConfigFileModule;
use Bacularis\Common\Modules\Logging;

/**
 * Manage hosts configuration.
 * Module is responsible for get/set hosts config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class HostConfig extends ConfigFileModule
{
	/**
	 * Host config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.hosts';

	/**
	 * Host config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Main host that provides Catalog
	 */
	public const MAIN_CATALOG_HOST = 'Main';

	/**
	 * These host options are obligatory for each host config.
	 */
	private $host_required_options = [
		'protocol',
		'address',
		'port',
		'auth_type',
		'login',
		'password',
		'client_id',
		'client_secret',
		'redirect_uri',
		'scope'
	];

	/**
	 * Get (read) host config.
	 *
	 * @access public
	 * @return array config
	 */
	public function getConfig()
	{
		$hosts_config = [];
		$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		// Hosts config validation per single host
		foreach ($config as $host => $host_config) {
			if ($this->isHostConfigValid([$host => $host_config]) === false) {
				/**
				 * If host config for one host is invalid, don't add this host config
				 * but continue checking next host config sections.
				 * It is because during adding new host manually, sys admin can do a typo
				 * in new host config section. I don't want that this typo causes no access
				 * to rest hosts by web interface. Validation errors are logged.
				 */
				continue;
			}
			$hosts_config[$host] = $host_config;
		}
		return $hosts_config;
	}

	/**
	 * Set (save) hosts config.
	 * Method is private, because this method saves whole hosts config.
	 * To add (or modify) host in config, use method to save single host in config.
	 * @see setHostConfig()
	 *
	 * @access public
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config)
	{
		$result = false;
		if ($this->isHostConfigValid($config) === true) {
			$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		return $result;
	}

	/**
	 * Get single host config.
	 *
	 * @access public
	 * @param $host host name
	 * @return array host config
	 */
	public function getHostConfig($host)
	{
		$host_config = [];
		$config = $this->getConfig();
		if (array_key_exists($host, $config)) {
			$host_config = $config[$host];
		}
		return $host_config;
	}

	/**
	 * Set single host config.
	 *
	 * @access public
	 * @param string $host host name
	 * @param array $host config
	 * @param array $host_config
	 * @return bool true if host config saved successfully, otherwise false
	 */
	public function setHostConfig($host, array $host_config)
	{
		$config = $this->getConfig();
		$config[$host] = $host_config;
		$result = $this->setConfig($config);
		return $result;
	}

	/**
	 * Validate hosts config.
	 *
	 * @access private
	 * @param array $config hosts config
	 * @return bool true if config valid, otherwise false
	 */
	private function isHostConfigValid(array $config)
	{
		$valid = true;
		$invalid = ['required' => null];

		foreach ($config as $host => $host_config) {
			for ($i = 0; $i < count($this->host_required_options); $i++) {
				if (!array_key_exists($this->host_required_options[$i], $host_config)) {
					$invalid['required'] = [
						'host' => $host,
						'value' => $this->host_required_options[$i],
						'type' => 'option'
					];
					$valid = false;
					break;
				}
			}
		}
		if ($valid != true) {
			$emsg = '';
			$path = $this->getConfigRealPath(self::CONFIG_FILE_PATH);
			if ($invalid['required']['type'] === 'option') {
				$emsg = "ERROR [$path] Required {$invalid['required']['type']} '{$invalid['required']['value']}' not found for host '{$invalid['required']['host']}.";
			} else {
				// it shouldn't happen
				$emsg = "ERROR [$path] Internal error";
			}
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		return $valid;
	}
}
