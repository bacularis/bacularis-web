<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\HostConfig;

/**
 * API host group list module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class APIHostGroups extends Security
{
	/**
	 * Modal window types for users and roles.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Initialize module.
	 *
	 * @param mixed $param oninit event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getPage()->IsCallBack || $this->getPage()->IsPostBack) {
			return;
		}
		$this->initAPIHostGroupWindow();
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

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oAPIHostGroups.load_api_host_group_list_cb', [
			$attributes
		]);
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
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide('api_host_group_window_group_exists');
		if ($host_group_win_type === self::TYPE_ADD_WINDOW) {
			if ($host_exists) {
				$cb->show('api_host_group_window_group_exists');
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
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide('api_host_group_window');

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

		// refresh API host group window
		$this->initAPIHostGroupWindow();

		$this->onSaveAPIHostGroup(null);
	}

	/**
	 * On save API host group event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveAPIHostGroup($param)
	{
		$this->raiseEvent('OnSaveAPIHostGroup', $this, $param);
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

		$this->onRemoveAPIHostGroup(null);
	}

	/**
	 * On remove API host group event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveAPIHostGroup($param)
	{
		$this->raiseEvent('OnRemoveAPIHostGroup', $this, $param);
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
		$cb = $this->getPage()->getCallbackClient();
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
			$cb = $this->getPage()->getCallbackClient();
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
			$console = $this->setJobResourceAccess(
				$api_host,
				$jobs,
				'api_host_group_access_window_error'
			);
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
