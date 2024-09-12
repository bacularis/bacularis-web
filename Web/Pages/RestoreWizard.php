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
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TCallback;
use Bacularis\Common\Modules\AuditLog;

/**
 * Restore wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class RestoreWizard extends BaculumWebPage
{
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
		$this->loadBackupClients();
		if ($this->Request->contains('jobid')) {
			$this->setJobIdToRestore($this->Request['jobid']);
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
	 * Set jobid to restore.
	 * Used to restore specific job by jobid.
	 *
	 * @param mixed $jobid
	 */
	public function setJobIdToRestore($jobid)
	{
		$jobid = (int) $jobid;
		$this->setRestoreByJobId($jobid);
		$this->RestoreWizard->setActiveStep($this->Step3);
		$param = new stdClass();
		$param->CurrentStepIndex = 1;
		$this->RestoreWizard->raiseEvent('OnNextButtonClick', null, $param);
	}

	/**
	 * Set/prepare restore wizard to restore specific jobid.
	 *
	 * @param mixed $jobid
	 */
	public function setRestoreByJobId($jobid)
	{
		$job = $this->getModule('api')->get(
			['jobs', $jobid]
		)->output;
		if (is_object($job)) {
			$this->setRestoreJob($jobid, $job->name, $job->type, $job->endtime, $job->jobstatus);
			$this->loadRestoreClients();
			$this->BackupClient->SelectedValue = $job->clientid;
			$this->RestoreClient->SelectedValue = $job->clientid;
			$this->loadBackupsForClient();
			$step_index = new stdClass();
			$step_index->CurrentStepIndex = 3;
			$this->wizardNext(null, $step_index);
		}
	}

	private function setRestoreJob($jobid, $name, $type, $endtime, $jobstatus)
	{
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
	 *
	 */
	public function setNavigationButtons()
	{
		$prev_btn = $this->RestoreWizard->getStepNavigation()->PreviousStepBtn;
		if ($this->Request->contains('jobid') && $this->RestoreWizard->getActiveStepIndex() === 2) {
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
			$this->loadRestoreClients();
			if ($this->BackupClient->DataChanged) {
				// remove previous restore jobid only if user changed client selection
				$this->Session->remove('restore_job');
			}
		} elseif ($param->CurrentStepIndex === 1) {
			if ($this->Request->contains('backup_to_restore')) {
				[$jobid, $name, $type, $endtime, $jobstatus] = explode('|', $this->Request['backup_to_restore'], 5);
				$this->setRestoreJob($jobid, $name, $type, $endtime, $jobstatus);
			}
			$this->setRestorePath();
			$this->setFileVersions();
			$this->loadSelectedFiles(null, null);
			$this->loadFileVersions(null, null);
			$this->goToPath();
		} elseif ($param->CurrentStepIndex === 2) {
			$this->loadRequiredVolumes();
			if ($this->Session->contains('file_relocation')) {
				$this->file_relocation_opt = $this->Session['file_relocation'];
			}
		} elseif ($param->CurrentStepIndex === 3) {
			if ($this->Request->contains('file_relocation')) {
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
		$this->resetWizard();
		$this->goToDefaultPage();
	}

	/**
	 * Load backup clients list (step 1).
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCommandParameter $param parameters object
	 */
	public function loadBackupClients()
	{
		$client_list = [];
		$clients = $this->getModule('api')->get(
			['clients']
		)->output;
		if (is_array($clients)) {
			for ($i = 0; $i < count($clients); $i++) {
				$client_list[$clients[$i]->clientid] = $clients[$i]->name;
			}
			asort($client_list);
		}
		$this->BackupClient->DataSource = $client_list;
		$this->BackupClient->dataBind();
	}

	/**
	 * Load restore client list.
	 *
	 */
	public function loadRestoreClients()
	{
		$client_list = [];
		$clients = $this->getModule('api')->get(
			['clients']
		)->output;
		if (is_array($clients)) {
			for ($i = 0; $i < count($clients); $i++) {
				$client_list[$clients[$i]->clientid] = $clients[$i]->name;
			}
			asort($client_list);
		}
		$this->RestoreClient->DataSource = $client_list;
		$this->RestoreClient->SelectedValue = $this->BackupClient->SelectedValue;
		$this->RestoreClient->dataBind();
	}

	/**
	 * Load backups for selected client (Step 2).
	 *
	 */
	public function loadBackupsForClient()
	{
		$clientid = $this->BackupClient->SelectedValue;
		$jobs_for_client = $this->getModule('api')->get(
			['clients', $clientid, 'jobs']
		)->output;
		$jobs = $this->getModule('misc')->objectToArray($jobs_for_client);
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
	private function loadBackupsByFilename($filename, $strict, $path)
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
		$result = $this->getModule('api')->get($params);
		$ret = [];
		if ($result->error == 0) {
			$jobs = $this->getModule('misc')->objectToArray($result->output);
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
	public function loadJobList($sender, $param)
	{
		$prop = $param->getCallbackParameter();
		$jobs = [];
		$list_type = self::JOB_LIST_BY_CLIENT;
		if (is_object($prop) && !empty($prop->filename)) {
			$list_type = self::JOB_LIST_BY_FILENAME;
			$jobs = $this->loadBackupsByFilename($prop->filename, $prop->strict, $prop->path);
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
	 * @return true if job should be listed to restore, otherwise false
	 */
	private function isJobToRestore($job)
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

	public function loadBackupSelection($sender, $param)
	{
		$this->GroupBackupToRestoreField->Display = ($sender->ID == $this->GroupBackupSelection->ID) ? 'Dynamic' : 'None';
		$this->BackupToRestoreField->Display = ($sender->ID == $this->OnlySelectedBackupSelection->ID) ? 'Dynamic' : 'None';
		$this->setBrowserFiles();
		$this->setFileVersions();
		$this->setFilesToRestore();
		$this->markFileToRestore(null, null);
		$this->setRestorePath();
	}

	/**
	 * Set selected backup client.
	 *
	 * @param int $clientid client identifier
	 */
	public function getBackupClient($clientid)
	{
		$client = null;
		$clients = $this->getModule('api')->get(['clients'])->output;
		for ($i = 0; $i < count($clients); $i++) {
			if ($clients[$i]->clientid === $clientid) {
				$client = $clients[$i]->name;
				break;
			}
		}
		return $client;
	}

	/**
	 * Load backup jobs to restore for group most recent backups feature.
	 *
	 */
	public function loadGroupBackupToRestore()
	{
		$jobs = $this->getModule('api')->get(['jobs']);
		$job_group = ['' => ''];
		if ($jobs->error === 0) {
			$jobs = $this->getModule('misc')->objectToArray($jobs->output);
			$clientid = (int) ($this->BackupClient->SelectedValue);
			for ($i = 0; $i < count($jobs); $i++) {
				if ($this->isJobToRestore($jobs[$i]) && $jobs[$i]['clientid'] === $clientid) {
					$job_group[$jobs[$i]['name']] = $jobs[$i]['name'];
				}
			}
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
	public function loadGroupBackupFileSets($sender, $param)
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
		$filesets = $this->getModule('api')->get([
			'filesets',
			$query
		]);
		$fileset_group = ['' => ''];
		if ($filesets->error === 0) {
			for ($i = 0; $i < count($filesets->output); $i++) {
				$fileset_group[$filesets->output[$i]->filesetid] = $filesets->output[$i]->fileset . ' (' . $filesets->output[$i]->createtime . ')';
			}
		}
		asort($fileset_group);

		$this->GroupBackupFileSet->DataSource = $fileset_group;
		$this->GroupBackupFileSet->dataBind();
	}

	/**
	 * Load filesets to restore for group most recent backups feature.
	 *
	 */
	public function loadGroupFileSetToRestore()
	{
		$filesets = $this->getModule('api')->get(['filesets'])->output;
		$fileset_group = [];
		for ($i = 0; $i < count($filesets); $i++) {
			$fileset_group[$filesets[$i]->filesetid] = $filesets[$i]->fileset . ' (' . $filesets[$i]->createtime . ')';
		}
		asort($fileset_group);

		$this->GroupBackupFileSet->DataSource = $fileset_group;
		$this->GroupBackupFileSet->dataBind();
	}

	/**
	 * Prepare left file browser content.
	 *
	 */
	private function prepareBrowserContent()
	{
		$jobids = $this->getElementaryBackup();
		$elements = [];
		if (!empty($jobids)) {
			// generating Bvfs may take a moment
			$this->generateBvfsCache($jobids);

			// get directory and file list
			$q = [
				'jobids' => $jobids,
				'output' => 'json'
			];
			if ($this->FileBrowserTypeFlat->Checked) {
				$offset = (int) ($this->RestoreBrowserOffset->Text);
				$limit = (int) ($this->RestoreBrowserLimit->Text);
				$q['offset'] = $offset;
				$q['limit'] = $limit;
			}
			if ($this->Session->contains('restore_pathid')) {
				$q['pathid'] = $this->Session['restore_pathid'];
			} else {
				$q['path'] = $this->FileBrowserTypeFlat->Checked ? implode($this->Session['restore_path']) : '';
			}
			$query = '?' . http_build_query($q);
			$bvfs_dirs = $this->getModule('api')->get(
				['bvfs', 'lsdirs', $query]
			);
			$dirs = [];
			if ($bvfs_dirs->error === 0) {
				$dirs = json_decode(json_encode($bvfs_dirs->output), true);
			}
			$dir_count = count($dirs);
			if ($dir_count == 1 && ($dirs[0]['name'] == '/' || preg_match('/^[A-Z]+:\/$/i', $dirs[0]['name']) === 1)) {
				$this->RestoreBrowserDirCount->Text = $dir_count;
			} elseif ($dir_count == 0) {
				$this->RestoreBrowserDirCount->Text = 0;
			} else {
				$this->RestoreBrowserDirCount->Text = ($dir_count - 1);
			}

			if ($this->Session->contains('restore_job')) {
				$this->RestoreBrowserClient->Text = $this->BackupClient->SelectedItem->Text;
				$this->RestoreBrowserName->Text = $this->Session['restore_job']['name'];
				$this->RestoreBrowserType->Text = $this->Session['restore_job']['type'];
				$this->RestoreBrowserStatus->Text = $this->Session['restore_job']['jobstatus'];
				$this->RestoreBrowserTimePoint->Text = $this->Session['restore_job']['endtime'];
			}

			// get files list
			$bvfs_files = $this->getModule('api')->get(
				['bvfs', 'lsfiles', $query]
			);
			$files = [];
			if ($bvfs_files->error === 0) {
				$files = json_decode(json_encode($bvfs_files->output), true);
			}
			$this->RestoreBrowserFileCount->Text = count($files);

			$elements = array_merge($dirs, $files);
			$elements = $this->addExtraPropsToElements($elements);
			if (count($this->Session['restore_path']) > 0) {
				array_unshift($elements, $this->browser_root_dir);
			}
			if ($this->Session->contains('restore_pathid')) {
				// clear pathid in session as it is used only for browser element request time.
				$this->Session->remove('restore_pathid');
			}
		}
		if (count($elements) > 0) {
			$this->NoFileFound->Display = 'None';
		} elseif ($this->Session->contains('restore_job')) {
			$this->NoFileFound->Display = 'Dynamic';
		}
		if ($this->FileBrowserTypeFlat->Checked) {
			$this->setBrowserFiles($elements);
		}
		$this->loadBrowserFiles(null, $elements);
	}

	private function addExtraPropsToElements($elements)
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
	private function getElementaryBackup()
	{
		$jobids = '';
		if ($this->OnlySelectedBackupSelection->Checked && $this->Session->contains('restore_job')) {
			$params = [
				'jobid' => $this->Session['restore_job']['jobid']
			];
			$query = '?' . http_build_query($params);
			$jobs = $this->getModule('api')->get(
				['bvfs', 'getjobids', $query]
			);
			$ids = is_object($jobs) ? $jobs->output : [];
			foreach ($ids as $jobid) {
				if (preg_match('/^([\d\,]+)$/', $jobid, $match) == 1) {
					$jobids = $match[1];
					break;
				}
			}
			if (empty($jobids)) {
				$jobids = $this->Session['restore_job']['jobid'];
			}
		} else {
			$params = [
				'clientid' => $this->BackupClient->SelectedValue,
				'filesetid' => $this->GroupBackupFileSet->SelectedValue
			];
			if ($this->EnableCopyJobRestore->Checked) {
				$params['inc_copy_job'] = 1;
			}
			$query = '?' . http_build_query($params);
			$jobs_recent = $this->getModule('api')->get([
				'jobs',
				'recent',
				$this->GroupBackupToRestore->SelectedValue,
				$query
			]);
			if (count($jobs_recent->output) > 0) {
				$ids = $jobs_recent->output;
				if (count($ids) > 0) {
					$jobid = $ids[0];
					$job = $this->getModule('api')->get(
						['jobs', $jobid]
					)->output;
					if (is_object($job)) {
						$this->setRestoreJob($jobid, $job->name, $job->type, $job->endtime, $job->jobstatus);
					}
				}
				$jobids = implode(',', $ids);
			}
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
	public function loadPath($sender, $param)
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
	private function goToPath($path = '', $full_path = false)
	{
		if (!empty($path) && !$full_path && $this->Session->contains('restore_path')) {
			if ($path == $this->browser_up_dir['name']) {
				$rp = $this->Session['restore_path'];
				array_pop($rp);
				$this->Session->add('restore_path', $rp);
			} elseif ($path == $this->browser_root_dir['name']) {
				$this->setRestorePath();
			} else {
				$rp = $this->Session['restore_path'];
				array_push($rp, $path);
				$this->Session->add('restore_path', $rp);
			}
		}
		if ($full_path && is_array($path)) {
			$this->setRestorePath($path);
		}
		$this->loadBrowserPath();
		$this->prepareBrowserContent();
	}

	/**
	 * Go to specific path in the file browser by pathid.
	 *
	 * @param string $pathid path to go
	 */
	private function goToPathByPathId($pathid)
	{
		$this->setRestorePathId($pathid);
		$this->loadBrowserPath();
		$this->prepareBrowserContent();
	}

	/**
	 * Add/mark file to restore.
	 * Used as callback to drag&drop browser elements.
	 *
	 * @param object $sender sender object
	 * @param object $param param object
	 */
	public function addFileToRestore($sender, $param)
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
	public function getVersions($sender, $param)
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
				$this->goToPathByPathId($pathid); // to go by pathid
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
		$versions = $this->getModule('api')->get(
			['bvfs', 'versions', $query]
		)->output;
		$versions = json_decode(json_encode($versions), true);
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
	public function loadBrowserFiles($sender, $param)
	{
		$files = [];
		if (is_array($param)) {
			$files = $param;
		} else {
			$files = $this->Session->contains('files_browser') ? $this->Session['files_browser'] : [];
		}
		$this->getCallbackClient()->callClientFunction('oRestoreBrowserFiles.populate', [$files]);
	}

	/**
	 * Load file versions area.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function loadFileVersions($sender, $param)
	{
		$versions = $this->Session->contains('files_versions') ? $this->Session['files_versions'] : [];
		$this->getCallbackClient()->callClientFunction('oRestoreBrowserVersions.populate', [array_values($versions)]);
	}

	/**
	 * Load selected files in drop area.
	 *
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function loadSelectedFiles($sender, $param)
	{
		$files = $this->Session->contains('files_restore') ? $this->Session['files_restore'] : [];
		$this->getCallbackClient()->callClientFunction('oRestoreBrowserSelectedFiles.populate', [array_values($files)]);
	}

	/**
	 * Set file browser path field.
	 *
	 */
	private function loadBrowserPath()
	{
		if ($this->FileBrowserTypeFlat->Checked) {
			// browser path field is used only for flat browser, not for tree browser
			$path = $this->Session->contains('restore_path') ? $this->Session['restore_path'] : [];
			$this->PathField->Text = implode($path);
		}
	}

	/**
	 * Generate Bvfs cache by job identifiers.
	 *
	 * @param string $jobids comma separated job identifiers
	 */
	private function generateBvfsCache($jobids)
	{
		$this->getModule('api')->set(
			['bvfs', 'update'],
			['jobids' => $jobids]
		);
	}

	/**
	 * Set versions for selected file.
	 *
	 * @param array $versions file versions data
	 */
	private function setFileVersions($versions = [])
	{
		$this->Session->add('files_versions', $versions);
	}

	/**
	 * Set browser files.
	 *
	 * @param array $files file list
	 */
	private function setBrowserFiles($files = [])
	{
		if ($this->FileBrowserTypeFlat->Checked) {
			$this->Session->add('files_browser', $files);
		}
	}

	/**
	 * Set restore browser path.
	 *
	 * @param array $path path
	 */
	private function setRestorePath($path = [])
	{
		$this->Session->add('restore_path', $path);
	}

	/**
	 * Set restore browser pathid.
	 *
	 * @param int $pathid pathid
	 */
	private function setRestorePathId($pathid)
	{
		$this->Session->add('restore_pathid', $pathid);
	}

	/**
	 * Mark file to restore.
	 *
	 * @param string $uniqid file identifier
	 * @param array $file_prop file properties to mark
	 */
	private function markFileToRestore($uniqid, $file_prop)
	{
		if (is_null($uniqid)) {
			$this->setFilesToRestore();
		} elseif ($file_prop['name'] != $this->browser_root_dir['name'] && $file_prop['name'] != $this->browser_up_dir['name']) {
			$fr = $this->Session['files_restore'];
			$fr[$uniqid] = $file_prop;
			$this->Session->add('files_restore', $fr);
		}
	}

	/**
	 * Unmark file to restore.
	 *
	 * @param string $uniqid file identifier
	 */
	private function unmarkFileToRestore($uniqid)
	{
		if (key_exists($uniqid, $this->Session['files_restore'])) {
			$fr = $this->Session['files_restore'];
			unset($fr[$uniqid]);
			$this->Session['files_restore'] = $fr;
		}
	}

	/**
	 * Get files to restore.
	 *
	 * @return array list with files to restore
	 */
	public function getFilesToRestore()
	{
		return ($this->Session->contains('files_restore') ? $this->Session['files_restore'] : []);
	}

	/**
	 * Set files to restore
	 *
	 * @param array $files files to restore
	 */
	public function setFilesToRestore($files = [])
	{
		$this->Session->add('files_restore', $files);
	}

	/**
	 * Get all restore elements (fileids and dirids).
	 *
	 * @param bool $as_object return result as object
	 * @return array list fileids and dirids
	 */
	public function getRestoreElements($as_object = false)
	{
		$fileids = [];
		$dirids = [];
		$findexes = [];
		foreach ($this->getFilesToRestore() as $uniqid => $properties) {
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
		if ($as_object === true) {
			$ret = (object) $ret;
		}
		return $ret;
	}

	/**
	 * Wizard finish method.
	 *
	 */
	public function wizardCompleted()
	{
		$jobids = $this->getElementaryBackup();
		$path = self::BVFS_PATH_PREFIX . getmypid();
		$restore_elements = $this->getRestoreElements();
		$cmd_props = ['jobids' => $jobids, 'path' => $path];
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
		$ret = new stdClass();
		$restore_props = [];
		$restore_props['client'] = $this->RestoreClient->SelectedItem->Text;
		if ($_SESSION['file_relocation'] == 2) {
			if (!empty($this->RestoreStripPrefix->Text)) {
				$restore_props['strip_prefix'] = $this->RestoreStripPrefix->Text;
			}
			if (!empty($this->RestoreAddPrefix->Text)) {
				$restore_props['add_prefix'] = $this->RestoreAddPrefix->Text;
			}
			if (!empty($this->RestoreAddSuffix->Text)) {
				$restore_props['add_suffix'] = $this->RestoreAddSuffix->Text;
			}
		} elseif ($_SESSION['file_relocation'] == 3) {
			if (!empty($this->RestoreRegexWhere->Text)) {
				$restore_props['regex_where'] = $this->RestoreRegexWhere->Text;
			}
		}
		if (!key_exists('add_prefix', $restore_props)) {
			$restore_props['where'] = $this->RestorePath->Text;
		}
		$restore_props['replace'] = $this->ReplaceFiles->SelectedValue;
		$restore_props['restorejob'] = $this->RestoreJob->SelectedValue;
		if ($is_element) {
			$this->getModule('api')->create(
				['bvfs', 'restore'],
				$cmd_props
			);
			$restore_props['rpath'] = $path;

			$ret = $this->getModule('api')->create(
				['jobs', 'restore'],
				$restore_props
			);
			$jobid = $this->getModule('misc')->findJobIdStartedJob($ret->output);
			// Remove temporary BVFS table
			$this->getModule('api')->set(['bvfs', 'cleanup'], ['path' => $path]);
		} elseif (count($this->Session['files_browser']) === 0 && $this->Session->contains('restore_job')) {
			$restore_props['full'] = 1;
			$restore_props['id'] = $this->Session['restore_job']['jobid'];
			$job = $this->getModule('api')->get(
				['jobs', $this->Session['restore_job']['jobid']]
			)->output;
			if (is_object($job)) {
				$restore_props['fileset'] = $job->fileset;
			}
			$ret = $this->getModule('api')->create(
				['jobs', 'restore'],
				$restore_props
			);
			$jobid = $this->getModule('misc')->findJobIdStartedJob($ret->output);
		} else {
			$ret->output = ['No file to restore found'];
		}
		$url_params = [];
		if (is_numeric($jobid)) {
			$this->resetWizard();
			$url_params['jobid'] = $jobid;
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Run restore. Job: {$restore_props['restorejob']}, JobId: $jobid"
			);
			$this->goToPage('JobView', $url_params);
		} else {
			$this->RestoreError->Text = implode('<br />', $ret->output);
			$this->show_error = true;
			$this->getModule('audit')->audit(
				AuditLog::TYPE_WARNING,
				AuditLog::CATEGORY_ACTION,
				"Run restore failed. Job: {$restore_props['restorejob']}"
			);
		}
	}

	/**
	 * Load restore jobs on the list.
	 *
	 */
	private function loadRestoreJobs()
	{
		$restore_job_tasks = $this->getModule('api')->get(
			['jobs', 'resnames', '?type=R']
		)->output;
		$jobs = [];
		foreach ($restore_job_tasks as $director => $restore_jobs) {
			$jobs = array_merge($jobs, $restore_jobs);
		}
		$this->RestoreJob->DataSource = array_combine($jobs, $jobs);
		if (count($jobs) > 0) {
			$this->RestoreJob->SelectedValue = $jobs[0];
		}
		$this->RestoreJob->dataBind();
	}

	public function setWherePath($sender, $param)
	{
		$restore_job = $this->RestoreJob->SelectedValue;
		if (empty($restore_job)) {
			return;
		}
		$params = [
			'name' => $restore_job,
			'output' => 'json'
		];
		$query = '?' . http_build_query($params);
		$result = $this->getModule('api')->get(
			['jobs', 'show', $query]
		);
		$where = '/tmp/restore';
		if ($result->error == 0 && isset($result->output->where)) {
			$where = $result->output->where;
		}
		$this->RestorePath->Text = $where;
	}

	private function loadRequiredVolumes()
	{
		$volumes = [];
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
			$result = $this->getModule('api')->get(
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
	 *
	 */
	private function resetWizard()
	{
		$this->setBrowserFiles();
		$this->setFileVersions();
		$this->setFilesToRestore();
		$this->Session->remove('files_browser');
		$this->Session->remove('files_versions');
		$this->Session->remove('files_restore');
		$this->loadRestoreJobs();
		$this->setWherePath(null, null);
		$this->Session->remove('restore_path');
		$this->Session->remove('restore_pathid');
		$this->Session->remove('restore_job');
		$this->Session->remove('file_relocation');
	}
}
