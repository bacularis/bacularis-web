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

use Prado\Prado;
use Prado\Web\UI\ActiveControls\TActiveCustomValidator;
use Prado\Web\UI\ActiveControls\TActiveDropDownList;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TCommandEventParameter;
use Bacularis\Common\Modules\AuthOAuth2;
use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Errors\BaculaConfigError;
use Bacularis\Common\Modules\Ldap;
use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\HostConfig;
use Bacularis\Web\Modules\HostGroupConfig;
use Bacularis\Web\Modules\JobInfo;
use Bacularis\Web\Modules\OAuth2Record;
use Bacularis\Web\Modules\WebConfig;
use Bacularis\Web\Modules\WebUserRoles;
use Bacularis\Web\Modules\WebUserConfig;

/**
 * Security page (auth methods, users, roles...).
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class Security extends BaculumWebPage
{
	/**
	 * Modal window types for users and roles.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Options for import users.
	 */
	public const IMPORT_OPT_ALL_USERS = 0;
	public const IMPORT_OPT_SELECTED_USERS = 1;
	public const IMPORT_OPT_CRITERIA = 2;

	/**
	 * Options for import criteria.
	 */
	public const IMPORT_CRIT_USERNAME = 0;
	public const IMPORT_CRIT_LONG_NAME = 1;
	public const IMPORT_CRIT_DESCRIPTION = 2;
	public const IMPORT_CRIT_EMAIL = 3;


	/**
	 * Store web user config.
	 */
	private $user_config = [];

	/**
	 * Store console ACL config.
	 */
	private $console_config = [];

	/**
	 * Initialize page.
	 *
	 * @param mixed $param oninit event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$this->initDefAccessForm();
		$this->initAuthForm();
		$this->initUserWindow();
		$this->initRoleWindow();
		$this->initAPIHostGroupWindow();
		$this->setBasicAuthConfig();
	}

	/**
	 * Initialize form with default access settings.
	 *
	 */
	public function initDefAccessForm()
	{
		$this->setRoles(
			$this->GeneralDefaultAccessRole,
			WebUserRoles::NORMAL
		);

		$this->setAPIHosts(
			$this->GeneralDefaultAccessAPIHost,
			HostConfig::MAIN_CATALOG_HOST
		);
		if (isset($this->web_config['security']['def_access'])) {
			if ($this->web_config['security']['def_access'] === WebConfig::DEF_ACCESS_NO_ACCESS) {
				$this->GeneralDefaultNoAccess->Checked = true;
			} elseif ($this->web_config['security']['def_access'] === WebConfig::DEF_ACCESS_DEFAULT_SETTINGS) {
				$this->GeneralDefaultAccess->Checked = true;
			}
			if (isset($this->web_config['security']['def_role'])) {
				$this->GeneralDefaultAccessRole->SelectedValue = $this->web_config['security']['def_role'];
			}
			if (isset($this->web_config['security']['def_api_host'])) {
				$this->GeneralDefaultAccessAPIHost->SelectedValue = $this->web_config['security']['def_api_host'];
			}
		} else {
			$this->GeneralDefaultAccess->Checked = true;
		}
	}

	/**
	 * Initialize form with authentication method settings.
	 *
	 */
	public function initAuthForm()
	{
		if (isset($this->web_config['security']['auth_method'])) {
			if ($this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_LOCAL) {
				$this->LocalAuth->Checked = true;
			} elseif ($this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_BASIC) {
				$this->BasicAuth->Checked = true;
			} elseif ($this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_LDAP) {
				$this->LdapAuth->Checked = true;
			}

			// Fill LDAP auth fileds
			if (key_exists('auth_ldap', $this->web_config)) {
				$this->LdapAuthServerAddress->Text = $this->web_config['auth_ldap']['address'];
				$this->LdapAuthServerPort->Text = $this->web_config['auth_ldap']['port'];
				$this->LdapAuthServerLdaps->Checked = ($this->web_config['auth_ldap']['ldaps'] == 1);
				$this->LdapAuthServerStartTLS->Checked = (key_exists('starttls', $this->web_config['auth_ldap']) && $this->web_config['auth_ldap']['starttls'] == 1);
				$this->LdapAuthServerProtocolVersion->Text = $this->web_config['auth_ldap']['protocol_ver'];
				if ($this->web_config['auth_ldap']['auth_method'] === Ldap::AUTH_METHOD_ANON) {
					$this->LdapAuthMethodAnonymous->Checked = true;
				} elseif ($this->web_config['auth_ldap']['auth_method'] === Ldap::AUTH_METHOD_SIMPLE) {
					$this->LdapAuthMethodSimple->Checked = true;
				}
				$this->LdapAuthMethodSimpleUsername->Text = $this->web_config['auth_ldap']['bind_dn'];
				$this->LdapAuthMethodSimplePassword->Text = $this->web_config['auth_ldap']['bind_password'];
				$this->LdapAuthServerBaseDn->Text = $this->web_config['auth_ldap']['base_dn'];
				$this->LdapAuthServerFilters->Text = $this->web_config['auth_ldap']['filters'] ?? '';
				$this->LdapAttributesUsername->Text = $this->web_config['auth_ldap']['user_attr'];
				$this->LdapAttributesLongName->Text = $this->web_config['auth_ldap']['long_name_attr'];
				$this->LdapAttributesEmail->Text = $this->web_config['auth_ldap']['email_attr'];
				$this->LdapAttributesDescription->Text = $this->web_config['auth_ldap']['desc_attr'];
			}
			// Fill Basic auth fields
			if (key_exists('auth_basic', $this->web_config)) {
				$this->BasicAuthAllowManageUsers->Checked = ($this->web_config['auth_basic']['allow_manage_users'] == 1);
				$this->BasicAuthUserFile->Text = $this->web_config['auth_basic']['user_file'];
				$this->BasicAuthHashAlgorithm->SelectedValue = $this->web_config['auth_basic']['hash_alg'];
			}
		} else {
			// Default set to Basic auth method
			$this->BasicAuth->Checked = true;
		}
	}

	/**
	 * Initialize values in user modal window.
	 *
	 */
	public function initUserWindow()
	{
		// set API hosts
		$this->setAPIHosts($this->UserAPIHosts, null, false);

		// set API host groups
		$this->setAPIHostGroups($this->UserAPIHostGroups);
		$this->setAPIHostGroups($this->APIHostGroups);

		// set roles
		$this->setRoles($this->UserRoles);
	}

	/**
	 * Set role list control.
	 *
	 * @param object $control control which contains role list
	 * @param mixed $def_val default value or null if no default value to set
	 */
	private function setRoles($control, $def_val = null)
	{
		// set roles
		$roles = $this->getModule('user_role')->getRoles();
		$role_items = [];
		foreach ($roles as $role_name => $role) {
			$role_items[$role_name] = $role['long_name'] ?: $role_name;
		}
		uasort($role_items, 'strnatcasecmp');
		$control->DataSource = $role_items;
		if ($def_val) {
			$control->SelectedValue = $def_val;
		}
		$control->dataBind();
	}

	/**
	 * Set API host list control.
	 *
	 * @param object $control control which contains API host list
	 * @param mixed $def_val default value or null if no default value to set
	 * @param bool $add_blank_item determines if add first blank item
	 * @param null|array $sel_api_hosts defines selected list of API hosts to set. If not set, all API hosts are taken
	 */
	private function setAPIHosts($control, $def_val = null, $add_blank_item = true, $sel_api_hosts = null)
	{
		$api_hosts = [];
		if (is_array($sel_api_hosts)) {
			$api_hosts = $sel_api_hosts;
		} else {
			$host_config = $this->getModule('host_config')->getConfig();
			$api_hosts = array_keys($host_config);
		}
		if ($add_blank_item) {
			array_unshift($api_hosts, '');
		}
		natcasesort($api_hosts);
		$control->DataSource = array_combine($api_hosts, $api_hosts);
		if ($def_val) {
			$control->SelectedValue = $def_val;
		}
		$control->dataBind();
	}

	/**
	 * Set API host group list control.
	 *
	 * @param object $control control which contains API host group list
	 * @param mixed $def_val default value or null if no default value to set
	 * @param mixed $add_blank_item
	 */
	private function setAPIHostGroups($control, $def_val = null)
	{
		$api_host_groups = array_keys($this->getModule('host_group_config')->getConfig());
		natcasesort($api_host_groups);
		$control->DataSource = array_combine($api_host_groups, $api_host_groups);
		if ($def_val) {
			$control->SelectedValue = $def_val;
		}
		$control->dataBind();
	}

	/**
	 * Set and load user list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setUserList($sender, $param)
	{
		$config = $this->getModule('user_config')->getConfig();
		$this->getCallbackClient()->callClientFunction('oUsers.load_user_list_cb', [
			array_values($config)
		]);
		$this->user_config = $config;
	}

	/**
	 * Load data in user modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadUserWindow($sender, $param)
	{
		//$this->getModule('user_config')->importBasicUsers();
		$cb = $this->getCallbackClient();
		$username = $param->getCallbackParameter();
		$config = $this->getModule('user_config')->getUserConfig($username);
		if (count($config) > 0) {
			// It is done only for existing users
			$this->UserName->Text = $config['username'];
			$this->UserLongName->Text = $config['long_name'];
			$this->UserDescription->Text = $config['description'];
			$this->UserEmail->Text = $config['email'];
			$this->UserPassword->Text = '';

			// set roles
			$selected_indices = [];
			$roles = explode(',', $config['roles']);
			for ($i = 0; $i < $this->UserRoles->getItemCount(); $i++) {
				if (in_array($this->UserRoles->Items[$i]->Value, $roles)) {
					$selected_indices[] = $i;
				}
			}
			$this->UserRoles->setSelectedIndices($selected_indices);

			$selected_indices = [];
			$api_hosts = $config['api_hosts'];
			for ($i = 0; $i < $this->UserAPIHosts->getItemCount(); $i++) {
				if (in_array($this->UserAPIHosts->Items[$i]->Value, $api_hosts)) {
					$selected_indices[] = $i;
				}
			}

			$this->UserAPIHosts->setSelectedIndices($selected_indices);

			$selected_indices = [];
			$api_host_groups = $config['api_host_groups'];
			for ($i = 0; $i < $this->UserAPIHostGroups->getItemCount(); $i++) {
				if (in_array($this->UserAPIHostGroups->Items[$i]->Value, $api_host_groups)) {
					$selected_indices[] = $i;
				}
			}

			$this->UserAPIHostGroups->setSelectedIndices($selected_indices);

			if ($config['api_hosts_method'] === WebUserConfig::API_HOST_METHOD_HOSTS) {
				$this->UserAPIHostsOpt->Checked = true;
				$this->UserAPIHostGroupsOpt->Checked = false;
				$cb->show('user_window_api_hosts');
				$cb->hide('user_window_api_host_groups');
			} elseif ($config['api_hosts_method'] === WebUserConfig::API_HOST_METHOD_HOST_GROUPS) {
				$this->UserAPIHostsOpt->Checked = false;
				$this->UserAPIHostGroupsOpt->Checked = true;
				$cb->hide('user_window_api_hosts');
				$cb->show('user_window_api_host_groups');
			}
			$this->UserIps->Text = $config['ips'];
			$this->UserEnabled->Checked = ($config['enabled'] == 1);
		}

		// It is done both for new user and for edit user
		if ($this->isManageUsersAvail()) {
			$cb->show('user_window_password');
		} else {
			$cb->hide('user_window_password');
		}
	}

	/**
	 * Save user.
	 * It works both for new users and for edited users.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveUser($sender, $param)
	{
		if (!$this->UserIps->IsValid) {
			// invalid IP restriction value
			return;
		}
		$user_win_type = $this->UserWindowType->Value;
		$username = $this->UserName->Text;
		$config = $this->getModule('user_config')->getUserConfig($username);
		$this->getCallbackClient()->hide('user_window_username_exists');
		if ($user_win_type === self::TYPE_ADD_WINDOW) {
			if (count($config) > 0) {
				$this->getCallbackClient()->show('user_window_username_exists');
				return;
			}
		}

		$config['username'] = $username;
		$config['long_name'] = $this->UserLongName->Text;
		$config['description'] = $this->UserDescription->Text;
		$config['email'] = $this->UserEmail->Text;

		// set roles config values
		$selected_indices = $this->UserRoles->getSelectedIndices();
		$roles = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->UserRoles->getItemCount(); $i++) {
				if ($i === $indice) {
					$roles[] = $this->UserRoles->Items[$i]->Value;
				}
			}
		}
		$config['roles'] = implode(',', $roles);

		$config['api_hosts_method'] = WebUserConfig::API_HOST_METHOD_HOSTS;
		if ($this->UserAPIHostsOpt->Checked) {
			$config['api_hosts_method'] = WebUserConfig::API_HOST_METHOD_HOSTS;
		} elseif ($this->UserAPIHostGroupsOpt->Checked) {
			$config['api_hosts_method'] = WebUserConfig::API_HOST_METHOD_HOST_GROUPS;
		}
		// set API hosts config values
		$selected_indices = $this->UserAPIHosts->getSelectedIndices();
		$api_hosts = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->UserAPIHosts->getItemCount(); $i++) {
				if ($i === $indice) {
					$api_hosts[] = $this->UserAPIHosts->Items[$i]->Value;
				}
			}
		}
		$config['api_hosts'] = $api_hosts;

		// set API host groups config values
		$selected_indices = $this->UserAPIHostGroups->getSelectedIndices();
		$api_host_groups = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->UserAPIHostGroups->getItemCount(); $i++) {
				if ($i === $indice) {
					$api_host_groups[] = $this->UserAPIHostGroups->Items[$i]->Value;
				}
			}
		}
		$config['api_host_groups'] = $api_host_groups;

		$config['ips'] = $this->trimIps($this->UserIps->Text);
		$config['enabled'] = $this->UserEnabled->Checked ? 1 : 0;
		$result = $this->getModule('user_config')->setUserConfig($username, $config);

		// Set password if auth method supports it
		if ($result === true && !empty($this->UserPassword->Text) && $this->isManageUsersAvail()) {
			$basic = $this->getModule('basic_webuser');
			if ($this->getModule('web_config')->isAuthMethodLocal()) {
				$basic->setUsersConfig(
					$username,
					$this->UserPassword->Text
				);
			} elseif ($this->getModule('web_config')->isAuthMethodBasic() &&
				isset($this->web_config['auth_basic']['user_file'])) { // Set Basic auth users password
				$opts = [];
				if (isset($this->web_config['auth_basic']['hash_alg'])) {
					$opts['hash_alg'] = $this->web_config['auth_basic']['hash_alg'];
				}

				// Setting basic users works both for adding and editing users
				$basic->setUsersConfig(
					$username,
					$this->UserPassword->Text,
					false,
					null,
					$opts
				);
			}
		}

		if ($result === true) {
			$amsg = '';
			if ($user_win_type == self::TYPE_ADD_WINDOW) {
				$amsg = "Create Bacularis user. User: $username";
			} elseif ($user_win_type == self::TYPE_EDIT_WINDOW) {
				$amsg = "Save Bacularis user. User: $username";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}

		$this->setUserList(null, null);
		$this->setRoleList(null, null);
		$this->getCallbackClient()->callClientFunction('oUsers.save_user_cb');
	}

	/**
	 * Remove users action.
	 * Here is possible to remove one user or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeUsers($sender, $param)
	{
		$usernames = explode('|', $param->getCallbackParameter());
		$config = $this->getModule('user_config')->getConfig();
		for ($i = 0; $i < count($usernames); $i++) {
			if (key_exists($usernames[$i], $config)) {
				unset($config[$usernames[$i]]);
			}
		}
		$result = $this->getModule('user_config')->setConfig($config);

		$wcm = $this->getModule('web_config');
		if ($result === true &&
			(($this->isManageUsersAvail() && $wcm->isAuthMethodBasic() && isset($this->web_config['auth_basic']['user_file'])) ||
			$wcm->isAuthMethodLocal())) {
			// Remove basic auth users too
			$basic = $this->getModule('basic_webuser');
			$basic->removeUsers($usernames);
		}

		if ($result === true) {
			for ($i = 0; $i < count($usernames); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove Bacularis user. User: {$usernames[$i]}"
				);
			}
		}

		// refresh user list
		$this->setUserList(null, null);

		// refresh role list
		$this->setRoleList(null, null);
	}

	/**
	 * Initialize values in role modal window.
	 *
	 */
	public function initRoleWindow()
	{
		// set role resources
		$resources = $this->getModule('page_category')->getCategories(false);
		natcasesort($resources);
		$this->RoleResources->DataSource = array_combine($resources, $resources);
		$this->RoleResources->dataBind();
	}

	/**
	 * Set and load role list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setRoleList($sender, $param)
	{
		$config = $this->getModule('user_role')->getRoles();
		$this->addUserStatsToRoles($config);
		$this->getCallbackClient()->callClientFunction('oRoles.load_role_list_cb', [
			array_values($config)
		]);
	}

	/**
	 * Load data in role modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadRoleWindow($sender, $param)
	{
		$role = $param->getCallbackParameter();
		$config = $this->getModule('user_role')->getRole($role);
		if (count($config) > 0) {
			// Edit role window
			$this->Role->Text = $config['role'];
			$this->RoleLongName->Text = $config['long_name'];
			$this->RoleDescription->Text = $config['description'];
			$selected_indices = [];
			$resources = explode(',', $config['resources']);
			for ($i = 0; $i < $this->RoleResources->getItemCount(); $i++) {
				if (in_array($this->RoleResources->Items[$i]->Value, $resources)) {
					$selected_indices[] = $i;
				}
			}
			$this->RoleResources->setSelectedIndices($selected_indices);
			$this->RoleEnabled->Checked = ($config['enabled'] == 1);
			if ($this->getModule('user_role')->isRolePreDefined($role)) {
				$this->RoleSave->Display = 'None';
				$this->PreDefinedRoleMsg->Display = 'Dynamic';
			} else {
				$this->RoleSave->Display = 'Dynamic';
				$this->PreDefinedRoleMsg->Display = 'None';
			}
		} else {
			// New role window
			$this->RoleSave->Display = 'Dynamic';
			$this->PreDefinedRoleMsg->Display = 'None';
		}
	}

	/**
	 * Save role.
	 * It works both for new roles and for edited roles.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRole($sender, $param)
	{
		$role_win_type = $this->RoleWindowType->Value;
		$role = $this->Role->Text;
		$this->getCallbackClient()->hide('role_window_role_exists');
		if ($role_win_type === self::TYPE_ADD_WINDOW) {
			$config = $this->getModule('user_role')->getRole($role);
			if (count($config) > 0) {
				$this->getCallbackClient()->show('role_window_role_exists');
				return;
			}
		}
		if ($this->getModule('user_role')->isRolePreDefined($role)) {
			// Predefined roles cannot be saved
			return;
		}
		$config = [];
		$config['long_name'] = $this->RoleLongName->Text;
		$config['description'] = $this->RoleDescription->Text;

		$selected_indices = $this->RoleResources->getSelectedIndices();
		$resources = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->RoleResources->getItemCount(); $i++) {
				if ($i === $indice) {
					$resources[] = $this->RoleResources->Items[$i]->Value;
				}
			}
		}
		$config['resources'] = implode(',', $resources);
		$config['enabled'] = $this->RoleEnabled->Checked ? 1 : 0;
		$result = $this->getModule('role_config')->setRoleConfig($role, $config);
		$this->setRoleList(null, null);
		if ($role_win_type === self::TYPE_ADD_WINDOW) {
			// refresh user window for new role
			$this->initUserWindow();
		}
		$this->getCallbackClient()->callClientFunction('oRoles.save_role_cb');

		if ($result === true) {
			$amsg = '';
			if ($role_win_type == self::TYPE_ADD_WINDOW) {
				$amsg = "Create role. Role: $role";
			} elseif ($role_win_type == self::TYPE_EDIT_WINDOW) {
				$amsg = "Save role. Role: $role";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}
	}

	/**
	 * Remove roles action.
	 * Here is possible to remove one role or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeRoles($sender, $param)
	{
		$roles = explode('|', $param->getCallbackParameter());
		$config = $this->getModule('role_config')->getConfig();
		$user_role = $this->getModule('user_role');
		for ($i = 0; $i < count($roles); $i++) {
			if (key_exists($roles[$i], $config)) {
				if ($user_role->isRolePreDefined($roles[$i])) {
					// Predefined roles cannot be saved
					continue;
				}
				unset($config[$roles[$i]]);
			}
		}
		$result = $this->getModule('role_config')->setConfig($config);
		$this->setRoleList(null, null);
		// refresh user window to now show removed roles
		$this->initUserWindow();
		if ($result === true) {
			for ($i = 0; $i < count($roles); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove role. Role: {$roles[$i]}"
				);
			}
		}
	}

	/**
	 * Add user statistics to roles.
	 * It adds user count to information about roles.
	 *
	 * @param array $role_config role config (note, passing by reference)
	 */
	private function addUserStatsToRoles(&$role_config)
	{
		$config = [];
		if (count($this->user_config) > 0) {
			$config = $this->user_config;
		} else {
			$config = $this->getModule('user_config')->getConfig();
		}
		$user_roles = [];
		foreach ($role_config as $role => $prop) {
			$user_roles[$role] = 0;
		}
		foreach ($config as $username => $prop) {
			$roles = explode(',', $prop['roles']);
			for ($i = 0; $i < count($roles); $i++) {
				$user_roles[$roles[$i]]++;
			}
		}
		foreach ($role_config as $role => $prop) {
			$role_config[$role]['user_count'] = $user_roles[$role];
		}
	}

	/**
	 * Set basic authentication user file.
	 *
	 */
	private function setBasicAuthConfig()
	{
		$is_basic = $this->getModule('web_config')->isAuthMethodBasic();
		if ($is_basic && $this->isManageUsersAvail() && isset($this->web_config['auth_basic']['user_file'])) {
			$this->getModule('basic_webuser')->setConfigPath($this->web_config['auth_basic']['user_file']);
		}
	}

	/**
	 * Get basic users and provide them to template.
	 *
	 * @param TActiveLinkButton $sender sender
	 * @param TCommandEventParameter $param event parameter object
	 */
	public function getBasicUsers($sender, $param)
	{
		if ($param instanceof TCommandEventParameter && $param->getCommandParameter() === 'load') {
			// reset criteria filters when modal is open
			$this->GetUsersImportOptions->SelectedValue = self::IMPORT_OPT_ALL_USERS;
			$this->GetUsersCriteria->SelectedValue = self::IMPORT_CRIT_USERNAME;
			$this->GetUsersCriteriaFilter->Text = '';
			$this->getCallbackClient()->hide('get_users_criteria');
			$this->getCallbackClient()->hide('get_users_advanced_options');

			// set role resources
			$this->setRoles($this->GetUsersDefaultRole, WebUserRoles::NORMAL);

			// set API hosts
			$this->setAPIHosts($this->GetUsersDefaultAPIHosts, HostConfig::MAIN_CATALOG_HOST, false);

			// set API host groups
			$this->setAPIHostGroups($this->GetUsersDefaultAPIHostGroups);
		}

		$params = $this->getBasicParams();

		// add additional parameters
		$this->addBasicExtraParams($params);

		$pattern = '';
		if (!empty($params['filter_val'])) {
			$pattern = '*' . $params['filter_val'] . '*';
		}

		$basic = $this->getModule('basic_webuser');
		// set path from input because user can have unsaved changes
		$basic->setConfigPath($this->BasicAuthUserFile->Text);
		$users = $basic->getUsers($pattern);
		$users = array_keys($users);
		$user_list = $this->convertBasicUsers($users);
		$this->getCallbackClient()->callClientFunction('oUserSecurity.set_user_table_cb', [
			$user_list
		]);
		if (count($users) > 0) {
			// Success
			$this->TestBasicGetUsersMsg->Text = '';
			$this->TestBasicGetUsersMsg->Display = 'None';
			$this->getCallbackClient()->hide('basic_get_users_error');
			$this->getCallbackClient()->show('basic_get_users_ok');
		} else {
			// Error
			$this->getCallbackClient()->show('basic_get_users_error');
			$this->TestBasicGetUsersMsg->Text = Prado::localize('Empty user list');
			$this->TestBasicGetUsersMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Convert basic users from simple username list into full form.
	 * There is option to return user list in config file form or data table form.
	 *
	 * @param array $users simple user list
	 * @param bool $config_form_result if true, sets the list in config file form
	 * @return array user list
	 */
	private function convertBasicUsers(array $users, $config_form_result = false)
	{
		$user_list = [];
		for ($i = 0; $i < count($users); $i++) {
			$user = [
				'username' => $users[$i],
				'long_name' => '',
				'email' => '',
				'description' => ''
			];
			if ($config_form_result) {
				$user_list[$users[$i]] = $user;
			} else {
				$user_list[] = $user;
			}
		}
		return $user_list;
	}

	/**
	 * Get basic auth specific parameters with form values.
	 *
	 * @return array array basic auth parameters
	 */
	private function getBasicParams()
	{
		$params = [];
		$params['allow_manage_users'] = $this->BasicAuthAllowManageUsers->Checked ? 1 : 0;
		$params['user_file'] = $this->BasicAuthUserFile->Text;
		$params['hash_alg'] = $this->BasicAuthHashAlgorithm->SelectedValue;
		return $params;
	}

	/**
	 * Add to basic auth params additional parameters.
	 * Note, extra parameters are not set in config.
	 *
	 * @param array $params basic auth parameters (passing by reference)
	 */
	private function addBasicExtraParams(&$params)
	{
		if ($this->GetUsersImportOptions->SelectedValue == self::IMPORT_OPT_CRITERIA) {
			$params['filter_val'] = $this->GetUsersCriteriaFilter->Text;
		}
	}

	/**
	 * Prepare basic users to import.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter object
	 * @return array web users list to import
	 */
	public function prepareBasicUsers($sender, $param)
	{
		$users_web = [];
		$import_opt = (int) $this->GetUsersImportOptions->SelectedValue;
		$basic_webuser = $this->getModule('basic_webuser');
		switch ($import_opt) {
			case self::IMPORT_OPT_ALL_USERS: {
				$users_web = $basic_webuser->getUsers();
				$users_web = array_keys($users_web);
				$users_web = $this->convertBasicUsers($users_web, true);
				break;
			}
			case self::IMPORT_OPT_SELECTED_USERS: {
				if ($param instanceof TCallbackEventParameter) {
					$cb_param = $param->getCallbackParameter();
					if (is_array($cb_param)) {
						for ($i = 0; $i < count($cb_param); $i++) {
							$val = (array) $cb_param[$i];
							$users_web[$val['username']] = $val;
						}
					}
				}
				break;
			}
			case self::IMPORT_OPT_CRITERIA: {
				$params = $this->getBasicParams();
				// add additional parameters
				$this->addBasicExtraParams($params);
				if (!empty($params['filter_val'])) {
					$pattern = '*' . $params['filter_val'] . '*';
					$users_web = $basic_webuser->getUsers($pattern);
					$users_web = array_keys($users_web);
					$users_web = $this->convertBasicUsers($users_web, true);
				}
				break;
			}
		}
		return $users_web;
	}

	/**
	 * Test basic user file.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter object
	 */
	public function doBasicUserFileTest($sender, $param)
	{
		$user_file = $this->BasicAuthUserFile->Text;
		$msg = '';
		$valid = true;
		if (!file_exists($user_file)) {
			$valid = false;
			$msg = Prado::localize('The user file is not accessible.');
		} elseif (!is_readable($user_file)) {
			$valid = false;
			$msg = Prado::localize('The user file is not readable by web server user.');
		} elseif (!is_writeable($user_file)) {
			$valid = false;
			$msg = Prado::localize('The user file is readable but not writeable by web server user.');
		}
		$this->BasicAuthUserFileMsg->Text = $msg;
		if ($valid) {
			$this->getCallbackClient()->show('basic_auth_user_file_test_ok');
			$this->BasicAuthUserFileMsg->Display = 'None';
		} else {
			$this->getCallbackClient()->show('basic_auth_user_file_test_error');
			$this->BasicAuthUserFileMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Get LDAP users and provide them to template.
	 *
	 * @param TActiveLinkButton $sender sender
	 * @param TCommandEventParameter $param event parameter object
	 */
	public function getLdapUsers($sender, $param)
	{
		if ($param instanceof TCommandEventParameter && $param->getCommandParameter() === 'load') {
			// reset criteria filters when modal is open
			$this->GetUsersImportOptions->SelectedValue = self::IMPORT_OPT_ALL_USERS;
			$this->GetUsersCriteria->SelectedValue = self::IMPORT_CRIT_USERNAME;
			$this->GetUsersCriteriaFilter->Text = '';
			$this->getCallbackClient()->hide('get_users_criteria');
			$this->getCallbackClient()->hide('get_users_advanced_options');

			// set role resources
			$this->setRoles($this->GetUsersDefaultRole, WebUserRoles::NORMAL);

			// set API hosts
			$this->setAPIHosts($this->GetUsersDefaultAPIHosts, HostConfig::MAIN_CATALOG_HOST, false);

			// set API host groups
			$this->setAPIHostGroups($this->GetUsersDefaultAPIHostGroups);
		}

		$ldap = $this->getModule('ldap');
		$params = $this->getLdapParams();
		$ldap->setParams($params);

		// add additional parameters
		$this->addLdapExtraParams($params);

		$filter = $ldap->getFilter($params['user_attr'], '*');
		if (!empty($params['filter_attr']) && !empty($params['filter_val'])) {
			$filter = $ldap->getFilter(
				$params['filter_attr'],
				'*' . $params['filter_val'] . '*'
			);
		}

		$users = $ldap->findUserAttr($filter, $params['attrs']);
		$user_list = $this->convertLdapUsers($users, $params);
		$this->getCallbackClient()->callClientFunction('oUserSecurity.set_user_table_cb', [
			$user_list
		]);

		if (key_exists('count', $users)) {
			// Success
			$this->TestLdapGetUsersMsg->Text = '';
			$this->TestLdapGetUsersMsg->Display = 'None';
			$this->getCallbackClient()->show('ldap_get_users_ok');
		} else {
			// Error
			$this->getCallbackClient()->show('ldap_get_users_error');
			$this->TestLdapGetUsersMsg->Text = $ldap->getLdapError();
			$this->TestLdapGetUsersMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Convert LDAP users from simple username list into full form.
	 * There is option to return user list in config file form or data table form.
	 *
	 * @param array $users simple user list
	 * @param array $params LDAP specific parameters (@see getLdapParams)
	 * @param bool $config_form_result if true, sets the list in config file form
	 * @return array user list
	 */
	private function convertLdapUsers(array $users, array $params, $config_form_result = false)
	{
		$user_list = [];
		if (!key_exists('count', $users)) {
			return $user_list;
		}
		for ($i = 0; $i < $users['count']; $i++) {
			if (!key_exists($params['user_attr'], $users[$i])) {
				$emsg = "User attribute '{$params['user_attr']}' doesn't exist in LDAP response.";
				Logging::log(
					Logging::CATEGORY_EXTERNAL,
					$emsg
				);
				continue;
			}
			$username = $long_name = $email = $desc = '';
			if ($params['user_attr'] !== Ldap::DN_ATTR && $users[$i][$params['user_attr']]['count'] != 1) {
				$emsg = "Invalid user attribute count for '{$params['user_attr']}'. Is {$users[$i][$params['user_attr']]['count']}, should be 1.";
				Logging::log(
					Logging::CATEGORY_EXTERNAL,
					$emsg
				);
				continue;
			}
			$username = $users[$i][$params['user_attr']];
			if ($params['user_attr'] !== Ldap::DN_ATTR) {
				$username = $users[$i][$params['user_attr']][0];
			}

			if (key_exists($params['long_name_attr'], $users[$i])) {
				if ($params['long_name_attr'] === Ldap::DN_ATTR) {
					$long_name = $users[$i][$params['long_name_attr']];
				} elseif ($users[$i][$params['long_name_attr']]['count'] === 1) {
					$long_name = $users[$i][$params['long_name_attr']][0];
				}
			}

			if (key_exists($params['email_attr'], $users[$i])) {
				if ($params['email_attr'] === Ldap::DN_ATTR) {
					$email = $users[$i][$params['email_attr']];
				} elseif ($users[$i][$params['email_attr']]['count'] === 1) {
					$email = $users[$i][$params['email_attr']][0];
				}
			}

			if (key_exists($params['desc_attr'], $users[$i])) {
				if ($params['desc_attr'] === Ldap::DN_ATTR) {
					$desc = $users[$i][$params['desc_attr']];
				} elseif ($users[$i][$params['desc_attr']]['count'] === 1) {
					$desc = $users[$i][$params['desc_attr']][0];
				}
			}

			if ($config_form_result) {
				$user_list[$username] = [
					'long_name' => $long_name,
					'email' => $email,
					'description' => $desc
				];
			} else {
				$user_list[] = [
					'username' => $username,
					'long_name' => $long_name,
					'email' => $email,
					'description' => $desc
				];
			}
		}
		return $user_list;
	}


	/**
	 * Get LDAP auth specific parameters with form values.
	 *
	 * @return array array LDAP auth parameters
	 */
	private function getLdapParams()
	{
		$params = [];
		$params['address'] = $this->LdapAuthServerAddress->Text;
		$params['port'] = $this->LdapAuthServerPort->Text;
		$params['ldaps'] = $this->LdapAuthServerLdaps->Checked ? 1 : 0;
		$params['starttls'] = $this->LdapAuthServerStartTLS->Checked ? 1 : 0;
		$params['protocol_ver'] = $this->LdapAuthServerProtocolVersion->SelectedValue;
		$params['base_dn'] = $this->LdapAuthServerBaseDn->Text;
		$params['filters'] = $this->LdapAuthServerFilters->Text;
		if ($this->LdapAuthMethodAnonymous->Checked) {
			$params['auth_method'] = Ldap::AUTH_METHOD_ANON;
		} elseif ($this->LdapAuthMethodSimple->Checked) {
			$params['auth_method'] = Ldap::AUTH_METHOD_SIMPLE;
		}
		$params['bind_dn'] = $this->LdapAuthMethodSimpleUsername->Text;
		$params['bind_password'] = $this->LdapAuthMethodSimplePassword->Text;
		$params['user_attr'] = $this->LdapAttributesUsername->Text;
		$params['long_name_attr'] = $this->LdapAttributesLongName->Text;
		$params['desc_attr'] = $this->LdapAttributesDescription->Text;
		$params['email_attr'] = $this->LdapAttributesEmail->Text;
		return $params;
	}

	/**
	 * Add to LDAP auth params additional parameters.
	 * Note, extra parameters are not set in config.
	 *
	 * @param array $params LDAP auth parameters (passing by reference)
	 */
	private function addLdapExtraParams(&$params)
	{
		$params['attrs'] = [$params['user_attr']]; // user attribute is obligatory
		if (key_exists('long_name_attr', $params) && !empty($params['long_name_attr'])) {
			$params['attrs'][] = $params['long_name_attr'];
		}
		if (key_exists('email_attr', $params) && !empty($params['email_attr'])) {
			$params['attrs'][] = $params['email_attr'];
		}
		if (key_exists('desc_attr', $params) && !empty($params['desc_attr'])) {
			$params['attrs'][] = $params['desc_attr'];
		}
		if ($this->GetUsersImportOptions->SelectedValue == self::IMPORT_OPT_CRITERIA) {
			$crit = (int) ($this->GetUsersCriteria->SelectedValue);
			switch ($crit) {
				case self::IMPORT_CRIT_USERNAME: $params['filter_attr'] = $params['user_attr'];
					break;
				case self::IMPORT_CRIT_LONG_NAME: $params['filter_attr'] = $params['long_name_attr'];
					break;
				case self::IMPORT_CRIT_DESCRIPTION: $params['filter_attr'] = $params['desc_attr'];
					break;
				case self::IMPORT_CRIT_EMAIL: $params['filter_attr'] = $params['email_attr'];
					break;
			}
			$params['filter_val'] = $this->GetUsersCriteriaFilter->Text;
		}
	}


	/**
	 * Prepare LDAP users to import.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter object
	 * @return array web users list to import
	 */
	private function prepareLdapUsers($sender, $param)
	{
		$ldap = $this->getModule('ldap');
		$params = $this->getLdapParams();
		$ldap->setParams($params);

		// add additional parameters
		$this->addLdapExtraParams($params);

		$import_opt = (int) $this->GetUsersImportOptions->SelectedValue;

		$users_web = [];
		switch ($import_opt) {
			case self::IMPORT_OPT_ALL_USERS: {
				$filter = $ldap->getFilter($params['user_attr'], '*');
				$users_ldap = $ldap->findUserAttr($filter, $params['attrs']);
				$users_web = $this->convertLdapUsers($users_ldap, $params, true);
				break;
			}
			case self::IMPORT_OPT_SELECTED_USERS: {
				if ($param instanceof TCallbackEventParameter) {
					$cb_param = $param->getCallbackParameter();
					if (is_array($cb_param)) {
						for ($i = 0; $i < count($cb_param); $i++) {
							$val = (array) $cb_param[$i];
							$users_web[$val['username']] = $val;
							unset($users_web[$val['username']]['username']);
						}
					}
				}
				break;
			}
			case self::IMPORT_OPT_CRITERIA: {
				if (!empty($params['filter_attr']) && !empty($params['filter_val'])) {
					$filter = $ldap->getFilter(
						$params['filter_attr'],
						'*' . $params['filter_val'] . '*'
					);
					$users_ldap = $ldap->findUserAttr($filter, $params['attrs']);
					$users_web = $this->convertLdapUsers($users_ldap, $params, true);
				}
				break;
			}
		}
		return $users_web;
	}

	/**
	 * Test LDAP connection.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function testLdapConnection($sender, $param)
	{
		$ldap = $this->getModule('ldap');
		$params = $this->getLdapParams();
		$ldap->setParams($params);

		if ($ldap->adminBind()) {
			$this->TestLdapConnectionMsg->Text = '';
			$this->TestLdapConnectionMsg->Display = 'None';
			$this->getCallbackClient()->show('ldap_test_connection_ok');
		} else {
			$this->getCallbackClient()->show('ldap_test_connection_error');
			$this->TestLdapConnectionMsg->Text = $ldap->getLdapError();
			$this->TestLdapConnectionMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Main method to import users.
	 * Supported are basic auth and LDAP auth user imports.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function importUsers($sender, $param)
	{
		if (!$this->GetUsersDefaultIps->IsValid) {
			// invalid IP restriction value
			return;
		}

		$users_web = [];
		if ($this->BasicAuth->Checked) {
			$users_web = $this->prepareBasicUsers($sender, $param);
		} elseif ($this->LdapAuth->Checked) {
			$users_web = $this->prepareLdapUsers($sender, $param);
		}

		// Get default roles for imported users
		$def_roles = $this->GetUsersDefaultRole->getSelectedIndices();
		$role_list = [];
		foreach ($def_roles as $indice) {
			for ($i = 0; $i < $this->GetUsersDefaultRole->getItemCount(); $i++) {
				if ($i === $indice) {
					$role_list[] = $this->GetUsersDefaultRole->Items[$i]->Value;
				}
			}
		}
		$roles = implode(',', $role_list);

		$api_hosts = [];
		$api_host_groups = [];
		$api_hosts_method = WebUserConfig::API_HOST_METHOD_HOSTS;
		if ($this->GetUsersAPIHostsOpt->Checked) {
			$api_hosts_method = WebUserConfig::API_HOST_METHOD_HOSTS;

			// Get default API hosts for imported users
			$selected_indices = $this->GetUsersDefaultAPIHosts->getSelectedIndices();
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->GetUsersDefaultAPIHosts->getItemCount(); $i++) {
					if ($i === $indice) {
						$api_hosts[] = $this->GetUsersDefaultAPIHosts->Items[$i]->Value;
					}
				}
			}
		} elseif ($this->GetUsersAPIHostGroupsOpt->Checked) {
			$api_hosts_method = WebUserConfig::API_HOST_METHOD_HOST_GROUPS;

			// Get default API host groups config values
			$selected_indices = $this->GetUsersDefaultAPIHostGroups->getSelectedIndices();
			$api_host_groups = [];
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->GetUsersDefaultAPIHostGroups->getItemCount(); $i++) {
					if ($i === $indice) {
						$api_host_groups[] = $this->GetUsersDefaultAPIHostGroups->Items[$i]->Value;
					}
				}
			}
		}

		// Get default IP address restrictions for imported users
		$ips = $this->trimIps($this->GetUsersDefaultIps->Text);

		// fill missing default values
		$add_def_user_params = function (&$user, $idx) use ($roles, $api_hosts_method, $api_hosts, $api_host_groups, $ips) {
			$user['roles'] = $roles;
			$user['api_hosts_method'] = $api_hosts_method;
			$user['api_hosts'] = $api_hosts;
			$user['api_host_groups'] = $api_host_groups;
			$user['ips'] = $ips;
			$user['enabled'] = '1';
		};
		array_walk($users_web, $add_def_user_params);

		$user_mod = $this->getModule('user_config');
		$users = $user_mod->getConfig();

		$users_cfg = [];
		if ($this->GetUsersProtectOverwrite->Checked) {
			$users_cfg = array_merge($users_web, $users);
		} else {
			$users_cfg = array_merge($users, $users_web);
		}
		$result = $user_mod->setConfig($users_cfg);


		if ($result === true) {
			$user_count = count($users_web);
			$amsg = '';
			if ($this->BasicAuth->Checked) {
				$amsg = "Import Basic users. Count: $user_count";
			} elseif ($this->LdapAuth->Checked) {
				$amsg = "Import LDAP users. Count: $user_count";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}

		// refresh user list
		$this->setUserList(null, null);

		// refresh role list
		$this->setRoleList(null, null);

		$this->getCallbackClient()->callClientFunction('oUserSecurity.show_user_modal', [
			false
		]);
	}

	/**
	 * Get users and provide them to template.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function getUsers($sender, $param)
	{
		if ($this->BasicAuth->Checked) {
			$this->getBasicUsers($sender, $param);
		} elseif ($this->LdapAuth->Checked) {
			$this->getLdapUsers($sender, $param);
		}
	}

	/**
	 * Save security config.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function saveSecurityConfig($sender, $param)
	{
		$config = $this->web_config;
		if (!key_exists('security', $config)) {
			$config['security'] = [];
		}
		if ($this->GeneralDefaultNoAccess->Checked) {
			$config['security']['def_access'] = WebConfig::DEF_ACCESS_NO_ACCESS;
		} elseif ($this->GeneralDefaultAccess->Checked) {
			$config['security']['def_access'] = WebConfig::DEF_ACCESS_DEFAULT_SETTINGS;
			$config['security']['def_role'] = $this->GeneralDefaultAccessRole->SelectedValue;
			$config['security']['def_api_host'] = $this->GeneralDefaultAccessAPIHost->SelectedValue;
		}
		if ($this->LocalAuth->Checked) {
			$config['security']['auth_method'] = WebConfig::AUTH_METHOD_LOCAL;
		} elseif ($this->BasicAuth->Checked) {
			$config['security']['auth_method'] = WebConfig::AUTH_METHOD_BASIC;
			$config['auth_basic'] = $this->getBasicParams();
		} elseif ($this->LdapAuth->Checked) {
			$config['security']['auth_method'] = WebConfig::AUTH_METHOD_LDAP;
			$config['auth_ldap'] = $this->getLdapParams();
		}
		$ret = $this->getModule('web_config')->setConfig($config);
		if ($ret === true) {
			$this->getCallbackClient()->hide('auth_method_save_error');
			$this->getCallbackClient()->show('auth_method_save_ok');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				'Save application settings'
			);
		} else {
			$this->getCallbackClient()->hide('auth_method_save_ok');
			$this->getCallbackClient()->show('auth_method_save_error');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				'Problem with saving application settings'
			);
		}
	}

	/**
	 * Determines if user management is enabled.
	 * This checking bases on selected auth method and permission to manage users.
	 *
	 * @return bool true if managing users is enabled, otherwise false
	 */
	private function isManageUsersAvail()
	{
		$is_local = $this->getModule('web_config')->isAuthMethodLocal();
		$is_basic = $this->getModule('web_config')->isAuthMethodBasic();
		$allow_manage_users = (isset($this->web_config['auth_basic']['allow_manage_users']) &&
			$this->web_config['auth_basic']['allow_manage_users'] == 1);
		return (($is_basic && $allow_manage_users) || $is_local);
	}

	/**
	 * Set and load console ACL list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setConsoleList($sender, $param)
	{
		$config = $this->getModule('api')->get(['config', 'dir', 'Console']);
		$console_directives = [
			'Description' => '',
			'JobAcl' => '',
			'ClientAcl' => '',
			'StorageAcl' => '',
			'ScheduleAcl' => '',
			'RunAcl' => '',
			'PoolAcl' => '',
			'CommandAcl' => '',
			'FilesetAcl' => '',
			'CatalogAcl' => '',
			'WhereAcl' => '',
			'PluginOptionsAcl' => '',
			'BackupClientAcl' => '',
			'RestoreClientAcl' => '',
			'DirectoryAcl' => ''
		];
		$consoles = [];
		$join_cons = function ($item) {
			if (is_array($item)) {
				$item = implode(',', $item);
			}
			return $item;
		};
		if ($config->error == 0) {
			for ($i = 0; $i < count($config->output); $i++) {
				$cons = (array) $config->output[$i]->Console;
				$cons = array_map($join_cons, $cons);
				$consoles[] = array_merge($console_directives, $cons);
			}
		}
		$this->getCallbackClient()->callClientFunction('oConsoles.load_console_list_cb', [
			$consoles
		]);
		$this->console_config = $consoles;
	}

	/**
	 * Load data in console modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadConsoleWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if (!empty($name)) {
			// edit existing console
			$this->ConsoleConfig->setResourceName($name);
			$this->ConsoleConfig->setLoadValues(true);
		} else {
			// add new console
			$this->ConsoleConfig->setLoadValues(false);
			$this->getCallbackClient()->callClientFunction('oBaculaConfigSection.show_sections', [true]);
		}
		$this->ConsoleConfig->setHost($this->User->getDefaultAPIHost());
		$this->ConsoleConfig->setComponentName($_SESSION['dir']);
		$this->ConsoleConfig->IsDirectiveCreated = false;
		$this->ConsoleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
	}

	/**
	 * Remove consoles action.
	 * Here is possible to remove one console or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeConsoles($sender, $param)
	{
		$consoles = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($consoles); $i++) {
			$result = $this->getModule('api')->remove(
				[
					'config',
					'dir',
					'Console',
					$consoles[$i]
				],
				$this->User->getDefaultAPIHost(),
				false
			);
			if ($result->error === 0) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_CONFIG,
					"Remove Bacula config resource. Component: Director, Resource: Console, Name: {$consoles[$i]}"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_CONFIG,
					"Problem with removing Bacula config resource. Component: Director, Resource: Console, Name: {$consoles[$i]}"
				);
			}
		}

		// refresh console list
		$this->setConsoleList(null, null);

		// refresh OAuth2 client console combobox
		$this->loadOAuth2ClientConsole(null, null);
	}

	public function setAllCommandAcls($sender, $param)
	{
		$config = [
			"CommandAcl" => JobInfo::COMMAND_ACL_USED_BY_WEB
		];
		$this->ConsoleConfig->loadConfig($sender, $param, 'ondirectivelistload', $config);
		$this->getCallbackClient()->callClientFunction('oBaculaConfigSection.show_sections', [true]);
	}

	/**
	 * Set and load API basic user list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setAPIBasicUserList($sender, $param)
	{
		$basic_users = $this->getModule('api')->get(['basic', 'users']);
		$this->getCallbackClient()->callClientFunction('oAPIBasicUsers.load_api_basic_user_list_cb', [
			$basic_users->output,
			$basic_users->error
		]);

		if ($basic_users->error === 0) {
			$usernames = ['' => ''];
			for ($i = 0; $i < count($basic_users->output); $i++) {
				$usernames[$basic_users->output[$i]->username] = $basic_users->output[$i]->username;
			}
			uasort($usernames, 'strnatcasecmp');
			$this->APIHostBasicUserSettings->DataSource = $usernames;
			$this->APIHostBasicUserSettings->dataBind();
		}
	}

	/**
	 * Load data in API basic user modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIBasicUserWindow($sender, $param)
	{
		$username = $param->getCallbackParameter();
		if (!empty($username)) {
			$basic_user_cfg = $this->getModule('api')->get(['basic', 'users', $username]);
			if ($basic_user_cfg->error === 0 && is_object($basic_user_cfg->output)) {
				// It is done only for existing basic user accounts
				$this->APIBasicUserUsername->Text = $basic_user_cfg->output->username;
				$this->APIBasicUserBconsoleCfgPath->Text = $basic_user_cfg->output->bconsole_cfg_path;
			}
		}
		$this->loadAPIBasicUserConsole(null, null);

		$dirs = $this->getModule('api')->get(['config', 'bcons', 'Director']);
		$dir_names = [];
		if ($dirs->error == 0) {
			for ($i = 0; $i < count($dirs->output); $i++) {
				$dir_names[$dirs->output[$i]->Director->Name] = $dirs->output[$i]->Director->Name;
			}
		}
		$this->APIBasicUserDirector->DataSource = $dir_names;
		$this->APIBasicUserDirector->dataBind();
	}

	public function loadAPIBasicUserConsole($sender, $param)
	{
		$cons = $this->getModule('api')->get(['config', 'dir', 'Console']);
		$console = ['' => ''];
		if ($cons->error == 0) {
			for ($i = 0; $i < count($cons->output); $i++) {
				$console[$cons->output[$i]->Console->Name] = $cons->output[$i]->Console->Name;
			}
		}
		$this->APIBasicUserConsole->DataSource = $console;
		$this->APIBasicUserConsole->dataBind();
	}

	/**
	 * Save API basic user config.
	 * It works both for new basic users and for edited basic users.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveAPIBasicUser($sender, $param)
	{
		$username = $this->APIBasicUserUsername->Text;
		$cfg = [];
		$cfg['username'] = $username;
		$cfg['password'] = $this->APIBasicUserPassword->Text;
		$cfg['bconsole_cfg_path'] = $this->APIBasicUserBconsoleCfgPath->Text;
		if ($this->APIBasicUserBconsoleCreate->Checked) {
			$cfg['console'] = $this->APIBasicUserConsole->SelectedValue;
			$cfg['director'] = $this->APIBasicUserDirector->SelectedValue;
		}

		$win_type = $this->APIBasicUserWindowType->Value;
		$result = (object) ['error' => -1];
		if ($win_type === self::TYPE_ADD_WINDOW) {
			$result = $this->getModule('api')->create(['basic', 'users', $username], $cfg);
		} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
			$result = $this->getModule('api')->set(['basic', 'users', $username], $cfg);
		}

		if ($result->error === 0) {
			// Refresh API basic user list
			$this->setAPIBasicUserList(null, null);

			$amsg = '';
			if ($win_type === self::TYPE_ADD_WINDOW) {
				$amsg = "Create API Basic user. User: $username";
			} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
				$amsg = "Save API Basic user. User: $username";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}
		$this->getCallbackClient()->callClientFunction('oAPIBasicUsers.save_api_basic_user_cb', [
			$result
		]);
	}

	/**
	 * Remove API basic users action.
	 * Here is possible to remove one basic user or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeAPIBasicUsers($sender, $param)
	{
		$usernames = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($usernames); $i++) {
			$result = $this->getModule('api')->remove(['basic', 'users', $usernames[$i]]);
			if ($result->error !== 0) {
				break;
			}
		}

		if (count($usernames) > 0) {
			// Refresh API basic user list
			$this->setAPIBasicUserList(null, null);

			for ($i = 0; $i < count($usernames); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove API Basic user. User: {$usernames[$i]}"
				);
			}
		}
	}

	/**
	 * Set and load basic user settings to API host modal window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function loadAPIBasicUserSettings($sender, $param)
	{
		$username = $this->APIHostBasicUserSettings->SelectedValue;
		if (!empty($username)) {
			$host = $this->APIHostSettings->SelectedValue ?: null;
			$basic_cfg = $this->getModule('api')->get(['basic', 'users', $username], $host);
			if ($basic_cfg->error === 0 && is_object($basic_cfg->output)) {
				$this->APIHostBasicLogin->Text = $basic_cfg->output->username;
			}
		}
	}

	/**
	 * Set and load OAuth2 client list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setOAuth2ClientList($sender, $param)
	{
		$oauth2_clients = $this->getModule('api')->get(['oauth2', 'clients']);
		$this->getCallbackClient()->callClientFunction('oOAuth2Clients.load_oauth2_client_list_cb', [
			$oauth2_clients->output,
			$oauth2_clients->error
		]);
	}

	public function loadOAuth2ClientConsole($sender, $param)
	{
		$cons = $this->getModule('api')->get(['config', 'dir', 'Console']);
		$console = ['' => ''];
		if ($cons->error == 0) {
			for ($i = 0; $i < count($cons->output); $i++) {
				$console[$cons->output[$i]->Console->Name] = $cons->output[$i]->Console->Name;
			}
		}
		$this->OAuth2ClientConsole->DataSource = $console;
		$this->OAuth2ClientConsole->dataBind();
	}

	/**
	 * Load data in OAuth2 client modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadOAuth2ClientWindow($sender, $param)
	{
		$client_id = $param->getCallbackParameter();
		if (!empty($client_id)) {
			$oauth2_cfg = $this->getModule('api')->get(['oauth2', 'clients', $client_id]);
			if ($oauth2_cfg->error === 0 && is_object($oauth2_cfg->output)) {
				// It is done only for existing OAuth2 client accounts
				$this->OAuth2ClientClientId->Text = $oauth2_cfg->output->client_id;
				$this->OAuth2ClientClientSecret->Text = $oauth2_cfg->output->client_secret;
				$this->OAuth2ClientRedirectURI->Text = $oauth2_cfg->output->redirect_uri;
				$this->OAuth2ClientScope->Text = $oauth2_cfg->output->scope;
				$this->OAuth2ClientBconsoleCfgPath->Text = $oauth2_cfg->output->bconsole_cfg_path;
				$this->OAuth2ClientName->Text = $oauth2_cfg->output->name;
			}
		}
		$this->loadOAuth2ClientConsole(null, null);

		$dirs = $this->getModule('api')->get(['config', 'bcons', 'Director']);
		$dir_names = [];
		if ($dirs->error == 0) {
			for ($i = 0; $i < count($dirs->output); $i++) {
				$dir_names[$dirs->output[$i]->Director->Name] = $dirs->output[$i]->Director->Name;
			}
		}
		$this->OAuth2ClientDirector->DataSource = $dir_names;
		$this->OAuth2ClientDirector->dataBind();
	}

	/**
	 * Save OAuth2 client.
	 * It works both for new OAuth2 client and for edited OAuth2 client.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveOAuth2Client($sender, $param)
	{
		$client_id = $this->OAuth2ClientClientId->Text;
		$cfg = [];
		$cfg['client_id'] = $client_id;
		$cfg['client_secret'] = $this->OAuth2ClientClientSecret->Text;
		$cfg['redirect_uri'] = $this->OAuth2ClientRedirectURI->Text;
		$cfg['scope'] = $this->OAuth2ClientScope->Text;
		$cfg['bconsole_cfg_path'] = $this->OAuth2ClientBconsoleCfgPath->Text;
		if ($this->OAuth2ClientBconsoleCreate->Checked) {
			$cfg['console'] = $this->OAuth2ClientConsole->SelectedValue;
			$cfg['director'] = $this->OAuth2ClientDirector->SelectedValue;
		}
		$cfg['name'] = $this->OAuth2ClientName->Text;

		$win_type = $this->OAuth2ClientWindowType->Value;
		$result = (object) ['error' => -1];
		if ($win_type === self::TYPE_ADD_WINDOW) {
			$result = $this->getModule('api')->create(['oauth2', 'clients', $client_id], $cfg);
		} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
			$result = $this->getModule('api')->set(['oauth2', 'clients', $client_id], $cfg);
		}

		if ($result->error === 0) {
			// Refresh OAuth2 client list
			$this->setOAuth2ClientList(null, null);

			$amsg = '';
			if ($win_type === self::TYPE_ADD_WINDOW) {
				$amsg = "Create API OAuth2 client. ClientId: $client_id";
			} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
				$amsg = "Save API OAuth2 client. ClientId: $client_id";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}
		$this->getCallbackClient()->callClientFunction('oOAuth2Clients.save_oauth2_client_cb', [
			$result
		]);
	}

	/**
	 * Remove OAuth2 client action.
	 * Here is possible to remove one OAuth2 client or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeOAuth2Clients($sender, $param)
	{
		$client_ids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($client_ids); $i++) {
			$result = $this->getModule('api')->remove(['oauth2', 'clients', $client_ids[$i]]);
			if ($result->error !== 0) {
				break;
			}
		}

		if (count($client_ids) > 0) {
			// Refresh OAuth2 client list
			$this->setOAuth2ClientList(null, null);

			for ($i = 0; $i < count($client_ids); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove API OAuth2 client. ClientId: {$client_ids[$i]}"
				);
			}
		}
	}

	/**
	 * Set and load OAuth2 client settings to API host modal window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function loadOAuth2ClientSettings($sender, $param)
	{
		$client_id = $this->APIHostOAuth2ClientSettings->SelectedValue;
		if (!empty($client_id)) {
			$host = $this->APIHostSettings->SelectedValue ?: null;
			$oauth2_cfg = $this->getModule('api')->get(['oauth2', 'clients', $client_id], $host);
			if ($oauth2_cfg->error === 0 && is_object($oauth2_cfg->output)) {
				$this->APIHostOAuth2ClientId->Text = $oauth2_cfg->output->client_id;
				$this->APIHostOAuth2ClientSecret->Text = $oauth2_cfg->output->client_secret;
				$this->APIHostOAuth2RedirectURI->Text = $oauth2_cfg->output->redirect_uri;
				$this->APIHostOAuth2Scope->Text = $oauth2_cfg->output->scope;
			}
		}
	}

	/**
	 * Load OAuth2 client list to get OAuth2 client settings.
	 *
	 */
	private function loadOAuth2ClientList()
	{
		$host = $this->APIHostSettings->SelectedValue ?: null;
		$oauth2_clients = $this->getModule('api')->get(['oauth2', 'clients'], $host);
		$oauth2_client_list = ['' => ''];
		if ($oauth2_clients->error == 0 && is_array($oauth2_clients->output)) {
			for ($i = 0; $i < count($oauth2_clients->output); $i++) {
				$name = $oauth2_clients->output[$i]->name ?: $oauth2_clients->output[$i]->client_id;
				$oauth2_client_list[$oauth2_clients->output[$i]->client_id] = $name;
			}
		}
		uasort($oauth2_client_list, 'strnatcasecmp');
		$this->APIHostOAuth2ClientSettings->DataSource = $oauth2_client_list;
		$this->APIHostOAuth2ClientSettings->dataBind();
	}

	/**
	 * Set and load API hosts list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setAPIHostList($sender, $param)
	{
		$api_hosts = $this->getModule('host_config')->getConfig();
		$shortnames = array_keys($api_hosts);
		$attributes = array_values($api_hosts);
		for ($i = 0; $i < count($attributes); $i++) {
			$attributes[$i]['name'] = $shortnames[$i];
		}

		$this->getCallbackClient()->callClientFunction('oAPIHosts.load_api_host_list_cb', [
			$attributes
		]);
	}

	/**
	 * Load data in API host modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIHostWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();

		// prepare API host combobox
		$api_hosts = $this->getModule('host_config')->getConfig();

		if (!empty($name) && key_exists($name, $api_hosts)) {
			$this->APIHostAddress->Text = $api_hosts[$name]['address'];
			$this->APIHostProtocol->SelectedValue = $api_hosts[$name]['protocol'];
			$this->APIHostPort->Text = $api_hosts[$name]['port'];
			$this->APIHostOAuth2ClientId->Text = $api_hosts[$name]['client_id'];
			$this->APIHostOAuth2ClientSecret->Text = $api_hosts[$name]['client_secret'];
			$this->APIHostOAuth2RedirectURI->Text = $api_hosts[$name]['redirect_uri'];
			$this->APIHostOAuth2Scope->Text = $api_hosts[$name]['scope'];
			$this->APIHostName->Text = $name;
			$this->APIHostBasicLogin->Text = $api_hosts[$name]['login'];
			$this->APIHostBasicPassword->Text = $api_hosts[$name]['password'];
			if ($api_hosts[$name]['auth_type'] == 'basic') {
				$this->APIHostAuthBasic->Checked = true;
				$this->getCallbackClient()->hide('configure_oauth2_auth');
				$this->getCallbackClient()->show('configure_basic_auth');
			} elseif ($api_hosts[$name]['auth_type'] == 'oauth2') {
				$this->APIHostAuthOAuth2->Checked = true;
				$this->getCallbackClient()->hide('configure_basic_auth');
				$this->getCallbackClient()->show('configure_oauth2_auth');
			}
		}

		$shortnames = array_keys($api_hosts);
		natcasesort($shortnames);

		$api_host_names = array_combine($shortnames, $shortnames);
		$this->APIHostSettings->DataSource = array_merge(['' => ''], $api_host_names);
		$this->APIHostSettings->dataBind();

		// prepare OAuth2 client combobox
		$this->loadOAuth2ClientList();
	}

	/**
	 * Load API host settings to API host modal window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function loadAPIHostSettings($sender, $param)
	{
		$api_host = $this->APIHostSettings->SelectedValue;
		if (!empty($api_host)) {
			$config = $this->getModule('host_config')->getConfig();
			if (key_exists($api_host, $config)) {
				// load OAuth2 clients to combobox from selected API host
				$this->loadOAuth2ClientList();

				$this->APIHostProtocol->SelectedValue = $config[$api_host]['protocol'];
				$this->APIHostAddress->Text = $config[$api_host]['address'];
				$this->APIHostPort->Text = $config[$api_host]['port'];
				if ($config[$api_host]['auth_type'] == 'basic') {
					$this->APIHostAuthBasic->Checked = true;
					$this->getCallbackClient()->hide('configure_oauth2_auth');
					$this->getCallbackClient()->show('configure_basic_auth');
				} elseif ($config[$api_host]['auth_type'] == 'oauth2') {
					$this->APIHostAuthOAuth2->Checked = true;
					$this->getCallbackClient()->hide('configure_basic_auth');
					$this->getCallbackClient()->show('configure_oauth2_auth');
				}
			}
		}
	}

	public function connectionAPITest($sender, $param)
	{
		$host = $this->APIHostAddress->Text;
		if (empty($host)) {
			$host = false;
		}
		$host_params = [
			'protocol' => $this->APIHostProtocol->SelectedValue,
			'address' => $this->APIHostAddress->Text,
			'port' => $this->APIHostPort->Text,
			'url_prefix' => ''
		];

		if ($this->APIHostAuthBasic->Checked) {
			$host_params['auth_type'] = 'basic';
			$host_params['login'] = $this->APIHostBasicLogin->Text;
			$host_params['password'] = $this->APIHostBasicPassword->Text;
		} elseif ($this->APIHostAuthOAuth2->Checked) {
			$host_params['auth_type'] = 'oauth2';
			$host_params['client_id'] = $this->APIHostOAuth2ClientId->Text;
			$host_params['client_secret'] = $this->APIHostOAuth2ClientSecret->Text;
			$host_params['redirect_uri'] = $this->APIHostOAuth2RedirectURI->Text;
			$host_params['scope'] = $this->APIHostOAuth2Scope->Text;
		}
		$api = $this->getModule('api');

		// Catalog test
		OAuth2Record::deleteByPk($host);
		$api->setHostParams($host, $host_params);
		$catalog = $api->get(['catalog'], $host, false);

		// Console test
		OAuth2Record::deleteByPk($host);
		$api->setHostParams($host, $host_params);
		$director = null;
		if (array_key_exists('director', $_SESSION)) {
			// Current director can't be passed to new remote host.
			$director = $_SESSION['director'];
			unset($_SESSION['director']);
		}

		$console = $api->set(['console'], ['version'], $host, false);
		if (!is_null($director)) {
			// Revert director setting if any
			$_SESSION['director'] = $director;
		}

		// Config test
		OAuth2Record::deleteByPk($host);
		$api->setHostParams($host, $host_params);
		$config = $api->get(['config'], $host, false);
		OAuth2Record::deleteByPk($host);

		$is_catalog = (is_object($catalog) && $catalog->error === 0);
		$is_console = (is_object($console) && $console->error === 0);
		$is_config = (is_object($config) && $config->error === 0);

		$status_ok = $is_catalog;
		if ($status_ok) {
			$status_ok = $is_console;
		}

		if (!$is_catalog) {
			$this->APIHostTestResultErr->Text .= $catalog->output . '<br />';
		}
		if (!$is_console) {
			$this->APIHostTestResultErr->Text .= $console->output . '<br />';
		}
		if (!$is_config) {
			$this->APIHostTestResultErr->Text .= $config->output . '<br />';
		}

		$this->APIHostTestResultOk->Display = ($status_ok === true) ? 'Dynamic' : 'None';
		$this->APIHostTestResultErr->Display = ($status_ok === false) ? 'Dynamic' : 'None';
		$this->APIHostCatalogSupportYes->Display = ($is_catalog === true) ? 'Dynamic' : 'None';
		$this->APIHostCatalogSupportNo->Display = ($is_catalog === false) ? 'Dynamic' : 'None';
		$this->APIHostConsoleSupportYes->Display = ($is_console === true) ? 'Dynamic' : 'None';
		$this->APIHostConsoleSupportNo->Display = ($is_console === false) ? 'Dynamic' : 'None';
		$this->APIHostConfigSupportYes->Display = ($is_config === true) ? 'Dynamic' : 'None';
		$this->APIHostConfigSupportNo->Display = ($is_config === false) ? 'Dynamic' : 'None';
	}

	private function assignNewAPIHostToAPIGroups($host)
	{
		if (!$this->APIHostUseHostGroups->Checked) {
			// No use API groups checkebox checked, no assigning
			return;
		}

		$ahg = $this->getModule('host_group_config');
		$selected_indices = $this->APIHostGroups->getSelectedIndices();
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->APIHostGroups->getItemCount(); $i++) {
				if ($i === $indice) {
					$host_group = $this->APIHostGroups->Items[$i]->Value;
					$config = $ahg->getHostGroupConfig($host_group);
					if (!in_array($host, $config['api_hosts'])) {
						$config['api_hosts'][] = $host;
					}
					if ($ahg->setHostGroupConfig($host_group, $config)) {
						$this->getModule('audit')->audit(
							AuditLog::TYPE_INFO,
							AuditLog::CATEGORY_APPLICATION,
							"Newly created API host assigned to API group. API host: {$host},  host group: {$host_group}"
						);
					} else {
						$this->getModule('audit')->audit(
							AuditLog::TYPE_ERROR,
							AuditLog::CATEGORY_APPLICATION,
							"Error while assigning newly created API host to API group. API host: {$host},  host group: {$host_group}"
						);
					}
				}
			}
		}
	}

	public function saveAPIHost($sender, $param)
	{
		$cfg_host = [
			'auth_type' => '',
			'login' => '',
			'password' => '',
			'client_id' => '',
			'client_secret' => '',
			'redirect_uri' => '',
			'scope' => ''
		];
		$cfg_host['protocol'] = $this->APIHostProtocol->Text;
		$cfg_host['address'] = $this->APIHostAddress->Text;
		$cfg_host['port'] = $this->APIHostPort->Text;
		$cfg_host['url_prefix'] = '';
		if ($this->APIHostAuthBasic->Checked == true) {
			$cfg_host['auth_type'] = 'basic';
			$cfg_host['login'] = $this->APIHostBasicLogin->Text;
			$cfg_host['password'] = $this->APIHostBasicPassword->Text;
		} elseif ($this->APIHostAuthOAuth2->Checked == true) {
			$cfg_host['auth_type'] = 'oauth2';
			$cfg_host['client_id'] = $this->APIHostOAuth2ClientId->Text;
			$cfg_host['client_secret'] = $this->APIHostOAuth2ClientSecret->Text;
			$cfg_host['redirect_uri'] = $this->APIHostOAuth2RedirectURI->Text;
			$cfg_host['scope'] = $this->APIHostOAuth2Scope->Text;
		}
		$hc = $this->getModule('host_config');
		$config = $hc->getConfig();
		$host_name = trim($this->APIHostName->Text);
		if (empty($host_name)) {
			$host_name = $cfg_host['address'];
		}
		$host_exists = key_exists($host_name, $config);
		$config[$host_name] = $cfg_host;
		$result = $hc->setConfig($config);
		$this->setAPIHostList(null, null);
		$this->getCallbackClient()->hide('api_host_window');

		if ($result === true) {
			if (!$host_exists) {
				$this->assignNewAPIHostToAPIGroups($host_name);
				$this->setAPIHostGroupList(null, null);
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Create API host. Name: $host_name"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Save API host. Name: $host_name"
				);
			}
		}

		// refresh user window
		$this->initUserWindow();

		// refresh API host group window
		$this->initAPIHostGroupWindow();
	}

	/**
	 * Remove API host action.
	 * Here is possible to remove one API host or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeAPIHosts($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$hc = $this->getModule('host_config');
		$config = $hc->getConfig();
		$cfg = [];
		foreach ($config as $host => $opts) {
			if (in_array($host, $names)) {
				continue;
			}
			$cfg[$host] = $opts;
		}
		$result = $hc->setConfig($cfg);
		if ($result === true) {
			$uc = $this->getModule('user_config');
			$uc->unassignAPIHosts($names);
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove API host. Name: {$names[$i]}"
				);
			}
		}

		$this->setAPIHostList(null, null);
		$this->setAPIHostGroupList(null, null);

		// refresh API host group window
		$this->initAPIHostGroupWindow();
	}

	/**
	 * Validate IP restriction address value.
	 *
	 * @param TActiveCustomValidator $sender sender object
	 * @param TServerValidateEventParameter $param event object parameter
	 */
	public function validateIps($sender, $param)
	{
		$valid = true;
		$val = trim($param->Value);
		if (!empty($val)) {
			$ips = explode(',', $val);
			for ($i = 0; $i < count($ips); $i++) {
				$ip = trim($ips[$i]);
				if (!filter_var($ip, FILTER_VALIDATE_IP) && !(strpos($ip, '*') !== false && preg_match('/^[\da-f:.*]+$/i', $ip) === 1)) {
					$valid = false;
					break;
				}
			}
		}
		$param->IsValid = $valid;
	}

	/**
	 * Simple helper that trims IP restriction address values.
	 *
	 * @param string $ips IP restriction address values
	 * @return string trimmed addresses
	 */
	public function trimIps($ips)
	{
		$ips = trim($ips);
		if (!empty($ips)) {
			$ips = explode(',', $ips);
			$ips = array_map('trim', $ips);
			$ips = implode(',', $ips);
		}
		return $ips;
	}

	/**
	 * Load user window with API hosts and API host groups access settings.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadUserAPIHostResourceAccessWindow($sender, $param)
	{
		$username = $this->UserAPIHostResourceAccessName->Value;

		$user_config = $this->getModule('user_config')->getUserConfig($username);
		if (count($user_config) > 0) {
			$api_hosts = [];
			if ($user_config['api_hosts_method'] === WebUserConfig::API_HOST_METHOD_HOSTS) {
				$api_hosts = $user_config['api_hosts'];
			} elseif ($user_config['api_hosts_method'] === WebUserConfig::API_HOST_METHOD_HOST_GROUPS) {
				$host_groups = $this->getModule('host_group_config');
				$api_hosts = $host_groups->getAPIHostsByGroups($user_config['api_host_groups']);
			}
			// strip main API host
			$cbf = function ($host) {
				return ($host !== HostConfig::MAIN_CATALOG_HOST);
			};
			$api_hosts = array_filter($api_hosts, $cbf);
			$this->setAPIHosts($this->UserAPIHostList, null, true, $api_hosts);
		}
	}

	/**
	 * Set user window with API hosts and API host groups access settings.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setUserAPIHostResourceAccessWindow($sender, $param)
	{
		$api_host = $this->UserAPIHostList->getSelectedValue();
		$this->setAPIHostJobs(
			$this->UserAPIHostResourceAccessJobs,
			$api_host,
			'user_access_window_error'
		);
		$this->setUserAPIHostConsole($api_host);
		$this->setAPIHostResourcePermissions(
			$this->UserAPIHostResourcePermissions,
			$api_host,
			'user_access_window_error'
		);
	}

	/**
	 * Set user API host console.
	 *
	 * @param string $api_host API host name
	 * @param bool $set_state determine if state (all/selected resources) should be changed
	 */
	private function setUserAPIHostConsole($api_host, $set_state = true)
	{
		$console = $this->isAPIHostConsole($api_host);
		$cb = $this->getCallbackClient();
		$cb->hide('user_access_window_console');
		if (!empty($console)) {
			$cb->show('user_access_window_select_jobs');
			$cb->show('user_access_window_console');
			if ($set_state) {
				$this->UserAPIHostResourceAccessSelectedResources->Checked = true;
			}
		} else {
			if ($set_state) {
				$cb->hide('user_access_window_select_jobs');
				$this->UserAPIHostResourceAccessAllResources->Checked = true;
			}
		}
	}

	/**
	 * Unassign console from API host.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function unassignUserAPIHostConsole($sender, $param)
	{
		$api_host = $param->getCallbackParameter();
		$success = $this->unassignAPIHostConsoleInternal($api_host);
		if ($success) {
			$this->setAPIHostJobs(
				$this->UserAPIHostResourceAccessJobs,
				$api_host,
				'user_access_window_error'
			);
			$this->setUserAPIHostConsole($api_host, false);
			$cb = $this->getCallbackClient();
			$cb->hide('user_access_window_console');
		}
	}

	/**
	 * Save user window with API hosts and API host groups.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveUserAPIHostResourceAccess($sender, $param)
	{
		$api_host = $this->UserAPIHostList->getSelectedValue();
		if ($this->UserAPIHostResourceAccessAllResources->Checked) {
			$state = $this->setResourceConsole(
				$api_host,
				'',
				'user_access_window_error'
			);
			if ($state) {
				$this->setAPIHostJobs(
					$this->UserAPIHostResourceAccessJobs,
					$api_host,
					'user_access_window_error'
				);
				$this->setUserAPIHostConsole($api_host);
			}
		} elseif ($this->UserAPIHostResourceAccessSelectedResources->Checked) {
			$selected_indices = $this->UserAPIHostResourceAccessJobs->getSelectedIndices();
			$jobs = [];
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->UserAPIHostResourceAccessJobs->getItemCount(); $i++) {
					if ($i === $indice) {
						$jobs[] = $this->UserAPIHostResourceAccessJobs->Items[$i]->Value;
					}
				}
			}
			$console = $this->setJobResourceAccess($api_host, $jobs);
			if ($console) {
				$state = $this->setResourceConsole(
					$api_host,
					$console,
					'user_access_window_error'
				);
				if ($state) {
					$this->setAPIHostJobs(
						$this->UserAPIHostResourceAccessJobs,
						$api_host,
						'user_access_window_error'
					);
				}
				$this->setUserAPIHostConsole($api_host);
			}
		}
		$this->saveAPIHostResourcePermissions(
			$this->UserAPIHostResourcePermissions,
			$api_host,
			'user_access_window_error'
		);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_SECURITY,
			"Save user API host access to resources. API host: $api_host"
		);
	}

	/**
	 * Load API host set resource access window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIHostResourceAccessWindow($sender, $param)
	{
		$api_host = $param->getCallbackParameter();
		$this->setAPIHostJobs(
			$this->APIHostResourceAccessJobs,
			$api_host,
			'api_host_access_window_error'
		);
		$this->setAPIHostConsole($api_host);
		$this->setAPIHostResourcePermissions(
			$this->APIHostResourcePermissions,
			$api_host,
			'api_host_access_window_error'
		);
	}

	/**
	 * Save window with API hosts.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveAPIHostResourceAccess($sender, $param)
	{
		$api_host = $this->APIHostResourceAccessName->Value;
		$cb = $this->getCallbackClient();
		if ($this->APIHostResourceAccessAllResources->Checked) {
			$state = $this->setResourceConsole(
				$api_host,
				'',
				'api_host_access_window_error'
			);
			if ($state) {
				$this->setAPIHostJobs(
					$this->APIHostResourceAccessJobs,
					$api_host,
					'api_host_access_window_error'
				);
				$this->setAPIHostConsole($api_host);
				$cb->hide('api_host_access_window');
			}
		} elseif ($this->APIHostResourceAccessSelectedResources->Checked) {
			$selected_indices = $this->APIHostResourceAccessJobs->getSelectedIndices();
			$jobs = [];
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->APIHostResourceAccessJobs->getItemCount(); $i++) {
					if ($i === $indice) {
						$jobs[] = $this->APIHostResourceAccessJobs->Items[$i]->Value;
					}
				}
			}
			$console = $this->setJobResourceAccess($api_host, $jobs);
			if ($console) {
				$state = $this->setResourceConsole(
					$api_host,
					$console,
					'api_host_access_window_error'
				);
				if ($state) {
					$this->setAPIHostJobs(
						$this->APIHostResourceAccessJobs,
						$api_host,
						'api_host_access_window_error'
					);
					$this->setAPIHostConsole($api_host);
					$cb->hide('api_host_access_window');
				}
			}
		}
		$this->saveAPIHostResourcePermissions(
			$this->APIHostResourcePermissions,
			$api_host,
			'api_host_access_window_error'
		);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_SECURITY,
			"Save API host access to resources. API host: $api_host"
		);
	}

	/**
	 * Check if Bacula Console ACL is assigned to given API host.
	 *
	 * @param string $api_host API host name
	 * @return bool true if console is set, otherwise false
	 */
	private function isAPIHostConsole($api_host)
	{
		$console = false;
		$host_config = $this->getModule('host_config')->getHostConfig($api_host);
		if (count($host_config) == 0) {
			// API host does not exist
			return $console;
		}

		if ($host_config['auth_type'] === AuthBasic::NAME) {
			$basic_users_result = $this->getModule('api')->get(
				['basic', 'users', $host_config['login']],
				$api_host
			);
			if ($basic_users_result->error !== 0) {
				return $console;
			}
			$console = !empty($basic_users_result->output->bconsole_cfg_path);
		} elseif ($host_config['auth_type'] === AuthOAuth2::NAME) {
			$oauth2_client_result = $this->getModule('api')->get(
				['oauth2', 'clients', $host_config['client_id']],
				$api_host
			);

			$oa2 = new OAuth2Record();
			$oa2::deleteByPk($api_host);

			if ($oauth2_client_result->error !== 0) {
				return $console;
			}
			$console = !empty($oauth2_client_result->output->bconsole_cfg_path);
		}
		return $console;
	}

	/**
	 * Set API host job list control.
	 *
	 * @param object $control control to set jobs
	 * @param string $api_host API host name
	 * @param string $error_el_id error element identifier
	 */
	private function setAPIHostJobs($control, $api_host, $error_el_id)
	{
		$cb = $this->getCallbackClient();
		$result = $this->getModule('api')->get(['jobs', 'resnames'], $api_host);
		if ($result->error === 0) {
			$res = array_values((array) $result->output);
			$jobs = array_shift($res);
			$control->DataSource = array_combine($jobs, $jobs);
			$control->dataBind();
			if ($this->isAPIHostConsole($api_host)) {
				$control->setSelectedValues($jobs);
			}
			$cb->hide($error_el_id);
		} else {
			$emsg = 'Error while loading API host resources. Please check connection with this API host. ErrorCode: %d, ErrorMsg: %s';
			$emsg = sprintf($emsg, $result->error, $result->output);
			$cb->update(
				$error_el_id,
				$emsg
			);
			$cb->show($error_el_id);
		}
	}

	/**
	 * Set API host resource permissions control.
	 *
	 * @param object $control control to set permissions
	 * @param string $api_host API host name
	 * @param string $error_el_id error element identifier
	 */
	private function setAPIHostResourcePermissions($control, $api_host, $error_el_id)
	{
		$state = false;
		$cb = $this->getCallbackClient();
		$host_config = $this->getModule('host_config')->getHostConfig($api_host);
		if (count($host_config) == 0) {
			$cb->update(
				$error_el_id,
				"API host $api_host does not exist"
			);
			$cb->show($error_el_id);
			return $state;
		}
		$result = null;
		if ($host_config['auth_type'] === AuthBasic::NAME) {
			$username = $host_config['login'];
			$result = $this->getModule('api')->get([
				'basic',
				'users',
				$username
			]);
			if ($result->error === 0) {
				$state = true;
			} else {
				$cb->update(
					$error_el_id,
					$result->output
				);
				$cb->show($error_el_id);
				return $state;
			}
		} elseif ($host_config['auth_type'] === AuthOAuth2::NAME) {
			$client_id = $host_config['client_id'];
			$result = $this->getModule('api')->get([
				'oauth2',
				'clients',
				$client_id
			]);
			if ($result->error === 0) {
				$state = true;
			} else {
				$cb->update(
					$error_el_id,
					$result->output
				);
				$cb->show($error_el_id);
				return $state;
			}
		}
		if ($state) {
			$misc = $this->getModule('misc');
			$items = $misc->objectToArray($result->output);
			$items = $misc->prepareResourcePermissionsConfig($items);
			$cb->callClientFunction(
				$control->ClientID . 'ResourcePermissions.set_user_props',
				[$items]
			);
		}
	}

	/**
	 * Save API host resource permissions.
	 *
	 * @param object $control control to set permissions
	 * @param string $api_host API host name
	 * @param string $error_el_id error element identifier
	 */
	private function saveAPIHostResourcePermissions($control, $api_host, $error_el_id)
	{
		$cb = $this->getCallbackClient();
		$host_config = $this->getModule('host_config')->getHostConfig($api_host);
		if (count($host_config) == 0) {
			$cb->update(
				$error_el_id,
				"API host $api_host does not exist"
			);
			$cb->show($error_el_id);
			return;
		}
		$result = null;
		if ($host_config['auth_type'] === AuthBasic::NAME) {
			$username = $host_config['login'];
			$result = $this->getModule('api')->get([
				'basic',
				'users',
				$username
			]);
			if ($result->error === 0) {
				$perms = $control->getPermissions();
				$user = (array) $result->output;
				$config = array_merge($user, $perms);
				$result = $this->getModule('api')->set([
					'basic',
					'users',
					$username
				], $config);
			}
		} elseif ($host_config['auth_type'] === AuthOAuth2::NAME) {
			$client_id = $host_config['client_id'];
			$result = $this->getModule('api')->get([
				'oauth2',
				'clients',
				$client_id
			]);
			if ($result->error === 0) {
				$perms = $control->getPermissions();
				$user = (array) $result->output;
				$config = array_merge($user, $perms);
				$result = $this->getModule('api')->set([
					'oauth2',
					'clients',
					$client_id
				], $config);
			}
		}
		if (is_object($result) && $result->error !== 0) {
			$cb->update(
				$error_el_id,
				$result->output
			);
			$cb->show($error_el_id);
		}
	}

	/**
	 * Set API host console.
	 *
	 * @param string $api_host API host name
	 * @param bool $set_state determine if state (all/selected resources) should be changed
	 */
	private function setAPIHostConsole($api_host, $set_state = true)
	{
		$console = $this->isAPIHostConsole($api_host);
		$cb = $this->getCallbackClient();
		if (!empty($console)) {
			$cb->show('api_host_access_window_select_jobs');
			$cb->show('api_host_access_window_console');
			if ($set_state) {
				$this->APIHostResourceAccessSelectedResources->Checked = true;
			}
		} else {
			if ($set_state) {
				$this->APIHostResourceAccessAllResources->Checked = true;
			}
		}
	}

	/**
	 * Initialize values in API host group modal window.
	 *
	 */
	public function initAPIHostGroupWindow()
	{
		// set API hosts
		$this->setAPIHosts($this->APIHostGroupAPIHosts, null, false);
	}

	/**
	 * Set and load API host groups list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setAPIHostGroupList($sender, $param)
	{
		$api_host_groups = $this->getModule('host_group_config')->getConfig();
		$shortnames = array_keys($api_host_groups);
		$attributes = array_values($api_host_groups);
		for ($i = 0; $i < count($attributes); $i++) {
			$attributes[$i]['name'] = $shortnames[$i];
			$attributes[$i]['api_hosts_str'] = implode(',', $attributes[$i]['api_hosts']);
		}

		$this->getCallbackClient()->callClientFunction('oAPIHostGroups.load_api_host_group_list_cb', [
			$attributes
		]);
	}

	/**
	 * Set resource access for given jobs.
	 * Create console with the jobs and all dependent resources (Clients, Filesets...etc)
	 *
	 * @param string $api_host api host name
	 * @param array $jobs job names
	 * @return string console name or empty string on error
	 */
	private function setJobResourceAccess($api_host, $jobs)
	{
		$result = $this->getModule('api')->get([
			'config',
			'dir',
			'Job',
			'?apply_jobdefs=1'
		], $api_host);

		$cb = $this->getCallbackClient();
		if ($result->error !== 0) {
			$cb->update(
				'api_host_access_window_error',
				$result->output
			);
			$cb->show('api_host_access_window_error');
			return '';
		}

		$acls = [
			'Name' => 'Console - ' . $api_host,
			'Password' => $this->getModule('crypto')->getRandomString(40),
			'JobAcl' => [],
			'ClientAcl' => [],
			'StorageAcl' => [],
			'FilesetAcl' => [],
			'PoolAcl' => [],
			'ScheduleAcl' => [],
			'CatalogAcl' => ['*all*'],
			'WhereAcl' => ['*all*'],
			'CommandAcl' => JobInfo::COMMAND_ACL_USED_BY_WEB
		];
		for ($i = 0; $i < count($jobs); $i++) {
			for ($j = 0; $j < count($result->output); $j++) {
				if ($result->output[$j]->Job->Name === $jobs[$i]) {
					// job
					$acls['JobAcl'][] = $result->output[$j]->Job->Name;
					// client
					if (!in_array($result->output[$j]->Job->Client, $acls['ClientAcl'])) {
						$acls['ClientAcl'][] = $result->output[$j]->Job->Client;
					}
					// storage
					$acls['StorageAcl'] = array_merge($acls['StorageAcl'], $result->output[$j]->Job->Storage);
					$acls['StorageAcl'] = array_unique($acls['StorageAcl']);
					// fileset
					if (!in_array($result->output[$j]->Job->Fileset, $acls['FilesetAcl'])) {
						$acls['FilesetAcl'][] = $result->output[$j]->Job->Fileset;
					}
					// pool
					if (!in_array($result->output[$j]->Job->Pool, $acls['PoolAcl'])) {
						$acls['PoolAcl'][] = $result->output[$j]->Job->Pool;
					}
					// schedule
					if (property_exists($result->output[$j]->Job, 'Schedule') && !in_array($result->output[$j]->Job->Schedule, $acls['ScheduleAcl'])) {
						$acls['ScheduleAcl'][] = $result->output[$j]->Job->Schedule;
					}
					break;
				}
			}
		}

		$result = $this->getModule('api')->create([
			'config',
			'dir',
			'Console',
			$acls['Name']
		], [
			'config' => json_encode($acls)
		], $api_host);

		if ($result->error === 0) {
			$this->getModule('api')->set(['console'], ['reload']);
		} elseif ($result->error === BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS) {
			// Config exists, so try to update it
			$result = $this->getModule('api')->set([
				'config',
				'dir',
				'Console',
				$acls['Name']
			], [
				'config' => json_encode($acls)
			], $api_host);
			if ($result->error === 0) {
				$this->getModule('api')->set(['console'], ['reload']);
			} else {
				$cb->update(
					'api_host_access_window_error',
					$result->output
				);
				$cb->show('api_host_access_window_error');
				return '';
			}
		} else {
			$cb->update(
				'api_host_access_window_error',
				$result->output
			);
			$cb->show('api_host_access_window_error');
			return '';
		}
		return $acls['Name'];
	}

	/**
	 * Set resource access  with Consoles for given API host.
	 * If console is not given, full access is set.
	 *
	 * @param string $api_host API host name
	 * @param string $console console name
	 * @param string $error_el_id error message container element identifier
	 * @return bool console setting state, true for success, otherwise false
	 */
	private function setResourceConsole($api_host, $console = '', $error_el_id = '')
	{
		$state = false;
		$host_config = $this->getModule('host_config')->getHostConfig($api_host);
		$cb = $this->getCallbackClient();
		if (count($host_config) == 0) {
			$cb->update(
				$error_el_id,
				"API host $api_host does not exist"
			);
			$cb->show($error_el_id);
			return $state;
		}

		$result = $this->getModule('api')->get(['directors'], $api_host);
		if ($result->error !== 0) {
			$cb->update(
				$error_el_id,
				$result->output
			);
			$cb->show($error_el_id);
			return $state;
		}
		$director = $result->output[0];
		if ($host_config['auth_type'] === AuthBasic::NAME) {
			$username = $host_config['login'];
			$config = [
				'username' => $username,
				'bconsole_cfg_path' => ''
			];
			if (!empty($console) && !empty($director)) {
				$config['console'] = $console;
				$config['director'] = $director;
			}
			$result = $this->getModule('api')->set([
				'basic',
				'users',
				$username
			], $config, $api_host);
			if ($result->error === 0) {
				$state = true;
			} else {
				$cb->update(
					$error_el_id,
					$result->output
				);
				$cb->show($error_el_id);
				return $state;
			}
		} elseif ($host_config['auth_type'] === AuthOAuth2::NAME) {
			$client_id = $host_config['client_id'];
			$config = [
				'client_id' => $client_id,
				'bconsole_cfg_path' => ''
			];
			if (!empty($console) && !empty($director)) {
				$config['console'] = $console;
				$config['director'] = $director;
			}
			$result = $this->getModule('api')->set([
				'oauth2',
				'clients',
				$client_id
			], $config, $api_host);

			$oa2 = new OAuth2Record();
			$oa2::deleteByPk($api_host);

			if ($result->error === 0) {
				$state = true;
			} else {
				$cb->update(
					$error_el_id,
					$result->output
				);
				$cb->show($error_el_id);
				return $state;
			}
		}
		return $state;
	}

	/**
	 * Unassign console from API host.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function unassignAPIHostConsole($sender, $param)
	{
		$api_host = $param->getCallbackParameter();
		$success = $this->unassignAPIHostConsoleInternal($api_host);
		if ($success) {
			$this->setAPIHostJobs(
				$this->APIHostResourceAccessJobs,
				$api_host,
				'api_host_access_window_error'
			);
			$this->setAPIHostConsole($api_host, false);
			$cb = $this->getCallbackClient();
			$cb->hide('api_host_access_window_console');
		}
	}

	/**
	 * Unassign console from API host (internal);
	 *
	 * @param string $api_host API host name
	 * @return bool true if console unassigned successfully, otherwise false
	 */
	private function unassignAPIHostConsoleInternal($api_host)
	{
		$host_config = $this->getModule('host_config')->getHostConfig($api_host);
		if (count($host_config) == 0) {
			// API host does not exist
			return;
		}

		$state = false;
		if ($host_config['auth_type'] === AuthBasic::NAME) {
			$basic_users_result = $this->getModule('api')->get(
				['basic', 'users', $host_config['login']],
				$api_host
			);
			if ($basic_users_result->error !== 0) {
				return $state;
			}
			$basic_users_result->output->bconsole_cfg_path = '';

			$basic_users_result = $this->getModule('api')->set(
				['basic', 'users', $host_config['login']],
				(array) $basic_users_result->output,
				$api_host
			);
			$state = ($basic_users_result->error === 0);
		} elseif ($host_config['auth_type'] === AuthOAuth2::NAME) {
			$oauth2_client_result = $this->getModule('api')->get(
				['oauth2', 'clients', $host_config['client_id']],
				$api_host
			);
			if ($oauth2_client_result->error !== 0) {
				return $state;
			}
			$oauth2_client_result->output->bconsole_cfg_path = '';

			$oauth2_client_result = $this->getModule('api')->set(
				['oauth2', 'clients', $host_config['client_id']],
				(array) $oauth2_client_result->output,
				$api_host
			);

			$oa2 = new OAuth2Record();
			$oa2::deleteByPk($api_host);

			$state = ($oauth2_client_result->error === 0);
		}
		return $state;
	}

	/**
	 * Load data in API host group modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIHostGroupWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();

		// prepare API host group config
		$hgc = $this->getModule('host_group_config');
		$config = $hgc->getHostGroupConfig($name);

		if (count($config) > 0) {
			$this->APIHostGroupName->Text = $name;
			$this->APIHostGroupDescription->Text = $config['description'];

			$selected_indices = [];
			$group_hosts = $config['api_hosts'];
			for ($i = 0; $i < $this->APIHostGroupAPIHosts->getItemCount(); $i++) {
				if (in_array($this->APIHostGroupAPIHosts->Items[$i]->Value, $group_hosts)) {
					$selected_indices[] = $i;
				}
			}
			$this->APIHostGroupAPIHosts->setSelectedIndices($selected_indices);
		}

	}

	/**
	 * Save API host group.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveAPIHostGroup($sender, $param)
	{
		$hgc = $this->getModule('host_group_config');
		$host_group_name = trim($this->APIHostGroupName->Text);
		$host_exists = $hgc->isHostGroupConfig($host_group_name);
		$cfg_group = [];
		$cfg_group['name'] = $host_group_name;
		$cfg_group['description'] = $this->APIHostGroupDescription->Text;

		$host_group_win_type = $this->APIHostGroupWindowType->Value;
		$this->getCallbackClient()->hide('api_host_group_window_group_exists');
		if ($host_group_win_type === self::TYPE_ADD_WINDOW) {
			if ($host_exists) {
				$this->getCallbackClient()->show('api_host_group_window_group_exists');
				return;
			}
		}

		// set API hosts config value
		$selected_indices = $this->APIHostGroupAPIHosts->getSelectedIndices();
		$api_hosts = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->APIHostGroupAPIHosts->getItemCount(); $i++) {
				if ($i === $indice) {
					$api_hosts[] = $this->APIHostGroupAPIHosts->Items[$i]->Value;
				}
			}
		}
		$cfg_group['api_hosts'] = $api_hosts;

		$config[$host_group_name] = $cfg_group;
		$result = $hgc->setHostGroupConfig($host_group_name, $cfg_group);
		$this->setAPIHostGroupList(null, null);
		$this->getCallbackClient()->hide('api_host_group_window');

		if ($result === true) {
			if (!$host_exists) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Create API host group. Name: $host_group_name"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Save API host group. Name: $host_group_name"
				);
			}
		}

		// refresh user window
		$this->initUserWindow();

		// refresh API host group window
		$this->initAPIHostGroupWindow();
	}

	/**
	 * Remove API host group action.
	 * Here is possible to remove one API host group or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeAPIHostGroups($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$hgc = $this->getModule('host_group_config');
		$result = $hgc->removeHostGroupsConfig($names);
		if ($result === true) {
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove API host group. Name: {$names[$i]}"
				);
			}
		}
		$this->setAPIHostGroupList(null, null);

		// refresh user window
		$this->initUserWindow();
	}


	/**
	 * Load API host groups access settings window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIHostGroupAPIHostResourceAccessWindow($sender, $param)
	{
		$host_group = $this->APIHostGroupAPIHostResourceAccessName->Value;
		$ahg = $this->getModule('host_group_config');
		$is_host_group = $ahg->isHostGroupConfig($host_group);
		if ($is_host_group) {
			$api_hosts = $ahg->getAPIHostsByGroups([$host_group]);
			if (count($api_hosts) == 1 && $api_hosts[0] === HostConfig::MAIN_CATALOG_HOST) {
				$host_config = $this->getModule('host_config')->getConfig();
				$api_hosts = array_keys($host_config);
			}
			// strip main API host
			$cbf = function ($host) {
				return ($host !== HostConfig::MAIN_CATALOG_HOST);
			};
			$api_hosts = array_filter($api_hosts, $cbf);
			$this->setAPIHosts($this->APIHostGroupAPIHostList, null, true, $api_hosts);
		}
	}

	/**
	 * Set API host groups access settings window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setAPIHostGroupAPIHostResourceAccessWindow($sender, $param)
	{
		$api_host = $this->APIHostGroupAPIHostList->getSelectedValue();
		$this->setAPIHostJobs(
			$this->APIHostGroupAPIHostResourceAccessJobs,
			$api_host,
			'api_host_group_access_window_error'
		);
		$this->setAPIHostGroupAPIHostConsole($api_host);
		$this->setAPIHostResourcePermissions(
			$this->APIHostGroupResourcePermissions,
			$api_host,
			'api_host_group_access_window_error'
		);
	}

	/**
	 * Set user API host console.
	 *
	 * @param string $api_host API host name
	 * @param bool $set_state determine if state (all/selected resources) should be changed
	 */
	private function setAPIHostGroupAPIHostConsole($api_host, $set_state = true)
	{
		$console = $this->isAPIHostConsole($api_host);
		$cb = $this->getCallbackClient();
		$cb->hide('api_host_group_access_window_console');
		if (!empty($console)) {
			$cb->show('api_host_group_access_window_select_jobs');
			$cb->show('api_host_group_access_window_console');
			if ($set_state) {
				$this->APIHostGroupAPIHostResourceAccessSelectedResources->Checked = true;
			}
		} else {
			if ($set_state) {
				$cb->hide('api_host_group_access_window_select_jobs');
				$this->APIHostGroupAPIHostResourceAccessAllResources->Checked = true;
			}
		}
	}

	/**
	 * Unassign console from API host.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function unassignAPIHostGroupAPIHostConsole($sender, $param)
	{
		$api_host = $param->getCallbackParameter();
		$success = $this->unassignAPIHostConsoleInternal($api_host);
		if ($success) {
			$this->setAPIHostJobs(
				$this->APIHostGroupAPIHostResourceAccessJobs,
				$api_host,
				'api_host_group_access_window_error'
			);
			$this->setAPIHostGroupAPIHostConsole($api_host, false);
			$cb = $this->getCallbackClient();
			$cb->hide('api_host_group_access_window_console');
		}
	}

	/**
	 * Save API host groups access settings window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveAPIHostGroupAPIHostResourceAccess($sender, $param)
	{
		$api_host = $this->APIHostGroupAPIHostList->getSelectedValue();
		if ($this->APIHostGroupAPIHostResourceAccessAllResources->Checked) {
			$state = $this->setResourceConsole(
				$api_host,
				'',
				'api_host_group_access_window_error'
			);
			if ($state) {
				$this->setAPIHostJobs(
					$this->APIHostGroupAPIHostResourceAccessJobs,
					$api_host,
					'api_host_group_access_window_error'
				);
				$this->setAPIHostConsole($api_host);
			}
		} elseif ($this->APIHostGroupAPIHostResourceAccessSelectedResources->Checked) {
			$selected_indices = $this->APIHostGroupAPIHostResourceAccessJobs->getSelectedIndices();
			$jobs = [];
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->APIHostGroupAPIHostResourceAccessJobs->getItemCount(); $i++) {
					if ($i === $indice) {
						$jobs[] = $this->APIHostGroupAPIHostResourceAccessJobs->Items[$i]->Value;
					}
				}
			}
			$console = $this->setJobResourceAccess($api_host, $jobs);
			if ($console) {
				$state = $this->setResourceConsole(
					$api_host,
					$console,
					'api_host_group_access_window_error'
				);
				if ($state) {
					$this->setAPIHostJobs(
						$this->APIHostGroupAPIHostResourceAccessJobs,
						$api_host,
						'api_host_group_access_window_error'
					);
				}
				$this->setAPIHostGroupAPIHostConsole($api_host);
			}
		}
		$this->saveAPIHostResourcePermissions(
			$this->APIHostGroupResourcePermissions,
			$api_host,
			'api_host_group_access_window_error'
		);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_SECURITY,
			"Save API host group access to resources. API host: $api_host"
		);
	}
}
