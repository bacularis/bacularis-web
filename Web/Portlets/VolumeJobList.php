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

use Prado\TPropertyValue;
use Bacularis\Web\Portlets\Portlets;

/**
 * Volume job list.
 * Show which jobs are stored on given volume.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class VolumeJobList extends Portlets
{
	public const MEDIAID = 'MediaId';

	/**
	 * Load volume job list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadJobs($sender, $param)
	{
		$mediaid = $this->getMediaId();
		if ($mediaid < 1) {
			// without mediaid nothing is loaded
			return;
		}
		$result = $this->getModule('api')->get([
			'volumes',
			$mediaid,
			'jobs'
		]);
		if ($result->error === 0) {
			$jobs = $result->output;
			$this->getPage()->getCallbackClient()->callClientFunction(
				'oJobsOnVolumeList.update',
				[$jobs]
			);
		}
	}

	/**
	 * Set media identifier to get jobs on the volume.
	 *
	 * @param int $mediaid media identifier
	 */
	public function setMediaId($mediaid)
	{
		$mediaid = TPropertyValue::ensureInteger($mediaid);
		$this->setViewState(self::MEDIAID, $mediaid);
	}

	/**
	 * Get media identifier to get jobs on the volume.
	 *
	 * @return int media identifier
	 */
	public function getMediaId()
	{
		return $this->getViewState(self::MEDIAID, 0);
	}
}
