<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TCallback;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Logging;
use Prado\Web\UI\ActiveControls\TActiveDropDownList;

/**
 * Restore wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class RestoreWizard extends BaculumWebPage
{
	/**
	 * Restore wizard modes.
	 */
	private const RESTORE_MODE_JOBID = 'jobid';       // selected jobid restore
	private const RESTORE_MODE_FULL = 'full';         // full restore flow

	// Restore modes - view state name
	private const RESTORE_MODE = 'RestoreMode';

	/**
	 * Job levels allowed to restore.
	 */
	private $joblevel = ['F', 'I', 'D'];

	/**
	 * Job statuses allowed to restore.
	 */
	private $jobstatus = ['T', 'W', 'A', 'E', 'e', 'f'];

	public const JOB_LIST_BY_CLIENT = 1;
	public const JOB_LIST_BY_FILENAME = 2;

	/**
	 * Browser types.
	 */
	public const BROWSER_TYPE_FLAT = 0;
	public const BROWSER_TYPE_TREE = 1;

	/**
	 * File browser special directories.
	 */
	private $browser_root_dir = [
		'name' => '.',
		'type' => 'dir',
		'fileid' => null,
		'pathid' => null,
		'filenameid' => null,
		'jobid' => null,
		'lstat' => '',
		'uniqid' => null
	];
	private $browser_up_dir = [
		'name' => '..',
		'type' => 'dir',
		'fileid' => null,
		'pathid' => null,
		'filenameid' => null,
		'jobid' => null,
		'lstat' => '',
		'uniqid' => null
	];

	/**
	 * Stores file relocation option. Used in template.
	 */
	public $file_relocation_opt;

	/**
	 * Stores list of jobs possible to select to restore.
	 */
	public $jobs_to_restore;

	/**
	 * If set to true, show modal with error message about problem during restore start.
	 */
	public $show_error = false;

	/**
	 * Prefix for Bvfs path.
	 */
	public const BVFS_PATH_PREFIX = 'b2';

	/**
	 * Initialize restore page.
	 *
	 * @param TXmlElement $param page config
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->resetWizard();
		if ($this->Request->contains('jobid')) {
			// Restore by given jobid
			$this->setRestoreMode(self::RESTORE_MODE_JOBID);
			$jobid = (int) $this->Request['jobid'];
			$this->setUpRestoreByJobId($jobid);
		} else {
			// Full restore flow
			$this->setRestoreMode(self::RESTORE_MODE_FULL);
			$this->loadBackupClients();
		}
	}

	/**
	 * On pre-render action.
	 *
	 * @param TXmlElement $param page config
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->setNavigationButtons();
	}

	/**
	 * Set restore wizard mode.
	 *
	 * @param string $mode wizard mode
	 */
	private function setRestoreMode(string $mode): void
	{
		$this->setViewState(self::RESTORE_MODE, $mode, '');
	}

	/**
	 * Get restore wizard mode.
	 *
	 * @return string restore wizard mode
	 */
	private function getRestoreMode(): string
	{
		return $this->getViewState(self::RESTORE_MODE, '');
	}

	/**
	 * Set backup jobid to restore.
	 * Used to restore specific job by jobid.
	 *
	 * @param int $jobid backup job identifier to restore
	 */
	public function setUpRestoreByJobId(int $jobid): void
	{
		$this->setBackupJobIdToRestore($jobid);
		$this->initBrowserContent();
		$this->RestoreWizard->setActiveStep($this->Step3);
		$param = new StdClass();
		$param->CurrentStepIndex = 1;
		$this->RestoreWizard->raiseEvent('OnNextButtonClick', null, $param);
	}

	/**
	 * Set/prepare restore wizard to restore specific jobid.
	 *
	 * @param mixed $jobid
	 */
	public function setBackupJobIdToRestore(int $jobid): void
	{
		// Set restore point
		$job = $this->setRestorePointInfo($jobid);
		if (!is_object($job)) {
			return;
		}

		// Prepare wizard to restore
		$clientid = (int) $job->clientid;

		// Set backup client list
		$this->loadBackupClients($clientid);

		// Set restore client list
		$this->loadRestoreClients($clientid);

		// Load backup job list for client
		$this->loadBackupsForClient();

		// Jump directly to third wizard step
		$step_index = new stdClass();
		$step_index->CurrentStepIndex = 3;
		$this->wizardNext(null, $step_index);
	}

	/**
	 * Set backup job details that is main point for restore.
	 *
	 * @param int $jobid backup job identifier
	 * @param string $name backup job name
	 * @param string $type backup job type
	 * @param string $endtime backup job time
	 * @param string $jobstatus backup job status
	 */
	private function setBackupJobToRestore(int $jobid, string $name, string $type, string $endtime, string $jobstatus): void
	{
		$this->Session->open();
		$this->Session->add(
			'restore_job',
			[
				'jobid' => $jobid,
				'name' => $name,
				'type' => $type,
				'endtime' => $endtime,
				'jobstatus' => $jobstatus
			]
		);
	}

	/**
	 * Set navigation buttons.
	 * Used for restore specific jobid (hide previous button)
	 */
	public function setNavigationButtons(): void
	{
		$prev_btn = $this->RestoreWizard->getStepNavigation()->PreviousStepBtn;
		if ($this->getRestoreMode() == self::RESTORE_MODE_JOBID && $this->RestoreWizard->getActiveStepIndex() === 2) {
			$prev_btn->Visible = false;
		} else {
			$prev_btn->Visible = true;
		}
	}

	/**
	 * Wizard next button callback actions.
	 *
	 * @param TWizard $sender sender object
	 * @param TWizardNavigationEventParameter $param sender parameters
	 */
	public function wizardNext($sender, $param)
	{
		if ($param->CurrentStepIndex === 0) {
			$this->loadBackupsForClient();
			$this->loadGroupBackupToRestore();
			$this->loadGroupBackupFileSets(null, null);
			$backup_clientid = (int) $this->BackupClient->SelectedValue;
			$this->loadRestoreClients($backup_clientid);
			if ($this->BackupClient->DataChanged) {
				// remove previous restore jobid only if user changed client selection
				$this->Session->open();
				$this->Session->remove('restore_job');
			}
		} elseif ($param->CurrentStepIndex === 1) {
			$this->setRestorePath();
			$this->setFileVersions();
			$this->initBrowserContent();
			$this->loadSelectedFiles(null, null);
			$this->loadFileVersions(null, null);
		} elseif ($param->CurrentStepIndex === 2) {
			$this->setPluginInfo();
			$this->loadRequiredVolumes();
			if ($this->Session->contains('file_relocation')) {
				$this->file_relocation_opt = $this->Session['file_relocation'];
			}
		} elseif ($param->CurrentStepIndex === 3) {
			$this->savePluginSettingsForm();
			if ($this->Request->contains('file_relocation')) {
				$this->Session->open();
				$this->Session->add(
					'file_relocation',
					$this->Request['file_relocation']
				);
			}
			$this->file_relocation_opt = $this->Session['file_relocation'];
		}
		$this->setNavigationButtons();
	}

	/**
	 * Wizard prev button callback actions.
	 *
	 * @param TWizard $sender sender object
	 * @param TWizardNavigationEventParameter $param sender parameters
	 */
	public function wizardPrev($sender, $param)
	{
		if ($param->CurrentStepIndex === 1) {
		} elseif ($param->CurrentStepIndex === 2) {
			$this->loadBackupsForClient();
		} elseif ($param->CurrentStepIndex === 3) {
			$this->loadSelectedFiles(null, null);
			$this->loadFileVersions(null, null);
			$this->goToPath();
		} elseif ($param->CurrentStepIndex === 4) {
			$this->file_relocation_opt = $this->Session['file_relocation'];
			$this->loadPluginSettings();
		}
	}

	/**
	 * Cancel wizard.
	 *
	 * @param mixed $sender sender object
	 * @param mixed $param event parameters
	 */
	public function wizardStop($sender, $param)
	{
		$this->resetWizard();
		$this->goToDefaultPage();
	}

	/**
	 * Load backup clients.
	 *
	 * @param null|int $clientid default selected client identifier
	 */
	public function loadBackupClients(?int $clientid = null): void
	{
		$client_list = [];
		$api = $this->getModule('api');
		$result = $api->get(['clients']);
		if ($result->error == 0 && is_array($result->output)) {
			$clients = $result->output;
			for ($i = 0; $i < count($clients); $i++) {
				$client_list[$clients[$i]->clientid] = $clients[$i]->name;
			}
			asort($client_list);
		}
		$this->BackupClient->DataSource = $client_list;
		if ($clientid) {
			$this->BackupClient->SelectedValue = $clientid;
		}
		$this->BackupClient->dataBind();
	}

	/**
	 * Load restore client list.
	 *
	 * @param null|int $clientid default selected client identifier
	 */
	public function loadRestoreClients(?int $clientid = null): void
	{
		$client_list = [];
		$api = $this->getModule('api');
		$result = $api->get(['clients']);
		if ($result->error == 0 && is_array($result->output)) {
			$clients = $result->output;
			for ($i = 0; $i < count($clients); $i++) {
				$client_list[$clients[$i]->clientid] = $clients[$i]->name;
			}
			asort($client_list);
		}
		$this->RestoreClient->DataSource = $client_list;
		if ($clientid) {
			$this->RestoreClient->SelectedValue = $clientid;
		}
		$this->RestoreClient->dataBind();
	}

	/**
	 * Load backups for selected client (Step 2).
	 *
	 * @return array client backup jobs
	 */
	public function loadBackupsForClient(): array
	{
		$clientid = $this->BackupClient->SelectedValue;
		$api = $this->getModule('api');
		$result = $api->get(
			['clients', $clientid, 'jobs']
		);
		if ($result->error != 0) {
			return [];
		}
		$jobs_for_client = $result->output;
		$misc = $this->getModule('misc');
		$jobs = $misc->objectToArray($jobs_for_client);
		$add_file = function ($item) {
			$item['file'] = '';
			return $item;
		};
		$jobs = array_map($add_file, $jobs);
		return array_filter($jobs, [$this, 'isJobToRestore']);
	}

	/**
	 * Load backups for selected client by filename (Step 2).
	 *
	 * @param string $filename filename to find a backup
	 * @param bool $strict strict mode with exact matching name == filename
	 * @param string $path path to narrow down results to given path
	 * @return array job list with files
	 */
	private function loadBackupsByFilename(string $filename, bool $strict, string $path): array
	{
		$clientid = $this->BackupClient->SelectedValue;
		$query = [
			'clientid' => $clientid,
			'filename' => $filename,
			'strict' => $strict,
			'path' => $path
		];
		$params = [
			'jobs',
			'files',
			'?' . http_build_query($query)
		];
		$api = $this->getModule('api');
		$result = $api->get($params);
		$ret = [];
		if ($result->error == 0) {
			$misc = $this->getModule('misc');
			$jobs = $misc->objectToArray($result->output);
			$ret = array_filter($jobs, [$this, 'isJobToRestore']);
		}
		return $ret;
	}

	/**
	 * Load job list.
	 * Common method both for loading job list for a client and for job list displayed
	 * after providing filename saved in backup.
	 * It is responsible for loading job list to select by user for restore.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param param object
	 */
	public function loadJobList($sender, $param): void
	{
		$prop = $param->getCallbackParameter();
		$jobs = [];
		$list_type = self::JOB_LIST_BY_CLIENT;
		if (is_object($prop) && !empty($prop->filename)) {
			$list_type = self::JOB_LIST_BY_FILENAME;
			$jobs = $this->loadBackupsByFilename(
				$prop->filename,
				$prop->strict,
				$prop->path
			);
		} else {
			$list_type = self::JOB_LIST_BY_CLIENT;
			$jobs = $this->loadBackupsForClient();
		}
		$this->getCallbackClient()->callClientFunction(
			'oJobsToRestoreList.update_table',
			[array_values($jobs), $list_type]
		);
	}

	/**
	 * Check if job can be used in restore.
	 *
	 * @param array $job job properties
	 * @return bool true if job should be listed to restore, otherwise false
	 */
	private function isJobToRestore($job): bool
	{
		$jobtype = ['B'];
		if ($this->EnableCopyJobRestore->Checked) {
			$jobtype[] = 'C';
		}
		return (
			in_array($job['type'], $jobtype) &&
			in_array($job['level'], $this->joblevel) &&
			in_array($job['jobstatus'], $this->jobstatus)
		);
	}

	/**
	 * Load backup jobs to restore for group most recent backups feature.
	 *
	 */
	public function loadGroupBackupToRestore(): void
	{
		// Get jobs
		$api = $this->getModule('api');
		$clientid = (int) $this->BackupClient->SelectedValue;
		$jobtype = ['B'];
		if ($this->EnableCopyJobRestore->Checked) {
			$jobtype[] = 'C';
		}
		$query = [
			'clientid' => $clientid,
			'type' => implode('', $jobtype),
			'jobstatus' => implode('', $this->jobstatus),
			'level' => implode('', $this->joblevel)
		];
		$jobs = $api->get([
			'jobs',
			'?' . http_build_query($query)
		]);

		if ($jobs->error != 0) {
			return;
		}

		$job_group = ['' => ''];
		for ($i = 0; $i < count($jobs->output); $i++) {
			$job_group[$jobs->output[$i]->name] = $jobs->output[$i]->name;
		}
		asort($job_group);

		$this->GroupBackupToRestore->DataSource = $job_group;
		$this->GroupBackupToRestore->dataBind();
	}

	/**
	 * Load filesets by selected job in group recent backups.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param param object
	 */
	public function loadGroupBackupFileSets($sender, $param): void
	{
		$job = $this->GroupBackupToRestore->SelectedValue;
		if (empty($job)) {
			// no job, no fileset
			$this->GroupBackupFileSet->DataSource = [];
			$this->GroupBackupFileSet->dataBind();
			return;
		}
		$params = [
			'job' => $job
		];
		$query = '?' . http_build_query($params);
		$api = $this->getModule('api');
		$filesets = $api->get([
			'filesets',
			$query
		]);
		$fileset_group = ['' => ''];
		if ($filesets->error == 0) {
			for ($i = 0; $i < count($filesets->output); $i++) {
				$key = $filesets->output[$i]->filesetid;
				$value = $filesets->output[$i]->fileset . ' (' . $filesets->output[$i]->createtime . ')';
				$fileset_group[$key] = $value;
			}
		}
		asort($fileset_group);

		$this->GroupBackupFileSet->DataSource = $fileset_group;
		$this->GroupBackupFileSet->dataBind();
	}

	/**
	 * Initialize restore file browser content.
	 */
	private function initBrowserContent(): void
	{
		$jobids = [];

		// Prepare jobid to set restore point
		$jobid = 0;
		$prev_jobid = $this->Session['restore_job']['jobid'] ?? 0;
		if ($this->OnlySelectedBackupSelection->Checked) {
			// Selected backup job restore method
			if ($this->Request->contains('jobid')) {
				$jobid = (int) $this->Request['jobid'];
				// Set restore point details
				$this->setRestorePointInfo($jobid);
				$jobids = $this->getElementaryBackupSelected();
			}
		} elseif ($this->GroupBackupSelection->Checked) {
			// Group most recent backups restore method
			$jobids = $this->getElementaryBackupGroup();
			$recent_len = count($jobids);
			if ($recent_len > 0) {
				$jobid = (int) $jobids[0];
				// Set restore point details
				$this->setRestorePointInfo($jobid);
			}
		}

		if ($prev_jobid != $jobid) {
			// Restore point changed - reset path
			$this->PathField->Text = '';
		}

		// remember elementary jobids
		$this->setElementaryBackups($jobids);

		if (!empty($jobids)) {
			// Generating Bvfs may take a moment
			$this->generateBvfsCache($jobids);
		}
	}

	/**
	 * Prepare restore file browser content.
	 *
	 * @param array $jobids job identifiers
	 * @return array list of elements do display in file browser
	 */
	private function prepareBrowserContent(array $jobids): array
	{
		$elements = [];

		if (empty($jobids)) {
			return $elements;
		}

		// Prepare offset and limit
		$offset = $limit = null;
		if ($this->FileBrowserTypeFlat->Checked) {
			$offset = (int) ($this->RestoreBrowserOffset->Text);
			$limit = (int) ($this->RestoreBrowserLimit->Text);
		}

		// Get BVFS directory list
		$dirs = [];
		if ($this->Session->contains('restore_pathid')) {
			$pathid = $this->Session['restore_pathid'];
			$dirs = $this->getBVFSDirectoriesByPathId($pathid, $jobids, $offset, $limit);
		} else {
			$path = $this->FileBrowserTypeFlat->Checked ? implode($this->Session['restore_path']) : '';
			$dirs = $this->getBVFSDirectoriesByPath($path, $jobids, $offset, $limit);
		}

		$dir_count = count($dirs);
		if ($dir_count == 1 && ($dirs[0]['name'] == '/' || preg_match('/^[A-Z]+:\/$/i', $dirs[0]['name']) === 1)) {
			$this->RestoreBrowserDirCount->Text = $dir_count;
		} elseif ($dir_count == 0) {
			$this->RestoreBrowserDirCount->Text = 0;
		} else {
			$this->RestoreBrowserDirCount->Text = ($dir_count - 1);
		}

		// Get BVFS file list
		$files = [];
		if ($this->Session->contains('restore_pathid')) {
			$pathid = $this->Session['restore_pathid'];
			$files = $this->getBVFSFilesByPathId($pathid, $jobids, $offset, $limit);
		} else {
			$path = $this->FileBrowserTypeFlat->Checked ? implode($this->Session['restore_path']) : '';
			$files = $this->getBVFSFilesByPath($path, $jobids, $offset, $limit);
		}
		$this->RestoreBrowserFileCount->Text = count($files);

		$elements = array_merge($dirs, $files);
		$elements = $this->addExtraPropsToElements($elements);
		if (count($this->Session['restore_path']) > 0) {
			array_unshift($elements, $this->browser_root_dir);
		}
		if ($this->Session->contains('restore_pathid')) {
			// clear pathid in session as it is used only for browser element request time.
			$this->Session->open();
			$this->Session->remove('restore_pathid');
		}
		return $elements;
	}

	/**
	 * Set restore jobid point.
	 * This jobid is oldest jobid taken into account in the restore.
	 *
	 * @param int $jobid job identifier
	 * @return null|object restore point jobid object or null on error
	 */
	private function setRestorePointInfo(int $jobid): ?object
	{
		if ($jobid <= 0) {
			return null;
		}

		// Get restore point details
		$api = $this->getModule('api');
		$result = $api->get(
			['jobs', $jobid]
		);
		if ($result->error != 0) {
			return null;
		}
		$job = $result->output;

		// Remember restore point
		$this->setBackupJobToRestore(
			$jobid,
			$job->name,
			$job->type,
			$job->endtime,
			$job->jobstatus
		);

		// Set restore point in template
		if ($this->Session->contains('restore_job')) {
			$this->RestoreBrowserClient->Text = $job->client;
			$this->RestoreBrowserName->Text = $this->Session['restore_job']['name'];
			$this->RestoreBrowserType->Text = $this->Session['restore_job']['type'];
			$this->RestoreBrowserStatus->Text = $this->Session['restore_job']['jobstatus'];
			$this->RestoreBrowserTimePoint->Text = $this->Session['restore_job']['endtime'];
		}

		return $job;
	}

	/**
	 * Get BVFS directory list by full path.
	 *
	 * @param string $path full path
	 * @param array $jobids job identifiers
	 * @param int $offset directory list offset
	 * @param int $limit directory list limit
	 * @return array directory list
	 */
	private function getBVFSDirectoriesByPath(string $path, array $jobids, ?int $offset = null, ?int $limit = null): array
	{
		$jobids_str = implode(',', $jobids);
		$criteria = [
			'jobids' => $jobids_str,
			'path' => $path,
			'output' => 'json'
		];
		if (is_int($offset)) {
			$criteria['offset'] = $offset;
		}
		if (is_int($limit)) {
			$criteria['limit'] = $limit;
		}
		return $this->getBVFSDirectories($criteria);
	}

	/**
	 * Get BVFS directory list by path identifier.
	 *
	 * @param string $pathid path identifier
	 * @param array $jobids job identifiers
	 * @param int $offset directory list offset
	 * @param int $limit directory list limit
	 * @return array directory list
	 */
	private function getBVFSDirectoriesByPathId(int $pathid, array $jobids, ?int $offset = null, ?int $limit = null): array
	{
		$jobids_str = implode(',', $jobids);
		$criteria = [
			'jobids' => $jobids_str,
			'pathid' => $pathid,
			'output' => 'json'
		];
		if (is_int($offset)) {
			$criteria['offset'] = $offset;
		}
		if (is_int($limit)) {
			$criteria['limit'] = $limit;
		}
		return $this->getBVFSDirectories($criteria);
	}

	/**
	 * Get BVFS directory list.
	 *
	 * @param array $criteria BVFS directory request parameters
	 * @return array directory list
	 */
	private function getBVFSDirectories(array $criteria): array
	{
		$query = '?' . http_build_query($criteria);
		$api = $this->getModule('api');
		$bvfs_dirs = $api->get(
			['bvfs', 'lsdirs', $query]
		);
		$dirs = [];
		if ($bvfs_dirs->error === 0) {
			$dirs_str = json_encode($bvfs_dirs->output);
			$dirs = json_decode($dirs_str, true);
		}
		return $dirs;
	}

	/**
	 * Get BVFS file list by full path.
	 *
	 * @param string $path full path
	 * @param array $jobids job identifiers
	 * @param int $offset directory list offset
	 * @param int $limit directory list limit
	 * @return array file list
	 */
	private function getBVFSFilesByPath(string $path, array $jobids, ?int $offset = null, ?int $limit = null): array
	{
		$jobids_str = implode(',', $jobids);
		$criteria = [
			'jobids' => $jobids_str,
			'path' => $path,
			'output' => 'json'
		];
		if (is_int($offset)) {
			$criteria['offset'] = $offset;
		}
		if (is_int($limit)) {
			$criteria['limit'] = $limit;
		}
		return $this->getBVFSFiles($criteria);
	}

	/**
	 * Get BVFS file list by path identifier.
	 *
	 * @param string $pathid path identifier
	 * @param array $jobids job identifiers
	 * @param int $offset directory list offset
	 * @param int $limit directory list limit
	 * @return array file list
	 */
	private function getBVFSFilesByPathId(int $pathid, array $jobids, ?int $offset = null, ?int $limit = null): array
	{
		$jobids_str = implode(',', $jobids);
		$criteria = [
			'jobids' => $jobids_str,
			'pathid' => $pathid,
			'output' => 'json'
		];
		if (is_int($offset)) {
			$criteria['offset'] = $offset;
		}
		if (is_int($limit)) {
			$criteria['limit'] = $limit;
		}
		return $this->getBVFSFiles($criteria);
	}

	/**
	 * Get BVFS file list.
	 *
	 * @param array $criteria BVFS file request parameters
	 * @return array file list
	 */
	private function getBVFSFiles(array $criteria): array
	{
		$query = '?' . http_build_query($criteria);
		$api = $this->getModule('api');
		$bvfs_dirs = $api->get(
			['bvfs', 'lsfiles', $query]
		);
		$dirs = [];
		if ($bvfs_dirs->error === 0) {
			$dirs_str = json_encode($bvfs_dirs->output);
			$dirs = json_decode($dirs_str, true);
		}
		return $dirs;
	}

	/**
	 * Set plugin parameters.
	 * The parameters are not set if backup does not contain plugin data.
	 */
	private function setPluginInfo(): void
	{
		$jobids = $this->getElementaryBackup();
		$q = [
			'jobids' => implode(',', $jobids),
			'output' => 'json',
			'path' => '/'
		];
		$query = '?' . http_build_query($q);
		$api = $this->getModule('api');
		$bvfs_dirs = $api->get(
			['bvfs', 'lsdirs', $query]
		);
		$dir = '';
		if ($bvfs_dirs->error == 0) {
			for ($i = 0; $i < count($bvfs_dirs->output); $i++) {
				if ($bvfs_dirs->output[$i]->type == 'dir' && strpos($bvfs_dirs->output[$i]->name, '#') !== false) {
					$dir = $bvfs_dirs->output[$i]->name;
					break;
				}
			}
		}
		if ($dir) {
			$q['path'] = '/' . $dir;
			$query = '?' . http_build_query($q);
			$bvfs_dirs = $api->get(
				['bvfs', 'lsdirs', $query]
			);
			if ($bvfs_dirs->error === 0 && count($bvfs_dirs->output) === 2 && $bvfs_dirs->output[1]->type == 'dir') {
				$name = rtrim($bvfs_dirs->output[1]->name, '/');
				$this->loadPluginSettings($name);
			}
		} else {
			$this->Session->open();
			$this->Session->add('plugin_info', []);
		}
	}

	/**
	 * Add extra properties to restore browser elements.
	 *
	 * @param array $elements browser elements
	 * @return array browser elements with extra properties added
	 */
	private function addExtraPropsToElements(array $elements): array
	{
		$ppathid = -1;
		if ($this->Session->contains('restore_pathid')) {
			$ppathid = $this->Session['restore_pathid'];
		}
		$add_extra_props_func = function ($el) use ($ppathid) {
			// add unique identifier
			$el = self::addUniqid($el);

			// add parent path identifier
			$el['ppathid'] = $ppathid;
			return $el;
		};
		$elements = array_map($add_extra_props_func, $elements);
		return $elements;
	}

	/**
	 * Small helper to prepare unique identifier for file and directory items.
	 * Uniqid is important to support restore all paths including paths that contain
	 * in FileSet File value, ex. restore "/home" where File="/home/gani/abc".
	 *
	 * @param array $el parsed Bvfs list item
	 * @return array Bvfs list item with uniqid
	 */
	public static function addUniqid($el)
	{
		$el['uniqid'] = sprintf(
			'%d:%d:%d',
			$el['jobid'],
			$el['pathid'],
			$el['fileid']
		);
		return $el;
	}

	/*
	 * Get single elementary backup job identifiers.
	 *
	 * @return string comma separated job identifiers
	 */
	private function getElementaryBackup(): array
	{
		$jobids = [];
		if (isset($this->Session['backup_jobids'])) {
			// Get jobids from session
			$jobids = $this->Session['backup_jobids'];
		} else {
			// Get jobids from API
			if ($this->OnlySelectedBackupSelection->Checked) {
				$jobids = $this->getElementaryBackupSelected();
			} elseif ($this->GroupBackupSelection->Checked) {
				$jobids = $this->getElementaryBackupGroup();
			}
		}
		return $jobids;
	}

	/**
	 * Set single elementary backup job identifiers.
	 *
	 * @param array $jobids job identifiers
	 */
	private function setElementaryBackups(array $jobids): void
	{
		$this->Session->open();
		$this->Session->add(
			'backup_jobids',
			$jobids
		);
	}

	/**
	 * Get single elementary backup job identifiers for selected backup restore method.
	 *
	 * @return array elementary backup job identifiers
	 */
	private function getElementaryBackupSelected(): array
	{
		$jobids = [];
		if (!$this->Session->contains('restore_job')) {
			return $jobids;
		}
		// Selected job restore method
		$options = [];
		if ($this->EnableCopyJobRestore->Checked) {
			$options['inc_copy_job'] = 1;
		}
		$jobids = $this->getBVFSJobIds(
			$this->Session['restore_job']['jobid'],
			$options
		);
		if (empty($jobids)) {
			// If no jobids, use the base jobid
			$jobids = [(int) $this->Session['restore_job']['jobid']];
		}
		return $jobids;
	}

	/**
	 * Get single elementary backup job identifiers for group recent job restore method.
	 *
	 * @return array elementary backup job identifiers
	 */
	private function getElementaryBackupGroup(): array
	{
		$jobids = [];
		if (!$this->GroupBackupToRestore->SelectedValue || !$this->GroupBackupFileSet->SelectedValue) {
			return $jobids;
		}

		// Group most recent jobs restore method
		$job_name = $this->GroupBackupToRestore->SelectedValue;
		$clientid = $this->BackupClient->SelectedValue;
		$filesetid = $this->GroupBackupFileSet->SelectedValue;
		$options = [];
		if ($this->EnableCopyJobRestore->Checked) {
			$options['inc_copy_job'] = 1;
		}
		$jobids = $this->getRecentJobIds(
			$job_name,
			$clientid,
			$filesetid,
			$options
		);
		return $jobids;
	}

	/**
	 * Get elementary job identifiers required to build full job restore tree.
	 * In practice they are all jobids for given jobid, that are required
	 * to consistent restore this job.
	 *
	 * @param int $jobid job identifier
	 * @param array $options BVFS options
	 * @return array job identifier list
	 */
	private function getBVFSJobIds(int $jobid, array $options = []): array
	{
		$params = [
			'jobid' => $jobid,
			'output' => 'json'
		];
		$params = array_merge($params, $options);
		$query = '?' . http_build_query($params);
		$api = $this->getModule('api');
		$result = $api->get(
			['bvfs', 'getjobids', $query]
		);
		$jobids = [];
		if ($result->error == 0) {
			$jobids = $result->output ?: [];
		}
		return $jobids;
	}

	/**
	 * Get recent job identifiers based on selected client and fileset.
	 *
	 * @param string $job_name job name
	 * @param int $clientid client identifier
	 * @param int $filesetid fileset identifier
	 * @param array $options request options
	 * @return array recent job identifiers or empty list on error
	 */
	private function getRecentJobIds(string $job_name, int $clientid, int $filesetid, array $options = []): array
	{
		$params = [
			'clientid' => $clientid,
			'filesetid' => $filesetid
		];
		$params = array_merge($params, $options);
		$query = '?' . http_build_query($params);
		$api = $this->getModule('api');
		$jobs_recent = $api->get([
			'jobs',
			'recent',
			$job_name,
			$query
		]);

		$jobids = [];
		if ($jobs_recent->error == 0) {
			$jobids = $jobs_recent->output;
		}
		return $jobids;
	}

	/**
	 * Load path callback method.
	 * Used for manually typed paths in path field.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TEventParameter $param events parameter
	 */
	public function loadPath($sender, $param): void
	{
		$path = null;
		if ($this->FileBrowserTypeTree->Checked && $param->CallbackParameter === null) {
			// empty initial path to get root directory tree node
			$path = '';
		} else {
			$spath = $this->PathField->Text;
			$path = explode('/', $spath);
			$path_len = count($path);
			for ($i = 0; $i < $path_len; $i++) {
				if ($i == ($path_len - 1) && empty($path[$i])) {
					// last path dir is slash so not add slash to last element
					break;
				}
				$path[$i] .= '/';
			}
			$path = array_filter($path); // remove empty item if any
		}
		$this->goToPath($path, true);
	}

	/**
	 * Go to specific path in the file browser.
	 * There is possible to pass both single directory 'somedir'
	 * or whole path '/etc/somedir'.
	 *
	 * @param array|string $path path to go
	 * @param bool $full_path determines if $path param is full path or relative path (singel directory)
	 */
	private function goToPath($path = '', bool $full_path = false): void
	{
		if (!empty($path) && !$full_path && $this->Session->contains('restore_path')) {
			if ($path == $this->browser_up_dir['name']) {
				$rp = $this->Session['restore_path'];
				array_pop($rp);
				$this->Session->open();
				$this->Session->add('restore_path', $rp);
			} elseif ($path == $this->browser_root_dir['name']) {
				$this->setRestorePath();
			} else {
				$rp = $this->Session['restore_path'];
				array_push($rp, $path);
				$this->Session->open();
				$this->Session->add('restore_path', $rp);
			}
		}
		if ($full_path && is_array($path)) {
			$this->setRestorePath($path);
		}
		$this->loadBrowserPath();
		$this->loadBrowserFiles();
	}

	/**
	 * Go to specific path in the file browser by pathid.
	 *
	 * @param int $pathid path identifer to go
	 */
	private function goToPathByPathId(int $pathid): void
	{
		$this->setRestorePathId($pathid);
		$this->loadBrowserPath();
		$this->loadBrowserFiles();
	}

	/**
	 * Add/mark file to restore.
	 * Used as callback to drag&drop browser elements.
	 *
	 * @param object $sender sender object
	 * @param object $param param object
	 */
	public function addFileToRestore($sender, $param): void
	{
		[$uniqid, $file_prop] = $param->CallbackParameter;
		$file_prop = (array) $file_prop;

		if ($file_prop['name'] != $this->browser_root_dir['name'] && $file_prop['name'] != $this->browser_up_dir['name']) {
			$this->markFileToRestore($uniqid, $file_prop);
			$this->loadSelectedFiles(null, null);
		}
	}

	/**
	 * Remove file from files marked to restore.
	 *
	 * @param TCallback $sender sender object
	 * @param TEventParameter $param param object
	 */
	public function removeSelectedFile($sender, $param)
	{
		$uniqid = $param->CallbackParameter;
		$this->unmarkFileToRestore($uniqid);
		$this->loadSelectedFiles(null, null);
	}

	/**
	 * Get file backed up versions.
	 * Called as callback on file element click.
	 *
	 * @param TCallback $sender sender object
	 * @param object $param param object
	 */
	public function getVersions($sender, $param): void
	{
		[$filename, $pathid, $filenameid, $jobid] = $param->CallbackParameter;
		if ($filenameid == 0) {
			if ($filename == $this->browser_root_dir['name'] || $filename == $this->browser_up_dir['name']) {
				$this->goToPath($filename);
			} else {
				if ($this->FileBrowserTypeFlat->Checked) {
					$rp = $this->Session['restore_path'];
					array_push($rp, $filename);
					$this->setRestorePath($rp); // to fill path field in the wizard
				}
				$this->goToPathByPathId((int) $pathid); // to go by pathid
			}
			return;
		}
		$clientid = $this->BackupClient->SelectedValue;
		$params = [
			'clientid' => $clientid,
			'jobid' => $jobid,
			'pathid' => $pathid,
			'filenameid' => $filenameid,
			'output' => 'json'
		];
		if ($this->EnableCopyJobRestore->Checked) {
			$params['copies'] = 1;
		}

		/**
		 * Helper for adding filename to versions list.
		 *
		 * @param array $el version list element
		 * @return return version list element
		 */
		$add_version_filename_func = function ($el) use ($filename) {
			$el['name'] = $filename;
			return $el;
		};

		$query = '?' . http_build_query($params);
		$api = $this->getModule('api');
		$result = $api->get(
			['bvfs', 'versions', $query]
		);
		if ($result->error != 0) {
			return;
		}
		$versions = json_decode(json_encode($result->output), true);
		$file_versions = array_map($add_version_filename_func, $versions);
		$file_versions = $this->addExtraPropsToElements($file_versions);
		$this->setFileVersions($file_versions);
		$this->loadFileVersions(null, null);
		$this->loadSelectedFiles(null, null);
	}

	/*
	 * Load file browser files to list.
	 *
	 * @param array $files files to list.
	 */
	public function loadBrowserFiles(): void
	{
		$jobids = $this->getElementaryBackup();
		$files = $this->prepareBrowserContent($jobids);

		// Set no file found message
		if (count($files) > 0) {
			$this->NoFileFound->Display = 'None';
		} elseif ($this->Session->contains('restore_job')) {
			$this->NoFileFound->Display = 'Dynamic';
		}

		$cb = $this->getCallbackClient();
		$cb->callClientFunction(
			'oRestoreBrowserFiles.populate',
			[$files]
		);
	}

	/**
	 * Load file versions area.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function loadFileVersions($sender, $param): void
	{
		$versions = $this->Session->contains('files_versions') ? $this->Session['files_versions'] : [];
		$cb = $this->getCallbackClient();
		$cb->callClientFunction(
			'oRestoreBrowserVersions.populate',
			[array_values($versions)]
		);
	}

	/**
	 * Load selected files in drop area.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function loadSelectedFiles($sender, $param): void
	{
		$files = $this->Session->contains('files_restore') ? $this->Session['files_restore'] : [];
		$cb = $this->getCallbackClient();
		$cb->callClientFunction(
			'oRestoreBrowserSelectedFiles.populate',
			[array_values($files)]
		);
	}

	/**
	 * Set file browser path field.
	 */
	private function loadBrowserPath(): void
	{
		if (!$this->FileBrowserTypeFlat->Checked) {
			return;
		}
		// browser path field is used only for flat browser, not for tree browser
		$path = $this->Session->contains('restore_path') ? $this->Session['restore_path'] : [];
		$this->PathField->Text = implode($path);
	}

	/**
	 * Generate Bvfs cache by job identifiers.
	 *
	 * @param array $jobids job identifiers
	 */
	private function generateBvfsCache(array $jobids): void
	{
		$jobids_str = implode(',', $jobids);
		$api = $this->getModule('api');
		$api->set(
			['bvfs', 'update'],
			['jobids' => $jobids_str]
		);
	}

	/**
	 * Set versions for selected file.
	 *
	 * @param array $versions file versions data
	 */
	private function setFileVersions(array $versions = []): void
	{
		$this->Session->open();
		$this->Session->add('files_versions', $versions);
	}

	/**
	 * Set restore browser path.
	 *
	 * @param array $path path
	 */
	private function setRestorePath($path = []): void
	{
		$this->Session->open();
		$this->Session->add('restore_path', $path);
	}

	/**
	 * Set restore browser pathid.
	 *
	 * @param int $pathid path identifier
	 */
	private function setRestorePathId(int $pathid): void
	{
		$this->Session->open();
		$this->Session->add('restore_pathid', $pathid);
	}

	/**
	 * Mark file to restore.
	 *
	 * @param string $uniqid file identifier
	 * @param array $file_prop file properties to mark
	 */
	private function markFileToRestore($uniqid, $file_prop): void
	{
		if (is_null($uniqid)) {
			$this->setFilesToRestore();
		} elseif ($file_prop['name'] != $this->browser_root_dir['name'] && $file_prop['name'] != $this->browser_up_dir['name']) {
			$fr = $this->Session['files_restore'];
			$fr[$uniqid] = $file_prop;
			$this->Session->open();
			$this->Session->add('files_restore', $fr);
		}
	}

	/**
	 * Unmark file to restore.
	 *
	 * @param string $uniqid file identifier
	 */
	private function unmarkFileToRestore($uniqid): void
	{
		if (key_exists($uniqid, $this->Session['files_restore'])) {
			$fr = $this->Session['files_restore'];
			unset($fr[$uniqid]);
			$this->Session->open();
			$this->Session->add('files_restore', $fr);
		}
	}

	/**
	 * Get files to restore.
	 *
	 * @return array list with files to restore
	 */
	public function getFilesToRestore(): array
	{
		return ($this->Session->contains('files_restore') ? $this->Session['files_restore'] : []);
	}

	/**
	 * Set files to restore
	 *
	 * @param array $files files to restore
	 */
	public function setFilesToRestore(array $files = []): void
	{
		$this->Session->open();
		$this->Session->add('files_restore', $files);
	}

	/**
	 * Get all restore elements (fileids and dirids).
	 *
	 * @return array list fileids and dirids
	 */
	public function getRestoreElements(): array
	{
		$fileids = [];
		$dirids = [];
		$findexes = [];
		$ftores = $this->getFilesToRestore();
		foreach ($ftores as $uniqid => $properties) {
			if ($properties['type'] == 'dir') {
				$dirids[] = $properties['pathid'];
			} elseif ($properties['type'] == 'file') {
				$fileids[] = $properties['fileid'];
				$lstat = (array) $properties['lstat'];
				if ($lstat['linkfi'] !== 0) {
					$findexes[] = $properties['jobid'] . ',' . $lstat['linkfi'];
				}
			}
		}
		$ret = [
			'fileid' => $fileids,
			'dirid' => $dirids,
			'findex' => $findexes
		];
		return $ret;
	}

	/**
	 * Wizard finish method.
	 */
	public function wizardCompleted(): void
	{
		$jobids = $this->getElementaryBackup();
		$path = self::BVFS_PATH_PREFIX . getmypid();
		$restore_elements = $this->getRestoreElements();
		$cmd_props = ['jobids' => implode(',', $jobids), 'path' => $path];
		$is_element = false;
		if (count($restore_elements['fileid']) > 0) {
			$cmd_props['fileid'] = implode(',', $restore_elements['fileid']);
			$is_element = true;
		}
		if (count($restore_elements['dirid']) > 0) {
			$cmd_props['dirid'] = implode(',', $restore_elements['dirid']);
			$is_element = true;
		}
		if (count($restore_elements['findex']) > 0) {
			$cmd_props['findex'] = implode(',', $restore_elements['findex']);
			$is_element = true;
		}

		$jobid = null;
		$ret = new StdClass();
		$restore_props = [
			'client' => $this->RestoreClient->SelectedItem->Text
		];
		$sess = $this->getApplication()->getSession();
		if ($sess->itemAt('file_relocation') == 2) {
			if (!empty($this->RestoreStripPrefix->Text)) {
				$restore_props['strip_prefix'] = $this->RestoreStripPrefix->Text;
			}
			if (!empty($this->RestoreAddPrefix->Text)) {
				$restore_props['add_prefix'] = $this->RestoreAddPrefix->Text;
			}
			if (!empty($this->RestoreAddSuffix->Text)) {
				$restore_props['add_suffix'] = $this->RestoreAddSuffix->Text;
			}
		} elseif ($sess->itemAt('file_relocation') == 3) {
			if (!empty($this->RestoreRegexWhere->Text)) {
				$restore_props['regex_where'] = $this->RestoreRegexWhere->Text;
			}
		}
		if (!key_exists('add_prefix', $restore_props)) {
			$restore_props['where'] = $this->RestorePath->Text;
		}
		if ($this->RestoreToOriginalLocation->Checked && isset($this->Session['plugin_info']['plugin']['parameters'])) {
			$where_params = json_encode($this->Session['plugin_info']['plugin']['parameters']);
			$restore_props['where'] = '#' . base64_encode($where_params);
		}

		$restore_props['replace'] = $this->ReplaceFiles->SelectedValue;
		$restore_props['restorejob'] = $this->RestoreJob->SelectedValue;
		$api = $this->getModule('api');
		$misc = $this->getModule('misc');
		if ($is_element) {
			// Single file restore
			$api->create(
				['bvfs', 'restore'],
				$cmd_props
			);
			$restore_props['rpath'] = $path;

			$ret = $api->create(
				['jobs', 'restore'],
				$restore_props
			);
			$jobid = $misc->findJobIdStartedJob($ret->output);
			// Remove temporary BVFS table
			$api->set(['bvfs', 'cleanup'], ['path' => $path]);
		} elseif ($this->Session->contains('restore_job')) {
			// Full backup restore
			$restore_props['full'] = 1;
			$restore_props['id'] = $this->Session['restore_job']['jobid'];
			$job = $api->get(
				['jobs', $this->Session['restore_job']['jobid']]
			)->output;
			if (is_object($job)) {
				$restore_props['fileset'] = $job->fileset;
			}
			$ret = $api->create(
				['jobs', 'restore'],
				$restore_props
			);
			$jobid = $misc->findJobIdStartedJob($ret->output);
		} else {
			// Nothing selected
			$ret->output = ['No file to restore found'];
		}
		$url_params = [];
		$audit = $this->getModule('audit');
		if (is_numeric($jobid)) {
			$this->resetWizard();
			$url_params['jobid'] = $jobid;
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Run restore. Job: {$restore_props['restorejob']}, JobId: $jobid"
			);
			$this->goToPage('JobView', $url_params);
		} else {
			$this->RestoreError->Text = implode('<br />', $ret->output);
			$this->show_error = true;
			$audit->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				"Run restore failed. Job: {$restore_props['restorejob']}"
			);
		}
	}

	/**
	 * Load restore jobs on the list.
	 */
	private function loadRestoreJobs(): void
	{
		$api = $this->getModule('api');
		$result = $api->get(
			['jobs', 'resnames', '?type=R']
		);
		if ($result->error != 0) {
			return;
		}
		$jobs = [];
		foreach ($result->output as $director => $restore_jobs) {
			$jobs = array_merge($jobs, $restore_jobs);
		}
		$this->RestoreJob->DataSource = array_combine($jobs, $jobs);
		if (count($jobs) > 0) {
			$this->RestoreJob->SelectedValue = $jobs[0];
		}
		$this->RestoreJob->dataBind();
	}

	/**
	 * Load where parameter from restore job.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function setWherePath($sender, $param): void
	{
		$restore_job = $this->RestoreJob->SelectedValue;
		if (empty($restore_job) || $this->RestoreToOriginalLocation->Checked) {
			return;
		}
		$params = [
			'name' => $restore_job,
			'output' => 'json'
		];
		$query = '?' . http_build_query($params);
		$api = $this->getModule('api');
		$result = $api->get(
			['jobs', 'show', $query]
		);
		$where = '/tmp/restore';
		if ($result->error == 0 && isset($result->output->where)) {
			$where = $result->output->where;
		}
		$this->RestorePath->Text = $where;
		$cb = $this->getCallbackClient();
		$cb->hide('restore_path_loader');
	}

	/**
	 * Load plugin settings.
	 *
	 * @param null|string $settings_name plugin setting name
	 */
	public function loadPluginSettings(?string $settings_name = null): void
	{
		$settings_name ??= ($this->Session['plugin_info']['plugin']['name'] ?? '');
		if (!$settings_name) {
			return;
		}
		$plugin_config = $this->getModule('plugin_config');
		$plugin = $plugin_config->getConfig($settings_name);
		if (count($plugin) == 0) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Plugin setting '{$settings_name}' does not exist."
			);
			return;
		}
		$props = $plugin_config->getPlugins(null, $plugin['plugin']);
		if (!key_exists($plugin['plugin'], $props)) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Plugin '{$plugin['plugin']}' is not installed."
			);
			return;
		}
		$setting = $props[$plugin['plugin']];
		$categories = $setting['cls']::getRestoreParameterCategories();
		$parameters = array_filter($setting['parameters'], function ($item) use ($categories) {
			if (count($item['category']) == 0) {
				return true;
			}
			$is_category = false;
			for ($i = 0; $i < count($item['category']); $i++) {
				if (in_array($item['category'][$i], $categories)) {
					$is_category = true;
					break;
				}
			}
			return $is_category;
		});
		$setting['parameters'] = array_values($parameters);
		$params_copy = $plugin['parameters'];
		foreach ($params_copy as $key => $val) {
			$found = false;
			for ($i = 0; $i < count($setting['parameters']); $i++) {
				if ($setting['parameters'][$i]['name'] == $key && $setting['parameters'][$i]['default'] !== $val) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				unset($plugin['parameters'][$key]);
			}
		}
		$plugin_info = [
			'plugin' => $plugin,
			'setting' => $setting
		];
		$this->Session->open();
		$this->Session->add('plugin_info', $plugin_info);
	}

	/**
	 * Save data from the plugin settings form.
	 */
	public function savePluginSettingsForm()
	{
		$fields = $this->Request->contains('restore_wizard_plugin_fields') ? json_decode($this->Request['restore_wizard_plugin_fields'], true) : [];
		if (!is_array($fields)) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Wrong plugin setting to save."
			);
			return false;
		}
		$name = $this->Session['plugin_info']['plugin']['name'] ?? '';
		if (!$name) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Plugin setting name is not set."
			);
			return false;
		}
		$plugin_config = $this->getModule('plugin_config');
		$settings = $plugin_config->getConfig($name);
		if (count($settings) == 0) {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Plugin setting does not exist."
			);
			return false;
		}
		$settings['parameters'] = array_merge($settings['parameters'], $fields);
		$result = $plugin_config->setPluginSettings($name, $settings);
		if ($result) {
			$this->loadPluginSettings();
		} else {
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				"Error while saving plugin setting."
			);
		}
	}

	/**
	 * Load volumes required to do restore.
	 */
	private function loadRequiredVolumes(): void
	{
		$volumes = [];
		$api = $this->getModule('api');
		foreach ($this->getFilesToRestore() as $uniqid => $props) {
			[$jobid, $pathid, $fileid] = explode(':', $uniqid, 3);
			if ($jobid === '0') {
				/**
				 * No way to determine proper jobid for elements.
				 * jobid=0 usually means that path is part of FileSet File value
				 * for example: selected path "/home" where File = "/home/gani/bbb".
				 */
				continue;
			}
			// it can be expensive for many restore paths
			$result = $api->get(
				['volumes', 'required', $jobid, $fileid]
			);
			if ($result->error === 0) {
				for ($i = 0; $i < count($result->output); $i++) {
					$volumes[$result->output[$i]->volume] = [
						'volume' => $result->output[$i]->volume,
						'inchanger' => $result->output[$i]->inchanger
					];
				}
			}
		}
		$this->RestoreVolumes->DataSource = array_values($volumes);
		$this->RestoreVolumes->dataBind();
	}

	/**
	 * Reset wizard.
	 * All fields are back to initial form.
	 */
	private function resetWizard(): void
	{
		$this->Session->open();
		$this->setFileVersions();
		$this->setFilesToRestore();
		$this->Session->remove('backup_jobids');
		$this->Session->remove('files_versions');
		$this->Session->remove('files_restore');
		$this->loadRestoreJobs();
		$this->setWherePath(null, null);
		$this->Session->remove('restore_path');
		$this->Session->remove('restore_pathid');
		$this->Session->remove('restore_job');
		$this->Session->remove('file_relocation');
		$this->Session->add('plugin_info', []);
	}
}
