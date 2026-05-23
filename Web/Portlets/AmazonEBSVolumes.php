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

namespace Bacularis\Web\Portlets;

use Bacularis\Common\Modules\Cloud\Amazon\EBS\EBS as AmazonEBS;
use Bacularis\Common\Modules\Cloud\Amazon\EBS\Volume as AmazonEBSVolume;
use StdClass;

/**
 * Amazon EBS volumes control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AmazonEBSVolumes extends AmazonBase
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	public function initialize()
	{
		$this->setEBSVolumeList(null, null);
		$fd_api_host = $this->getFDAPIHost();
		$this->AmazonEBSVolumeBackupWindow->setFDAPIHost($fd_api_host);
	}

	/**
	 * Load Amazon EBS volume list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setEBSVolumeList($sender, $param)
	{
		$api = $this->getModule('api');
		$host = $this->getFDAPIHost();
		$cb = $this->getPage()->getCallbackClient();
		$account = $this->getAmazonAccount();
		if (empty($account)) {
			// account not selected yet
			$cb->callClientFunction(
				'oAmazonEBSVolumes.load_ebs_volume_list_cb',
				[[]]
			);
			return;
		}
		$result = $api->get(
			['cloud', 'amazon', 'ebs', 'volumes', 'describe', "?account={$account}"],
			$host
		);
		$data = new StdClass();
		if ($result->error == 0) {
			$data = $result->output;
		}

		$instances = $this->prepareVolumeData($data);
		$cb->callClientFunction('oAmazonEBSVolumes.load_ebs_volume_list_cb', [
			$instances
		]);
	}

	/**
	 * Prepare EBS volume list do display in table.
	 *
	 * @param object $data volume data structure
	 * @return array prepared volume data
	 */
	private function prepareVolumeData(object $data): array
	{
		$vols = [];
		$volumes = $data->Volumes ?? [];
		for ($i = 0; $i < count($volumes); $i++) {
			$vol = AmazonEBSVolume::parseObject(
				$volumes[$i],
				AmazonEBS::EMPTY_VALUE_MARK
			);
			$vols[] = $vol;
		}
		return $vols;
	}
}
