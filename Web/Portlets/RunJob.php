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

namespace Bacularis\Web\Portlets;

use Prado\Prado;
use Bacularis\Common\Modules\AuditLog;

/**
 * Run job control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class RunJob extends Portlets
{
	public const JOBID = 'JobId';
	public const JOB_NAME = 'JobName';

	public const USE_CACHE = true;

	public const DEFAULT_JOB_PRIORITY = 10;

	public $job_to_verify = ['O', 'd', 'A'];

	public $verify_no_accurate = ['O', 'd'];

	public $verify_options = ['jobname' => 'Verify by Job Name', 'jobid' => 'Verify by JobId'];

	public function loadData()
	{
		$jobid = $this->getJobId();
		$jobname = $this->getJobName();
		$jobdata = null;
		$job_info = [];

		if ($jobid > 0) {
			$job = $this->getModule('api')->get(
				['jobs', $jobid],
				null,
				true,
				self::USE_CACHE
			);
			if ($job->error == 0) {
				$jobdata = $job->output;
				$jobname = $job->output->name;
			}
		}

		if (!empty($jobname)) {
			$job_show = $this->getModule('api')->get(
				['jobs', 'show', '?name=' . rawurlencode($jobname)],
				null,
				true,
				self::USE_CACHE
			);
			if ($job_show->error == 0) {
				$job_info = $this->getModule('job_info')->parseResourceDirectives($job_show->output);
			}
		}

		$job_info_keys = array_keys($job_info);
		$storage_idx = array_search('storage', $job_info_keys) ?: -1;
		$autochanger_idx = array_search('autochanger', $job_info_keys) ?: -1;
		if ($jobid > 0) {
			$storage = key_exists('storage', $job_info) ? $job_info['storage']['name'] : null;
			$autochanger = key_exists('autochanger', $job_info) ? $job_info['autochanger']['name'] : null;
			$jobdata->storage = ($autochanger_idx > -1 && ($storage_idx == -1 || $autochanger_idx < $storage_idx)) ? $autochanger : $storage;
			$this->getPage()->getCallbackClient()->show('run_job_storage_from_config_info');
		} elseif (!empty($jobname)) {
			$jobdata = new \StdClass();
			$levels = $this->getModule('misc')->getJobLevels();
			$levels_flip = array_flip($levels);

			if (key_exists('job', $job_info) && !empty($job_info['job']['level'])) {
				$jobdata->level = $levels_flip[$job_info['job']['level']];
			}
			$client = key_exists('client', $job_info) ? $job_info['client']['name'] : null;
			$fileset = key_exists('fileset', $job_info) ? $job_info['fileset']['name'] : null;
			$pool = key_exists('pool', $job_info) ? $job_info['pool']['name'] : null;
			$storage = key_exists('storage', $job_info) ? $job_info['storage']['name'] : null;
			$autochanger = key_exists('autochanger', $job_info) ? $job_info['autochanger']['name'] : null;
			$priority = key_exists('job', $job_info) ? $job_info['job']['priority'] : self::DEFAULT_JOB_PRIORITY;
			$accurate = key_exists('job', $job_info) && key_exists('accurate', $job_info['job']) ? $job_info['job']['accurate'] : 0;
			$jobdata->client = $client;
			$jobdata->fileset = $fileset;
			$jobdata->pool = $pool;
			$jobdata->storage = ($autochanger_idx > -1 && ($storage_idx == -1 || $autochanger_idx < $storage_idx)) ? $autochanger : $storage;
			$jobdata->priorjobid = $priority;
			$jobdata->accurate = ($accurate == 1);
		} else {
			$jobs = [];
			$job_list = $this->getModule('api')->get(['jobs', 'resnames'], null, true, self::USE_CACHE)->output;
			foreach ($job_list as $director => $job) {
				// Note for doubles for different dirs with different databases
				$jobs = array_merge($jobs, $job);
			}
			sort($jobs, SORT_NATURAL | SORT_FLAG_CASE);
			$this->JobToRun->DataSource = array_combine($jobs, $jobs);
			$this->JobToRun->dataBind();
			$this->JobToRunLine->Display = 'Dynamic';
			if (count($jobs) > 0) {
				$this->setJobName($jobs[0]);
				$this->loadData();
				// set first job as selected and then load job config
				return;
			}
		}
		$this->Level->DataSource = $this->getModule('misc')->getJobLevels();
		$is_verify_option = false;
		$is_accurate = true;
		if (is_object($jobdata) && property_exists($jobdata, 'level')) {
			$this->Level->SelectedValue = $jobdata->level;
			$is_verify_option = in_array($jobdata->level, $this->job_to_verify);
			$is_accurate = !in_array($jobdata->level, $this->verify_no_accurate);
		}
		$this->Level->dataBind();

		$this->AccurateLine->Display = ($is_accurate === true) ? 'Dynamic' : 'None';
		$this->JobToVerifyOptionsLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';
		$this->JobToVerifyJobNameLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';
		$this->JobToVerifyJobIdLine->Display = 'None';

		$verify_values = [];
		foreach ($this->verify_options as $value => $text) {
			$verify_values[$value] = Prado::localize($text);
		}
		$this->JobToVerifyOptions->DataSource = $verify_values;
		$this->JobToVerifyOptions->dataBind();

		$jobTasks = $this->getModule('api')->get(['jobs', 'resnames'], null, true, self::USE_CACHE)->output;
		$jobsAllDirs = [];
		foreach ($jobTasks as $director => $tasks) {
			$jobsAllDirs = array_merge($jobsAllDirs, $tasks);
		}
		natcasesort($jobsAllDirs);

		$this->JobToVerifyJobName->DataSource = array_combine($jobsAllDirs, $jobsAllDirs);
		if (key_exists('job', $job_info) && key_exists('jobtoverify', $job_info['job'])) {
			$this->JobToVerifyJobName->SelectedValue = $job_info['job']['jobtoverify'];
		}
		$this->JobToVerifyJobName->dataBind();

		$clients = $this->getModule('api')->get(['clients'], null, true, self::USE_CACHE)->output;
		$client_list = [];
		foreach ($clients as $client) {
			if (is_object($jobdata) && property_exists($jobdata, 'client') && $client->name === $jobdata->client) {
				$jobdata->clientid = $client->clientid;
			}
			$client_list[$client->clientid] = $client->name;
		}
		natcasesort($client_list);
		$this->Client->DataSource = $client_list;
		if (is_object($jobdata)) {
			$this->Client->SelectedValue = $jobdata->clientid;
		}
		$this->Client->dataBind();

		$fileset_all = $this->getModule('api')->get(['filesets', 'resnames'], null, true, self::USE_CACHE)->output;
		$fileset_list = [];
		foreach ($fileset_all as $director => $filesets) {
			$fileset_list = array_merge($filesets, $fileset_list);
		}
		$selected_fileset = '';
		if (is_object($jobdata)) {
			if (property_exists($jobdata, 'fileset')) {
				$selected_fileset = $jobdata->fileset;
			} elseif ($jobdata->filesetid != 0) {
				$fileset = $this->getModule('api')->get(
					['filesets', $jobdata->filesetid],
					null,
					true,
					self::USE_CACHE
				);
				if ($fileset->error === 0) {
					$selected_fileset = $fileset->output->fileset;
				}
			}
		}
		natcasesort($fileset_list);
		$this->FileSet->DataSource = array_combine($fileset_list, $fileset_list);
		$this->FileSet->SelectedValue = $selected_fileset;
		$this->FileSet->dataBind();

		$pools = $this->getModule('api')->get(['pools'], null, true, self::USE_CACHE)->output;
		$pool_list = [];
		foreach ($pools as $pool) {
			if (is_object($jobdata) && property_exists($jobdata, 'pool') && $pool->name === $jobdata->pool) {
				$jobdata->poolid = $pool->poolid;
			}
			$pool_list[$pool->poolid] = $pool->name;
		}
		natcasesort($pool_list);
		$this->Pool->DataSource = $pool_list;
		if (is_object($jobdata)) {
			$this->Pool->SelectedValue = $jobdata->poolid;
		}
		$this->Pool->dataBind();

		if (is_object($jobdata) && !property_exists($jobdata, 'storage')) {
			$jobshow = $this->getModule('api')->get(
				['jobs', $jobdata->jobid, 'show'],
				null,
				true,
				self::USE_CACHE
			)->output;
			$jobdata->storage = $this->getResourceName('storage', $jobshow);
		}
		$storage_list = [];
		$storages = $this->getModule('api')->get(['storages'], null, true, self::USE_CACHE)->output;
		foreach ($storages as $storage) {
			if (is_object($jobdata) && property_exists($jobdata, 'storage') && $storage->name === $jobdata->storage) {
				$jobdata->storageid = $storage->storageid;
			}
			$storage_list[$storage->storageid] = $storage->name;
		}
		natcasesort($storage_list);
		$this->Storage->DataSource = $storage_list;
		if (is_object($jobdata) && property_exists($jobdata, 'storageid')) {
			$this->Storage->SelectedValue = $jobdata->storageid;
		}
		$this->Storage->dataBind();

		if (is_object($jobdata) && property_exists($jobdata, 'accurate')) {
			$this->Accurate->Checked = $jobdata->accurate;
		}

		$priority = self::DEFAULT_JOB_PRIORITY;
		if (is_object($jobdata) && property_exists($jobdata, 'priorjobid') && $jobdata->priorjobid > 0) {
			$priority = $jobdata->priorjobid;
		}
		$this->Priority->Text = $priority;
		$this->Estimate->Enabled = false;
	}

	public function selectJobValues($sender, $param)
	{
		$this->setJobName($sender->SelectedValue);
		$this->loadData();
	}

	/**
	 * set jobid to run job again.
	 *
	 * @param mixed $jobid
	 */
	public function setJobId($jobid)
	{
		$jobid = (int) $jobid;
		$this->setViewState(self::JOBID, $jobid, 0);
	}

	/**
	 * Get jobid to run job again.
	 *
	 * @return int jobid
	 */
	public function getJobId()
	{
		return $this->getViewState(self::JOBID, 0);
	}

	/**
	 * set job name to run job again.
	 *
	 * @param mixed $job_name
	 */
	public function setJobName($job_name)
	{
		$this->setViewState(self::JOB_NAME, $job_name);
	}

	/**
	 * Get job name to run job again.
	 *
	 * @return string job name
	 */
	public function getJobName()
	{
		return $this->getViewState(self::JOB_NAME);
	}

	public function priorityValidator($sender, $param)
	{
		$isValid = preg_match('/^[0-9]+$/', $this->Priority->Text) === 1 && $this->Priority->Text > 0;
		$param->setIsValid($isValid);
	}

	public function jobIdToVerifyValidator($sender, $param)
	{
		$verifyVals = $this->getVerifyVals();
		if (in_array($this->Level->SelectedValue, $this->job_to_verify) && $this->JobToVerifyOptions->SelectedItem->Value == $verifyVals['jobid']) {
			$isValid = preg_match('/^[0-9]+$/', $this->JobToVerifyJobId->Text) === 1 && $this->JobToVerifyJobId->Text > 0;
		} else {
			$isValid = true;
		}
		$param->setIsValid($isValid);
		return $isValid;
	}

	private function getVerifyVals()
	{
		$verifyOpt = array_keys($this->verify_options);
		$verifyVals = array_combine($verifyOpt, $verifyOpt);
		return $verifyVals;
	}

	public function estimate($sender, $param)
	{
		$params = [];
		$jobid = $this->getJobId();
		$job_name = $this->getJobName();
		if ($jobid > 0) {
			$params['id'] = $jobid;
		} elseif (!empty($job_name)) {
			$params['name'] = $job_name;
		} else {
			$params['name'] = $this->JobToRun->SelectedValue;
		}
		$params['level'] = $this->Level->SelectedValue;
		$params['fileset'] = $this->FileSet->SelectedValue;
		$params['clientid'] = $this->Client->SelectedValue;
		$params['accurate'] = (int) $this->Accurate->Checked;
		$result = $this->getModule('api')->create(['jobs', 'estimate'], $params);
		if ($result->error === 0 && count($result->output) == 1) {
			$out = json_decode($result->output[0]);
			if (is_object($out) && property_exists($out, 'out_id')) {
				$result = $this->getEstimateOutput($out->out_id);
				$this->getPage()->getCallbackClient()->callClientFunction(
					'estimate_output_refresh',
					[$out->out_id]
				);
			}
		}

		if ($result->error === 0) {
			$this->getPage()->getCallbackClient()->callClientFunction('set_loading_status', ['loading']);
			$this->RunJobLog->Text = implode('', $result->output);
		} else {
			$this->RunJobLog->Text = $result->output;
		}
	}

	public function getEstimateOutput($out_id)
	{
		$result = $this->getModule('api')->get(
			['jobs', 'estimate', '?out_id=' . rawurlencode($out_id)]
		);
		return $result;
	}

	public function refreshEstimateOutput($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$result = $this->getEstimateOutput($out_id);

		if ($result->error === 0) {
			if (count($result->output) > 0) {
				$this->RunJobLog->Text = implode('', $result->output);
				$this->getPage()->getCallbackClient()->callClientFunction(
					'estimate_output_refresh',
					[$out_id]
				);
			} else {
				$this->getPage()->getCallbackClient()->callClientFunction(
					'set_loading_status',
					['finish']
				);
			}
		} else {
			$this->RunJobLog->Text = $result->output;
		}
	}
	public function runJobAgain($sender, $param)
	{
		$jobid = $this->getJobId();
		$job_name = $this->getJobName();
		if ($jobid > 0) {
			$params['id'] = $jobid;
		} elseif (!empty($job_name)) {
			$params['name'] = $job_name;
		} else {
			$params['name'] = $this->JobToRun->SelectedValue;
		}
		$params['level'] = $this->Level->SelectedValue;
		$params['fileset'] = $this->FileSet->SelectedValue;
		$params['clientid'] = $this->Client->SelectedValue;
		$params['storageid'] = $this->Storage->SelectedValue;
		$params['poolid'] = $this->Pool->SelectedValue;
		$params['priority'] = $this->Priority->Text;
		$params['accurate'] = (int) $this->Accurate->Checked;

		if (!empty($this->Level->SelectedItem) && in_array($this->Level->SelectedItem->Value, $this->job_to_verify)) {
			$verifyVals = $this->getVerifyVals();
			if ($this->JobToVerifyOptions->SelectedItem->Value == $verifyVals['jobname']) {
				$params['verifyjob'] = $this->JobToVerifyJobName->SelectedValue;
			} elseif ($this->JobToVerifyOptions->SelectedItem->Value == $verifyVals['jobid']) {
				$params['jobid'] = $this->JobToVerifyJobId->Text;
			}
		}
		$result = $this->getModule('api')->create(['jobs', 'run'], $params);
		$cc = $this->getPage()->getCallbackClient();
		if ($result->error === 0) {
			$started_jobid = $this->getModule('misc')->findJobIdStartedJob($result->output);
			if (is_numeric($started_jobid)) {
				if ($this->GoToJobAfterStart->Checked) {
					$cc->callClientFunction(
						'run_job_go_to_running_job',
						$started_jobid
					);
				} else {
					$cc->callClientFunction('oMonitor');
					$cc->hide('run_job');
				}
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_ACTION,
					"Run job. Job: $job_name, JobId: $started_jobid"
				);
			} else {
				$output = implode('', $result->output);
				$cc->callClientFunction(
					'set_run_job_output',
					[$output]
				);
				$this->getModule('audit')->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_ACTION,
					"Run job failed. Job: $job_name"
				);
			}
		} else {
			$cc->callClientFunction(
				'set_run_job_output',
				[$result->output]
			);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				"Run job failed. Job: $job_name"
			);
		}
	}
}
