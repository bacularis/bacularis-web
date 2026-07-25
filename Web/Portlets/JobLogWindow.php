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

/**
 * Display job log window.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class JobLogWindow extends Portlets
{
	public const JOBID = 'JobId';
	public const LOG_CSS = 'LogCSS';

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
