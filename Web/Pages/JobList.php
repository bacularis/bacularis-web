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
 * Copyright (C) 2013-2019 Kern Sibbald
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

use Bacularis\Web\Modules\BaculumWebPage;
use Prado\Prado;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;

/**
 * Job list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class JobList extends BaculumWebPage
{
	public const USE_CACHE = true;

	public const DEFAULT_JOB_PRIORITY = 10;

	public $jobs;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$result = $this->getModule('api')->get(
			['jobs', 'show', '?output=json'],
			null,
			true,
			self::USE_CACHE
		);
		$jobs = [];
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$jobs[] = [
					'job' => $result->output[$i]->name,
					'enabled' => $result->output[$i]->enabled,
					'priority' => $result->output[$i]->priority,
					'type' => chr($result->output[$i]->jobtype),
					'maxjobs' => $result->output[$i]->maxjobs
				];
			}
		}
		$this->jobs = $jobs;
		$this->setDataViews();
	}

	private function setDataViews()
	{
		$job_view_desc = [
			'jobid' => ['type' => 'number', 'name' => Prado::localize('JobId')],
			'name' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'type' => ['type' => 'string', 'name' => Prado::localize('Type'), 'formatter' => 'JobType.get_type'],
			'level' => ['type' => 'string', 'name' => Prado::localize('Level'), 'formatter' => 'JobLevel.get_level'],
			'jobstatus' => ['type' => 'string', 'name' => Prado::localize('Job status'), 'formatter' => 'JobStatus.get_desc'],
			'client' => ['type' => 'string', 'name' => Prado::localize('Client')],
			'fileset' => ['type' => 'string', 'name' => Prado::localize('FileSet')],
			'pool' => ['type' => 'string', 'name' => Prado::localize('Pool')],
			'schedtime' => ['type' => 'isodatetime', 'name' => Prado::localize('Scheduled time')],
			'starttime' => ['type' => 'isodatetime', 'name' => Prado::localize('Start time')],
			'endtime' => ['type' => 'isodatetime', 'name' => Prado::localize('End time')],
			'realendtime' => ['type' => 'isodatetime', 'name' => Prado::localize('Real end time')],
			'jobbytes' => ['type' => 'number', 'name' => Prado::localize('Size')],
			'jobfiles' => ['type' => 'number', 'name' => Prado::localize('Files')],
			'joberrors' => ['type' => 'number', 'name' => Prado::localize('Job errors')],
			'volcount' => ['type' => 'number', 'name' => Prado::localize('Vol. count')]
		];
		$this->JobViews->setViewName('job_history');
		$this->JobViews->setViewDataFunction('get_job_history_data');
		$this->JobViews->setUpdateViewFunction('update_job_history_table');
		$this->JobViews->setDescription($job_view_desc);
	}

	public function loadRunJobModal($sender, $param)
	{
		$this->RunJobModal->loadData();
	}

	/**
	 * Run multiple jobs by name event handler.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function runJobs(TCallback $sender, TCallbackEventParameter $param): void
	{
		$result = [];
		$jobs = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($jobs); $i++) {
			$ret = $this->runJob($jobs[$i]);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update(
			$this->BulkActions->BulkActionsOutput,
			implode(PHP_EOL, $result)
		);
	}

	/**
	 * Run single job by name.
	 *
	 * @param string $name job name to run
	 * @return StdClass run job result object
	 */
	public function runJob(string $name): object
	{
		$params = ['name' => $name];
		$result = $this->getModule('api')->create(
			['jobs', 'run'],
			$params
		);
		if ($result->error === 0) {
			$started_jobid = $this->getModule('misc')->findJobIdStartedJob($result->output);
			if (!is_numeric($started_jobid)) {
				$errmsg = implode('<br />', $result->output);
				$this->getPage()->getCallbackClient()->callClientFunction(
					'show_error',
					[$errmsg, $result->error]
				);
			}
		} else {
			$this->getPage()->getCallbackClient()->callClientFunction(
				'show_error',
				[$result->output, $result->error]
			);
		}
		return $result;
	}


	/**
	 * Run job again event handler.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function runJobAgain($sender, $param)
	{
		$jobid = (int) ($param->getCallbackParameter());
		$this->rerunJob($jobid);
	}

	/**
	 * Rerun job by jobid.
	 *
	 * @param int $jobid job identifier
	 * @return object response from run job API request
	 */
	private function rerunJob($jobid)
	{
		if ($jobid <= 0) {
			return;
		}
		$jobdata = $this->getModule('api')->get(
			['jobs', $jobid],
			null,
			true,
			self::USE_CACHE
		)->output;
		$params = [];
		$params['id'] = $jobid;
		$level = trim($jobdata->level);
		$params['level'] = !empty($level) ? $level : 'F'; // Admin job has empty level
		$job_show = $this->getModule('api')->get(
			['jobs', $jobid, 'show'],
			null,
			true,
			self::USE_CACHE
		)->output;
		$job_info = $this->getModule('job_info')->parseResourceDirectives($job_show);
		$job_info_keys = array_keys($job_info);
		$storage_idx = array_search('storage', $job_info_keys) ?: -1;
		$autochanger_idx = array_search('autochanger', $job_info_keys) ?: -1;
		if ($jobdata->filesetid > 0) {
			$params['filesetid'] = $jobdata->filesetid;
		} else {
			$params['fileset'] = key_exists('fileset', $job_info) ? $job_info['fileset']['name'] : '';
		}
		$params['clientid'] = $jobdata->clientid;
		$storage = key_exists('storage', $job_info) ? $job_info['storage']['name'] : null;
		$autochanger = key_exists('autochanger', $job_info) ? $job_info['autochanger']['name'] : null;
		$params['storage'] = ($autochanger_idx > -1 && ($storage_idx == -1 || $autochanger_idx < $storage_idx)) ? $autochanger : $storage;

		/**
		 * For 'c' type (Copy Job) and 'g' type (Migration Job) the in job table in poolid property is written
		 * write pool, not read pool. Here in 'pool' property is set read pool and from this reason for 'c'
		 * and 'g' types the pool cannot be taken from job table.
		 */
		if ($jobdata->poolid > 0 && $jobdata->type != 'c' && $jobdata->type != 'g') {
			$params['poolid'] = $jobdata->poolid;
		} else {
			$params['pool'] = key_exists('pool', $job_info) ? $job_info['pool']['name'] : '';
		}
		$params['priority'] = key_exists('job', $job_info) ? $job_info['job']['priority'] : self::DEFAULT_JOB_PRIORITY;
		$accurate = key_exists('job', $job_info) && key_exists('accurate', $job_info['job']) ? $job_info['job']['accurate'] : 0;
		$params['accurate'] = ($accurate == 1);

		$result = $this->getModule('api')->create(
			['jobs', 'run'],
			$params
		);
		if ($result->error === 0) {
			$started_jobid = $this->getModule('misc')->findJobIdStartedJob($result->output);
			if (!is_numeric($started_jobid)) {
				$errmsg = implode('<br />', $result->output);
				$this->getPage()->getCallbackClient()->callClientFunction(
					'show_error',
					[$errmsg, $result->error]
				);
			}
		} else {
			$this->getPage()->getCallbackClient()->callClientFunction(
				'show_error',
				[$result->output, $result->error]
			);
		}
		return $result;
	}

	/**
	 * Rerun multiple jobs.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function rerunJobs($sender, $param)
	{
		$result = [];
		$jobids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($jobids); $i++) {
			$ret = $this->rerunJob($jobids[$i]);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update(
			$this->BulkActions->BulkActionsOutput,
			implode(PHP_EOL, $result)
		);
	}

	public function cancelJob($sender, $param)
	{
		$jobid = (int) ($param->getCallbackParameter());
		$result = $this->getModule('api')->set(
			['jobs', $jobid, 'cancel'],
			[]
		);
	}

	/**
	 * Cancel multiple jobs.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function cancelJobs($sender, $param)
	{
		$result = [];
		$jobids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($jobids); $i++) {
			$ret = $this->getModule('api')->set(
				['jobs', (int) ($jobids[$i]), 'cancel']
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update(
			$this->BulkActions->BulkActionsOutput,
			implode(PHP_EOL, $result)
		);
	}

	/**
	 * Delete multiple jobs.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function deleteJobs($sender, $param)
	{
		$result = [];
		$jobids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($jobids); $i++) {
			$ret = $this->getModule('api')->remove(
				['jobs', (int) ($jobids[$i])]
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update(
			$this->BulkActions->BulkActionsOutput,
			implode(PHP_EOL, $result)
		);
	}

	/**
	 * Load job log.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function loadJobLog($sender, $param)
	{
		$jobid = (int) ($param->getCallbackParameter());
		if ($jobid == 0) {
			return;
		}

		$params = ['joblog', $jobid];

		// add time to log if defiend in configuration
		if (key_exists('time_in_job_log', $this->web_config['baculum'])) {
			$query_params = [
				'show_time' => $this->web_config['baculum']['time_in_job_log']
			];
			$params[] = '?' . http_build_query($query_params);
		}
		$result = $this->getModule('api')->get($params);

		$log = '';
		if ($result->error === 0) {
			$log = implode(PHP_EOL, $result->output);
		}
		$this->getCallbackClient()->update(
			'job_history_report_details_joblog_' . $jobid,
			$log
		);
	}
}
