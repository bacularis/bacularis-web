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

use Bacularis\Web\Modules\BaculumWebPage;
use Prado\Prado;

/**
 * Volume list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class VolumeList extends BaculumWebPage
{
	public const USE_CACHE = false;

	public $volumes = [];
	public $pools = [];

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->volumes = $this->getVolumes();
		$this->pools = $this->getPools();
		$this->setDataViews();
	}

	private function setDataViews()
	{
		$volume_view_desc = [
			'volumename' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'volstatus' => ['type' => 'string', 'name' => Prado::localize('Vol. status')],
			'mediatype' => ['type' => 'string', 'name' => 'MediaType'],
			'pool' => ['type' => 'string', 'name' => Prado::localize('Pool')],
			'scratchpool' => ['type' => 'string', 'name' => Prado::localize('Scratch pool')],
			'recyclepool' => ['type' => 'string', 'name' => Prado::localize('Recycle pool')],
			'storage' => ['type' => 'string', 'name' => Prado::localize('Storage')],
			'slot' => ['type' => 'number', 'name' => Prado::localize('Slot')],
			'inchanger' => ['type' => 'boolean', 'name' => Prado::localize('InChanger')],
			'firstwritten' => ['type' => 'isodatetime', 'name' => Prado::localize('First written')],
			'lastwritten' => ['type' => 'isodatetime', 'name' => Prado::localize('Last written')],
			'volerrors' => ['type' => 'number', 'name' => Prado::localize('Vol. errors')],
			'volbytes' => ['type' => 'number', 'name' => Prado::localize('Vol. bytes')],
			'voljobs' => ['type' => 'number', 'name' => Prado::localize('Vol. jobs')],
			'volfiles' => ['type' => 'number', 'name' => Prado::localize('Vol. files')],
			'volblocks' => ['type' => 'number', 'name' => Prado::localize('Vol. blocks')],
			'volmounts' => ['type' => 'number', 'name' => Prado::localize('Vol. mounts')],
			'maxvoljobs' => ['type' => 'number', 'name' => Prado::localize('Max. vol. jobs')],
			'maxvolbytes' => ['type' => 'number', 'name' => Prado::localize('Max. vol. bytes')],
			'maxvolfiles' => ['type' => 'number', 'name' => Prado::localize('Max. vol. files')],
			'recycle' => ['type' => 'boolean', 'name' => Prado::localize('Recycle')],
			'enabled' => ['type' => 'boolean', 'name' => Prado::localize('Enabled')],
			'mediaid' => ['type' => 'number', 'name' => 'MediaId']
		];
		$this->VolumeViews->setViewName('volume_list');
		$this->VolumeViews->setViewDataFunction('get_volume_list_data');
		$this->VolumeViews->setUpdateViewFunction('update_volume_list_table');
		$this->VolumeViews->setDescription($volume_view_desc);
	}

	public function getVolumes()
	{
		$volumes = $this->getModule('api')->get(
			['volumes'],
			null,
			true,
			self::USE_CACHE
		);
		$ret = [];
		if ($volumes->error === 0) {
			$ret = $volumes->output;
		}
		return $ret;
	}

	public function getPools()
	{
		$pools = $this->getModule('api')->get(
			['pools'],
			null,
			true,
			self::USE_CACHE
		);
		$ret = [];
		if ($pools->error === 0) {
			$ret = $pools->output;
			$cb = function ($a, $b) {
				return strnatcasecmp($a->name, $b->name);
			};
			usort($ret, $cb);
		}
		return $ret;
	}

	/**
	 * Prune multiple volumes.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function pruneVolumes($sender, $param)
	{
		$result = [];
		$mediaids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($mediaids); $i++) {
			$ret = $this->getModule('api')->set(
				['volumes', (int) ($mediaids[$i]), 'prune']
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update($this->BulkActions->BulkActionsOutput, implode(PHP_EOL, $result));
		$this->updateVolumes($sender, $param);
	}

	/**
	 * Purge multiple volumes.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function purgeVolumes($sender, $param)
	{
		$result = [];
		$mediaids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($mediaids); $i++) {
			$ret = $this->getModule('api')->set(
				['volumes', (int) ($mediaids[$i]), 'purge']
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update($this->BulkActions->BulkActionsOutput, implode(PHP_EOL, $result));
		$this->updateVolumes($sender, $param);
	}

	/**
	 * Delete multiple volumes.
	 * Used for bulk actions.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function deleteVolumes($sender, $param)
	{
		$result = [];
		$mediaids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($mediaids); $i++) {
			$ret = $this->getModule('api')->remove(
				['volumes', (int) ($mediaids[$i])]
			);
			if ($ret->error !== 0) {
				$result[] = $ret->output;
				break;
			}
			$result[] = implode(PHP_EOL, $ret->output);
		}
		$this->getCallbackClient()->update($this->BulkActions->BulkActionsOutput, implode(PHP_EOL, $result));
		$this->updateVolumes($sender, $param);
	}

	/**
	 * Update volumes callback.
	 * It updates volume table data.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function updateVolumes($sender, $param)
	{
		$volumes = $this->getVolumes();
		$this->getCallbackClient()->callClientFunction('set_volume_list_data', [$volumes]);
	}
}
