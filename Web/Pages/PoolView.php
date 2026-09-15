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
 * Pool view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class PoolView extends BaculumWebPage
{
	public const USE_CACHE = true;

	public const POOLID = 'PoolId';
	public const POOL_NAME = 'PoolName';

	public $volumes_in_pool;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$poolid = 0;
		if ($this->Request->contains('poolid')) {
			$poolid = $this->Request['poolid'];
		} elseif ($this->Request->contains('pool')) {
			$result = $this->getModule('api')->get(['pools']);
			if ($result->error === 0) {
				for ($i = 0; $i < count($result->output); $i++) {
					if ($this->Request['pool'] === $result->output[$i]->name) {
						$poolid = $result->output[$i]->poolid;
						break;
					}
				}
			}
		}
		$this->setPoolId($poolid);
		$this->setPool();
		$this->setVolumesinPool();
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		$sess = $this->getApplication()->getSession();
		$component_name = $sess->itemAt('dir');
		if ($component_name) {
			$this->PoolConfig->setComponentName($component_name);
			$this->PoolConfig->setResourceName($this->getPoolName());
			$this->PoolConfig->setLoadValues(true);
			$this->PoolConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Set pool poolid.
	 *
	 * @param mixed $poolid
	 */
	public function setPoolId($poolid)
	{
		$poolid = (int) $poolid;
		$this->setViewState(self::POOLID, $poolid, 0);
	}

	/**
	 * Get pool poolid.
	 *
	 * @return int poolid
	 */
	public function getPoolId()
	{
		return $this->getViewState(self::POOLID, 0);
	}

	/**
	 * Set pool name.
	 *
	 * @param mixed $pool_name
	 */
	public function setPoolName($pool_name)
	{
		$this->setViewState(self::POOL_NAME, $pool_name);
	}

	/**
	 * Get pool name.
	 *
	 * @return string pool name
	 */
	public function getPoolName()
	{
		return $this->getViewState(self::POOL_NAME);
	}

	/**
	 * Get pool ID as JSON safe for JavaScript context.
	 *
	 * @return string pool ID JSON
	 */
	public function getPoolIdJSON(): string
	{
		$pool_id = $this->getPoolId();
		return Miscellaneous::json_value($pool_id);
	}

	/**
	 * Get pool name as JSON safe for JavaScript context.
	 *
	 * @return string pool name JSON
	 */
	public function getPoolNameJSON(): string
	{
		$pool_name = $this->getPoolName();
		return Miscellaneous::json_value($pool_name);
	}

	/**
	 * Get volumes as JSON safe for JavaScript context.
	 *
	 * @return string volumes JSON
	 */
	public function getVolumesJSON(): string
	{
		return Miscellaneous::json_value($this->volumes_in_pool);
	}

	public function setPool()
	{
		$pool = $this->Application->getModule('api')->get(
			['pools', $this->getPoolId()],
			null,
			true,
			self::USE_CACHE
		)->output;

		$scratchpool = '-';
		if ($pool->scratchpoolid > 0) {
			$result = $this->getModule('api')->get(
				['pools', $pool->scratchpoolid],
				null,
				true,
				self::USE_CACHE
			);
			if ($result->error === 0) {
				$scratchpool = $result->output->name;
			}
		}

		$recyclepool = '-';
		if ($pool->recyclepoolid === $pool->scratchpoolid) {
			$recyclepool = $scratchpool;
		} elseif ($pool->recyclepoolid > 0) {
			$result = $this->getModule('api')->get(
				['pools', $pool->recyclepoolid],
				null,
				true,
				self::USE_CACHE
			);
			if ($result->error === 0) {
				$recyclepool = $result->output->name;
			}
		}
		$nextpool = '-';
		if ($pool->nextpoolid === $pool->scratchpoolid) {
			$nextpool = $scratchpool;
		} elseif ($pool->nextpoolid === $pool->recyclepoolid) {
			$nextpool = $recyclepool;
		} elseif ($pool->nextpoolid > 0) {
			$result = $this->getModule('api')->get(
				['pools', $pool->nextpoolid],
				null,
				true,
				self::USE_CACHE
			);
			if ($result->error === 0) {
				$nextpool = $result->output->name;
			}
		}

		$this->setPoolName($pool->name);
		$this->MaxVols->Text = Miscellaneous::html_value($pool->maxvols);
		$this->MaxVolJobs->Text = Miscellaneous::html_value($pool->maxvoljobs);
		$this->MaxVolBytes->Text = Miscellaneous::html_value($pool->maxvolbytes);
		$this->MaxVolFiles->Text = Miscellaneous::html_value($pool->maxvolfiles);
		$this->VolUseDuration->Text = Miscellaneous::html_value($pool->voluseduration);
		$this->VolRetention->Text = Miscellaneous::html_value($pool->volretention);
		$this->Recycle->Text = $pool->recycle === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->AutoPrune->Text = $pool->autoprune === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->RecyclePool->Text = Miscellaneous::html_value($recyclepool);
		$this->Enabled->Text = $pool->enabled === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->ActionOnPurge->Text = $pool->actiononpurge === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->ScratchPool->Text = Miscellaneous::html_value($scratchpool);
		$this->NextPool->Text = Miscellaneous::html_value($nextpool);
	}

	public function setVolumesinPool()
	{
		$this->volumes_in_pool = $this->getModule('api')->get(
			['pools', $this->getPoolId(), 'volumes'],
			null,
			true,
			self::USE_CACHE
		)->output;
	}

	public function updatePool($sender, $param)
	{
		$result = $this->getModule('api')->set(
			['pools', $this->getPoolId(), 'update'],
			[]
		);
		$pool_log = implode(PHP_EOL, $result->output);
		$this->PoolLog->Text = Miscellaneous::html_value($pool_log);
		$this->getCallbackClient()->show('pool_log');
	}

	public function updateAllVolumesInPool($sender, $param)
	{
		$result = $this->getModule('api')->set(
			['pools', $this->getPoolId(), 'update', 'volumes'],
			[]
		);
		if ($result->error == 0) {
			$pool_log = implode(PHP_EOL, $result->output);
		} else {
			$pool_log = $result->output;
		}
		$this->PoolLog->Text = Miscellaneous::html_value($pool_log);
		$this->getCallbackClient()->show('pool_log');
	}

	public function showAssignVolumesWarning($sender, $param)
	{
		$this->getCallbackClient()->show('pool_view_rename_resource');
	}

	public function getNavData()
	{
		$poolid = $this->getPoolId();
		$name = $this->getPoolName();
		$params = [];
		if ($poolid) {
			$params['poolid'] = $poolid;
		}
		if ($name) {
			$params['pool'] = $name;
		}
		$page_url = $this->Service->constructUrl('PoolView', $params);
		return [
			[
				'page' => 'Dashboard',
			],
			[
				'page' => 'PoolList',
			],
			[
				'page' => 'PoolView',
				'params' => $params,
				'label' => 'Pool details',
				'sub_label' => $name . ($poolid ? sprintf(' [%s]', $poolid) : ''),
				'icon' => 'fa-solid fa-file-lines fa-fw',
				'actions' => [
					[
						'address' => $page_url . '#pool_actions',
						'label' => 'Actions',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					],
					[
						'address' => $page_url . '#pool_graphs',
						'label' => 'Graphs',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					],
					[
						'address' => $page_url . '#volumes_in_pool',
						'label' => 'Volumes in pool',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					],
					[
						'address' => $page_url . '#pool_config',
						'label' => 'Configure pool',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					]
				]
			]
		];
	}


	public function isDirConfigVisible(): bool
	{
		return ($this->getApplication()->getSession()->itemAt('dir') ? true : false);
	}
}
