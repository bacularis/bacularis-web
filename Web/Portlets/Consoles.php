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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\JobInfo;

/**
 * Console list module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class Consoles extends Security
{
	/**
	 * Modal window types for users and roles.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load console ACL list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setConsoleList($sender, $param)
	{
		$api = $this->getModule('api');
		$config = $api->get(['config', 'dir', 'Console']);
		$console_directives = [
			'Description' => '',
			'JobAcl' => '',
			'ClientAcl' => '',
			'StorageAcl' => '',
			'ScheduleAcl' => '',
			'RunAcl' => '',
			'PoolAcl' => '',
			'CommandAcl' => '',
			'FilesetAcl' => '',
			'CatalogAcl' => '',
			'WhereAcl' => '',
			'PluginOptionsAcl' => '',
			'BackupClientAcl' => '',
			'RestoreClientAcl' => '',
			'DirectoryAcl' => ''
		];
		$consoles = [];
		$join_cons = function ($item) {
			if (is_array($item)) {
				$item = implode(',', $item);
			}
			return $item;
		};
		if ($config->error == 0) {
			for ($i = 0; $i < count($config->output); $i++) {
				$cons = (array) $config->output[$i]->Console;
				$cons = array_map($join_cons, $cons);
				$consoles[] = array_merge($console_directives, $cons);
			}
		}
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oConsoles.load_console_list_cb', [
			$consoles
		]);
	}

	/**
	 * Load data in console modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadConsoleWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if (!empty($name)) {
			// edit existing console
			$this->ConsoleConfig->setResourceName($name);
			$this->ConsoleConfig->setLoadValues(true);
		} else {
			// add new console
			$this->ConsoleConfig->setLoadValues(false);
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction('oBaculaConfigSection.show_sections', [true]);
		}
		$sess = $this->getApplication()->getSession();
		$component_name = $sess->itemAt('dir');
		$this->ConsoleConfig->setHost($this->User->getDefaultAPIHost());
		$this->ConsoleConfig->setComponentName($component_name);
		$this->ConsoleConfig->IsDirectiveCreated = false;
		$this->ConsoleConfig->raiseEvent('OnDirectiveListLoad', $this, null);
	}

	/**
	 * Remove consoles action.
	 * Here is possible to remove one console or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeConsoles($sender, $param)
	{
		$consoles = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($consoles); $i++) {
			$result = $this->getModule('api')->remove(
				[
					'config',
					'dir',
					'Console',
					$consoles[$i]
				],
				$this->User->getDefaultAPIHost(),
				false
			);
			if ($result->error === 0) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_CONFIG,
					"Remove Bacula config resource. Component: Director, Resource: Console, Name: {$consoles[$i]}"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_CONFIG,
					"Problem with removing Bacula config resource. Component: Director, Resource: Console, Name: {$consoles[$i]}"
				);
			}
		}

		// refresh console list
		$this->setConsoleList($sender, $param);

		$this->onRemoveConsole(null);
	}

	/**
	 * On create console event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveConsole($param)
	{
		$this->raiseEvent('OnSaveConsole', $this, $param);
	}

	/**
	 * On remove console event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveConsole($param)
	{
		$this->raiseEvent('OnRemoveConsole', $this, $param);
	}

	public function postSaveConsole($sender, $param)
	{
		$this->onSaveConsole(null);
	}

	public function setAllCommandAcls($sender, $param)
	{
		$config = [
			"CommandAcl" => JobInfo::COMMAND_ACL_USED_BY_WEB
		];
		$this->ConsoleConfig->loadConfig(
			$sender,
			$param,
			'ondirectivelistload',
			$config
		);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oBaculaConfigSection.show_sections',
			[true]
		);
	}
}
