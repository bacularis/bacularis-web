<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\Web\Portlets;

use Bacularis\Common\Modules\AuthOAuth2;
use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\Errors\BaculaConfigError;
use Bacularis\Web\Modules\JobInfo;
use Bacularis\Web\Modules\OAuth2Record;

/**
 * Security module providing common interface for security portlets.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class Security extends Portlets
{
	public $web_config;

	/**
	 * Initialize page.
	 *
	 * @param mixed $param oninit event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		$this->web_config = $this->getPage()->web_config;
	}

	/**
	 * Set role list control.
	 *
	 * @param object $control control which contains role list
	 * @param mixed $def_val default value or null if no default value to set
	 */
	protected function setRoles($control, $def_val = null)
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
	protected function setAPIHosts($control, $def_val = null, $add_blank_item = true, $sel_api_hosts = null)
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
	 * Set organization list control.
	 *
	 * @param object $control control which contains organization list
	 * @param mixed $def_val default value or null if no default value to set
	 * @param mixed $add_blank_item
	 */
	protected function setAPIHostGroups($control, $def_val = null)
	{
		$host_group_config = $this->getModule('host_group_config');
		$api_host_groups = array_keys($host_group_config->getConfig());
		natcasesort($api_host_groups);
		$control->DataSource = array_combine($api_host_groups, $api_host_groups);
		if ($def_val) {
			$control->SelectedValue = $def_val;
		}
		$control->dataBind();
	}

	/**
	 * Set organization list control.
	 *
	 * @param object $control control which contains organization list
	 * @param mixed $def_val default value or null if no default value to set
	 * @param bool $add_blank_item determines if add first blank item
	 */
	protected function setOrganizations($control, $def_val = null, $add_blank_item = true)
	{
		$org_config = $this->getModule('org_config');
		$orgs = $org_config->getConfig();
		$org_items = [];
		if ($add_blank_item) {
			$org_items = ['' => ''];
		}
		foreach ($orgs as $org_name => $org) {
			$org_items[$org_name] = $org['full_name'] ?: $org_name;
		}
		uasort($org_items, 'strnatcasecmp');
		$control->DataSource = $org_items;
		if ($def_val) {
			$control->SelectedValue = $def_val;
		}
		$control->dataBind();
	}

	/**
	 * Determines if user management is enabled.
	 * This checking bases on selected auth method and permission to manage users.
	 *
	 * @return bool true if managing users is enabled, otherwise false
	 */
	protected function isManageUsersAvail()
	{
		$web_config = $this->getModule('web_config');
		$is_local = $web_config->isAuthMethodLocal();
		$is_basic = $web_config->isAuthMethodBasic();
		$allow_manage_users = (isset($this->web_config['auth_basic']['allow_manage_users']) &&
			$this->web_config['auth_basic']['allow_manage_users'] == 1);
		return (($is_basic && $allow_manage_users) || $is_local);
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
	protected function trimIps($ips)
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
	 * Check if Bacula Console ACL is assigned to given API host.
	 *
	 * @param string $api_host API host name
	 * @return bool true if console is set, otherwise false
	 */
	protected function isAPIHostConsole($api_host)
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
	protected function setAPIHostJobs($control, $api_host, $error_el_id)
	{
		$cb = $this->getPage()->getCallbackClient();
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
	protected function setAPIHostResourcePermissions($control, $api_host, $error_el_id)
	{
		$state = false;
		$cb = $this->getPage()->getCallbackClient();
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
	protected function saveAPIHostResourcePermissions($control, $api_host, $error_el_id)
	{
		$cb = $this->getPage()->getCallbackClient();
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
	 * Set resource access for given jobs.
	 * Create console with the jobs and all dependent resources (Clients, Filesets...etc)
	 *
	 * @param string $api_host api host name
	 * @param array $jobs job names
	 * @param string $error_el_id error element identifier
	 * @return string console name or empty string on error
	 */
	protected function setJobResourceAccess($api_host, $jobs, $error_el_id)
	{
		$api = $this->getModule('api');
		$result = $api->get([
			'config',
			'dir',
			'Job',
			'?apply_jobdefs=1'
		], $api_host);

		$cb = $this->getPage()->getCallbackClient();
		if ($result->error !== 0) {
			$cb->update(
				$error_el_id,
				$result->output
			);
			$cb->show($error_el_id);
			return '';
		}

		$acls_base = [
			'Name' => 'Console - ' . $api_host,
			'Password' => $this->getModule('crypto')->getRandomString(40),
			'JobAcl' => [],
			'ClientAcl' => [],
			'StorageAcl' => [],
			'FilesetAcl' => [],
			'PoolAcl' => [],
			'ScheduleAcl' => []
		];
		$to_new_acls = [
			'CatalogAcl' => ['*all*'],
			'WhereAcl' => ['*all*'],
			'CommandAcl' => JobInfo::COMMAND_ACL_USED_BY_WEB
		];
		for ($i = 0; $i < count($jobs); $i++) {
			for ($j = 0; $j < count($result->output); $j++) {
				if ($result->output[$j]->Job->Name === $jobs[$i]) {
					// job
					$acls_base['JobAcl'][] = $result->output[$j]->Job->Name;
					// client
					if (!in_array($result->output[$j]->Job->Client, $acls_base['ClientAcl'])) {
						$acls_base['ClientAcl'][] = $result->output[$j]->Job->Client;
					}
					// storage
					$acls_base['StorageAcl'] = array_merge($acls_base['StorageAcl'], $result->output[$j]->Job->Storage);
					$acls_base['StorageAcl'] = array_unique($acls_base['StorageAcl']);
					// fileset
					if (!in_array($result->output[$j]->Job->Fileset, $acls_base['FilesetAcl'])) {
						$acls_base['FilesetAcl'][] = $result->output[$j]->Job->Fileset;
					}
					// pool
					if (!in_array($result->output[$j]->Job->Pool, $acls_base['PoolAcl'])) {
						$acls_base['PoolAcl'][] = $result->output[$j]->Job->Pool;
					}
					// schedule
					if (property_exists($result->output[$j]->Job, 'Schedule') && !in_array($result->output[$j]->Job->Schedule, $acls_base['ScheduleAcl'])) {
						$acls_base['ScheduleAcl'][] = $result->output[$j]->Job->Schedule;
					}
					break;
				}
			}
		}

		// Add default fields for new Console ACL
		$acls = array_merge($acls_base, $to_new_acls);

		$result = $api->create([
			'config',
			'dir',
			'Console',
			$acls_base['Name']
		], [
			'config' => json_encode($acls)
		], $api_host);

		if ($result->error === 0) {
			$api->set(['console'], ['reload']);
		} elseif ($result->error === BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS) {
			// Config exists, so try to update it
			$result = $api->get([
				'config',
				'dir',
				'Console',
				$acls_base['Name']
			], $api_host);

			if ($result->error === 0) {
				$console = json_decode(json_encode($result->output), true);

				// overwrite base console directives (JobAcl, ClientAcl, StorageAcl...etc) using new values
				foreach ($acls_base as $directive_name => $directive_value) {
					$console[$directive_name] = $directive_value;
				}

				$result = $api->set([
					'config',
					'dir',
					'Console',
					$acls_base['Name']
				], [
					'config' => json_encode($console)
				], $api_host);

				if ($result->error === 0) {
					$api->set(['console'], ['reload']);
				}
			}
		}

		$ret = $acls_base['Name'];
		if ($result->error != 0) {
			$cb->update(
				$error_el_id,
				$result->output
			);
			$cb->show($error_el_id);
			$ret = '';
		}
		return $ret;
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
	protected function setResourceConsole($api_host, $console = '', $error_el_id = '')
	{
		$state = false;
		$host_config = $this->getModule('host_config')->getHostConfig($api_host);
		$cb = $this->getPage()->getCallbackClient();
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
	 * Unassign console from API host (internal);
	 *
	 * @param string $api_host API host name
	 * @return bool true if console unassigned successfully, otherwise false
	 */
	protected function unassignAPIHostConsoleInternal($api_host)
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
}
