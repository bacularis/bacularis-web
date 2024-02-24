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
 * Copyright (C) 2013-2020 Kern Sibbald
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
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Volume view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class VolumeView extends BaculumWebPage
{
	public const USE_CACHE = false;

	public const MEDIAID = 'MediaId';
	public const VOLUME_NAME = 'VolumeName';
	private const VOLSTATUS_ERROR = ['Error'];

	public $jobs_on_volume;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}

		$mediaid = 0;
		if ($this->Request->contains('mediaid')) {
			$mediaid = (int) ($this->Request['mediaid']);
		} elseif ($this->Request->contains('media')) {
			$result = $this->getModule('api')->get(['volumes']);
			if ($result->error === 0) {
				for ($i = 0; $i < count($result->output); $i++) {
					if ($this->Request['media'] === $result->output[$i]->volumename) {
						$mediaid = $result->output[$i]->mediaid;
						break;
					}
				}
			}
		}
		$this->setMediaId($mediaid);
		$this->setVolume();
		$this->VolumeConfig->setMediaId($mediaid);
		$this->VolumeConfig->setVolume();
	}

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
		$scratchpool = '-';
		if ($volume->scratchpoolid > 0) {
			$result = $this->getModule('api')->get(
				['pools', $volume->scratchpoolid],
				null,
				true,
				self::USE_CACHE
			)->output;
			$scratchpool = $result->name;
		}

		$recyclepool = '-';
		if ($volume->recyclepoolid === $volume->scratchpoolid) {
			$recyclepool = $scratchpool;
		} else {
			$result = $this->getModule('api')->get(
				['pools', $volume->recyclepoolid],
				null,
				true,
				self::USE_CACHE
			)->output;
			$recyclepool = $result->name;
		}
		$this->OMaxVolJobs->Text = $volume->maxvoljobs;
		$this->OMaxVolBytes->Text = $volume->maxvolbytes;
		$this->OMaxVolFiles->Text = $volume->maxvolfiles;
		$this->OVolUseDuration->Text = $volume->voluseduration;
		$this->OVolRetention->Text = $volume->volretention;
		$this->ORecycle->Text = $volume->recycle === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OEnabled->Text = $volume->enabled === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OSlot->Text = $volume->slot;
		$this->OInChanger->Text = $volume->inchanger === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OActionOnPurge->Text = $volume->actiononpurge === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OScratchPool->Text = $scratchpool;
		$this->ORecyclePool->Text = $recyclepool;
		$this->ORecycleCount->Text = $volume->recyclecount;
		$this->OVolJobs->Text = $volume->voljobs;
		$this->OVolBytes->Text = $volume->volbytes;
		$this->OVolFiles->Text = $volume->volfiles;
		$this->OFirstWritten->Text = $volume->firstwritten ?: '-';
		$this->OLastWritten->Text = $volume->lastwritten ?: '-';
		$this->OVolStatus->Text = $volume->volstatus;
		if (in_array($volume->volstatus, self::VOLSTATUS_ERROR)) {
			$this->OVolStatus->CssClass = 'w3-text-red';
		}
		$this->OWhenExpire->Text = $volume->whenexpire;
		$this->OVolErrors->Text = $volume->volerrors;
		$this->OVolMounts->Text = $volume->volmounts;

		// Load jobs on volume list
		$this->VolumeJobList->setMediaId($volume->mediaid);
		$this->VolumeJobList->loadJobs(null, null);
	}

	public function prune($sender, $param)
	{
		$result = $this->getModule('api')->set(
			['volumes', $this->getMediaId(), 'prune'],
			[]
		);
		if ($result->error === 0) {
			$this->VolumeActionLog->Text = implode(PHP_EOL, $result->output);
		} else {
			$this->VolumeActionLog->Text = $result->output;
		}
	}

	public function purge($sender, $param)
	{
		$result = $this->getModule('api')->set(
			['volumes', $this->getMediaId(), 'purge'],
			[]
		);
		if ($result->error === 0) {
			$this->VolumeActionLog->Text = implode(PHP_EOL, $result->output);
		} else {
			$this->VolumeActionLog->Text = $result->output;
		}
	}
}
