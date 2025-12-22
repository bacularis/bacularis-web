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
use Bacularis\Common\Modules\Ldap;
use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\HostConfig;
use Bacularis\Web\Modules\WebConfig;
use Bacularis\Web\Modules\WebUserRoles;
use Bacularis\Web\Modules\WebUserConfig;
use Prado\Prado;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TCommandEventParameter;

/**
 * Authentication control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AuthenticationMethods extends Security
{
	/**
	 * Options for import users.
	 */
	public const IMPORT_OPT_ALL_USERS = 0;
	public const IMPORT_OPT_SELECTED_USERS = 1;
	public const IMPORT_OPT_CRITERIA = 2;

	/**
	 * Options for import criteria.
	 */
	public const IMPORT_CRIT_USERNAME = 0;
	public const IMPORT_CRIT_LONG_NAME = 1;
	public const IMPORT_CRIT_DESCRIPTION = 2;
	public const IMPORT_CRIT_EMAIL = 3;

	/**
	 * Initialize module.
	 *
	 * @param mixed $param oninit event parameter
	 */
	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getPage()->IsCallBack || $this->getPage()->IsPostBack) {
			return;
		}
		$this->initAuthForm();
		$this->setBasicAuthConfig();
	}

	/**
	 * Initialize form with authentication method settings.
	 *
	 */
	public function initAuthForm()
	{
		if (isset($this->web_config['security']['auth_method'])) {
			if ($this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_LOCAL) {
				$this->LocalAuth->Checked = true;
			} elseif ($this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_BASIC) {
				$this->BasicAuth->Checked = true;
			} elseif ($this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_LDAP) {
				$this->LdapAuth->Checked = true;
			}

			// Fill LDAP auth fileds
			if (key_exists('auth_ldap', $this->web_config)) {
				$this->LdapAuthServerAddress->Text = $this->web_config['auth_ldap']['address'];
				$this->LdapAuthServerPort->Text = $this->web_config['auth_ldap']['port'];
				$this->LdapAuthServerLdaps->Checked = ($this->web_config['auth_ldap']['ldaps'] == 1);
				$this->LdapAuthServerStartTLS->Checked = (key_exists('starttls', $this->web_config['auth_ldap']) && $this->web_config['auth_ldap']['starttls'] == 1);
				$this->LdapAuthServerProtocolVersion->Text = $this->web_config['auth_ldap']['protocol_ver'];
				if ($this->web_config['auth_ldap']['auth_method'] === Ldap::AUTH_METHOD_ANON) {
					$this->LdapAuthMethodAnonymous->Checked = true;
				} elseif ($this->web_config['auth_ldap']['auth_method'] === Ldap::AUTH_METHOD_SIMPLE) {
					$this->LdapAuthMethodSimple->Checked = true;
				}
				$this->LdapAuthMethodSimpleUsername->Text = $this->web_config['auth_ldap']['bind_dn'];
				$this->LdapAuthMethodSimplePassword->Text = $this->web_config['auth_ldap']['bind_password'];
				$this->LdapAuthServerBaseDn->Text = $this->web_config['auth_ldap']['base_dn'];
				$this->LdapAuthServerFilters->Text = $this->web_config['auth_ldap']['filters'] ?? '';
				$this->LdapAttributesUsername->Text = $this->web_config['auth_ldap']['user_attr'];
				$this->LdapAttributesLongName->Text = $this->web_config['auth_ldap']['long_name_attr'];
				$this->LdapAttributesEmail->Text = $this->web_config['auth_ldap']['email_attr'];
				$this->LdapAttributesDescription->Text = $this->web_config['auth_ldap']['desc_attr'];
			}
			// Fill Basic auth fields
			if (key_exists('auth_basic', $this->web_config)) {
				$this->BasicAuthAllowManageUsers->Checked = ($this->web_config['auth_basic']['allow_manage_users'] == 1);
				$this->BasicAuthUserFile->Text = $this->web_config['auth_basic']['user_file'];
				$this->BasicAuthHashAlgorithm->SelectedValue = $this->web_config['auth_basic']['hash_alg'];
			}
		} else {
			// Default set to Basic auth method
			$this->BasicAuth->Checked = true;
		}
	}

	/**
	 * Set basic authentication user file.
	 *
	 */
	private function setBasicAuthConfig()
	{
		$web_config = $this->getModule('web_config');
		$is_basic = $web_config->isAuthMethodBasic();
		if ($is_basic && $this->isManageUsersAvail() && isset($this->web_config['auth_basic']['user_file'])) {
			$basic_webuser = $this->getModule('basic_webuser');
			$basic_webuser->setConfigPath($this->web_config['auth_basic']['user_file']);
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
		$prev_auth_method = $config['security']['auth_method'] ?? '';
		if ($this->LocalAuth->Checked) {
			$config['security']['auth_method'] = WebConfig::AUTH_METHOD_LOCAL;
		} elseif ($this->BasicAuth->Checked) {
			$config['security']['auth_method'] = WebConfig::AUTH_METHOD_BASIC;
			$config['auth_basic'] = $this->getBasicParams();
		} elseif ($this->LdapAuth->Checked) {
			$config['security']['auth_method'] = WebConfig::AUTH_METHOD_LDAP;
			$config['auth_ldap'] = $this->getLdapParams();
		}
		$web_config = $this->getModule('web_config');
		$ret = $web_config->setConfig($config);
		$cb = $this->getPage()->getCallbackClient();
		if ($ret === true) {
			$cb->hide('auth_method_save_error');
			$cb->show('auth_method_save_ok');
			if ($prev_auth_method != $config['security']['auth_method'] && $config['security']['auth_method'] != WebConfig::AUTH_METHOD_LOCAL) {
				// show advice info window if user switched to a new auth method
				$cb->show('admin_user_required_info');
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				'Save auth method authentication settings'
			);
		} else {
			$cb->hide('auth_method_save_ok');
			$cb->show('auth_method_save_error');
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				'Problem auth method authentication settings'
			);
		}
	}

	/**
	 * Get basic users and provide them to template.
	 *
	 * @param TActiveLinkButton $sender sender
	 * @param TCommandEventParameter $param event parameter object
	 */
	public function getBasicUsers($sender, $param)
	{
		$cb = $this->getPage()->getCallbackClient();
		if ($param instanceof TCommandEventParameter && $param->getCommandParameter() === 'load') {
			// reset criteria filters when modal is open
			$this->GetUsersImportOptions->SelectedValue = self::IMPORT_OPT_ALL_USERS;
			$this->GetUsersCriteria->SelectedValue = self::IMPORT_CRIT_USERNAME;
			$this->GetUsersCriteriaFilter->Text = '';
			$cb->hide('get_users_criteria');
			$cb->hide('get_users_advanced_options');

			// set role resources
			$this->setRoles(
				$this->GetUsersDefaultRole,
				WebUserRoles::NORMAL
			);

			// set API hosts
			$this->setAPIHosts(
				$this->GetUsersDefaultAPIHosts,
				HostConfig::MAIN_CATALOG_HOST,
				false
			);

			// set API host groups
			$this->setAPIHostGroups(
				$this->GetUsersDefaultAPIHostGroups
			);

			// set organizations
			$this->setOrganizations(
				$this->GetUsersDefaultOrganization
			);
		}

		$params = $this->getBasicParams();

		// add additional parameters
		$this->addBasicExtraParams($params);

		$pattern = '';
		if (!empty($params['filter_val'])) {
			$pattern = '*' . $params['filter_val'] . '*';
		}

		$basic = $this->getModule('basic_webuser');
		// set path from input because user can have unsaved changes
		$basic->setConfigPath($this->BasicAuthUserFile->Text);
		$users = $basic->getUsers($pattern);
		$users = array_keys($users);
		$user_list = $this->convertBasicUsers('', $users);
		$cb->callClientFunction('oUserSecurity.set_user_table_cb', [
			$user_list
		]);
		if (count($users) > 0) {
			// Success
			$this->TestBasicGetUsersMsg->Text = '';
			$this->TestBasicGetUsersMsg->Display = 'None';
			$cb->hide('basic_get_users_error');
			$cb->show('basic_get_users_ok');
		} else {
			// Error
			$cb->show('basic_get_users_error');
			$this->TestBasicGetUsersMsg->Text = Prado::localize('Empty user list');
			$this->TestBasicGetUsersMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Convert basic users from simple username list into full form.
	 * There is option to return user list in config file form or data table form.
	 *
	 * @param strong $org_id organization identifier
	 * @param array $users simple user list
	 * @param bool $config_form_result if true, sets the list in config file form
	 * @return array user list
	 */
	private function convertBasicUsers(string $org_id, array $users, $config_form_result = false)
	{
		$user_list = [];
		for ($i = 0; $i < count($users); $i++) {
			$uid = WebUserConfig::getOrgUserID($org_id, $users[$i]);
			$user = [
				'username' => $uid,
				'long_name' => '',
				'email' => '',
				'description' => ''
			];
			if ($config_form_result) {
				$user_list[$users[$i]] = $user;
			} else {
				$user_list[] = $user;
			}
		}
		return $user_list;
	}

	/**
	 * Get basic auth specific parameters with form values.
	 *
	 * @return array array basic auth parameters
	 */
	private function getBasicParams()
	{
		$params = [];
		$params['allow_manage_users'] = $this->BasicAuthAllowManageUsers->Checked ? 1 : 0;
		$params['user_file'] = $this->BasicAuthUserFile->Text;
		$params['hash_alg'] = $this->BasicAuthHashAlgorithm->SelectedValue;
		return $params;
	}

	/**
	 * Add to basic auth params additional parameters.
	 * Note, extra parameters are not set in config.
	 *
	 * @param array $params basic auth parameters (passing by reference)
	 */
	private function addBasicExtraParams(&$params)
	{
		if ($this->GetUsersImportOptions->SelectedValue == self::IMPORT_OPT_CRITERIA) {
			$params['filter_val'] = $this->GetUsersCriteriaFilter->Text;
		}
	}

	/**
	 * Prepare basic users to import.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter object
	 * @param string $org_id organization identifier
	 * @return array web users list to import
	 */
	public function prepareBasicUsers($sender, $param, $org_id)
	{
		$users_web = [];
		$import_opt = (int) $this->GetUsersImportOptions->SelectedValue;
		$basic_webuser = $this->getModule('basic_webuser');
		switch ($import_opt) {
			case self::IMPORT_OPT_ALL_USERS: {
				$users_web = $basic_webuser->getUsers();
				$users_web = array_keys($users_web);
				$users_web = $this->convertBasicUsers($org_id, $users_web, true);
				break;
			}
			case self::IMPORT_OPT_SELECTED_USERS: {
				if ($param instanceof TCallbackEventParameter) {
					$cb_param = $param->getCallbackParameter();
					if (is_array($cb_param)) {
						for ($i = 0; $i < count($cb_param); $i++) {
							$val = (array) $cb_param[$i];
							$uid = WebUserConfig::getOrgUserID($org_id, $val['username']);
							$users_web[$uid] = $val;
						}
					}
				}
				break;
			}
			case self::IMPORT_OPT_CRITERIA: {
				$params = $this->getBasicParams();
				// add additional parameters
				$this->addBasicExtraParams($params);
				if (!empty($params['filter_val'])) {
					$pattern = '*' . $params['filter_val'] . '*';
					$users_web = $basic_webuser->getUsers($pattern);
					$users_web = array_keys($users_web);
					$users_web = $this->convertBasicUsers($org_id, $users_web, true);
				}
				break;
			}
		}
		return $users_web;
	}

	/**
	 * Test basic user file.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter object
	 */
	public function doBasicUserFileTest($sender, $param)
	{
		$user_file = $this->BasicAuthUserFile->Text;
		$msg = '';
		$valid = true;
		if (!file_exists($user_file)) {
			$valid = false;
			$msg = Prado::localize('The user file is not accessible.');
		} elseif (!is_readable($user_file)) {
			$valid = false;
			$msg = Prado::localize('The user file is not readable by web server user.');
		} elseif (!is_writeable($user_file)) {
			$valid = false;
			$msg = Prado::localize('The user file is readable but not writeable by web server user.');
		}
		$this->BasicAuthUserFileMsg->Text = $msg;
		$cb = $this->getPage()->getCallbackClient();
		if ($valid) {
			$cb->show('basic_auth_user_file_test_ok');
			$this->BasicAuthUserFileMsg->Display = 'None';
		} else {
			$cb->show('basic_auth_user_file_test_error');
			$this->BasicAuthUserFileMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Get LDAP users and provide them to template.
	 *
	 * @param TActiveLinkButton $sender sender
	 * @param TCommandEventParameter $param event parameter object
	 */
	public function getLdapUsers($sender, $param)
	{
		$cb = $this->getPage()->getCallbackClient();
		if ($param instanceof TCommandEventParameter && $param->getCommandParameter() === 'load') {
			// reset criteria filters when modal is open
			$this->GetUsersImportOptions->SelectedValue = self::IMPORT_OPT_ALL_USERS;
			$this->GetUsersCriteria->SelectedValue = self::IMPORT_CRIT_USERNAME;
			$this->GetUsersCriteriaFilter->Text = '';
			$cb->hide('get_users_criteria');
			$cb->hide('get_users_advanced_options');

			// set role resources
			$this->setRoles(
				$this->GetUsersDefaultRole,
				WebUserRoles::NORMAL
			);

			// set API hosts
			$this->setAPIHosts(
				$this->GetUsersDefaultAPIHosts,
				HostConfig::MAIN_CATALOG_HOST,
				false
			);

			// set API host groups
			$this->setAPIHostGroups(
				$this->GetUsersDefaultAPIHostGroups
			);

			// set organizations
			$this->setOrganizations(
				$this->GetUsersDefaultOrganization
			);
		}

		$ldap = $this->getModule('ldap');
		$params = $this->getLdapParams();
		$ldap->setParams($params);

		// add additional parameters
		$this->addLdapExtraParams($params);

		$filter = $ldap->getFilter($params['user_attr'], '*');
		if (!empty($params['filter_attr']) && !empty($params['filter_val'])) {
			$filter = $ldap->getFilter(
				$params['filter_attr'],
				'*' . $params['filter_val'] . '*'
			);
		}

		$users = $ldap->findUserAttr($filter, $params['attrs']);
		$user_list = $this->convertLdapUsers('', $users, $params);
		$cb->callClientFunction('oUserSecurity.set_user_table_cb', [
			$user_list
		]);

		if (key_exists('count', $users)) {
			// Success
			$this->TestLdapGetUsersMsg->Text = '';
			$this->TestLdapGetUsersMsg->Display = 'None';
			$cb->show('ldap_get_users_ok');
		} else {
			// Error
			$cb->show('ldap_get_users_error');
			$this->TestLdapGetUsersMsg->Text = $ldap->getLdapError();
			$this->TestLdapGetUsersMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Convert LDAP users from simple username list into full form.
	 * There is option to return user list in config file form or data table form.
	 *
	 * @param string $org_id organization identifier
	 * @param array $users simple user list
	 * @param array $params LDAP specific parameters (@see getLdapParams)
	 * @param bool $config_form_result if true, sets the list in config file form
	 * @return array user list
	 */
	private function convertLdapUsers(string $org_id, array $users, array $params, $config_form_result = false)
	{
		$user_list = [];
		if (!key_exists('count', $users)) {
			return $user_list;
		}
		for ($i = 0; $i < $users['count']; $i++) {
			if (!key_exists($params['user_attr'], $users[$i])) {
				$emsg = "User attribute '{$params['user_attr']}' doesn't exist in LDAP response.";
				Logging::log(
					Logging::CATEGORY_EXTERNAL,
					$emsg
				);
				continue;
			}
			$username = $long_name = $email = $desc = '';
			if ($params['user_attr'] !== Ldap::DN_ATTR && $users[$i][$params['user_attr']]['count'] != 1) {
				$emsg = "Invalid user attribute count for '{$params['user_attr']}'. Is {$users[$i][$params['user_attr']]['count']}, should be 1.";
				Logging::log(
					Logging::CATEGORY_EXTERNAL,
					$emsg
				);
				continue;
			}
			$username = $users[$i][$params['user_attr']];
			if ($params['user_attr'] !== Ldap::DN_ATTR) {
				$username = $users[$i][$params['user_attr']][0];
			}

			if (key_exists($params['long_name_attr'], $users[$i])) {
				if ($params['long_name_attr'] === Ldap::DN_ATTR) {
					$long_name = $users[$i][$params['long_name_attr']];
				} elseif ($users[$i][$params['long_name_attr']]['count'] === 1) {
					$long_name = $users[$i][$params['long_name_attr']][0];
				}
			}

			if (key_exists($params['email_attr'], $users[$i])) {
				if ($params['email_attr'] === Ldap::DN_ATTR) {
					$email = $users[$i][$params['email_attr']];
				} elseif ($users[$i][$params['email_attr']]['count'] === 1) {
					$email = $users[$i][$params['email_attr']][0];
				}
			}

			if (key_exists($params['desc_attr'], $users[$i])) {
				if ($params['desc_attr'] === Ldap::DN_ATTR) {
					$desc = $users[$i][$params['desc_attr']];
				} elseif ($users[$i][$params['desc_attr']]['count'] === 1) {
					$desc = $users[$i][$params['desc_attr']][0];
				}
			}

			if ($config_form_result) {
				$uid = WebUserConfig::getOrgUserID($org_id, $username);
				$user_list[$uid] = [
					'long_name' => $long_name,
					'email' => $email,
					'description' => $desc
				];
			} else {
				$user_list[] = [
					'username' => $username,
					'long_name' => $long_name,
					'email' => $email,
					'description' => $desc
				];
			}
		}
		return $user_list;
	}


	/**
	 * Get LDAP auth specific parameters with form values.
	 *
	 * @return array array LDAP auth parameters
	 */
	private function getLdapParams()
	{
		$params = [];
		$params['address'] = $this->LdapAuthServerAddress->Text;
		$params['port'] = $this->LdapAuthServerPort->Text;
		$params['ldaps'] = $this->LdapAuthServerLdaps->Checked ? 1 : 0;
		$params['starttls'] = $this->LdapAuthServerStartTLS->Checked ? 1 : 0;
		$params['protocol_ver'] = $this->LdapAuthServerProtocolVersion->SelectedValue;
		$params['base_dn'] = $this->LdapAuthServerBaseDn->Text;
		$params['filters'] = $this->LdapAuthServerFilters->Text;
		if ($this->LdapAuthMethodAnonymous->Checked) {
			$params['auth_method'] = Ldap::AUTH_METHOD_ANON;
		} elseif ($this->LdapAuthMethodSimple->Checked) {
			$params['auth_method'] = Ldap::AUTH_METHOD_SIMPLE;
		}
		$params['bind_dn'] = $this->LdapAuthMethodSimpleUsername->Text;
		$params['bind_password'] = $this->LdapAuthMethodSimplePassword->Text;
		$params['user_attr'] = $this->LdapAttributesUsername->Text;
		$params['long_name_attr'] = $this->LdapAttributesLongName->Text;
		$params['desc_attr'] = $this->LdapAttributesDescription->Text;
		$params['email_attr'] = $this->LdapAttributesEmail->Text;
		return $params;
	}

	/**
	 * Add to LDAP auth params additional parameters.
	 * Note, extra parameters are not set in config.
	 *
	 * @param array $params LDAP auth parameters (passing by reference)
	 */
	private function addLdapExtraParams(&$params)
	{
		$params['attrs'] = [$params['user_attr']]; // user attribute is obligatory
		if (key_exists('long_name_attr', $params) && !empty($params['long_name_attr'])) {
			$params['attrs'][] = $params['long_name_attr'];
		}
		if (key_exists('email_attr', $params) && !empty($params['email_attr'])) {
			$params['attrs'][] = $params['email_attr'];
		}
		if (key_exists('desc_attr', $params) && !empty($params['desc_attr'])) {
			$params['attrs'][] = $params['desc_attr'];
		}
		if ($this->GetUsersImportOptions->SelectedValue == self::IMPORT_OPT_CRITERIA) {
			$crit = (int) ($this->GetUsersCriteria->SelectedValue);
			switch ($crit) {
				case self::IMPORT_CRIT_USERNAME: $params['filter_attr'] = $params['user_attr'];
					break;
				case self::IMPORT_CRIT_LONG_NAME: $params['filter_attr'] = $params['long_name_attr'];
					break;
				case self::IMPORT_CRIT_DESCRIPTION: $params['filter_attr'] = $params['desc_attr'];
					break;
				case self::IMPORT_CRIT_EMAIL: $params['filter_attr'] = $params['email_attr'];
					break;
			}
			$params['filter_val'] = $this->GetUsersCriteriaFilter->Text;
		}
	}


	/**
	 * Prepare LDAP users to import.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter object
	 * @param string $org_id organization id
	 * @return array web users list to import
	 */
	private function prepareLdapUsers($sender, $param, $org_id)
	{
		$ldap = $this->getModule('ldap');
		$params = $this->getLdapParams();
		$ldap->setParams($params);

		// add additional parameters
		$this->addLdapExtraParams($params);

		$import_opt = (int) $this->GetUsersImportOptions->SelectedValue;

		$users_web = [];
		switch ($import_opt) {
			case self::IMPORT_OPT_ALL_USERS: {
				$filter = $ldap->getFilter($params['user_attr'], '*');
				$users_ldap = $ldap->findUserAttr($filter, $params['attrs']);
				$users_web = $this->convertLdapUsers($org_id, $users_ldap, $params, true);
				break;
			}
			case self::IMPORT_OPT_SELECTED_USERS: {
				if ($param instanceof TCallbackEventParameter) {
					$cb_param = $param->getCallbackParameter();
					if (is_array($cb_param)) {
						for ($i = 0; $i < count($cb_param); $i++) {
							$val = (array) $cb_param[$i];
							$uid = WebUserConfig::getOrgUserID($org_id, $val['username']);
							$users_web[$uid] = $val;
						}
					}
				}
				break;
			}
			case self::IMPORT_OPT_CRITERIA: {
				if (!empty($params['filter_attr']) && !empty($params['filter_val'])) {
					$filter = $ldap->getFilter(
						$params['filter_attr'],
						'*' . $params['filter_val'] . '*'
					);
					$users_ldap = $ldap->findUserAttr($filter, $params['attrs']);
					$users_web = $this->convertLdapUsers($org_id, $users_ldap, $params, true);
				}
				break;
			}
		}
		return $users_web;
	}

	/**
	 * Test LDAP connection.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function testLdapConnection($sender, $param)
	{
		$ldap = $this->getModule('ldap');
		$params = $this->getLdapParams();
		$ldap->setParams($params);

		$cb = $this->getPage()->getCallbackClient();
		if ($ldap->adminBind()) {
			$this->TestLdapConnectionMsg->Text = '';
			$this->TestLdapConnectionMsg->Display = 'None';
			$cb->show('ldap_test_connection_ok');
		} else {
			$cb->show('ldap_test_connection_error');
			$this->TestLdapConnectionMsg->Text = $ldap->getLdapError();
			$this->TestLdapConnectionMsg->Display = 'Dynamic';
		}
	}

	/**
	 * Main method to import users.
	 * Supported are basic auth and LDAP auth user imports.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function importUsers($sender, $param)
	{
		if (!$this->GetUsersDefaultIps->IsValid) {
			// invalid IP restriction value
			return;
		}

		// Get default organization for imported users
		$org_id = $this->GetUsersDefaultOrganization->SelectedValue;

		$users_web = [];
		if ($this->BasicAuth->Checked) {
			$users_web = $this->prepareBasicUsers($sender, $param, $org_id);
		} elseif ($this->LdapAuth->Checked) {
			$users_web = $this->prepareLdapUsers($sender, $param, $org_id);
		}

		// Get default roles for imported users
		$def_roles = $this->GetUsersDefaultRole->getSelectedIndices();
		$role_list = [];
		foreach ($def_roles as $indice) {
			for ($i = 0; $i < $this->GetUsersDefaultRole->getItemCount(); $i++) {
				if ($i === $indice) {
					$role_list[] = $this->GetUsersDefaultRole->Items[$i]->Value;
				}
			}
		}
		$roles = implode(',', $role_list);

		$api_hosts = [];
		$api_host_groups = [];
		$api_hosts_method = WebUserConfig::API_HOST_METHOD_HOSTS;
		if ($this->GetUsersAPIHostsOpt->Checked) {
			$api_hosts_method = WebUserConfig::API_HOST_METHOD_HOSTS;

			// Get default API hosts for imported users
			$selected_indices = $this->GetUsersDefaultAPIHosts->getSelectedIndices();
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->GetUsersDefaultAPIHosts->getItemCount(); $i++) {
					if ($i === $indice) {
						$api_hosts[] = $this->GetUsersDefaultAPIHosts->Items[$i]->Value;
					}
				}
			}
		} elseif ($this->GetUsersAPIHostGroupsOpt->Checked) {
			$api_hosts_method = WebUserConfig::API_HOST_METHOD_HOST_GROUPS;

			// Get default API host groups config values
			$selected_indices = $this->GetUsersDefaultAPIHostGroups->getSelectedIndices();
			$api_host_groups = [];
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->GetUsersDefaultAPIHostGroups->getItemCount(); $i++) {
					if ($i === $indice) {
						$api_host_groups[] = $this->GetUsersDefaultAPIHostGroups->Items[$i]->Value;
					}
				}
			}
		}

		// Get default IP address restrictions for imported users
		$ips = $this->trimIps($this->GetUsersDefaultIps->Text);

		// fill missing default values
		$add_def_user_params = function (&$user, $idx) use ($roles, $api_hosts_method, $api_hosts, $api_host_groups, $org_id, $ips) {
			$user['roles'] = $roles;
			$user['api_hosts_method'] = $api_hosts_method;
			$user['api_hosts'] = $api_hosts;
			$user['api_host_groups'] = $api_host_groups;
			$user['organization_id'] = $org_id;
			$user['ips'] = $ips;
			$user['enabled'] = '1';
		};
		array_walk($users_web, $add_def_user_params);

		$user_mod = $this->getModule('user_config');
		$users = $user_mod->getConfig();

		$users_cfg = [];
		if ($this->GetUsersProtectOverwrite->Checked) {
			$users_cfg = array_merge($users_web, $users);
		} else {
			$users_cfg = array_merge($users, $users_web);
		}
		$result = $user_mod->setConfig($users_cfg);


		if ($result === true) {
			$user_count = count($users_web);
			$amsg = '';
			if ($this->BasicAuth->Checked) {
				$amsg = "Import Basic users. Count: $user_count";
			} elseif ($this->LdapAuth->Checked) {
				$amsg = "Import LDAP users. Count: $user_count";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}

		$this->onImportUsers(null);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oUserSecurity.show_user_modal', [
			false
		]);
	}

	/**
	 * On import users event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onImportUsers($param)
	{
		$this->raiseEvent('OnImportUsers', $this, $param);
	}

	/**
	 * Get users and provide them to template.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event object parameter
	 */
	public function getUsers($sender, $param)
	{
		if ($this->BasicAuth->Checked) {
			$this->getBasicUsers($sender, $param);
		} elseif ($this->LdapAuth->Checked) {
			$this->getLdapUsers($sender, $param);
		}
	}
}
