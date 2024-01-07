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
 * Copyright (C) 2013-2020 Kern Sibbald
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
 * Manage web role configuration.
 * Module is responsible for get/set web role config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebRoleConfig extends ConfigFileModule
{
	/**
	 * Web role name allowed characters pattern
	 */
	public const ROLE_PATTERN = '[\w\@\-\.]+';

	/**
	 * Web role config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.roles';

	/**
	 * Web role config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * These options are obligatory for web config.
	 */
	private $role_required_options = [
		'long_name',
		'description',
		'enabled',
		'resources'
	];

	/**
	 * Stores web roles config content.
	 */
	private $config;

	/**
	 * Get (read) web role config.
	 *
	 * @return array config
	 */
	public function getConfig()
	{
		if (is_null($this->config)) {
			$this->config = $this->getConfigInternal();
		}
		return $this->config;
	}

	private function getConfigInternal()
	{
		$roles_config = [];
		$config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		// Web role config validation per single role
		foreach ($config as $role => $role_config) {
			if ($this->isRoleConfigValid([$role => $role_config]) === false) {
				/**
				 * If config for one web role is invalid, don't add this role config
				 * but continue checking next role config sections.
				 * It is because during adding new role manually, admin can do a typo
				 * in new role config section.  Validation errors are logged.
				 */
				continue;
			}
			$role_config['role'] = $role;
			$roles_config[$role] = $role_config;
		}
		return $roles_config;
	}

	/**
	 * Set web role config.
	 * Method is private, because this method saves whole web role config.
	 * To add (or modify) role in config, use method to save single role in config.
	 * @see setRoleConfig()
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config)
	{
		$result = false;
		if ($this->isRoleConfigValid($config) === true) {
			$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			if ($result === true) {
				$this->config = null;
				;
			}
		}
		return $result;
	}

	/**
	 * Get single role config.
	 *
	 * @param $role role name
	 * @return array role config
	 */
	public function getRoleConfig($role)
	{
		$role_config = [];
		$config = $this->getConfig();
		if (key_exists($role, $config)) {
			$config[$role]['role'] = $role;
			$role_config = $config[$role];
		}
		return $role_config;
	}

	/**
	 * Set single role config.
	 *
	 * @param string $role role name
	 * @param array $role config
	 * @param array $role_config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setRoleConfig($role, array $role_config)
	{
		$config = $this->getConfig();
		$config[$role] = $role_config;
		return $this->setConfig($config);
	}

	/**
	 * Validate role single role section in config.
	 *
	 * @param array $config role config section
	 * @return bool true if config valid, otherwise false
	 */
	private function isRoleConfigValid(array $config)
	{
		$invalid = ['required' => []];
		foreach ($config as $role => $role_config) {
			for ($i = 0; $i < count($this->role_required_options); $i++) {
				if (!key_exists($this->role_required_options[$i], $role_config)) {
					$invalid['required'][] = [
						'role' => $role,
						'value' => $this->role_required_options[$i],
						'type' => 'option'
					];
				}
			}
		}

		$valid = true;
		$required_len = count($invalid['required']);
		if ($required_len > 0) {
			$valid = false;
			$emsg = '';
			$path = $this->getConfigRealPath(self::CONFIG_FILE_PATH);
			for ($i = 0; $i < $required_len; $i++) {
				$emsg = "ERROR [$path] Required {$invalid['required'][$i]['type']} '{$invalid['required'][$i]['value']}' not found for role '{$invalid['required'][$i]['role']}.";
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					$emsg
				);
			}
		}
		return $valid;
	}
}
