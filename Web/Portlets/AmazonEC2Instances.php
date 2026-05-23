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

use Bacularis\Common\Modules\Cloud\Amazon\EC2\EC2 as AmazonEC2;
use Bacularis\Common\Modules\Cloud\Amazon\EC2\Instance as AmazonEC2Instance;
use StdClass;

/**
 * Amazon EC2 instances control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AmazonEC2Instances extends AmazonBase
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	public function initialize()
	{
		$this->setEC2InstanceList(null, null);
	}

	/**
	 * Load Amazon EC2 instance list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setEC2InstanceList($sender, $param)
	{
		$api = $this->getModule('api');
		$host = $this->getFDAPIHost();
		$cb = $this->getPage()->getCallbackClient();
		$account = $this->getAmazonAccount();
		if (empty($account)) {
			// account not selected yet
			$cb->callClientFunction(
				'oAmazonEC2Instances.load_ec2_instance_list_cb',
				[[]]
			);
			return;
		}
		$result = $api->get(
			['cloud', 'amazon', 'ec2', 'instances', 'describe', "?account={$account}"],
			$host
		);
		$data = new StdClass();
		if ($result->error == 0) {
			$data = $result->output;
		}

		$instances = $this->prepareInstanceData($data);
		$cb->callClientFunction('oAmazonEC2Instances.load_ec2_instance_list_cb', [
			$instances
		]);
	}

	/**
	 * Prepare EC2 instance list do display in table.
	 *
	 * @param object $data instance data structure
	 * @return array prepared instance data
	 */
	private function prepareInstanceData(object $data): array
	{
		$instances = [];
		$reservations = $data->Reservations ?? [];
		for ($i = 0; $i < count($reservations); $i++) {
			$owner_id = $reservations[$i]->OwnerId ?? '';
			$insts = $reservations[$i]->Instances;
			for ($j = 0; $j < count($insts); $j++) {
				$inst = AmazonEC2Instance::parseObject(
					$insts[$j],
					AmazonEC2::EMPTY_VALUE_MARK
				);
				$instances[] = $inst;
			}
		}
		return $instances;
	}
}
