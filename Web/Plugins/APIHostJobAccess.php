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

use Bacularis\Common\Modules\AuthOAuth2;
use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\Errors\BaculaConfigError;
use Bacularis\Common\Modules\IBacularisActionPlugin;
use Bacularis\Common\Modules\Plugins;
use Bacularis\Web\Modules\BacularisWebPluginBase;
use Bacularis\Web\Modules\JobInfo;
use Bacularis\Web\Modules\OAuth2Record;

/**
 * Add job access to API host plugin module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Plugin
 */
class APIHostJobAccess extends BacularisWebPluginBase implements IBacularisActionPlugin
{
	/**
	 * Plugin parameter categories
	 */
	private const PARAM_CAT_JOB_CRITERIA = 'Job criteria';
	private const PARAM_CAT_DESTINATION = 'Destination';

	/**
	 * Get plugin name displayed in web interface.
	 *
	 * @return string plugin name
	 */
	public static function getName(): string
	{
		return 'API host job access';
	}

	/**
	 * Get plugin version.
	 *
	 * @return string plugin version
	 */
	public static function getVersion(): string
	{
		return '1.0.0';
	}

	/**
	 * Get plugin type.
	 *
	 * @return string plugin type
	 */
	public static function getType(): string
	{
		return 'action';
	}

	/**
	 * Get plugin resource.
	 *
	 * @return string plugin resource
	 */
	public static function getResource(): string
	{
		return 'Job';
	}

	/**
	 * Get plugin configuration parameters.
	 *
	 * return array plugin parameters
	 */
	public static function getParameters(): array
	{
		return [
			[
				'name' => 'include_regex',
				'type' => 'string',
				'default' => '',
				'label' => 'Job name include regex',
				'category' => [self::PARAM_CAT_JOB_CRITERIA]
			],
			[
				'name' => 'exclude_regex',
				'type' => 'string',
				'default' => '',
				'label' => 'Job name exclude regex',
				'category' => [self::PARAM_CAT_JOB_CRITERIA]
			],
			[
				'name' => 'api_host',
				'type' => 'array_multiple',
				'default' => '',
				'data' => [],
				'resource' => 'api_host',
				'label' => 'API host',
				'category' => [self::PARAM_CAT_DESTINATION]
			],
		];
	}

	/**
	 * Main run action command.
	 *
	 * @param null|string $type resource type
	 * @param null|string $name resource name
	 * @return bool true on success, false otherwise
	 */
	public function run(?string $type = null, ?string $name = null): bool
	{
		if (!$this->isJobAllowed($name)) {
			// Job is not allowed to add to API host
			return false;
		}
		$config = $this->getConfig();
		if (!is_array($config['parameters']['api_host'])) {
			// Not an array type - unexpected error
			return false;
		}
		$state = true;
		for ($i = 0; $i < count($config['parameters']['api_host']); $i++) {
			$api_host = $config['parameters']['api_host'][$i];
			$jobs = $this->getAPIHostJobs($api_host);
			$jobs[] = $name;
			$job_res = $this->getAPIHostJobResources($api_host, $jobs);
			$console = $this->setAPIHostJobs($api_host, $job_res);
			if (!$console) {
				// stop on error
				break;
			}
			$state = $this->setResourceConsole($api_host, $console);
		}
		return $state;
	}

	/**
	 * Set resource access  with Consoles for given API host.
	 *
	 * @param string $api_host API host name
	 * @param string $console console name
	 * @return bool console setting state, true for success, otherwise false
	 */
	private function setResourceConsole(string $api_host, string $console)
	{
		$state = false;
		$host_config = $this->getModule('host_config')->getHostConfig($api_host);
		if (count($host_config) == 0) {
			Plugins::log(
				Plugins::LOG_ERROR,
				"API host $api_host does not exist.",
				Plugins::LOG_DEST_FILE
			);
			return $state;
		}
		$api = $this->getModule('api');
		$result = $api->get(['directors'], $api_host);
		if ($result->error !== 0) {
			Plugins::log(
				Plugins::LOG_ERROR,
				"Unable to get director list.",
				Plugins::LOG_DEST_FILE
			);
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
			$result = $api->set([
				'basic',
				'users',
				$username
			], $config, $api_host);
			if ($result->error === 0) {
				$state = true;
			} else {
				Plugins::log(
					Plugins::LOG_ERROR,
					"Error while setting Console ACL '$console' for Director '$director' and API host '$api_host'.",
					Plugins::LOG_DEST_FILE
				);
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
			$result = $api->set([
				'oauth2',
				'clients',
				$client_id
			], $config, $api_host);

			$oa2 = new OAuth2Record();
			$oa2::deleteByPk($api_host);

			if ($result->error === 0) {
				$state = true;
			} else {
				Plugins::log(
					Plugins::LOG_ERROR,
					"Error while setting Console ACL '$console' for Director '$director' and API host '$api_host'.",
					Plugins::LOG_DEST_FILE
				);
				return $state;
			}
		}
		return $state;
	}

	/**
	 * Get job names currently allowed for given API host.
	 *
	 * @param string $api_host API host name
	 * @return array allowed job names
	 */
	private function getAPIHostJobs(string $api_host): array
	{
		$jobs = [];
		$api = $this->getModule('api');
		$params = ['jobs', 'resnames'];
		$result = $api->get($params, $api_host);
		if ($result->error === 0) {
			$res = array_values((array) $result->output);
			$jobs = array_shift($res);
		}
		return $jobs;
	}

	/**
	 * Set console with all job dependencies.
	 *
	 * @param string $api_host API host name
	 * @param array $job_res job names allowed to use in console
	 * @return string console name or empty string on error
	 */
	private function setAPIHostJobs(string $api_host, $job_res): string
	{
		// Prepare the console configuration
		$crypto = $this->getModule('crypto');
		$acls = [
			'Name' => 'Console - ' . $api_host,
			'Password' => $crypto->getRandomString(40),
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
		for ($i = 0; $i < count($job_res); $i++) {
			// job
			$acls['JobAcl'][] = $job_res[$i]->Job->Name;
			// client
			if (!in_array($job_res[$i]->Job->Client, $acls['ClientAcl'])) {
				$acls['ClientAcl'][] = $job_res[$i]->Job->Client;
			}
			// storage
			$acls['StorageAcl'] = array_merge($acls['StorageAcl'], $job_res[$i]->Job->Storage);
			$acls['StorageAcl'] = array_unique($acls['StorageAcl']);
			// fileset
			if (!in_array($job_res[$i]->Job->Fileset, $acls['FilesetAcl'])) {
				$acls['FilesetAcl'][] = $job_res[$i]->Job->Fileset;
			}
			// pool
			if (!in_array($job_res[$i]->Job->Pool, $acls['PoolAcl'])) {
				$acls['PoolAcl'][] = $job_res[$i]->Job->Pool;
			}
			// schedule
			if (property_exists($job_res[$i]->Job, 'Schedule') && !in_array($job_res[$i]->Job->Schedule, $acls['ScheduleAcl'])) {
				$acls['ScheduleAcl'][] = $job_res[$i]->Job->Schedule;
			}
		}

		// Create console
		$api = $this->getModule('api');
		$result = $api->create([
			'config',
			'dir',
			'Console',
			$acls['Name']
		], [
			'config' => json_encode($acls)
		], $api_host);

		$saved = false;
		if ($result->error === 0) {
			// Console created
			$saved = true;
		} elseif ($result->error === BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS) {
			// Console exists, update it
			$result = $api->set([
				'config',
				'dir',
				'Console',
				$acls['Name']
			], [
				'config' => json_encode($acls)
			], $api_host);
			$saved = ($result->error === 0);
		}
		$ret = '';
		if ($saved) {
			// Config saved, reload it now
			$api->set(['console'], ['reload']);
			$ret = $acls['Name'];
		}
		return $ret;
	}

	/**
	 * Get job configuration for selected job names.
	 * This configuration is used to determine all job dependent resources.
	 *
	 * @param string $api_host API host name
	 * @param array $job_filter job names to get results
	 * @return array given job configuration
	 */
	private function getAPIHostJobResources(string $api_host, array $job_filter = []): array
	{
		$job_res = [];
		$api = $this->getModule('api');
		$result = $api->get([
			'config',
			'dir',
			'Job',
			'?apply_jobdefs=1'
		], $api_host);

		if ($result->error == 0) {
			$job_res = array_filter(
				$result->output,
				fn ($item) => in_array($item->Job->Name, $job_filter)
			);
			$job_res = array_values($job_res);
		}
		return $job_res;
	}

	/**
	 * Check if job is allowed to be processed in the plugin.
	 * It uses include and exclude lists defined in the regular expression form.
	 *
	 * @param string $job job name to check
	 * @return bool true if job is allowed to use, otherwise false
	 */
	private function isJobAllowed(string $job): bool
	{
		$allowed_inc = $allowed_exc = false;
		$config = $this->getConfig();
		$include_regex = $config['parameters']['include_regex'];
		$exclude_regex = $config['parameters']['exclude_regex'];
		if (empty($include_regex) || preg_match('/' . $include_regex . '/', $job) === 1) {
			$allowed_inc = true;
		}
		if (empty($exclude_regex) || preg_match('/' . $exclude_regex . '/', $job) === 0) {
			$allowed_exc = true;
		}
		return ($allowed_inc && $allowed_exc);
	}
}
