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

namespace Bacularis\Web\Portlets;

/**
 * Job history range slider control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class JobHistoryRangeSlider extends Portlets
{
	public const JOB_NAME = 'JobName';
	public const DAYS = 'Days';

	private const DEFAULT_DAYS = 30;

	public function onLoad($param)
	{
		parent::onLoad($param);
	}

	public function setJobName($job_name)
	{
		$this->setViewState(self::JOB_NAME, $job_name, '');
	}

	public function getJobName()
	{
		return $this->getViewState(self::JOB_NAME);
	}

	public function setDays($days)
	{
		$this->setViewState(self::DAYS, $days, self::DEFAULT_DAYS);
	}

	public function getDays()
	{
		return $this->getViewState(self::DAYS);
	}

	public function loadJobHistory()
	{
		$result = $this->getModule('api')->get([
			'jobs', '?name=' . rawurlencode($this->getJobName())
		]);
		if ($result->error === 0) {
			$this->getPage()->getCallbackClient()->callClientFunction(
				$this->ClientID . '_job_history_range_slider_obj.set_job_history',
				[$result->output]
			);
		}
	}
}
