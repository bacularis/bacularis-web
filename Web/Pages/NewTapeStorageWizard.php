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
 * New tape storage wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewTapeStorageWizard extends BaculumWebPage
{
	public $storage_created;
	public $storage_address;
	public $storage_create_errors;
	public $storage_running_jobs_no = 0;

	private const TAPE_DRIVES = 'TapeDrives';

	public const TYPE_TAPE_DRIVE_BACKUP_RESTORE = 'BR';
	public const TYPE_TAPE_DRIVE_RESTORE_ONLY = 'RO';

	private const STORAGE_TYPE_SINGLE = 'Single';
	private const STORAGE_TYPE_AUTOCHANGER = 'Autochanger';

	private const TYPE_TAPE_DEVICE = 'Tape';

	private const DEF_DEVICE_DIRECTIVES = [
		'DeviceType' => 'Tape',
		'RemovableMedia' => true,
		'AutomaticMount' => true,
		'MaximumConcurrentJobs' => 1
	];

	private const DEF_AUTOCHANGER_DIRECTIVES = [
	];

	private const DEF_SINGLE_STORAGE_DIRECTIVES = [
		'MaximumConcurrentJobs' => 1
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

		if (!$this->IsPostBack && !$this->IsCallBack) {
			// First step. Init wizard fields.
			$this->loadAPIHosts();
			return;
		}
		$step_index = $this->NewStorageWizard->getActiveStepIndex();
		$prev_step = $this->getPrevStep();
		$this->setPrevStep($step_index);
		if ($prev_step > $step_index) {
			switch ($step_index) {
				case 1: {
					$tds = $this->getRepaterTapeDrives();
					$this->setTapeDrives($tds);
					$this->loadTapeDrives();
					break;
				}
			}
		} elseif ($prev_step < $step_index) {
			switch ($step_index) {
				case 0:	{
					$this->loadAPIHosts();
					break;
				}
				case 1: {
					$this->setExistingAutochangerFields();
					$this->loadTapeDrives();
					break;
				}
				case 2: {
					$this->showSummary();
					break;
				}
			}
		} else {
			switch ($step_index) {
				case 2: {
					$this->showSummary();
					break;
				}
			}
		}
	}

	/**
	 * Load API hosts (step 1).
	 */
	public function loadAPIHosts(): void
	{
		$api_hosts = $this->User->getAPIHosts();
		array_unshift($api_hosts, '');

		$api_hosts = array_combine($api_hosts, $api_hosts);
		$this->StorageAPIHost->DataSource = $api_hosts;
		$this->StorageAPIHost->dataBind();
	}

	/**
	 * Load tape drives (step 2).
	 */
	public function loadTapeDrives(): void
	{
		$tape_drives = $this->getTapeDrives();
		$this->BackupRestoreDevicesRepeater->DataSource = $tape_drives;
		$this->BackupRestoreDevicesRepeater->dataBind();
	}

	/**
	 * Get single tape drive properities by general properties.
	 * Used both for autochanger drives and single drives.
	 *
	 * @param array $props general drive properties
	 * @return array single drive properties
	 */
	private function getSingleTapeDrive(array $props): array
	{
		$tape_drive = [];
		if (key_exists('file', $props)) {
			$tape_drive['drive_file'] = $props['file'];
		}
		if (key_exists('index', $props)) {
			$tape_drive['drive_index'] = $props['index'];
		}
		if (key_exists('type', $props)) {
			$tape_drive['drive_type'] = $props['type'];
		}
		return $tape_drive;
	}

	/**
	 * Get tape drives.
	 *
	 * @return array tape drives with properties.
	 */
	private function getTapeDrives(): array
	{
		$def_td = $this->getSingleTapeDrive([
			'file' => '/dev/nst0',
			'index' => 0,
			'type' => self::TYPE_TAPE_DRIVE_BACKUP_RESTORE
		]);
		return $this->getViewState(self::TAPE_DRIVES, [$def_td]);
	}

	/**
	 * Set tape drives.
	 *
	 * @param array $props tape drive properties
	 */
	private function setTapeDrives(array $props): void
	{
		$this->setViewState(self::TAPE_DRIVES, $props);
	}

	/**
	 * Get repeater tape drive properties.
	 *
	 * @return tape drives with properties
	 */
	private function getRepaterTapeDrives(): array
	{
		$tds = [];
		foreach ($this->BackupRestoreDevicesRepeater->Items as $item) {
			$file = $item->findControl('TapeDriveFile')->Text;
			$index = $item->findControl('TapeDriveIndex')->Text;
			$type = $item->findControl('TapeDriveType')->SelectedValue;
			$tds[] = $this->getSingleTapeDrive([
				'file' => $file,
				'index' => $index,
				'type' => $type
			]);
		}
		return $tds;
	}

	/**
	 * Add new tape drive action.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param param object
	 */
	public function addNewTapeDrive($sender, $param): void
	{
		$tape_drives = $this->getRepaterTapeDrives();
		$td = $this->getSingleTapeDrive([
			'file' => '',
			'index' => count($tape_drives),
			'type' => self::TYPE_TAPE_DRIVE_BACKUP_RESTORE
		]);
		$tape_drives[] = $td;
		$this->setTapeDrives($tape_drives);

		$this->loadTapeDrives();
	}

	/**
	 * Remove tape drive action.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param param object
	 */
	public function removeTapeDrive($sender, $param): void
	{
		$param = $param->getCallbackParameter();
		if (isset($param->index)) {
			$tape_drives = $this->getRepaterTapeDrives();
			if (key_exists($param->index, $tape_drives)) {
				array_splice($tape_drives, $param->index, 1);
				$this->setTapeDrives($tape_drives);
				$this->loadTapeDrives();
			}
		}
	}

	/**
	 * Backup restore device data binder.
	 *
	 * @param TRepeater $sender sender object
	 * @param TEventParameter $param param object
	 */
	public function backupRestoreDeviceDataBound($sender, $param)
	{
		$tds = $this->getTapeDrives();
		$item = $param->Item;
		if ($item->ItemType == 'Item' || $item->ItemType == 'AlternatingItem') {
			$item->TapeDriveFile->Text = $tds[$item->ItemIndex]['drive_file'];
			$item->TapeDriveFile->dataBind();
			$item->TapeDriveIndex->Text = $tds[$item->ItemIndex]['drive_index'];
			$item->TapeDriveIndex->dataBind();
			$item->TapeDriveType->SelectedValue = $tds[$item->ItemIndex]['drive_type'];
			$item->TapeDriveType->dataBind();
		}
	}

	/**
	 * Check if selected API host have the SD config capability.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function checkAPIHosts($sender, $param)
	{
		$err_id = 'api_host_storage_error';
		$cb = $this->getCallbackClient();
		$cb->hide($err_id);
		$cb->update('api_host_storage_daemon_name', '-');

		$api_host = $this->StorageAPIHost->getSelectedValue();
		$api_hosts = $this->User->getAPIHosts();
		if (!in_array($api_host, $api_hosts)) {
			// unauthorized API host, return...
			return false;
		}
		$result = $this->getModule('api')->get(
			['config'],
			$api_host
		);
		$sd_state = false;
		$sd_name = '';
		$sd_found = false;
		$sd_error = '';
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if ($result->output[$i]->component_type != 'sd') {
					continue;
				}
				$sd_found = true;
				$sd_name = $result->output[$i]->component_name;
				$sd_state = $result->output[$i]->state;
				$sd_error = $result->output[$i]->error_msg;
				break;
			}

			if ($sd_found === true) {
				if ($sd_state === true) {
					$cb->update(
						'api_host_storage_daemon_name',
						$sd_name
					);

					// Everything is fine. Now load the autochanger list.
					$this->loadTapeAutochangers();
				} else {
					$message = Prado::localize('The storage daemon configuration capability on API host \'%s\' is configured but not work correctly. Please check the bsdjson configuration on that API host. Error: %s.');
					$emsg = sprintf($message, $api_host, $sd_error);
					$cb->update($err_id, $emsg);
					$cb->show($err_id);
				}
			} else {
				$message = Prado::localize('The storage daemon configuration capability is not configured on the API host \'%s\'. Please configure the bsdjson on that API host.');
				$emsg = sprintf($message, $api_host);
				$cb->update($err_id, $emsg);
				$cb->show($err_id);
			}
		} else {
			$emsg = sprintf(
				'Error %d: %s',
				$result->error,
				$result->output
			);
			$cb->update($err_id, $emsg);
			$cb->show($err_id);
		}
	}

	/**
	 * Load tape autochangers.
	 */
	public function loadTapeAutochangers(): void
	{
		$tape_achs = $this->getTapeAutochangers();
		$this->StorageTapeAutochangers->DataSource = array_combine($tape_achs, $tape_achs);
		$this->StorageTapeAutochangers->dataBind();
	}

	/**
	 * Get all tape autochangers from the Bacula configuration.
	 *
	 * @return array tape autochanger names
	 */
	private function getTapeAutochangers(): array
	{
		$api_host = $this->StorageAPIHost->getSelectedValue();
		$api = $this->getModule('api');
		$result = $api->get(
			['config', 'sd', 'Autochanger'],
			$api_host,
			true,
			true
		);
		$tape_achs = [];
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if (!isset($result->output[$i]->Autochanger->Device)) {
					continue;
				}
				$dev_no = count($result->output[$i]->Autochanger->Device);
				$tape_dev_no = 0;
				for ($j = 0; $j < $dev_no; $j++) {
					if (!$this->isTapeDevice($result->output[$i]->Autochanger->Device[$j])) {
						continue;
					}
					$tape_dev_no++;
				}
				if ($dev_no == $tape_dev_no) {
					$tape_achs[] = $result->output[$i]->Autochanger->Name;
				}
			}
		}
		return $tape_achs;
	}

	/**
	 * Check if given device is a tape device.
	 *
	 * @param string $device device name
	 * @return bool true if device is tape type, otherwise false
	 */
	private function isTapeDevice(string $device): bool
	{
		$is_tape_dev = false;
		$api_host = $this->StorageAPIHost->getSelectedValue();
		$api = $this->getModule('api');
		$result = $api->get(
			['config', 'sd', 'Device'],
			$api_host,
			true,
			true
		);
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if (!isset($result->output[$i]->Device->Name) || !isset($result->output[$i]->Device->DeviceType)) {
					continue;
				}
				if ($result->output[$i]->Device->Name == $device && $result->output[$i]->Device->DeviceType == self::TYPE_TAPE_DEVICE) {
					$is_tape_dev = true;
					break;
				}
			}
		}
		return $is_tape_dev;
	}

	/**
	 * New storage name validation method.
	 *
	 * @param TRequiredFieldValidator $sender sender object
	 * @param TEventParameter $param param object
	 */
	public function checkNewStorageValidation($sender, $param): void
	{
		$sender->Enabled = $this->StorageNotInBacula->Checked;
	}

	/**
	 * Existing storage name validator.
	 *
	 * @param TRequiredFieldValidator $sender sender object
	 * @param TEventParameter $param param object
	 */
	public function storageConfiguredValidator($sender, $param): bool
	{
		return ($this->StorageInBacula->Checked || $this->StorageNotInBacula->Checked);
	}

	/**
	 * Show summary with all properties (Step 3).
	 */
	private function showSummary(): void
	{
		$tds = $this->getRepaterTapeDrives();
		$this->setTapeDrives($tds);
		$this->loadTapeDrivesSummary();
	}

	/**
	 * Set fields for adding autochanger to Bacularis only.
	 */
	private function setExistingAutochangerFields()
	{
		if (!$this->StorageInBacula->Checked) {
			return;
		}
		$autochanger = $this->StorageTapeAutochangers->getSelectedValue();
		if (!$autochanger) {
			return;
		}
		$api_host = $this->StorageAPIHost->getSelectedValue();
		$storage_tools = $this->getModule('storage_tools');
		$ach_config = $storage_tools->getAutochangerConfig($api_host, $autochanger);
		$this->StorageName->Text = $autochanger;
		$this->StorageTypeAutochanger->Checked = true;
		$this->AutochangerFile->Text = $ach_config['ChangerDevice'];
		$this->AutochangerCommand->Text = $ach_config['ChangerCommand'];
		$tds = [];
		$media_type = '';
		for ($i = 0; $i < count($ach_config['Device']); $i++) {
			$dev_config = $storage_tools->getDeviceConfig($api_host, $ach_config['Device'][$i]);
			$type = isset($dev_config['ReadOnly']) && $dev_config['ReadOnly'] ? self::TYPE_TAPE_DRIVE_BACKUP_RESTORE : self::TYPE_TAPE_DRIVE_BACKUP_RESTORE;
			$tds[] = $this->getSingleTapeDrive([
				'file' => $dev_config['ArchiveDevice'],
				'index' => ($dev_config['DriveIndex'] ?? $i),
				'type' => $type
			]);
			$media_type = $dev_config['MediaType'];
		}
		$this->setTapeDrives($tds);
		$this->StorageMediaType->Text = $media_type;
	}

	/**
	 * Load tape drives on summary page.
	 */
	private function loadTapeDrivesSummary(): void
	{
		$this->BackupRestoreDevicesSummaryRepeater->DataSource = $this->getTapeDrives();
		$this->BackupRestoreDevicesSummaryRepeater->dataBind();
	}

	/**
	 * Save all wizard data.
	 *
	 * @param TWizard $sender sender object
	 * @param TEventParameter $param param object
	 */
	public function wizardCompleted($sender, $param)
	{
		$storage_name = $this->StorageName->Text;
		$storage_desc = $this->StorageDescription->Text;
		$api_host = $this->StorageAPIHost->getSelectedValue();
		$api_hosts = $this->User->getAPIHosts();
		$storage_type = null;
		if ($this->StorageTypeSingle->Checked) {
			$storage_type = self::STORAGE_TYPE_SINGLE;
		} elseif ($this->StorageTypeAutochanger->Checked) {
			$storage_type = self::STORAGE_TYPE_AUTOCHANGER;
		}
		$autochanger_file = $this->AutochangerFile->Text;
		$autochanger_command = $this->AutochangerCommand->Text;
		$tape_drive_file = $this->StorageSingleTapeDrive->Text;
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
		$add_to_bacula = $this->StorageNotInBacula->Checked;
		if ($storage_type == self::STORAGE_TYPE_AUTOCHANGER || $this->StorageInBacula->Checked) {
			$tape_drives = $this->getTapeDrives();
			for ($i = 0; $i < count($tape_drives); $i++) {
				$name = sprintf(
					'%s-Dev%02d',
					$storage_name,
					($i + 1)
				);
				$error = 0;
				if ($add_to_bacula) {
					// Add device to Bacula config
					$ro = ($tape_drives[$i]['drive_type'] == self::TYPE_TAPE_DRIVE_RESTORE_ONLY);
					$dev_directives = [
						'Name' => $name,
						'Description' => $storage_desc,
						'DriveIndex' => $tape_drives[$i]['drive_index'],
						'ArchiveDevice' => $tape_drives[$i]['drive_file'],
						'MediaType' => $media_type,
						'ReadOnly' => $ro,
						'Autochanger' => true
					];
					$result = $storage_tools->createDevice(
						$api_host,
						$dev_directives,
						self::DEF_DEVICE_DIRECTIVES
					);
					$output = $result->output;
					$error = $result->error;
				}
				if ($error !== 0) {
					$errors[] = "Error: {$error}: $output";
					break;
				} else {
					$dev_bis = [
						'name' => $name,
						'type' => 'device',
						'device' => $tape_drives[$i]['drive_file'],
						'index' => $tape_drives[$i]['drive_index']
					];
					$result = $storage_tools->addBacularisDevice(
						$api_host,
						$dev_bis
					);
					$output = $result->output;
					$error = $result->error;
					if ($error !== 0) {
						$errors[] = "Error: {$error}: $output";
						break;
					}
				}
				$devices[] = $name;
			}
			if ($error == 0) {
				$autochanger = false;
				if ($storage_type == self::STORAGE_TYPE_AUTOCHANGER || $this->StorageInBacula->Checked) {
					// Add autochanger to Bacula config
					$error = 0;
					if ($add_to_bacula) {
						$autochanger = true;
						$ach_directives = [
							'Name' => $storage_name,
							'ChangerDevice' => $autochanger_file,
							'ChangerCommand' => $autochanger_command,
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
					if ($error === 0) {
						$ach_bis = [
							'name' => $storage_name,
							'type' => 'autochanger',
							'device' => $autochanger_file,
							'command' => $autochanger_command,
							'use_sudo' => 1,
							'drives' => implode(',', $devices)
						];
						$storage_tools->addBacularisDevice(
							$api_host,
							$ach_bis
						);
						$output = $result->output;
						$error = $result->error;
						if ($error !== 0) {
							$errors[] = "Error: {$error}: $output";
						}
					}
				}
				if ($add_to_bacula && $error == 0) {
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
					$stor_directives['Autochanger'] = $autochanger;
					$def_directives = self::DEF_AUTOCHANGER_STORAGE_DIRECTIVES;
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
		} elseif ($storage_type == self::STORAGE_TYPE_SINGLE) {
			$dev_directives = [
				'Name' => $storage_name,
				'Description' => $storage_desc,
				'ArchiveDevice' => $tape_drive_file,
				'MediaType' => $media_type
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
			} else {
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
				$def_directives = self::DEF_SINGLE_STORAGE_DIRECTIVES;
				$result = $storage_tools->createStorage($stor_directives, $def_directives);
				$output = $result->output;
				$error = $result->error;
				if ($error != 0) {
					$output = str_replace([PHP_EOL, '"'], ['<br />', '\"'], $output);
					$errors[] = "Error: {$error}: $output";
				}
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

	/**
	 * Restart storage daemon action.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
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
				'oNewTapeStorageStep3.set_restart_result',
				[true, $result->error, Prado::localize('Done!')]
			);
		} else {
			$cb->callClientFunction(
				'oNewTapeStorageStep3.set_restart_result',
				[false, $result->error, $result->output]
			);
		}
	}

	/**
	 * Set previous wizard step.
	 *
	 * @param int $step previous step number
	 */
	public function setPrevStep(int $step): void
	{
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
