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

/**
 * Main dashboard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class Dashboard extends BaculumWebPage
{
	public function loadRunJobModal($sender, $param)
	{
		$this->RunJobModal->loadData();
	}

	public function getNavData()
	{
		$page_url = $this->Service->constructUrl('Dashboard');
		return [
			[
				'page' => 'Dashboard',
				'label' => 'Dashboard',
				'icon' => 'fa-solid fa-tachometer-alt fa-fw',
				'actions' => [
					[
						'address' => $page_url . '#btn_run_job',
						'label' => 'Run job',
						'icon' => 'fa-solid fa-cogs fa-fw'
					],
					[
						'address' => $page_url . '#dashboard_recent_jobs_subtab',
						'label' => 'Recent jobs',
						'icon' => 'fa-solid fa-tasks fa-fw'
					],
					[
						'address' => $page_url . '#dashboard_scheduled_today_jobs_subtab',
						'label' => 'Scheduled for today',
						'icon' => 'fa-solid fa-clock fa-fw'
					],
					[
						'address' => $page_url . '#dashboard_scheduled_days_jobs_subtab',
						'label' => 'Scheduled for next 5 days',
						'icon' => 'fa-solid fa-clock fa-fw'
					]
				]
			]
		];
	}
}
