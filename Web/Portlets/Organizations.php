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
use Bacularis\Web\Modules\IdentityProviderConfig;
use Bacularis\Web\Modules\OrganizationConfig;
use Bacularis\Web\Modules\WebConfig;
use Prado\Prado;

/**
 * Organizations control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class Organizations extends Security
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load organization list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setOrganizationList($sender, $param)
	{
		$org_config = $this->getModule('org_config');
		$orgs = $org_config->getConfig();
		$idp_config = $this->getModule('idp_config');
		$idps = $idp_config->getConfig();

		$vals = array_values($orgs);
		$this->addOrganizationStatsInfo($vals);
		for ($i = 0; $i < count($vals); $i++) {
			$idp_type = ($idps[$vals[$i]['identity_provider']]['type'] ?? '-');
			$vals[$i]['idp_type'] = IdentityProviderConfig::getIDPDescByType($idp_type);
		}

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oOrganizations.load_organization_list_cb', [
			$vals
		]);

		// Load IdP list
		$this->setIdPList($sender, $param);
	}

	/**
	 * Set identity provider list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setIdPList($sender, $param)
	{
		$idp_config = $this->getModule('idp_config');
		$idps = $idp_config->getConfig();

		$idp_ids = array_keys($idps);
		array_unshift($idp_ids, '');
		$this->OrganizationIdP->DataSource = array_combine($idp_ids, $idp_ids);
		$this->OrganizationIdP->dataBind();
	}

	/**
	 * Add organization statistics.
	 *
	 * @param array organization configuration
	 */
	private function addOrganizationStatsInfo(&$vals)
	{
		$user_config = $this->getModule('user_config');
		$users = $user_config->getConfig();
		$user_list = array_values($users);
		$user_list_len = count($user_list);
		$stats = [];
		for ($i = 0; $i < $user_list_len; $i++)	{
			$org_id = $user_list[$i]['organization_id'] ?? '';
			if (!$org_id) {
				continue;
			}
			if (!key_exists($org_id, $stats)) {
				$stats[$org_id] = 0;
			}
			$stats[$org_id]++;
		}
		for ($i = 0; $i < count($vals); $i++) {
			if (!key_exists($vals[$i]['name'], $stats)) {
				$vals[$i]['user_no'] = 0;
				continue;
			}
			$vals[$i]['user_no'] = $stats[$vals[$i]['name']];
		}
	}

	/**
	 * Load data in organization modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadOrganizationWindow($sender, $param)
	{
		$name = $param->getCallbackParameter() ?? '';
		$cb = $this->getPage()->getCallbackClient();

		// prepare organization config
		$org = $this->getModule('org_config');
		$config = $org->getOrganizationConfig($name);

		if (count($config) > 0) {
			$this->OrganizationName->Text = $name;
			$this->OrganizationFullName->Text = $config['full_name'];
			$this->OrganizationDescription->Text = $config['description'];
			if ($config['auth_type'] == OrganizationConfig::AUTH_TYPE_AUTH_METHOD) {
				$this->OrganizationAuthMethodOpt->Checked = true;
				$cb->hide('organization_window_auth_type_idp');
			} elseif ($config['auth_type'] == OrganizationConfig::AUTH_TYPE_IDP) {
				$this->OrganizationIdPOpt->Checked = true;
				$cb->show('organization_window_auth_type_idp');
			}
			$this->OrganizationIdP->SelectedValue = $config['identity_provider'];
			$this->OrganizationEnabled->Checked = ($config['enabled'] == 1);
			$cb->setValue($this->OrganizationLoginBtnColor, $config['login_btn_color']);
			$cb->jQuery($this->OrganizationLoginBtnColor->ClientID . '_button', 'css', ['background-color', $config['login_btn_color']]);
		}

		$this->setCurrentAuthMethodLabel();
	}

	private function setCurrentAuthMethodLabel() {
		$web_config = $this->getModule('web_config');
		$security = $web_config->getConfig('security');
		$txt = Prado::localize('Use current auth method');
		$txt .= ' - ';
		switch($security['auth_method']) {
			case WebConfig::AUTH_METHOD_LOCAL: $txt .= Prado::localize('Local user authentication'); break;
			case WebConfig::AUTH_METHOD_BASIC: $txt .= Prado::localize('HTTP Basic authentication'); break;
			case WebConfig::AUTH_METHOD_LOCAL: $txt .= Prado::localize('LDAP authentication'); break;
		}
		$this->OrganizationCurrentAuthMethod->Text = $txt;
	}

	/**
	 * Save organization.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveOrganization($sender, $param)
	{
		$org_config = $this->getModule('org_config');
		$org_name = trim($this->OrganizationName->Text);
		$org_exists = $org_config->organizationConfigExists($org_name);
		$cfg_org = [];
		$cfg_org['name'] = $org_name;
		$cfg_org['full_name'] = $this->OrganizationFullName->Text;
		$cfg_org['description'] = $this->OrganizationDescription->Text;
		if ($this->OrganizationAuthMethodOpt->Checked) {
			$cfg_org['auth_type'] = OrganizationConfig::AUTH_TYPE_AUTH_METHOD;
		} elseif ($this->OrganizationIdPOpt->Checked) {
			$cfg_org['auth_type'] = OrganizationConfig::AUTH_TYPE_IDP;
		} else {
			$cfg_org['auth_type'] = OrganizationConfig::AUTH_TYPE_AUTH_METHOD;
		}
		$cfg_org['identity_provider'] = $this->OrganizationIdP->SelectedValue;
		$cfg_org['enabled'] = $this->OrganizationEnabled->Checked ? '1': '0';
		$cfg_org['login_btn_color'] = $this->OrganizationLoginBtnColor->Text;

		$org_win_type = $this->OrganizationWindowType->Value;
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->OrganizationErrorMsg);
		if ($org_win_type === self::TYPE_ADD_WINDOW) {
			if ($org_exists) {
				$msg = Prado::localize('Organization with identifier \'%s\' already exists.');
				$emsg = sprintf($msg, $org_name);
				$cb->update($this->OrganizationErrorMsg, $emsg);
				$cb->show($this->OrganizationErrorMsg);
				return;
			}
		}

		$config[$org_name] = $cfg_org;
		$result = $org_config->setOrganizationConfig($org_name, $cfg_org);
		$cb->hide('organization_window');

		if ($result === true) {
			if (!$org_exists) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Create organization. Name: $org_name"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Save organization. Name: $org_name"
				);
			}
		}

		$this->onSaveOrganization(null);

		// Refresh organization list
		$this->setOrganizationList($sender, $param);
	}

	/**
	 * On save organization event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveOrganization($param)
	{
		$this->raiseEvent('OnSaveOrganization', $this, $param);
	}

	/**
	 * Remove organization action.
	 * Here is possible to remove one organization or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeOrganizations($sender, $param)
	{
		$rm_orgs = $param->getCallbackParameter();
		$rm_orgs = json_decode(json_encode($rm_orgs), true);
		$names = [];
		$names_fbd = [];
		for ($i = 0; $i < count($rm_orgs); $i++) {
			if ($rm_orgs[$i]['user_no'] > 0) {
				$names_fbd[] = ['name' => $rm_orgs[$i]['name']];
			} else {
				$names[] = $rm_orgs[$i]['name'];
			}
		}
		$org_config = $this->getModule('org_config');
		$result = $org_config->removeOrganizationsConfig($names);
		if ($result === true) {
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove organizations. Name: {$names[$i]}"
				);
			}
		}

		$this->onRemoveOrganization(null);

		// Refresh organization list
		$this->setOrganizationList($sender, $param);

		if (count($names_fbd) > 0) {
			$this->OrganizationFbd->DataSource = $names_fbd;
			$this->OrganizationFbd->dataBind();
			$cb = $this->getPage()->getCallbackClient();
			$cb->show('organization_action_rm_warning_window');
		}
	}

	/**
	 * On remove organization event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveOrganization($param)
	{
		$this->raiseEvent('OnRemoveOrganization', $this, $param);
	}
}
