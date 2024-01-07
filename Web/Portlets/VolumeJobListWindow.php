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

use Bacularis\Web\Portlets\Portlets;

/**
 * Modal window iwth volume job list.
 * Modal shows which jobs are stored on given volume.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class VolumeJobListWindow extends Portlets
{
	public function openWindow($sender, $param)
	{
		$mediaid = (int) $param->getCallbackParameter();
		$this->getPage()->getCallbackClient()->show('volume_job_list_window');
		$this->loadWindow($mediaid);
	}

	public function loadWindow($mediaid)
	{
		$this->VolumeJobList->setMediaId($mediaid);
		$this->VolumeJobList->loadJobs(null, null);
	}
}
