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
use Bacularis\Common\Modules\Logging;

/**
 * Manage host groups configuration.
 * Module is responsible for get/set host groups config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class HostGroupConfig extends ConfigFileModule
{
	/**
	 * API host group name allowed characters pattern
	 */
	public const HOST_GROUP_PATTERN = '[\w\s\@\-\.]+';

	/**
	 * Host group config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.host_groups';

	/**
	 * Host group config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * These host group options are obligatory for each host group config.
	 */
	private $host_group_required_options = [
		'name',
		'api_hosts'
	];

	/**
	 * Get (read) host config.
	 *
	 * @return array config
	 */
	public function getConfig()
	{
		$host_groups_config = [];
		$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		// Host groups config validation per single host
		foreach ($config as $group => $host_group_config) {
			if ($this->isHostGroupConfigValid([$group => $host_group_config]) === false) {
				/**
				 * If host config for one host is invalid, don't add this host config
				 * but continue checking next host config sections.
				 * It is because during adding new host manually, sys admin can do a typo
				 * in new host config section. I don't want that this typo causes no access
				 * to rest hosts by web interface. Validation errors are logged.
				 */
				continue;
			}
			$host_groups_config[$group] = $host_group_config;
		}
		return $host_groups_config;
	}

	/**
	 * Set (save) host groups config.
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config)
	{
		$result = false;
		if ($this->isHostGroupConfigValid($config) === true) {
			$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		return $result;
	}

	/**
	 * Get single host group config.
	 *
	 * @param $group host group name
	 * @return array host config
	 */
	public function getHostGroupConfig($group)
	{
		$host_group_config = [];
		$config = $this->getConfig();
		if (key_exists($group, $config)) {
			$host_group_config = $config[$group];
		}
		return $host_group_config;
	}

	/**
	 * Set single host group config.
	 *
	 * @param string $group host group name
	 * @param array $host_group_config host group config
	 * @return bool true if host group config saved successfully, otherwise false
	 */
	public function setHostGroupConfig($group, array $host_group_config)
	{
		$config = $this->getConfig();
		$config[$group] = $host_group_config;
		$result = $this->setConfig($config);
		return $result;
	}

	/**
	 * Check if single host group exists.
	 *
	 * @param $group host group name
	 * @return bool true if host group exists, othwerwise false
	 */
	public function isHostGroupConfig($group)
	{
		$config = $this->getHostGroupConfig($group);
		return (count($config) > 0);
	}

	/**
	 * Remove host groups from config.
	 *
	 * @param array $groups host group name
	 * @return bool true if host group removed successfully, othwerwise false
	 */
	public function removeHostGroupsConfig(array $groups)
	{
		$config = $this->getConfig();
		$cfg = [];
		foreach ($config as $group => $opts) {
			if (in_array($group, $groups)) {
				continue;
			}
			$cfg[$group] = $opts;
		}
		return $this->setConfig($cfg);
	}

	/**
	 * Validate host groups config.
	 *
	 * @param array $config host groups config
	 * @return bool true if config valid, otherwise false
	 */
	private function isHostGroupConfigValid(array $config)
	{
		$valid = true;
		$invalid = ['required' => null];

		foreach ($config as $group => $host_group_config) {
			for ($i = 0; $i < count($this->host_group_required_options); $i++) {
				if (!key_exists($this->host_group_required_options[$i], $host_group_config)) {
					$invalid['required'] = [
						'host_group' => $group,
						'value' => $this->host_group_required_options[$i],
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
				$emsg = "ERROR [$path] Required {$invalid['required']['type']} '{$invalid['required']['value']}' not found for host group '{$invalid['required']['host_group']}.";
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

	/**
	 * Get API hosts by API host groups.
	 * It is  useful to get all particular API hosts from API group(s).
	 *
	 * @param array $host_groups host groups
	 * @return array API host list
	 */
	public function getAPIHostsByGroups(array $host_groups)
	{
		$api_hosts = [];
		$config = $this->getConfig();
		for ($i = 0; $i < count($host_groups); $i++) {
			if (!key_exists($host_groups[$i], $config)) {
				// group does not exists, skip it
				continue;
			}
			$api_hosts = array_merge(
				$api_hosts,
				$config[$host_groups[$i]]['api_hosts']
			);
		}
		return $api_hosts;
	}
}
