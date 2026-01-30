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
