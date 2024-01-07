<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

/**
 * Display job log window.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class JobLog extends Portlets
{
	public const JOBID = 'JobId';

	/**
	 * Load job log.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadJobLog($sender, $param)
	{
		$jobid = (int) $param->getCallbackParameter();
		if ($jobid < 1) {
			// without jobid nothing is loaded
			return;
		}
		$params = ['joblog', $jobid];

		// add time to log if defiend in configuration
		$web_config = $this->getModule('web_config')->getConfig('baculum');
		if (key_exists('time_in_job_log', $web_config)) {
			$query_params = [
				'show_time' => $web_config['time_in_job_log']
			];
			$params[] = '?' . http_build_query($query_params);
		}
		$result = $this->getModule('api')->get($params);
		$joblog = [];
		if ($result->error === 0) {
			if (!is_array($result->output) || count($result->output) == 0) {
				$msg = Prado::localize("Output for selected job is not available yet or you do not have enabled logging job logs to the catalog database.\n\nTo watch job log you need to add to the job Messages resource the following directive:\n\nCatalog = all, !debug, !skipped, !saved");
				$joblog = [$msg];
			} else {
				$joblog = $result->output;
			}
		} else {
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
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_ACTION,
				$emsg
			);
		}
		$log = $this->getModule('log_parser')->parse($joblog);
		$log = implode(PHP_EOL, $log);
		$this->getPage()->getCallbackClient()->callClientFunction(
			'oJobLogWindow.set_log',
			[$log, $joblog]
		);
	}

	/**
	 * Set job identifier to get job log.
	 *
	 * @param int $jobid job identifier
	 */
	public function setJobId($jobid)
	{
		$jobid = TPropertyValue::ensureInteger($jobid);
		$this->setViewState(self::JOBID, $jobid);
	}

	/**
	 * Get job identifier to get job log.
	 *
	 * @return int job identifier
	 */
	public function getJobId()
	{
		return $this->getViewState(self::JOBID, 0);
	}
}
