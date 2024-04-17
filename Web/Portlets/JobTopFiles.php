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
 * Top 10 file list control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class JobTopFiles extends Portlets
{
	public const JOBID = 'JobId';

	private const NB_TOPS_TO_DISPLAY = 10;

	public function loadTopFileList($sender, $param)
	{
		$params = [
			'offset' => 0,
			'limit' => self::NB_TOPS_TO_DISPLAY
		];
		[$order_by, $order_type] = explode(':', $this->FileListTops->SelectedValue, 2);

		if ($order_by !== 'none') {
			$params['order_by'] = $order_by;
			$params['order_type'] = $order_type;
		}
		$params['details'] = '1';
		$query = '?' . http_build_query($params);
		$result = $this->getModule('api')->get(
			['jobs', $this->getJobId(), 'files', $query]
		);
		if ($result->error === 0) {
			$file_list = $result->output;
			$cb = 'oJobTopFiles' . $this->ClientID . '.init';
			$this->getPage()->getCallbackClient()->callClientFunction(
				$cb,
				[$file_list]
			);
		}
	}

	/**
	 * Set job identifier to show files.
	 *
	 * @param mixed $jobid
	 */
	public function setJobId($jobid)
	{
		$jobid = (int) $jobid;
		$this->setViewState(self::JOBID, $jobid, 0);
	}

	/**
	 * Get job identifier to show files.
	 *
	 * @return int job identifier
	 */
	public function getJobId()
	{
		return $this->getViewState(self::JOBID, 0);
	}
}
