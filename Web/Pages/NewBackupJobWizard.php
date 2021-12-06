<?php
/*
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2021 Kern Sibbald
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

Prado::using('Application.Web.Class.BaculumWebPage'); 
Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.WebControls.TWizard');

/**
 * New backup job wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class NewBackupJobWizard extends BaculumWebPage {

	const PREV_STEP = 'PrevStep';
	const JOBDEFS = 'JobDefs';

	public function onPreRender($param) {
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
				$this->loadJobDefs();
				break;
			}
			case 1:	{
				$this->setupJobDefs();
				$this->loadClients();
				$this->loadFilesets();
				$this->loadNewFilesetForm();
				break;
			}
			case 2: {
				$this->loadStorages();
				$this->loadNewPoolForm();
				$this->loadPools();
				break;
			}
			case 3: {
				$this->loadBackupJobDirectives();
				$this->loadLevels();
				$this->loadMessages();
				break;
			}
			case 4: {
				$this->loadRescheduleDirectives();
				$this->loadNewScheduleForm();
				$this->loadSchedules();
				break;
			}
		}
	}

	/**
	 * Wizard previous button callback actions.
	 *
	 * @param TWizard $sender sender object
	 * @param TWizardNavigationEventParameter $param sender parameters
	 * @return none
	 */
	public function wizardPrev($sender, $param) {
	}

	/**
	 * Wizard next button callback actions.
	 *
	 * @param TWizard $sender sender object
	 * @param TWizardNavigationEventParameter $param sender parameters
	 * @return none
	 */
	public function wizardNext($sender, $param) {
	}


	/**
	 * Load JobDefs (step 1).
	 *
	 * @return none
	 */
	public function loadJobDefs() {
		$jobdefs_list = array();
		$jobdefs = $this->getModule('api')->get([
			'config', 'dir', 'jobdefs'
		])->output;
		for ($i = 0; $i < count($jobdefs); $i++) {
			$jobdefs_list[] = $jobdefs[$i]->JobDefs->Name;
		}
		asort($jobdefs_list);
		$this->JobDefs->setData($jobdefs_list);
		$this->JobDefs->createDirective();
	}

	/**
	 * Setup and remember selected JobDefs values to use in next wizard steps.
	 *
	 * @return none
	 */
	public function setupJobDefs() {
		$directive_value = $this->JobDefs->getDirectiveValue();
		if (is_null($directive_value)) {
			return;
		}
		$jobdefs = rawurlencode($directive_value);
		$result = $this->getModule('api')->get([
			'config', 'dir', 'jobdefs', $jobdefs
		]);
		if ($result->error === 0) {
			$value = (array)$result->output;
			$this->setJobDefs($value);
		}
	}

	public function isInJobDefs($directive_name, $directive_value) {
		$jobdefs = $this->getJobDefs();
		$ret = false;
		if ($directive_name === 'Storage') {
			$ret = (key_exists($directive_name, $jobdefs) && $jobdefs[$directive_name][0] === $directive_value);
		} else {
			$ret = (key_exists($directive_name, $jobdefs) && $jobdefs[$directive_name] === $directive_value);
		}
		return $ret;
	}

	/**
	 * Load client list (step 2).
	 *
	 * @return none
	 */
	public function loadClients() {
		$client_list = array();
		$clients = $this->getModule('api')->get(array('clients'))->output;
		for ($i = 0; $i < count($clients); $i++) {
			$client_list[$clients[$i]->name] = $clients[$i]->name;
		}
		asort($client_list);
		$this->Client->setData($client_list);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Client', $jobdefs) && is_null($this->Client->getDirectiveValue())) {
			$this->Client->setDirectiveValue($jobdefs['Client']);
		}
		$this->Client->createDirective();
	}

	/**
	 * Load fileset list (step 2).
	 *
	 * @return none
	 */
	public function loadFilesets() {
		$this->loadFilesetList(null, null);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Fileset', $jobdefs) && is_null($this->Fileset->getDirectiveValue())) {
			$this->Fileset->setDirectiveValue($jobdefs['Fileset']);
		}
	}

	public function loadFilesetList($sender, $param) {
		$fileset_list = array();
		$filesets = $this->getModule('api')->get(array('config', 'dir', 'fileset'))->output;
		for ($i = 0; $i < count($filesets); $i++) {
			$fileset_list[] = $filesets[$i]->Fileset->Name;
		}
		asort($fileset_list);
		$this->Fileset->setData($fileset_list);
		$this->Fileset->createDirective();
	}

	/**
	 * Load new fileset form.
	 *
	 * @return none
	 */
	public function loadNewFilesetForm() {
		if ($this->IsCallBack) {
			return;
		}
		if (!empty($_SESSION['dir'])) {
			$this->FilesetConfig->setComponentName($_SESSION['dir']);
			$this->FilesetConfig->setLoadValues(false);
			$this->FilesetConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Load new pool form.
	 *
	 * @return none
	 */
	public function loadNewPoolForm() {
		if ($this->IsCallBack) {
			return;
		}
		if (!empty($_SESSION['dir'])) {
			$this->PoolConfig->setComponentName($_SESSION['dir']);
			$this->PoolConfig->setLoadValues(false);
			$this->PoolConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Load new schedule form.
	 *
	 * @return none
	 */
	public function loadNewScheduleForm() {
		if ($this->IsCallBack) {
			return;
		}
		if (!empty($_SESSION['dir'])) {
			$this->ScheduleConfig->setComponentName($_SESSION['dir']);
			$this->ScheduleConfig->setLoadValues(false);
			$this->ScheduleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Load storage list (step 2).
	 *
	 * @return none
	 */
	public function loadStorages() {
		$storage_list = array();
		$storages = $this->getModule('api')->get(array('config', 'dir', 'storage'))->output;
		for ($i = 0; $i < count($storages); $i++) {
			$storage_list[] = $storages[$i]->Storage->Name;
		}
		asort($storage_list);
		$this->Storage->setData($storage_list);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Storage', $jobdefs) && is_array($jobdefs['Storage']) && count($jobdefs['Storage']) == 1 && is_null($this->Storage->getDirectiveValue())) {
			$this->Storage->setDirectiveValue($jobdefs['Storage'][0]);
			$this->Storage->createDirective();
		}
		if (key_exists('SpoolData', $jobdefs) && is_null($this->SpoolData->getDirectiveValue())) {
			$this->SpoolData->setDirectiveValue($jobdefs['SpoolData']);
			$this->SpoolData->createDirective();
		}
		if (key_exists('SpoolAttributes', $jobdefs) && is_null($this->SpoolAttributes->getDirectiveValue())) {
			$this->SpoolAttributes->setDirectiveValue($jobdefs['SpoolAttributes']);
			$this->SpoolAttributes->createDirective();
		}
		if (key_exists('SpoolSize', $jobdefs) && is_null($this->SpoolSize->getDirectiveValue())) {
			$this->SpoolSize->setDirectiveValue($jobdefs['SpoolSize']);
			$this->SpoolSize->createDirective();
		}
	}

	/**
	 * Load pool list (step 2).
	 *
	 * @return none
	 */
	public function loadPools() {
		$pool_list = $this->loadPoolList(null, null);
		$jobdefs = $this->getJobDefs();
		$this->FullBackupPool->setData($pool_list);
		if (key_exists('FullBackupPool', $jobdefs) && is_null($this->FullBackupPool->getDirectiveValue())) {
			$this->FullBackupPool->setDirectiveValue($jobdefs['FullBackupPool']);
		}
		$this->FullBackupPool->createDirective();
		$this->IncrementalBackupPool->setData($pool_list);
		if (key_exists('IncrementalBackupPool', $jobdefs) && is_null($this->IncrementalBackupPool->getDirectiveValue())) {
			$this->IncrementalBackupPool->setDirectiveValue($jobdefs['IncrementalBackupPool']);
		}
		$this->IncrementalBackupPool->createDirective();
		$this->DifferentialBackupPool->setData($pool_list);
		if (key_exists('DifferentialBackupPool', $jobdefs) && is_null($this->DifferentialBackupPool->getDirectiveValue())) {
			$this->DifferentialBackupPool->setDirectiveValue($jobdefs['DifferentialBackupPool']);
		}
		$this->DifferentialBackupPool->createDirective();
		if (key_exists('Pool', $jobdefs) && is_null($this->Pool->getDirectiveValue())) {
			$this->Pool->setDirectiveValue($jobdefs['Pool']);
		}
		$this->Pool->createDirective();
	}

	public function loadPoolList($sender, $param) {
		$pool_list = array();
		$pools = $this->getModule('api')->get(array('config', 'dir', 'pool'))->output;
		for ($i = 0; $i < count($pools); $i++) {
			$pool_list[] = $pools[$i]->Pool->Name;
		}
		asort($pool_list);
		$this->Pool->setData($pool_list);
		$this->Pool->createDirective();
		return $pool_list;
	}

	public function loadBackupJobDirectives() {
		$jobdefs = $this->getJobDefs();
		if (key_exists('Accurate', $jobdefs) && is_null($this->Accurate->getDirectiveValue())) {
			$this->Accurate->setDirectiveValue($jobdefs['Accurate']);
			$this->Accurate->createDirective();
		}
		if (key_exists('MaximumConcurrentJobs', $jobdefs) && is_null($this->MaximumConcurrentJobs->getDirectiveValue())) {
			$this->MaximumConcurrentJobs->setDirectiveValue($jobdefs['MaximumConcurrentJobs']);
			$this->MaximumConcurrentJobs->createDirective();
		}
		if (key_exists('Priority', $jobdefs) && is_null($this->Priority->getDirectiveValue())) {
			$this->Priority->setDirectiveValue($jobdefs['Priority']);
			$this->Priority->createDirective();
		}
		if (key_exists('ReRunFailedLevels', $jobdefs) && is_null($this->ReRunFailedLevels->getDirectiveValue())) {
			$this->ReRunFailedLevels->setDirectiveValue($jobdefs['ReRunFailedLevels']);
			$this->ReRunFailedLevels->createDirective();
		}
	}

	public function loadRescheduleDirectives() {
		$jobdefs = $this->getJobDefs();
		if (key_exists('RescheduleOnError', $jobdefs) && is_null($this->RescheduleOnError->getDirectiveValue())) {
			$this->RescheduleOnError->setDirectiveValue($jobdefs['RescheduleOnError']);
			$this->RescheduleOnError->createDirective();
		}
		if (key_exists('RescheduleIncompleteJobs', $jobdefs) && is_null($this->RescheduleIncompleteJobs->getDirectiveValue())) {
			$this->RescheduleIncompleteJobs->setDirectiveValue($jobdefs['RescheduleIncompleteJobs']);
			$this->RescheduleIncompleteJobs->createDirective();
		}
		if (key_exists('RescheduleInterval', $jobdefs) && is_null($this->RescheduleInterval->getDirectiveValue())) {
			$this->RescheduleInterval->setDirectiveValue($jobdefs['RescheduleInterval']);
			$this->RescheduleInterval->createDirective();
		}
		if (key_exists('RescheduleTimes', $jobdefs) && is_null($this->RescheduleTimes->getDirectiveValue())) {
			$this->RescheduleTimes->setDirectiveValue($jobdefs['RescheduleTimes']);
			$this->RescheduleTimes->createDirective();
		}
	}


	/**
	 * Load job levels.
	 *
	 * @return none
	 */
	public function loadLevels() {
		// so far backup job levels only
		$level_list = array(
			'Full', 'Incremental', 'Differential', 'VirtualFull'
		);
		$this->Level->setData($level_list);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Level', $jobdefs)) {
			$this->Level->setDirectiveValue($jobdefs['Level']);
		}
		$this->Level->createDirective();
	}
	/**
	 * Load messages.
	 *
	 * @return none
	 */
	public function loadMessages() {
		$message_list = array();
		$messages = $this->getModule('api')->get(array('config', 'dir', 'messages'))->output;
		for ($i = 0; $i < count($messages); $i++) {
			$message_list[] = $messages[$i]->Messages->Name;
		}
		asort($message_list);
		$this->Messages->setData($message_list);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Messages', $jobdefs)) {
			$this->Messages->setDirectiveValue($jobdefs['Messages']);
		}
		$this->Messages->createDirective();
	}

	/**
	 * Load schedule.
	 *
	 * @return none
	 */
	public function loadSchedules() {
		$this->loadScheduleList(null, null);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Schedule', $jobdefs)) {
			$this->Schedule->setDirectiveValue($jobdefs['Schedule']);
		}
		$this->Schedule->createDirective();
	}

	public function loadScheduleList($sender, $param) {
		$schedule_list = array();
		$schedules = $this->getModule('api')->get(array('config', 'dir', 'schedule'))->output;
		for ($i = 0; $i < count($schedules); $i++) {
			$schedule_list[] = $schedules[$i]->Schedule->Name;
		}
		asort($schedule_list);
		$this->Schedule->setData($schedule_list);
		$this->Schedule->createDirective();
	}

	public function wizardCompleted($sender, $param) {
		$jobdefs = $this->getJobDefs();
		$job = array(
			'Name' => $this->Name->getDirectiveValue(),
			'Type' => 'Backup',
		);
		$jd = $this->JobDefs->getDirectiveValue();
		$directives = array('Description', 'Client', 'Fileset', 'Storage', 'SpoolData', 'SpoolAttributes',
			'SpoolSize', 'Pool', 'FullBackupPool', 'IncrementalBackupPool', 'DifferentialBackupPool',
			'Level', 'Accurate', 'MaximumConcurrentJobs', 'Priority', 'ReRunFailedLevels', 'Schedule',
			'RescheduleOnError', 'RescheduleIncompleteJobs', 'RescheduleInterval', 'RescheduleTimes',
			'Messages'
		);
		if (is_string($jd)) {
			$job['JobDefs'] = $jd;
		}
		for ($i = 0; $i < count($directives); $i++) {
			$val = $this->{$directives[$i]}->getDirectiveValue();
			if (is_null($val)) {
				continue;
			}
			if (is_null($jd) || !$this->isInJobDefs($directives[$i], $val)) {
				$job[$directives[$i]] = $val;
			}
		}
		$params = array(
			'config',
			'dir',
			'Job',
			$job['Name']
		);
		$result = $this->getModule('api')->set(
			$params,
			array('config' => json_encode($job))
		);
		if ($result->error === 0) {
			$this->getModule('api')->set(array('console'), array('reload'));
			$this->goToPage('JobList');
		} else {
			$this->CreateResourceErrMsg->Display = 'None';
			$this->CreateResourceErrMsg->Text = '';
		}
	}

	/**
	 * Cancel wizard.
	 *
	 * @return none
	 */
	public function wizardStop($sender, $param) {
		$this->goToDefaultPage();
	}

	/**
	 * Set selected JobDefs values.
	 *
	 * @param $jobdefs selected JobDefs values
	 * @return none
	 */
	public function setJobDefs($jobdefs) {
		$this->setViewState(self::JOBDEFS, $jobdefs);
	}

	/**
	 * Get selected JobDefs values.
	 *
	 * @return array selected JobDefs values
	 */
	public function getJobDefs() {
		return $this->getViewState(self::JOBDEFS, array());
	}

	/**
	 * Set previous wizard step.
	 *
	 * @param integer $step previous step number
	 * @return none
	 */
	public function setPrevStep($step) {
		$step = intval($step);
		$this->setViewState(self::PREV_STEP, $step);
	}

	/**
	 * Get previous wizard step.
	 *
	 * @return integer previous wizard step
	 */
	public function getPrevStep() {
		return $this->getViewState(self::PREV_STEP);
	}
}
?>
