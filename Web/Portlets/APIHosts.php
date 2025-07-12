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
use Bacularis\Web\Modules\OAuth2Record;

/**
 * API host list module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class APIHosts extends Security
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
		$this->initAPIHostWindow();
		$this->loadAPIBasicUsers(null, null);
	}

	/**
	 * Initialize values in API host modal window.
	 *
	 */
	public function initAPIHostWindow()
	{
		// set API host groups
		$this->setAPIHostGroups($this->APIHostGroups);
	}

	/**
	 * Set and load basic user settings to API host modal window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIBasicUserSettings($sender, $param)
	{
		$username = $this->APIHostBasicUserSettings->SelectedValue;
		if (!empty($username)) {
			$host = $this->APIHostSettings->SelectedValue ?: null;
			$basic_cfg = $this->getModule('api')->get(['basic', 'users', $username], $host);
			if ($basic_cfg->error === 0 && is_object($basic_cfg->output)) {
				$this->APIHostBasicLogin->Text = $basic_cfg->output->username;
			}
		}
	}

	/**
	 * Load user list in API host window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIBasicUsers($sender, $param)
	{
		$basic_users = $this->getModule('api')->get(['basic', 'users']);
		if ($basic_users->error === 0) {
			$usernames = ['' => ''];
			for ($i = 0; $i < count($basic_users->output); $i++) {
				$usernames[$basic_users->output[$i]->username] = $basic_users->output[$i]->username;
			}
			uasort($usernames, 'strnatcasecmp');
			$this->APIHostBasicUserSettings->DataSource = $usernames;
			$this->APIHostBasicUserSettings->dataBind();
		}
	}

	/**
	 * Set and load OAuth2 client settings to API host modal window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadOAuth2ClientSettings($sender, $param)
	{
		$client_id = $this->APIHostOAuth2ClientSettings->SelectedValue;
		if (!empty($client_id)) {
			$host = $this->APIHostSettings->SelectedValue ?: null;
			$oauth2_cfg = $this->getModule('api')->get(['oauth2', 'clients', $client_id], $host);
			if ($oauth2_cfg->error === 0 && is_object($oauth2_cfg->output)) {
				$this->APIHostOAuth2ClientId->Text = $oauth2_cfg->output->client_id;
				$this->APIHostOAuth2ClientSecret->Text = $oauth2_cfg->output->client_secret;
				$this->APIHostOAuth2RedirectURI->Text = $oauth2_cfg->output->redirect_uri;
				$this->APIHostOAuth2Scope->Text = $oauth2_cfg->output->scope;
			}
		}
	}

	/**
	 * Load OAuth2 client list to get OAuth2 client settings.
	 *
	 */
	private function loadAPIOAuth2Clients()
	{
		$host = $this->APIHostSettings->SelectedValue ?: null;
		$oauth2_clients = $this->getModule('api')->get(['oauth2', 'clients'], $host);
		$oauth2_client_list = ['' => ''];
		if ($oauth2_clients->error == 0 && is_array($oauth2_clients->output)) {
			for ($i = 0; $i < count($oauth2_clients->output); $i++) {
				$name = $oauth2_clients->output[$i]->name ?: $oauth2_clients->output[$i]->client_id;
				$oauth2_client_list[$oauth2_clients->output[$i]->client_id] = $name;
			}
		}
		uasort($oauth2_client_list, 'strnatcasecmp');
		$this->APIHostOAuth2ClientSettings->DataSource = $oauth2_client_list;
		$this->APIHostOAuth2ClientSettings->dataBind();
	}

	/**
	 * Set and load API hosts list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setAPIHostList($sender, $param)
	{
		$api_hosts = $this->getModule('host_config')->getConfig();
		$shortnames = array_keys($api_hosts);
		$attributes = array_values($api_hosts);
		for ($i = 0; $i < count($attributes); $i++) {
			$attributes[$i]['name'] = $shortnames[$i];
		}

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oAPIHosts.load_api_host_list_cb', [
			$attributes
		]);
	}

	/**
	 * Load data in API host modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIHostWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();

		// prepare API host combobox
		$api_hosts = $this->getModule('host_config')->getConfig();

		if (!empty($name) && key_exists($name, $api_hosts)) {
			$this->APIHostAddress->Text = $api_hosts[$name]['address'];
			$this->APIHostProtocol->SelectedValue = $api_hosts[$name]['protocol'];
			$this->APIHostPort->Text = $api_hosts[$name]['port'];
			$this->APIHostOAuth2ClientId->Text = $api_hosts[$name]['client_id'];
			$this->APIHostOAuth2ClientSecret->Text = $api_hosts[$name]['client_secret'];
			$this->APIHostOAuth2RedirectURI->Text = $api_hosts[$name]['redirect_uri'];
			$this->APIHostOAuth2Scope->Text = $api_hosts[$name]['scope'];
			$this->APIHostName->Text = $name;
			$this->APIHostBasicLogin->Text = $api_hosts[$name]['login'];
			$this->APIHostBasicPassword->Text = $api_hosts[$name]['password'];
			$cb = $this->getPage()->getCallbackClient();
			if ($api_hosts[$name]['auth_type'] == 'basic') {
				$this->APIHostAuthBasic->Checked = true;
				$cb->hide('configure_oauth2_auth');
				$cb->show('configure_basic_auth');
			} elseif ($api_hosts[$name]['auth_type'] == 'oauth2') {
				$this->APIHostAuthOAuth2->Checked = true;
				$cb->hide('configure_basic_auth');
				$cb->show('configure_oauth2_auth');
			}
		}

		$shortnames = array_keys($api_hosts);
		natcasesort($shortnames);

		$api_host_names = array_combine($shortnames, $shortnames);
		$this->APIHostSettings->DataSource = array_merge(['' => ''], $api_host_names);
		$this->APIHostSettings->dataBind();

		// prepare OAuth2 client combobox
		$this->loadAPIOAuth2Clients();
	}

	/**
	 * Load API host settings to API host modal window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function loadAPIHostSettings($sender, $param)
	{
		$api_host = $this->APIHostSettings->SelectedValue;
		if (!empty($api_host)) {
			$config = $this->getModule('host_config')->getConfig();
			if (key_exists($api_host, $config)) {
				// load OAuth2 clients to combobox from selected API host
				$this->loadAPIOAuth2Clients();

				$this->APIHostProtocol->SelectedValue = $config[$api_host]['protocol'];
				$this->APIHostAddress->Text = $config[$api_host]['address'];
				$this->APIHostPort->Text = $config[$api_host]['port'];
				$cb = $this->getPage()->getCallbackClient();
				if ($config[$api_host]['auth_type'] == 'basic') {
					$this->APIHostAuthBasic->Checked = true;
					$cb->hide('configure_oauth2_auth');
					$cb->show('configure_basic_auth');
				} elseif ($config[$api_host]['auth_type'] == 'oauth2') {
					$this->APIHostAuthOAuth2->Checked = true;
					$cb->hide('configure_basic_auth');
					$cb->show('configure_oauth2_auth');
				}
			}
		}
	}

	public function connectionAPITest($sender, $param)
	{
		$host = $this->APIHostAddress->Text;
		if (empty($host)) {
			$host = false;
		}
		$host_params = [
			'protocol' => $this->APIHostProtocol->SelectedValue,
			'address' => $this->APIHostAddress->Text,
			'port' => $this->APIHostPort->Text,
			'url_prefix' => ''
		];

		if ($this->APIHostAuthBasic->Checked) {
			$host_params['auth_type'] = 'basic';
			$host_params['login'] = $this->APIHostBasicLogin->Text;
			$host_params['password'] = $this->APIHostBasicPassword->Text;
		} elseif ($this->APIHostAuthOAuth2->Checked) {
			$host_params['auth_type'] = 'oauth2';
			$host_params['client_id'] = $this->APIHostOAuth2ClientId->Text;
			$host_params['client_secret'] = $this->APIHostOAuth2ClientSecret->Text;
			$host_params['redirect_uri'] = $this->APIHostOAuth2RedirectURI->Text;
			$host_params['scope'] = $this->APIHostOAuth2Scope->Text;
		}
		$api = $this->getModule('api');

		// Catalog test
		OAuth2Record::deleteByPk($host);
		$api->setHostParams($host, $host_params);
		$catalog = $api->get(['catalog'], $host, false);

		// Console test
		OAuth2Record::deleteByPk($host);
		$api->setHostParams($host, $host_params);
		$sess = $this->getApplication()->getSession();
		$sess->open();
		$director = null;
		if ($sess->contains('director')) {
			// Current director can't be passed to new remote host.
			$director = $sess->remove('director');
		}

		$console = $api->set(['console'], ['version'], $host, false);
		if (!is_null($director)) {
			// Revert director setting if any
			$sess->add('director', $director);
		}

		// Config test
		OAuth2Record::deleteByPk($host);
		$api->setHostParams($host, $host_params);
		$config = $api->get(['config'], $host, false);
		OAuth2Record::deleteByPk($host);

		$is_catalog = (is_object($catalog) && $catalog->error === 0);
		$is_console = (is_object($console) && $console->error === 0);
		$is_config = (is_object($config) && $config->error === 0);

		$status_ok = $is_catalog;
		if ($status_ok) {
			$status_ok = $is_console;
		}

		if (!$is_catalog) {
			$this->APIHostTestResultErr->Text .= $catalog->output . '<br />';
		}
		if (!$is_console) {
			$this->APIHostTestResultErr->Text .= $console->output . '<br />';
		}
		if (!$is_config) {
			$config_output = '';
			if (!is_string($config->output)) {
				/**
				 * For special error codes that do not provide
				 * string in output like BaculaConfigError::ERROR_CONFIG_NO_JSONTOOL_READY
				 */
				$config_output = var_export($config->output, true);
			} else {
				$config_output = $config->output;
			}
			$this->APIHostTestResultErr->Text .= $config_output . '<br />';
		}

		$this->APIHostTestResultOk->Display = ($status_ok === true) ? 'Dynamic' : 'None';
		$this->APIHostTestResultErr->Display = ($status_ok === false) ? 'Dynamic' : 'None';
		$this->APIHostCatalogSupportYes->Display = ($is_catalog === true) ? 'Dynamic' : 'None';
		$this->APIHostCatalogSupportNo->Display = ($is_catalog === false) ? 'Dynamic' : 'None';
		$this->APIHostConsoleSupportYes->Display = ($is_console === true) ? 'Dynamic' : 'None';
		$this->APIHostConsoleSupportNo->Display = ($is_console === false) ? 'Dynamic' : 'None';
		$this->APIHostConfigSupportYes->Display = ($is_config === true) ? 'Dynamic' : 'None';
		$this->APIHostConfigSupportNo->Display = ($is_config === false) ? 'Dynamic' : 'None';
	}

	private function assignNewAPIHostToAPIGroups($host)
	{
		if (!$this->APIHostUseHostGroups->Checked) {
			// No use API groups checkebox checked, no assigning
			return;
		}

		$ahg = $this->getModule('host_group_config');
		$selected_indices = $this->APIHostGroups->getSelectedIndices();
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->APIHostGroups->getItemCount(); $i++) {
				if ($i === $indice) {
					$host_group = $this->APIHostGroups->Items[$i]->Value;
					$config = $ahg->getHostGroupConfig($host_group);
					if (!in_array($host, $config['api_hosts'])) {
						$config['api_hosts'][] = $host;
					}
					if ($ahg->setHostGroupConfig($host_group, $config)) {
						$this->getModule('audit')->audit(
							AuditLog::TYPE_INFO,
							AuditLog::CATEGORY_APPLICATION,
							"Newly created API host assigned to API group. API host: {$host},  host group: {$host_group}"
						);
					} else {
						$this->getModule('audit')->audit(
							AuditLog::TYPE_ERROR,
							AuditLog::CATEGORY_APPLICATION,
							"Error while assigning newly created API host to API group. API host: {$host},  host group: {$host_group}"
						);
					}
				}
			}
		}
	}

	public function saveAPIHost($sender, $param)
	{
		$cfg_host = [
			'auth_type' => '',
			'login' => '',
			'password' => '',
			'client_id' => '',
			'client_secret' => '',
			'redirect_uri' => '',
			'scope' => ''
		];
		$cfg_host['protocol'] = $this->APIHostProtocol->Text;
		$cfg_host['address'] = $this->APIHostAddress->Text;
		$cfg_host['port'] = $this->APIHostPort->Text;
		$cfg_host['url_prefix'] = '';
		if ($this->APIHostAuthBasic->Checked == true) {
			$cfg_host['auth_type'] = 'basic';
			$cfg_host['login'] = $this->APIHostBasicLogin->Text;
			$cfg_host['password'] = $this->APIHostBasicPassword->Text;
		} elseif ($this->APIHostAuthOAuth2->Checked == true) {
			$cfg_host['auth_type'] = 'oauth2';
			$cfg_host['client_id'] = $this->APIHostOAuth2ClientId->Text;
			$cfg_host['client_secret'] = $this->APIHostOAuth2ClientSecret->Text;
			$cfg_host['redirect_uri'] = $this->APIHostOAuth2RedirectURI->Text;
			$cfg_host['scope'] = $this->APIHostOAuth2Scope->Text;
		}
		$hc = $this->getModule('host_config');
		$config = $hc->getConfig();
		$host_name = trim($this->APIHostName->Text);
		if (empty($host_name)) {
			$host_name = $cfg_host['address'];
		}
		$host_exists = key_exists($host_name, $config);
		$config[$host_name] = $cfg_host;
		$result = $hc->setConfig($config);
		$this->setAPIHostList(null, null);
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide('api_host_window');

		if ($result === true) {
			if (!$host_exists) {
				$this->assignNewAPIHostToAPIGroups($host_name);
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Create API host. Name: $host_name"
				);
			} else {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Save API host. Name: $host_name"
				);
			}
		}

		$this->onSaveAPIHost(null);
	}

	/**
	 * On save API host event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveAPIHost($param)
	{
		$this->raiseEvent('OnSaveAPIHost', $this, $param);
	}

	/**
	 * Remove API host action.
	 * Here is possible to remove one API host or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeAPIHosts($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$hc = $this->getModule('host_config');
		$config = $hc->getConfig();
		$cfg = [];
		foreach ($config as $host => $opts) {
			if (in_array($host, $names)) {
				continue;
			}
			$cfg[$host] = $opts;
		}
		$result = $hc->setConfig($cfg);
		if ($result === true) {
			$uc = $this->getModule('user_config');
			$uc->unassignAPIHosts($names);
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove API host. Name: {$names[$i]}"
				);
			}
		}

		$this->setAPIHostList($sender, $param);

		$this->onRemoveAPIHost(null);
	}

	/**
	 * On remove API host event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveAPIHost($param)
	{
		$this->raiseEvent('OnRemoveAPIHost', $this, $param);
	}

	/**
	 * Load API host set resource access window.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAPIHostResourceAccessWindow($sender, $param)
	{
		$api_host = $param->getCallbackParameter();
		$this->setAPIHostJobs(
			$this->APIHostResourceAccessJobs,
			$api_host,
			'api_host_access_window_error'
		);
		$this->setAPIHostConsole($api_host);
		$this->setAPIHostResourcePermissions(
			$this->APIHostResourcePermissions,
			$api_host,
			'api_host_access_window_error'
		);
	}

	/**
	 * Save window with API hosts.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveAPIHostResourceAccess($sender, $param)
	{
		$api_host = $this->APIHostResourceAccessName->Value;
		$cb = $this->getPage()->getCallbackClient();
		if ($this->APIHostResourceAccessAllResources->Checked) {
			$state = $this->setResourceConsole(
				$api_host,
				'',
				'api_host_access_window_error'
			);
			if ($state) {
				$this->setAPIHostJobs(
					$this->APIHostResourceAccessJobs,
					$api_host,
					'api_host_access_window_error'
				);
				$this->setAPIHostConsole($api_host);
				$cb->hide('api_host_access_window');
			}
		} elseif ($this->APIHostResourceAccessSelectedResources->Checked) {
			$selected_indices = $this->APIHostResourceAccessJobs->getSelectedIndices();
			$jobs = [];
			foreach ($selected_indices as $indice) {
				for ($i = 0; $i < $this->APIHostResourceAccessJobs->getItemCount(); $i++) {
					if ($i === $indice) {
						$jobs[] = $this->APIHostResourceAccessJobs->Items[$i]->Value;
					}
				}
			}
			$console = $this->setJobResourceAccess(
				$api_host,
				$jobs,
				'api_host_access_window_error'
			);
			if ($console) {
				$state = $this->setResourceConsole(
					$api_host,
					$console,
					'api_host_access_window_error'
				);
				if ($state) {
					$this->setAPIHostJobs(
						$this->APIHostResourceAccessJobs,
						$api_host,
						'api_host_access_window_error'
					);
					$this->setAPIHostConsole($api_host);
					$cb->hide('api_host_access_window');
				}
			}
		}
		$this->saveAPIHostResourcePermissions(
			$this->APIHostResourcePermissions,
			$api_host,
			'api_host_access_window_error'
		);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_SECURITY,
			"Save API host access to resources. API host: $api_host"
		);
	}

	/**
	 * Set API host console.
	 *
	 * @param string $api_host API host name
	 * @param bool $set_state determine if state (all/selected resources) should be changed
	 */
	private function setAPIHostConsole($api_host, $set_state = true)
	{
		$console = $this->isAPIHostConsole($api_host);
		$cb = $this->getPage()->getCallbackClient();
		if (!empty($console)) {
			$cb->show('api_host_access_window_select_jobs');
			$cb->show('api_host_access_window_console');
			if ($set_state) {
				$this->APIHostResourceAccessSelectedResources->Checked = true;
			}
		} else {
			if ($set_state) {
				$this->APIHostResourceAccessAllResources->Checked = true;
			}
		}
	}

	/**
	 * Unassign console from API host.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function unassignAPIHostConsole($sender, $param)
	{
		$api_host = $param->getCallbackParameter();
		$success = $this->unassignAPIHostConsoleInternal($api_host);
		if ($success) {
			$this->setAPIHostJobs(
				$this->APIHostResourceAccessJobs,
				$api_host,
				'api_host_access_window_error'
			);
			$this->setAPIHostConsole($api_host, false);
			$cb = $this->getPage()->getCallbackClient();
			$cb->hide('api_host_access_window_console');
		}
	}

}
