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

use Bacularis\Web\Modules\BaculumWebPage;
use Prado\Web\UI\WebControls\TWizard;

/**
 * New backup job wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewBackupJobWizard extends BaculumWebPage
{
	public const PREV_STEP = 'PrevStep';
	public const JOBDEFS = 'JobDefs';

	public function onPreRender($param)
	{
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
	 */
	public function wizardPrev($sender, $param)
	{
	}

	/**
	 * Wizard next button callback actions.
	 *
	 * @param TWizard $sender sender object
	 * @param TWizardNavigationEventParameter $param sender parameters
	 */
	public function wizardNext($sender, $param)
	{
	}


	/**
	 * Load JobDefs (step 1).
	 *
	 */
	public function loadJobDefs()
	{
		$jobdefs_list = [];
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
	 */
	public function setupJobDefs()
	{
		$directive_value = $this->JobDefs->getDirectiveValue();
		if (is_null($directive_value)) {
			return;
		}
		$jobdefs = rawurlencode($directive_value);
		$result = $this->getModule('api')->get([
			'config', 'dir', 'jobdefs', $jobdefs
		]);
		if ($result->error === 0) {
			$value = (array) $result->output;
			$this->setJobDefs($value);
		}
	}

	public function isInJobDefs($directive_name, $directive_value)
	{
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
	 */
	public function loadClients()
	{
		$client_list = [];
		$clients = $this->getModule('api')->get(['clients'])->output;
		for ($i = 0; $i < count($clients); $i++) {
			$client_list[] = $clients[$i]->name;
		}
		sort($client_list, SORT_NATURAL | SORT_FLAG_CASE);
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
	 */
	public function loadFilesets()
	{
		$this->loadFilesetList(null, null);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Fileset', $jobdefs) && is_null($this->Fileset->getDirectiveValue())) {
			$this->Fileset->setDirectiveValue($jobdefs['Fileset']);
		}
	}

	public function loadFilesetList($sender, $param)
	{
		$fileset_list = [];
		$filesets = $this->getModule('api')->get(['config', 'dir', 'fileset'])->output;
		for ($i = 0; $i < count($filesets); $i++) {
			$fileset_list[] = $filesets[$i]->Fileset->Name;
		}
		sort($fileset_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->Fileset->setData($fileset_list);
		$this->Fileset->createDirective();
	}

	/**
	 * Load new fileset form.
	 *
	 */
	public function loadNewFilesetForm()
	{
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
	 */
	public function loadNewPoolForm()
	{
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
	 */
	public function loadNewScheduleForm()
	{
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
	 */
	public function loadStorages()
	{
		$storage_list = [];
		$storages = $this->getModule('api')->get(['config', 'dir', 'storage'])->output;
		for ($i = 0; $i < count($storages); $i++) {
			$storage_list[] = $storages[$i]->Storage->Name;
		}
		sort($storage_list, SORT_NATURAL | SORT_FLAG_CASE);
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
	 */
	public function loadPools()
	{
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

	public function loadPoolList($sender, $param)
	{
		$pool_list = [];
		$pools = $this->getModule('api')->get(['config', 'dir', 'pool'])->output;
		for ($i = 0; $i < count($pools); $i++) {
			$pool_list[] = $pools[$i]->Pool->Name;
		}
		sort($pool_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->Pool->setData($pool_list);
		$this->Pool->createDirective();
		return $pool_list;
	}

	public function loadBackupJobDirectives()
	{
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

	public function loadRescheduleDirectives()
	{
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
	 */
	public function loadLevels()
	{
		// so far backup job levels only
		$level_list = [
			'Full', 'Incremental', 'Differential', 'VirtualFull'
		];
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
	 */
	public function loadMessages()
	{
		$message_list = [];
		$messages = $this->getModule('api')->get(['config', 'dir', 'messages'])->output;
		for ($i = 0; $i < count($messages); $i++) {
			$message_list[] = $messages[$i]->Messages->Name;
		}
		sort($message_list, SORT_NATURAL | SORT_FLAG_CASE);
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
	 */
	public function loadSchedules()
	{
		$this->loadScheduleList(null, null);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Schedule', $jobdefs)) {
			$this->Schedule->setDirectiveValue($jobdefs['Schedule']);
		}
		$this->Schedule->createDirective();
	}

	public function loadScheduleList($sender, $param)
	{
		$schedule_list = [];
		$schedules = $this->getModule('api')->get(['config', 'dir', 'schedule'])->output;
		for ($i = 0; $i < count($schedules); $i++) {
			$schedule_list[] = $schedules[$i]->Schedule->Name;
		}
		sort($schedule_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->Schedule->setData($schedule_list);
		$this->Schedule->createDirective();
	}

	public function wizardCompleted($sender, $param)
	{
		$jobdefs = $this->getJobDefs();
		$job = [
			'Name' => $this->Name->getDirectiveValue(),
			'Type' => 'Backup',
		];
		$jd = $this->JobDefs->getDirectiveValue();
		$directives = ['Description', 'Client', 'Fileset', 'Storage', 'SpoolData', 'SpoolAttributes',
			'SpoolSize', 'Pool', 'FullBackupPool', 'IncrementalBackupPool', 'DifferentialBackupPool',
			'Level', 'Accurate', 'MaximumConcurrentJobs', 'Priority', 'ReRunFailedLevels', 'Schedule',
			'RescheduleOnError', 'RescheduleIncompleteJobs', 'RescheduleInterval', 'RescheduleTimes',
			'Messages'
		];
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
		$params = [
			'config',
			'dir',
			'Job',
			$job['Name']
		];
		$result = $this->getModule('api')->create(
			$params,
			['config' => json_encode($job)]
		);
		if ($result->error === 0) {
			$this->getModule('api')->set(['console'], ['reload']);
			$this->goToPage('JobList');
		} else {
			$this->CreateResourceErrMsg->Display = 'None';
			$this->CreateResourceErrMsg->Text = '';
		}
	}

	/**
	 * Cancel wizard.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function wizardStop($sender, $param)
	{
		$this->goToPage('JobList');
	}

	/**
	 * Set selected JobDefs values.
	 *
	 * @param $jobdefs selected JobDefs values
	 */
	public function setJobDefs($jobdefs)
	{
		$this->setViewState(self::JOBDEFS, $jobdefs);
	}

	/**
	 * Get selected JobDefs values.
	 *
	 * @return array selected JobDefs values
	 */
	public function getJobDefs()
	{
		return $this->getViewState(self::JOBDEFS, []);
	}

	/**
	 * Set previous wizard step.
	 *
	 * @param int $step previous step number
	 */
	public function setPrevStep($step)
	{
		$step = (int) $step;
		$this->setViewState(self::PREV_STEP, $step);
	}

	/**
	 * Get previous wizard step.
	 *
	 * @return int previous wizard step
	 */
	public function getPrevStep()
	{
		return $this->getViewState(self::PREV_STEP);
	}
}
