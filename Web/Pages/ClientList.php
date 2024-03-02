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
 * Copyright (C) 2013-2019 Kern Sibbald
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
 * Client list page.
 *
 * @category Page
 */
class ClientList extends BaculumWebPage
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
		$client_view_desc = [
			'name' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'uname' => ['type' => 'string', 'name' => Prado::localize('Uname')],
			'clientid' => ['type' => 'number', 'name' => 'ClientId'],
			'autoprune' => ['type' => 'boolean', 'name' => Prado::localize('AutoPrune')]
		];
		$this->ClientViews->setViewName('client_list');
		$this->ClientViews->setViewDataFunction('get_client_list_data');
		$this->ClientViews->setUpdateViewFunction('update_client_list_table');
		$this->ClientViews->setDescription($client_view_desc);
	}
}
