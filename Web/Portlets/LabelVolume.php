<?php
/*
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

Prado::using('System.Web.UI.ActiveControls.TActiveCheckBox');
Prado::using('System.Web.UI.ActiveControls.TActiveDropDownList');
Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');
Prado::using('System.Web.UI.ActiveControls.TActiveTextBox');
Prado::using('Application.Web.Portlets.Portlets');

/**
 * Label volume control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class LabelVolume extends Portlets {

	const SHOW_BUTTON = 'ShowButton';
	const BARCODE_LABEL = 'BarcodeLabel';
	const STORAGE = 'Storage';
	const POOL = 'Pool';

	public function loadValues() {
		$storages = $this->getModule('api')->get(array('storages'));
		$storage_list = array();
		if ($storages->error === 0) {
			foreach($storages->output as $storage) {
				$storage_list[$storage->storageid] = $storage->name;
			}
		}
		$this->StorageLabel->DataSource = $storage_list;
		if ($this->Storage) {
			$storage_list_flip = array_flip($storage_list);
			if (key_exists($this->Storage, $storage_list_flip)) {
				$this->StorageLabel->SelectedValue = $storage_list_flip[$this->Storage];
			}
		}
		$this->StorageLabel->dataBind();

		$pools = $this->Application->getModule('api')->get(array('pools'));
		$pool_list = array();
		if ($pools->error === 0) {
			foreach($pools->output as $pool) {
				$pool_list[$pool->poolid] = $pool->name;
			}
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

	public function labelVolumes($sender, $param) {
		$result = null;
		if ($this->Barcodes->Checked || $this->BarcodeLabel) {
			$params = array(
				'slots' => $this->SlotsLabel->Text,
				'drive' => $this->DriveLabel->Text,
				'storageid' => $this->StorageLabel->SelectedValue,
				'poolid' => $this->PoolLabel->SelectedValue
			);
			$result = $this->getModule('api')->create(array('volumes', 'label', 'barcodes'), $params);
			if ($result->error === 0 && count($result->output) === 1) {
				$out = json_decode($result->output[0]);
				if (is_object($out) && property_exists($out, 'out_id')) {
					$result = $this->getLabelBarcodesOutput($out->out_id);
					$this->getPage()->getCallbackClient()->callClientFunction('label_volume_output_refresh', array($out->out_id));
				}
			}
		} else {
			$params = array(
				'slot' => $this->SlotLabel->Text,
				'volume' => $this->LabelName->Text,
				'drive' => $this->DriveLabel->Text,
				'storageid' => $this->StorageLabel->SelectedValue,
				'poolid' => $this->PoolLabel->SelectedValue
			);
			$result = $this->getModule('api')->create(array('volumes', 'label'), $params);
			if ($result->error === 0 && count($result->output) === 1) {
				$out = json_decode($result->output[0]);
				if (is_object($out) && property_exists($out, 'out_id')) {
					$result = $this->getLabelOutput($out->out_id);
					$this->getPage()->getCallbackClient()->callClientFunction('label_volume_output_refresh', array($out->out_id));
				}
			}
		}
		if ($result->error === 0) {
			$this->getPage()->getCallbackClient()->callClientFunction('set_labeling_status', array('loading'));
			$this->LabelVolumeLog->Text = implode('', $result->output);
			$this->onLabelStart($param);
		} else {
			$this->LabelVolumeLog->Text = $result->output;
			$this->onLabelFail($param);
		}
	}

	private function getLabelOutput($out_id) {
		$result = $this->getModule('api')->get(
			array('volumes', 'label', '?out_id=' . rawurlencode($out_id))
		);
		return $result;
	}

	private function getLabelBarcodesOutput($out_id) {
		$result = $this->getModule('api')->get(
			array('volumes', 'label', 'barcodes', '?out_id=' . rawurlencode($out_id))
		);
		return $result;
	}

	public function refreshOutput($sender, $param) {
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
				$this->getPage()->getCallbackClient()->callClientFunction('label_volume_output_refresh', array($out_id));
			} else {
				$this->getPage()->getCallbackClient()->callClientFunction('set_labeling_status', array('finish'));
				$this->onLabelSuccess($param);
				$this->onLabelComplete($param);
			}
		} else {
			$this->LabelVolumeLog->Text = $result->output;
			$this->onLabelFail($param);
			$this->onLabelComplete($param);
		}
	}

	public function onLabelStart($param) {
		$this->raiseEvent('OnLabelStart', $this, $param);
	}

	public function onLabelComplete($param) {
		$this->raiseEvent('OnLabelComplete', $this, $param);
	}

	public function onLabelSuccess($param) {
		$this->raiseEvent('OnLabelSuccess', $this, $param);
	}

	public function onLabelFail($param) {
		$this->raiseEvent('OnLabelFail', $this, $param);
	}

	public function setSlots(array $slots) {
		$this->SlotsLabel->Text = implode(',', $slots);
	}

	public function setShowButton($show) {
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_BUTTON, $show);
	}

	public function getShowButton() {
		return $this->getViewState(self::SHOW_BUTTON, true);
	}

	public function setBarcodeLabel($barcode_label) {
		$barcode_label = TPropertyValue::ensureBoolean($barcode_label);
		$this->setViewState(self::BARCODE_LABEL, $barcode_label);
	}

	public function getBarcodeLabel() {
		return $this->getViewState(self::BARCODE_LABEL);
	}

	public function setStorage($storage) {
		$this->setViewState(self::STORAGE, $storage);
	}

	public function getStorage() {
		return $this->getViewState(self::STORAGE);
	}

	public function setPool($pool) {
		$this->setViewState(self::POOL, $pool);
	}

	public function getPool() {
		return $this->getViewState(self::POOL);
	}
}
?>
