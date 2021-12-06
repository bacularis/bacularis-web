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

Prado::using('Application.Web.Class.BaculumWebPage'); 

/**
 * Schedule view page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 * @package Baculum Web
 */
class ScheduleView extends BaculumWebPage {

	const USE_CACHE = true;

	const SCHEDULE_NAME = 'ScheduleName';

	public function onInit($param) {
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		if ($this->Request->contains('schedule')) {
			$this->setScheduleName($this->Request['schedule']);
		}
	}

	public function onPreRender($param) {
		parent::onPreRender($param);
		if ($this->IsCallBack || $this->IsPostBack) {
			return;
		}
		if (!empty($_SESSION['dir'])) {
			$this->ScheduleConfig->setComponentName($_SESSION['dir']);
			$this->ScheduleConfig->setResourceName($this->getScheduleName());
			$this->ScheduleConfig->setLoadValues(true);
			$this->ScheduleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	/**
	 * Set schedule name.
	 *
	 * @return none;
	 */
	public function setScheduleName($schedule_name) {
		$this->setViewState(self::SCHEDULE_NAME, $schedule_name);
	}

	/**
	 * Get schedule name.
	 *
	 * @return string schedule name
	 */
	public function getScheduleName() {
		return $this->getViewState(self::SCHEDULE_NAME);
	}
}
?>
