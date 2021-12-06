<?php
/*
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

Prado::using('System.Web.UI.ActiveControls.TActiveDropDownList');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');
Prado::using('System.Web.UI.ActiveControls.TActiveTextBox');
Prado::using('System.Web.UI.ActiveControls.TActiveCustomValidator');
Prado::using('System.Web.UI.ActiveControls.TActiveCheckBox');
Prado::using('System.Web.UI.ActiveControls.TCallback');
Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('Application.Web.Portlets.Portlets');

/**
 * Run job control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class RunJob extends Portlets {

	const JOBID = 'JobId';
	const JOB_NAME = 'JobName';

	const USE_CACHE = true;

	const DEFAULT_JOB_PRIORITY = 10;

	public $job_to_verify = array('C', 'O', 'd', 'A');

	public $verify_options = array('jobname' => 'Verify by Job Name', 'jobid' => 'Verify by JobId');

	public function loadData() {
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
				['jobs', 'show', '?name='. rawurlencode($jobname)],
				null,
				true,
				self::USE_CACHE
			);
			if ($job_show->error == 0) {
				$job_info = $this->getModule('job_info')->parseResourceDirectives($job_show->output);
			}
		}

		if ($jobid > 0) {
			$storage = key_exists('storage', $job_info) ? $job_info['storage']['name'] : null;
			$autochanger = key_exists('autochanger', $job_info) ? $job_info['autochanger']['name'] : null;
			$jobdata->storage = $storage ?: $autochanger;
			$this->getPage()->getCallbackClient()->show('run_job_storage_from_config_info');
		} elseif (!empty($jobname)) {
			$jobdata = new stdClass;
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
			$jobdata->storage = $storage ?: $autochanger;
			$jobdata->priorjobid = $priority;
			$jobdata->accurate = ($accurate == 1);
		} else {
			$jobs = array();
			$job_list = $this->getModule('api')->get(array('jobs', 'resnames'), null, true, self::USE_CACHE)->output;
			foreach ($job_list as $director => $job) {
				// Note for doubles for different dirs with different databases
				$jobs = array_merge($jobs, $job);
			}
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
		$this->Level->dataSource = $this->getModule('misc')->getJobLevels();
		$is_verify_option = false;
		if (is_object($jobdata) && property_exists($jobdata, 'level')) {
			$this->Level->SelectedValue = $jobdata->level;
			$is_verify_option = is_object($jobdata) && in_array($jobdata->level, $this->job_to_verify);
		}
		$this->Level->dataBind();

		$this->JobToVerifyOptionsLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';
		$this->JobToVerifyJobNameLine->Display = ($is_verify_option === true) ? 'Dynamic' : 'None';
		$this->JobToVerifyJobIdLine->Display = 'None';

		$verify_values = array();
		foreach($this->verify_options as $value => $text) {
			$verify_values[$value] = Prado::localize($text);
		}
		$this->JobToVerifyOptions->dataSource = $verify_values;
		$this->JobToVerifyOptions->dataBind();

		$jobTasks = $this->getModule('api')->get(array('jobs', 'resnames'), null, true, self::USE_CACHE)->output;
		$jobsAllDirs = array();
		foreach($jobTasks as $director => $tasks) {
			$jobsAllDirs = array_merge($jobsAllDirs, $tasks);
		}

		$this->JobToVerifyJobName->dataSource = array_combine($jobsAllDirs, $jobsAllDirs);
		$this->JobToVerifyJobName->dataBind();

		$clients = $this->getModule('api')->get(array('clients'), null, true, self::USE_CACHE)->output;
		$client_list = array();
		foreach($clients as $client) {
			if (is_object($jobdata) && property_exists($jobdata, 'client') && $client->name === $jobdata->client) {
				$jobdata->clientid = $client->clientid;
			}
			$client_list[$client->clientid] = $client->name;
		}
		$this->Client->dataSource = $client_list;
		if (is_object($jobdata)) {
			$this->Client->SelectedValue = $jobdata->clientid;
		}
		$this->Client->dataBind();

		$fileset_all = $this->getModule('api')->get(array('filesets', 'resnames'), null, true, self::USE_CACHE)->output;
		$fileset_list = array();
		foreach($fileset_all as $director => $filesets) {
			$fileset_list = array_merge($filesets, $fileset_list);
		}
		$selected_fileset = '';
		if(is_object($jobdata)) {
			if (property_exists($jobdata, 'fileset')) {
				$selected_fileset = $jobdata->fileset;
			} elseif ($jobdata->filesetid != 0) {
				$fileset = $this->getModule('api')->get(
					array('filesets', $jobdata->filesetid), null, true, self::USE_CACHE
				);
				if ($fileset->error === 0) {
					$selected_fileset = $fileset->output->fileset;
				}
		       }
		}
		$this->FileSet->dataSource = array_combine($fileset_list, $fileset_list);
		$this->FileSet->SelectedValue =  $selected_fileset;
		$this->FileSet->dataBind();

		$pools = $this->getModule('api')->get(array('pools'), null, true, self::USE_CACHE)->output;
		$pool_list = array();
		foreach($pools as $pool) {
			if (is_object($jobdata) && property_exists($jobdata, 'pool') && $pool->name === $jobdata->pool) {
				$jobdata->poolid = $pool->poolid;
			}
			$pool_list[$pool->poolid] = $pool->name;
		}
		$this->Pool->dataSource = $pool_list;
		if (is_object($jobdata)) {
			$this->Pool->SelectedValue = $jobdata->poolid;
		}
		$this->Pool->dataBind();

		if (is_object($jobdata) && !property_exists($jobdata, 'storage')) {
			$jobshow = $this->getModule('api')->get(
				array('jobs', $jobdata->jobid, 'show'), null, true, self::USE_CACHE
			)->output;
			$jobdata->storage = $this->getResourceName('storage', $jobshow);
		}
		$storage_list = array();
		$storages = $this->getModule('api')->get(array('storages'), null, true, self::USE_CACHE)->output;
		foreach($storages as $storage) {
			if (is_object($jobdata) && property_exists($jobdata, 'storage') && $storage->name === $jobdata->storage) {
				$jobdata->storageid = $storage->storageid;
			}
			$storage_list[$storage->storageid] = $storage->name;
		}
		$this->Storage->dataSource = $storage_list;
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

	public function selectJobValues($sender, $param) {
		$this->setJobName($sender->SelectedValue);
		$this->loadData();
	}

	/**
	 * set jobid to run job again.
	 *
	 * @return none;
	 */
	public function setJobId($jobid) {
		$jobid = intval($jobid);
		$this->setViewState(self::JOBID, $jobid, 0);
	}

	/**
	 * Get jobid to run job again.
	 *
	 * @return integer jobid
	 */
	public function getJobId() {
		return $this->getViewState(self::JOBID, 0);
	}

	/**
	 * set job name to run job again.
	 *
	 * @return none;
	 */
	public function setJobName($job_name) {
		$this->setViewState(self::JOB_NAME, $job_name);
	}

	/**
	 * Get job name to run job again.
	 *
	 * @return string job name
	 */
	public function getJobName() {
		return $this->getViewState(self::JOB_NAME);
	}

	public function priorityValidator($sender, $param) {
		$isValid = preg_match('/^[0-9]+$/',$this->Priority->Text) === 1 && $this->Priority->Text > 0;
		$param->setIsValid($isValid);
	}

	public function jobIdToVerifyValidator($sender, $param) {
		$verifyVals = $this->getVerifyVals();
		if (in_array($this->Level->SelectedValue, $this->job_to_verify) && $this->JobToVerifyOptions->SelectedItem->Value == $verifyVals['jobid']) {
			$isValid = preg_match('/^[0-9]+$/',$this->JobToVerifyJobId->Text) === 1 && $this->JobToVerifyJobId->Text > 0;
		} else {
			$isValid = true;
		}
		$param->setIsValid($isValid);
		return $isValid;
	}

	private function getVerifyVals() {
		$verifyOpt = array_keys($this->verify_options);
		$verifyVals = array_combine($verifyOpt, $verifyOpt);
		return $verifyVals;
	}

	public function estimate($sender, $param) {
		$params = array();
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
		$params['accurate'] = (integer)$this->Accurate->Checked;
		$result = $this->getModule('api')->create(array('jobs', 'estimate'), $params);
		if ($result->error === 0 && count($result->output) == 1) {
			$out = json_decode($result->output[0]);
			if (is_object($out) && property_exists($out, 'out_id')) {
				$result = $this->getEstimateOutput($out->out_id);
				$this->getPage()->getCallbackClient()->callClientFunction(
					'estimate_output_refresh',
					array($out->out_id)
				);
			}
		}

		if ($result->error === 0) {
			$this->getPage()->getCallbackClient()->callClientFunction('set_loading_status', array('loading'));
			$this->RunJobLog->Text = implode('', $result->output);
		} else {
			$this->RunJobLog->Text = $result->output;
		}
	}

	public function getEstimateOutput($out_id) {
		$result = $this->getModule('api')->get(
			array('jobs', 'estimate', '?out_id=' . rawurlencode($out_id))
		);
		return $result;
	}

	public function refreshEstimateOutput($sender, $param) {
		$out_id = $param->getCallbackParameter();
		$result = $this->getEstimateOutput($out_id);

		if ($result->error === 0) {
			if (count($result->output) > 0) {
				$this->RunJobLog->Text = implode('', $result->output);
				$this->getPage()->getCallbackClient()->callClientFunction(
					'estimate_output_refresh',
					array($out_id)
				);
			} else {
				$this->getPage()->getCallbackClient()->callClientFunction(
					'set_loading_status',
					array('finish')
				);
			}
		} else {
			$this->RunJobLog->Text = $result->output;
		}
	}
	public function runJobAgain($sender, $param) {
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
		$params['accurate'] = (integer)$this->Accurate->Checked;

		if (!empty($this->Level->SelectedItem) && in_array($this->Level->SelectedItem->Value, $this->job_to_verify)) {
			$verifyVals = $this->getVerifyVals();
			if ($this->JobToVerifyOptions->SelectedItem->Value == $verifyVals['jobname']) {
				$params['verifyjob'] = $this->JobToVerifyJobName->SelectedValue;
			} elseif ($this->JobToVerifyOptions->SelectedItem->Value == $verifyVals['jobid']) {
				$params['jobid'] = $this->JobToVerifyJobId->Text;
			}
		}
		$result = $this->getModule('api')->create(array('jobs', 'run'), $params);
		if ($result->error === 0) {
			$started_jobid = $this->getModule('misc')->findJobIdStartedJob($result->output);
			if (is_numeric($started_jobid)) {
				$this->getPage()->getCallbackClient()->callClientFunction('run_job_go_to_running_job', $started_jobid);
			} else {
				$this->RunJobLog->Text = implode('', $result->output);
				$this->getPage()->getCallbackClient()->callClientFunction('show_job_log', true);
			}
		} else {
			$this->RunJobLog->Text = $result->output;
			$this->getPage()->getCallbackClient()->callClientFunction('show_job_log', true);
		}
	}
}
?>
