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

use Bacularis\Common\Modules\AuditLog;

/**
 * Basic user list module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BasicUsers extends Security
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load API basic user list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setAPIBasicUserList($sender, $param)
	{
		$basic_users = $this->getModule('api')->get(['basic', 'users']);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oAPIBasicUsers.load_api_basic_user_list_cb', [
			$basic_users->output,
			$basic_users->error
		]);
	}

	/**
	 * Load data in API basic user modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIBasicUserWindow($sender, $param)
	{
		$username = $param->getCallbackParameter();
		if (!empty($username)) {
			$basic_user_cfg = $this->getModule('api')->get(['basic', 'users', $username]);
			if ($basic_user_cfg->error === 0 && is_object($basic_user_cfg->output)) {
				// It is done only for existing basic user accounts
				$this->APIBasicUserUsername->Text = $basic_user_cfg->output->username;
				$this->APIBasicUserBconsoleCfgPath->Text = $basic_user_cfg->output->bconsole_cfg_path;
			}
		}
		$this->loadAPIBasicUserConsole(null, null);

		$dirs = $this->getModule('api')->get(['config', 'bcons', 'Director']);
		$dir_names = [];
		if ($dirs->error == 0) {
			for ($i = 0; $i < count($dirs->output); $i++) {
				$dir_names[$dirs->output[$i]->Director->Name] = $dirs->output[$i]->Director->Name;
			}
		}
		$this->APIBasicUserDirector->DataSource = $dir_names;
		$this->APIBasicUserDirector->dataBind();
	}

	public function loadAPIBasicUserConsole($sender, $param)
	{
		$cons = $this->getModule('api')->get(['config', 'dir', 'Console']);
		$console = ['' => ''];
		if ($cons->error == 0) {
			for ($i = 0; $i < count($cons->output); $i++) {
				$console[$cons->output[$i]->Console->Name] = $cons->output[$i]->Console->Name;
			}
		}
		$this->APIBasicUserConsole->DataSource = $console;
		$this->APIBasicUserConsole->dataBind();
	}

	/**
	 * Save API basic user config.
	 * It works both for new basic users and for edited basic users.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveAPIBasicUser($sender, $param)
	{
		$username = $this->APIBasicUserUsername->Text;
		$cfg = [];
		$cfg['username'] = $username;
		$cfg['password'] = $this->APIBasicUserPassword->Text;
		$cfg['bconsole_cfg_path'] = $this->APIBasicUserBconsoleCfgPath->Text;
		if ($this->APIBasicUserBconsoleCreate->Checked) {
			$cfg['console'] = $this->APIBasicUserConsole->SelectedValue;
			$cfg['director'] = $this->APIBasicUserDirector->SelectedValue;
		}

		$win_type = $this->APIBasicUserWindowType->Value;
		$result = (object) ['error' => -1];
		if ($win_type === self::TYPE_ADD_WINDOW) {
			$result = $this->getModule('api')->create(['basic', 'users', $username], $cfg);
		} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
			$result = $this->getModule('api')->set(['basic', 'users', $username], $cfg);
		}

		if ($result->error === 0) {
			// Refresh API basic user list
			$this->setAPIBasicUserList(null, null);

			$amsg = '';
			if ($win_type === self::TYPE_ADD_WINDOW) {
				$amsg = "Create API Basic user. User: $username";
			} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
				$amsg = "Save API Basic user. User: $username";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}

		$this->onSaveBasicUser(null);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oAPIBasicUsers.save_api_basic_user_cb', [
			$result
		]);
	}

	/**
	 * On save basic user event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveBasicUser($param)
	{
		$this->raiseEvent('OnSaveBasicUser', $this, $param);
	}

	/**
	 * Remove API basic users action.
	 * Here is possible to remove one basic user or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeAPIBasicUsers($sender, $param)
	{
		$usernames = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($usernames); $i++) {
			$result = $this->getModule('api')->remove(['basic', 'users', $usernames[$i]]);
			if ($result->error !== 0) {
				break;
			}
		}

		if (count($usernames) > 0) {
			// Refresh API basic user list
			$this->setAPIBasicUserList(null, null);

			for ($i = 0; $i < count($usernames); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove API Basic user. User: {$usernames[$i]}"
				);
			}
		}

		$this->onRemoveBasicUser(null);
	}

	/**
	 * On remove basic user event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveBasicUser($param)
	{
		$this->raiseEvent('OnRemoveBasicUser', $this, $param);
	}
}
