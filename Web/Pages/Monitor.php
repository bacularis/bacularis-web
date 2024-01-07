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
use Bacularis\Common\Modules\Errors\GenericError;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\JobInfo;
use Bacularis\Web\Modules\WebConfig;
use Bacularis\Web\Modules\WebUserRoles;

/**
 * Monitor class.
 *
 * NOTE: It must inherit from BaculumPage, not from BaculumWebPage,
 * because this way it has not any redundant API request from BaculumWebPage.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class Monitor extends BaculumWebPage
{
	/**
	 * Pre-initialization with authenticate.
	 *
	 * NOTE: This method cannot be removed and cannot call parent::onPreInit()
	 * because in parent method are called redundant API requests not used by Monitor.
	 * @param mixed $param
	 */
	public function onPreInit($param)
	{
		if ($this->authenticate() === false) {
			exit();
		}
	}

	public function onInit($param)
	{
		parent::onInit($param);
		$monitor_data = [
			'jobs' => [],
			'running_jobs' => [],
			'terminated_jobs' => [],
			'pools' => [],
			'clients' => [],
			'jobtotals' => [],
			'dbsize' => [],
			'messages' => [],
			'error' => ['error' => 0, 'output' => '']
		];

		// Initialize session cache to have clear session for Monitor
		$this->getModule('api')->initSessionCache(true);

		$web_config = $this->getModule('web_config')->getConfig();
		$job_limit = JobInfo::DEFAULT_MAX_JOBS;
		$job_age = WebConfig::DEF_JOB_AGE_ON_JOB_STATUS_GRAPH;
		if (count($web_config) > 0) {
			if (key_exists('max_jobs', $web_config['baculum'])) {
				$job_limit = $web_config['baculum']['max_jobs'];
			}
			if (key_exists('job_age_on_job_status_graph', $web_config['baculum'])) {
				$job_age = $web_config['baculum']['job_age_on_job_status_graph'];
			}
		}

		$error = null;
		$params = $this->Request->contains('params') ? $this->Request['params'] : [];
		if (!is_array($params)) {
			$error = (object) [
				'output' => 'Wrong monitor parameter.',
				'error' => GenericError::ERROR_INTERNAL_ERROR
			];
		}
		if (!$error && key_exists('jobs', $params)) {
			$job_params = ['jobs'];
			$job_query = [];
			if (is_array($params['jobs'])) {
				if (key_exists('name', $params['jobs']) && is_array($params['jobs']['name'])) {
					for ($i = 0; $i < count($params['jobs']['name']); $i++) {
						// @TODO: Add support for multiple names in query
						$job_query['name'] = $params['jobs']['name'][$i];
					}
				}
				if (key_exists('client', $params['jobs']) && is_array($params['jobs']['client'])) {
					for ($i = 0; $i < count($params['jobs']['client']); $i++) {
						// @TODO: Add support for multiple clients in query
						$job_query['client'] = $params['jobs']['client'][$i];
					}
				}
			}
			if ($this->Request->contains('use_limit') && $this->Request['use_limit'] == 1) {
				$job_query['limit'] = $job_limit;
			}
			if ($this->Request->contains('use_age') && $this->Request['use_age'] == 1) {
				$job_query['age'] = $job_age;
			}

			if (count($job_query) > 0) {
				$job_params[] = '?' . http_build_query($job_query);
			}
			$result = $this->getModule('api')->get($job_params);
			if ($result->error === 0) {
				$monitor_data['jobs'] = $result->output;
			} else {
				$error = $result;
			}
		}
		if (!$error && key_exists('clients', $params)) {
			$result = $this->getModule('api')->get(['clients']);
			if ($result->error === 0) {
				$monitor_data['clients'] = $result->output;
			} else {
				$error = $result;
			}
		}
		if (!$error && key_exists('pools', $params)) {
			$result = $this->getModule('api')->get(['pools']);
			if ($result->error === 0) {
				$monitor_data['pools'] = $result->output;
			} else {
				$error = $result;
			}
		}
		if (!$error && key_exists('job_totals', $params)) {
			$result = $this->getModule('api')->get(['jobs', 'totals']);
			if ($result->error === 0) {
				$monitor_data['jobtotals'] = $result->output;
			} else {
				$error = $result;
			}
		}
		if (!$error && key_exists('dbsize', $params)) {
			$result = $this->getModule('api')->get(['dbsize']);
			if ($result->error === 0) {
				$monitor_data['dbsize'] = $result->output;
			} else {
				$error = $result;
			}
		}
		if (!$error && $this->getModule('web_config')->isMessagesLogEnabled() && $this->User->isInRole(WebUserRoles::ADMIN)) {
			$result = $this->getModule('api')->get(['joblog', 'messages']);
			if ($result->error === 0) {
				$ml = [];
				if (count($result->output) > 0 && $result->output[0] != 'You have no messages.') {
					$ml = $this->getModule('messages_log')->append($result->output);
				} else {
					$ml = $this->getModule('messages_log')->read();
				}
				$monitor_data['messages'] = $this->getModule('log_parser')->parse($ml);
			} else {
				$error = $result;
			}
		}

		$running_from_all = false;
		if (key_exists('jobs', $params)) {
			$running_from_all = empty($params['jobs']);
			$running_job_states = $this->Application->getModule('misc')->getRunningJobStates();
			for ($i = 0; $i < count($monitor_data['jobs']); $i++) {
				if (in_array($monitor_data['jobs'][$i]->jobstatus, $running_job_states)) {
					// @NOTE: Running jobs are taken from all jobs only
					// if there is not any job criteria in query (see $params['jobs'])
					if ($running_from_all) {
						$monitor_data['running_jobs'][] = $monitor_data['jobs'][$i];
					}
				} else {
					$monitor_data['terminated_jobs'][] = $monitor_data['jobs'][$i];
				}
			}
		}

		if (!$error && !$running_from_all) {
			$result = $this->getModule('api')->get(['jobs', '?jobstatus=CR']);
			if ($result->error === 0) {
				$monitor_data['running_jobs'] = $result->output;
			} else {
				$error = $result;
			}
		}

		if (is_object($error)) {
			$monitor_data['error'] = $error;
		}

		echo json_encode($monitor_data);
		exit();
	}
}
