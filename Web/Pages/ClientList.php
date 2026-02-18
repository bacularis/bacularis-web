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
	public const DISABLE_ENABLE_METHOD_BCONSOLE = 0;
	public const DISABLE_ENABLE_METHOD_CONFIG = 1;

	public $client_show = [];

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->setDataViews();
		$this->loadClientShow(null, null);
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
	 * Load client show properties.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function loadClientShow($sender, $param): void
	{
		$this->client_show = $this->getClientShow();
		if ($this->IsCallBack) {
			$cb = $this->getCallbackClient();
			$cb->callClientFunction(
				'oClientList.load_client_show_cb',
				[$this->client_show]
			);
		}
	}

	/**
	 * Get client show properties.
	 *
	 * @return array client show properties or empty array on error
	 */
	private function getClientShow(): array
	{
		$client_show = [];
		$api = $this->getModule('api');
		$result = $api->get(['clients', 'show', '?output=json']);
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				if (!property_exists($result->output[$i], 'name')) {
					continue;
				}
				$client_show[$result->output[$i]->name] = $result->output[$i];
			}
		}
		return $client_show;
	}

	/**
	 * Disable client - bulk action.
	 * NOTE: Action available only for users with admin role assigned.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function disableClient($sender, $param)
	{
		if (!$this->User->isInRole(WebUserRoles::ADMIN)) {
			// non-admin user - end
			return;
		}
		$params = $param->getCallbackParameter();
		if (!is_object($params)) {
			// this is not object - end
			return;
		}
		$method = (int) $params->method;
		$clients = $params->items;
		$error = null;
		$err_cli = null;
		for ($i = 0; $i < count($clients); $i++) {
			$result = null;
			if ($method == self::DISABLE_ENABLE_METHOD_BCONSOLE) {
				$result = $this->disableClientConsole(
					$clients[$i]->clientid,
					$clients[$i]->name
				);
			} elseif ($method == self::DISABLE_ENABLE_METHOD_CONFIG) {
				$result = $this->disableClientConfig(
					$clients[$i]->name
				);
			}
			if (is_object($result) && $result->error != 0) {
				$err_cli = $clients[$i]->name;
				$error = $result;
				break;
			}
		}
		// Finish or report error
		$eid = 'client_list_disable_client_error';
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($eid);
		if (!$error) {
			$cb->callClientFunction('oDisableClientWindow.show', [false]);
		} else {
			$emsg = 'Error while disabling client "%s". ErrorCode: %d, Message: %s.';
			$emsg = sprintf($emsg, $err_cli, $error->error, $error->output);
			$cb->update($eid, $emsg);
			$cb->show($eid);
			$cb->hide('client_list_disable_client_btn');
		}
		$this->loadClientShow($sender, $param);
	}

	/**
	 * Disable client using Bconsole 'disable' command.
	 *
	 * @param int $clientid client identifier to disable
	 * @param string $name client name to disable
	 * @return object disable client result
	 */
	private function disableClientConsole(int $clientid, string $name): object
	{
		$api = $this->getModule('api');
		$result = $api->set(
			['clients', $clientid, 'disable']
		);
		if ($result->error === 0) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Client '{$name}' has been disabled."
			);
		}
		return $result;
	}

	/**
	 * Disable client in Bacula Director configuration.
	 *
	 * @param string $name client name to disable
	 * @return object disable client result
	 */
	private function disableClientConfig(string $name): ?object
	{
		$sess = $this->getApplication()->getSession();
		if (!$sess->itemAt('dir')) {
			// Configuration part not enabled for user - end.
			return null;
		}
		$component_type = 'dir';
		$resource_type = 'Client';
		$resource_name = $name;
		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$api = $this->getModule('api');
		$result = $api->get($params, null, false);
		if ($result->error != 0) {
			return $result;
		}

		$directives = $result->output;
		$directives->Enabled = false;
		$result = $api->set(
			$params,
			['config' => json_encode($directives)],
			null,
			false
		);
		if ($result->error != 0) {
			return $result;
		}

		$audit = $this->getModule('audit');
		$audit->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_ACTION,
			"Client '{$name}' has been disabled."
		);

		$result = $api->set(
			['console'],
			['reload']
		);
		return $result;
	}

	/**
	 * Enable client - bulk action.
	 * NOTE: Action available only for users with admin role assigned.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function enableClient($sender, $param)
	{
		if (!$this->User->isInRole(WebUserRoles::ADMIN)) {
			// non-admin user - end
			return;
		}
		$params = $param->getCallbackParameter();
		if (!is_object($params)) {
			// this is not object - end
			return;
		}
		$method = (int) $params->method;
		$clients = $params->items;
		$error = null;
		$err_cli = null;
		for ($i = 0; $i < count($clients); $i++) {
			$result = null;
			if ($method == self::DISABLE_ENABLE_METHOD_BCONSOLE) {
				$result = $this->enableClientConsole(
					$clients[$i]->clientid,
					$clients[$i]->name
				);
			} elseif ($method == self::DISABLE_ENABLE_METHOD_CONFIG) {
				$result = $this->enableClientConfig(
					$clients[$i]->name
				);
			}
			if (is_object($result) && $result->error != 0) {
				$err_cli = $clients[$i]->name;
				$error = $result;
				break;
			}
		}
		// Finish or report error
		$eid = 'client_list_enable_client_error';
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($eid);
		if (!$error) {
			$cb->callClientFunction('oEnableClientWindow.show', [false]);
		} else {
			$emsg = 'Error while enabling client "%s". ErrorCode: %d, Message: %s.';
			$emsg = sprintf($emsg, $err_cli, $error->error, $error->output);
			$cb->update($eid, $emsg);
			$cb->show($eid);
			$cb->hide('client_list_enable_client_btn');
		}
		$this->loadClientShow($sender, $param);
	}

	/**
	 * Enable client using Bconsole 'enable' command.
	 *
	 * @param int $clientid client identifier to enable
	 * @param string $name client name to enable
	 * @return object enable client result
	 */
	private function enableClientConsole(int $clientid, string $name): object
	{
		$api = $this->getModule('api');
		$result = $api->set(
			['clients', $clientid, 'enable']
		);
		if ($result->error === 0) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Client '{$name}' has been enabled."
			);
		}
		return $result;
	}

	/**
	 * Enable client in Bacula Director configuration.
	 *
	 * @param string $name client name to enable
	 * @return object enable client result
	 */
	private function enableClientConfig(string $name): ?object
	{
		$sess = $this->getApplication()->getSession();
		if (!$sess->itemAt('dir')) {
			// Configuration part not enabled for user - end.
			return null;
		}
		$component_type = 'dir';
		$resource_type = 'Client';
		$resource_name = $name;
		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$api = $this->getModule('api');
		$result = $api->get($params, null, false);
		if ($result->error != 0) {
			return $result;
		}

		$directives = $result->output;
		$directives->Enabled = true;
		$result = $api->set(
			$params,
			['config' => json_encode($directives)],
			null,
			false
		);
		if ($result->error != 0) {
			return $result;
		}

		$audit = $this->getModule('audit');
		$audit->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_ACTION,
			"Client '{$name}' has been enabled."
		);

		$result = $api->set(
			['console'],
			['reload']
		);
		return $result;
	}

	/**
	 * Delete director component client configuration and catalog resources - bulk action
	 * NOTE: Action available only for users with admin role assigned.
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
