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
 * Storage list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class StorageList extends BaculumWebPage
{
	public const USE_CACHE = true;

	public $storages;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$result = $this->getModule('api')->get(['storages'], null, true, self::USE_CACHE);
		if ($result->error === 0) {
			$this->storages = $result->output;
		}
		$this->setDataViews();
	}

	private function setDataViews()
	{
		$storage_view_desc = [
			'name' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'autochanger' => ['type' => 'boolean', 'name' => Prado::localize('Autochanger')],
			'storageid' => ['type' => 'number', 'name' => 'StorageId']
		];
		$this->StorageViews->setViewName('storage_list');
		$this->StorageViews->setViewDataFunction('get_storage_list_data');
		$this->StorageViews->setUpdateViewFunction('update_storage_list_table');
		$this->StorageViews->setDescription($storage_view_desc);
	}
}
