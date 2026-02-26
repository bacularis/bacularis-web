<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Bacularis\Web\Modules\BaculumWebPage;

class AddOns extends BaculumWebPage
{
	public function getNavData()
	{
		$page_url = $this->Service->constructUrl('AddOns');
		return [
			[
				'page' => 'Dashboard'
			],
			[
				'page' => 'AddOns',
				'label' => 'Add-ons',
				'icon' => 'fa-solid fa-puzzle-piece fa-fw',
				'actions' => [
					[
						'address' => $page_url . '#plugins',
						'label' => 'Plugins',
						'icon' => 'fa-solid fa-table-columns fa-fw'
					]
				]
			]
		];
	}
}
