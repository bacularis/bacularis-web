<?php
/*
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

Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('Application.Web.Class.BaculumWebPage'); 

/**
 * Pool view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class PoolView extends BaculumWebPage {

	const USE_CACHE = true;

	const POOLID = 'PoolId';
	const POOL_NAME = 'PoolName';

	public $volumes_in_pool;

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$poolid = 0;
		if ($this->Request->contains('poolid')) {
			$poolid = $this->Request['poolid'];
		} elseif ($this->Request->contains('pool')) {
			$result = $this->getModule('api')->get(array('pools'));
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
		$poolshow = $this->getModule('api')->get(
			array('pools', $this->getPoolId(), 'show')
		);
		if ($poolshow->error === 0) {
			$this->PoolLog->Text = implode(PHP_EOL, $poolshow->output);
		}
	}

	public function onPreRender($param) {
		parent::onPreRender($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		if (!empty($_SESSION['dir'])) {
			$this->PoolConfig->setComponentName($_SESSION['dir']);
			$this->PoolConfig->setResourceName($this->getPoolName());
			$this->PoolConfig->setLoadValues(true);
			$this->PoolConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Set pool poolid.
	 *
	 * @return none;
	 */
	public function setPoolId($poolid) {
		$poolid = intval($poolid);
		$this->setViewState(self::POOLID, $poolid, 0);
	}

	/**
	 * Get pool poolid.
	 *
	 * @return integer poolid
	 */
	public function getPoolId() {
		return $this->getViewState(self::POOLID, 0);
	}

	/**
	 * Set pool name.
	 *
	 * @return none;
	 */
	public function setPoolName($pool_name) {
		$this->setViewState(self::POOL_NAME, $pool_name);
	}

	/**
	 * Get pool name.
	 *
	 * @return string pool name
	 */
	public function getPoolName() {
		return $this->getViewState(self::POOL_NAME);
	}

	public function setPool() {
		$pool = $this->Application->getModule('api')->get(
			array('pools', $this->getPoolId()),
			null,
			true,
			self::USE_CACHE
		)->output;

		$scratchpool = '-';
		if ($pool->scratchpoolid > 0) {
			$result = $this->getModule('api')->get(
				array('pools', $pool->scratchpoolid),
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
				array('pools', $pool->recyclepoolid),
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
				array('pools', $pool->nextpoolid),
				null,
				true,
				self::USE_CACHE
			);
			if ($result->error === 0) {
				$nextpool = $result->output->name;
			}
		}

		$this->setPoolName($pool->name);
		$this->MaxVols->Text = $pool->maxvols;
		$this->MaxVolJobs->Text = $pool->maxvoljobs;
		$this->MaxVolBytes->Text = $pool->maxvolbytes;
		$this->MaxVolFiles->Text = $pool->maxvolfiles;
		$this->VolUseDuration->Text = $pool->voluseduration;
		$this->VolRetention->Text = $pool->volretention;
		$this->Recycle->Text = $pool->recycle === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->AutoPrune->Text = $pool->autoprune === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->RecyclePool->Text = $recyclepool;
		$this->Enabled->Text = $pool->enabled === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->ActionOnPurge->Text = $pool->actiononpurge === 1 ? Prado::localize('Yes') : Prado::localize('No');
		$this->ScratchPool->Text = $scratchpool;
		$this->NextPool->Text = $nextpool;
	}

	public function setVolumesinPool() {
		$this->volumes_in_pool = $this->getModule('api')->get(
			array('pools', $this->getPoolId(), 'volumes'),
			null,
			true,
			self::USE_CACHE
		)->output;
	}

	public function updatePool($sender, $param) {
		$result = $this->getModule('api')->set(
			array('pools', $this->getPoolId(), 'update'),
			array()
		);
		$this->PoolLog->Text = implode(PHP_EOL, $result->output);
	}

	public function updateAllVolumesInPool($sender, $param) {
		$result = $this->getModule('api')->set(
			array('pools', $this->getPoolId(), 'update', 'volumes'),
			array()
		);
		if ($result->error == 0) {
			$this->PoolLog->Text = implode(PHP_EOL, $result->output);
		} else {
			$this->PoolLog->Text = $result->output;
		}
	}

	public function showAssignVolumesWarning($sender, $param) {
		$this->getCallbackClient()->show('pool_view_rename_resource');
	}
}
?>
