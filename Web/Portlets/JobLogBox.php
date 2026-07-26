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

use Prado\Prado;
use Prado\TPropertyValue;
use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Portlets\Portlets;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;

/**
 * Display job log window.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Portlet
 */
class JobLogBox extends Portlets
{
	public const JOBID = 'JobId';
	public const AUTO_LOAD = 'AutoLoad';
	public const LOG_CSS = 'LogCSS';

	/**
	 * Sort order types.
	 */
	public const SORT_ASC = 0;
	public const SORT_DESC = 1;

	/**
	 * Load job log.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadLog($sender, $param)
	{
		$parameters = (array) $param->getCallbackParameter();
		$jobid = isset($parameters['jobid']) ? (int) $parameters['jobid'] : 0;
		$offset = isset($parameters['offset']) ? (int) $parameters['offset'] : 0;
		$limit = isset($parameters['limit']) ? (int) $parameters['limit'] : 0;
		$change_order = isset($parameters['change_order']) ? (bool) $parameters['change_order'] : false;
		if ($jobid < 1) {
			// Without jobid nothing is loaded
			return;
		}
		if ($change_order) {
			// Requested changing log order
			$this->changeLogOrder();
		}
		// Add log query parameters
		$query_params = [
			'offset' => $offset,
			'limit' => $limit
		];
		$wc = $this->getModule('web_config');
		$web_config = $wc->getConfig('baculum');
		if (key_exists('time_in_job_log', $web_config)) {
			$query_params['show_time'] = $web_config['time_in_job_log'];
		}
		$order_type = $this->getLogOrder();
		$query_params['order_type'] = ($order_type == self::SORT_ASC ? 'asc' : 'desc');
		$query_params['order_by'] = 'LogId';

		$query = '?' . http_build_query($query_params);
		$params = [
			'joblog',
			$jobid,
			$query
		];

		// Get job log
		$api = $this->getModule('api');
		$result = $api->get($params);
		$joblog = [];
		if ($result->error === 0) {
			if ($offset == 0 && (!is_array($result->output) || count($result->output) == 0)) {
				// Log response is OK but no job log record found
				$msg = Prado::localize("Output for selected job is not available yet or you do not have enabled logging job logs to the catalog database.\n\nTo watch job log you need to add to the job Messages resource the following directive:\n\nCatalog = all, !debug, !skipped, !saved");
				$joblog = [$msg];
			} else {
				// Log is avaialble
				$joblog = $result->output;
			}
		} else {
			// Error while getting job log - report it
			$emsg = sprintf(
				'Error while getting job log for JobId: %d, Error: %s',
				$jobid,
				$result->output
			);
			$joblog = [$emsg];
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}

		// Check if job is still running
		$params = ['jobs', $jobid];
		$job = $api->get($params);
		$jobstatus = $job->error == 0 ? $job->output->jobstatus : '';

		// Parse and provide the job log
		$log_parser = $this->getModule('log_parser');
		$log = $log_parser->parse($joblog);
		$log = implode(PHP_EOL, $log);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oJobLogBox' . $this->ClientID . '.set_log',
			[[
				'log' => $log,
				'joblog' => $joblog,
				'jobstatus' => $jobstatus,
				'order_type' => $order_type
			]]
		);

		$this->onLogRefresh(['joblog' => $joblog]);
	}

	public function onLogRefresh($param)
	{
		$parameter = new TCallbackEventParameter(null, $param);
		$this->raiseEvent('OnLogRefresh', $this, $parameter);
	}

	/**
	 * Set log order.
	 *
	 * @see JobLogBox::SORT_ASC and JobLogBox::SORT_DESC
	 * @param int $order order type
	 */
	private function setLogOrder($order): void
	{
		$order = TPropertyValue::ensureInteger($order);
		// set cookie for one year
		setcookie('log_order', $order, time() + 60 * 60 * 24 * 365, '/');
		$_COOKIE['log_order'] = $order;
	}

	/**
	 * Get current log order setting.
	 * Returned value represents the log order.
	 *
	 * @see JobLogBox::SORT_ASC and JobLogBox::SORT_DESC
	 * @return string log order
	 */
	private function getLogOrder()
	{
		return (key_exists('log_order', $_COOKIE) ? (int) ($_COOKIE['log_order']) : self::SORT_ASC);
	}

	/**
	 * Switch log order.
	 */
	private function changeLogOrder(): void
	{
		$order = $this->getLogOrder();
		if ($order === self::SORT_DESC) {
			$this->setLogOrder(self::SORT_ASC);
		} else {
			$this->setLogOrder(self::SORT_DESC);
		}
	}

	/**
	 * Set job log automatic loading.
	 *
	 * @param bool $auto_load load log automatically on load
	 */
	public function setAutoLoad($auto_load): void
	{
		$al = TPropertyValue::ensureBoolean($auto_load);
		$this->setViewState(self::AUTO_LOAD, $al);
	}

	/**
	 * Get job identifier to get job log.
	 *
	 * @return int job identifier
	 */
	public function getJobId(): int
	{
		return $this->getViewState(self::JOBID, 0);
	}

	/**
	 * Set job identifier to get job log.
	 *
	 * @param int $jobid job identifier
	 */
	public function setJobId($jobid): void
	{
		$jobid = TPropertyValue::ensureInteger($jobid);
		$this->setViewState(self::AUTO_LOAD, $jobid);
	}

	/**
	 * Get automatic job log loading
	 *
	 * @return bool true if job log should be loaded automatically (default true)
	 */
	public function getAutoLoad(): bool
	{
		return $this->getViewState(self::AUTO_LOAD, true);
	}

	/**
	 * Set log container CSS class.
	 *
	 * @param string $css CSS class
	 */
	public function setLogCSS($css): void
	{
		$this->setViewState(self::LOG_CSS, $css);
	}

	/**
	 * Get log container CSS class.
	 *
	 * @return string log container CSS class
	 */
	public function getLogCSS(): string
	{
		return $this->getViewState(self::LOG_CSS, '');
	}
}
