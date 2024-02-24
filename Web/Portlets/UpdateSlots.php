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

namespace Bacularis\Web\Portlets;

use Prado\TPropertyValue;

/**
 * Update slots control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class UpdateSlots extends Portlets
{
	public const SHOW_BUTTON = 'ShowButton';
	public const BARCODE_UPDATE = 'BarcodeUpdate';
	public const STORAGE = 'Storage';

	public function loadValues()
	{
		$storages = $this->getModule('api')->get(['storages']);
		$storage_list = [];
		if ($storages->error === 0) {
			foreach ($storages->output as $storage) {
				$storage_list[$storage->storageid] = $storage->name;
			}
			natcasesort($storage_list);
		}
		$this->StorageUpdate->DataSource = $storage_list;
		if ($this->Storage) {
			$storage_list_flip = array_flip($storage_list);
			$this->StorageUpdate->SelectedValue = $storage_list_flip[$this->Storage];
		}
		$this->StorageUpdate->dataBind();
	}

	public function update($sender, $param)
	{
		$url_params = [];
		if ($this->Barcodes->Checked == true || $this->BarcodeUpdate) {
			$url_params = ['volumes', 'update', 'barcodes'];
		} else {
			$url_params = ['volumes', 'update'];
		}
		$params = [
			'slots' => $this->SlotsUpdate->Text,
			'drive' => $this->DriveUpdate->Text,
			'storageid' => $this->StorageUpdate->SelectedValue
		];

		$result = $this->getModule('api')->set($url_params, $params);

		if ($result->error === 0 && count($result->output) === 1) {
			$out = json_decode($result->output[0]);
			if (is_object($out) && property_exists($out, 'out_id')) {
				$result = $this->getUpdateSlotsOutput($out->out_id);
				$this->getPage()->getCallbackClient()->callClientFunction('update_slots_output_refresh', [$out->out_id]);
			}
		}

		if ($result->error === 0) {
			$this->getPage()->getCallbackClient()->callClientFunction('set_updating_status', ['loading']);
			$this->UpdateSlotsLog->Text = implode('', $result->output);
			$this->onUpdateStart($param);
		} else {
			$this->UpdateSlotsLog->Text = $result->output;
			$this->onUpdateFail($param);
		}
	}

	private function getUpdateSlotsOutput($out_id)
	{
		$result = $this->getModule('api')->get(
			['volumes', 'update', '?out_id=' . rawurlencode($out_id)]
		);
		return $result;
	}

	private function getUpdateSlotsBarcodesOutput($out_id)
	{
		$result = $this->getModule('api')->get(
			['volumes', 'update', 'barcodes', '?out_id=' . rawurlencode($out_id)]
		);
		return $result;
	}

	public function refreshOutput($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$result = null;
		if ($this->Barcodes->Checked == true) {
			$result = $this->getUpdateSlotsBarcodesOutput($out_id);
		} else {
			$result = $this->getUpdateSlotsOutput($out_id);
		}

		if ($result->error === 0) {
			if (count($result->output) > 0) {
				$this->UpdateSlotsLog->Text = implode('', $result->output);
				$this->getPage()->getCallbackClient()->callClientFunction('update_slots_output_refresh', [$out_id]);
			} else {
				$this->getPage()->getCallbackClient()->callClientFunction('set_updating_status', ['finish']);
				$this->onUpdateSuccess($param);
				$this->onUpdateComplete($param);
			}
		} else {
			$this->UpdateSlotsLog->Text = $result->output;
			$this->onUpdateFail($param);
			$this->onUpdateComplete($param);
		}
	}
	public function onUpdateStart($param)
	{
		$this->raiseEvent('OnUpdateStart', $this, $param);
	}

	public function onUpdateComplete($param)
	{
		$this->raiseEvent('OnUpdateComplete', $this, $param);
	}

	public function onUpdateSuccess($param)
	{
		$this->raiseEvent('OnUpdateSuccess', $this, $param);
	}

	public function onUpdateFail($param)
	{
		$this->raiseEvent('OnUpdateFail', $this, $param);
	}

	public function setSlots(array $slots)
	{
		$this->SlotsUpdate->Text = implode(',', $slots);
	}

	public function setShowButton($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_BUTTON, $show);
	}

	public function getShowButton()
	{
		return $this->getViewState(self::SHOW_BUTTON, true);
	}

	public function setBarcodeUpdate($barcode_update)
	{
		$barcode_update = TPropertyValue::ensureBoolean($barcode_update);
		$this->setViewState(self::BARCODE_UPDATE, $barcode_update);
	}

	public function getBarcodeUpdate()
	{
		return $this->getViewState(self::BARCODE_UPDATE);
	}

	public function setStorage($storage)
	{
		$this->setViewState(self::STORAGE, $storage);
	}

	public function getStorage()
	{
		return $this->getViewState(self::STORAGE);
	}
}
