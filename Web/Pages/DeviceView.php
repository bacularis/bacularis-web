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
 * Copyright (C) 2013-2019 Kern Sibbald
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

/**
 * Device view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class DeviceView extends BaculumWebPage
{
	public const USE_CACHE = true;

	public const STORAGEID = 'StorageId';
	public const STORAGE_NAME = 'StorageName';
	public const DEVICE_NAME = 'DeviceName';

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		if ($this->Request->contains('storageid')) {
			$this->setStorageId($this->Request['storageid']);
		}
		if ($this->Request->contains('device')) {
			$this->setDeviceName($this->Request['device']);
		}
		$storageid = $this->getStorageId();
		if ($storageid > 0) {
			$storage = $this->Application->getModule('api')->get(
				['storages', $this->getStorageId()],
				null,
				true,
				self::USE_CACHE
			);
			if ($storage->error === 0) {
				$this->setStorageName($storage->output->name);
			}
		}
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

	public function setDevice($sender, $param)
	{
		$this->DeviceConfig->setComponentName($_SESSION['sd']);
		$this->DeviceConfig->setResourceName($this->getDeviceName());
		$this->DeviceConfig->setLoadValues(true);
		$this->DeviceConfig->raiseEvent('OnDirectiveListLoad', $this, null);
	}

	public function mount($sender, $param)
	{
		$query = '?device=' . rawurlencode($this->getDeviceName());
		$query .= '&slot=' . (int) ($this->Slot->Text);
		$mount = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'mount', $query]
		);
		if ($mount->error === 0) {
			$this->DeviceLog->Text = implode(PHP_EOL, $mount->output);
		} else {
			$this->DeviceLog->Text = $mount->output;
		}
	}

	public function umount($sender, $param)
	{
		$query = '?device=' . rawurlencode($this->getDeviceName());
		$umount = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'umount', $query]
		);
		if ($umount->error === 0) {
			$this->DeviceLog->Text = implode(PHP_EOL, $umount->output);
		} else {
			$this->DeviceLog->Text = $umount->output;
		}
	}

	public function release($sender, $param)
	{
		$query = '?device=' . rawurlencode($this->getDeviceName());
		$release = $this->getModule('api')->get(
			['storages', $this->getStorageId(), 'release', $query]
		);
		if ($release->error === 0) {
			$this->DeviceLog->Text = implode(PHP_EOL, $release->output);
		} else {
			$this->DeviceLog->Text = $release->output;
		}
	}
}
