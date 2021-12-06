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

Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('System.Web.UI.ActiveControls.TCallback');
Prado::using('Application.Web.Class.BaculumWebPage'); 

/**
 * Job view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class JobView extends BaculumWebPage {

	const JOB_NAME = 'JobName';
	const JOB_INFO = 'JobInfo';

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$job_name = '';
		if ($this->Request->contains('job')) {
			$job_name = $this->Request['job'];
		}
		$this->RunJobModal->setJobName($job_name);
		$this->setJobName($job_name);
		$this->Schedules->setJob($job_name);
		$this->Schedules->setDays(90);
		$this->setJobInfo($job_name);
	}

	/**
	 * Set job name to run job again.
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

	/**
	 * Set job information from show job output.
	 *
	 * @return none
	 */
	public function setJobInfo($job_name) {
		$job_show = $this->getModule('api')->get(
			array('jobs', 'show', '?name='. rawurlencode($job_name)),
			null,
			true,
			false
		);
		if ($job_show->error == 0) {
			$job_info = $this->getModule('job_info')->parseResourceDirectives($job_show->output);
			$this->setViewState(self::JOB_INFO, $job_info);
		}
	}

	/**
	 * Get job information.
	 *
	 * @return array job information
	 */
	public function getJobInfo() {
		return $this->getViewState(self::JOB_INFO, []);
	}

	/**
	 * Reload job information.
	 *
	 * @param BaculaConfigDirectives $sender sender object
	 * @param mixed $param save event parameter
	 * @return none
	 */
	public function reloadJobInfo($sender, $param) {
		if ($this->Request->contains('job')) {
			$this->setJobInfo($this->Request['job']);
		}
	}

	public function loadRunJobModal($sender, $param) {
		$this->RunJobModal->loadData();
	}

	public function loadJobConfig($sender, $param) {
		if (!empty($_SESSION['dir'])) {
			$this->JobConfig->setComponentName($_SESSION['dir']);
			$this->JobConfig->setResourceName($this->getJobName());
			$this->JobConfig->setLoadValues(true);
			$this->JobConfig->raiseEvent('OnDirectiveListLoad', $this, null);
			$this->FileSetConfig->unloadDirectives();
			$this->ScheduleConfig->unloadDirectives();
		}
	}

	public function loadFileSetConfig($sender, $param) {
		if (!empty($_SESSION['dir'])) {
			$job_info = $this->getJobInfo();
			if (key_exists('fileset', $job_info)) {
				$this->FileSetConfig->setComponentName($_SESSION['dir']);
				$this->FileSetConfig->setResourceName($job_info['fileset']['name']);
				$this->FileSetConfig->setLoadValues(true);
				$this->FileSetConfig->raiseEvent('OnDirectiveListLoad', $this, null);
				$this->JobConfig->unloadDirectives();
				$this->ScheduleConfig->unloadDirectives();
			}
		}
	}

	public function loadScheduleConfig($sender, $param) {
		if (!empty($_SESSION['dir'])) {
			$job_info = $this->getJobInfo();
			if (key_exists('schedule', $job_info)) {
				$this->ScheduleConfig->setComponentName($_SESSION['dir']);
				$this->ScheduleConfig->setResourceName($job_info['schedule']['name']);
				$this->ScheduleConfig->setLoadValues(true);
				$this->ScheduleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
				$this->JobConfig->unloadDirectives();
				$this->FileSetConfig->unloadDirectives();
			} else {
				$this->ScheduleConfig->unloadDirectives();
			}
		}
	}

	public function loadSchedules($sender, $param) {
		$this->Schedules->loadSchedules();
	}
}
?>
