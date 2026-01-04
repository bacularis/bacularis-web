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
use Bacularis\Web\Modules\HostConfig;
use Bacularis\Web\Modules\WebConfig;
use Bacularis\Web\Modules\WebUserRoles;

/**
 * Authentication authentication settings control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AuthenticationGeneral extends Security
{
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
		$this->initDefAccessForm();
	}

	/**
	 * Initialize form with default access settings.
	 *
	 */
	public function initDefAccessForm()
	{
		$this->setRoles(
			$this->GeneralDefaultAccessRole,
			WebUserRoles::NORMAL
		);
		$this->setRoles(
			$this->GeneralProvisionUserAccessRole,
			WebUserRoles::NORMAL
		);

		$this->setAPIHosts(
			$this->GeneralDefaultAccessAPIHost,
			HostConfig::MAIN_CATALOG_HOST
		);
		$this->setAPIHosts(
			$this->GeneralProvisionUserAccessAPIHost,
			HostConfig::MAIN_CATALOG_HOST
		);
		$this->setOrganizations(
			$this->GeneralProvisionUserAccessOrganization
		);

		if (isset($this->web_config['security']['def_access'])) {
			if ($this->web_config['security']['def_access'] === WebConfig::DEF_ACCESS_NO_ACCESS) {
				$this->GeneralDefaultNoAccess->Checked = true;
			} elseif ($this->web_config['security']['def_access'] === WebConfig::DEF_ACCESS_DEFAULT_SETTINGS) {
				$this->GeneralDefaultAccess->Checked = true;
			} elseif ($this->web_config['security']['def_access'] === WebConfig::DEF_ACCESS_PROVISION_USER) {
				$this->GeneralProvisionUserAccess->Checked = true;
			}
			if (isset($this->web_config['security']['def_role'])) {
				$roles = $this->web_config['security']['def_role'];
				if (is_string($roles)) {
					$roles = [$roles]; // for backward config compatibility
				}
				$this->GeneralDefaultAccessRole->setSelectedValues($roles);
			}
			if (isset($this->web_config['security']['def_api_host'])) {
				$api_hosts = $this->web_config['security']['def_api_host'];
				if (is_string($api_hosts)) {
					$api_hosts = [$api_hosts]; // for backward config compatibility
				}
				$this->GeneralDefaultAccessAPIHost->setSelectedValues($api_hosts);
			}
			if (isset($this->web_config['security']['new_user_role'])) {
				$roles = $this->web_config['security']['new_user_role'];
				$this->GeneralProvisionUserAccessRole->setSelectedValues($roles);
			}
			if (isset($this->web_config['security']['new_user_api_host'])) {
				$api_hosts = $this->web_config['security']['new_user_api_host'];
				$this->GeneralProvisionUserAccessAPIHost->setSelectedValues($api_hosts);
			}
			if (isset($this->web_config['security']['new_user_organization_id'])) {
				$org_id = $this->web_config['security']['new_user_organization_id'];
				$this->GeneralProvisionUserAccessOrganization->setSelectedValue($org_id);
			}
		} else {
			$this->GeneralDefaultAccess->Checked = true;
		}
	}

	/**
	 * Save security config.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function saveSecurityConfig($sender, $param)
	{
		$config = $this->web_config;
		if (!key_exists('security', $config)) {
			$config['security'] = [];
		}
		if ($this->GeneralDefaultNoAccess->Checked) {
			$config['security']['def_access'] = WebConfig::DEF_ACCESS_NO_ACCESS;
		} elseif ($this->GeneralDefaultAccess->Checked) {
			$config['security']['def_access'] = WebConfig::DEF_ACCESS_DEFAULT_SETTINGS;
			$config['security']['def_role'] = $this->GeneralDefaultAccessRole->getSelectedValues();
			$config['security']['def_api_host'] = $this->GeneralDefaultAccessAPIHost->getSelectedValues();
		} elseif ($this->GeneralProvisionUserAccess->Checked) {
			$config['security']['def_access'] = WebConfig::DEF_ACCESS_PROVISION_USER;
			$config['security']['new_user_role'] = $this->GeneralProvisionUserAccessRole->getSelectedValues();
			$config['security']['new_user_api_host'] = $this->GeneralProvisionUserAccessAPIHost->getSelectedValues();
			$config['security']['new_user_organization_id'] = $this->GeneralProvisionUserAccessOrganization->getSelectedValue();
		}

		$web_config = $this->getModule('web_config');
		$ret = $web_config->setConfig($config);
		$cb = $this->getPage()->getCallbackClient();
		if ($ret === true) {
			$cb->hide('auth_general_save_error');
			$cb->show('auth_general_save_ok');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				'Save general authentication settings'
			);
		} else {
			$cb->hide('auth_general_save_ok');
			$cb->show('auth_general_save_error');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				'Problem with saving general authentication settings'
			);
		}
	}
}
