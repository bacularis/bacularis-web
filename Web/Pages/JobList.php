<?php
/*
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

Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('Application.Web.Class.BaculumWebPage'); 

/**
 * Job list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class JobList extends BaculumWebPage {

	const USE_CACHE = true;

	public $jobs;

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$result = $this->getModule('api')->get(
			['jobs', 'show', '?output=json'], null, true, self::USE_CACHE
		);
		$jobs = [];
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$jobs[] = [
					'job' => $result->output[$i]->name,
					'enabled' => $result->output[$i]->enabled,
					'priority' => $result->output[$i]->priority,
					'type' => chr($result->output[$i]->jobtype),
					'maxjobs' => $result->output[$i]->maxjobs
				];
			}
		}
		$this->jobs = $jobs;
	}

	public function loadRunJobModal($sender, $param) {
		$this->RunJobModal->loadData();
	}
}
