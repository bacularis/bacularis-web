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
use Prado\Prado;
use Prado\Web\UI\WebControls\TWizard;

/**
 * New migrate job wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewMigrateJobWizard extends BaculumWebPage
{
	public const PREV_STEP = 'PrevStep';
	public const JOBDEFS = 'JobDefs';

	/**
	 * Stores available selection types.
	 *
	 * @var array
	 */
	public $sel_types = [];

	public function onInit($param)
	{
		parent::onInit($param);
		$this->sel_types = [
			'Job' => Prado::localize('Migrate by job'),
			'Client' => Prado::localize('Migrate by client'),
			'Volume' => Prado::localize('Migrate by volume'),
			'SmallestVolume' => Prado::localize('Migrate by smallest volume'),
			'OldestVolume' => Prado::localize('Migrate by oldest volume'),
			'PoolOccupancy' => Prado::localize('Migrate using Pool occupancy'),
			'PoolTime' => Prado::localize('Migrate by Pool time'),
			'SQLQuery' => Prado::localize('Migrate by SQL query')
		];
	}

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
				$this->loadPools();
				$this->loadSourceStorages();
				break;
			}
			case 2: {
				$this->loadSelectionTypes();
				break;
			}
			case 3: {
				$this->loadNextPools();
				$this->loadDestinationStorages();
				break;
			}
			case 4: {
				$this->loadSchedules();
				$this->loadMessages();
				$this->loadLevels();
				$this->loadClients();
				$this->loadFileSets();
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
		$jobdefs = $this->getModule('api')->get(
			[
			'config',
			'dir',
			'jobdefs']
		);
		if ($jobdefs->error === 0) {
			for ($i = 0; $i < count($jobdefs->output); $i++) {
				$jobdefs_list[] = $jobdefs->output[$i]->JobDefs->Name;
			}
			sort($jobdefs_list, SORT_NATURAL | SORT_FLAG_CASE);
			$this->JobDefs->setData($jobdefs_list);
			$this->JobDefs->createDirective();
		}
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
		$jobdefs = $directive_value;
		$result = $this->getModule('api')->get([
			'config',
			'dir',
			'jobdefs',
			$jobdefs
		]);
		if ($result->error === 0) {
			$value = (array) $result->output;
			$this->setJobDefs($value);
		}
	}

	/**
	 * Check if directive with given value exists in used JobDefs.
	 *
	 * @param string $directive_name directive name
	 * @param string $directive_value directive value
	 * @return bool true if directive exists in JobDefs, otherwise false
	 */
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
	 * Set pool type controls (Pool, NextPool ...etc.)
	 *
	 * @param string $name pool type directive name
	 * @param object $control different type of controls (usually DirectiveComboBox)
	 */
	public function setPools($name, $control)
	{
		$pool_list = [];
		$pools = $this->getModule('api')->get(['config', 'dir', 'pool'])->output;
		for ($i = 0; $i < count($pools); $i++) {
			$pool_list[] = $pools[$i]->Pool->Name;
		}
		sort($pool_list, SORT_NATURAL | SORT_FLAG_CASE);
		$control->setData($pool_list);
		$jobdefs = $this->getJobDefs();
		if (key_exists($name, $jobdefs) && is_null($control->getDirectiveValue())) {
			$control->setDirectiveValue($jobdefs[$name]);
		}
		$control->createDirective();
	}

	/**
	 * Load pool list (step 2).
	 *
	 */
	public function loadPools()
	{
		$this->setPools('Pool', $this->Pool);
	}

	/**
	 * Load volumes to display while source pool is configured.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadVolumes($sender, $param)
	{
		$pool = $param->getCallbackParameter();
		$volumes = $this->getVolumes($pool);
		$this->getCallbackClient()->callClientFunction(
			'oVolumeList.update',
			[$volumes]
		);
	}

	/**
	 * Get volume list for given pool.
	 *
	 * @param string $pool pool name
	 * @return array volume list or empty array on error
	 */
	public function getVolumes($pool)
	{
		$poolid = null;
		$result = $this->getModule('api')->get(['pools']);
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if ($pool === $result->output[$i]->name) {
					$poolid = $result->output[$i]->poolid;
					break;
				}
			}
		}
		$ret = [];
		if ($poolid) {
			$result = $this->getModule('api')->get(
				['pools', $poolid, 'volumes']
			);
			if ($result->error === 0) {
				$ret = $result->output;
			}
		}
		return $ret;
	}

	/**
	 * Load source storage list (step 2).
	 *
	 */
	public function loadSourceStorages()
	{
		$this->setStorages($this->SourceStorage);
	}

	/**
	 * Set source storage control basing on pool configuration.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setSourceStorageByPool($sender, $param)
	{
		$pool = $this->Pool->getDirectiveValue();
		if (empty($pool)) {
			return;
		}
		$this->setStorageByPool($pool, 'set_storage_from_pool_cb');
	}

	/**
	 * Load selection types (step 3).
	 *
	 */
	public function loadSelectionTypes()
	{
		$this->SelectionType->setData($this->sel_types);
		$this->SelectionType->createDirective();
	}

	/**
	 * Load jobs to select one (step 3).
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadJobList($sender, $param)
	{
		$result = $this->getModule('api')->get([
			'jobs',
			'show',
			'?output=json'
		]);
		$jobs = [];
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if ($result->output[$i]->jobtype != '66') {
					continue;
				}
				$jobs[] = [
					'job' => $result->output[$i]->name,
					'enabled' => $result->output[$i]->enabled,
					'priority' => $result->output[$i]->priority,
					'type' => chr($result->output[$i]->jobtype),
					'maxjobs' => $result->output[$i]->maxjobs
				];
			}
		}
		$this->getCallbackClient()->callClientFunction(
			'oJobList.init',
			[$jobs]
		);
	}

	/**
	 * Load clients to select one (step 3).
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadClientList($sender, $param)
	{
		$result = $this->getModule('api')->get(
			['clients']
		);
		$clients = [];
		if ($result->error === 0) {
			$clients = $result->output;
		}
		$this->getCallbackClient()->callClientFunction(
			'oClientList.init',
			[$clients]
		);
	}

	/**
	 * Load pool list (step 4).
	 *
	 */
	public function loadNextPools()
	{
		$this->setPools('NextPool', $this->NextPool);
	}

	public function setStorages($control)
	{
		$storage_list = [];
		$storages = $this->getModule('api')->get([
			'config',
			'dir',
			'storage'
		]);
		if ($storages->error === 0) {
			for ($i = 0; $i < count($storages->output); $i++) {
				$storage_list[] = $storages->output[$i]->Storage->Name;
			}
			sort($storage_list, SORT_NATURAL | SORT_FLAG_CASE);
			$control->setData($storage_list);
			$jobdefs = $this->getJobDefs();
			if (key_exists('Storage', $jobdefs) && is_array($jobdefs['Storage']) && count($jobdefs['Storage']) == 1 && is_null($control->getDirectiveValue())) {
				$control->setDirectiveValue($jobdefs['Storage'][0]);
			}
			$control->createDirective();
		}
	}

	/**
	 * Load destination storage list (step 4).
	 *
	 */
	public function loadDestinationStorages()
	{
		$this->setStorages($this->DestinationStorage);
	}

	/**
	 * Set storage control basing on usage in pool.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventParameter $param callback parameter
	 * @param mixed $pool
	 * @param mixed $cb
	 */
	public function setStorageByPool($pool, $cb)
	{
		$pool = $this->getModule('api')->get([
			'config',
			'dir',
			'pool',
			$pool
		]);
		if ($pool->error === 0) {
			$storage = null;
			if (property_exists($pool->output, 'Storage') && is_array($pool->output->Storage) && count($pool->output->Storage) == 1) {
				$storage = $pool->output->Storage[0];
			}
			$this->getCallbackClient()->callClientFunction($cb, [$storage]);
		}
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
			sort($job_list, SORT_NATURAL | SORT_FLAG_CASE);
			$this->getCallbackClient()->callClientFunction('show_storage_warning', [
				$values->storage,
				$values->pool,
				$job_list
			]);
		}
	}

	/**
	 * Load messages (step 5).
	 *
	 */
	public function loadMessages()
	{
		$message_list = [];
		$messages = $this->getModule('api')->get([
			'config',
			'dir',
			'messages'
		]);
		if ($messages->error === 0) {
			for ($i = 0; $i < count($messages->output); $i++) {
				$message_list[] = $messages->output[$i]->Messages->Name;
			}
			sort($message_list, SORT_NATURAL | SORT_FLAG_CASE);
			$this->Messages->setData($message_list);
			$jobdefs = $this->getJobDefs();
			if (key_exists('Messages', $jobdefs)) {
				$this->Messages->setDirectiveValue($jobdefs['Messages']);
			}
			$this->Messages->createDirective();
		}
	}

	/**
	 * Load schedule (step 5).
	 *
	 */
	public function loadSchedules()
	{
		$schedule_list = [];
		$schedules = $this->getModule('api')->get([
			'config',
			'dir',
			'schedule'
		]);
		if ($schedules->error === 0) {
			for ($i = 0; $i < count($schedules->output); $i++) {
				$schedule_list[] = $schedules->output[$i]->Schedule->Name;
			}
			sort($schedule_list, SORT_NATURAL | SORT_FLAG_CASE);
			$this->Schedule->setData($schedule_list);
			$jobdefs = $this->getJobDefs();
			if (key_exists('Schedule', $jobdefs)) {
				$this->Schedule->setDirectiveValue($jobdefs['Schedule']);
			}
			$this->Schedule->createDirective();
		}
	}

	/**
	 * Load job levels (step 5).
	 *
	 */
	public function loadLevels()
	{
		// so far backup job levels only
		$levels = $this->getModule('misc')->getJobLevels();
		$level_list = array_values($levels);
		$this->Level->setData($level_list);
		$jobdefs = $this->getJobDefs();
		if (key_exists('Level', $jobdefs)) {
			$this->Level->setDirectiveValue($jobdefs['Level']);
		} elseif (count($level_list) > 0) {
			// no level in jobdefs, take first level
			$this->Level->setDirectiveValue($level_list[0]);
		}
		$this->Level->createDirective();
	}

	/**
	 * Load clients (step 5).
	 *
	 */
	public function loadClients()
	{
		$clients = $this->getModule('api')->get([
			'config',
			'dir',
			'client'
		]);
		if ($clients->error === 0) {
			for ($i = 0; $i < count($clients->output); $i++) {
				$client_list[] = $clients->output[$i]->Client->Name;
			}
			sort($client_list, SORT_NATURAL | SORT_FLAG_CASE);
			$this->Client->setData($client_list);
			$jobdefs = $this->getJobDefs();
			if (key_exists('Client', $jobdefs) && is_array($jobdefs['Client']) && is_null($this->Client->getDirectiveValue())) {
				$this->Client->setDirectiveValue($jobdefs['Client']);
			} elseif (count($client_list) > 0) {
				$this->Client->setDirectiveValue($client_list[0]);
			}
			$this->Client->createDirective();
		}
	}

	/**
	 * Load filesets (step 5).
	 *
	 */
	public function loadFileSets()
	{
		$filesets = $this->getModule('api')->get([
			'config',
			'dir',
			'fileset'
		]);
		if ($filesets->error === 0) {
			for ($i = 0; $i < count($filesets->output); $i++) {
				$fileset_list[] = $filesets->output[$i]->Fileset->Name;
			}
			sort($fileset_list, SORT_NATURAL | SORT_FLAG_CASE);
			$this->FileSet->setData($fileset_list);
			$jobdefs = $this->getJobDefs();
			if (key_exists('Fileset', $jobdefs) && is_array($jobdefs['Fileset']) && is_null($this->Fileset->getDirectiveValue())) {
				$this->FileSet->setDirectiveValue($jobdefs['Fileset']);
			} elseif (count($fileset_list) > 0) {
				$this->FileSet->setDirectiveValue($fileset_list[0]);
			}
			$this->FileSet->createDirective();
		}
	}

	/**
	 * Get selection pattern control.
	 *
	 * @return DirectiveTextBox selection pattern control.
	 */
	public function getSelectionPatternControl()
	{
		$control = null;
		$sel_type = $this->SelectionType->getDirectiveValue();
		switch ($sel_type) {
			case 'Job': $control = $this->SelectionPatternJob;
				break;
			case 'Client': $control = $this->SelectionPatternClient;
				break;
			case 'Volume': $control = $this->SelectionPatternVolume;
				break;
			case 'SQLQuery': $control = $this->SelectionPatternSQLQuery;
				break;
		}
		return $control;
	}

	/**
	 * Get selection pattern value.
	 *
	 * @return string selection pattern value
	 */
	public function getSelectionPatternValue()
	{
		$sel_pattern = '';
		$sp_control = $this->getSelectionPatternControl();
		if (is_object($sp_control)) {
			$sel_pattern = $sp_control->getDirectiveValue();
		}
		return $sel_pattern;
	}

	public function wizardCompleted($sender, $param)
	{
		$jobdefs = $this->getJobDefs();
		$job = [
			'Name' => $this->Name->getDirectiveValue(),
			'Type' => 'Migrate',
		];
		$jd = $this->JobDefs->getDirectiveValue();
		$directives = ['Description', 'Pool', 'SourceStorage', 'Level',
			'SelectionType', 'MaximumSpawnedJobs', 'Schedule',
			'Messages', 'Client', 'FileSet', 'NextPool', 'PurgeMigrationJob'
		];
		if (is_string($jd)) {
			$job['JobDefs'] = $jd;
		}
		for ($i = 0; $i < count($directives); $i++) {
			$val = $this->{$directives[$i]}->getDirectiveValue();
			if (is_null($val)) {
				continue;
			}
			$directive = $directives[$i];
			if ($directive == 'SourceStorage') {
				$directive = 'Storage';
			}
			if (is_null($jd) || !$this->isInJobDefs($directive, $val)) {
				$job[$directive] = $val;
			}
		}

		// selection type
		$sel_type = $this->SelectionType->getDirectiveValue();
		$job['SelectionType'] = $sel_type;

		// selection pattern
		$sel_pattern = $this->getSelectionPatternValue();
		if (!empty($sel_pattern)) {
			$job['SelectionPattern'] = $sel_pattern;
		}

		// Add to source pool directives specific for  pool occupancy and pool time selection type
		if ($sel_type == 'PoolOccupancy' || $sel_type == 'PoolTime') {
			$pool = $this->Pool->getDirectiveValue();
			$params = [
				'config',
				'dir',
				'Pool',
				$pool
			];
			$result = $this->getModule('api')->get(
				$params
			);

			if ($result->error === 0) {
				$pool = (array) $result->output;
				if ($sel_type == 'PoolOccupancy') {
					$pool['MigrationLowBytes'] = $this->MigrationLowBytes->getDirectiveValue();
					$pool['MigrationHighBytes'] = $this->MigrationHighBytes->getDirectiveValue();
				} elseif ($sel_type == 'PoolTime') {
					$pool['MigrationTime'] = $this->MigrationTime->getDirectiveValue();
				}

				$result = $this->getModule('api')->set(
					$params,
					['config' => json_encode($pool)]
				);

				if ($result->error !== 0) {
					$this->CreateResourceErrMsg->Display = 'Dynamic';
					$this->CreateResourceErrMsg->Text = $result->output;
					return; // END
				}
			}
		}

		// Add storage to pool
		$nextpool = $this->NextPool->getDirectiveValue();
		$params = [
			'config',
			'dir',
			'Pool',
			$nextpool
		];
		$result = $this->getModule('api')->get(
			$params
		);

		$pool_modified = false;
		if ($result->error === 0) {
			$pool = (array) $result->output;
			$pool['Storage'] = $this->DestinationStorage->getDirectiveValue();
			$result = $this->getModule('api')->set(
				$params,
				['config' => json_encode($pool)]
			);
			if ($result->error === 0) {
				$pool_modified = true;
			} else {
				$this->CreateResourceErrMsg->Display = 'Dynamic';
				$this->CreateResourceErrMsg->Text = $result->output;
			}
		}

		// create migrate job
		if ($pool_modified) {
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
				$this->CreateResourceErrMsg->Display = 'Dynamic';
				$this->CreateResourceErrMsg->Text = $result->output;
			}
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
