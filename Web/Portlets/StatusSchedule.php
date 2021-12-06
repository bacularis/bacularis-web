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

Prado::using('System.Web.UI.JuiControls.TJuiDatePicker');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('Application.Web.Portlets.Portlets');

/**
 * Schedule status control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class StatusSchedule extends Portlets {

	/**
	 * Default days limit.
	 */
	const DEF_DAYS = 90;

	const JOB = 'Job';
	const CLIENT = 'Client';
	const SCHEDULE = 'Schedule';
	const DAYS = 'Days';
	const LIMIT = 'Limit';
	const TIME = 'Time';
	const SHOW_CLIENT_FILTER = 'ShowClientFilter';
	const SHOW_SCHEDULE_FILTER = 'ShowScheduleFilter';

	public $schedules = array();

	public function onLoad($param) {
		parent::onLoad($param);
		if ($this->getPage()->IsCallBack || $this->getPage()->IsPostBack) {
			return;
		}
		$this->loadSchedules();
		$this->Days->Text = self::DEF_DAYS;
		$this->DatePicker->setDate(date('Y-m-d'));

		if ($this->getShowClientFilter()) {
			$clients = $this->getClients();
			$this->Client->DataSource = array_combine($clients, $clients);
			$this->Client->dataBind();
		} else {
			$this->Client->Visible = false;
		}

		if ($this->getShowScheduleFilter()) {
			$schedules = $this->getSchedules();
			$this->Schedule->DataSource = array_combine($schedules, $schedules);
			$this->Schedule->dataBind();
		} else {
			$this->Schedule->Visible = false;
		}
	}

	public function loadSchedules() {
		$query = array();
		$job = $this->getJob();
		if (!empty($job)) {
			$query[] = 'job=' . rawurlencode($job);
		}
		$client = $this->getClient();
		if (!empty($client)) {
			$query[] = 'client=' . rawurlencode($client);
		}
		$schedule = $this->getSchedule();
		if (!empty($schedule)) {
			$query[] = 'schedule=' . rawurlencode($schedule);
		}
		$days = $this->getDays();
		if (!empty($days)) {
			$query[] = 'days=' . $days;
		} else {
			$query[] = 'days=' . self::DEF_DAYS;
		}
		$limit = $this->getLimit();
		if (!empty($limit)) {
			$query[] = 'limit=' . $limit;
		} else {
			$query[] = 'limit=0';
		}
		$time = $this->getTime();
		if (!empty($time)) {
			$query[] = 'time=' . rawurlencode($time);
		}
		$params = array('schedules', 'status');
		if (count($query) > 0) {
			$params[] = '?' . implode('&', $query);
		}

		$result = $this->getModule('api')->get($params);
		if ($result->error === 0) {
			$schedules = $result->output;
			if ($this->getPage()->IsCallBack) {
				$this->getPage()->getCallbackClient()->callClientFunction(
					'set_job_schedule_data',
					json_encode($schedules)
				);
				$this->getPage()->getCallbackClient()->callClientFunction(
					'init_job_schedule'
				);
			} else {
				$this->schedules = $schedules;
			}
		}
	}

	public function getClients() {
		$clients = array('');
		$result = $this->getModule('api')->get(array('clients'));
		if ($result->error === 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$clients[] = $result->output[$i]->name;
			}
		}
		return $clients;
	}

	public function getSchedules() {
		$schedules = array();
		$result = $this->getModule('api')->get(array('schedules', 'resnames'));
		if ($result->error === 0) {
			$schedules = $result->output;
			array_unshift($schedules, '');
		}
		return $schedules;
	}

	public function applyFilters($sender, $param) {
		$time = $this->DatePicker->getDate() . ' 00:00:00';
		$this->setTime($time);

		$days = intval($this->Days->Text);
		$this->setDays($days);

		if ($this->getShowClientFilter()) {
			$this->setClient($this->Client->SelectedValue);
		}

		if ($this->getShowScheduleFilter()) {
			$this->setSchedule($this->Schedule->SelectedValue);
		}

		$this->loadSchedules();
	}

	public function setJob($job) {
		$this->setViewState(self::JOB, $job);
	}

	public function getJob() {
		return $this->getViewState(self::JOB);
	}


	public function setClient($client) {
		$this->setViewState(self::CLIENT, $client);
	}

	public function getClient() {
		return $this->getViewState(self::CLIENT);
	}

	public function setSchedule($schedule) {
		$this->setViewState(self::SCHEDULE, $schedule);
	}

	public function getSchedule() {
		return $this->getViewState(self::SCHEDULE);
	}

	public function setDays($days) {
		$this->setViewState(self::DAYS, $days);
	}

	public function getDays() {
		return $this->getViewState(self::DAYS);
	}

	public function setLimit($limit) {
		$this->setViewState(self::LIMIT, $limit);
	}

	public function getLimit() {
		return $this->getViewState(self::LIMIT);
	}

	public function setTime($time) {
		$this->setViewState(self::TIME, $time);
	}

	public function getTime() {
		return $this->getViewState(self::TIME);
	}

	public function setShowClientFilter($show) {
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_CLIENT_FILTER, $show);
	}

	public function getShowClientFilter() {
		return $this->getViewState(self::SHOW_CLIENT_FILTER, false);
	}

	public function setShowScheduleFilter($show) {
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_SCHEDULE_FILTER, $show);
	}

	public function getShowScheduleFilter() {
		return $this->getViewState(self::SHOW_SCHEDULE_FILTER, false);
	}
}
?>
