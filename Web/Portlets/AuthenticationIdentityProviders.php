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
use Bacularis\Common\Modules\PKCE;
use Bacularis\Web\Modules\IdentityProviderConfig;
use Bacularis\Web\Modules\OrganizationConfig;
use Prado\Prado;

/**
 * Authentication identity providers control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AuthenticationIdentityProviders extends Security
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load identity provider list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setIdPList($sender, $param)
	{
		$idp_config = $this->getModule('idp_config');
		$idps = $idp_config->getConfig();

		$vals = array_values($idps);
		$this->addIdPRelationInfo($vals);
		for ($i = 0; $i < count($vals); $i++) {
			$vals[$i]['idp_type'] = IdentityProviderConfig::getIDPDescByType($vals[$i]['type']);
		}

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oIdPs.load_idp_list_cb', [
			$vals
		]);
	}

	/**
	 * Add IdP relations.
	 *
	 * @param array IdP configuration
	 */
	private function addIdPRelationInfo(&$vals)
	{
		$org_config = $this->getModule('org_config');
		$orgs = $org_config->getConfig();
		$org_list = array_values($orgs);
		$org_list_len = count($org_list);

		$rels = [];
		for ($i = 0; $i < count($vals); $i++) {
			$vals[$i]['orgs'] = [];
			for ($j = 0; $j < $org_list_len; $j++) {
				if ($org_list[$j]['auth_type'] !== OrganizationConfig::AUTH_TYPE_IDP) {
					continue;
				}
				if ($vals[$i]['name'] !== $org_list[$j]['identity_provider']) {
					continue;
				}
				$vals[$i]['orgs'][] = $org_list[$j]['name'];
			}
		}
	}

	/**
	 * Load data in identity provider modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadIdPWindow($sender, $param)
	{
		$name = $param->getCallbackParameter() ?? '';

		$idp = $this->getModule('idp_config');
		$config = $idp->getIdentityProviderConfig($name);
		$cb = $this->getPage()->getCallbackClient();

		if (count($config) > 0) {
			$this->IdPName->Text = $name;
			$this->IdPFullName->Text = $config['full_name'];
			$this->IdPDescription->Text = $config['description'];
			$this->IdPType->SelectedValue = $config['type'];
			$this->IdPEnabled->Checked = ($config['enabled'] == 1);
			if ($config['type'] === IdentityProviderConfig::IDP_TYPE_OIDC) {
				$this->IdentityProviderOIDC->loadSettings($config);
			} elseif ($config['type'] === IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE) {
				$this->IdentityProviderOIDCGoogle->loadSettings($config);
			}
			$cb->callClientFunction(
				'oIdPUserSecurity.show_idp_settings',
				[$config['type'], true]
			);
		} else {
			$this->IdentityProviderOIDC->loadDefaultSettings();
			$this->IdentityProviderOIDCGoogle->loadDefaultSettings();
		}
		$cb->callClientFunction(
			'oIdPUserSecurity.load_settings'
		);
	}

	/**
	 * Save identity provider.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveIdP($sender, $param)
	{
		$idp_config = $this->getModule('idp_config');
		$idp_name = trim($this->IdPName->Text);
		$idp_exists = $idp_config->identityProviderConfigExists($idp_name);
		$cfg_idp = [];
		$cfg_idp['name'] = $idp_name;
		$cfg_idp['full_name'] = $this->IdPFullName->Text;
		$cfg_idp['description'] = $this->IdPDescription->Text;
		$cfg_idp['type'] = $this->IdPType->SelectedValue;
		$cfg_idp['enabled'] = $this->IdPEnabled->Checked ? '1': '0';

		$settings = [];
		switch ($cfg_idp['type']) {
			case IdentityProviderConfig::IDP_TYPE_OIDC: {
				$settings = $this->IdentityProviderOIDC->getSettings();
				break;
			}
			case IdentityProviderConfig::IDP_TYPE_OIDC_GOOGLE: {
				$settings = $this->IdentityProviderOIDCGoogle->getSettings();
				break;
			}
		}

		$cfg_idp = array_merge($cfg_idp, $settings);

		$idp_win_type = $this->IdPWindowType->Value;
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->IdPWindowError);
		if ($idp_win_type === self::TYPE_ADD_WINDOW) {
			if ($idp_exists) {
				$emsg = Prado::localize('Identity provider identifier \'%s\' already exists. Please type different identifier.');
				$emsg = sprintf($emsg, $idp_name);
				$cb->update($this->IdPWindowError, $emsg);
				$cb->show($this->IdPWindowError);
				return;
			}
		}

		$config[$idp_name] = $cfg_idp;
		$result = $idp_config->setIdentityProviderConfig($idp_name, $cfg_idp);
		$cb->hide('idp_window');

		if ($result === true) {
			if (!$idp_exists) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Create identity provider. Name: $idp_name"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Save identity provider. Name: $idp_name"
				);
			}
		}

		$this->onSaveIdP(null);

		// Refresh identity provider list
		$this->setIdPList($sender, $param);
	}

	/**
	 * On save IdP event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveIdP($param)
	{
		$this->raiseEvent('OnSaveIdP', $this, $param);
	}

	/**
	 * Remove identity provider action.
	 * Here is possible to remove one identity provider or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeIdPs($sender, $param)
	{
		$idps = $param->getCallbackParameter();
		$idp_list = json_decode(json_encode($idps), true);

		$names = [];
		$names_fbd = [];
		for ($i = 0; $i < count($idp_list); $i++) {
			if (count($idp_list[$i]['orgs']) > 0) {
				$names_fbd[] = ['name' => $idp_list[$i]['name']];
			} else {
				$names[] = $idp_list[$i]['name'];
			}
		}

		$idp_config = $this->getModule('idp_config');
		$result = $idp_config->removeIdentityProvidersConfig($names);
		if ($result === true) {
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove identity providers. Name: {$names[$i]}"
				);
			}
		}

		if (count($names_fbd) > 0) {
			$this->IdPFbd->DataSource = $names_fbd;
			$this->IdPFbd->dataBind();
			$cb = $this->getPage()->getCallbackClient();
			$cb->show('idp_action_rm_warning_window');
		}

		$this->onRemoveIdP(null);

		// Refresh identity provider list
		$this->setIdPList($sender, $param);
	}

	/**
	 * On remove IdP event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveIdP($param)
	{
		$this->raiseEvent('OnRemoveIdP', $this, $param);
	}
}
