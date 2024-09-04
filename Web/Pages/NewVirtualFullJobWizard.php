<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Params;
use Bacularis\Web\Modules\BaculumWebPage;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\WebControls\TWizard;

/**
 * Virtual Full job wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewVirtualFullJobWizard extends BaculumWebPage
{
	public const PREV_STEP = 'PrevStep';
	public const JOBDEFS = 'JobDefs';

	public function onLoad($param)
	{
		parent::onLoad($param);
		$step_index = $this->NewVirtualFullJobWizard->getActiveStepIndex();
		if ($this->IsPostBack) {
			if ($step_index == 0 || $step_index == 2 || $step_index == 3) {
				$this->setStorageServerSideValidators();
				$this->setStorageClientSideValidators();
			} elseif ($step_index == 4 || $step_index == 5) {
				$this->setScheduleServerSideValidators();
				$this->setScheduleClientSideValidators();
			}
		}
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->IsCallBack) {
			return;
		}
		$step_index = $this->NewVirtualFullJobWizard->getActiveStepIndex();
		$prev_step = $this->getPrevStep();
		$this->setPrevStep($step_index);
		if ($prev_step > $step_index) {
			return;
		}
		if ($step_index == 1 && $this->WhatToDoWithVirtualFullExistingJob->Checked) {
			$this->NewVirtualFullJobWizard->setActiveStepIndex(2);
			$step_index = 2;
		}
		switch ($step_index) {
			case 0:	{
				$this->loadJobDefs();
				$this->loadBackupJobs();
				break;
			}
			case 1:	{
				$this->setupJobDefs();
				$this->loadNormalStorageControl();
				$this->loadVirtualFullStorageControl();
				$this->loadClients();
				$this->loadFilesets();
				$this->loadNewFilesetForm();
				break;
			}
			case 2: {
				$this->loadNormalStorageControl();
				$this->loadVirtualFullStorageControl();
				$this->loadPools(null, null);
				$this->loadNewPoolForm();
				break;
			}
			case 3: {
				$this->setupScheduleOptions();
				$this->loadBackupJobDirectives();
				$this->loadJobHistorySlider();
				$this->loadMessages();
				break;
			}
			case 4: {
				$this->loadSchedules();
				$this->loadScheduleNewConfig();
				$this->setScheduleClientSideValidators();
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
	 * Load JobDefs.
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
		sort($jobdefs_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->JobDefs->setData($jobdefs_list);
		$this->JobDefs->createDirective();
	}

	/**
	 * Setup and remember selected JobDefs values to use in next wizard steps.
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

	/**
	 * Check if directive is already defined in JobDefs.
	 *
	 * @param string $directive_name directive name
	 * @param string $directive_value directive value
	 * @return bool true if directive value is defined in JobDefs, false otherwise
	 */
	public function isInJobDefs($directive_name, $directive_value)
	{
		$jobdefs = $this->getJobDefs();
		if ($directive_name == 'Priority') {
			$directive_value = (int) $directive_value;
		}
		$ret = (key_exists($directive_name, $jobdefs) && $jobdefs[$directive_name] === $directive_value);
		return $ret;
	}

	/**
	 * Get selected directive value from existing job.
	 *
	 * @param string $job_name job name to get directive value
	 * @param string $directive_name directive name
	 * @return mixed directive value or null if directive not found or in case errors
	 */
	private function getExistingJobDirective($job_name, $directive_name)
	{
		$value = null;
		$result = $this->getModule('api')->get([
			'config', 'dir', 'Job', $job_name, '?apply_jobdefs=1'
		]);
		if ($result->error === 0) {
			if (property_exists($result->output, $directive_name)) {
				$value = $result->output->{$directive_name};
			}
		}
		return $value;
	}

	/**
	 * Load existing backup jobs.
	 */
	public function loadBackupJobs()
	{
		$job_list = [];
		$jobs = $this->getModule('api')->get([
			'config', 'dir', 'job', '?apply_jobdefs=1'
		])->output;
		for ($i = 0; $i < count($jobs); $i++) {
			if ($jobs[$i]->Job->Type !== 'Backup') {
				continue;
			}
			$job_list[] = $jobs[$i]->Job->Name;
		}
		sort($job_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->ExistingJob->setData($job_list);
		$this->ExistingJob->createDirective();
	}

	/**
	 * Load client list.
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
	 * Load fileset list.
	 */
	public function loadFilesets()
	{
		$this->loadFilesetList();
		$jobdefs = $this->getJobDefs();
		if (key_exists('Fileset', $jobdefs) && is_null($this->Fileset->getDirectiveValue())) {
			$this->Fileset->setDirectiveValue($jobdefs['Fileset']);
		}
	}

	/**
	 * Load list with FileSets.
	 */
	public function loadFilesetList()
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
	 * Load storage control with storages for normal backup.
	 */
	public function loadNormalStorageControl()
	{
		$this->loadStorageList($this->Storage);
		$this->loadNormalBackupStorages();
		$this->Storage->createDirective();
	}

	/**
	 * Load storage control with storages for virtual full backup.
	 */
	public function loadVirtualFullStorageControl()
	{
		$this->loadStorageList($this->VirtualFullBackupStorage);
		$this->VirtualFullBackupStorage->createDirective();
		$this->VirtualFullBackupStorage->copyAttributes();
	}

	/**
	 * Load storage list in given control.
	 *
	 * @param object $control control object
	 * @return array loaded storage list
	 */
	public function loadStorageList($control)
	{
		$storage_list = [];
		$storages = $this->getModule('api')->get(
			['config', 'dir', 'storage']
		)->output;
		for ($i = 0; $i < count($storages); $i++) {
			$storage_list[] = $storages[$i]->Storage->Name;
		}
		sort($storage_list, SORT_NATURAL | SORT_FLAG_CASE);
		$control->setData($storage_list);
		//$control->createDirective();
		return $storage_list;
	}

	/**
	 * Load normal backup storage list.
	 */
	public function loadNormalBackupStorages()
	{
		$jobdefs = $this->getJobDefs();
		$job_name = $this->ExistingJob->getValue();
		if ($this->WhatToDoWithVirtualFullExistingJob->Checked && isset($job_name)) {
			$storage = $this->getExistingJobDirective($job_name, 'Storage');
			$this->Storage->setDirectiveValue($storage);
		} elseif (key_exists('Storage', $jobdefs) && is_array($jobdefs['Storage']) && is_null($this->Storage->getDirectiveValue())) {
			$this->Storage->setDirectiveValue($jobdefs['Storage']);
		}
	}

	/**
	 * Get jobs that use pool and send them to warning box.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function getJobsUsingPool($sender, $param)
	{
		$values = $param->getCallbackParameter();
		$job_list = [];
		$jobs = $this->getModule('api')->get([
			'config',
			'dir',
			'job',
			'?apply_jobdefs=1'
		]);
		if ($jobs->error === 0) {
			for ($i = 0; $i < count($jobs->output); $i++) {
				if (property_exists($jobs->output[$i]->Job, 'Pool') && $jobs->output[$i]->Job->Pool == $values->pool) {
					$job_list[] = $jobs->output[$i]->Job->Name;
				}
			}
		}
		if (count($job_list) > 0) {
			sort($job_list);
			$this->getCallbackClient()->callClientFunction('show_storage_warning', [
				$values->storage,
				$values->pool,
				$job_list
			]);
		}
	}

	/**
	 * Set server side validators for storage control.
	 */
	public function setStorageServerSideValidators()
	{
		$this->Storage->DirectiveValidator->attachEventHandler(
			'OnValidate',
			[$this, 'validateStorage']
		);
	}

	/**
	 * Set client side validators for storage control.
	 */
	public function setStorageClientSideValidators()
	{
		$dis_cs_validation_func = TJavaScript::quoteJsLiteral(
			'function(sender, param) { sender.enabled = false; }'
		);
		$this->Storage->DirectiveValidator->getClientSide()->getOptions()->add(
			'OnValidate',
			$dis_cs_validation_func
		);
	}

	/**
	 * Storage control validator method.
	 *
	 * @param TRequiredFieldValidator $sender sender object
	 * @param null $param event parameter
	 */
	public function validateStorage($sender, $param)
	{
		if ($this->WhatToDoWithVirtualFullExistingJob->Checked) {
			$this->Storage->DirectiveValidator->setEnabled(false);
		}
	}

	/**
	 * Load normal backup (inc/diff) pool list.
	 */
	public function loadNormalBackupPools()
	{
		$this->loadPoolList($this->Pool);
		$jobdefs = $this->getJobDefs();
		$job_name = $this->ExistingJob->getValue();
		if ($this->WhatToDoWithVirtualFullExistingJob->Checked && isset($job_name)) {
			$pool = $this->getExistingJobDirective($job_name, 'Pool');
			$this->Pool->setDirectiveValue($pool);
			$this->Pool->createDirective();
		} elseif (key_exists('Pool', $jobdefs) && is_null($this->Pool->getDirectiveValue())) {
			$this->Pool->setDirectiveValue($jobdefs['Pool']);
			$this->Pool->createDirective();
		}
	}

	/**
	 * Load virtual full backup pool list.
	 */
	public function loadVirtualFullBackupNextPools()
	{
		$this->loadPoolList($this->NextPool);
		$jobdefs = $this->getJobDefs();
		$job_name = $this->ExistingJob->getValue();
		if ($this->WhatToDoWithVirtualFullExistingJob->Checked && isset($job_name)) {
			$nextpool = $this->getExistingJobDirective($job_name, 'NextPool');
			$this->NextPool->setDirectiveValue($nextpool);
			$this->NextPool->createDirective();
		} elseif (key_exists('NextPool', $jobdefs) && is_null($this->NextPool->getDirectiveValue())) {
			$this->NextPool->setDirectiveValue($jobdefs['NextPool']);
			$this->NextPool->createDirective();
		}
	}


	/**
	 * Load pool list in given control.
	 *
	 * @param object $control control object
	 * @return array loaded pool list
	 */
	public function loadPoolList($control)
	{
		$pool_list = [];
		$pools = $this->getModule('api')->get(
			['config', 'dir', 'pool']
		)->output;
		for ($i = 0; $i < count($pools); $i++) {
			$pool_list[] = $pools[$i]->Pool->Name;
		}
		sort($pool_list, SORT_NATURAL | SORT_FLAG_CASE);
		$control->setData($pool_list);
		$control->createDirective();
		return $pool_list;
	}

	/**
	 * Load normal and virtual full (NextPool) pools.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadPools($sender, $param)
	{
		$this->loadNormalBackupPools();
		$this->loadVirtualFullBackupNextPools();
	}

	/**
	 * Set storage in control for virtual full backup storages.
	 * Storage is taken from selected pool.
	 *
	 * @param string $pool pool name to get storage
	 * @param null|TCallbackEventParameter $param callback parameter
	 */
	public function setVirtualFullBackupStorage($pool, $param = null)
	{
		$storage = [];
		if (!empty($pool)) {
			$result = $this->getModule('api')->get([
				'config',
				'dir',
				'pool',
				$pool
			]);
			if ($result->error === 0) {
				if (property_exists($result->output, 'Storage') && is_array($result->output->Storage) && count($result->output->Storage) > 0) {
					$storage = $result->output->Storage;
				}
			}
		}
		$this->getCallbackClient()->callClientFunction(
			'set_storage_list_cb',
			[$storage]
		);
	}

	/**
	 * Set control with virtual full backup storage.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setVirtualFullBackupStorageValue($sender, $param)
	{
		$nextpool = $this->NextPool->getValue();
		$this->setVirtualFullBackupStorage($nextpool, $param);
	}

	/**
	 * Set destination storage control basing on pool configuration.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setDestinationStorageByPool($sender, $param)
	{
		$nextpool = $this->NextPool->getDirectiveValue();
		if (empty($nextpool)) {
			return;
		}
		$this->setStorageByPool($nextpool, 'set_storage_from_pool_cb');
	}

	/**
	 * Load values for backup job specific directives (Accurate, Priority...).
	 */
	public function loadBackupJobDirectives()
	{
		$jobdefs = $this->getJobDefs();
		if (key_exists('Accurate', $jobdefs) && is_null($this->Accurate->getDirectiveValue())) {
			$this->Accurate->setDirectiveValue($jobdefs['Accurate']);
			$this->Accurate->createDirective();
		}
		if (key_exists('Priority', $jobdefs) && is_null($this->Priority->getDirectiveValue())) {
			$this->Priority->setDirectiveValue($jobdefs['Priority']);
			$this->Priority->createDirective();
		}
	}

	/**
	 * Load job history range slider control.
	 */
	public function loadJobHistorySlider()
	{
		$job_name = $this->ExistingJob->getValue();
		$this->JobHistorySlider->setJobName($job_name);
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
		$val = $this->Messages->getDirectiveValue();
		$def_msg = 'Standard';
		if (key_exists('Messages', $jobdefs)) {
			$this->Messages->setDirectiveValue($jobdefs['Messages']);
		} elseif ($this->WhatToDoWithVirtualFullNewJob->Checked && empty($val) && array_search($def_msg, $message_list) !== false) {
			$this->Messages->setDirectiveValue($def_msg);
		}
		$this->Messages->createDirective();
	}

	/**
	 * Prepare schedule options.
	 */
	public function setupScheduleOptions()
	{
		$job_name = $this->ExistingJob->getValue();
		if ($this->WhatToDoWithVirtualFullExistingJob->Checked && isset($job_name)) {
			$schedule = $this->getExistingJobDirective($job_name, 'Schedule');
			$this->VirtualFullScheduleExisting->Checked = true;
			$this->VirtualFullScheduleExistingList->setDirectiveValue($schedule);
		}
	}

	/**
	 * Load schedule.
	 */
	public function loadSchedules()
	{
		$this->loadScheduleList(null, null);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Schedule', $jobdefs)) {
			$this->VirtualFullScheduleExistingList->setDirectiveValue($jobdefs['Schedule']);
		}
		$this->VirtualFullScheduleExistingList->createDirective();
	}

	/**
	 * Set server side validators for schedule controls.
	 */
	public function setScheduleServerSideValidators()
	{
		$this->VirtualFullScheduleBasicName->DirectiveValidator->attachEventHandler(
			'OnValidate',
			[$this, 'validateSchedule']
		);
		$this->VirtualFullScheduleExistingList->DirectiveValidator->attachEventHandler(
			'OnValidate',
			[$this, 'validateSchedule']
		);
	}

	/**
	 * Set client side validators for schedule controls.
	 */
	public function setScheduleClientSideValidators()
	{
		$dis_cs_validation_func = TJavaScript::quoteJsLiteral(
			'function(sender, param) { sender.enabled = false; }'
		);
		$this->VirtualFullScheduleBasicName->DirectiveValidator->getClientSide()->getOptions()->add(
			'OnValidate',
			$dis_cs_validation_func
		);
		$this->VirtualFullScheduleExistingList->DirectiveValidator->getClientSide()->getOptions()->add(
			'OnValidate',
			$dis_cs_validation_func
		);
	}

	/**
	 * Schedule controls validator method.
	 *
	 * @param TControl $sender validator object
	 * @param object $param validator callback parameters
	 */
	public function validateSchedule($sender, $param)
	{
		if ($this->VirtualFullScheduleBasic->Checked) {
			$this->VirtualFullScheduleExistingList->DirectiveValidator->setEnabled(false);
		}
		if ($this->VirtualFullScheduleExisting->Checked) {
			$this->VirtualFullScheduleBasicName->DirectiveValidator->setEnabled(false);
		}
		if ($this->VirtualFullScheduleNoSchedule->Checked) {
			$this->VirtualFullScheduleExistingList->DirectiveValidator->setEnabled(false);
			$this->VirtualFullScheduleBasicName->DirectiveValidator->setEnabled(false);
		}
	}

	/**
	 * Load control to create schedule.
	 */
	public function loadScheduleNewConfig()
	{
		if (!empty($_SESSION['dir'])) {
			$this->ScheduleConfig->setComponentName($_SESSION['dir']);
			$this->ScheduleConfig->setLoadValues(false);
			$this->ScheduleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Load schedule list in given control.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadScheduleList($sender, $param)
	{
		$schedule_list = [];
		$schedules = $this->getModule('api')->get(['config', 'dir', 'schedule'])->output;
		for ($i = 0; $i < count($schedules); $i++) {
			$schedule_list[] = $schedules[$i]->Schedule->Name;
		}
		sort($schedule_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->VirtualFullScheduleExistingList->setData($schedule_list);
		$this->VirtualFullScheduleExistingList->createDirective();
	}

	/**
	 * Save wizard directives.
	 *
	 * @param TWizard $sender sender object
	 * @param object $param wizard parameter
	 */
	public function wizardCompleted($sender, $param)
	{
		if ($this->VirtualFullScheduleBasic->Checked) {
			$schedule = [
				'Name' => $this->VirtualFullScheduleBasicName->getDirectiveValue(),
				'Run' => [
					sprintf(
						'Level="%s" %s-%s %s',
						$this->VirtualFullScheduleBasicLevel->getValue(),
						Params::getDaysOfWeekConfig(
							[(int) $this->VirtualFullScheduleBasicScheduleFrom->getSelectedValue()]
						),
						Params::getDaysOfWeekConfig(
							[(int) $this->VirtualFullScheduleBasicScheduleTo->getSelectedValue()]
						),
						Params::getTimeConfig(
							[(int) $this->VirtualFullScheduleBasicScheduleAtHour->getSelectedValue()],
							(int) $this->VirtualFullScheduleBasicScheduleAtHour->getSelectedValue()
						)
					),
					sprintf(
						'Level="VirtualFull" %s %s',
						Params::getDaysOfWeekConfig(
							[(int) $this->VirtualFullScheduleBasicScheduleRunVFOn->getSelectedValue()]
						),
						Params::getTimeConfig(
							[(int) $this->VirtualFullScheduleBasicScheduleVFAtHour->getSelectedValue()],
							(int) $this->VirtualFullScheduleBasicScheduleVFAtMinute->getSelectedValue()
						)
					)
				]
			];
			$params = [
				'config',
				'dir',
				'Schedule',
				$schedule['Name']
			];
			$result = $this->getModule('api')->create(
				$params,
				['config' => json_encode($schedule)]
			);
			if ($result->error === 0) {
				$this->getModule('api')->set(['console'], ['reload']);
			} else {
				$this->CreateResourceErrMsg->Display = 'Dynamic';
				$this->CreateResourceErrMsg->Text = $result->output;
				return;
			}
		};

		$job = [];
		$directives = [];
		$jd = null;
		if ($this->WhatToDoWithVirtualFullNewJob->Checked) {
			// Create new job
			$job = [
				'Name' => $this->Name->getDirectiveValue(),
				'Type' => 'Backup',
			];
			$jd = $this->JobDefs->getDirectiveValue();
			if (is_string($jd)) {
				$job['JobDefs'] = $jd;
			}
			$directives = ['Description', 'Client', 'Fileset', 'Storage', 'Pool',
				'NextPool', 'Accurate', 'Priority', 'Messages'
			];
			if ($this->VirtualFullTypeProgressive->Checked) {
				$directives[] = 'BackupsToKeep';
				$directives[] = 'DeleteConsolidatedJobs';
			}
		} elseif ($this->WhatToDoWithVirtualFullExistingJob->Checked) {
			// Use existing job and modify it
			$job_name = $this->ExistingJob->getDirectiveValue();
			$result = $this->getModule('api')->get([
				'config', 'dir', 'Job', $job_name
			]);
			if ($result->error != 0) {
				$this->CreateResourceErrMsg->Display = 'Dynamic';
				$this->CreateResourceErrMsg->Text = $result->output;
				return;
			}
			$job = (array) $result->output;
			$directives = ['NextPool'];
			if ($this->VirtualFullTypeProgressive->Checked) {
				$directives[] = 'BackupsToKeep';
				$directives[] = 'DeleteConsolidatedJobs';
			}
		}
		// Add selected directives
		for ($i = 0; $i < count($directives); $i++) {
			$val = null;
			if (get_class($this->{$directives[$i]}) == 'Bacularis\Web\Portlets\DirectiveCheckBox') {
				$val = $this->{$directives[$i]}->getValue();
			} else {
				$val = $this->{$directives[$i]}->getDirectiveValue();
			}
			if (is_null($val)) {
				continue;
			}
			if (is_null($jd) || !$this->isInJobDefs($directives[$i], $val)) {
				$job[$directives[$i]] = $val;
			}
		}

		// Save storage in NextPool
		$nextpool = $this->NextPool->getDirectiveValue();
		if (!empty($nextpool)) {
			$result = $this->getModule('api')->get([
				'config', 'dir', 'Pool', $nextpool
			]);
			if ($result->error != 0) {
				$this->CreateResourceErrMsg->Display = 'Dynamic';
				$this->CreateResourceErrMsg->Text = $result->output;
				return;
			}
			$pool = (array) $result->output;
			$pool['Storage'] = $this->VirtualFullBackupStorage->getDirectiveValue();
			$params = [
				'config',
				'dir',
				'Pool',
				$pool['Name']
			];
			$result = $this->getModule('api')->set(
				$params,
				['config' => json_encode($pool)]
			);
			if ($result->error != 0) {
				$this->CreateResourceErrMsg->Display = 'Dynamic';
				$this->CreateResourceErrMsg->Text = $result->output;
				return;
			}
		}

		if ($this->VirtualFullScheduleBasic->Checked) {
			$job['Schedule'] = $this->VirtualFullScheduleBasicName->getDirectiveValue();
		} elseif ($this->VirtualFullScheduleExisting->Checked) {
			$job['Schedule'] = $this->VirtualFullScheduleExistingList->getDirectiveValue();
		} elseif ($this->VirtualFullScheduleNoSchedule->Checked && $this->WhatToDoWithVirtualFullExistingJob->Checked && key_exists('Schedule', $job)) {
			unset($job['Schedule']);
		}
		$params = [
			'config',
			'dir',
			'Job',
			$job['Name']
		];

		$result = (object) ['error' => -1, 'output' => ''];
		if ($this->WhatToDoWithVirtualFullNewJob->Checked) {
			$result = $this->getModule('api')->create(
				$params,
				['config' => json_encode($job)]
			);
		} elseif ($this->WhatToDoWithVirtualFullExistingJob->Checked) {

			$result = $this->getModule('api')->set(
				$params,
				['config' => json_encode($job)]
			);
		}
		if ($result->error === 0) {
			$this->getModule('api')->set(['console'], ['reload']);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				"Create new virtual full backup job. Name: {$job['Name']}"
			);
			$this->goToPage('JobList');
		} else {
			$this->CreateResourceErrMsg->Display = 'Dynamic';
			$this->CreateResourceErrMsg->Text = $result->output;
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				"Error while creating new virtual full backup job. Name: {$job['Name']}, Error: {$result->error}, Output: {$result->output}"
			);
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
		$this->goToDefaultPage();
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
