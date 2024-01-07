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

use Prado\Prado;

/**
 * Assign volumes to pool control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AssignVolumesToPool extends Portlets
{
	public const POOL_ID = 'PoolId';

	/**
	 * Set pool identifier.
	 *
	 * @param mixed $pool
	 */
	public function setPoolId($pool)
	{
		settype($pool, 'integer');
		$this->setViewState(self::POOL_ID, $pool);
	}

	/**
	 * Get pool identifier.
	 *
	 * @return int pool identifier
	 */
	public function getPoolId()
	{
		return $this->getViewState(self::POOL_ID, 0);
	}

	public function loadValues()
	{
		$pools = $this->Application->getModule('api')->get(['pools']);
		$pool_list = [];
		if ($pools->error === 0) {
			$curr_poolid = $this->getPoolId();
			foreach ($pools->output as $pool) {
				if ($pool->poolid == $curr_poolid) {
					continue;
				}
				$pool_list[$pool->poolid] = $pool->name;
			}
		}
		$this->Pool->DataSource = $pool_list;
		$this->Pool->dataBind();
	}

	public function loadVolumeList($sender, $param)
	{
		$poolid = $this->Pool->SelectedValue;
		$volumes = $this->getModule('api')->get(
			['pools', $poolid, 'volumes']
		);
		if ($volumes->error === 0) {
			if (count($volumes->output) > 0) {
				$this->getPage()->getCallbackClient()->callClientFunction(
					'oAssignVolumesToPool.load_volume_list_cb',
					[$volumes->output]
				);
			} else {
				$this->AssignVolumesLog->Text .= PHP_EOL . Prado::localize('No volumes in the pool to assign.');
				$this->getPage()->getCallbackClient()->show('assign_volumes_log');
				$this->getPage()->getCallbackClient()->callClientFunction(
					'oAssignVolumesToPool.show_loader',
					[false]
				);
			}
		}
	}

	public function assignVolume($sender, $param)
	{
		$mediaid = $param->getCallbackParameter();
		if (!is_numeric($mediaid) || $mediaid <= 0) {
			return;
		}
		$mediaid = (int) $mediaid;
		if ($mediaid === 0) {
			return;
		}
		$poolid = $this->getPoolId();
		$result = $this->getModule('api')->set(
			['volumes', $mediaid],
			['poolid' => $poolid]
		);
		if ($result->error === 0) {
			$this->getPage()->getCallbackClient()->callClientFunction('oAssignVolumesToPool.set_logbox_scroll');
			$this->AssignVolumesLog->Text .= PHP_EOL . implode(PHP_EOL, $result->output);
			$this->getPage()->getCallbackClient()->show('assign_volumes_log');
			// try to assign next volume
			$this->getPage()->getCallbackClient()->callClientFunction('oAssignVolumesToPool.assign_volume');
		}
	}
}
