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

use Prado\Prado;
use Bacularis\Common\Modules\Params;
use Bacularis\Common\Modules\Errors\DeviceError;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Storage view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class StorageView extends BaculumWebPage
{
	public const STORAGEID = 'StorageId';
	public const STORAGE_NAME = 'StorageName';
	public const STORAGE_ADDRESS = 'StorageAddress';
	public const IS_AUTOCHANGER = 'IsAutochanger';
	public const DEVICE_NAME = 'DeviceName';

	public const USE_CACHE = true;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$storageid = 0;
		if ($this->Request->contains('storageid')) {
			$storageid = (int) ($this->Request['storageid']);
		} elseif ($this->Request->contains('storage')) {
			$result = $this->getModule('api')->get(['storages']);
			if ($result->error === 0) {
				for ($i = 0; $i < count($result->output); $i++) {
					if ($this->Request['storage'] === $result->output[$i]->name) {
						$storageid = $result->output[$i]->storageid;
						break;
					}
				}
			}
		}
		$this->setStorageId($storageid);
		$storageshow = $this->getModule('api')->get(
			['storages', $storageid, 'show', '?output=json'],
			null,
			true
		);
		if ($storageshow->error === 0) {
			$this->setStorageName($storageshow->output->name);

			if (property_exists($storageshow->output, 'address')) {
				$this->setStorageAddress($storageshow->output->address);
				$this->OSDAddress->Text = $storageshow->output->address;
			}
			if (property_exists($storageshow->output, 'sdport')) {
				$this->OSDPort->Text = $storageshow->output->sdport;
			}
			if (property_exists($storageshow->output, 'maxjobs') && property_exists($storageshow->output, 'numjobs')) {
				$this->ORunningJobs->Text = $storageshow->output->numjobs . '/' . $storageshow->output->maxjobs;
			}
			if (property_exists($storageshow->output, 'devicename')) {
				$this->setDeviceName($storageshow->output->devicename);
				$this->ODeviceName->Text = $storageshow->output->devicename;
			}
			if (property_exists($storageshow->output, 'mediatype')) {
				$this->OMediaType->Text = $storageshow->output->mediatype;
			}
			if (property_exists($storageshow->output, 'autochanger')) {
				$is_autochanger = ($storageshow->output->autochanger == 1);
				$this->setIsAutochanger($is_autochanger);
				$this->OAutoChanger->Text = $is_autochanger ? Prado::localize('Yes') : Prado::localize('No');
				$this->Autochanger->Display = $is_autochanger ? 'Dynamic' : 'None';
			}
		}
		$this->setAPIHosts();

		// Set component actions
		$sd_api_host = $this->getSDAPIHost();
		if ($sd_api_host) {
			$this->CompActions->setHost($sd_api_host);
			$this->CompActions->setComponentType('sd');
			$this->BulkApplyPatternsStorage->setHost($sd_api_host);
		}
	}

	private function setAPIHosts()
	{
		$def_host = null;
		$api_hosts = $this->getModule('host_config')->getConfig();
		$user_api_hosts = $this->User->getAPIHosts();
		$storage_address = $this->getStorageAddress();
		foreach ($api_hosts as $name => $attrs) {
			if (in_array($name, $user_api_hosts) && $attrs['address'] === $storage_address) {
				$def_host = $name;
				break;
			}
		}
		$this->UserAPIHosts->DataSource = array_combine($user_api_hosts, $user_api_hosts);
		if ($def_host) {
			$this->UserAPIHosts->SelectedValue = $def_host;
		} else {
			$this->UserAPIHosts->SelectedValue = $this->User->getDefaultAPIHost();
		}
		$this->UserAPIHosts->dataBind();
		if (count($user_api_hosts) === 1) {
			$this->UserAPIHostsContainter->Visible = false;
		}
	}

	public function status($sender, $param)
	{
		$raw_status = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'status']
		)->output;
		$this->StorageLog->Text = implode(PHP_EOL, $raw_status);

		$query_str = '?output=json&type=header';
		$graph_status = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'status', $query_str]
		);
		$storage_status = [
			'header' => [],
			'devices' => [],
			'running' => [],
			'terminated' => [],
			'version' => Params::getComponentVersion($raw_status)
		];
		if ($graph_status->error === 0) {
			$storage_status['header'] = $graph_status->output;
		}

		// running
		$query_str = '?output=json&type=running';
		$graph_status = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'status', $query_str]
		);
		if ($graph_status->error === 0) {
			$storage_status['running'] = $graph_status->output;
		}

		// terminated
		$query_str = '?output=json&type=terminated';
		$graph_status = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'status', $query_str]
		);
		if ($graph_status->error === 0) {
			$storage_status['terminated'] = $graph_status->output;
		}

		// devices
		$query_str = '?output=json&type=devices';
		$graph_status = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'status', $query_str]
		);
		if ($graph_status->error === 0) {
			$storage_status['devices'] = $graph_status->output;
		}

		// show
		$query_str = '?output=json';
		$show = $this->getModule('api')->get(
			['storages', 'show', $query_str]
		);
		if ($show->error === 0) {
			$storage_status['show'] = $show->output;
		}

		$this->getCallbackClient()->callClientFunction('init_graphical_storage_status', [$storage_status]);
	}

	private function getSDAPIHost()
	{
		if (!$this->User->isUserAPIHost($this->UserAPIHosts->SelectedValue)) {
			// Validation error. Somebody manually modified select values
			return false;
		}
		return $this->UserAPIHosts->SelectedValue;
	}

	private function getSDName()
	{
		if (!($host = $this->getSDAPIHost())) {
			return;
		}
		$sdname = null;
		$result = $this->getModule('api')->get(['config'], $host);
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if ($result->output[$i]->component_type === 'sd' && $result->output[$i]->state) {
					$sdname = $result->output[$i]->component_name;
				}
			}
		}
		return $sdname;
	}

	public function setStorage($sender, $param)
	{
		$this->SDStorageDaemonConfig->unloadDirectives();
		if (!empty($_SESSION['dir'])) {
			$this->DIRStorageConfig->setComponentName($_SESSION['dir']);
			$this->DIRStorageConfig->setResourceName($this->getStorageName());
			$this->DIRStorageConfig->setLoadValues(true);
			$this->DIRStorageConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	public function loadSDStorageDaemonConfig($sender, $param)
	{
		if (!($host = $this->getSDAPIHost())) {
			return;
		}
		$this->DIRStorageConfig->unloadDirectives();
		$component_name = $this->getSDName();
		if (!is_null($component_name)) {
			$this->SDStorageDaemonConfigErr->Display = 'None';
			$this->SDStorageDaemonConfig->setHost($host);
			$this->SDStorageDaemonConfig->setComponentName($component_name);
			$this->SDStorageDaemonConfig->setResourceName($component_name);
			$this->SDStorageDaemonConfig->setLoadValues(true);
			$this->SDStorageDaemonConfig->raiseEvent('OnDirectiveListLoad', $this, null);
			$this->BulkApplyPatternsStorage->setHost($host);
		} else {
			$this->SDStorageDaemonConfigErr->Display = 'Dynamic';
		}
	}

	public function loadSDResourcesConfig($sender, $param)
	{
		if (!($host = $this->getSDAPIHost())) {
			return;
		}
		$resource_type = $param->getCallbackParameter();
		$this->DIRStorageConfig->unloadDirectives();
		$this->SDStorageDaemonConfig->unloadDirectives();
		$component_name = $this->getSDName();
		if (!is_null($component_name) && !empty($resource_type)) {
			$this->StorageDaemonResourcesConfig->setHost($host);
			$this->StorageDaemonResourcesConfig->setResourceType($resource_type);
			$this->StorageDaemonResourcesConfig->setComponentName($component_name);
			$this->StorageDaemonResourcesConfig->loadResourceListTable();
			$this->BulkApplyPatternsStorage->setHost($host);
		} else {
			$this->StorageDaemonResourcesConfig->showError(true);
		}
	}

	private function actionLoading($result, $out_id, $refresh_func)
	{
		$messages_log = $this->getModule('messages_log');
		if ($result->error === 0) {
			$rlen = count($result->output);
			$last = $rlen > 0 ? trim($result->output[$rlen - 1]) : '';
			if ($last === 'quit') {
				array_pop($result->output);
			}
			$messages_log->append($result->output);
			if (count($result->output) > 0) {
				// log messages
				$output = implode('', $result->output);
				$this->getCallbackClient()->callClientFunction(
					'oStorageActions.log',
					[$output]
				);
				// refresh output periodically
				$this->getCallbackClient()->callClientFunction(
					$refresh_func,
					[$out_id]
				);
			} else {
				$this->getCallbackClient()->callClientFunction(
					'oStorageActions.show_loader',
					[false]
				);
			}
		} else {
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$messages_log->append($result->output);
			$this->getCallbackClient()->callClientFunction(
				'oStorageActions.log',
				[$emsg]
			);
		}
	}

	private function logActionError($result)
	{
		$emsg = sprintf('Error %s: %s', $result->error, $result->output);
		$messages_log = $this->getModule('messages_log');
		$messages_log->append($result->output);
		$this->getCallbackClient()->callClientFunction(
			'oStorageActions.log',
			[$emsg]
		);
	}

	public function mount($sender, $param)
	{
		$drive = $this->getIsAutochanger() ? (int) ($this->Drive->Text) : 0;
		$slot = $this->getIsAutochanger() ? (int) ($this->Slot->Text) : 0;
		$result = $this->mountAction($drive, $slot);
		if ($result->error === 0 && count($result->output) == 1) {
			$out = json_decode($result->output[0]);
			if (is_object($out) && property_exists($out, 'out_id')) {
				$this->getCallbackClient()->callClientFunction(
					'oStorageActions.refresh_mount',
					[$out->out_id]
				);
			}
		} else {
		}
	}

	private function mountAction($drive, $slot)
	{
		$params = [
			'drive' => $drive,
			'slot' => $slot
		];
		$query = '?' . http_build_query($params);
		$result = $this->getModule('api')->set(
			[
				'storages',
				$this->getStorageId(),
				'mount',
				$query
			]
		);
		return $result;
	}

	public function mountLoading($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$parameters = [
			'out_id' => $out_id
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->get([
			'storages',
			$this->getStorageId(),
			'mount',
			$query
		]);
		$this->actionLoading(
			$result,
			$out_id,
			'oStorageActions.refresh_mount'
		);
	}

	public function umount($sender, $param)
	{
		$drive = $this->getIsAutochanger() ? (int) ($this->Drive->Text) : 0;
		$result = $this->umountAction($drive);
		if ($result->error === 0) {
			if (count($result->output) == 1) {
				$out = json_decode($result->output[0]);
				if (is_object($out) && property_exists($out, 'out_id')) {
					$this->getCallbackClient()->callClientFunction(
						'oStorageActions.refresh_umount',
						[$out->out_id]
					);
				}
			}
		} else {
			$this->logActionError($result);
		}
	}

	private function umountAction($drive)
	{
		$params = [
			'drive' => $drive
		];
		$query = '?' . http_build_query($params);
		$result = $this->getModule('api')->set([
			'storages',
			$this->getStorageId(),
			'umount',
			$query
		]);
		return $result;
	}

	public function umountLoading($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$parameters = [
			'out_id' => $out_id
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->get([
			'storages',
			$this->getStorageId(),
			'umount',
			$query
		]);
		$this->actionLoading(
			$result,
			$out_id,
			'oStorageActions.refresh_umount'
		);
	}

	public function release($sender, $param)
	{
		$drive = $this->getIsAutochanger() ? (int) ($this->Drive->Text) : 0;
		$result = $this->releaseAction($drive);
		if ($result->error === 0) {
			if (count($result->output) == 1) {
				$out = json_decode($result->output[0]);
				if (is_object($out) && property_exists($out, 'out_id')) {
					$this->getCallbackClient()->callClientFunction(
						'oStorageActions.refresh_release',
						[$out->out_id]
					);
				}
			}
		} else {
			$this->logActionError($result);
		}
	}

	private function releaseAction($drive)
	{
		$params = [
			'drive' => $drive
		];
		$query = '?' . http_build_query($params);
		$result = $this->getModule('api')->set([
			'storages',
			$this->getStorageId(),
			'release',
			$query
		]);
		return $result;
	}

	public function releaseLoading($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$parameters = [
			'out_id' => $out_id
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->get([
			'storages',
			$this->getStorageId(),
			'release',
			$query
		]);
		$this->actionLoading(
			$result,
			$out_id,
			'oStorageActions.refresh_release'
		);
	}

	public function loadAutochanger($sender, $param)
	{
		if (!($host = $this->getSDAPIHost())) {
			return;
		}
		$result = $this->getModule('api')->get(
			[
				'devices',
				$this->getDeviceName(),
				'listall'
			],
			$host
		);
		$cb = $this->getCallbackClient();
		if ($result->error === 0) {
			$cb->show('drive_list_container');
			$cb->show('slot_list_container');
			$cb->hide('manage_autochanger_not_available');
			$cb->callClientFunction(
				'oDrives.load_drives_cb',
				[$result->output->drives]
			);
			$slots = array_merge(
				$result->output->slots,
				$result->output->ie_slots
			);
			$cb->callClientFunction(
				'oSlots.load_slots_cb',
				[$slots]
			);
		} elseif ($result->error === DeviceError::ERROR_DEVICE_DEVICE_CONFIG_DOES_NOT_EXIST || $result->error === DeviceError::ERROR_DEVICE_AUTOCHANGER_DOES_NOT_EXIST) {
			$cb->hide('drive_list_container');
			$cb->hide('slot_list_container');
			$cb->show('manage_autochanger_not_available');
		}
	}

	public function loadDrive($sender, $param)
	{
		$data = $param->getCallbackParameter();
		if (!is_object($data)) {
			return;
		}
		$result = [];
		if ($this->LoadDriveMount->Checked) {
			$parameters = [
				'device' => $data->drive,
				'slot' => $data->slot
			];
			$query = '?' . http_build_query($parameters);
			$result = $this->getModule('api')->set([
				'storages',
				$this->getStorageId(),
				'mount',
				$query
			]);
		} else {
			if ($host = $this->getSDAPIHost()) {
				$parameters = [
					'drive' => $data->drive,
					'slot' => $data->slot
				];
				$query = '?' . http_build_query($parameters);
				$result = $this->getModule('api')->set(
					[
						'devices',
						$this->getDeviceName(),
						'load',
						$query
					],
					[],
					$host
				);
			} else {
				$result = new StdClass();
				$result->error = DeviceError::ERROR_DEVICE_AUTOCHANGER_DRIVE_DOES_NOT_EXIST;
				$result->output = Prado::localize('There was a problem with loading the resource configuration. Please check if selected API host is working and if it provides access to the resource configuration.');
			}
		}
		if ($result->error === 0) {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.set_drive_window_ok'
			);
			$out_id = '';
			if ($this->LoadDriveMount->Checked) {
				if (count($result->output) == 1) {
					$out = json_decode($result->output[0]);
					if (is_object($out) && property_exists($out, 'out_id')) {
						$out_id = $out->out_id;
					}
				}
				$this->getCallbackClient()->callClientFunction(
					'oSlots.refresh_drive_with_mount_loading',
					[$out_id]
				);
			} else {
				$out_id = $result->output->out_id;
				$this->getCallbackClient()->callClientFunction(
					'oSlots.refresh_drive_without_mount_loading',
					[$out_id]
				);
			}
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$this->getModule('messages_log')->append([$emsg]);
		}
	}

	public function loadedDriveWithMount($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$parameters = [
			'out_id' => $out_id
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->get([
			'storages',
			$this->getStorageId(),
			'mount',
			$query
		]);
		$this->loadedDrive(
			'oSlots.refresh_drive_with_mount_loading',
			$out_id,
			$result
		);
	}

	public function loadedDriveWithoutMount($sender, $param)
	{
		if (!($host = $this->getSDAPIHost())) {
			return;
		}
		$out_id = $param->getCallbackParameter();
		$parameters = [
			'out_id' => $out_id
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->get(
			[
				'devices',
				$this->getDeviceName(),
				'load',
				$query
			],
			$host
		);
		$this->loadedDrive(
			'oSlots.refresh_drive_without_mount_loading',
			$out_id,
			$result
		);
	}

	public function loadedDrive($refresh_func, $out_id, $result)
	{
		$messages_log = $this->getModule('messages_log');
		if ($result->error === 0) {
			$rlen = count($result->output);
			$last = $rlen > 0 ? trim($result->output[$rlen - 1]) : '';
			if ($last === 'quit') {
				array_pop($result->output);
			}
			$messages_log->append($result->output);
		} else {
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$messages_log->append($result->output);
		}
		if ($result->error === 0) {
			if (count($result->output) > 0) {
				// refresh output periodically
				$this->getCallbackClient()->callClientFunction(
					$refresh_func,
					[$out_id]
				);
			} else {
				$this->getCallbackClient()->callClientFunction(
					'oSlots.show_changer_loader',
					[false]
				);
				// finish refreshing output
				$this->loadAutochanger(null, null);
			}
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$this->getModule('messages_log')->append([$emsg]);
		}
	}

	public function unloadDrive($sender, $param)
	{
		$data = $param->getCallbackParameter();
		if (!is_object($data)) {
			return;
		}
		$parameters = [
			'device' => $data->drive,
			'slot' => $data->slot
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->set([
			'storages',
			$this->getStorageId(),
			'release',
			$query
		]);
		if ($result->error === 0) {
			$out_id = '';
			if (count($result->output) == 1) {
				$out = json_decode($result->output[0]);
				if (is_object($out) && property_exists($out, 'out_id')) {
					$out_id = $out->out_id;
				}
			}
			$this->getCallbackClient()->callClientFunction(
				'oDrives.refresh_drive_unloading',
				[$out_id]
			);
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$this->getModule('messages_log')->append([$emsg]);
		}
	}

	public function unloadedDrive($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$parameters = [
			'out_id' => $out_id
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->get([
			'storages',
			$this->getStorageId(),
			'release',
			$query
		]);
		$messages_log = $this->getModule('messages_log');
		if ($result->error === 0) {
			$rlen = count($result->output);
			$last = $rlen > 0 ? trim($result->output[$rlen - 1]) : '';
			if ($last === 'quit') {
				array_pop($result->output);
			}
			$messages_log->append($result->output);
			if (count($result->output) > 0) {
				// refresh output periodically
				$this->getCallbackClient()->callClientFunction(
					'oDrives.refresh_drive_unloading',
					[$out_id]
				);
			} else {
				$this->getCallbackClient()->callClientFunction(
					'oSlots.show_changer_loader',
					[false]
				);
				// finish refreshing output
				$this->loadAutochanger(null, null);
			}
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$messages_log->append($result->output);
		}
	}

	public function labelBarcodes($sender, $param)
	{
		$slots_ach = explode('|', $param->getCallbackParameter());
		$this->LabelBarcodes->setSlots($slots_ach);
		$this->LabelBarcodes->loadValues();
	}

	public function labelComplete($sender, $param)
	{
		$this->getCallbackClient()->callClientFunction(
			'show_label_volume_window',
			[false]
		);
		$this->getCallbackClient()->callClientFunction(
			'oSlots.show_changer_loader',
			[false]
		);
		$this->loadAutochanger(null, null);
	}

	private function transferSlots($slotsrc, $slotdest)
	{
		if (!($host = $this->getSDAPIHost())) {
			return;
		}
		$parameters = [
			'slotsrc' => $slotsrc,
			'slotdest' => $slotdest
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->set(
			[
				'devices',
				$this->getDeviceName(),
				'transfer',
				$query
			],
			[],
			$host
		);
		return $result;
	}

	private function getTransferOutput($out_id)
	{
		if (!($host = $this->getSDAPIHost())) {
			return;
		}
		$parameters = [
			'out_id' => $out_id
		];
		$query = '?' . http_build_query($parameters);
		$result = $this->getModule('api')->get(
			[
				'devices',
				$this->getDeviceName(),
				'transfer',
				$query
			],
			$host
		);
		return $result;
	}

	public function moveToIE($sender, $param)
	{
		[$slot_ach, $ie_slot] = explode(',', $param->getCallbackParameter(), 2);
		$result = $this->transferSlots($slot_ach, $ie_slot);
		if ($result->error === 0) {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.refresh_move_to_ie_loading',
				[$result->output->out_id]
			);
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$this->getModule('messages_log')->append([$emsg]);
		}
	}

	public function movingToIE($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$result = $this->getTransferOutput($out_id);
		$messages_log = $this->getModule('messages_log');
		if ($result->error === 0) {
			if (count($result->output) > 0) {
				// refresh output periodically
				$this->getCallbackClient()->callClientFunction(
					'oSlots.refresh_move_to_ie_loading',
					[$out_id]
				);
				$rlen = count($result->output);
				$last = $rlen > 0 ? trim($result->output[$rlen - 1]) : '';
				if ($last === 'quit') {
					array_pop($result->output);
				}
				$messages_log->append($result->output);
				$out = implode(PHP_EOL, $result->output);
				$this->getCallbackClient()->callClientFunction(
					'oSlots.set_move_to_ie_log',
					[$out]
				);
			} else {
				$this->getCallbackClient()->callClientFunction(
					'oSlots.show_changer_loader',
					[false]
				);
				$this->getCallbackClient()->callClientFunction(
					'oSlots.move_to_ie'
				);
				// finish refreshing output
				$this->loadAutochanger(null, null);
			}
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$messages_log->append($result->output);
		}
	}

	public function releaseIE($sender, $param)
	{
		[$ie_slot, $slot_ach] = explode(',', $param->getCallbackParameter(), 2);
		$result = $this->transferSlots($ie_slot, $slot_ach);
		if ($result->error === 0) {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.refresh_release_ie_loading',
				[$result->output->out_id]
			);
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$this->getModule('messages_log')->append([$emsg]);
		}
	}

	public function releasingIE($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$result = $this->getTransferOutput($out_id);
		$messages_log = $this->getModule('messages_log');
		if ($result->error === 0) {
			if (count($result->output) > 0) {
				// refresh output periodically
				$this->getCallbackClient()->callClientFunction(
					'oSlots.refresh_release_ie_loading',
					[$out_id]
				);
				$rlen = count($result->output);
				$last = $rlen > 0 ? trim($result->output[$rlen - 1]) : '';
				if ($last === 'quit') {
					array_pop($result->output);
				}
				$messages_log->append($result->output);
				$out = implode(PHP_EOL, $result->output);
				$this->getCallbackClient()->callClientFunction(
					'oSlots.set_release_ie_log',
					[$out]
				);
			} else {
				$this->getCallbackClient()->callClientFunction(
					'oSlots.show_changer_loader',
					[false]
				);
				$this->getCallbackClient()->callClientFunction(
					'oSlots.release_ie',
					[true]
				);

				// finish refreshing output
				$this->loadAutochanger(null, null);
			}
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$messages_log->append($result->output);
		}
	}

	public function updateSlotsBarcodes($sender, $param)
	{
		$slots_ach = explode('|', $param->getCallbackParameter());
		$this->UpdateSlots->BarcodeUpdate = true;
		$this->UpdateSlots->setSlots($slots_ach);
		$this->UpdateSlots->loadValues();
	}

	public function updateSlots($sender, $param)
	{
		$slots_ach = explode('|', $param->getCallbackParameter());
		$this->UpdateSlots->BarcodeUpdate = false;
		$this->UpdateSlots->setSlots($slots_ach);
		$this->UpdateSlots->loadValues();
	}

	public function moveFromIE($sender, $param)
	{
		[$ie_slot, $slot_ach] = explode(',', $param->getCallbackParameter(), 2);
		$result = $this->transferSlots($ie_slot, $slot_ach);
		if ($result->error === 0) {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.refresh_release_all_ie_loading',
				[$result->output->out_id]
			);
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$this->getModule('messages_log')->append([$emsg]);
		}
	}

	public function movingFromIE($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$result = $this->getTransferOutput($out_id);
		$messages_log = $this->getModule('messages_log');
		if ($result->error === 0) {
			if (count($result->output) > 0) {
				// refresh output periodically
				$this->getCallbackClient()->callClientFunction(
					'oSlots.refresh_release_all_ie_loading',
					[$out_id]
				);
				$rlen = count($result->output);
				$last = $rlen > 0 ? trim($result->output[$rlen - 1]) : '';
				if ($last === 'quit') {
					array_pop($result->output);
				}
				$messages_log->append($result->output);
			} else {
				$this->getCallbackClient()->callClientFunction(
					'oSlots.show_changer_loader',
					[false]
				);
				$this->getCallbackClient()->callClientFunction(
					'oSlots.release_all_ie'
				);
				// finish refreshing output
				$this->loadAutochanger(null, null);
			}
		} else {
			$this->getCallbackClient()->callClientFunction(
				'oSlots.show_changer_loader',
				[false]
			);
			$emsg = sprintf('Error %s: %s', $result->error, $result->output);
			$messages_log->append($result->output);
		}
	}

	public function showChangerLoading($sender, $param)
	{
		$this->getCallbackClient()->callClientFunction(
			'oSlots.show_changer_loader',
			[true]
		);
	}

	public function hideChangerLoading($sender, $param)
	{
		$this->getCallbackClient()->callClientFunction(
			'oSlots.show_changer_loader',
			[false]
		);
	}

	/**
	 * Set storage storageid.
	 *
	 * @param mixed $storageid
	 */
	public function setStorageId($storageid)
	{
		$storageid = (int) $storageid;
		$this->setViewState(self::STORAGEID, $storageid, 0);
	}

	/**
	 * Get storage storageid.
	 *
	 * @return int storageid
	 */
	public function getStorageId()
	{
		return $this->getViewState(self::STORAGEID, 0);
	}

	/**
	 * Set storage name.
	 *
	 * @param mixed $storage_name
	 */
	public function setStorageName($storage_name)
	{
		$this->setViewState(self::STORAGE_NAME, $storage_name);
	}

	/**
	 * Get storage name.
	 *
	 * @return string storage name
	 */
	public function getStorageName()
	{
		return $this->getViewState(self::STORAGE_NAME);
	}

	/**
	 * Set device name.
	 *
	 * @param mixed $device_name
	 */
	public function setDeviceName($device_name)
	{
		$this->setViewState(self::DEVICE_NAME, $device_name);
	}

	/**
	 * Get device name.
	 *
	 * @return string device name
	 */
	public function getDeviceName()
	{
		return $this->getViewState(self::DEVICE_NAME);
	}

	/**
	 * Check if storage is autochanger
	 *
	 * @return bool true if autochanger, otherwise false
	 */
	public function getIsAutochanger()
	{
		return $this->getViewState(self::IS_AUTOCHANGER, false);
	}

	/**
	 * Set autochanger value for storage
	 *
	 * @param mixed $is_autochanger
	 */
	public function setIsAutochanger($is_autochanger)
	{
		settype($is_autochanger, 'bool');
		$this->setViewState(self::IS_AUTOCHANGER, $is_autochanger);
	}

	/**
	 * Set storage address.
	 *
	 * @param mixed $address
	 */
	public function setStorageAddress($address)
	{
		$this->setViewState(self::STORAGE_ADDRESS, $address);
	}

	/**
	 * Get storage address.
	 *
	 * @return string address
	 */
	public function getStorageAddress()
	{
		return $this->getViewState(self::STORAGE_ADDRESS);
	}
}
