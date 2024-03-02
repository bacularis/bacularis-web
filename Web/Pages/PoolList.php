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
 * Pool list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class PoolList extends BaculumWebPage
{
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->setDataViews();
	}

	private function setDataViews()
	{
		$pool_view_desc = [
			'name' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'numvols' => ['type' => 'number', 'name' => Prado::localize('No. vols')],
			'maxvols' => ['type' => 'number', 'name' => Prado::localize('Max. vols')],
			'poolid' => ['type' => 'number', 'name' => 'PoolId'],
			'autoprune' => ['type' => 'boolean', 'name' => Prado::localize('AutoPrune')],
			'recycle' => ['type' => 'boolean', 'name' => Prado::localize('Recycle')]
		];
		$this->PoolViews->setViewName('pool_list');
		$this->PoolViews->setViewDataFunction('get_pool_list_data');
		$this->PoolViews->setUpdateViewFunction('update_pool_list_table');
		$this->PoolViews->setDescription($pool_view_desc);
	}
}
