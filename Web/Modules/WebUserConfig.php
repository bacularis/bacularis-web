<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
	 * Multi-factor authentication types.
	 */
	public const MFA_TYPE_NONE = 'none';
	public const MFA_TYPE_TOTP = 'totp';
	public const MFA_TYPE_FIDOU2F = 'fidou2f';

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
			if (!key_exists('username', $user_config)) {
				$user_config['username'] = $username;
			}

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
			if (!key_exists('organization_id', $user_config)) {
				$user_config['organization_id'] = '';
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
			if ($result) {
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
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @return array user config
	 */
	public function getUserConfig(string $org_id, string $user_id): array
	{
		$user_config = [];
		$config = $this->getConfig();
		$uid = $user_id;
		if (is_string($org_id) && !empty($org_id)) {
			$uid = self::getOrgUserID($org_id, $user_id);
		}
		if (key_exists($uid, $config)) {
			$user_config = $config[$uid];
		}
		return $user_config;
	}

	/**
	 * Get organization user identifier.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @return string organization user identifier
	 */
	public static function getOrgUserID(string $org_id, string $user_id): string
	{
		$ouid = !empty($org_id) ? "{$org_id} {$user_id}" : $user_id;
		return $ouid;
	}
	/**
	 * Get organization user.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @return array organization user data
	 */
	public static function getOrgUser(string $org_id, string $user_id): array
	{
		return [
			'user_id' => $user_id,
			'org_id' => $org_id
		];
	}

	/**
	 * Check if user exists.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @return bool true if user exists, otherwise false
	 */
	public function userExists(string $org_id, string $user_id): bool
	{
		$uid = self::getOrgUserID($org_id, $user_id);
		$config = $this->getConfig();
		return key_exists($uid, $config);
	}

	/**
	 * Set single user config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param array $user_config user configuration
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function setUserConfig(string $org_id, string $user_id, array $user_config): bool
	{
		$config = $this->getConfig();
		$prev_org_id = $prev_user_id = $new_org_id = $new_user_id = '';
		$rm_uid = null;
		$add_uid = self::getOrgUserID($user_config['organization_id'], $user_id);
		if ($org_id != $user_config['organization_id']) {
			if (!empty($org_id) && !empty($user_config['organization_id'])) {
				// changed organization org1 => org2
				$prev_org_id = $org_id;
				$prev_user_id = $user_id;
				$new_org_id = $user_config['organization_id'];
				$new_user_id = $user_id;
			} elseif (!empty($org_id) && empty($user_config['organization_id'])) {
				// unassigned from organization org1 => none
				$prev_org_id = $org_id;
				$prev_user_id = $user_id;
				$new_org_id = '';
				$new_user_id = $user_id;
			} elseif (empty($org_id) && !empty($user_config['organization_id'])) {
				// assigned to organization none => org1
				$prev_org_id = '';
				$prev_user_id = $user_id;
				$new_org_id = $user_config['organization_id'];
				$new_user_id = $user_id;
			}
			$rm_uid = self::getOrgUserID($prev_org_id, $prev_user_id);
			if (key_exists($rm_uid, $config)) {
				unset($config[$rm_uid]);
			}
			$add_uid = self::getOrgUserID($new_org_id, $new_user_id);
		}
		$config[$add_uid] = $user_config;
		$ret = $this->setConfig($config);
		if ($ret) {
			if ($org_id != $user_config['organization_id']) {
				// user organization changed, rename related configs
				$this->moveUserConfig(
					$prev_org_id,
					$new_org_id,
					$prev_user_id,
					$new_user_id
				);
			}
		}
		return $ret;
	}

	/**
	 * On organization rename move related user settings.
	 *
	 * @param string $prev_org_id previous organization identifier
	 * @param string $new_org_id new organization identifier
	 * @param string $prev_user_id previous user identifier
	 * @param string $new_user_id new user identifier
	 */
	private function moveUserConfig(string $prev_org_id, string $new_org_id, string $prev_user_id, string $new_user_id): void
	{
		// move tag config
		$tag_config = $this->getModule('tag_config');
		$tag_config->moveUserTagConfig(
			$prev_org_id,
			$new_org_id,
			$prev_user_id,
			$new_user_id
		);

		// move tag assigns config
		$tag_assign_config = $this->getModule('tag_assign_config');
		$tag_assign_config->moveUserTagAssignsConfig(
			$prev_org_id,
			$new_org_id,
			$prev_user_id,
			$new_user_id
		);

		// move data view config
		$dataview_config = $this->getModule('dataview_config');
		$dataview_config->moveUserDataViewConfig(
			$prev_org_id,
			$new_org_id,
			$prev_user_id,
			$new_user_id
		);
	}

	/**
	 * Remove single user config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @return bool true on success, false otherwise
	 */
	public function removeUserConfig(string $org_id, string $user_id): bool
	{
		$users = $this->getConfig();
		$uid = self::getOrgUserID($org_id, $user_id);
		if (key_exists($uid, $users)) {
			unset($users[$uid]);
		}
		return $this->setConfig($users);
	}

	/**
	 * Remove users config.
	 *
	 * @param string $uids user identifiers
	 * @return bool true on success, false otherwise
	 */
	public function removeUsersConfig(array $uids): bool
	{
		$users = $this->getConfig();
		for ($i = 0; $i < count($uids); $i++) {
			if (!isset($uids[$i]['org_id']) || !isset($uids[$i]['user_id'])) {
				continue;
			}
			$uid = self::getOrgUserID($uids[$i]['org_id'], $uids[$i]['user_id']);
			if (key_exists($uid, $users)) {
				unset($users[$uid]);
			}
		}
		return $this->setConfig($users);
	}

	/**
	 * Update single user config.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param array $user_config user configuration part to update
	 * @return bool true if config saved successfully, otherwise false
	 */
	public function updateUserConfig(string $org_id, string $user_id, array $user_config): bool
	{
		$config = $this->getUserConfig($org_id, $user_id);
		if (count($config) == 0) {
			return false;
		}
		foreach ($user_config as $key => $value) {
			if (key_exists($key, $config) && is_array($config[$key])) {
				$config[$key] = array_merge(
					$config[$key],
					$value
				);
			} else {
				$config[$key] = $value;
			}
		}
		return $this->setUserConfig($org_id, $user_id, $config);
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
	 * Assign roles to given users.
	 *
	 * @param array $users user list
	 * @param array $roles role list
	 * @return bool true if roles assigned successfully, otherwise false
	 */
	public function assignUserRoles(array $users, array $roles): bool
	{
		$config = $this->getConfig();
		$user_len = count($users);
		for ($i = 0; $i < $user_len; $i++) {
			$uid = self::getOrgUserID(
				$users[$i]['org_id'],
				$users[$i]['user_id']
			);
			if (!key_exists($uid, $config)) {
				// non-existing user given, skip it
				continue;
			}
			$rls = explode(',', $config[$uid]['roles']);
			$rls = array_merge($rls, $roles);
			$rls = array_unique($rls);
			$config[$uid]['roles'] = implode(',', $rls);
		}
		return $this->setConfig($config);
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
			foreach ($config as $uid => $conf) {
				$new_api_hosts = [];
				for ($j = 0; $j < count($conf['api_hosts']); $j++) {
					if ($hosts[$i] === $conf['api_hosts'][$j]) {
						continue;
					}
					$new_api_hosts[] = $conf['api_hosts'][$j];
				}
				$config[$uid]['api_hosts'] = $new_api_hosts;
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
