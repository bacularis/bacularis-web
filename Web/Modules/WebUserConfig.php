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
 * Copyright (C) 2013-2021 Kern Sibbald
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
 * Manage web user configuration.
 * Module is responsible for get/set web user config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebUserConfig extends ConfigFileModule
{
	/**
	 * Web user name allowed characters pattern
	 */
	public const USER_PATTERN = '[\w\@\-\.]+';

	/**
	 * Regular expression to validate e-mail address.
	 * Note, there exists TEmailAddressValidator control but it doesn't allow
	 * local e-mail address, for example root@localhost or gani@darkstar.
	 *
	 * @see http://www.regular-expressions.info/email.html
	 */
	public const EMAIL_ADDRESS_PATTERN = '[a-zA-Z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.?)+';

	/**
	 * Web user config file path
	 */
	public const CONFIG_FILE_PATH = 'Bacularis.Web.Config.users';

	/**
	 * Web user config file format
	 */
	public const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * API host methods.
	 * It determines if are used API hosts or API host groups.
	 */
	public const API_HOST_METHOD_HOSTS = 'hosts';
	public const API_HOST_METHOD_HOST_GROUPS = 'host_groups';

	/**
	 * Stores web user config content.
	 */
	private $config;

	/**
	 * These properties are obligatory for web config.
	 */
	private $user_req_prop = [
		'long_name',
		'description',
		'email',
		'roles',
		'enabled',
		'ips'
	];

	/**
	 * Get web user config.
	 *
	 * @return array config
	 */
	public function getConfig()
	{
		$config = [];
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
		}
		// Web user config validation per single user
		foreach ($this->config as $username => $user_config) {
			if ($this->isUserConfigValid([$username => $user_config]) === false) {
				/**
				 * If config for one web user is invalid, don't add this user config
				 * but continue checking next user config sections.
				 * It is because during adding new user manually, admin can do a typo
				 * in new user config section.  Validation errors are logged.
				 */
				continue;
			}
			$user_config['username'] = $username;
			// for API hosts backward compatibility
			if (key_exists('api_hosts', $user_config)) {
				if (!is_array($user_config['api_hosts'])) {
					$user_config['api_hosts'] = !empty($user_config['api_hosts']) ? [$user_config['api_hosts']] : [];
				}
			} else {
				$user_config['api_hosts'] = [];
			}
			if (!key_exists('api_hosts_method', $user_config)) {
				// default is 'hosts' method for backward compatibility
				$user_config['api_hosts_method'] = self::API_HOST_METHOD_HOSTS;
			}
			if (!key_exists('api_host_groups', $user_config)) {
				$user_config['api_host_groups'] = [];
			}
			$config[$username] = $user_config;
		}
		return $config;
	}

	/**
	 * Set web user config.
	 * Method is private, because this method saves whole web user config.
	 * To add (or modify) user in config, use method to save single user in config.
	 * @see setUserConfig()
	 *
	 * @param array $config config
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config)
	{
		$result = false;
		if ($this->isUserConfigValid($config) === true) {
			$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			if ($result === true) {
				$this->config = null;
			}
		}
		return $result;
	}

	public function configExists()
	{
		return parent::isConfig(self::CONFIG_FILE_PATH);
	}

	/**
	 * Get single user config.
	 *
	 * @param string $username
	 * @return array user config
	 */
	public function getUserConfig($username)
	{
		$user_config = [];
		$config = $this->getConfig();
		if (key_exists($username, $config)) {
			$config[$username]['username'] = $username;
			$user_config = $config[$username];
		}
		return $user_config;
	}

	/**
	 * Set single user config.
	 *
	 * @param string $username user name
	 * @param array $user_config user configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setUserConfig($username, array $user_config)
	{
		$config = $this->getConfig();
		$config[$username] = $user_config;
		return $this->setConfig($config);
	}

	/**
	 * Get user config properties.
	 * If custom properties provided, they are merged with required properties.
	 *
	 * @param array $prop custom user properties
	 * @return array user config properties
	 */
	public function getUserConfigProps($prop = [])
	{
		$req_prop = array_fill_keys($this->user_req_prop, '');
		return array_merge($req_prop, $prop);
	}

	/**
	 * Unassign given API hosts from all user accounts.
	 *
	 * @param array $hosts API host list to unassign
	 * @return bool true if hosts unassigned successfully, otherwise false
	 */
	public function unassignAPIHosts(array $hosts)
	{
		$config = $this->getConfig();
		for ($i = 0; $i < count($hosts); $i++) {
			foreach ($config as $username => $conf) {
				$new_api_hosts = [];
				for ($j = 0; $j < count($conf['api_hosts']); $j++) {
					if ($hosts[$i] === $conf['api_hosts'][$j]) {
						continue;
					}
					$new_api_hosts[] = $conf['api_hosts'][$j];
				}
				$config[$username]['api_hosts'] = $new_api_hosts;
			}
		}
		return $this->setConfig($config);
	}


	/**
	 * Import basic auth users to web user config.
	 * It can be done both if web user config hasn't been created yet
	 * and if web user config exists already with some users.
	 *
	 * @return bool true if config with imported users saved successfully,
	 *                      otherwise false
	 */
	public function importUsersToConfig()
	{
		$basic_users = $this->getModule('basic_webuser')->getUsers();
		$web_config = $this->getModule('web_config')->getConfig();
		$users = array_keys($basic_users);
		sort($users);
		$users_list = [];
		$is_users_config = (key_exists('users', $web_config) && is_array($web_config['users']));
		$user_count = count($users);
		for ($i = 0; $i < $user_count; $i++) {
			$host = '';
			if ($is_users_config && key_exists($users[$i], $web_config['users'])) {
				$host = $web_config['users'][$users[$i]];
			}
			$role = '';
			if ((isset($web_config['baculum']['login']) && $users[$i] === $web_config['baculum']['login']) || $user_count === 1) {
				$role = WebUserRoles::ADMIN;
			} else {
				$role = WebUserRoles::NORMAL;
			}
			$users_list[$users[$i]] = $this->getUserConfigProps([
				'roles' => $role,
				'api_hosts' => $host,
				'enabled' => 1
			]);
		}
		/**
		 * Merge existing user configs if any.
		 * Already existing users overwrites basic user configs if the same users
		 * exists in basic user config and web user configuration.
		 */
		$users_list = array_merge($users_list, $this->getConfig());
		return $this->setConfig($users_list);
	}

	/**
	 * Import users config from basic auth file into users.conf file.
	 * If users config file doesn't exist, create it and populate
	 * using basic users file. It is one time operation.
	 * Basic auth method is the main Baculum Web auth method. Before introducing
	 * users.conf file, it was the only one supported method.
	 *
	 * @return bool true on successfull import, otherwise false
	 */
	public function importUsers()
	{
		// import can take place only if user config file doesn't exist
		if (parent::isConfig(self::CONFIG_FILE_PATH) === true) {
			return false;
		}

		// import users
		$ret = $this->importUsersToConfig();
		if ($ret === true) {
			$ret = $this->getModule('web_config')->setDefConfigOpts();
		} else {
			$ret = false;
			$emsg = 'Error while importing basic users.';
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		return $ret;
	}

	/**
	 * Validate user single user section in config.
	 *
	 * @param array $config user config section
	 * @return bool true if config valid, otherwise false
	 */
	private function isUserConfigValid(array $config)
	{
		$invalid = ['required' => []];
		foreach ($config as $username => $user_config) {
			for ($i = 0; $i < count($this->user_req_prop); $i++) {
				if (!key_exists($this->user_req_prop[$i], $user_config)) {
					$invalid['required'][] = [
						'username' => $username,
						'value' => $this->user_req_prop[$i],
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
				$emsg = "ERROR [$path] Required {$invalid['required'][$i]['type']} '{$invalid['required'][$i]['value']}' not found for user '{$invalid['required'][$i]['username']}.";
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					$emsg
				);
			}
		}
		return $valid;
	}
}
