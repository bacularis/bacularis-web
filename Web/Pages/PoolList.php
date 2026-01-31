<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Errors\BaculaConfigError;
use Bacularis\Common\Modules\Errors\PoolError;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\WebUserRoles;
use Bacularis\Web\Portlets\BaculaConfigDirectives;
use Prado\Prado;

/**
 * Pool list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class PoolList extends BaculumWebPage
{
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->setDataViews();
	}

	private function setDataViews()
	{
		$pool_view_desc = [
			'name' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'numvols' => ['type' => 'number', 'name' => Prado::localize('No. vols')],
			'maxvols' => ['type' => 'number', 'name' => Prado::localize('Max. vols')],
			'poolid' => ['type' => 'number', 'name' => 'PoolId'],
			'autoprune' => ['type' => 'boolean', 'name' => Prado::localize('AutoPrune')],
			'recycle' => ['type' => 'boolean', 'name' => Prado::localize('Recycle')]
		];
		$this->PoolViews->setViewName('pool_list');
		$this->PoolViews->setViewDataFunction('get_pool_list_data');
		$this->PoolViews->setUpdateViewFunction('update_pool_list_table');
		$this->PoolViews->setDescription($pool_view_desc);
	}

	/**
	 * Delete director component pool configuration and catalog resources - bulk action
	 * NOTE: Action available only for users wiht admin role assigned.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function deletePoolResources($sender, $param)
	{
		if (!$this->User->isInRole(WebUserRoles::ADMIN)) {
			// non-admin user - end
			return;
		}
		$pools = $param->getCallbackParameter();
		if (!is_array($pools)) {
			// this is not list - end
			return;
		}
		$error = null;
		$err_pool = '';
		$api = $this->getModule('api');
		for ($i = 0; $i < count($pools); $i++) {
			$params = [
				'pools',
				$pools[$i]->poolid,
				'volumes'
			];
			$volumes = $api->get($params);
			if (count($volumes->output) > 0) {
				$emsg = sprintf(
					'Pool %s is not empty. Please move or remove all volumes from given pool and try again.',
					$pools[$i]->name
				);
				$error = (object) [
					'output' => $emsg,
					'error' => -1
				];
				$err_pool = $pools[$i];
				break;
			}
			$params = [
				'config',
				'dir',
				'Pool',
				$pools[$i]->name
			];
			$result = $api->remove($params);
			if ($result->error != 0) {
				$error = $result;
				$err_pool = $pools[$i];
				break;
			}
			$api->set(['console'], ['reload']);

			$amsg = sprintf(
				'Remove Bacula config resource. Component: %s, Resource: %s, Name: %s',
				$this->getApplication()->getSession()->itemAt('dir'),
				'Pool',
				$pools[$i]->name
			);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				$amsg
			);

			$params = [
				'pools',
				$pools[$i]->poolid
			];
			$result = $api->remove($params);
			if ($result->error != 0) {
				$error = $result;
				$err_pool = $pools[$i];
				break;
			}
		}
		$message = '';
		$cb = $this->getCallbackClient();
		if (!$error) {
			$cb->callClientFunction(
				'oPoolListDeletePoolResourceWindow.show',
				[false]
			);
		} elseif ($error->error == BaculaConfigError::ERROR_CONFIG_DEPENDENCY_ERROR) {
			// Other resources depend on this pool so it cannot be removed.
			$message = BaculaConfigDirectives::getDependenciesError(
				json_decode($error->output, true),
				'Pool',
				$err_pool->name
			);
		} else {
			// Errors from console command execution
			$emsg = "Error while removing pool '%s'. ErrorCode: %d, ErrorMessage: '%s'";
			$message = sprintf($emsg, $err_pool->name, $error->error, $error->output);
		}
		if ($message) {
			$cb->callClientFunction(
				'oPoolListDeletePoolResourceWindow.error',
				[$message]
			);
		}
	}
}
