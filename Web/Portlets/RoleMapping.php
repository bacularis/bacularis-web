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
use Bacularis\Web\Modules\OIDC;

/**
 * Role mapping list module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class RoleMapping extends Security
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
		$this->initRoleMappingWindow();
	}

	/**
	 * Initialize values in role mapping modal window.
	 *
	 */
	public function initRoleMappingWindow()
	{
		// set role list
		$role_config = $this->getModule('user_role');
		$roles = $role_config->getRoles();
		$resources = array_keys($roles);
		natcasesort($resources);
		$this->RoleMappingRoles->DataSource = array_combine($resources, $resources);
		$this->RoleMappingRoles->dataBind();
	}

	/**
	 * Set and load role mapping list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setRoleMappingList($sender, $param)
	{
		$role_mapping_config = $this->getModule('role_mapping_config');
		$config = $role_mapping_config->getConfig();
		$cb = $this->getPage()->getCallbackClient();
		$mappings = array_values($config);
		$this->addDependencyInfo($mappings);
		$cb->callClientFunction('oRoleMapping.load_role_mapping_list_cb', [
			$mappings
		]);
	}

	/**
	 * Add dependency info.
	 *
	 * @param array $vals role mappings
	 */
	private function addDependencyInfo(&$vals)
	{
		$idp_config = $this->getModule('idp_config');
		$idps = $idp_config->getConfig();
		$idp_list = array_values($idps);
		$idp_list_len = count($idp_list);

		for ($i = 0; $i < count($vals); $i++) {
			$vals[$i]['deps'] = [];
			for ($j = 0; $j < $idp_list_len; $j++) {
				if (!isset($idp_list[$j]['oidc_role_source']) || $idp_list[$j]['oidc_role_source'] !== OIDC::IDP_ROLE_SOURCE_IDP) {
					continue;
				}
				if (!isset($idp_list[$j]['oidc_role_mapping']) || $idp_list[$j]['oidc_role_mapping'] !== $vals[$i]['name']) {
					continue;
				}
				$vals[$i]['deps'][] = $idp_list[$j]['name'];
			}
		}
	}

	/**
	 * Load data in role mapping modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadRoleMappingWindow($sender, $param)
	{
		$role_mapping_name = $param->getCallbackParameter();
		$role_mapping_config = $this->getModule('role_mapping_config');
		$config = $role_mapping_config->getMappingConfig($role_mapping_name);
		$roles = [];
		if (count($config) > 0) {
			// Edit role window
			$this->RoleMappingName->Text = $config['name'];
			$this->RoleMappingDescription->Text = $config['description'];
			$this->RoleMappingEnabled->Checked = ($config['enabled'] == 1);
			$roles = $config['roles'] ?? [];
		}
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oRoleMapping.load_role_mapping_window_cb',
			[$roles]
		);
	}

	/**
	 * Save role mapping.
	 * It works both for new role mapping and for edited role mapping.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRoleMapping($sender, $param)
	{
		$role_mapping_win_type = $this->RoleMappingWindowType->Value;
		$role_mapping_name = $this->RoleMappingName->Text;
		$cb = $this->getPage()->getCallbackClient();
		$id_exists = 'role_mapping_window_role_mapping_exists';
		$cb->hide($id_exists);
		$role_mapping_config = $this->getModule('role_mapping_config');
		if ($role_mapping_win_type === self::TYPE_ADD_WINDOW) {
			$config = $role_mapping_config->getMappingConfig($role_mapping_name);
			if (count($config) > 0) {
				$cb->show($id_exists);
				return;
			}
		}
		$mappings = json_decode($this->RoleMappingValues->Value, true);
		$config = [];
		$config['description'] = $this->RoleMappingDescription->Text;
		$config['roles'] = $mappings;
		$config['enabled'] = $this->RoleMappingEnabled->Checked ? 1 : 0;
		$result = $role_mapping_config->setMappingConfig($role_mapping_name, $config);
		$this->setRoleMappingList($sender, $param);

		$this->onSaveRoleMapping(null);

		if ($result === true) {
			$amsg = '';
			if ($role_mapping_win_type == self::TYPE_ADD_WINDOW) {
				$amsg = "Create role mapping. Role mapping: $role_mapping_name";
			} elseif ($role_mapping_win_type == self::TYPE_EDIT_WINDOW) {
				$amsg = "Save role mapping. Role mapping: $role_mapping_name";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);

			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oRoleMapping.save_role_mapping_cb'
			);
		}
	}

	/**
	 * On save role mapping event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveRoleMapping($param)
	{
		$this->raiseEvent('OnSaveRoleMapping', $this, $param);
	}

	/**
	 * Remove roles action.
	 * Here is possible to remove one role mapping or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeRoleMappings($sender, $param)
	{
		$result = false;
		$mappings = [];
		$mappings_fbd = [];
		$role_mappings = $param->getCallbackParameter();
		$role_mapping_config = $this->getModule('role_mapping_config');
		for ($i = 0; $i < count($role_mappings); $i++) {
			if (count($role_mappings[$i]->deps) > 0) {
				$mappings_fbd[] = ['name' => $role_mappings[$i]->name];
			} else {
				$result = $role_mapping_config->removeMappingConfig($role_mappings[$i]->name);
				if (!$result) {
					// error happened, stop here
					break;
				}
				$mappings[] = $role_mappings[$i]->name;
			}

		}
		$this->setRoleMappingList($sender, $param);

		// refresh user window to now show removed roles
		$this->onRemoveRoleMapping(null);

		if ($result === true) {
			$audit = $this->getModule('audit');
			for ($i = 0; $i < count($mappings); $i++) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove role mapping. Role mapping: {$mappings[$i]}"
				);
			}
		}

		if (count($mappings_fbd) > 0) {
			$this->RoleMappingFbd->DataSource = $mappings_fbd;
			$this->RoleMappingFbd->dataBind();
			$cb = $this->getPage()->getCallbackClient();
			$cb->show('role_mapping_action_rm_warning_window');
		}
	}

	/**
	 * On remove role mapping event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveRoleMapping($param)
	{
		$this->raiseEvent('OnRemoveRoleMapping', $this, $param);
	}
}
