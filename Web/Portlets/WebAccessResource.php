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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\JobInfo;
use Bacularis\Web\Modules\WebAccessBaculaResource;
use Bacularis\Web\Modules\WebAccessConfig;
use Bacularis\Web\Modules\WebUserRoles;
use DateTime;
use DateTimeZone;
use Prado\Prado;

/**
 * Web access resource module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebAccessResource extends Portlets
{
	private const API_HOSTS = 'APIHosts';
	private const COMPONENT_TYPE = 'ComponentType';
	private const COMPONENT_NAME = 'ComponentName';
	private const RESOURCE_TYPE = 'ResourceType';
	private const RESOURCE_NAME = 'ResourceName';

	public $verify_job_options = [
		'jobname' => 'Verify by Job Name',
		'jobid' => 'Verify by JobId'
	];

	public function onInit($param)
	{
		parent::onInit($param);
		if (!$this->User->isInRole(WebUserRoles::ADMIN)) {
			die();
		}
	}

	/**
	 * Load web access resource list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadWebAccessResourceList($sender, $param)
	{
		$filters = [
			'api_hosts' => $this->getAPIHosts(),
			'component_type' => $this->getComponentType(),
			'component_name' => $this->getComponentName(),
			'resource_type' => $this->getResourceType(),
			'resource_name' => $this->getResourceName()
		];
		$web_access_config = $this->getModule('web_access_config');
		$resource_list = $web_access_config->getConfig($filters);
		$resource_list = array_values($resource_list);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oWebAccessResource.load_web_access_resource_list_cb',
			[$resource_list]
		);
	}

	/**
	 * Load web access window.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadWebAccessResourceWindow($sender, $param): void
	{
		$name = $param->getCallbackParameter();

		// prepare API hosts
		$api_hosts = $this->User->getAPIHosts();
		$def_api_host = $this->User->getDefaultAPIHost();
		$this->WebAccessResourceAPIHosts->DataSource = array_combine($api_hosts, $api_hosts);
		$this->WebAccessResourceAPIHosts->SelectedValues = [$def_api_host];
		$this->WebAccessResourceAPIHosts->dataBind();
		$this->WebAccessResourceAPIHosts->setEnabled(false);
		$this->WebAccessResourceAPIHostsTxt->Text = $def_api_host;

		// Componen type
		$misc = $this->getModule('misc');
		$comp_short = $this->getComponentType();
		$this->WebAccessResourceComponentType->Text = $misc->getComponentFullName($comp_short);

		// Component name
		$this->WebAccessResourceComponentName->Text = $this->getComponentName();

		// Resource type
		$this->WebAccessResourceResourceType->Text = $this->getResourceType();

		// Resource name
		$this->WebAccessResourceResourceName->Text = $this->getResourceName();

		// Action name
		$this->WebAccessResourceActionName->DataSource = [
			' ' => Prado::localize('Select action'),
			WebAccessBaculaResource::ACTION_RUN_NAME => WebAccessBaculaResource::ACTION_RUN_DESC
			//WebAccessBaculaResource::ACTION_CANCEL_NAME => WebAccessBaculaResource::ACTION_CANCEL_DESC
		];
		$this->WebAccessResourceActionName->dataBind();

		$params = [];
		if ($name) {
			$web_access_config = $this->getModule('web_access_config');
			$config = $web_access_config->getWebAccessConfig($name);
			$this->WebAccessResourceActionName->setSelectedValue($config['action']);
			$params = $config['action_params'];

			$cb = $this->getPage()->getCallbackClient();
			$cb->hide('web_access_resource_access_method_x_days');
			$cb->hide('web_access_resource_access_method_from_to_date');
			switch ($config['time_method']) {
				case WebAccessConfig::WEB_ACCESS_TIME_METHOD_UNLIMITED: {
					$this->WebAccessResourceAccessMethodAllTheTime->Checked = true;
					break;
				}
				case WebAccessConfig::WEB_ACCESS_TIME_METHOD_FOR_DAYS: {
					$this->WebAccessResourceAccessMethodGivenDays->Checked = true;
					$cb->show('web_access_resource_access_method_x_days');
					$tf = (int) $config['time_from'];
					$tt = (int) $config['time_to'];
					$diff = (($tt - $tf) / 60 / 60 / 24);
					$this->WebAccessResourceAccessMethodXDays->setText($diff);
					break;
				}
				case WebAccessConfig::WEB_ACCESS_TIME_METHOD_DATE_RANGE: {
					$this->WebAccessResourceAccessMethodDateRange->Checked = true;
					$cb->show('web_access_resource_access_method_from_to_date');
					$tf = date('Y-m-d H:i:s', $config['time_from']);
					$tt = date('Y-m-d H:i:s', $config['time_to']);
					$this->WebAccessResourceAccessMethodFromDate->setDate($tf);
					$this->WebAccessResourceAccessMethodToDate->setDate($tt);
					break;
				}
			}

			$cb->hide('web_access_resource_access_method_x_number_use');
			switch ($config['usage_method']) {
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_UNLIMITED: {
					$this->WebAccessResourceAccessMethodUnlimitedUse->Checked = true;
					break;
				}
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_ONE_USE: {
					$this->WebAccessResourceAccessMethodOneTimeUse->Checked = true;
					break;
				}
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_NUMBER_USES: {
					$this->WebAccessResourceAccessMethodNumberOfUse->Checked = true;
					$cb->show('web_access_resource_access_method_x_number_use');
					$this->WebAccessResourceAccessNumberOfUse->setText($config['usage_max']);
					break;
				}
			}

			$cb->hide('web_access_resource_access_method_source_ips');
			switch ($config['source_access']) {
				case WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_NO_RESTRICTION: {
					$this->WebAccessResourceAccessMethodSourceAccess->Checked = false;
					$this->WebAccessResourceAccessSourceIPs->setText('');
					break;
				}
				case WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_IP_RESTRICTION: {
					$this->WebAccessResourceAccessMethodSourceAccess->Checked = true;
					$cb->show('web_access_resource_access_method_source_ips');
					$sips = implode(',', $config['source_ips_allowed']);
					$this->WebAccessResourceAccessSourceIPs->setText($sips);
					break;
				}
			}
		}

		// Action params
		$this->setActionParams($params);
	}

	/**
	 * Load job action parameters.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadJobActionParameters($sender, $param): void
	{
		$this->setActionParams();
	}

	/**
	 * Set action parameters.
	 *
	 * @param array $params selected action parameters
	 */
	private function setActionParams(array $params = []): void
	{
		$resource_type = $this->getResourceType();
		switch ($resource_type) {
			case 'Job': {
				$this->setJobActionParams($params);
				break;
			}
		}
	}

	/**
	 * Get action parameters.
	 */
	private function getActionParams(): array
	{
		$action_params = [];
		$resource_type = $this->getResourceType();
		switch ($resource_type) {
			case 'Job': {
				$action_params = $this->getJobActionParams();
				break;
			}
		}
		return $action_params;
	}

	/**
	 * Set parameters for job actions.
	 *
	 * @param array $params selected job action parameters
	 */
	private function setJobActionParams(array $params = []): void
	{
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide('run_job_action_params_line_cont');
		$action = $this->WebAccessResourceActionName->SelectedValue;
		switch ($action) {
			case WebAccessBaculaResource::ACTION_RUN_NAME: {
				$this->setRunJobActionParams($params);
				break;
			}
		}
	}

	/**
	 * Get parameters for job actions.
	 */
	private function getJobActionParams(): array
	{
		$action_params = [];
		$action = $this->WebAccessResourceActionName->SelectedValue;
		switch ($action) {
			case WebAccessBaculaResource::ACTION_RUN_NAME: {
				$action_params = $this->getRunJobActionParams();
				break;
			}
		}
		return $action_params;
	}


	/**
	 * Set parameters for run job action.
	 *
	 * @param array $params selected action parameters
	 */
	private function setRunJobActionParams(array $params = []): void
	{
		$directives = $params ?: $this->getJobDirectives();
		$this->setJobLevel($directives);
		$this->setJobVerifyOptions($directives);
		$this->setJobClient($directives);
		$this->setJobFileSet($directives);
		$this->setJobPool($directives);
		$this->setJobStorage($directives);
		$this->setJobAccurate($directives);
		$this->setJobPriority($directives);

		$cb = $this->getPage()->getCallbackClient();
		$cb->show('run_job_action_params_line_cont');
	}

	/**
	 * Get parameters for run job action.
	 *
	 * @return array run job action parameters
	 */
	private function getRunJobActionParams(): array
	{
		$config = [
			'level' => $this->getJobLevel(),
			'client' => $this->getJobClient(),
			'fileset' => $this->getJobFileSet(),
			'pool' => $this->getJobPool(),
			'storage' => $this->getJobStorage(),
			'accurate' => $this->getJobAccurate(),
			'priority' => $this->getJobPriority()
		];
		$verify_opts = $this->getJobVerifyOptions();
		$config = array_merge($config, $verify_opts);
		return $config;
	}

	/**
	 * Get job directives.
	 *
	 * @return array job directives or empty array on error
	 */
	private function getJobDirectives(): array
	{
		$directives = [];
		$job_name = $this->getResourceName();
		$api = $this->getModule('api');
		$job_show = $api->get(
			[
				'jobs',
				'show',
				'?name=' . rawurlencode($job_name)
			]
		);
		if ($job_show->error == 0) {
			$job_info = $this->getModule('job_info');
			$result = $job_info->parseResourceDirectives($job_show->output);

			$misc = $this->getModule('misc');
			$levels = $misc->getJobLevels();
			$levels_flip = array_flip($levels);
			$directives = [
				'level' => $levels_flip[$result['job']['level']] ?? '',
				'verifyjob' => $result['job']['jobtoverify'] ?? '',
				'client' => $result['client']['name'] ?? '',
				'fileset' => $result['fileset']['name'] ?? '',
				'pool' => $result['pool']['name'] ?? '',
				'storage' => $result['storage']['name'] ?? '',
				'autochanger' => $result['autochanger']['name'] ?? '',
				'accurate' => $result['job']['accurate'] ?? 0,
				'priority' => $result['job']['priority'] ?? ''
			];
		}
		return $directives;
	}

	/**
	 * Get job level letter.
	 *
	 * @param array $directives job default directives
	 * @return job level letter
	 */
	private function getJobLevelLetter(array $directives): string
	{
		$level = '';
		if (key_exists('level', $directives)) {
			$level = $directives['level'];
		}
		return $level;
	}

	/**
	 * Set job level.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobLevel(array $directives): void
	{
		$misc = $this->getModule('misc');
		$levels = $misc->getJobLevels();
		$level = $this->getJobLevelLetter($directives);
		$this->WebAccessResourceActionParamLevel->DataSource = $levels;
		$this->WebAccessResourceActionParamLevel->SelectedValue = $level;
		$this->WebAccessResourceActionParamLevel->dataBind();
	}

	/**
	 * Get job level.
	 *
	 * @return string selected job level letter
	 */
	private function getJobLevel(): string
	{
		return $this->WebAccessResourceActionParamLevel->getSelectedItem()->getValue();
	}

	/**
	 * Set verify job options.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobVerifyOptions(array $directives): void
	{
		$level = $this->getJobLevelLetter($directives);
		$is_verify_option = ($level && in_array($level, JobInfo::VERIFY_JOBS));
		$this->WebAccessResourceActionParamJobToVerifyOptionsLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';
		$this->WebAccessResourceActionParamJobToVerifyJobNameLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';
		$this->WebAccessResourceActionParamJobToVerifyJobIdLine->Display = 'None';

		$verify_values = [];
		foreach ($this->verify_job_options as $value => $text) {
			$verify_values[$value] = Prado::localize($text);
		}
		$this->WebAccessResourceActionParamJobToVerifyOptions->DataSource = $verify_values;
		$this->WebAccessResourceActionParamJobToVerifyOptions->dataBind();
		$jobs = [];
		$api = $this->getModule('api');
		$result = $api->get(['jobs', 'resnames']);
		if ($result->error == 0) {
			foreach ($result->output as $director => $tasks) {
				$jobs = array_merge($jobs, $tasks);
			}
			natcasesort($jobs);
		}
		$this->WebAccessResourceActionParamJobToVerifyJobName->DataSource = array_combine($jobs, $jobs);
		if (key_exists('verifyjob', $directives)) {
			$this->WebAccessResourceActionParamJobToVerifyJobName->SelectedValue = $directives['verifyjob'];
			$this->WebAccessResourceActionParamJobToVerifyOptions->setSelectedValue('jobname');
			$this->WebAccessResourceActionParamJobToVerifyJobIdLine->Display = 'None';
			$this->WebAccessResourceActionParamJobToVerifyJobNameLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';
		} elseif (key_exists('jobid', $directives)) {
			$this->WebAccessResourceActionParamJobToVerifyJobId->setText($directives['jobid']);
			$this->WebAccessResourceActionParamJobToVerifyOptions->setSelectedValue('jobid');
			$this->WebAccessResourceActionParamJobToVerifyJobNameLine->Display = 'None';
			$this->WebAccessResourceActionParamJobToVerifyJobIdLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';

		}
		$this->WebAccessResourceActionParamJobToVerifyJobName->dataBind();
	}

	/**
	 * Get verify job options.
	 *
	 * @return array $directives job directives
	 */
	private function getJobVerifyOptions(): array
	{
		$level = $this->WebAccessResourceActionParamLevel->getSelectedValue();
		$is_verify_option = ($level && in_array($level, JobInfo::VERIFY_JOBS));
		$verify_type = $this->WebAccessResourceActionParamJobToVerifyOptions->getSelectedValue();
		$options = [];
		if ($is_verify_option) {
			if ($verify_type == 'jobname') {
				$options['verifyjob'] = $this->WebAccessResourceActionParamJobToVerifyJobName->getSelectedItem()->getText();
			} elseif ($verify_type == 'jobid') {
				$options['jobid'] = $this->WebAccessResourceActionParamJobToVerifyJobId->getText();
			}
		}
		return $options;
	}

	/**
	 * Set job client value.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobClient(array $directives): void
	{
		$client_list = [];
		$clientid_sel = null;
		$client_sel = key_exists('client', $directives) ? $directives['client'] : null;
		$api = $this->getModule('api');
		$result = $api->get(['clients']);
		if ($result->error == 0) {
			foreach ($result->output as $client) {
				if ($client_sel && $client->name === $client_sel) {
					$clientid_sel = $client->clientid;
				}
				$client_list[$client->clientid] = $client->name;
			}
			natcasesort($client_list);
		}
		$this->WebAccessResourceActionParamClient->DataSource = $client_list;
		$this->WebAccessResourceActionParamClient->SelectedValue = $clientid_sel;
		$this->WebAccessResourceActionParamClient->dataBind();
	}

	/**
	 * Get job client value.
	 *
	 * @param string client name
	 */
	private function getJobClient(): string
	{
		return $this->WebAccessResourceActionParamClient->getSelectedItem()->getText();
	}

	/**
	 * Set job fileset value.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobFileSet(array $directives): void
	{
		$fileset_list = [];
		$fileset = key_exists('fileset', $directives) ? $directives['fileset'] : null;
		$api = $this->getModule('api');
		$result = $api->get(['filesets', 'resnames']);
		if ($result->error == 0) {
			foreach ($result->output as $director => $filesets) {
				$fileset_list = array_merge($filesets, $fileset_list);
			}
		}
		natcasesort($fileset_list);
		$this->WebAccessResourceActionParamFileSet->DataSource = array_combine($fileset_list, $fileset_list);
		$this->WebAccessResourceActionParamFileSet->SelectedValue = $fileset;
		$this->WebAccessResourceActionParamFileSet->dataBind();
	}

	/**
	 * Get job fileset value.
	 *
	 * @return string fileset value
	 */
	private function getJobFileSet(): string
	{
		return $this->WebAccessResourceActionParamFileSet->getSelectedItem()->getText();
	}

	/**
	 * Set job pool value.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobPool(array $directives): void
	{
		$pool_list = [];
		$poolid_sel = null;
		$pool_sel = key_exists('pool', $directives) ? $directives['pool'] : null;
		$api = $this->getModule('api');
		$result = $api->get(['pools']);
		if ($result->error == 0) {
			foreach ($result->output as $pool) {
				if ($pool->name === $pool_sel) {
					$poolid_sel = $pool->poolid;
				}
				$pool_list[$pool->poolid] = $pool->name;
			}
			natcasesort($pool_list);
		}
		$this->WebAccessResourceActionParamPool->DataSource = $pool_list;
		$this->WebAccessResourceActionParamPool->SelectedValue = $poolid_sel;
		$this->WebAccessResourceActionParamPool->dataBind();
	}

	/**
	 * Get job pool value.
	 *
	 * @return string job default directive
	 */
	private function getJobPool(): string
	{
		return $this->WebAccessResourceActionParamPool->getSelectedItem()->getText();
	}

	/**
	 * Set job storage value.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobStorage(array $directives): void
	{
		$directives_keys = array_keys($directives);
		$storage = key_exists('storage', $directives) ? $directives['storage'] : null;
		$autochanger = key_exists('autochanger', $directives) ? $directives['autochanger'] : null;
		$storage_idx = array_search('storage', $directives_keys) ?: -1;
		$autochanger_idx = array_search('autochanger', $directives_keys) ?: -1;
		$storage_sel = ($autochanger_idx > -1 && ($storage_idx == -1 || $autochanger_idx < $storage_idx)) ? $autochanger : $storage;

		$storage_list = [];
		$storageid_sel = null;
		$api = $this->getModule('api');
		$result = $api->get(['storages']);
		if ($result->error == 0) {
			foreach ($result->output as $storage) {
				if ($storage->name === $storage_sel) {
					$storageid_sel = $storage->storageid;
				}
				$storage_list[$storage->storageid] = $storage->name;
			}
			natcasesort($storage_list);
		}
		$this->WebAccessResourceActionParamStorage->DataSource = $storage_list;
		$this->WebAccessResourceActionParamStorage->SelectedValue = $storageid_sel;
		$this->WebAccessResourceActionParamStorage->dataBind();
	}

	/**
	 * Get job storage value.
	 *
	 * @return string $directives job directive
	 */
	private function getJobStorage(): string
	{
		return $this->WebAccessResourceActionParamStorage->getSelectedItem()->getText();
	}

	/**
	 * Set job accurate mode value.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobAccurate(array $directives): void
	{
		$level = $this->getJobLevelLetter($directives);
		$is_accurate = !in_array($level, JobInfo::VERIFY_JOBS_NO_ACCURATE);
		$this->WebAccessResourceActionParamAccurateLine->Display = ($is_accurate === true) ? 'Dynamic' : 'None';
		$accurate = key_exists('accurate', $directives) ? $directives['accurate'] : 0;
		$this->WebAccessResourceActionParamAccurate->Checked = ($accurate == 1);
	}

	/**
	 * Get job accurate mode value.
	 *
	 * @return bool accurate job directive
	 */
	private function getJobAccurate(): int
	{
		$accurate = 0;
		$level = $this->WebAccessResourceActionParamLevel->getSelectedValue();
		$is_accurate = !in_array($level, JobInfo::VERIFY_JOBS_NO_ACCURATE);
		if ($is_accurate) {
			$accurate = $this->WebAccessResourceActionParamAccurate->Checked ? 1 : 0;
		}
		return $accurate;
	}

	/**
	 * Set job priority value.
	 *
	 * @param array $directives job default directives
	 */
	private function setJobPriority(array $directives): void
	{
		$priority = key_exists('priority', $directives) ? (int) $directives['priority'] : JobInfo::DEFAULT_JOB_PRIORITY;
		$this->WebAccessResourceActionParamPriority->Text = $priority;
	}

	/**
	 * Get job priority value.
	 *
	 * @return int job priority directive
	 */
	private function getJobPriority(): int
	{
		$priority = (int) $this->WebAccessResourceActionParamPriority->Text;
		return $priority;
	}

	/**
	 * Save web access settings.
	 *
	 * @param TActiveButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function saveWebAccessResource($sender, $param): void
	{
		$api_hosts = $this->getAPIHosts();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$action_params = $this->getActionParams();

		$web_access_config = $this->getModule('web_access_config');
		$token = $this->WebAccessResourceToken->getValue();
		if (empty($token)) {
			$token = $web_access_config->generateWebConfigToken();
		}

		// General config
		$config = [
			'access_type' => WebAccessConfig::WEB_ACCESS_TYPE_RESOURCE,
			'api_hosts' => $api_hosts,
			'component_type' => $component_type,
			'component_name' => $component_name,
			'resource_type' => $resource_type,
			'resource_name' => $resource_name,
			'action' => $this->WebAccessResourceActionName->getSelectedValue(),
			'action_params' => $action_params
		];

		// Time access methods
		if ($this->WebAccessResourceAccessMethodAllTheTime->Checked) {
			$config['time_method'] = WebAccessConfig::WEB_ACCESS_TIME_METHOD_UNLIMITED;
			$config['time_from'] = $config['time_to'] = -1;
		} elseif ($this->WebAccessResourceAccessMethodGivenDays->Checked) {
			$config['time_method'] = WebAccessConfig::WEB_ACCESS_TIME_METHOD_FOR_DAYS;
			$config['time_from'] = time();
			$days = (int) $this->WebAccessResourceAccessMethodXDays->getText();
			$config['time_to'] = $config['time_from'] + ($days * 24 * 60 * 60);
		} elseif ($this->WebAccessResourceAccessMethodDateRange->Checked) {
			$config['time_method'] = WebAccessConfig::WEB_ACCESS_TIME_METHOD_DATE_RANGE;

			// Set date from
			$timestamp_from = $this->WebAccessResourceAccessMethodFromDate->getDate();
			$t_from = new DateTime($timestamp_from);
			$t_from->setTimezone(new DateTimeZone('UTC'));
			$config['time_from'] = $t_from->getTimestamp();

			// Set date to
			$timestamp_to = $this->WebAccessResourceAccessMethodToDate->getDate();
			$t_to = new DateTime($timestamp_to);
			$t_to->setTimezone(new DateTimeZone('UTC'));
			$config['time_to'] = $t_to->getTimestamp();
		}

		// Usage access methods
		if ($this->WebAccessResourceAccessMethodUnlimitedUse->Checked) {
			$config['usage_method'] = WebAccessConfig::WEB_ACCESS_USAGE_METHOD_UNLIMITED;
			$config['usage_max'] = $config['usage_left'] = -1;
		} elseif ($this->WebAccessResourceAccessMethodOneTimeUse->Checked) {
			$config['usage_method'] = WebAccessConfig::WEB_ACCESS_USAGE_METHOD_ONE_USE;
			$config['usage_max'] = $config['usage_left'] = 1;
		} elseif ($this->WebAccessResourceAccessMethodNumberOfUse->Checked) {
			$config['usage_method'] = WebAccessConfig::WEB_ACCESS_USAGE_METHOD_NUMBER_USES;
			$config['usage_max'] = $config['usage_left'] = $this->WebAccessResourceAccessNumberOfUse->getText();
		}

		// Source access methods
		if ($this->WebAccessResourceAccessMethodSourceAccess->Checked) {
			$config['source_access'] = WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_IP_RESTRICTION;
			$allowed_ips = $this->WebAccessResourceAccessSourceIPs->getText();
			$config['source_ips_allowed'] = explode(',', $allowed_ips);
		} else {
			$config['source_access'] = WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_NO_RESTRICTION;
		}

		// Access time 0 - no access yet
		$config['access_time'] = 0;

		// Create time - current
		$config['create_time'] = time();

		$result = $web_access_config->setWebAccessConfig($token, $config);

		$cb = $this->getPage()->getCallbackClient();
		$eid = 'web_access_resource_window_error';
		$audit = $this->getModule('audit');
		$resmsg = $this->getResourceLogInfo();
		if ($result) {
			// Success
			$cb->hide($eid);

			$this->loadWebAccessResourceList($sender, $param);

			$cb->callClientFunction(
				'oWebAccessResource.show_window',
				[false]
			);

			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				sprintf(
					"Web access has been created: {$resmsg}, Action: %s, Params: %s",
					$config['action'],
					json_encode($config['action_params'])
				)
			);
		} else {
			// Error
			$cb->update($eid, 'Error while creating web access configuration.');
			$cb->show($eid);

			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				sprintf(
					"Error while creating web access: {$resmsg}, Action: %s, Params: %s",
					$config['action'],
					json_encode($config['action_params'])
				)
			);
		}
	}

	/**
	 * Remove web access settings.
	 *
	 * @param TActiveButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function removeWebAccessResources($sender, $param): void
	{
		$token_list = $param->getCallbackParameter();
		$tokens = explode('|', $token_list);
		$web_access_config = $this->getModule('web_access_config');
		$result = $web_access_config->removeWebAccessConfigs($tokens);
		$audit = $this->getModule('audit');
		$resmsg = $this->getResourceLogInfo();
		if ($result) {
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				'Web access has been removed. ' . $resmsg
			);

			$this->loadWebAccessResourceList($sender, $param);
		} else {
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				'Error while removing web access. ' . $resmsg
			);
		}
	}

	/**
	 * Get resource information to log.
	 *
	 * @return string resource info
	 */
	private function getResourceLogInfo(): string
	{
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$resmsg = sprintf(
			'Component Type: %s, Component Name: %s Resource Type: %s, Resource Name: %s',
			$component_type,
			$component_name,
			$resource_type,
			$resource_name
		);
		return $resmsg;
	}

	/**
	 * Set API hosts.
	 *
	 * @param array $api_hosts API host list
	 */
	public function setAPIHosts(array $api_hosts): void
	{
		$this->setViewState(self::API_HOSTS, $api_hosts);
	}

	/**
	 * Get API hosts.
	 *
	 * @return array API host list
	 */
	public function getAPIHosts(): array
	{
		return $this->getViewState(self::API_HOSTS, []);
	}

	/**
	 * Set Bacula component type.
	 *
	 * @param string $component_type Bacula component type
	 */
	public function setComponentType(string $component_type): void
	{
		$this->setViewState(self::COMPONENT_TYPE, $component_type);
	}

	/**
	 * Get Bacula component type.
	 *
	 * @return string Bacula component type
	 */
	public function getComponentType(): string
	{
		return $this->getViewState(self::COMPONENT_TYPE, '');
	}

	/**
	 * Set Bacula component name.
	 *
	 * @param string $component_name Bacula component name
	 */
	public function setComponentName(string $component_name): void
	{
		$this->setViewState(self::COMPONENT_NAME, $component_name);
	}

	/**
	 * Get Bacula component name.
	 *
	 * @return string Bacula component name.
	 */
	public function getComponentName(): string
	{
		return $this->getViewState(self::COMPONENT_NAME, '');
	}

	/**
	 * Set Bacula resource type.
	 *
	 * @param string $resource_type Bacula resource type
	 */
	public function setResourceType(string $resource_type): void
	{
		$this->setViewState(self::RESOURCE_TYPE, $resource_type);
	}

	/**
	 * Get Bacula resource type.
	 *
	 * @return string Bacula resource type
	 */
	public function getResourceType(): string
	{
		return $this->getViewState(self::RESOURCE_TYPE, '');
	}

	/**
	 * Set Bacula resource name.
	 *
	 * @param string $resource_name Bacula resource name
	 */
	public function setResourceName(string $resource_name): void
	{
		$this->setViewState(self::RESOURCE_NAME, $resource_name);
	}

	/**
	 * Get Bacula resource name.
	 *
	 * @return string Bacula resource name
	 */
	public function getResourceName(): string
	{
		return $this->getViewState(self::RESOURCE_NAME, '');
	}
}
