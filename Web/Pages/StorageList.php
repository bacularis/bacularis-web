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
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\WebUserRoles;
use Bacularis\Web\Portlets\BaculaConfigDirectives;
use Prado\Prado;

/**
 * Storage list page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class StorageList extends BaculumWebPage
{
	public const USE_CACHE = true;

	public const DISABLE_ENABLE_METHOD_BCONSOLE = 0;
	public const DISABLE_ENABLE_METHOD_CONFIG = 1;

	public $client_show = [];

	public $storages;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$result = $this->getModule('api')->get(['storages'], null, true, self::USE_CACHE);
		if ($result->error === 0) {
			$this->storages = $result->output;
		}
		$this->setDataViews();
	}

	private function setDataViews()
	{
		$storage_view_desc = [
			'name' => ['type' => 'string', 'name' => Prado::localize('Name')],
			'autochanger' => ['type' => 'boolean', 'name' => Prado::localize('Autochanger')],
			'storageid' => ['type' => 'number', 'name' => 'StorageId']
		];
		$this->StorageViews->setViewName('storage_list');
		$this->StorageViews->setViewDataFunction('get_storage_list_data');
		$this->StorageViews->setUpdateViewFunction('update_storage_list_table');
		$this->StorageViews->setDescription($storage_view_desc);
	}

	/**
	 * Load storage resource list.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function loadStorageList($sender, $param)
	{
		$api = $this->getModule('api');
		$params = ['storages'];
		$storage_list = $api->get($params, null, true, self::USE_CACHE);
		if ($storage_list->error === 0) {
			$cb = $this->getCallbackClient();
			$cb->callClientFunction(
				'oStorageList.load_storage_list_cb',
				[$storage_list->output]
			);
		}
	}

	/**
	 * Disable storage - bulk action.
	 * NOTE: Action available only for users with admin role assigned.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function disableStorage($sender, $param)
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
		$storages = $params->items;
		$error = null;
		$err_cli = null;
		for ($i = 0; $i < count($storages); $i++) {
			$result = null;
			if ($method == self::DISABLE_ENABLE_METHOD_BCONSOLE) {
				$result = $this->disableStorageConsole(
					$storages[$i]->storageid,
					$storages[$i]->name
				);
			} elseif (false && $method == self::DISABLE_ENABLE_METHOD_CONFIG) {
				// Remove 'false' if the config method will be fixed in Bacula
				$result = $this->disableStorageConfig(
					$storages[$i]->name
				);
			}
			if (is_object($result) && $result->error != 0) {
				$err_cli = $storages[$i]->name;
				$error = $result;
				break;
			}
		}
		// Finish or report error
		$eid = 'storage_list_disable_storage_error';
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($eid);
		if (!$error) {
			$cb->callClientFunction('oDisableStorageWindow.show', [false]);
		} else {
			$emsg = 'Error while disabling storage "%s". ErrorCode: %d, Message: %s.';
			$emsg = sprintf($emsg, $err_cli, $error->error, $error->output);
			$cb->update($eid, $emsg);
			$cb->show($eid);
			$cb->hide('storage_list_disable_storage_btn');
		}
	}

	/**
	 * Disable storage using Bconsole 'disable' command.
	 *
	 * @param int $storageid storage identifier to disable
	 * @param string $name storage name to disable
	 * @return object disable storage result
	 */
	private function disableStorageConsole(int $storageid, string $name): object
	{
		$api = $this->getModule('api');
		$result = $api->set(
			['storages', $storageid, 'disable']
		);
		if ($result->error === 0) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Storage '{$name}' has been disabled."
			);
		}
		return $result;
	}

	/**
	 * Disable storage in Bacula Director configuration.
	 *
	 * @param string $name storage name to disable
	 * @return object disable storage result
	 */
	private function disableStorageConfig(string $name): ?object
	{
		$sess = $this->getApplication()->getSession();
		if (!$sess->itemAt('dir')) {
			// Configuration part not enabled for user - end.
			return null;
		}
		$component_type = 'dir';
		$resource_type = 'Storage';
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
			"Storage '{$name}' has been disabled."
		);

		$result = $api->set(
			['console'],
			['reload']
		);
		return $result;
	}

	/**
	 * Enable storage - bulk action.
	 * NOTE: Action available only for users with admin role assigned.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function enableStorage($sender, $param)
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
		$storages = $params->items;
		$error = null;
		$err_cli = null;
		for ($i = 0; $i < count($storages); $i++) {
			$result = null;
			if ($method == self::DISABLE_ENABLE_METHOD_BCONSOLE) {
				$result = $this->enableStorageConsole(
					$storages[$i]->storageid,
					$storages[$i]->name
				);
			} elseif (false && $method == self::DISABLE_ENABLE_METHOD_CONFIG) {
				// Remove 'false' if the config method will be fixed in Bacula
				$result = $this->enableStorageConfig(
					$storages[$i]->name
				);
			}
			if (is_object($result) && $result->error != 0) {
				$err_cli = $storages[$i]->name;
				$error = $result;
				break;
			}
		}
		// Finish or report error
		$eid = 'storage_list_enable_storage_error';
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($eid);
		if (!$error) {
			$cb->callClientFunction('oEnableStorageWindow.show', [false]);
		} else {
			$emsg = 'Error while enabling storage "%s". ErrorCode: %d, Message: %s.';
			$emsg = sprintf($emsg, $err_cli, $error->error, $error->output);
			$cb->update($eid, $emsg);
			$cb->show($eid);
			$cb->hide('storage_list_enable_storage_btn');
		}
	}

	/**
	 * Enable storage using Bconsole 'enable' command.
	 *
	 * @param int $storageid storage identifier to enable
	 * @param string $name storage name to enable
	 * @return object enable storage result
	 */
	private function enableStorageConsole(int $storageid, string $name): object
	{
		$api = $this->getModule('api');
		$result = $api->set(
			['storages', $storageid, 'enable']
		);
		if ($result->error === 0) {
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_ACTION,
				"Storage '{$name}' has been enabled."
			);
		}
		return $result;
	}

	/**
	 * Enable storage in Bacula Director configuration.
	 *
	 * @param string $name storage name to enable
	 * @return object enable storage result
	 */
	private function enableStorageConfig(string $name): ?object
	{
		$sess = $this->getApplication()->getSession();
		if (!$sess->itemAt('dir')) {
			// Configuration part not enabled for user - end.
			return null;
		}
		$component_type = 'dir';
		$resource_type = 'Storage';
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
			"Storage '{$name}' has been enabled."
		);

		$result = $api->set(
			['console'],
			['reload']
		);
		return $result;
	}


	/**
	 * Delete director component storage configuration and catalog resources - bulk action
	 * NOTE: Action available only for users wiht admin role assigned.
	 *
	 * @param TCallback $sender callback object
	 * @param TCallbackEventPrameter $param event parameter
	 */
	public function deleteStorageResources($sender, $param)
	{
		if (!$this->User->isInRole(WebUserRoles::ADMIN)) {
			// non-admin user - end
			return;
		}
		$storages = $param->getCallbackParameter();
		if (!is_array($storages)) {
			// this is not list - end
			return;
		}
		$error = null;
		$err_storage = '';
		$api = $this->getModule('api');
		for ($i = 0; $i < count($storages); $i++) {
			$params = [
				'config',
				'dir',
				'Storage',
				$storages[$i]->name
			];
			$result = $api->remove($params);
			if ($result->error != 0) {
				$error = $result;
				$err_storage = $storages[$i];
				break;
			}
			$api->set(['console'], ['reload']);

			$amsg = sprintf(
				'Remove Bacula config resource. Component: %s, Resource: %s, Name: %s',
				$this->getApplication()->getSession()->itemAt('dir'),
				'Storage',
				$storages[$i]->name
			);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				$amsg
			);

		}
		$cb = $this->getCallbackClient();
		$message = '';
		if (!$error) {
			$cb->callClientFunction(
				'oStorageListDeleteStorageResourceWindow.show',
				[false]
			);
		} elseif ($error->error == BaculaConfigError::ERROR_CONFIG_DEPENDENCY_ERROR) {
			// Other resources depend on this storage so it cannot be removed.
			$message = BaculaConfigDirectives::getDependenciesError(
				json_decode($error->output, true),
				'Storage',
				$err_storage->name
			);
		} else {
			$emsg = "Error while removing storage '%s'. ErrorCode: %d, ErrorMessage: '%s'";
			$message = sprintf($emsg, $err_storage->name, $error->error, $error->output);
		}
		if ($message) {
			$cb->callClientFunction(
				'oStorageListDeleteStorageResourceWindow.error',
				[$message]
			);
		}
	}
}
