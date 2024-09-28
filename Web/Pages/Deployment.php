<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

use Prado\Prado;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\DeployAPIHost;
use Bacularis\Web\Modules\HostConfig;
use Bacularis\Web\Modules\OAuth2Record;
use Bacularis\Web\Modules\OSProfileConfig;
use Bacularis\Web\Modules\SSH;
use Bacularis\Web\Modules\WebUserConfig;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Errors\BaculaConfigError;

/**
 * Deployment page.
 * Install/upgrade/remove Bacula software, OS profiles, SSH config...
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class Deployment extends BaculumWebPage
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	public const API_HOST_SHORT_NAME_PATTERN = '[a-zA-Z0-9:.\-_ ]+';

	public const DEPLOY_STEPS_TXT = [
		'add_bacularis_repo' => 'Add package repository',
		'add_sudo' => 'Add SUDO settings',
		'pre_install_bacularis' => 'Pre-install Bacularis command',
		'install_bacularis' => 'Install Bacularis',
		'post_install_bacularis' => 'Post-install Bacularis command',
		'configure_bacularis' => 'Configure Bacularis',
		'create_bacularis_user' => 'Create Bacularis user',
		'set_bacularis_pwd' => 'Set Bacularis password',
		'start_bacularis' => 'Start Bacularis',
		'access_link' => 'API access link'
	];

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

		$this->getCallbackClient()->callClientFunction('oAPIHosts.load_api_host_list_cb', [
			$attributes
		]);
	}
	/**
	 * Set API host list control.
	 */
	private function setAPIHostGroupsControl()
	{
		$hgc = $this->getModule('host_group_config')->getConfig();
		$host_groups = array_keys($hgc);
		natcasesort($host_groups);
		$this->DeployAPIHostGroups->DataSource = array_combine($host_groups, $host_groups);
		$this->DeployAPIHostGroups->dataBind();
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
			if ($api_hosts[$name]['auth_type'] == 'basic') {
				$this->APIHostAuthBasic->Checked = true;
				$this->getCallbackClient()->hide('configure_oauth2_auth');
				$this->getCallbackClient()->show('configure_basic_auth');
			} elseif ($api_hosts[$name]['auth_type'] == 'oauth2') {
				$this->APIHostAuthOAuth2->Checked = true;
				$this->getCallbackClient()->hide('configure_basic_auth');
				$this->getCallbackClient()->show('configure_oauth2_auth');
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
		$director = null;
		if (array_key_exists('director', $_SESSION)) {
			// Current director can't be passed to new remote host.
			$director = $_SESSION['director'];
			unset($_SESSION['director']);
		}

		$console = $api->set(['console'], ['version'], $host, false);
		if (!is_null($director)) {
			// Revert director setting if any
			$_SESSION['director'] = $director;
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
			$this->APIHostTestResultErr->Text .= $config->output . '<br />';
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

		$this->setAPIHostList(null, null);
	}


	/**
	 * Set and load OS profile list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setOSProfileList($sender, $param)
	{
		$config = $this->getModule('osprofile_config')->getConfig();
		$profiles = array_values($config);
		$names = array_keys($config);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$this->getCallbackClient()->callClientFunction('oOSProfiles.load_osprofile_list_cb', [
			$profiles
		]);
		array_unshift($names, '');
		$osps = array_combine($names, $names);

		// main osprofile table
		$this->DeployAPIHostOSProfile->DataSource = $osps;
		$this->DeployAPIHostOSProfile->dataBind();

		// Combobox to copy resource
		$this->OSProfileToCopy->DataSource = $osps;
		$this->OSProfileToCopy->dataBind();
	}

	/**
	 * Load data in OS profile  modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadOSProfileWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if ($this->OSProfileWindowType->Value === self::TYPE_EDIT_WINDOW) {
			$config = $this->getModule('osprofile_config')->getConfig($name);
			if (count($config) > 0) {
				// It is done only for existing OS profile
				$this->setOSProfileFieldsInWindow($config);

				if (key_exists('predefined', $config) && $config['predefined'] === true) {
					$this->OSProfileSave->Display = 'None';
					$this->getCallbackClient()->show('osprofile_window_predefined_profile_msg');
				} else {
					$this->OSProfileSave->Display = 'Dynamic';
					$this->getCallbackClient()->hide('osprofile_window_predefined_profile_msg');
				}
			}
			$this->getCallbackClient()->hide('osprofile_window_copy_osprofile');
		} elseif ($this->OSProfileWindowType->Value === self::TYPE_ADD_WINDOW) {
			$this->OSProfileSave->Display = 'Dynamic';
			$this->getCallbackClient()->hide('osprofile_window_predefined_profile_msg');
			$this->getCallbackClient()->show('osprofile_window_copy_osprofile');
		}
	}

	/**
	 * Set OS profile fields in the add/edit profile window.
	 *
	 * @param array $config single OS profile config
	 */
	private function setOSProfileFieldsInWindow(array $config)
	{
		// General
		$this->OSProfileName->Text = $config['name'];
		$this->OSProfileDescription->Text = $config['description'];

		// Repositories
		$this->OSProfileRepositoryType->Text = $config['repository_type'];
		$this->OSProfileBacularisRepositoryAddr->Text = $config['bacularis_repository_addr'];
		$this->OSProfileBacularisRepositoryKey->Text = $config['bacularis_repository_key'];
		$this->OSProfileBacularisRepositoryAuth->SelectedValue = $config['bacularis_repository_auth'] ?? '';
		$this->OSProfileBacularisRepositoryAuthDef->Checked = empty($config['bacularis_repository_auth']);
		if ($this->OSProfileBacularisRepositoryAuthDef->Checked) {
			$this->getCallbackClient()->hide('osprofile_window_bacularis_repo_auth');
		} else {
			$this->getCallbackClient()->show('osprofile_window_bacularis_repo_auth');
		}
		$this->OSProfileBacularisAdminUser->Text = $config['bacularis_admin_user'];
		$this->OSProfileBacularisAdminPwd->Text = $config['bacularis_admin_pwd'];
		$this->OSProfileBacularisUseHTTPS->Checked = $config['bacularis_use_https'] == 1;
		$this->OSProfileBaculaUseSystemRepo->Checked = $config['bacula_use_system_repo'] == 1;
		if (!$this->OSProfileBaculaUseSystemRepo->Checked) {
			$this->getCallbackClient()->show('osprofile_window_bacula_repo_options');
		}
		$this->OSProfileBaculaRepositoryAddr->Text = $config['bacula_repository_addr'];
		$this->OSProfileBaculaRepositoryKey->Text = $config['bacula_repository_key'];

		// Bacularis commands
		$this->OSProfilePackagesUseSudo->Checked = $config['packages_use_sudo'] == 1;
		if ($this->OSProfilePackagesUseSudo->Checked) {
			$this->getCallbackClient()->show('osprofile_packages_sudo_user');
		}
		$this->OSProfilePackagesBacularisStart->Text = $config['packages_bacularis_start'];
		$this->OSProfilePackagesSudoUser->Text = $config['packages_sudo_user'];
		$this->OSProfilePackagesBacularisInstall->Text = $config['packages_bacularis_install'];
		$this->OSProfilePackagesBacularisUpgrade->Text = $config['packages_bacularis_upgrade'];
		$this->OSProfilePackagesBacularisRemove->Text = $config['packages_bacularis_remove'];
		$this->OSProfilePackagesBacularisInfo->Text = $config['packages_bacularis_info'];
		$this->OSProfilePackagesBacularisEnable->Text = $config['packages_bacularis_enable'];
		$this->OSProfilePackagesPreBacularisInstallCmd->Text = $config['packages_bacularis_pre_install_cmd'];
		$this->OSProfilePackagesPreBacularisUpgradeCmd->Text = $config['packages_bacularis_pre_upgrade_cmd'];
		$this->OSProfilePackagesPreBacularisRemoveCmd->Text = $config['packages_bacularis_pre_remove_cmd'];
		$this->OSProfilePackagesPostBacularisInstallCmd->Text = $config['packages_bacularis_post_install_cmd'];
		$this->OSProfilePackagesPostBacularisUpgradeCmd->Text = $config['packages_bacularis_post_upgrade_cmd'];
		$this->OSProfilePackagesPostBacularisRemoveCmd->Text = $config['packages_bacularis_post_remove_cmd'];

		// Catalog commands
		$this->OSProfilePackagesCatInstall->Text = $config['packages_cat_install'] ?? '';
		$this->OSProfilePackagesCatUpgrade->Text = $config['packages_cat_upgrade'] ?? '';
		$this->OSProfilePackagesCatRemove->Text = $config['packages_cat_remove'] ?? '';
		$this->OSProfilePackagesCatInfo->Text = $config['packages_cat_info'] ?? '';
		$this->OSProfilePackagesCatEnable->Text = $config['packages_cat_enable'] ?? '';
		$this->OSProfilePackagesPreCatInstallCmd->Text = $config['packages_cat_pre_install_cmd'] ?? '';
		$this->OSProfilePackagesPreCatUpgradeCmd->Text = $config['packages_cat_pre_upgrade_cmd'] ?? '';
		$this->OSProfilePackagesPreCatRemoveCmd->Text = $config['packages_cat_pre_remove_cmd'] ?? '';
		$this->OSProfilePackagesPostCatInstallCmd->Text = $config['packages_cat_post_install_cmd'] ?? '';
		$this->OSProfilePackagesPostCatUpgradeCmd->Text = $config['packages_cat_post_upgrade_cmd'] ?? '';
		$this->OSProfilePackagesPostCatRemoveCmd->Text = $config['packages_cat_post_remove_cmd'] ?? '';

		// Director commands
		$this->OSProfilePackagesDirInstall->Text = $config['packages_dir_install'];
		$this->OSProfilePackagesDirUpgrade->Text = $config['packages_dir_upgrade'];
		$this->OSProfilePackagesDirRemove->Text = $config['packages_dir_remove'];
		$this->OSProfilePackagesDirInfo->Text = $config['packages_dir_info'];
		$this->OSProfilePackagesDirEnable->Text = $config['packages_dir_enable'];
		$this->OSProfilePackagesPreDirInstallCmd->Text = $config['packages_dir_pre_install_cmd'];
		$this->OSProfilePackagesPreDirUpgradeCmd->Text = $config['packages_dir_pre_upgrade_cmd'];
		$this->OSProfilePackagesPreDirRemoveCmd->Text = $config['packages_dir_pre_remove_cmd'];
		$this->OSProfilePackagesPostDirInstallCmd->Text = $config['packages_dir_post_install_cmd'];
		$this->OSProfilePackagesPostDirUpgradeCmd->Text = $config['packages_dir_post_upgrade_cmd'];
		$this->OSProfilePackagesPostDirRemoveCmd->Text = $config['packages_dir_post_remove_cmd'];

		// Storage daemon commands
		$this->OSProfilePackagesSdInstall->Text = $config['packages_sd_install'];
		$this->OSProfilePackagesSdUpgrade->Text = $config['packages_sd_upgrade'];
		$this->OSProfilePackagesSdRemove->Text = $config['packages_sd_remove'];
		$this->OSProfilePackagesSdInfo->Text = $config['packages_sd_info'];
		$this->OSProfilePackagesSdEnable->Text = $config['packages_sd_enable'];
		$this->OSProfilePackagesPreSdInstallCmd->Text = $config['packages_sd_pre_install_cmd'];
		$this->OSProfilePackagesPreSdUpgradeCmd->Text = $config['packages_sd_pre_upgrade_cmd'];
		$this->OSProfilePackagesPreSdRemoveCmd->Text = $config['packages_sd_pre_remove_cmd'];
		$this->OSProfilePackagesPostSdInstallCmd->Text = $config['packages_sd_post_install_cmd'];
		$this->OSProfilePackagesPostSdUpgradeCmd->Text = $config['packages_sd_post_upgrade_cmd'];
		$this->OSProfilePackagesPostSdRemoveCmd->Text = $config['packages_sd_post_remove_cmd'];

		// File daemon commands
		$this->OSProfilePackagesFdInstall->Text = $config['packages_fd_install'];
		$this->OSProfilePackagesFdUpgrade->Text = $config['packages_fd_upgrade'];
		$this->OSProfilePackagesFdRemove->Text = $config['packages_fd_remove'];
		$this->OSProfilePackagesFdInfo->Text = $config['packages_fd_info'];
		$this->OSProfilePackagesFdEnable->Text = $config['packages_fd_enable'];
		$this->OSProfilePackagesPreFdInstallCmd->Text = $config['packages_fd_pre_install_cmd'];
		$this->OSProfilePackagesPreFdUpgradeCmd->Text = $config['packages_fd_pre_upgrade_cmd'];
		$this->OSProfilePackagesPreFdRemoveCmd->Text = $config['packages_fd_pre_remove_cmd'];
		$this->OSProfilePackagesPostFdInstallCmd->Text = $config['packages_fd_post_install_cmd'];
		$this->OSProfilePackagesPostFdUpgradeCmd->Text = $config['packages_fd_post_upgrade_cmd'];
		$this->OSProfilePackagesPostFdRemoveCmd->Text = $config['packages_fd_post_remove_cmd'];

		// Bconsole commands
		$this->OSProfilePackagesBconsInstall->Text = $config['packages_bcons_install'];
		$this->OSProfilePackagesBconsUpgrade->Text = $config['packages_bcons_upgrade'];
		$this->OSProfilePackagesBconsRemove->Text = $config['packages_bcons_remove'];
		$this->OSProfilePackagesBconsInfo->Text = $config['packages_bcons_info'];
		$this->OSProfilePackagesPreBconsInstallCmd->Text = $config['packages_bcons_pre_install_cmd'];
		$this->OSProfilePackagesPreBconsUpgradeCmd->Text = $config['packages_bcons_pre_upgrade_cmd'];
		$this->OSProfilePackagesPreBconsRemoveCmd->Text = $config['packages_bcons_pre_remove_cmd'];
		$this->OSProfilePackagesPostBconsInstallCmd->Text = $config['packages_bcons_post_install_cmd'];
		$this->OSProfilePackagesPostBconsUpgradeCmd->Text = $config['packages_bcons_post_upgrade_cmd'];
		$this->OSProfilePackagesPostBconsRemoveCmd->Text = $config['packages_bcons_post_remove_cmd'];

		// Configuration
		$this->OSProfileJSONToolsUseSudo->Checked = $config['jsontools_use_sudo'] == 1;
		$this->OSProfileJSONToolsWorkDir->Text = $config['jsontools_bconfig_dir'];
		$this->OSProfileJSONToolsBDirJSONPath->Text = $config['jsontools_bdirjson_path'];
		$this->OSProfileJSONToolsDirCfgPath->Text = $config['jsontools_dir_cfg_path'];
		$this->OSProfileJSONToolsBSdJSONPath->Text = $config['jsontools_bsdjson_path'];
		$this->OSProfileJSONToolsSdCfgPath->Text = $config['jsontools_sd_cfg_path'];
		$this->OSProfileJSONToolsBFdJSONPath->Text = $config['jsontools_bfdjson_path'];
		$this->OSProfileJSONToolsFdCfgPath->Text = $config['jsontools_fd_cfg_path'];
		$this->OSProfileJSONToolsBBconsJSONPath->Text = $config['jsontools_bbconsjson_path'];
		$this->OSProfileJSONToolsBconsCfgPath->Text = $config['jsontools_bcons_cfg_path'];

		// Console
		$this->OSProfileBconsoleUseSudo->Checked = $config['bconsole_use_sudo'] == 1;
		$this->OSProfileBconsoleBin->Text = $config['bconsole_bin_path'];
		$this->OSProfileBconsoleCfg->Text = $config['bconsole_cfg_path'];

		// Catalog
		$this->OSProfileDbType->SelectedValue = $config['db_type'];
		$this->OSProfileDbName->Text = $config['db_name'];
		$this->OSProfileDbLogin->Text = $config['db_login'];
		$this->OSProfileDbPassword->Text = $config['db_password'];
		$this->OSProfileDbAddress->Text = $config['db_ip_addr'];
		$this->OSProfileDbPort->Text = $config['db_port'];
		$this->OSProfileDbPath->Text = $config['db_path'];

		// Actions
		$this->OSProfileActionsUseSudo->Checked = $config['actions_use_sudo'] == 1;
		$this->OSProfileActionsDirStart->Text = $config['actions_dir_start'];
		$this->OSProfileActionsDirStop->Text = $config['actions_dir_stop'];
		$this->OSProfileActionsDirRestart->Text = $config['actions_dir_restart'];
		$this->OSProfileActionsSdStart->Text = $config['actions_sd_start'];
		$this->OSProfileActionsSdStop->Text = $config['actions_sd_stop'];
		$this->OSProfileActionsSdRestart->Text = $config['actions_sd_restart'];
		$this->OSProfileActionsFdStart->Text = $config['actions_fd_start'];
		$this->OSProfileActionsFdStop->Text = $config['actions_fd_stop'];
		$this->OSProfileActionsFdRestart->Text = $config['actions_fd_restart'];
	}

	public function saveOSProfile($sender, $param)
	{
		$win_type = $this->OSProfileWindowType->Value;
		$osprofile = $this->OSProfileName->Text;
		$config = $this->getModule('osprofile_config')->getOSProfileConfig($osprofile);
		$this->getCallbackClient()->hide('osprofile_window_osprofilename_exists');
		if ($win_type === self::TYPE_ADD_WINDOW) {
			if (count($config) > 0) {
				$this->getCallbackClient()->show('osprofile_window_osprofilename_exists');
				return;
			}
		}
		$config['name'] = $osprofile;
		$config['description'] = $this->OSProfileDescription->Text;
		$config['bacularis_admin_user'] = $this->OSProfileBacularisAdminUser->Text;
		$config['bacularis_admin_pwd'] = $this->OSProfileBacularisAdminPwd->Text;
		$config['bacularis_use_https'] = $this->OSProfileBacularisUseHTTPS->Checked ? '1' : '0';
		$config['packages_use_sudo'] = $this->OSProfilePackagesUseSudo->Checked ? '1' : '0';
		$config['packages_sudo_user'] = $this->OSProfilePackagesSudoUser->Text;
		$config['packages_bacularis_start'] = $this->OSProfilePackagesBacularisStart->Text;
		$config['packages_bacularis_install'] = $this->OSProfilePackagesBacularisInstall->Text;
		$config['packages_bacularis_upgrade'] = $this->OSProfilePackagesBacularisUpgrade->Text;
		$config['packages_bacularis_remove'] = $this->OSProfilePackagesBacularisRemove->Text;
		$config['packages_bacularis_info'] = $this->OSProfilePackagesBacularisInfo->Text;
		$config['packages_bacularis_enable'] = $this->OSProfilePackagesBacularisEnable->Text;
		$config['packages_bacularis_pre_install_cmd'] = $this->OSProfilePackagesPreBacularisInstallCmd->Text;
		$config['packages_bacularis_pre_upgrade_cmd'] = $this->OSProfilePackagesPreBacularisUpgradeCmd->Text;
		$config['packages_bacularis_pre_remove_cmd'] = $this->OSProfilePackagesPreBacularisRemoveCmd->Text;
		$config['packages_bacularis_post_install_cmd'] = $this->OSProfilePackagesPostBacularisInstallCmd->Text;
		$config['packages_bacularis_post_upgrade_cmd'] = $this->OSProfilePackagesPostBacularisUpgradeCmd->Text;
		$config['packages_bacularis_post_remove_cmd'] = $this->OSProfilePackagesPostBacularisRemoveCmd->Text;
		$config['packages_cat_install'] = $this->OSProfilePackagesCatInstall->Text;
		$config['packages_cat_upgrade'] = $this->OSProfilePackagesCatUpgrade->Text;
		$config['packages_cat_remove'] = $this->OSProfilePackagesCatRemove->Text;
		$config['packages_cat_info'] = $this->OSProfilePackagesCatInfo->Text;
		$config['packages_cat_enable'] = $this->OSProfilePackagesCatEnable->Text;
		$config['packages_cat_pre_install_cmd'] = $this->OSProfilePackagesPreCatInstallCmd->Text;
		$config['packages_cat_pre_upgrade_cmd'] = $this->OSProfilePackagesPreCatUpgradeCmd->Text;
		$config['packages_cat_pre_remove_cmd'] = $this->OSProfilePackagesPreCatRemoveCmd->Text;
		$config['packages_cat_post_install_cmd'] = $this->OSProfilePackagesPostCatInstallCmd->Text;
		$config['packages_cat_post_upgrade_cmd'] = $this->OSProfilePackagesPostCatUpgradeCmd->Text;
		$config['packages_cat_post_remove_cmd'] = $this->OSProfilePackagesPostCatRemoveCmd->Text;
		$config['packages_dir_install'] = $this->OSProfilePackagesDirInstall->Text;
		$config['packages_dir_upgrade'] = $this->OSProfilePackagesDirUpgrade->Text;
		$config['packages_dir_remove'] = $this->OSProfilePackagesDirRemove->Text;
		$config['packages_dir_info'] = $this->OSProfilePackagesDirInfo->Text;
		$config['packages_dir_enable'] = $this->OSProfilePackagesDirEnable->Text;
		$config['packages_dir_pre_install_cmd'] = $this->OSProfilePackagesPreDirInstallCmd->Text;
		$config['packages_dir_pre_upgrade_cmd'] = $this->OSProfilePackagesPreDirUpgradeCmd->Text;
		$config['packages_dir_pre_remove_cmd'] = $this->OSProfilePackagesPreDirRemoveCmd->Text;
		$config['packages_dir_post_install_cmd'] = $this->OSProfilePackagesPostDirInstallCmd->Text;
		$config['packages_dir_post_upgrade_cmd'] = $this->OSProfilePackagesPostDirUpgradeCmd->Text;
		$config['packages_dir_post_remove_cmd'] = $this->OSProfilePackagesPostDirRemoveCmd->Text;
		$config['packages_sd_install'] = $this->OSProfilePackagesSdInstall->Text;
		$config['packages_sd_upgrade'] = $this->OSProfilePackagesSdUpgrade->Text;
		$config['packages_sd_remove'] = $this->OSProfilePackagesSdRemove->Text;
		$config['packages_sd_info'] = $this->OSProfilePackagesSdInfo->Text;
		$config['packages_sd_enable'] = $this->OSProfilePackagesSdEnable->Text;
		$config['packages_sd_pre_install_cmd'] = $this->OSProfilePackagesPreSdInstallCmd->Text;
		$config['packages_sd_pre_upgrade_cmd'] = $this->OSProfilePackagesPreSdUpgradeCmd->Text;
		$config['packages_sd_pre_remove_cmd'] = $this->OSProfilePackagesPreSdRemoveCmd->Text;
		$config['packages_sd_post_install_cmd'] = $this->OSProfilePackagesPostSdInstallCmd->Text;
		$config['packages_sd_post_upgrade_cmd'] = $this->OSProfilePackagesPostSdUpgradeCmd->Text;
		$config['packages_sd_post_remove_cmd'] = $this->OSProfilePackagesPostSdRemoveCmd->Text;
		$config['packages_fd_install'] = $this->OSProfilePackagesFdInstall->Text;
		$config['packages_fd_upgrade'] = $this->OSProfilePackagesFdUpgrade->Text;
		$config['packages_fd_remove'] = $this->OSProfilePackagesFdRemove->Text;
		$config['packages_fd_info'] = $this->OSProfilePackagesFdInfo->Text;
		$config['packages_fd_enable'] = $this->OSProfilePackagesFdEnable->Text;
		$config['packages_fd_pre_install_cmd'] = $this->OSProfilePackagesPreFdInstallCmd->Text;
		$config['packages_fd_pre_upgrade_cmd'] = $this->OSProfilePackagesPreFdUpgradeCmd->Text;
		$config['packages_fd_pre_remove_cmd'] = $this->OSProfilePackagesPreFdRemoveCmd->Text;
		$config['packages_fd_post_install_cmd'] = $this->OSProfilePackagesPostFdInstallCmd->Text;
		$config['packages_fd_post_upgrade_cmd'] = $this->OSProfilePackagesPostFdUpgradeCmd->Text;
		$config['packages_fd_post_remove_cmd'] = $this->OSProfilePackagesPostFdRemoveCmd->Text;
		$config['packages_bcons_install'] = $this->OSProfilePackagesBconsInstall->Text;
		$config['packages_bcons_upgrade'] = $this->OSProfilePackagesBconsUpgrade->Text;
		$config['packages_bcons_remove'] = $this->OSProfilePackagesBconsRemove->Text;
		$config['packages_bcons_info'] = $this->OSProfilePackagesBconsInfo->Text;
		$config['packages_bcons_pre_install_cmd'] = $this->OSProfilePackagesPreBconsInstallCmd->Text;
		$config['packages_bcons_pre_upgrade_cmd'] = $this->OSProfilePackagesPreBconsUpgradeCmd->Text;
		$config['packages_bcons_pre_remove_cmd'] = $this->OSProfilePackagesPreBconsRemoveCmd->Text;
		$config['packages_bcons_post_install_cmd'] = $this->OSProfilePackagesPostBconsInstallCmd->Text;
		$config['packages_bcons_post_upgrade_cmd'] = $this->OSProfilePackagesPostBconsUpgradeCmd->Text;
		$config['packages_bcons_post_remove_cmd'] = $this->OSProfilePackagesPostBconsRemoveCmd->Text;
		$config['db_type'] = $this->OSProfileDbType->Text;
		$config['db_name'] = $this->OSProfileDbName->Text;
		$config['db_login'] = $this->OSProfileDbLogin->Text;
		$config['db_password'] = $this->OSProfileDbPassword->Text;
		$config['db_ip_addr'] = $this->OSProfileDbAddress->Text;
		$config['db_port'] = $this->OSProfileDbPort->Text;
		$config['db_path'] = $this->OSProfileDbPath->Text;
		$config['bconsole_use_sudo'] = $this->OSProfileBconsoleUseSudo->Checked ? '1' : '0';
		$config['bconsole_bin_path'] = $this->OSProfileBconsoleBin->Text;
		$config['bconsole_cfg_path'] = $this->OSProfileBconsoleCfg->Text;
		$config['jsontools_use_sudo'] = $this->OSProfileJSONToolsUseSudo->Checked ? '1' : '0';
		$config['jsontools_bconfig_dir'] = $this->OSProfileJSONToolsWorkDir->Text;
		$config['jsontools_bdirjson_path'] = $this->OSProfileJSONToolsBDirJSONPath->Text;
		$config['jsontools_dir_cfg_path'] = $this->OSProfileJSONToolsDirCfgPath->Text;
		$config['jsontools_bsdjson_path'] = $this->OSProfileJSONToolsBSdJSONPath->Text;
		$config['jsontools_sd_cfg_path'] = $this->OSProfileJSONToolsSdCfgPath->Text;
		$config['jsontools_bfdjson_path'] = $this->OSProfileJSONToolsBFdJSONPath->Text;
		$config['jsontools_fd_cfg_path'] = $this->OSProfileJSONToolsFdCfgPath->Text;
		$config['jsontools_bbconsjson_path'] = $this->OSProfileJSONToolsBBconsJSONPath->Text;
		$config['jsontools_bcons_cfg_path'] = $this->OSProfileJSONToolsBconsCfgPath->Text;
		$config['actions_use_sudo'] = $this->OSProfileActionsUseSudo->Checked ? '1' : '0';
		$config['actions_dir_start'] = $this->OSProfileActionsDirStart->Text;
		$config['actions_dir_stop'] = $this->OSProfileActionsDirStop->Text;
		$config['actions_dir_restart'] = $this->OSProfileActionsDirRestart->Text;
		$config['actions_sd_start'] = $this->OSProfileActionsSdStart->Text;
		$config['actions_sd_stop'] = $this->OSProfileActionsSdStop->Text;
		$config['actions_sd_restart'] = $this->OSProfileActionsSdRestart->Text;
		$config['actions_fd_start'] = $this->OSProfileActionsFdStart->Text;
		$config['actions_fd_stop'] = $this->OSProfileActionsFdStop->Text;
		$config['actions_fd_restart'] = $this->OSProfileActionsFdRestart->Text;
		$config['repository_type'] = $this->OSProfileRepositoryType->SelectedValue;
		$config['bacularis_repository_key'] = $this->OSProfileBacularisRepositoryKey->Text;
		$config['bacularis_repository_addr'] = $this->OSProfileBacularisRepositoryAddr->Text;
		if ($this->OSProfileBacularisRepositoryAuthDef->Checked) {
			$config['bacularis_repository_auth'] = '';
		} else {
			$config['bacularis_repository_auth'] = $this->OSProfileBacularisRepositoryAuth->SelectedValue;
		}
		$config['bacula_use_system_repo'] = $this->OSProfileBaculaUseSystemRepo->Checked ? '1' : '0';
		$config['bacula_repository_key'] = $this->OSProfileBaculaRepositoryKey->Text;
		$config['bacula_repository_addr'] = $this->OSProfileBaculaRepositoryAddr->Text;
		$result = $this->getModule('osprofile_config')->setOSProfileConfig($osprofile, $config);
		if ($result === true) {
			$amsg = '';
			if ($win_type == self::TYPE_ADD_WINDOW) {
				$amsg = "Create OS profile config. Config: $osprofile";
			} elseif ($win_type == self::TYPE_EDIT_WINDOW) {
				$amsg = "Save OS profile config. Config: $osprofile";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
			$this->getCallbackClient()->hide('osprofile_window');
		}

		// refresh OS profile list
		$this->setOSProfileList(null, null);
	}

	/**
	 * Remove OS profiles action.
	 * Here is possible to remove one profile or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeOSProfiles($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$osprofile_config = $this->getModule('osprofile_config');
		$config = $osprofile_config->getConfig(null, false);
		for ($i = 0; $i < count($names); $i++) {
			if (key_exists($names[$i], $config)) {
				unset($config[$names[$i]]);
			}
		}
		$result = $osprofile_config->setConfig($config);

		if ($result === true) {
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove OS profile. OS profile name: {$names[$i]}"
				);
			}
		}

		// refresh OS profile list
		$this->setOSProfileList(null, null);
	}

	public function adaptOSProfileToBaculaOrgRepo($sender, $param)
	{
		$repo_type = $this->OSProfileRepositoryType->getSelectedValue();
		$this->OSProfileBaculaRepositoryAddr->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['bacula_repository_addr'];
		$this->OSProfileBaculaRepositoryKey->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['bacula_repository_key'];
		$this->OSProfilePackagesCatInstall->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_cat_install'];
		$this->OSProfilePackagesPostCatInstallCmd->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_cat_post_install_cmd'];
		$this->OSProfilePackagesDirInstall->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_dir_install'];
		$this->OSProfilePackagesDirUpgrade->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_dir_upgrade'];
		$this->OSProfilePackagesDirRemove->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_dir_remove'];
		$this->OSProfilePackagesDirInfo->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_dir_info'];
		$this->OSProfilePackagesDirEnable->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_dir_enable'];
		$this->OSProfilePackagesPostDirInstallCmd->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_dir_post_install_cmd'];
		$this->OSProfilePackagesSdInstall->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_sd_install'];
		$this->OSProfilePackagesSdUpgrade->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_sd_upgrade'];
		$this->OSProfilePackagesSdRemove->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_sd_remove'];
		$this->OSProfilePackagesSdInfo->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_sd_info'];
		$this->OSProfilePackagesSdEnable->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_sd_enable'];
		$this->OSProfilePackagesPostSdInstallCmd->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_sd_post_install_cmd'];
		$this->OSProfilePackagesFdInstall->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_fd_install'];
		$this->OSProfilePackagesFdUpgrade->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_fd_upgrade'];
		$this->OSProfilePackagesFdRemove->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_fd_remove'];
		$this->OSProfilePackagesFdInfo->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_fd_info'];
		$this->OSProfilePackagesFdEnable->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_fd_enable'];
		$this->OSProfilePackagesPostFdInstallCmd->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_fd_post_install_cmd'];
		$this->OSProfilePackagesBconsInstall->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_bcons_install'];
		$this->OSProfilePackagesBconsUpgrade->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_bcons_upgrade'];
		$this->OSProfilePackagesBconsRemove->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_bcons_remove'];
		$this->OSProfilePackagesBconsInfo->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_bcons_info'];
		$this->OSProfilePackagesPostBconsInstallCmd->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['packages_bcons_post_install_cmd'];
		$this->OSProfileBconsoleBin->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['bconsole_bin_path'];
		$this->OSProfileBconsoleCfg->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['bconsole_cfg_path'];
		$this->OSProfileJSONToolsBDirJSONPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_bdirjson_path'];
		$this->OSProfileJSONToolsDirCfgPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_dir_cfg_path'];
		$this->OSProfileJSONToolsBSdJSONPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_bsdjson_path'];
		$this->OSProfileJSONToolsSdCfgPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_sd_cfg_path'];
		$this->OSProfileJSONToolsBFdJSONPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_bfdjson_path'];
		$this->OSProfileJSONToolsFdCfgPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_fd_cfg_path'];
		$this->OSProfileJSONToolsBBconsJSONPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_bbconsjson_path'];
		$this->OSProfileJSONToolsBconsCfgPath->Text = OSProfileConfig::BACULA_ORG_REPOSITORY[$repo_type]['jsontools_bcons_cfg_path'];
	}

	/**
	 * Set and load repo auth list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setRepoAuthList($sender, $param)
	{
		$config = $this->getModule('repoauth_config')->getConfig();
		foreach ($config as $name => $cfg) {
			$config[$name]['name'] = $name;
		}
		$this->getCallbackClient()->callClientFunction('oRepoAuths.load_repo_auth_list_cb', [
			array_values($config)
		]);

		// Update repo auth list in OS profile window
		$repo_auths = array_keys($config);
		$this->OSProfileBacularisRepositoryAuth->DataSource = array_combine($repo_auths, $repo_auths);
		$this->OSProfileBacularisRepositoryAuth->dataBind();
	}

	/**
	 * Load data in repo auth window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadRepoAuthWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if ($this->RepoAuthWindowType->Value === self::TYPE_EDIT_WINDOW) {
			$repo_auth = $this->getModule('repoauth_config');
			$config = $repo_auth->getRepoAuthConfig($name);
			if (count($config) > 0) {
				// It is done only for existing ssh config
				$this->RepoAuthName->Text = $name;
				$this->RepoAuthType->SelectedValue = $config['auth_type'];
				$this->RepoAuthUsername->Text = $config['username'];
				$this->RepoAuthPassword->Text = $config['password'];
				$this->RepoAuthDefault->Checked = ($config['default'] == 1);
			}
		}
		$this->getCallbackClient()->hide('repo_auth_window_repo_authname_exists');
	}

	/**
	 * Save repo auth.
	 * It works both for new repo auths and for edited repo auths.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRepoAuth($sender, $param)
	{
		$repo_auth_win_type = $this->RepoAuthWindowType->Value;
		$name = $this->RepoAuthName->Text;
		$repo_auth = $this->getModule('repoauth_config');
		$config = $repo_auth->getRepoAuthConfig($name);
		$this->getCallbackClient()->hide('repo_auth_window_repo_authname_exists');
		if ($repo_auth_win_type === self::TYPE_ADD_WINDOW) {
			if (count($config) > 0) {
				$this->getCallbackClient()->show('repo_auth_window_repo_authname_exists');
				return;
			}
		}

		$config['auth_type'] = $this->RepoAuthType->SelectedValue;
		$config['username'] = $this->RepoAuthUsername->Text;
		$config['password'] = $this->RepoAuthPassword->Text;
		$config['default'] = $this->RepoAuthDefault->Checked ? '1' : '0';

		$result = $repo_auth->setRepoAuthConfig($name, $config);
		if ($result === true) {
			$amsg = '';
			if ($repo_auth_win_type == self::TYPE_ADD_WINDOW) {
				$amsg = "Create repo auth. Config: $name";
			} elseif ($repo_auth_win_type == self::TYPE_EDIT_WINDOW) {
				$amsg = "Save repo auth. Config: $name";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}
		$this->getCallbackClient()->callClientFunction('oRepoAuths.save_repo_auth_cb');

		$this->setRepoAuthList(null, null);
	}

	/**
	 * Remove repo auth action.
	 * Here is possible to remove one config or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeRepoAuths($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$repo_auth = $this->getModule('repoauth_config');
		$result = true;
		for ($i = 0; $i < count($names); $i++) {
			$success = $repo_auth->removeRepoAuthConfig($names[$i]);
			if (!$success) {
				$result = false;
				break;
			}
		}
		if ($result === true) {
			// Repo auths removed. Now remove it from all dependent OS profiles.
			$osprofile_config = $this->getModule('osprofile_config');
			$osprofiles = $osprofile_config->getConfig(null, false);
			foreach ($osprofiles as $pname => $pval) {
				if (isset($pval['bacularis_repository_auth']) && in_array($pval['bacularis_repository_auth'], $names)) {
					$osprofiles[$pname]['bacularis_repository_auth'] = '';
				}
			}
			$osprofile_config->setConfig($osprofiles);

			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove repo auth. repo auth name: {$names[$i]}"
				);
			}
		}

		// refresh repo auth list
		$this->setRepoAuthList(null, null);
	}

	/**
	 * Set and load SSH config list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setSSHConfigList($sender, $param)
	{
		$config = $this->getModule('ssh_config')->getConfig();
		$ssh_key = $this->getModule('ssh_key');
		foreach ($config as $hostname => $cfg) {
			$config[$hostname]['Hostname'] = $hostname;
			if (key_exists('IdentityFile', $config[$hostname])) {
				$config[$hostname]['IdentityFile'] = $ssh_key->getNameByKey($cfg['IdentityFile']);
			} else {
				$config[$hostname]['IdentityFile'] = '';
			}
		}
		$this->getCallbackClient()->callClientFunction('oSSHConfigs.load_ssh_config_list_cb', [
			array_values($config)
		]);
	}

	/**
	 * Load data in SSH config window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadSSHConfigWindow($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if ($this->SSHConfigWindowType->Value === self::TYPE_EDIT_WINDOW) {
			$ssh_config = $this->getModule('ssh_config');
			$config = $ssh_config->getConfig($name);
			if (count($config) > 0) {
				// It is done only for existing ssh config
				$this->SSHConfigHostname->Text = $name;
				$this->SSHConfigPort->Text = $config['Port'];
				$this->SSHConfigUsername->Text = $config['User'];
				if (key_exists('IdentityFile', $config)) {
					$this->SSHConfigSSHKey->SelectedValue = $this->getModule('ssh_key')->getNameByKey($config['IdentityFile']);
				}
			}
		}
	}

	/**
	 * Save SSH config.
	 * It works both for new SSH configs and for edited SSH configs.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveSSHConfig($sender, $param)
	{
		$ssh_config_win_type = $this->SSHConfigWindowType->Value;
		$hostname = $this->SSHConfigHostname->Text;
		$ssh_config = $this->getModule('ssh_config');
		$config = $ssh_config->getHostConfig($hostname);
		$this->getCallbackClient()->hide('ssh_config_window_ssh_configname_exists');
		if ($ssh_config_win_type === self::TYPE_ADD_WINDOW) {
			if (key_exists($hostname, $config)) {
				$this->getCallbackClient()->show('ssh_config_window_ssh_configname_exists');
				return;
			}
		}

		$config['Port'] = $this->SSHConfigPort->Text;
		$config['User'] = $this->SSHConfigUsername->Text;
		$key = trim($this->SSHConfigSSHKey->Text);
		if (!empty($key)) {
			$config['IdentityFile'] = $this->getModule('ssh_key')->getPathByName($key);
		} elseif (key_exists('IdentityFile', $config)) {
			unset($config['IdentityFile']);
		}

		$result = $ssh_config->setHostConfig($hostname, $config);
		if ($result === true) {
			$amsg = '';
			if ($ssh_config_win_type == self::TYPE_ADD_WINDOW) {
				$amsg = "Create SSH config. Config: $hostname";
			} elseif ($ssh_config_win_type == self::TYPE_EDIT_WINDOW) {
				$amsg = "Save SSH config. Config: $hostname";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}
		$this->getCallbackClient()->callClientFunction('oSSHConfigs.save_ssh_config_cb');

		$this->setSSHConfigList(null, null);
	}

	/**
	 * Remove SSH config action.
	 * Here is possible to remove one config or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeSSHConfigs($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$ssh_config = $this->getModule('ssh_config');
		$config = $ssh_config->getConfig();
		for ($i = 0; $i < count($names); $i++) {
			if (key_exists($names[$i], $config)) {
				unset($config[$names[$i]]);
			}
		}
		$result = $ssh_config->setConfig($config);

		if ($result === true) {
			for ($i = 0; $i < count($names); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove SSH config. SSH config name: {$names[$i]}"
				);
			}
		}

		// refresh SSH config list
		$this->setSSHConfigList(null, null);
	}

	/**
	 * Load and set SSH key list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function setSSHKeyList($sender, $param)
	{
		$ssh_keys = $this->getModule('ssh_key')->getKeys();
		$this->getCallbackClient()->callClientFunction('oSSHKeys.load_ssh_key_list_cb', [
			$ssh_keys
		]);

		$keys = ['' => ''];
		for ($i = 0; $i < count($ssh_keys); $i++) {
			$keys[] = $ssh_keys[$i]['key'];
		}
		$key_list = array_combine($keys, $keys);

		$this->SSHConfigSSHKey->DataSource = $key_list;
		$this->SSHConfigSSHKey->dataBind();

		$this->DeployAPIHostSSHKey->DataSource = $key_list;
		$this->DeployAPIHostSSHKey->dataBind();
	}

	/**
	 * Save SSH key.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter callback parameter
	 * @param mixed $param
	 */
	public function saveSSHKey($sender, $param)
	{
		$key = $this->SSHKeyName->Text;
		$content = $this->SSHKeyContent->Text;
		$result = $this->getModule('ssh_key')->setKey($key, $content);
		if ($result === true) {
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"Add SSH key. Name: $key"
			);
		}

		// refresh SSH key list
		$this->setSSHKeyList(null, null);

		// close new SSH key window
		$this->getCallbackClient()->hide('ssh_key_window');
	}

	/**
	 * Remove SSH key action.
	 * Here is possible to remove one SSH key or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeSSHKeys($sender, $param)
	{
		$names = explode('|', $param->getCallbackParameter());
		$ssh_keys = $this->getModule('ssh_key');
		$result = true;
		for ($i = 0; $i < count($names); $i++) {
			$result = $ssh_keys->removeKey($names[$i]);
			if ($result === true) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove SSH key. Name: {$names[$i]}"
				);
			} else {
				break;
			}
		}
		$this->setSSHKeyList(null, null);
	}

	/**
	 * Load deploy API host window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadDeployAPIHostWindow($sender, $param)
	{
		$this->setAPIHostGroupsControl();
	}

	public function checkDeployAPIHostConnection($sender, $param)
	{
		// First create directory for temporary files for all other steps
		$ssh = $this->getModule('ssh');
		$deploy_api = $this->getModule('deploy_api');
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$cmd = $deploy_api->getCheckHostConnectionCommand();
		if ($params['use_sudo']) {
			array_unshift($cmd, 'sudo');
		}
		$ret = $ssh->execCommand(
			$host,
			$params,
			$cmd
		);
		$this->getCallbackClient()->callClientFunction(
			'oDeployAPIHost.check_host_connection_cb',
			[
				[
					'output' => $ret['output'],
					'success' => ($ret['exitcode'] === 0)
				]
			]
		);
	}

	private function deployAPICopyStep($file)
	{
		$scp = $this->getModule('scp');
		$ssh = $this->getModule('ssh');
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();

		$ret = ['output' => [], 'exitcode' => 0];
		if ($params['use_sudo']) {
			$tempdir = DeployAPIHost::TMP_DIR;
			$tempfile = basename($file['src_file']);
			$temppath = implode(DIRECTORY_SEPARATOR, [$tempdir, $tempfile]);
			$ret_cp = $scp->execCommand(
				$file['src_file'],
				$temppath,
				$host,
				$params
			);
			$ret['output'] = array_merge($ret['output'], $ret_cp['output']);
			if ($ret_cp['exitcode'] === 0) {
				$ret_mv = $ssh->execCommand(
					$host,
					$params,
					['sudo', 'mv', $temppath, $file['dst_file']]
				);
				if ($ret_mv['exitcode'] !== 0) {
					$ret['exitcode'] = $ret_cp['exitcode'];
				}
				$ret['output'] = array_merge($ret['output'], $ret_mv['output']);
			} else {
				$ret['exitcode'] = $ret_cp['exitcode'];
			}
		} else {
			$ret_cp = $scp->execCommand(
				$file['src_file'],
				$file['dst_file'],
				$host,
				$params
			);
			$ret['output'] = array_merge($ret['output'], $ret_cp['output']);
			$ret['exitcode'] = $ret_cp['exitcode'];
		}
		unlink($file['src_file']);

		if ($ret['exitcode'] === 0) {
			// set ownership
			$ret_owner = $this->deployAPISetOwnership($file);
			$ret['output'] = array_merge($ret['output'], $ret_owner['output']);
			$ret['exitcode'] = $ret_owner['exitcode'];
		}

		if ($ret['exitcode'] === 0) {
			// set permissions
			$ret_perm = $this->deployAPISetPermissions($file);
			$ret['output'] = array_merge($ret['output'], $ret_perm['output']);
			$ret['exitcode'] = $ret_owner['exitcode'];
		}

		return $ret;
	}

	private function deployAPISetOwnership($file)
	{
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$ssh = $this->getModule('ssh');
		$deploy_api = $this->getModule('deploy_api');
		$cmd = $deploy_api->getSetOwnershipCommand($file, $params);
		$ret = $ssh->execCommand(
			$host,
			$params,
			$cmd
		);
		return $ret;
	}

	private function deployAPISetPermissions($file)
	{
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$ssh = $this->getModule('ssh');
		$deploy_api = $this->getModule('deploy_api');
		$cmd = $deploy_api->getSetPermissionsCommand($file, $params);
		$ret = $ssh->execCommand(
			$host,
			$params,
			$cmd
		);
		return $ret;
	}

	public function deployAPIAddBacularisRepository($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$osprofile = $this->getDeployOSProfile();
		$br_key = $osprofile['bacularis_repository_key'];
		$label = Prado::localize(self::DEPLOY_STEPS_TXT[$step_id]);
		$ret = ['exitcode' => 0];

		// First create directory for temporary files for all other steps
		$ssh = $this->getModule('ssh');
		$deploy_api = $this->getModule('deploy_api');
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$cmd = $deploy_api->getCreateTmpDirCommand();
		$ret = $ssh->execCommand(
			$host,
			$params,
			$cmd
		);
		$this->displayRawOutput($label, $ret['output']);

		if ($ret['exitcode'] === 0) {
			if ($osprofile['repository_type'] == OSProfileConfig::REPOSITORY_TYPE_DEB) {
				// Prepare and copy GPG key for DEB specific repos
				$file = $deploy_api->prepareGPGKey(
					$osprofile['bacularis_repository_key'],
					'bacularis-archive-keyring.gpg'
				);
				if ($file) {
					$ret = $this->deployAPICopyStep($file);
					$this->displayRawOutput(null, $ret['output']);
					$br_key = $file['dst_file'];
				} else {
					$ret['output'] = ['Error while preparing Bacularis GPG key'];
					$ret['exitcode'] = 1;
					$this->displayRawOutput(null, $ret['output']);
				}
			}
			if ($ret['exitcode'] === 0) {
				// Prepare and copy repository file
				$repository_auth = $osprofile['bacularis_repository_auth'] ?? '';
				$file = $deploy_api->prepareRepositoryFile(
					$osprofile['repository_type'],
					'Bacularis',
					$osprofile['bacularis_repository_addr'],
					$br_key,
					$repository_auth
				);
				$ret = $this->deployAPICopyStep($file);
				if ($osprofile['repository_type'] == 'deb') {
					$repo_auth = [];
					$repoauth_config = $this->getModule('repoauth_config');
					if (!empty($repository_auth)) {
						$repo_auth = $repoauth_config->getRepoAuthConfig($repository_auth);
					} else {
						$repo_auth = $repoauth_config->getDefaultRepoAuthConfig();
					}
					if (count($repo_auth) > 0) {
						$repo_info = explode(' ', $osprofile['bacularis_repository_addr']);
						$repo_url = array_shift($repo_info);
						$file = $deploy_api->prepareDEBRepositoryAuthFile(
							$repo_url,
							$repo_auth
						);
						$result = $this->deployAPICopyStep($file);
						if ($result['exitcode'] !== 0) {
							$ret['exitcode'] = $result['exitcode'];
						}
						$ret['output'] = array_merge($ret['output'], $result['output']);
					}
				}

				$this->displayRawOutput(null, $ret['output']);
			}
		}

		if ($ret['exitcode'] === 0 && $osprofile['bacula_use_system_repo'] == 0) {
			$br_key = $osprofile['bacula_repository_key'];
			if ($osprofile['repository_type'] == OSProfileConfig::REPOSITORY_TYPE_DEB) {
				// Prepare and copy GPG key for DEB specific repos
				$file = $deploy_api->prepareGPGKey(
					$osprofile['bacula_repository_key'],
					'bacula-archive-keyring.gpg'
				);
				if ($file) {
					$ret = $this->deployAPICopyStep($file);
					$this->displayRawOutput(null, $ret['output']);
					$br_key = $file['dst_file'];
				} else {
					$ret['output'] = ['Error while preparing Bacula GPG key'];
					$ret['exitcode'] = 1;
					$this->displayRawOutput(null, $ret['output']);
				}
			}
			if ($ret['exitcode'] === 0) {
				// Prepare and copy repository file
				$file = $deploy_api->prepareRepositoryFile(
					$osprofile['repository_type'],
					'Bacula',
					$osprofile['bacula_repository_addr'],
					$br_key
				);
				$ret = $this->deployAPICopyStep($file);

				$this->displayRawOutput(null, $ret['output']);
			}
		}

		$this->deployLogOutput($step_id, null, $ret['exitcode']);
	}

	public function deployAPIAddSUDOSettings($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$osprofile = $this->getDeployOSProfile();
		$file = $this->getModule('deploy_api')->prepareSUDOFile($osprofile);
		$ret = $this->deployAPICopyStep($file);

		$label = Prado::localize(self::DEPLOY_STEPS_TXT[$step_id]);
		$this->displayRawOutput($label, $ret['output']);

		$this->deployLogOutput($step_id, null, $ret['exitcode']);
	}

	public function deployAPIPreInstallBacularis($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$ssh = $this->getModule('ssh');
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$osprofile = $this->getDeployOSProfile();
		$cmd = [$osprofile['packages_bacularis_pre_install_cmd']];
		if ($params['use_sudo']) {
			array_unshift($cmd, 'sudo');
		}

		// Pre-install command
		$output_id = null;
		if (!empty($osprofile['packages_bacularis_pre_install_cmd'])) {
			$ret = $ssh->execCommand(
				$host,
				$params,
				$cmd,
				SSH::PTYPE_BG_CMD
			);
			$output_id = $ret['output_id'];
		}
		$this->deployLogOutput($step_id, $output_id);
	}

	public function deployAPIInstallBacularis($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$ssh = $this->getModule('ssh');
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$osprofile = $this->getDeployOSProfile();
		$cmd = [$osprofile['packages_bacularis_install']];
		if ($params['use_sudo']) {
			array_unshift($cmd, 'sudo');
		}

		// Install command
		$ret = $ssh->execCommand(
			$host,
			$params,
			$cmd,
			SSH::PTYPE_BG_CMD
		);
		$this->deployLogOutput($step_id, $ret['output_id']);
	}

	public function deployAPIPostInstallBacularis($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$ssh = $this->getModule('ssh');
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$osprofile = $this->getDeployOSProfile();
		$cmd = [$osprofile['packages_bacularis_post_install_cmd']];
		if ($params['use_sudo']) {
			array_unshift($cmd, 'sudo');
		}

		// Post-install command
		$output_id = null;
		if (!empty($osprofile['packages_bacularis_post_install_cmd'])) {
			$ret = $ssh->execCommand(
				$host,
				$params,
				$cmd,
				SSH::PTYPE_BG_CMD
			);
			$output_id = $ret['output_id'];
		}
		$this->deployLogOutput($step_id, $output_id);
	}

	public function deployAPIConfigureBacularis($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$osprofile = $this->getDeployOSProfile();
		$deploy_api = $this->getModule('deploy_api');

		// Configure command
		$file = $deploy_api->prepareConfigureFile($osprofile);
		$ret = $this->deployAPICopyStep($file);

		$label = Prado::localize(self::DEPLOY_STEPS_TXT[$step_id]);
		$this->displayRawOutput($label, $ret['output']);

		// Setup HTTPS
		if ($ret['exitcode'] === 0 && $osprofile['bacularis_use_https'] == 1) {
			// Genrate certificate
			$ssh = $this->getModule('ssh');
			$host = $this->DeployAPIHostHostname->Text;
			$params = $this->getDeployParams();
			$cmd = $deploy_api->getPrepareHTTPSCertCommand($host, $params);
			$ret = $ssh->execCommand(
				$host,
				$params,
				$cmd
			);
			$this->displayRawOutput(null, $ret['output']);

			if ($ret['exitcode'] === 0 && strpos($osprofile['packages_bacularis_install'], 'lighttpd') !== false) {
				// For lighttpd prepare also PEM file
				$cmd = $deploy_api->getPrepareHTTPSPemCommand($host, $params);
				$ret = $ssh->execCommand(
					$host,
					$params,
					$cmd
				);
				$this->displayRawOutput(null, $ret['output']);
			}

			if ($ret['exitcode'] === 0) {
				// Enable HTTPS in web server config
				$cmd = '';
				if (strpos($osprofile['packages_bacularis_install'], 'nginx') !== false) {
					$cmd = $deploy_api->getEnableHTTPSNginxCommand($osprofile['repository_type'], $params);
				} elseif (strpos($osprofile['packages_bacularis_install'], 'lighttpd') !== false) {
					$cmd = $deploy_api->getEnableHTTPSLighttpdCommand($params);
				} elseif (strpos($osprofile['packages_bacularis_install'], 'apache') !== false || strpos($osprofile['packages_bacularis_install'], 'httpd') !== false) {
					$cmd = $deploy_api->getEnableHTTPSApacheCommand($osprofile['repository_type'], $params);
				}
				$ret = $ssh->execCommand(
					$host,
					$params,
					$cmd
				);
				$this->displayRawOutput(null, $ret['output']);
			}
		}

		$this->deployLogOutput($step_id, null, $ret['exitcode']);
	}

	public function deployAPICreateBacularisUser($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$osprofile = $this->getDeployOSProfile();

		// Create user command
		$file = $this->getModule('deploy_api')->prepareCreateUserFile($osprofile);
		$ret = $this->deployAPICopyStep($file);

		$label = Prado::localize(self::DEPLOY_STEPS_TXT[$step_id]);
		$this->displayRawOutput($label, $ret['output']);

		$this->deployLogOutput($step_id, null, $ret['exitcode']);
	}

	public function deployAPISetBacularisPwd($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$osprofile = $this->getDeployOSProfile();
		$deploy_api = $this->getModule('deploy_api');
		$file = $deploy_api->prepareUserPwdFile($osprofile, 'API');

		// Set user password for API
		$ret = $this->deployAPICopyStep($file);

		$label = Prado::localize(self::DEPLOY_STEPS_TXT[$step_id]);
		$this->displayRawOutput($label, $ret['output']);

		if ($ret['exitcode'] === 0) {
			// Set user password for Web
			$file = $deploy_api->prepareUserPwdFile($osprofile, 'Web');
			$ret = $this->deployAPICopyStep($file);
			$this->displayRawOutput($label, $ret['output']);
		}

		$this->deployLogOutput($step_id, null, $ret['exitcode']);
	}

	public function deployAPIStartBacularis($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$ssh = $this->getModule('ssh');
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$osprofile = $this->getDeployOSProfile();
		$cmd = [$osprofile['packages_bacularis_start']];
		if ($params['use_sudo']) {
			array_unshift($cmd, 'sudo');
		}

		// Start web server command
		$ret = $ssh->execCommand(
			$host,
			$params,
			$cmd,
			SSH::PTYPE_BG_CMD
		);
		$this->deployLogOutput($step_id, $ret['output_id']);
	}

	public function deployAPIAccessLink($sender, $param)
	{
		$step_id = $param->getCallbackParameter();
		$host = $this->DeployAPIHostHostname->Text;
		$params = $this->getDeployParams();
		$osprofile = $this->getDeployOSProfile();
		$port = 9097;
		$ssh = $this->getModule('ssh');

		$pattern = '';
		if ($osprofile['bacularis_use_https']) {
			$pattern = 'https://%s:%d/panel';
		} else {
			$pattern = 'http://%s:%d/panel';
		}
		$url = sprintf($pattern, $host, $port);

		$cmd = $this->getModule('deploy_api')->getRemoveTmpDirCommand();
		$ret = $ssh->execCommand(
			$host,
			$params,
			$cmd
		);
		$this->displayRawOutput(null, $ret['output']);

		$this->deployLogOutput($step_id, null, 0);

		$this->getCallbackClient()->callClientFunction(
			'oDeployAPIHost.set_access_link',
			[$step_id, $url]
		);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_APPLICATION,
			"New API has been deployed to host: {$host}"
		);

		// It is last step. Add new API host to host config
		$this->saveAPIHost(
			$host,
			$port,
			$osprofile,
			$this->DeployAPIHostName->Text
		);

		// Refresh host list
		$this->setAPIHostList(null, null);
	}

	private function saveAPIHost($host, $port, $osprofile, $short_name = '')
	{
		$host_config = [];
		$host_config['protocol'] = $osprofile['bacularis_use_https'] ? 'https' : 'http';
		$host_config['address'] = $host;
		$host_config['port'] = $port;
		$host_config['auth_type'] = 'basic';
		$host_config['login'] = $osprofile['bacularis_admin_user'];
		$host_config['password'] = $osprofile['bacularis_admin_pwd'];
		$host_config['client_id'] = '';
		$host_config['client_secret'] = '';
		$host_config['redirect_uri'] = '';
		$host_config['scope'] = '';
		$host_config['url_prefix'] = '';
		$api_host_name = $short_name ?: $host;
		$ret = $this->getModule('host_config')->setHostConfig($api_host_name, $host_config);

		if ($ret) {
			// Automatically assign new host to user who deployed this host
			$this->assignNewAPIHostToUser($api_host_name);

			// Assign to selected API host groups
			$this->assignNewAPIHostToAPIGroups($api_host_name);
		}
	}

	private function assignNewAPIHostToUser($host)
	{
		$user_config = $this->getModule('user_config');
		$username = $this->User->getUsername();
		$user = $user_config->getUserConfig($username);
		if (!in_array($host, $user['api_hosts'])) {
			if (count($user['api_hosts']) == 0) {
				/**
				 * If user is not assigned to any host, it means that he uses Main host.
				 * To not loose access to Main host after assigning new API host first he needs
				 * to be assigned to the Main host.
				 */
				$user['api_hosts'][] = HostConfig::MAIN_CATALOG_HOST;
			}
			$user['api_hosts'][] = $host;
			$user_config->setUserConfig($username, $user);
		}
	}

	private function assignNewAPIHostToAPIGroups($host)
	{
		if (!$this->DeployAPIHostUseHostGroups->Checked) {
			// No use API groups checkebox checked, no assigning
			return;
		}

		$ahg = $this->getModule('host_group_config');
		$selected_indices = $this->DeployAPIHostGroups->getSelectedIndices();
		foreach ($selected_indices as $indice) {
			for ($i = 0; $i < $this->DeployAPIHostGroups->getItemCount(); $i++) {
				if ($i === $indice) {
					$host_group = $this->DeployAPIHostGroups->Items[$i]->Value;
					$config = $ahg->getHostGroupConfig($host_group);
					if (!in_array($host, $config['api_hosts'])) {
						$config['api_hosts'][] = $host;
					}
					if ($ahg->setHostGroupConfig($host_group, $config)) {
						$this->getModule('audit')->audit(
							AuditLog::TYPE_INFO,
							AuditLog::CATEGORY_APPLICATION,
							"Newly deployed API host assigned to API group. API host: {$host},  host group: {$host_group}"
						);
					} else {
						$this->getModule('audit')->audit(
							AuditLog::TYPE_ERROR,
							AuditLog::CATEGORY_APPLICATION,
							"Error while assigning newly deployed API host to API group. API host: {$host},  host group: {$host_group}"
						);
					}
				}
			}
		}
	}

	public function getDeployOutput($sender, $param)
	{
		[$step_id, $output_id] = $param->getCallbackParameter();
		if ($output_id) {
			$ret = SSH::readOutputFile($output_id);
			if (count($ret['output']) > 0) {
				$label = Prado::localize(self::DEPLOY_STEPS_TXT[$step_id]);
				$this->displayRawOutput($label, $ret['output']);
				$output_id = null;
			}
			$this->deployLogOutput($step_id, $output_id, $ret['exitcode']);
		}
	}

	private function displayRawOutput($label, $output = [])
	{
		if (count($output) == 0) {
			return;
		}
		if ($label) {
			$step = Prado::localize('Step');
			$label = PHP_EOL . "===== {$step}: {$label} =====" . PHP_EOL;
			array_unshift($output, $label);
		}
		$out = implode(PHP_EOL, $output);
		$cbc = $this->getCallbackClient();
		$cbc->appendContent('deploy_api_raw_output', htmlentities($out));
	}

	private function deployLogOutput($step_id, $output_id, $exitcode = -1)
	{
		$cbc = $this->getCallbackClient();

		if ($exitcode !== -1) {
			if ($exitcode === 0) {
				// Process finished successfully
				$cbc->callClientFunction(
					'oDeployAPIHost.run_step_cb_ok',
					[$step_id, $output_id]
				);
			} else {
				// Process finished with error
				$cbc->callClientFunction(
					'oDeployAPIHost.run_step_cb_err',
					[$step_id]
				);
			}
		} else {
			// Process is pending
			$cbc->callClientFunction(
				'oDeployAPIHost.run_step_cb_ok',
				[$step_id, $output_id]
			);
		}
	}

	private function getDeployParams()
	{
		$user = $this->DeployAPIHostUsername->Text;
		$params = [
			'ssh_config' => '',
			'password' => '',
			'password_sudo' => '',
			'passphrase' => '',
			'key' => '',
			'use_sudo' => $this->DeployAPIHostUseSudo->Checked
		];
		if ($this->DeployAPIHostUsePassword->Checked) {
			$params['username'] = $user;
			$params['password'] = $this->DeployAPIHostPassword->Text;
		} elseif ($this->DeployAPIHostUseKey->Checked) {
			$key = $this->getModule('ssh_key')->getPathByName($this->DeployAPIHostSSHKey->SelectedValue);
			$params['username'] = $user;
			$params['key'] = $key;
			$params['passphrase'] = $this->DeployAPIHostPassphrase->Text;
		} elseif ($this->DeployAPIHostUseSSHConfig->Checked) {
			$params['passphrase'] = $this->DeployAPIHostPassphrase->Text;
		}
		if ($params['use_sudo']) {
			$params['password_sudo'] = $this->DeployAPIHostPassword->Text;
		}
		return $params;
	}

	private function getDeployOSProfile()
	{
		$osp = $this->DeployAPIHostOSProfile->SelectedValue;
		$osprofile = $this->getModule('osprofile_config')->getConfig($osp);

		if (count($osprofile) == 0) {
			// no OS profile, no deployment
			return;
		}
		return $osprofile;
	}

	public function deployAPICopyOSProfileConfig($sender, $param)
	{
		$osprofile = $this->OSProfileToCopy->SelectedValue;
		if (!empty($osprofile)) {
			$config = $this->getModule('osprofile_config')->getConfig($osprofile);
			if (count($config) > 0) {
				$this->setOSProfileFieldsInWindow($config);
			}
		}
	}

	public function loadAPIHostCommandWindow($sender, $param)
	{
		[$host, $command] = $param->getCallbackParameter();
		$ret = [];
		$components = ['catalog', 'director', 'storage', 'client', 'console'];
		for ($i = 0; $i < count($components); $i++) {
			$result = $this->getModule('api')->get(
				['software', $components[$i], 'info'],
				$host
			);
			$ret[$components[$i]] = [
				'output' => ($result->error === 0 ? implode(PHP_EOL, $result->output) : $result->output),
				'error' => $result->error,
				'command' => $command,
				'host' => $host
			];
		}
		$this->getCallbackClient()->callClientFunction(
			'oAPIHostCommands.load_window_cb',
			[$ret]
		);
	}

	public function runAPIHostCommand($sender, $param)
	{
		[$host, $component, $command, $options] = $param->getCallbackParameter();
		$result = $this->getModule('api')->get(
			['software', $component, $command],
			$host
		);
		if ($result->error === 0 && $command === 'install' && property_exists($options, 'add_comp_to_dir') && $options->add_comp_to_dir === true && in_array($component, ['client', 'storage'])) {
			$ctd = $this->addComponentToDirector($host, $component);
			if ($ctd->error === 0) {
				$result->output = array_merge($result->output, [$ctd->output]);
			} else {
				$result->output[] = $ctd->output;
			}
			$result->error = $ctd->error;
			if ($result->error === 0 || $result->error === BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS) {
				// for new installed components, enable them
				$ec = $this->enableComponent($host, $component);
				if ($ec->error === 0) {
					$result->output = array_merge($result->output, $ec->output);
				} else {
					$result->output[] = $ec->output;
				}
			}
			if ($result->error === 0 || $result->error === BaculaConfigError::ERROR_CONFIG_ALREADY_EXISTS) {
				$sc = $this->startComponent($host, $component);
				$result->output[] = $sc->output;
			}
		}

		$this->getCallbackClient()->callClientFunction(
			'oAPIHostCommands.execute_cb',
			[
				[
					'output' => ($result->error === 0 ? implode(PHP_EOL, $result->output) : $result->output),
					'error' => $result->error,
					'host' => $host,
					'component' => $component,
					'command' => $command,
					'options' => $options
				]
			]
		);
	}

	private function addComponentToDirector($host, $component)
	{
		$api = $this->getModule('api');
		$result = (object) [
			'output' => [],
			'error' => 0
		];
		$dir_cfg = $api->get(['config', 'dir']);
		if ($dir_cfg->error === 0) {
			$dir_name = $cat_name = '';
			for ($i = 0; $i < count($dir_cfg->output); $i++) {
				if (property_exists($dir_cfg->output[$i], 'Director')) {
					$dir_name = $dir_cfg->output[$i]->Director->Name;
				} elseif (property_exists($dir_cfg->output[$i], 'Catalog')) {
					$cat_name = $dir_cfg->output[$i]->Catalog->Name;
				}
			}
			$host_config = $this->getModule('host_config')->getHostConfig($host);

			// Add file daemon
			if ($component === 'client') {
				$result = $this->addClientToDirector(
					$host,
					$host_config['address'],
					$dir_name,
					$cat_name
				);
			} elseif ($component === 'storage') {
				$result = $this->addStorageToDirector(
					$host,
					$host_config['address'],
					$dir_name
				);
			}
		} else {
			$result->output = $dir_cfg->output;
			$result->error = $dir_cfg->error;
		}

		if ($result->error === 0) {
			# Everything fine, reload Director
			$api->set(['console'], ['reload']);
		}
		return $result;
	}

	private function addClientToDirector($host, $fd_addr, $dir_name, $cat_name)
	{
		$fd_name = "{$host}-fd";
		$crypto = $this->getModule('crypto');
		$fd_pwd = $crypto->getRandomString(32);
		$api = $this->getModule('api');
		$fd_dir_cfg = [
			'Name' => $dir_name,
			'Password' => $fd_pwd
		];
		$result = $api->create(
			['config', 'fd', 'Director', $dir_name],
			['config' => json_encode($fd_dir_cfg)],
			$host
		);
		if ($result->error === 0) {
			$dir_fd_cfg = [
				'Name' => $fd_name,
				'Address' => $fd_addr,
				'Password' => $fd_pwd,
				'Catalog' => $cat_name
			];
			$result = $api->create(
				['config', 'dir', 'Client', $fd_name],
				['config' => json_encode($dir_fd_cfg)]
			);
		}
		return $result;
	}

	private function addStorageToDirector($host, $sd_addr, $dir_name)
	{
		$crypto = $this->getModule('crypto');
		$sd_pwd = $crypto->getRandomString(32);
		$api = $this->getModule('api');
		$sd_dir_cfg = [
			'Name' => $dir_name,
			'Password' => $sd_pwd
		];
		$result = $api->create(
			['config', 'sd', 'Director', $dir_name],
			['config' => json_encode($sd_dir_cfg)],
			$host
		);
		if ($result->error === 0) {
			$devices = [
				'autochanger' => [],
				'device' => []
			];
			// Get autochangers to add
			$ret = $api->get(
				['config', 'sd', 'Autochanger'],
				$host
			);

			if ($ret->error === 0) {
				for ($i = 0; $i < count($ret->output); $i++) {
					$name = $ret->output[$i]->Autochanger->Name;
					$devices['autochanger'][$name] = [
						'Device' => $ret->output[$i]->Autochanger->Device
					];
				}
			} else {
				$result->error = $ret->error;
				$result->output = $ret->output;
				// NOTE: With non-zero error we stop here (not too clear). @TODO: Refactor this part
				return $result;
			}
			// Get devices to add
			$ret = $api->get(
				['config', 'sd', 'Device'],
				$host
			);
			if ($ret->error === 0) {
				for ($i = 0; $i < count($ret->output); $i++) {
					$name = $ret->output[$i]->Device->Name;
					$devices['device'][$name] = [
						'MediaType' => $ret->output[$i]->Device->MediaType
					];
				}
			} else {
				$result->error = $ret->error;
				$result->output = $ret->output;
				// NOTE: With non-zero error  we stop here (not too clear). @TODO: Refactor this part
				return $result;
			}

			// Add devices
			$achs_added = [];
			foreach ($devices['device'] as $device => $dev_config) {
				$add = false;
				$dir_sd_cfg = [
					'Name' => "{$host}-{$device}",
					'Address' => $sd_addr,
					'Password' => $sd_pwd,
					'MediaType' => $dev_config['MediaType']
				];
				foreach ($devices['autochanger'] as $autochanger => $ach_config) {
					if (in_array($device, $ach_config['Device'])) {
						// Device belongs to autochanger

						if (in_array($autochanger, $achs_added)) {
							//device from already added autochanger, skip it
							continue 2;
						}
						$dir_sd_cfg['Name'] = "{$host}-{$autochanger}";
						$dir_sd_cfg['Device'] = $autochanger;
						$dir_sd_cfg['Autochanger'] = true;
						$add = true;
						$achs_added[] = $autochanger;
						break;
					}
				}
				if (!$add) {
					// single non-autochanger device
					$dir_sd_cfg['Device'] = $device;
				}

				// Add config to Director
				if ($add) {
					$ret = $api->create(
						['config', 'dir', 'Storage', $dir_sd_cfg['Name']],
						['config' => json_encode($dir_sd_cfg)]
					);
					if ($ret->error !== 0) {
						$result->error = $ret->error;
						$result->output = $ret->output;
						break;
					}
				}
				$add = false;
			}
		}
		return $result;
	}

	private function enableComponent($host, $component)
	{
		$result = $this->getModule('api')->get(
			['software', $component, 'enable'],
			$host
		);
		return $result;
	}

	private function startComponent($host, $component)
	{
		$result = $this->getModule('api')->get(
			['actions', $component, 'stop'],
			$host
		);
		if ($result->error === 0) {
			$result = $this->getModule('api')->get(
				['actions', $component, 'start'],
				$host
			);
		}
		return $result;
	}
}
