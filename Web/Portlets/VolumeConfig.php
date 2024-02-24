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

use Prado\Prado;
use Prado\TPropertyValue;
use Bacularis\Web\Portlets\Portlets;

/**
 * Volume config
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class VolumeConfig extends Portlets
{
	public const USE_CACHE = false;

	public const MEDIAID = 'MediaId';
	public const VOLUME_NAME = 'VolumeName';
	public const SAVE_VOLUME_ACTION_OK = 'SaveVolumeActionOk';
	public const DISPLAY_LOG = 'DisplayLog';

	private $volstatus_by_user = ['Append', 'Archive', 'Disabled', 'Full', 'Used', 'Cleaning', 'Read-Only'];

	/**
	 * Set volume mediaid.
	 *
	 * @param mixed $mediaid
	 */
	public function setMediaId($mediaid)
	{
		$mediaid = (int) $mediaid;
		$this->setViewState(self::MEDIAID, $mediaid, 0);
	}

	/**
	 * Get volume mediaid.
	 *
	 * @return int mediaid
	 */
	public function getMediaId()
	{
		return $this->getViewState(self::MEDIAID, 0);
	}

	/**
	 * Set volume name.
	 *
	 * @param mixed $volume_name
	 */
	public function setVolumeName($volume_name)
	{
		$this->setViewState(self::VOLUME_NAME, $volume_name);
	}

	/**
	 * Get volume name.
	 *
	 * @return string volume name
	 */
	public function getVolumeName()
	{
		return $this->getViewState(self::VOLUME_NAME);
	}

	public function setVolume()
	{
		$volume = $this->getModule('api')->get(
			['volumes', $this->getMediaId()],
			null,
			true,
			self::USE_CACHE
		)->output;
		$this->setVolumeName($volume->volumename);

		$volstatus = $this->volstatus_by_user;
		if (!in_array($volume->volstatus, $this->volstatus_by_user)) {
			array_push($volstatus, $volume->volstatus);
		}
		$this->VolumeStatus->DataSource = array_combine($volstatus, $volstatus);
		$this->VolumeStatus->SelectedValue = $volume->volstatus;
		$this->VolumeStatus->dataBind();
		$this->RetentionPeriod->setDirectiveValue($volume->volretention);
		$this->RetentionPeriod->createDirective();
		$this->UseDuration->setDirectiveValue($volume->voluseduration);
		$this->UseDuration->createDirective();
		$this->MaxVolJobs->Text = $volume->maxvoljobs;
		$this->MaxVolFiles->Text = $volume->maxvolfiles;
		$this->MaxVolBytes->setDirectiveValue($volume->maxvolbytes);
		$this->MaxVolBytes->createDirective();
		$this->Slot->Text = $volume->slot;
		$this->Recycle->Checked = ($volume->recycle === 1);
		$this->Enabled->Checked = ($volume->enabled === 1);
		$this->InChanger->Checked = ($volume->inchanger === 1);
		$pools = $this->Application->getModule('api')->get(['pools']);
		$pool_list = [];
		if ($pools->error === 0) {
			foreach ($pools->output as $pool) {
				$pool_list[$pool->poolid] = $pool->name;
			}
			natcasesort($pool_list);
		}
		$this->Pool->dataSource = $pool_list;
		$this->Pool->SelectedValue = $volume->poolid;
		$this->Pool->dataBind();
	}

	public function updateVolume($sender, $param)
	{
		$volume = [];
		$volume['mediaid'] = $this->getMediaId();
		$volume['volstatus'] = $this->VolumeStatus->SelectedValue;
		$volume['poolid'] = $this->Pool->SelectedValue;
		$volume['volretention'] = $this->RetentionPeriod->getValue();
		$volume['voluseduration'] = $this->UseDuration->getValue();
		$volume['maxvoljobs'] = $this->MaxVolJobs->Text;
		$volume['maxvolfiles'] = $this->MaxVolFiles->Text;
		$volume['maxvolbytes'] = $this->MaxVolBytes->getValue();
		$volume['slot'] = $this->Slot->Text;
		$volume['recycle'] = (int) $this->Recycle->Checked;
		$volume['enabled'] = (int) $this->Enabled->Checked;
		$volume['inchanger'] = (int) $this->InChanger->Checked;
		$result = $this->getModule('api')->set(
			['volumes', $volume['mediaid']],
			$volume
		);
		if ($result->error === 0) {
			$this->VolumeConfigLog->Text = implode(PHP_EOL, $result->output);
		} else {
			$this->VolumeConfigLog->Text = $result->output;
		}
		$this->setVolume();
		$this->onSave(null);
	}

	/**
	 * On save event fired when volume is saved.
	 *
	 * @param mixed $param
	 */
	public function onSave($param)
	{
		$this->raiseEvent('OnSave', $this, $param);
	}

	public function getSaveVolumeActionOK()
	{
		return $this->getViewState(self::SAVE_VOLUME_ACTION_OK, '');
	}

	public function setSaveVolumeActionOK($action_ok)
	{
		$this->setViewState(self::SAVE_VOLUME_ACTION_OK, $action_ok);
	}

	public function setDisplayLog($display)
	{
		$display = TPropertyValue::ensureBoolean($display);
		$this->setViewState(self::DISPLAY_LOG, $display);
	}

	public function getDisplayLog()
	{
		return $this->getViewState(self::DISPLAY_LOG, false);
	}
}
