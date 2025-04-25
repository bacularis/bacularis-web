<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
 * Bulk cancel jobs modal.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BulkCancelJobsModal extends Portlets
{
	/**
	 * Cancel job.
	 * Multiple jobs are cancelled one by one.
	 *
	 * @param TCallback $sender sender parameter
	 * @param TCallbackEventParameter $param parameter
	 */
	public function cancelJob($sender, $param): void
	{
		$jobid = (int) $param->getCallbackParameter();
		$result = $this->getModule('api')->set(
			['jobs', $jobid, 'cancel'],
			[]
		);
		$stat = ($result->error == 0);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oBulkCancelJobsModal.cancel_job_cb',
			[$jobid, $stat]
		);
	}
}
