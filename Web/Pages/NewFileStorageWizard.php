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

		// Get SD password
		$api = $this->getModule('api');
		$result = $api->get(
			['config', 'sd', 'Director', $_SESSION['dir']],
			$api_host,
			false
		);
		$sd_password = '';
		if ($result->error == 0) {
			$sd_password = $result->output->Password;
		}

		// Get SD port
		$api = $this->getModule('api');
		$result = $api->get(
			['config', 'sd', 'Storage'],
			$api_host,
			false
		);
		$sd_port = 0;
		if ($result->error == 0) {
			$sd_port = (int) ($result->output[0]->Storage->SdPort ?? 9103);
		}

		$host_config = $this->getModule('host_config');
		$host_params = $host_config->getHostConfig($api_host);
		$sd_address = $this->storage_address = $host_params['address'] ?? '';
		if ($sd_address) {
			$storage_id = $this->getStorageIdByAddress($sd_address);
			if ($storage_id > 0) {
				$this->storage_running_jobs_no = $this->getRunningJobNumber($storage_id);
			}
		}

		$storage = [
			'Name' => $storage_name,
		];
		if ($storage_desc) {
			$storage['Description'] = $storage_desc;
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
			$result = $this->createDevice(
				$api_host,
				$name,
				$storage_desc,
				$data_vol_dir,
				$media_type,
				$ro,
				$autochanger
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
				$result = $this->createAutochanger(
					$api_host,
					$storage_name,
					'',
					$devices
				);
				$output = $result->output;
				$error = $result->error;
			}
			if ($error == 0) {
				// create storage
				$result = $this->createStorage(
					$storage_name,
					$storage_desc,
					$sd_address,
					$sd_port,
					$sd_password,
					$storage_name,
					$media_type,
					$autochanger
				);
				$output = $result->output;
				$error = $result->error;
				if ($error != 0) {
					$errors[] = "Error: {$error}: $output";
				}
			} else {
				$errors[] = "Error: {$error}: $output";
			}
		}
		if ($error == 0) {
			$api->set(['console'], ['reload']);
			$this->storage_created = true;
		} else {
			$this->storage_created = false;
			$this->storage_create_errors = implode('<br /><br />', $errors);
		}
	}

	private function createDevice(string $api_host, string $name, string $desc, string $data_vol_dir, string $media_type, bool $ro = false, bool $autochanger = false): object
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'sd',
			'Device',
			$name
		];
		$config = [
			'Name' => $name,
			'ArchiveDevice' => $data_vol_dir,
			'MediaType' => $media_type
		];
		if ($desc) {
			$config['Description'] = $desc;
		}
		if ($ro) {
			$config['ReadOnly'] = true;
		}
		if ($autochanger) {
			$config['Autochanger'] = true;
		}
		$config = array_merge(self::DEF_DEVICE_DIRECTIVES, $config);
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			$api_host,
			false
		);
		return $result;
	}

	public function createAutochanger(string $api_host, string $name, string $desc, array $devices): object
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'sd',
			'Autochanger',
			$name
		];
		$config = [
			'Name' => $name,
			'Device' => $devices
		];
		if ($desc) {
			$config['Description'] = $desc;
		}
		$config = array_merge(self::DEF_AUTOCHANGER_DIRECTIVES, $config);
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			$api_host,
			false
		);
		return $result;
	}

	public function createStorage(string $name, string $desc, string $address, int $port, string $pwd, string $device, string $media_type, bool $ach = false): object
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'dir',
			'Storage',
			$name
		];
		$config = [
			'Name' => $name,
			'Address' => $address,
			'SdPort' => $port,
			'Password' => $pwd,
			'Device' => $device,
			'MediaType' => $media_type
		];
		if ($port > 0) {
			$config['SdPort'] = $port;
		}
		if ($desc) {
			$config['Description'] = $desc;
		}
		if ($ach) {
			$config['Autochanger'] = $name;
			$config = array_merge(self::DEF_AUTOCHANGER_STORAGE_DIRECTIVES, $config);
		} else {
			$config = array_merge(self::DEF_SINGLE_STORAGE_DIRECTIVES, $config);
		}
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			null,
			false
		);
		return $result;
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

	private function getStorageIdByAddress(string $sd_address): int
	{
		$api = $this->getModule('api');
		$params = [
			'config',
			'dir',
			'Storage'
		];
		$storage_name = '';
		$result = $api->get($params);
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$addr = $result->output[$i]->Storage->Address ?? '';
				if ($addr === $sd_address) {
					$storage_name = $result->output[$i]->Storage->Name;
					break;
				}
			}
		}
		$storage_id = 0;
		if ($storage_name) {
			$query_params = [
				'name' => $storage_name
			];
			$query = '?' . http_build_query($query_params);
			$params = [
				'storages',
				$query
			];
			$result = $api->get($params);
			if ($result->error == 0) {
				$storage_id = $result->output[0]->storageid ?? 0;
			}
		}
		return $storage_id;
	}

	private function getRunningJobNumber(int $storage_id): int
	{
		$api = $this->getModule('api');
		$query_params = [
			'output' => 'json',
			'type' => 'header'
		];
		$query = '?' . http_build_query($query_params);
		$params = [
			'storages',
			$storage_id,
			'status',
			$query
		];
		$running_job_nb = 0;
		$result = $api->get($params);
		if ($result->error == 0) {
			$running_job_nb = $result->output->jobs_running ?? 0;
		}
		return $running_job_nb;
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
