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
 * Copyright (C) 2013-2019 Kern Sibbald
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
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\WebUserRoles;
use Bacularis\Web\Portlets\BaculaConfigDirectives;
use Prado\Prado;

/**
 * Client list page.
 *
 * @category Page
 */
class ClientList extends BaculumWebPage
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
		$client_view_desc = [
			'name' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'uname' => ['type' => 'string', 'name' => Prado::localize('Uname')],
			'clientid' => ['type' => 'number', 'name' => 'ClientId'],
			'autoprune' => ['type' => 'boolean', 'name' => Prado::localize('AutoPrune')]
		];
		$this->ClientViews->setViewName('client_list');
		$this->ClientViews->setViewDataFunction('get_client_list_data');
		$this->ClientViews->setUpdateViewFunction('update_client_list_table');
		$this->ClientViews->setDescription($client_view_desc);
	}

	/**
	 * Delete director component client configuration and catalog resources - bulk action
	 * NOTE: Action available only for users wiht admin role assigned.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function deleteClientResources($sender, $param)
	{
		if (!$this->User->isInRole(WebUserRoles::ADMIN)) {
			// non-admin user - end
			return;
		}
		$clients = $param->getCallbackParameter();
		if (!is_array($clients)) {
			// this is not list - end
			return;
		}
		$error = null;
		$err_client = '';
		$api = $this->getModule('api');
		for ($i = 0; $i < count($clients); $i++) {
			$params = [
				'config',
				'dir',
				'Client',
				$clients[$i]->name
			];
			$result = $api->remove($params);
			if ($result->error != 0) {
				$error = $result;
				$err_client = $clients[$i];
				break;
			}
			$api->set(['console'], ['reload']);

			$amsg = sprintf(
				'Remove Bacula config resource. Component: %s, Resource: %s, Name: %s',
				$this->getApplication()->getSession()->itemAt('dir'),
				'Client',
				$clients[$i]->name
			);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				$amsg
			);

			$params = [
				'clients',
				$clients[$i]->clientid
			];
			$result = $api->remove($params);
			if ($result->error != 0) {
				$error = $result;
				$err_client = $clients[$i];
				break;
			}
		}
		$cb = $this->getCallbackClient();
		$message = '';
		if (!$error) {
			$cb->callClientFunction(
				'oClientListDeleteClientResourceWindow.show',
				[false]
			);
		} elseif ($error->error == BaculaConfigError::ERROR_CONFIG_DEPENDENCY_ERROR) {
			// Other resources depend on this client so it cannot be removed.
			$message = BaculaConfigDirectives::getDependenciesError(
				json_decode($error->output, true),
				'Client',
				$err_client->name
			);
		} else {
			$emsg = "Error while removing client '%s'. ErrorCode: %d, ErrorMessage: '%s'";
			$message = sprintf($emsg, $err_client->name, $error->error, $error->output);
		}
		if ($message) {
			$cb->callClientFunction(
				'oClientListDeleteClientResourceWindow.error',
				[$message]
			);
		}
	}
}
