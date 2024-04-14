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

use Bacularis\Web\Modules\WebConfig;

/**
 * Resource monitor.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class ResourceMonitor extends Portlets
{
	public $job_age_on_job_status_graph = 0;

	public function onInit($param)
	{
		if (get_class($this->Service->getRequestedPage()) == 'Dashboard') {
			$web_config = $this->getModule('web_config')->getConfig();

			// set job age for job status summary graph
			if (isset($web_config['baculum']['job_age_on_job_status_graph'])) {
				$this->job_age_on_job_status_graph = $web_config['baculum']['job_age_on_job_status_graph'];
			} else {
				$this->job_age_on_job_status_graph = WebConfig::DEF_JOB_AGE_ON_JOB_STATUS_GRAPH;
			}
		}
	}
}
