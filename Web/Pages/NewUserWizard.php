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

use Prado\Prado;
use Prado\Exceptions\TNotSupportedException;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\JobInfo;
use Bacularis\Web\Modules\OAuth2Record;
use Bacularis\Web\Modules\WebUserConfig;

/**
 * New user wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewUserWizard extends BaculumWebPage
{
	public const PREV_STEP = 'PrevStep';

	public function onLoad($param)
	{
		parent::onLoad($param);

		if ($this->IsPostBack || $this->IsCallback) {
			return;
		}
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->IsCallBack) {
			return;
		}
		$step_index = $this->NewJobWizard->getActiveStepIndex();
		$prev_step = $this->getPrevStep();
		$this->setPrevStep($step_index);
		if ($prev_step > $step_index) {
			return;
		}
		switch ($step_index) {
			case 0:	{
				break;
			}
			case 1:	{
				$this->setRoles();
				$this->setRoleWindow();
				break;
			}
			case 2: {
				$this->setAPIHosts();
				$this->setAPIHostGroups();
				break;
			}
			case 3: {
				break;
			}
			case 4: {
				break;
			}
		}
	}

	/**
	 * Set role list control.
	 *
	 * @param null|mixed $selected_value
	 */
	private function setRoles($selected_value = null)
	{
		// Selected values
		$selected = $this->getMultiSelectValues($this->UserRoles);

		// set roles
		$role_items = [];
		$roles = $this->getModule('user_role')->getRoles();
		$roles_orig = array_keys($roles);
		natcasesort($roles_orig);
		$roles_sort = array_values($roles_orig);
		for ($i = 0; $i < count($roles_sort); $i++) {
			$role_items[$roles_sort[$i]] = $roles[$roles_sort[$i]]['long_name'] ?: $roles_sort[$i];
		}

		// Update indices because there can be indice shift (added new indice(s))
		$selected_indices = [];
		$i = 0;
		foreach ($role_items as $role_name => $name) {
			if (in_array($role_name, $selected) || $role_name === $selected_value) {
				$selected_indices[] = $i;
			}
			$i++;
		}

		$this->UserRoles->DataSource = $role_items;
		if (count($selected_indices) > 0) {
			$this->UserRoles->setSelectedIndices($selected_indices);
		}
		$this->UserRoles->dataBind();
	}

	/**
	 * Save role.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRole($sender, $param)
	{
		$role = $this->Role->Text;
		$this->getCallbackClient()->hide('role_window_role_exists');
		$config = $this->getModule('user_role')->getRole($role);
		if (count($config) > 0) {
			$this->getCallbackClient()->show('role_window_role_exists');
			return;
		}
		$config = [];
		$config['long_name'] = $this->RoleLongName->Text;
		$config['description'] = $this->RoleDescription->Text;

		$selected = $this->getMultiSelectValues($this->RoleResources);
		$config['resources'] = implode(',', $selected);
		$config['enabled'] = $this->RoleEnabled->Checked ? 1 : 0;
		$this->getModule('role_config')->setRoleConfig($role, $config);
		$this->setRoles($role);
		$this->getCallbackClient()->callClientFunction('oRoles.save_role_cb');
	}

	/**
	 * Initialize values in role modal window.
	 *
	 */
	public function setRoleWindow()
	{
		// set role resources
		$resources = $this->getModule('page_category')->getCategories(false);
		$this->RoleResources->DataSource = array_combine($resources, $resources);
		$this->RoleResources->dataBind();
	}

	/**
	 * Set API host list - generic method.
	 *
	 * @param object $control control object
	 * @param null|mixed $selected_value
	 */
	private function setAPIHostControl($control, $selected_value = null)
	{
		// get existing selection (if any)
		$selected = $this->getMultiSelectValues($control);

		$host_config = $this->getModule('host_config')->getConfig();
		$api_hosts_orig = array_keys($host_config);
		natcasesort($api_hosts_orig);
		$api_hosts = array_values($api_hosts_orig);

		// Update indices because there can be indice shift (added new indice(s))
		$selected_indices = [];
		for ($i = 0; $i < count($api_hosts); $i++) {
			if (in_array($api_hosts[$i], $selected) || $api_hosts[$i] === $selected_value) {
				$selected_indices[] = $i;
			}
		}

		$control->DataSource = array_combine($api_hosts, $api_hosts);
		if (count($selected_indices) > 0) {
			try {
				$control->setSelectedIndices($selected_indices);
			} catch (TNotSupportedException $e) {
				// for single value TDropDownList the SelectedIndices is read-only
				try {
					$control->setSelectedValue($selected_value);
				} catch (Exception $e) {
				}
			}
		}
		$control->dataBind();
	}

	/**
	 * Set API host group list - generic method.
	 *
	 * @param object $control control object
	 * @param null|mixed $selected_value
	 */
	private function setAPIHostGroupControl($control, $selected_value = null)
	{
		// get existing selection (if any)
		$selected = $this->getMultiSelectValues($control);

		$host_group_config = $this->getModule('host_group_config')->getConfig();
		$api_host_groups_orig = array_keys($host_group_config);
		natcasesort($api_host_groups_orig);
		$api_host_groups = array_values($api_host_groups_orig);

		// Update indices because there can be indice shift (added new indice(s))
		$selected_indices = [];
		for ($i = 0; $i < count($api_host_groups); $i++) {
			if (in_array($api_host_groups[$i], $selected) || $api_host_groups[$i] === $selected_value) {
				$selected_indices[] = $i;
			}
		}

		$control->DataSource = array_combine($api_host_groups, $api_host_groups);
		if (count($selected_indices) > 0) {
			try {
				$control->setSelectedIndices($selected_indices);
			} catch (TNotSupportedException $e) {
				// for single value TDropDownList the SelectedIndices is read-only
				try {
					$control->setSelectedValue($selected_value);
				} catch (Exception $e) {
				}
			}
		}
		$control->dataBind();
	}

	/**
	 * Special treating API hosts validator that needs
	 * to be enabled/disabled depending on selected option.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function checkAPIHostsValidator($sender, $param)
	{
		$sender->enabled = $this->APIHostsOpt->Checked;
	}

	/**
	 * Special treating API host groups validator that needs
	 * to be enabled/disabled depending on selected option.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function checkAPIHostGroupsValidator($sender, $param)
	{
		$sender->enabled = $this->APIHostGroupsOpt->Checked;
	}

	/**
	 * Set API host list control.
	 *
	 * @param string $selected_value selected value
	 */
	private function setAPIHosts($selected_value = null)
	{
		// Set API hosts in main API host list
		$this->setAPIHostControl($this->UserAPIHosts, $selected_value);

		// Set API hosts in API new host group window
		$this->setAPIHostControl($this->NewAPIHostGroupAPIHosts, $selected_value);
	}

	/**
	 * Set API host group list control.
	 *
	 * @param string $selected_value selected value
	 */
	private function setAPIHostGroups($selected_value = null)
	{
		$this->setAPIHostGroupControl($this->UserAPIHostGroups, $selected_value);
	}

	/**
	 * Load admin API host list used for creating other API host accounts.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param parameter object
	 */
	public function loadAdminAPIHosts($sender, $param)
	{
		$this->setAPIHostControl($this->AdminAPIHost, 'Main');
	}

	/**
	 * Create API host on the API host side and on Web side as well.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function createAPIHost($sender, $param)
	{
		$api_hosts = $this->getModule('host_config')->getConfig();
		$admin_api_host = $this->AdminAPIHost->getSelectedValue();
		if (!key_exists($admin_api_host, $api_hosts)) {
			return;
		}

		// Prepare parameters for API host config
		$api_host = $api_hosts[$admin_api_host];
		$new_web = [
			'auth_type' => '',
			'login' => '',
			'password' => '',
			'client_id' => '',
			'client_secret' => '',
			'redirect_uri' => '',
			'scope' => ''
		];
		$new_web['protocol'] = $api_host['protocol'];
		$new_web['address'] = $api_host['address'];
		$new_web['port'] = $api_host['port'];
		$new_web['url_prefix'] = '';
		$new_web['auth_type'] = $api_host['auth_type'];
		$new_api['name'] = $name = trim($this->NewAPIHostName->Text);
		$crypto = $this->getModule('crypto');
		if ($api_host['auth_type'] === 'basic') {
			// Sanitize basic user string
			$basic_user = preg_replace('/[^a-zA-Z0-9]+/', '', $name);
			if (empty($basic_user)) {
				// if user empty, generate user name
				$basic_user = 'user' . $crypto->getRandomString(10);
			}
			$new_api['username'] = $new_web['login'] = $basic_user;
			$new_api['password'] = $new_web['password'] = $crypto->getRandomString(40);
			$new_api['bconsole_cfg_path'] = '';
		} elseif ($api_host['auth_type'] === 'oauth2') {
			$new_api['client_id'] = $new_web['client_id'] = $crypto->getRandomString(32);
			$new_api['client_secret'] = $new_web['client_secret'] = $crypto->getRandomString(50);
			$new_api['redirect_uri'] = $new_web['redirect_uri'] = $api_host['redirect_uri'];
			$new_api['scope'] = $new_web['scope'] = 'console jobs directors clients storages devices volumes pools bvfs joblog filesets schedules config oauth2';
		}


		$result = new StdClass();
		$result->error = -1;
		$result->output = 'Internal error';

		// save API host config on API side
		if ($api_host['auth_type'] === 'basic') {
			$result = $this->getModule('api')->create([
				'basic',
				'users',
				$new_api['username']
			], $new_api, $admin_api_host);
		} elseif ($api_host['auth_type'] === 'oauth2') {
			$result = $this->getModule('api')->create([
				'oauth2',
				'clients',
				$new_api['client_id']
			], $new_api, $admin_api_host);
		}

		if ($result->error === 0) {
			// save API host config on Web side
			$host_config = $this->getModule('host_config');
			$config = $host_config->getConfig();
			if (empty($name)) {
				$name = $api_host['address'];
			}
			$config[$name] = $new_web;
			$result = $host_config->setConfig($config);
			if ($result !== true) {
				// Error
				$this->getCallbackClient()->update(
					$this->NewAPIHostError,
					'Error while creating API account'
				);
				$this->NewAPIHostError->Display = 'Dynamic';
			} else {
				$this->setAPIHosts($name);
				// Everything fine - close window
				$this->getCallbackClient()->callClientFunction(
					'oAPIHosts.show_new_api_host_window',
					[false]
				);
			}
		} else {
			// Error
			$this->getCallbackClient()->update(
				$this->NewAPIHostError,
				$result->output
			);
			$this->NewAPIHostError->Display = 'Dynamic';
		}
	}

	/**
	 * Create API host group.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function createAPIHostGroup($sender, $param)
	{
		$hgc = $this->getModule('host_group_config');
		$host_group_name = trim($this->NewAPIHostGroupName->Text);
		$host_exists = $hgc->isHostGroupConfig($host_group_name);
		$cfg_group = [];
		$cfg_group['name'] = $host_group_name;
		$cfg_group['description'] = $this->NewAPIHostGroupDescription->Text;

		if ($host_exists) {
			$this->getCallbackClient()->show('api_host_group_window_group_exists');
			return;
		}

		// set API hosts config value
		$selected_indices = $this->NewAPIHostGroupAPIHosts->getSelectedIndices();
		$api_hosts = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->NewAPIHostGroupAPIHosts->getItemCount(); $i++) {
				if ($i === $indice) {
					$api_hosts[] = $this->NewAPIHostGroupAPIHosts->Items[$i]->Value;
				}
			}
		}
		$cfg_group['api_hosts'] = $api_hosts;

		$config[$host_group_name] = $cfg_group;
		$result = $hgc->setHostGroupConfig($host_group_name, $cfg_group);
		$this->getCallbackClient()->hide('new_api_host_group_window');

		if ($result === true) {
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"Create API host group. Name: $host_group_name"
			);
			$this->setAPIHostGroups($host_group_name);
		} else {
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				"Error while creating API host group. Name: $host_group_name"
			);
			$this->getCallbackClient()->update(
				$this->NewAPIHostGroupError,
				$result->output
			);
			$this->NewAPIHostGroupError->Display = 'Dynamic';
		}
	}

	/**
	 * Set resource API host list control.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter object
	 */
	public function setAPIHostList($sender, $param)
	{
		$api_hosts = $this->getAPIHostsWithConsoles();
		$this->getCallbackClient()->callClientFunction(
			'oAPIHostList.update',
			[$api_hosts]
		);
	}

	/**
	 * Get API host list with assigned console (if any)
	 *
	 * @return array api hosts with consoles
	 */
	private function getAPIHostsWithConsoles()
	{
		$host_config = $this->getModule('host_config')->getConfig();
		$ahg = $this->getModule('host_group_config');
		$basic_users_result = $this->getModule('api')->get(['basic', 'users']);
		if ($basic_users_result->error !== 0) {
			return;
		}
		$basic_users = $basic_users_result->output;

		$oauth2_clients_result = $this->getModule('api')->get(['oauth2', 'clients']);
		if ($oauth2_clients_result->error !== 0) {
			return;
		}
		$oauth2_clients = $oauth2_clients_result->output;

		$hosts = [];
		$groups = [];
		if ($this->APIHostsOpt->Checked) {
			$selected_indices = $this->UserAPIHosts->getSelectedIndices();
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->UserAPIHosts->getItemCount(); $i++) {
					if ($i === $indice) {
						$hosts[] = $this->UserAPIHosts->Items[$i]->Value;
					}
				}
			}
		} elseif ($this->APIHostGroupsOpt->Checked) {
			$selected_indices = $this->UserAPIHostGroups->getSelectedIndices();
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->UserAPIHostGroups->getItemCount(); $i++) {
					if ($i === $indice) {
						$groups[] = $this->UserAPIHostGroups->Items[$i]->Value;
					}
				}
			}
			$hosts = $ahg->getAPIHostsByGroups($groups);
		}

		// the same API host can be in many API groups, so consider only unique API hosts
		$hosts = array_unique($hosts);
		// reindexing after making unique
		$hosts = array_values($hosts);

		$api_hosts = [];
		for ($i = 0; $i < count($hosts); $i++) {
			if (!key_exists($hosts[$i], $host_config)) {
				continue;
			}
			$console = false;
			if ($host_config[$hosts[$i]]['auth_type'] == 'basic') {
				for ($j = 0; $j < count($basic_users); $j++) {
					if ($host_config[$hosts[$i]]['login'] === $basic_users[$j]->username) {
						$console = !empty($basic_users[$j]->bconsole_cfg_path);
						break;
					}
				}
			} elseif ($host_config[$hosts[$i]]['auth_type'] == 'oauth2') {
				for ($j = 0; $j < count($oauth2_clients); $j++) {
					if ($host_config[$hosts[$i]]['client_id'] === $oauth2_clients[$j]->client_id) {
						$console = !empty($oauth2_clients[$j]->bconsole_cfg_path);
						break;
					}
				}
			}
			$api_hosts[] = [
				'api_host' => $hosts[$i],
				'console' => $console
			];
		}
		return $api_hosts;
	}

	/**
	 * Set API host job list control.
	 *
	 * @param string $api_host API host name
	 */
	private function setAPIHostJobs($api_host)
	{
		$result = $this->getModule('api')->get(['jobs', 'resnames'], $api_host);
		if ($result->error === 0) {
			$res = array_values((array) $result->output);
			$jobs = array_shift($res);
			$this->APIHostJobs->DataSource = array_combine($jobs, $jobs);
			$this->APIHostJobs->dataBind();
		}
	}

	/**
	 * Set API host console list control.
	 *
	 * @param null|TCallback $sender sender object
	 * @param null|TCallbackEventParameter parameter object
	 * @param mixed $param
	 */
	public function setAPIHostConsoles($sender, $param)
	{
		// get existing selection (if any)
		$selected = '';
		if (is_object($param)) {
			$selected = $param->getCallbackParameter();
		} else {
			$selected = $this->APIHostConsoles->getSelectedValue();
		}

		// get new consoles from API host
		$consoles = [];
		$result = $this->getModule('api')->get(['config', 'dir', 'Console']);
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$consoles[] = $result->output[$i]->Console->Name;
			}
		}

		$this->APIHostConsoles->DataSource = array_combine($consoles, $consoles);
		if ($selected) {
			$this->APIHostConsoles->setSelectedValue($selected);
		}
		$this->APIHostConsoles->dataBind();
	}

	/**
	 * Set custom console control.
	 *
	 * @param mixed $api_host
	 */
	private function setAPIHostCustomConsole($api_host)
	{
		$this->ConsoleConfig->setHost($api_host);
		$this->ConsoleConfig->setComponentName($_SESSION['dir']);
		$this->ConsoleConfig->setLoadValues(false);
		$this->ConsoleConfig->IsDirectiveCreated = false;
		$this->ConsoleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
	}

	/**
	 * Load API host resources list control.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadConsoleWindow($sender, $param)
	{
		$api_host = $param->getCallbackParameter();
		$this->setAPIHostJobs($api_host);
		$this->setAPIHostConsoles(null, null);
		$this->setAPIHostCustomConsole($api_host);
	}

	/**
	 * Set all possible command ACLs used by Bacularis Web.
	 * It is used in the new console window.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setAllCommandAcls($sender, $param)
	{
		$config = (object) [
			"CommandAcl" => JobInfo::COMMAND_ACL_USED_BY_WEB
		];
		$this->ConsoleConfig->loadConfig($sender, $param, 'ondirectivelistload', $config);
		$this->getCallbackClient()->callClientFunction('oBaculaConfigSection.show_sections', [true]);
	}

	/**
	 * Set access to resources for specific API host.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setResourceAccess($sender, $param)
	{
		$api_host = $this->AccessWindowAPIHost->Value;
		if ($this->SelectedJobs->Checked) {
			$selected = $this->getMultiSelectValues($this->APIHostJobs);
			$this->setJobResourceAccess($api_host, $selected);
		} elseif ($this->ExistingConsole->Checked) {
			$this->setExistingConsole($api_host);
		} elseif ($this->FullAccess->Checked) {
			$this->setFullAccess($api_host);
		}
		$this->setAPIHostList(null, null);
	}

	/**
	 * Set resource access for given jobs.
	 * Create console with the jobs and all dependent resources (Clients, Filesets...etc)
	 *
	 * @param string $api_host api host name
	 * @param array $jobs job names
	 */
	private function setJobResourceAccess($api_host, $jobs)
	{
		$result = $this->getModule('api')->get([
			'config',
			'dir',
			'Job',
			'?apply_jobdefs=1'
		], $api_host);

		if ($result->error !== 0) {
			$this->getCallbackClient()->update(
				$this->NewAPIHostConsoleError,
				$result->output
			);
			$this->NewAPIHostConsoleError->Display = 'Dynamic';
			return;
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
			$this->setResourceConsole($api_host, $acls['Name']);
		} else {
			$this->getCallbackClient()->update(
				$this->NewAPIHostConsoleError,
				$result->output
			);
			$this->NewAPIHostConsoleError->Display = 'Dynamic';
		}
	}

	/**
	 * Set full resource access for given API host.
	 *
	 * @param string $api_host API host name
	 */
	private function setFullAccess($api_host)
	{
		$this->setResourceConsole($api_host);
	}

	/**
	 * Set existing console for given API host.
	 *
	 * @param string $api_host API host name
	 */
	private function setExistingConsole($api_host)
	{
		$console = $this->APIHostConsoles->getSelectedValue();
		$this->setResourceConsole($api_host, $console);
	}

	/**
	 * Set resource access  with Consoles for given API host.
	 * If console is not given, full access is set.
	 *
	 * @param string $api_host API host name
	 * @param string $console console name
	 */
	private function setResourceConsole($api_host, $console = '')
	{
		$host_config = $this->getModule('host_config')->getConfig();
		if (!key_exists($api_host, $host_config)) {
			$this->getCallbackClient()->update(
				$this->NewAPIHostConsoleError,
				"API host $api_host does not exist"
			);
			$this->NewAPIHostConsoleError->Display = 'Dynamic';
		} else {
			$result = $this->getModule('api')->get(['directors'], $api_host);
			if ($result->error !== 0) {
				$this->getCallbackClient()->update(
					$this->NewAPIHostConsoleError,
					$result->output
				);
				$this->NewAPIHostConsoleError->Display = 'Dynamic';
				return;
			}
			$director = $result->output[0];
			if ($host_config[$api_host]['auth_type'] === 'basic') {
				$username = $host_config[$api_host]['login'];
				$config = [
					'username' => $username
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
				if ($result->error !== 0) {
					$this->getCallbackClient()->update(
						$this->NewAPIHostConsoleError,
						$result->output
					);
					$this->NewAPIHostConsoleError->Display = 'Dynamic';
					return;
				}
			} elseif ($host_config[$api_host]['auth_type'] === 'oauth2') {
				$client_id = $host_config[$api_host]['client_id'];
				$client_secret = $host_config[$api_host]['client_secret'];
				$redirect_uri = $host_config[$api_host]['redirect_uri'];
				$scope = $host_config[$api_host]['scope'];
				$config = [
					'client_id' => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri' => $redirect_uri,
					'scope' => $scope,
					'bconsole_cfg_path' => '',
					'name' => $api_host
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

				/**
				 * Remove token information because now the API host has new console assigned.
				 * To apply the new console config, current token has to discarded (removed).
				 */
				$oa2 = new OAuth2Record();
				$oa2::deleteByPk($api_host);

				if ($result->error !== 0) {
					$this->getCallbackClient()->update(
						$this->NewAPIHostConsoleError,
						$result->output
					);
					$this->NewAPIHostConsoleError->Display = 'Dynamic';
					return;
				}
			}
			$this->getCallbackClient()->callClientFunction(
				'oConsoleWindow.show',
				[false]
			);
		}
	}

	/**
	 * Create user.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveUser()
	{
		$username = $this->Username->Value;
		$config = $this->getModule('user_config')->getUserConfig($username);
		if (count($config) > 0) {
			return false;
		}

		$config = [];
		$config['long_name'] = '';
		$config['description'] = '';
		$config['email'] = '';

		// set roles config values
		$roles = $this->getMultiSelectValues($this->UserRoles);
		$config['roles'] = implode(',', $roles);

		// set API hosts config values
		$api_hosts = $this->getMultiSelectValues($this->UserAPIHosts);
		$config['api_hosts'] = $api_hosts;
		$api_host_groups = $this->getMultiSelectValues($this->UserAPIHostGroups);
		$config['api_host_groups'] = $api_host_groups;
		if ($this->APIHostsOpt->Checked) {
			$config['api_hosts_method'] = WebUserConfig::API_HOST_METHOD_HOSTS;
		} elseif ($this->APIHostGroupsOpt->Checked) {
			$config['api_hosts_method'] = WebUserConfig::API_HOST_METHOD_HOST_GROUPS;
		}
		$config['ips'] = '';
		$config['enabled'] = 1;
		$result = $this->getModule('user_config')->setUserConfig($username, $config);

		// Set password if auth method supports it
		if ($result === true && !empty($this->UserPassword->Value) && $this->isManageUsersAvail()) {
			$basic = $this->getModule('basic_webuser');
			if ($this->getModule('web_config')->isAuthMethodLocal()) {
				$basic->setUsersConfig(
					$username,
					$this->UserPassword->Value
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
					$this->UserPassword->Value,
					false,
					null,
					$opts
				);
			}
		}

		if ($result === true) {
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Create new user. User: $username"
			);
		}
	}

	/**
	 * Determines if user management is enabled.
	 * This checking bases on selected auth method and permission to manage users.
	 *
	 * @return bool true if managing users is enabled, otherwise false
	 */
	public function isManageUsersAvail()
	{
		$is_local = $this->getModule('web_config')->isAuthMethodLocal();
		$is_basic = $this->getModule('web_config')->isAuthMethodBasic();
		$allow_manage_users = (isset($this->web_config['auth_basic']['allow_manage_users']) &&
			$this->web_config['auth_basic']['allow_manage_users'] == 1);
		return (($is_basic && $allow_manage_users) || $is_local);
	}

	public function wizardCompleted($sender, $param)
	{
		$this->saveUser();
		$this->wizardStop(null, null);
	}

	public function wizardStop($sender, $param)
	{
		$this->goToPage('Security', null, 'user_list');
	}

	/**
	 * Set previous wizard step.
	 *
	 * @param int $step previous step number
	 */
	public function setPrevStep($step)
	{
		$step = (int) $step;
		$this->setViewState(self::PREV_STEP, $step);
	}

	/**
	 * Get previous wizard step.
	 *
	 * @return int previous wizard step
	 */
	public function getPrevStep()
	{
		return $this->getViewState(self::PREV_STEP);
	}

	/**
	 * Helper method to get multi-select control values.
	 *
	 * @param object $control multi-select control
	 * @return array selected values
	 */
	public function getMultiSelectValues($control)
	{
		$selected_indices = $control->getSelectedIndices();
		$selected = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $control->getItemCount(); $i++) {
				if ($i === $indice) {
					$selected[] = $control->Items[$i]->Value;
				}
			}
		}
		return $selected;
	}

	/**
	 * Get API hosts with consoles summary to display in the wizard summary step.
	 *
	 * @return string API hosts with consoles summary
	 */
	public function getAPIHostsWithConsolesSummary()
	{
		$result = [];
		$api_hosts = $this->getAPIHostsWithConsoles();
		for ($i = 0; $i < count($api_hosts); $i++) {
			$result[] = Prado::localize('API host:') . ' ' . $api_hosts[$i]['api_host'] . ', ' . Prado::localize('Console:') . ' ' . ($api_hosts[$i]['console'] ? Prado::localize('Yes') : Prado::localize('No'));
		}
		return implode('<br />', $result);
	}
}
