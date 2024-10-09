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
use Prado\Prado;

/**
 * New file storage wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewFileStorageWizard extends BaculumWebPage
{
	public $storage_created;
	public $storage_address;
	public $storage_create_errors;
	public $storage_running_jobs_no = 0;

	private const STORAGE_TYPE_SINGLE = 'Single';
	private const STORAGE_TYPE_AUTOCHANGER = 'Autochanger';

	private const DEF_DEVICE_DIRECTIVES = [
		'DeviceType' => 'File',
		'RemovableMedia' => false,
		'AutomaticMount' => true,
		'LabelMedia' => true,
		'MaximumConcurrentJobs' => 5,
		'RandomAccess' => true
	];

	private const DEF_AUTOCHANGER_DIRECTIVES = [
		'ChangerCommand' => '/dev/null',
		'ChangerDevice' => '/dev/null'
	];

	private const DEF_SINGLE_STORAGE_DIRECTIVES = [
		'MaximumConcurrentJobs' => 5
	];

	private const DEF_AUTOCHANGER_STORAGE_DIRECTIVES = [
		'MaximumConcurrentJobs' => 50
	];

	public const PREV_STEP = 'PrevStep';

	public function wizardStop($sender, $param)
	{
		$this->goToPage('StorageList');
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->IsCallBack) {
			return;
		}
		$step_index = $this->NewStorageWizard->getActiveStepIndex();
		$prev_step = $this->getPrevStep();
		$this->setPrevStep($step_index);
		if ($prev_step > $step_index) {
			return;
		}
		switch ($step_index) {
			case 0:	{
				$this->loadAPIHosts();
				break;
			}
		}
	}

	/**
	 * Load API hosts (step 1).
	 *
	 */
	public function loadAPIHosts()
	{
		$api_hosts = $this->User->getAPIHosts();
		array_unshift($api_hosts, '');

		$this->StorageAPIHost->DataSource = array_combine($api_hosts, $api_hosts);
		if (count($api_hosts) === 2) {
			$this->StorageAPIHost->setSelectedValue($api_hosts[1]);
			$this->APIHostContainer->Display = 'None';
		} else {
			$this->APIHostContainer->Display = 'Dynamic';
		}
		$this->StorageAPIHost->dataBind();
	}

	public function wizardCompleted($sender, $param)
	{
		$storage_name = $this->StorageName->getDirectiveValue();
		$storage_desc = $this->StorageDescription->getDirectiveValue() ?? '';
		$api_host = $this->StorageAPIHost->getSelectedValue();
		$api_hosts = $this->User->getAPIHosts();
		$storage_type = null;
		$backup_restore_dev_no = 0;
		$restore_dev_no = 0;
		if ($this->StorageTypeSingle->Checked) {
			$storage_type = self::STORAGE_TYPE_SINGLE;
			$backup_restore_dev_no = 1;
		} elseif ($this->StorageTypeAutochanger->Checked) {
			$storage_type = self::STORAGE_TYPE_AUTOCHANGER;
			$backup_restore_dev_no = (int) $this->StorageNumberOfBackupRestoreDevices->Text;
			$restore_dev_no = (int) $this->StorageNumberOfRestoreDevices->Text;
		}
		$data_vol_dir = $this->StorageDataVolumeDir->Text;
		$media_type = $this->StorageMediaType->Text;

		if (!in_array($api_host, $api_hosts)) {
			// unauthorized API host, return...
			return false;
		}

		$storage_tools = $this->getModule('storage_tools');

		// Get SD password
		$sd_password = $storage_tools->getSdPassword($api_host, $_SESSION['dir']);

		// Get SD port
		$sd_port = $storage_tools->getSdPort($api_host);

		$host_config = $this->getModule('host_config');
		$host_params = $host_config->getHostConfig($api_host);
		$sd_address = $this->storage_address = $host_params['address'] ?? '';
		if ($sd_address) {
			$storage_id = $storage_tools->getStorageIdByAddress($sd_address);
			if ($storage_id > 0) {
				$this->storage_running_jobs_no = $storage_tools->getRunningJobNumber($storage_id);
			}
		}

		$output = '';
		$error = 0;
		$errors = [];
		$devices = [];
		$total_dev_no = $backup_restore_dev_no + $restore_dev_no;
		for ($i = 0; $i < $total_dev_no; $i++) {
			$name = $storage_name;
			if ($backup_restore_dev_no > 1) {
				$name = sprintf(
					'%s-%03d',
					$storage_name,
					($i + 1)
				);
			}
			$ro = (($i + 1) > $backup_restore_dev_no);
			$autochanger = ($storage_type == self::STORAGE_TYPE_AUTOCHANGER);
			$dev_directives = [
				'Name' => $name,
				'Description' => $storage_desc,
				'DriveIndex' => $i,
				'ArchiveDevice' => $data_vol_dir,
				'MediaType' => $media_type,
				'ReadOnly' => $ro,
				'Autochanger' => $autochanger
			];
			$result = $storage_tools->createDevice(
				$api_host,
				$dev_directives,
				self::DEF_DEVICE_DIRECTIVES
			);
			$output = $result->output;
			$error = $result->error;
			if ($error !== 0) {
				$errors[] = "Error: {$error}: $output";
				break;
			}
			$devices[] = $name;
		}
		if ($error == 0) {
			$autochanger = false;
			if ($storage_type == self::STORAGE_TYPE_AUTOCHANGER) {
				// create autochanger
				$autochanger = true;
				$ach_directives = [
					'Name' => $storage_name,
					'Device' => $devices
				];
				$result = $storage_tools->createAutochanger(
					$api_host,
					$ach_directives,
					self::DEF_AUTOCHANGER_DIRECTIVES
				);
				$output = $result->output;
				$error = $result->error;
			}
			if ($error == 0) {
				// create storage
				$stor_directives = [
					'Name' => $storage_name,
					'Description' => $storage_desc,
					'Address' => $sd_address,
					'SdPort' => $sd_port,
					'Password' => $sd_password,
					'Device' => $storage_name,
					'MediaType' => $media_type
				];
				$def_directives = [];
				if ($autochanger) {
					$stor_directives['Autochanger'] = $autochanger;
					$def_directives = self::DEF_AUTOCHANGER_STORAGE_DIRECTIVES;
				} else {
					$def_directives = self::DEF_SINGLE_STORAGE_DIRECTIVES;
				}
				$result = $storage_tools->createStorage($stor_directives, $def_directives);
				$output = $result->output;
				$error = $result->error;
				if ($error != 0) {
					$output = str_replace([PHP_EOL, '"'], ['<br />', '\"'], $output);
					$errors[] = "Error: {$error}: $output";
				}
			} else {
				$output = str_replace([PHP_EOL, '"'], ['<br />', '\"'], $output);
				$errors[] = "Error: {$error}: $output";
			}
		}
		if ($error == 0) {
			$api = $this->getModule('api');
			$api->set(['console'], ['reload']);
			$this->storage_created = true;
		} else {
			$this->storage_created = false;
			$this->storage_create_errors = implode('<br /><br />', $errors);
		}
	}

	public function restartStorage($sender, $param)
	{
		$api_host = $this->StorageAPIHost->getSelectedValue();
		$api_hosts = $this->User->getAPIHosts();
		if (!in_array($api_host, $api_hosts)) {
			// unauthorized API host, return...
			return false;
		}
		$api = $this->getModule('api');
		$params = [
			'actions',
			'storage',
			'restart'
		];
		$result = $api->get(
			$params,
			$api_host
		);
		$cb = $this->getCallbackClient();
		if ($result->error === 0) {
			$cb->callClientFunction(
				'oNewFileStorageStep3.set_restart_result',
				[true, $result->error, Prado::localize('Done!')]
			);
		} else {
			$cb->callClientFunction(
				'oNewFileStorageStep3.set_restart_result',
				[false, $result->error, $result->output]
			);
		}
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
