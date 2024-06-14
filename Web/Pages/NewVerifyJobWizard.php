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

use Bacularis\Web\Modules\BaculumWebPage;

/**
 * New verify job wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewVerifyJobWizard extends BaculumWebPage
{
	public const PREV_STEP = 'PrevStep';
	public const JOBDEFS = 'JobDefs';
	public const CLIENT = 'Client';
	public const STORAGE = 'Storage';

	public function onLoad($param)
	{
		parent::onLoad($param);
		$step_index = $this->NewJobWizard->getActiveStepIndex();
		if ($step_index === 0) {
			// It is special case for support changing jobdefs from one to another
			$this->JobDefs->setValue();
		}
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
				break;
			}
			case 2: {
				$this->loadClients();
				$this->loadJobs();
				break;
			}
			case 3: {
				$this->setJobDirectives();
				$this->loadClientsVerify();
				$this->loadMessages();
				$this->loadSchedules();
				$this->loadStorages();
				$this->loadPools();
				break;
			}
			case 4: {
				break;
			}
		}
	}

	/**
	 * Load JobDefs (step 1).
	 *
	 */
	public function loadJobDefs()
	{
		$jobdefs_list = [];
		$jobdefs = $this->getModule('api')->get([
			'config',
			'dir',
			'jobdefs'
		]);
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
		$jd = $this->getJobDefs();
		if (empty($directive_value)) {
			$this->setJobDefs([]);
			if (count($jd) > 0) {
				// jobdefs has been unselected, reset all directives.
				$this->resetDirectives();
			}
			return;
		}

		if (count($jd) > 0 && $jd['Name'] !== $directive_value) {
			// jobdefs changed, reset directives
			$this->resetDirectives();
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
	 * Reset verify job directives.
	 * Useful when JobDefs selection is changed from one to another.
	 *
	 */
	private function resetDirectives()
	{
		$this->Client->setDirectiveValue(null);
		$this->Client->createDirective();
		$this->ClientVerify->setDirectiveValue(null);
		$this->ClientVerify->createDirective();
		$this->Messages->setDirectiveValue(null);
		$this->Messages->createDirective();
		$this->Storage->setDirectiveValue(null);
		$this->Storage->createDirective();
		$this->Pool->setDirectiveValue(null);
		$this->Pool->createDirective();
		$this->Schedule->setDirectiveValue(null);
		$this->Schedule->createDirective();
	}

	/**
	 * Load clients (step 3)
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
			$jobdefs = $this->getJobDefs();
			$client = $this->getClient();
			$is_in_jobdefs = key_exists('Client', $jobdefs);
			$this->Client->setData($client_list);
			if (is_null($this->Client->getDirectiveValue())) {
				if ($is_in_jobdefs) {
					$this->Client->setDirectiveValue($jobdefs['Client']);
				} elseif (count($client_list) > 0) {
					if (!empty($client)) {
						$this->Client->setDirectiveValue($client);
					} else {
						$this->Client->setDirectiveValue($client_list[0]);
					}
				}
			}
			$this->Client->createDirective();
		}
	}

	/**
	 * Load clients verify (step 4)
	 *
	 */
	public function loadClientsVerify()
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
			$jobdefs = $this->getJobDefs();
			$client = $this->getClient();
			$is_in_jobdefs = key_exists('Client', $jobdefs);
			$this->ClientVerify->setData($client_list);
			if (is_null($this->ClientVerify->getDirectiveValue())) {
				if ($is_in_jobdefs) {
					$this->ClientVerify->setDirectiveValue($jobdefs['Client']);
				} elseif (count($client_list) > 0) {
					if (!empty($client)) {
						$this->ClientVerify->setDirectiveValue($client);
					} else {
						$this->ClientVerify->setDirectiveValue($client_list[0]);
					}
				}
			}
			$this->ClientVerify->createDirective();
		}
	}

	/**
	 * Load storages (step 4).
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
		if (is_null($this->Storage->getDirectiveValue())) {
			if (key_exists('Storage', $jobdefs) && is_array($jobdefs['Storage']) && count($jobdefs['Storage']) == 1) {
				$this->Storage->setDirectiveValue($jobdefs['Storage'][0]);
			} elseif (count($storage_list) > 0) {
				$storage = $this->getStorage();
				if (!empty($storage)) {
					$this->Storage->setDirectiveValue($storage);
				} else {
					$this->Storage->setDirectiveValue($storage_list[0]);
				}
			}
		}
		$this->Storage->createDirective();
	}

	/**
	 * Load jobs (step 3).
	 *
	 */
	public function loadJobs()
	{
		$jobs = $this->getModule('api')->get([
			'config',
			'dir',
			'Job',
			'?apply_jobdefs=1'
		]);
		if ($jobs->error === 0) {
			for ($i = 0; $i < count($jobs->output); $i++) {
				if (property_exists($jobs->output[$i]->Job, 'Type') && $jobs->output[$i]->Job->Type == 'Backup') {
					$job_list[] = $jobs->output[$i]->Job->Name;
				}
			}
			sort($job_list, SORT_NATURAL | SORT_FLAG_CASE);
			$this->VerifyJob->setData($job_list);
			$jobdefs = $this->getJobDefs();
			if (is_null($this->VerifyJob->getDirectiveValue())) {
				if (key_exists('VerifyJob', $jobdefs)) {
					$this->VerifyJob->setDirectiveValue($jobdefs['VerifyJob']);
				} elseif (count($job_list) > 0) {
					$this->VerifyJob->setDirectiveValue($job_list[0]);
				}
			}
			$this->VerifyJob->createDirective();
		}
	}

	/**
	 * Set verify job directives basing on selected job to verify directives.
	 *
	 */
	public function setJobDirectives()
	{
		$job = $this->VerifyJob->getDirectiveValue();
		$result = $this->getModule('api')->get([
			'config',
			'dir',
			'Job',
			$job
		]);

		/**
		 * Try to determine Storage first from selected job, then from jobdefs,
		 * then from pool and at the end if all those methods fail check pool
		 * defined in jobdefs.
		 * NOTE: Storage defined in Schedule is not supported so far.
		 */
		$storage = null;
		if ($result->error === 0) {
			if (property_exists($result->output, 'Storage') && is_array($result->output->Storage) && count($result->output->Storage) > 0) {
				// use storage from selected job to verify
				$storage = $result->output->Storage[0];
			}
		}
		$jobdefs = null;
		if (property_exists($result->output, 'JobDefs')) {
			// Get jobdefs data for determining storage here and for client as well
			$jobdefs = $this->getModule('api')->get([
				'config',
				'dir',
				'JobDefs',
				$result->output->JobDefs
			]);
			if (is_null($storage) && $jobdefs->error === 0) {
				if (property_exists($jobdefs->output, 'Storage') && is_array($jobdefs->output->Storage) && count($jobdefs->output->Storage) > 0) {
					// use storage from jobdefs
					$storage = $jobdefs->output->Storage[0];
				}
			}
		}

		if (is_null($storage) && property_exists($result->output, 'Pool')) {
			$pool = $this->getModule('api')->get([
				'config',
				'dir',
				'Pool',
				$result->output->Pool
			]);
			if ($pool->error === 0) {
				if (property_exists($pool->output, 'Storage') && is_array($pool->output->Storage) && count($pool->output->Storage) > 0) {
					// use storage from pool defined in job
					$storage = $pool->output->Storage[0];
				}
			}
		}
		if (is_null($storage) && !property_exists($result->output, 'Pool') && property_exists($jobdefs->output, 'Pool')) {
			$pool2 = $this->getModule('api')->get([
				'config',
				'dir',
				'Pool',
				$jobdefs->output->Pool
				]);
			if ($pool2->error === 0) {
				if (property_exists($pool2->output, 'Storage') && is_array($pool2->output->Storage) && count($pool2->output->Storage) > 0) {
					// use storage from pool defined in jobdefs
					$storage = $pool2->output->Storage[0];
				}
			}
		}
		$this->setStorage($storage);

		/**
		 * Try to determine client from job to verify, first by checking job resource,
		 * then by checking jobdefs
		 */
		$client = null;
		if ($result->error === 0) {
			if (property_exists($result->output, 'Client')) {
				// use client from selected job to verify
				$client = $result->output->Client;
			}
			if (is_null($client) && $jobdefs->error === 0 && property_exists($result->output, 'JobDefs')) {
				if (property_exists($jobdefs->output, 'Client')) {
					// use client from jobdefs
					$client = $jobdefs->output->Client;
				}
			}
		}
		if (is_null($client)) {
			$this->setClient($this->Client->getDirectiveValue());
		} else {
			$this->setClient($client);
		}
	}

	/**
	 * Load messages (step 4).
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
			if (is_null($this->Messages->getDirectiveValue())) {
				if (key_exists('Messages', $jobdefs)) {
					$this->Messages->setDirectiveValue($jobdefs['Messages']);
				} elseif (count($message_list) > 0) {
					if (in_array('Standard', $message_list)) {
						$this->Messages->setDirectiveValue('Standard');
					} else {
						$this->Messages->setDirectiveValue($message_list[0]);
					}
				}
			}
			$this->Messages->createDirective();
		}
	}

	/**
	 * Load schedule (step 4).
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
			if (is_null($this->Schedule->getDirectiveValue())) {
				if (key_exists('Schedule', $jobdefs)) {
					$this->Schedule->setDirectiveValue($jobdefs['Schedule']);
				}
			}
			$this->Schedule->createDirective();
		}
	}

	/**
	 * Load pools (step 4).
	 *
	 */
	public function loadPools()
	{
		$pool_list = [];
		$pools = $this->getModule('api')->get(['config', 'dir', 'pool'])->output;
		for ($i = 0; $i < count($pools); $i++) {
			$pool_list[] = $pools[$i]->Pool->Name;
		}
		sort($pool_list, SORT_NATURAL | SORT_FLAG_CASE);
		$this->Pool->setData($pool_list);
		$jobdefs = $this->getJobDefs();
		if (is_null($this->Pool->getDirectiveValue())) {
			if (key_exists('Pool', $jobdefs)) {
				$this->Pool->setDirectiveValue($jobdefs['Pool']);
			} elseif (count($pool_list) > 0) {
				$this->Pool->setDirectiveValue($pool_list[0]);
			}
		}
		$this->Pool->createDirective();
	}

	public function wizardCompleted($sender, $param)
	{
		$job = [
			'Name' => $this->Name->getDirectiveValue(),
			'Type' => 'Verify'
		];

		if ($this->WhatJobData->Checked) {
			if ($this->HowVolumeToCatalog->Checked) {
				$job['Level'] = 'VolumeToCatalog';
			} elseif ($this->HowData->Checked) {
				$job['Level'] = 'Data';
			} elseif ($this->HowDiskToCatalog->Checked) {
				$job['Level'] = 'DiskToCatalog';
			}
		} elseif ($this->WhatFilesystem->Checked) {
			$job['Level'] = 'Catalog';
		}

		$jd = $this->JobDefs->getDirectiveValue();
		$directives = ['Description', 'Schedule', 'Messages', 'Client',
			'Pool', 'Storage', 'VerifyJob'
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
			if (is_null($jd) || !$this->isInJobDefs($directive, $val)) {
				$job[$directive] = $val;
			}
		}

		if (!$this->WhatFilesystem->Checked) {
			$val = $this->ClientVerify->getDirectiveValue();
			if (is_null($jd) || !$this->isInJobDefs('Client', $val)) {
				$job['Client'] = $val;
			}
		}

		$fileset = null;
		if ($this->WhatJobToVerifyJobName->Checked && ($this->HowDiskToCatalog->Checked || $this->HowData->Checked)) {
			$params = [
				'config',
				'dir',
				'Job',
				$this->VerifyJob->getDirectiveValue()
			];
			$result = $this->getModule('api')->get(
				$params
			);

			if ($result->error === 0) {
				$verifyjob = (array) $result->output;
				if (key_exists('Fileset', $verifyjob)) {
					$fileset = $verifyjob['Fileset'];
					$params = [
						'config',
						'dir',
						'Fileset',
						$fileset
					];
					$result = $this->getModule('api')->get(
						$params
					);

					if ($result->error === 0) {
						/**
						 * Add file attributes to options block from fileset assigned to selected job.
						 * Note, if fileset is defined if JobDefs (a rare case), file attributes are not added.
						 */
						$fileset_cfg = (array) $result->output;
						if (key_exists('Include', $fileset_cfg)) {
							$fattrs = $this->getFileAttributes();
							if (!empty($fattrs) && $this->HowData->Checked) {
								// enable accurate mode for Data level to check file attributes from Director
								$job['Accurate'] = true;
							}
							for ($i = 0; $i < count($fileset_cfg['Include']); $i++) {
								if (!property_exists($fileset_cfg['Include'][$i], 'Options')) {
									// No options, so add one option block
									$fileset_cfg['Include'][$i]->Options = [
										'Verify' => $fattrs
									];
								} else {
									if (is_array($fileset_cfg['Include'][$i]->Options) && count($fileset_cfg['Include'][$i]->Options) > 0) {
										// Add attributes to each first options block per Include value
										$fileset_cfg['Include'][$i]->Options[0]->Verify = $fattrs;
									}
								}
							}
						}
						$params = [
							'config',
							'dir',
							'Fileset',
							$fileset
						];
						$result = $this->getModule('api')->set(
							$params,
							['config' => json_encode($fileset_cfg)]
						);
					}
				}
			}
		}

		if ($this->WhatFilesystem->Checked) {
			// Create fileset for Catalog verify job level.
			$includes = trim($this->NewVerifyJobIncludePaths->Text);
			$excludes = trim($this->NewVerifyJobExcludePaths->Text);
			if (!empty($includes)) {
				$inc = explode("\r\n", $includes);
				$exc = null;
				if (!empty($excludes)) {
					$exc = explode("\r\n", $excludes);
				}
				$fileset = $this->Name->getDirectiveValue() . ' Verify FileSet';
				$fileset_cfg = [
					'Name' => $fileset,
					'Include' => [
						[
							'Options' => [
								['Verify' => $this->getFileAttributes()]
							],
							'File' => $inc
						]
					],
				];
				if (is_array($exc)) {
					$fileset_cfg['Exclude'] = [[
						'File' => $exc
					]];
				}
				$params = [
					'config',
					'dir',
					'Fileset',
					$fileset
				];
				$result = $this->getModule('api')->create(
					$params,
					['config' => json_encode($fileset_cfg)]
				);
			}
		}
		if (is_string($fileset)) {
			$job['Fileset'] = $fileset;
		} else {
			/**
			 * If fileset is not defined, take first fileset from the config.
			 * Fileset in verify job is not used for verifying.
			 */
			$params = [
				'config',
				'dir',
				'Fileset'
			];
			$result = $this->getModule('api')->get(
				$params
			);
			if ($result->error === 0 && count($result->output) > 0) {
				$job['Fileset'] = $result->output[0]->Fileset->Name;
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
			$this->CreateResourceErrMsg->Display = 'Dynamic';
			$this->CreateResourceErrMsg->Text = $result->output;
		}
	}

	/**
	 * Get file attributes to compare for DiskToCatalog and Catalog job levels.
	 *
	 * @return string value with all selected attributes
	 */
	private function getFileAttributes()
	{
		$attrs = '';
		if (!$this->WhatJobData->Checked || $this->HowDiskToCatalog->Checked) {
			if ($this->CompareInodes->Checked) {
				$attrs .= $this->CompareInodes->Value;
			}
			if ($this->ComparePermissionBits->Checked) {
				$attrs .= $this->ComparePermissionBits->Value;
			}
			if ($this->CompareNumberOfLinks->Checked) {
				$attrs .= $this->CompareNumberOfLinks->Value;
			}
			if ($this->CompareUserID->Checked) {
				$attrs .= $this->CompareUserID->Value;
			}
			if ($this->CompareGroupID->Checked) {
				$attrs .= $this->CompareGroupID->Value;
			}
		}
		if ($this->CompareSize->Checked) {
			$attrs .= $this->CompareSize->Value;
		}
		if (!$this->WhatJobData->Checked || $this->HowDiskToCatalog->Checked) {
			if ($this->CompareAtime->Checked) {
				$attrs .= $this->CompareAtime->Value;
			}
			if ($this->CompareMtime->Checked) {
				$attrs .= $this->CompareMtime->Value;
			}
			if ($this->CompareSizeDecreases->Checked) {
				$attrs .= $this->CompareSizeDecreases->Value;
			}
		}
		if ($this->CompareMD5Sum->Checked) {
			$attrs .= $this->CompareMD5Sum->Value;
		}
		if ($this->CompareSHA1Sum->Checked) {
			$attrs .= $this->CompareSHA1Sum->Value;
		}
		return $attrs;
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

	/**
	 * Load fileset file browser to select include/exclude paths.
	 * Used for InitCatalog and Catalog job levels.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function loadFSBrowser($sender, $param)
	{
		$client = $this->Client->getDirectiveValue();
		$this->FSBrowser->loadClients($sender, $param);
		$clients = $this->FSBrowser->Client->getItems();
		$clientid = null;
		foreach ($clients as $cli) {
			if ($cli->getText() === $client) {
				$clientid = $cli->getValue();
				break;
			}
		}
		if (is_string($clientid)) {
			$this->FSBrowser->Client->setSelectedValue($clientid);
			$this->FSBrowser->selectClient($sender, $param);
		}
	}

	/**
	 * Special treating include paths validator that needs to be
	 * enabled/disabled depending on selected verify job level.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function checkIncludePathValidator($sender, $param)
	{
		$sender->enabled = $this->WhatFilesystem->Checked;
	}

	/**
	 * Set selected client name.
	 *
	 * @param string $client client name
	 */
	public function setClient($client)
	{
		$this->setViewState(self::CLIENT, $client);
	}

	/**
	 * Get selected client name.
	 *
	 * @return string client name or empty string if client not set
	 */
	public function getClient()
	{
		return $this->getViewState(self::CLIENT, '');
	}

	/**
	 * Set selected storage name.
	 *
	 * @param string $storage storage name
	 */
	public function setStorage($storage)
	{
		$this->setViewState(self::STORAGE, $storage);
	}

	/**
	 * Get selected storage name.
	 *
	 * @return string storage name or empty string if storage not set
	 */
	public function getStorage()
	{
		return $this->getViewState(self::STORAGE, '');
	}
}
