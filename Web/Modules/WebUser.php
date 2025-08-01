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

use Prado\Prado;
use Prado\Security\TUser;
use Prado\TPropertyValue;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\WebUserConfig;

/**
 * Web user module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebUser extends TUser
{
	/**
	 * Saved in current state user properties.
	 */
	public const LONG_NAME = 'LongName';
	public const EMAIL = 'Email';
	public const DESCRIPTION = 'Description';
	public const API_HOST_METHOD = 'ApiHostMethod';
	public const API_HOSTS = 'ApiHosts';
	public const DEFAULT_API_HOST = 'DefaultApiHost';
	public const ORGANIZATION = 'Organization';
	public const IPS = 'Ips';
	public const ENABLED = 'Enabled';
	public const IN_CONFIG = 'InConfig';

	/**
	 * Stores non-session information about user.
	 */
	private $current_state = [];

	/**
	 * Create single user instance.
	 * Used for authenticated users.
	 * If user doesn't exist in configuration then default access values can be taken into accout.
	 *
	 * @param array $org_user user data
	 * @param bool $new_obj create new user object
	 * @param mixed $username
	 * @return WebUser user instance
	 */
	public function createUser($org_user, $new_obj = true)
	{
		$user = $this;
		if ($new_obj === true) {
			$user = Prado::createComponent(__CLASS__, $this->getManager());
		}

		$user->setUsername($org_user['user_id']);
		$user->setOrganization($org_user['org_id']);

		$application = $this->getManager()->getApplication();
		$user_config = $application->getModule('user_config');
		$user_cfg = $user_config->getUserConfig(
			$org_user['org_id'],
			$org_user['user_id']
		);
		$web_config = $application->getModule('web_config')->getConfig();
		$host_groups = $application->getModule('host_group_config');

		if (count($user_cfg) > 0) {
			// User exists in Bacularis Web users database
			$user->setInConfig(true);
			$user->setDescription($user_cfg['description']);
			$user->setLongName($user_cfg['long_name']);
			$user->setEmail($user_cfg['email']);
			$user->setRoles($user_cfg['roles']);
			$user->setAPIHostMethod($user_cfg['api_hosts_method']);
			if ($user_cfg['api_hosts_method'] === WebUserConfig::API_HOST_METHOD_HOSTS) {
				$user->setAPIHosts($user_cfg['api_hosts']);
			} elseif ($user_cfg['api_hosts_method'] === WebUserConfig::API_HOST_METHOD_HOST_GROUPS) {
				$api_hosts = $host_groups->getAPIHostsByGroups($user_cfg['api_host_groups']);
				$user->setAPIHosts($api_hosts);
			}
			$user->setIps($user_cfg['ips']);
			$user->setEnabled($user_cfg['enabled']);
		} elseif (isset($web_config['security']['def_access'])) {
			// User doesn't exist. Check if user can have access.
			$user->setInConfig(false);
			if ($web_config['security']['def_access'] === WebConfig::DEF_ACCESS_NO_ACCESS) {
				// no access, nothing to do
			} elseif ($web_config['security']['def_access'] === WebConfig::DEF_ACCESS_DEFAULT_SETTINGS) {
				// access with default settings
				if (isset($web_config['security']['def_role'])) {
					$user->setRoles($web_config['security']['def_role']);
				}
				if (isset($web_config['security']['def_api_host'])) {
					$user->setAPIHosts($web_config['security']['def_api_host']);
				}
			} elseif ($web_config['security']['def_access'] === WebConfig::DEF_ACCESS_PROVISION_USER) {
				//provision a new user
				$new_user_roles = $web_config['security']['new_user_role'] ?? '';
				$new_user_api_hosts = $web_config['security']['new_user_api_host'] ?? '';
				$new_user_organization_id = $web_config['security']['new_user_organization_id'] ?? '';
				$result = $this->provisionUser(
					$new_user_organization_id,
					$org_user['user_id'],
					$new_user_roles,
					$new_user_api_hosts
				);
				if ($result) {
					if ($new_user_roles) {
						$user->setRoles($new_user_roles);
					}
					if ($new_user_api_hosts) {
						$user->setAPIHosts($new_user_api_hosts);
					}
					if ($new_user_organization_id) {
						$user->setOrganization($new_user_organization_id);
					}
				}
			}
		}
		return $user;
	}

	/**
	 * Provision new user.
	 *
	 * @param string $org_id organization identifier
	 * @param string $user_id user identifier
	 * @param array $roles user roles
	 * @param array $api_hosts user API hosts
	 * @return bool true on success, false otherwise
	 */
	private function provisionUser(string $org_id, string $user_id, array $roles, array $api_hosts): bool
	{
		$application = $this->getManager()->getApplication();
		$user_config = $application->getModule('user_config');
		$new_user_prop = $user_config->getUserConfigProps([
			'username' => $user_id,
			'organization_id' => $org_id,
			'roles' => implode(',', $roles),
			'api_hosts' => $api_hosts,
			'enabled' => 1
		]);
		$ret = $user_config->setUserConfig(
			$org_id,
			$user_id,
			$new_user_prop
		);
		if ($ret) {
			$msg = "New provisioned user. Org: '{$org_id}', User: '{$user_id}'.";
			$application->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$msg
			);
		} else {
			$emsg = "Error while provisioning new user. Org: '{$org_id}' User: '{$user_id}'.";
			$application->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				$emsg
			);
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
		return $ret;
	}

	/**
	 * Username setter.
	 *
	 * @param string $username user name
	 */
	public function setUsername($username)
	{
		$this->setName($username);
	}

	/**
	 * Username getter.
	 *
	 * @return string user name
	 */
	public function getUsername()
	{
		return $this->getName();
	}

	/**
	 * Long name setter.
	 *
	 * @param string $long_name long name
	 */
	public function setLongName($long_name)
	{
		$this->setCurrentState(self::LONG_NAME, $long_name);
	}

	/**
	 * Long name getter.
	 *
	 * @return string long name (default empty string)
	 */
	public function getLongName()
	{
		return $this->getCurrentState(self::LONG_NAME, '');
	}

	/**
	 * E-mail address setter.
	 *
	 * @param string $email e-mail address
	 */
	public function setEmail($email)
	{
		$this->setCurrentState(self::EMAIL, $email);
	}

	/**
	 * E-mail address getter.
	 *
	 * @return string e-mail address
	 */
	public function getEmail()
	{
		return $this->getCurrentState(self::EMAIL, '');
	}

	/**
	 * Description setter.
	 *
	 * @param string $desc description
	 */
	public function setDescription($desc)
	{
		$this->setCurrentState(self::DESCRIPTION, $desc);
	}

	/**
	 * Description getter.
	 *
	 * @return string description
	 */
	public function getDescription()
	{
		return $this->getCurrentState(self::DESCRIPTION, '');
	}

	/**
	 * Get user roles.
	 *
	 * @return array role list assigned to user
	 */
	public function getRoles()
	{
		return $this->getCurrentState('Roles', []);
	}

	/**
	 * Set user roles.
	 *
	 * @param mixed $value roles assigned to user
	 */
	public function setRoles($value)
	{
		if (is_array($value)) {
			$this->setCurrentState('Roles', $value, []);
		} else {
			$roles = [];
			foreach (explode(',', $value) as $role) {
				if (($role = trim($role)) !== '') {
					$roles[] = $role;
				}
			}
			$this->setCurrentState('Roles', $roles, []);
		}
	}

	/**
	 * API host method setter.
	 *
	 * @param string $method API host method
	 */
	public function setAPIHostMethod($method)
	{
		$this->setCurrentState(self::API_HOST_METHOD, $method);
	}

	/**
	 * API host method getter.
	 *
	 * @return string API host method
	 */
	public function getAPIHostMethod()
	{
		return $this->getCurrentState(
			self::API_HOST_METHOD,
			WebUserConfig::API_HOST_METHOD_HOSTS
		);
	}

	/**
	 * Set API hosts.
	 *
	 * @param array $api_hosts user API hosts
	 */
	public function setAPIHosts($api_hosts)
	{
		$this->setCurrentState(self::API_HOSTS, $api_hosts);
	}

	/**
	 * API hosts getter.
	 *
	 * @return array user API hosts
	 */
	public function getAPIHosts()
	{
		$api_hosts = [];
		$hosts = $this->getCurrentState(self::API_HOSTS);
		/**
		 * This checking is for backward compatibility because previously
		 * hosts were written in session as string. Now it is written as array.
		 */
		if (is_string($hosts)) {
			if (!empty($hosts)) {
				$hosts = explode(',', $hosts);
			} else {
				$hosts = [];
			}
		} elseif (is_null($hosts)) {
			$hosts = [];
		}

		if (count($hosts) > 0) {
			$api_hosts = $hosts;
		} else {
			// add default API host
			$api_hosts[] = HostConfig::MAIN_CATALOG_HOST;
		}
		return $api_hosts;
	}

	/**
	 * Set default API host for user.
	 * It determines which host will be used as default API host to login
	 * to Bacularis Web interface. This host needs to have at least the catalog
	 * and the console capabilities.
	 *
	 * @param string $api_host default API host
	 */
	public function setDefaultAPIHost($api_host)
	{
		$this->setState(self::DEFAULT_API_HOST, $api_host);
		$application = $this->getManager()->getApplication();
		$web_session = $application->getModule('web_session');
		$web_session->updateSessionUser($this);
	}

	/**
	 * Get default API host for user.
	 * If default API host is not set, there happens a try to determine
	 * this host if user has only one API host assigned.
	 *
	 * @return null|string default API host or null if no default host set
	 */
	public function getDefaultAPIHost()
	{
		$def_host = $this->getState(self::DEFAULT_API_HOST);
		$api_hosts = $this->getAPIHosts();
		if ($def_host && !in_array($def_host, $api_hosts)) {
			// The default host is not longer assigned to user. Don't allow to use this host.
			$def_host = null;
			$this->setDefaultAPIHost(null); // delete default host
		}
		if (!$def_host) {
			if (count($api_hosts) == 1) {
				// only one host assigned, so use it as default host
				$def_host = $api_hosts[0];
				$this->setDefaultAPIHost($def_host);
			}
		}
		return $def_host;
	}

	/**
	 * Check if given API host belongs to user API hosts.
	 *
	 * @param string $api_host API host to check
	 * @return bool true if API host belongs to user API hosts, otherwise false
	 */
	public function isUserAPIHost($api_host)
	{
		$api_hosts = $this->getAPIHosts();
		return in_array($api_host, $api_hosts);
	}

	/**
	 * Organization setter.
	 *
	 * @param string $org_id organization identifier
	 */
	public function setOrganization(string $org_id): void
	{
		$this->setState(self::ORGANIZATION, $org_id);
		$application = $this->getManager()->getApplication();
		$web_session = $application->getModule('web_session');
		$web_session->updateSessionUser($this);
	}

	/**
	 * Organization getter.
	 *
	 * @return string organization identifier (default empty string)
	 */
	public function getOrganization(): string
	{
		return $this->getState(self::ORGANIZATION, '');
	}

	/**
	 * IP address restriction setter.
	 *
	 * @param string $ips comma separated IP addresses
	 */
	public function setIps($ips)
	{
		$this->setCurrentState(self::IPS, $ips);
	}

	/**
	 * IP address restriction getter.
	 *
	 * @return string comma separated IP address list (default empty string)
	 */
	public function getIps()
	{
		return $this->getCurrentState(self::IPS, '');
	}

	/**
	 * Enabled setter
	 *
	 * @param bool $enabled enabled flag state
	 */
	public function setEnabled($enabled)
	{
		$enabled = TPropertyValue::ensureBoolean($enabled);
		$this->setCurrentState(self::ENABLED, $enabled);
	}

	/**
	 * Enabled getter.
	 *
	 * @return string enabled flag state (default false)
	 */
	public function getEnabled()
	{
		return $this->getCurrentState(self::ENABLED, false);
	}

	/**
	 * Set if user exists in configuration file.
	 *
	 * @param bool $in_config in config state value
	 */
	public function setInConfig($in_config)
	{
		$in_config = TPropertyValue::ensureBoolean($in_config);
		$this->setCurrentState(self::IN_CONFIG, $in_config);
	}

	/**
	 * In config getter.
	 *
	 * @return string in config state value (default false)
	 */
	public function getInConfig()
	{
		return $this->getCurrentState(self::IN_CONFIG, false);
	}

	/**
	 * Set current state.
	 * Unlike user session (setState and getState), current state is not remembered.
	 * It is for storing user data only for current request.
	 *
	 * @param string $key state key
	 * @param mixed $value value to set
	 */
	private function setCurrentState($key, $value)
	{
		$this->current_state[$key] = $value;
	}

	/**
	 * Get current state.
	 *
	 * @param string $key state key
	 * @param mixed $default_value default value
	 * @return mixed state value or default value if state value does not exist
	 */
	private function getCurrentState($key, $default_value = null)
	{
		return key_exists($key, $this->current_state) ? $this->current_state[$key] : $default_value;
	}

	/**
	 * Load user information from session data.
	 * This method overloads parent method for adding user data from user config file.
	 *
	 * @param string $data user data
	 * @return IUser user object
	 */
	public function loadFromString($data)
	{
		parent::loadFromString($data);
		if ($this->getIsGuest() === false) {
			$user_id = $this->getName();
			$org_id = $this->getOrganization();
			$user = WebUserConfig::getOrgUser($org_id, $user_id);

			// Create user from config file data.
			$this->createUser($user, false);
		}
		return $this;
	}
}
