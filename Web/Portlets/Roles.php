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

/**
 * Role list module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class Roles extends Security
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Initialize page.
	 *
	 * @param mixed $param oninit event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getPage()->IsCallBack || $this->getPage()->IsPostBack) {
			return;
		}
		$this->initRoleWindow();
	}

	/**
	 * Initialize values in role modal window.
	 *
	 */
	public function initRoleWindow()
	{
		// set role resources
		$resources = $this->getModule('page_category')->getCategories(false);
		natcasesort($resources);
		$this->RoleResources->DataSource = array_combine($resources, $resources);
		$this->RoleResources->dataBind();
	}

	/**
	 * Set and load role list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setRoleList($sender, $param)
	{
		$user_role = $this->getModule('user_role');
		$config = $user_role->getRoles();
		$this->addUserStatsToRoles($config);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oRoles.load_role_list_cb', [
			array_values($config)
		]);
	}

	/**
	 * Load data in role modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadRoleWindow($sender, $param)
	{
		$role = $param->getCallbackParameter();
		$config = $this->getModule('user_role')->getRole($role);
		if (count($config) > 0) {
			// Edit role window
			$this->Role->Text = $config['role'];
			$this->RoleLongName->Text = $config['long_name'];
			$this->RoleDescription->Text = $config['description'];
			$selected_indices = [];
			$resources = explode(',', $config['resources']);
			for ($i = 0; $i < $this->RoleResources->getItemCount(); $i++) {
				if (in_array($this->RoleResources->Items[$i]->Value, $resources)) {
					$selected_indices[] = $i;
				}
			}
			$this->RoleResources->setSelectedIndices($selected_indices);
			$this->RoleEnabled->Checked = ($config['enabled'] == 1);
			if ($this->getModule('user_role')->isRolePreDefined($role)) {
				$this->RoleSave->Display = 'None';
				$this->PreDefinedRoleMsg->Display = 'Dynamic';
			} else {
				$this->RoleSave->Display = 'Dynamic';
				$this->PreDefinedRoleMsg->Display = 'None';
			}
		} else {
			// New role window
			$this->RoleSave->Display = 'Dynamic';
			$this->PreDefinedRoleMsg->Display = 'None';
		}
	}

	/**
	 * Save role.
	 * It works both for new roles and for edited roles.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRole($sender, $param)
	{
		$role_win_type = $this->RoleWindowType->Value;
		$role = $this->Role->Text;
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide('role_window_role_exists');
		if ($role_win_type === self::TYPE_ADD_WINDOW) {
			$config = $this->getModule('user_role')->getRole($role);
			if (count($config) > 0) {
				$cb->show('role_window_role_exists');
				return;
			}
		}
		if ($this->getModule('user_role')->isRolePreDefined($role)) {
			// Predefined roles cannot be saved
			return;
		}
		$config = [];
		$config['long_name'] = $this->RoleLongName->Text;
		$config['description'] = $this->RoleDescription->Text;

		$selected_indices = $this->RoleResources->getSelectedIndices();
		$resources = [];
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->RoleResources->getItemCount(); $i++) {
				if ($i === $indice) {
					$resources[] = $this->RoleResources->Items[$i]->Value;
				}
			}
		}
		$config['resources'] = implode(',', $resources);
		$config['enabled'] = $this->RoleEnabled->Checked ? 1 : 0;
		$role_config = $this->getModule('role_config');
		$result = $role_config->setRoleConfig($role, $config);
		$this->setRoleList($sender, $param);

		$this->onSaveRole(null);

		$cb->callClientFunction('oRoles.save_role_cb');

		if ($result === true) {
			$amsg = '';
			if ($role_win_type == self::TYPE_ADD_WINDOW) {
				$amsg = "Create role. Role: $role";
			} elseif ($role_win_type == self::TYPE_EDIT_WINDOW) {
				$amsg = "Save role. Role: $role";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}
	}

	/**
	 * On save role event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveRole($param)
	{
		$this->raiseEvent('OnSaveRole', $this, $param);
	}

	/**
	 * Remove roles action.
	 * Here is possible to remove one role or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeRoles($sender, $param)
	{
		$roles = explode('|', $param->getCallbackParameter());
		$config = $this->getModule('role_config')->getConfig();
		$user_role = $this->getModule('user_role');
		for ($i = 0; $i < count($roles); $i++) {
			if (key_exists($roles[$i], $config)) {
				if ($user_role->isRolePreDefined($roles[$i])) {
					// Predefined roles cannot be saved
					continue;
				}
				unset($config[$roles[$i]]);
			}
		}
		$result = $this->getModule('role_config')->setConfig($config);
		$this->setRoleList($sender, $param);

		// refresh user window to now show removed roles
		$this->onRemoveRole(null);

		if ($result === true) {
			for ($i = 0; $i < count($roles); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove role. Role: {$roles[$i]}"
				);
			}
		}
	}

	/**
	 * On remove role event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveRole($param)
	{
		$this->raiseEvent('OnRemoveRole', $this, $param);
	}

	/**
	 * Add user statistics to roles.
	 * It adds user count to information about roles.
	 *
	 * @param array $role_config role config (note, passing by reference)
	 */
	private function addUserStatsToRoles(&$role_config)
	{
		$user_config =$this->getModule('user_config');
		$config = $user_config->getConfig();
		$user_roles = [];
		foreach ($role_config as $role => $prop) {
			$user_roles[$role] = 0;
		}
		foreach ($config as $uid => $prop) {
			$roles = explode(',', $prop['roles']);
			for ($i = 0; $i < count($roles); $i++) {
				$user_roles[$roles[$i]]++;
			}
		}
		foreach ($role_config as $role => $prop) {
			$role_config[$role]['user_count'] = $user_roles[$role];
		}
	}
}
