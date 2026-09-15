<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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

use Bacularis\Common\Modules\Miscellaneous;
use Bacularis\Web\Modules\BaculumWebPage;
use Prado\Prado;

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
		$this->OMaxVolJobs->Text = Miscellaneous::html_value($volume->maxvoljobs);
		$this->OMaxVolBytes->Text = Miscellaneous::html_value($volume->maxvolbytes);
		$this->OMaxVolFiles->Text = Miscellaneous::html_value($volume->maxvolfiles);
		$this->OVolUseDuration->Text = Miscellaneous::html_value($volume->voluseduration);
		$this->OVolRetention->Text = Miscellaneous::html_value($volume->volretention);
		$this->ORecycle->Text = $volume->recycle === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OEnabled->Text = $volume->enabled === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OSlot->Text = Miscellaneous::html_value($volume->slot);
		$this->OInChanger->Text = $volume->inchanger === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OActionOnPurge->Text = $volume->actiononpurge === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->OScratchPool->Text = Miscellaneous::html_value($scratchpool);
		$this->ORecyclePool->Text = Miscellaneous::html_value($recyclepool);
		$this->ORecycleCount->Text = Miscellaneous::html_value($volume->recyclecount);
		$this->OVolJobs->Text = Miscellaneous::html_value($volume->voljobs);
		$this->OVolBytes->Text = Miscellaneous::html_value($volume->volbytes);
		$this->OVolFiles->Text = Miscellaneous::html_value($volume->volfiles);
		$this->OFirstWritten->Text = Miscellaneous::html_value($volume->firstwritten ?: '-');
		$this->OLastWritten->Text = Miscellaneous::html_value($volume->lastwritten ?: '-');
		$this->OVolStatus->Text = Miscellaneous::html_value($volume->volstatus);
		if (in_array($volume->volstatus, self::VOLSTATUS_ERROR)) {
			$this->OVolStatus->CssClass = 'w3-text-red';
		}
		$this->OWhenExpire->Text = Miscellaneous::html_value($volume->whenexpire);
		$this->OVolErrors->Text = Miscellaneous::html_value($volume->volerrors);
		$this->OVolMounts->Text = Miscellaneous::html_value($volume->volmounts);

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
			$volume_log = implode(PHP_EOL, $result->output);
		} else {
			$volume_log = $result->output;
		}
		$this->VolumeActionLog->Text = Miscellaneous::html_value($volume_log);
	}

	public function purge($sender, $param)
	{
		$result = $this->getModule('api')->set(
			['volumes', $this->getMediaId(), 'purge'],
			[]
		);
		if ($result->error === 0) {
			$volume_log = implode(PHP_EOL, $result->output);
		} else {
			$volume_log = $result->output;
		}
		$this->VolumeActionLog->Text = Miscellaneous::html_value($volume_log);
	}

	public function getNavData()
	{
		$mediaid = $this->getMediaId();
		$name = $this->getVolumeName();
		$params = [];
		if ($mediaid) {
			$params['mediaid'] = $mediaid;
		} elseif ($name) {
			$params['media'] = $name;
		}
		$page_url = $this->Service->constructUrl('VolumeView', $params);
		return [
			[
				'page' => 'Dashboard',
			],
			[
				'page' => 'VolumeList',
			],
			[
				'page' => 'VolumeView',
				'params' => $params,
				'label' => 'Volume details',
				'sub_label' => $name . ($mediaid ? sprintf(' [%s]', $mediaid) : ''),
				'icon' => 'fa-solid fa-file-lines fa-fw',
				'actions' => [
					[
						'address' => $page_url . '#volume_actions',
						'label' => 'Actions',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					],
					[
						'address' => $page_url . '#jobs_on_volume',
						'label' => 'Jobs on volume',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					],
					[
						'address' => $page_url . '#volume_config',
						'label' => 'Configure volume',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					]
				]
			]
		];
	}

}
