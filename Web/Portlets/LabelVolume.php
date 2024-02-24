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
 * Label volume control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class LabelVolume extends Portlets
{
	public const SHOW_BUTTON = 'ShowButton';
	public const BARCODE_LABEL = 'BarcodeLabel';
	public const STORAGE = 'Storage';
	public const POOL = 'Pool';

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
		$this->StorageLabel->DataSource = $storage_list;
		if ($this->Storage) {
			$storage_list_flip = array_flip($storage_list);
			if (key_exists($this->Storage, $storage_list_flip)) {
				$this->StorageLabel->SelectedValue = $storage_list_flip[$this->Storage];
			}
		}
		$this->StorageLabel->dataBind();

		$pools = $this->Application->getModule('api')->get(['pools']);
		$pool_list = [];
		if ($pools->error === 0) {
			foreach ($pools->output as $pool) {
				$pool_list[$pool->poolid] = $pool->name;
			}
			natcasesort($pool_list);
		}
		$this->PoolLabel->dataSource = $pool_list;
		if ($this->Pool) {
			$pool_list_flip = array_flip($pool_list);
			if (key_exists($this->Pool, $pool_list_flip)) {
				$this->PoolLabel->SelectedValue = $pool_list_flip[$this->Pool];
			}
		}
		$this->PoolLabel->dataBind();
	}

	public function labelVolumes($sender, $param)
	{
		$result = null;
		if ($this->Barcodes->Checked || $this->BarcodeLabel) {
			$params = [
				'slots' => $this->SlotsLabel->Text,
				'drive' => $this->DriveLabel->Text,
				'storageid' => $this->StorageLabel->SelectedValue,
				'poolid' => $this->PoolLabel->SelectedValue
			];
			$result = $this->getModule('api')->create(['volumes', 'label', 'barcodes'], $params);
			if ($result->error === 0 && count($result->output) === 1) {
				$out = json_decode($result->output[0]);
				if (is_object($out) && property_exists($out, 'out_id')) {
					$result = $this->getLabelBarcodesOutput($out->out_id);
					$this->getPage()->getCallbackClient()->callClientFunction('label_volume_output_refresh', [$out->out_id]);
				}
			}
		} else {
			$params = [
				'slot' => $this->SlotLabel->Text,
				'volume' => $this->LabelName->Text,
				'drive' => $this->DriveLabel->Text,
				'storageid' => $this->StorageLabel->SelectedValue,
				'poolid' => $this->PoolLabel->SelectedValue
			];
			$result = $this->getModule('api')->create(['volumes', 'label'], $params);
			if ($result->error === 0 && count($result->output) === 1) {
				$out = json_decode($result->output[0]);
				if (is_object($out) && property_exists($out, 'out_id')) {
					$result = $this->getLabelOutput($out->out_id);
					$this->getPage()->getCallbackClient()->callClientFunction('label_volume_output_refresh', [$out->out_id]);
				}
			}
		}
		if ($result->error === 0) {
			$this->getPage()->getCallbackClient()->callClientFunction('set_labeling_status', ['loading']);
			$this->LabelVolumeLog->Text = implode('', $result->output);
			$this->onLabelStart($param);
		} else {
			$this->LabelVolumeLog->Text = $result->output;
			$this->onLabelFail($param);
		}
	}

	private function getLabelOutput($out_id)
	{
		$result = $this->getModule('api')->get(
			['volumes', 'label', '?out_id=' . rawurlencode($out_id)]
		);
		return $result;
	}

	private function getLabelBarcodesOutput($out_id)
	{
		$result = $this->getModule('api')->get(
			['volumes', 'label', 'barcodes', '?out_id=' . rawurlencode($out_id)]
		);
		return $result;
	}

	public function refreshOutput($sender, $param)
	{
		$out_id = $param->getCallbackParameter();
		$result = null;
		if ($this->Barcodes->Checked == true) {
			$result = $this->getLabelBarcodesOutput($out_id);
		} else {
			$result = $this->getLabelOutput($out_id);
		}

		if ($result->error === 0) {
			if (count($result->output) > 0) {
				$this->LabelVolumeLog->Text = implode('', $result->output);
				$this->getPage()->getCallbackClient()->callClientFunction('label_volume_output_refresh', [$out_id]);
			} else {
				$this->getPage()->getCallbackClient()->callClientFunction('set_labeling_status', ['finish']);
				$this->onLabelSuccess($param);
				$this->onLabelComplete($param);
			}
		} else {
			$this->LabelVolumeLog->Text = $result->output;
			$this->onLabelFail($param);
			$this->onLabelComplete($param);
		}
	}

	public function onLabelStart($param)
	{
		$this->raiseEvent('OnLabelStart', $this, $param);
	}

	public function onLabelComplete($param)
	{
		$this->raiseEvent('OnLabelComplete', $this, $param);
	}

	public function onLabelSuccess($param)
	{
		$this->raiseEvent('OnLabelSuccess', $this, $param);
	}

	public function onLabelFail($param)
	{
		$this->raiseEvent('OnLabelFail', $this, $param);
	}

	public function setSlots(array $slots)
	{
		$this->SlotsLabel->Text = implode(',', $slots);
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

	public function setBarcodeLabel($barcode_label)
	{
		$barcode_label = TPropertyValue::ensureBoolean($barcode_label);
		$this->setViewState(self::BARCODE_LABEL, $barcode_label);
	}

	public function getBarcodeLabel()
	{
		return $this->getViewState(self::BARCODE_LABEL);
	}

	public function setStorage($storage)
	{
		$this->setViewState(self::STORAGE, $storage);
	}

	public function getStorage()
	{
		return $this->getViewState(self::STORAGE);
	}

	public function setPool($pool)
	{
		$this->setViewState(self::POOL, $pool);
	}

	public function getPool()
	{
		return $this->getViewState(self::POOL);
	}
}
