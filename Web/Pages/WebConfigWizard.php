<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\HostConfig;
use Bacularis\Web\Modules\WebUserRoles;

/**
 * Web config wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class WebConfigWizard extends BaculumWebPage
{
	public $first_run;
	public $host_config;

	public function onInit($param)
	{
		parent::onInit($param);
		$this->host_config = $this->getModule('host_config')->getConfig();
		$this->first_run = (count($this->host_config) == 0 || !key_exists(HostConfig::MAIN_CATALOG_HOST, $this->host_config));
		Logging::$debug_enabled = Logging::$debug_enabled ?: $this->first_run;
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		if ($this->first_run === false) {
			$host = HostConfig::MAIN_CATALOG_HOST;
			$this->AddNewHost->APIProtocol->SelectedValue = $this->host_config[$host]['protocol'];
			$this->AddNewHost->APIAddress->Text = $this->host_config[$host]['address'];
			$this->AddNewHost->APIPort->Text = $this->host_config[$host]['port'];
			$this->AddNewHost->APIBasicLogin->Text = $this->host_config[$host]['login'];
			if ($this->host_config[$host]['auth_type'] === 'basic') {
				$this->AddNewHost->AuthOAuth2->Checked = false;
				$this->AddNewHost->AuthBasic->Checked = true;
				$this->AddNewHost->APIBasicLogin->Text = $this->host_config[$host]['login'];
				$this->AddNewHost->APIBasicPassword->Text = $this->host_config[$host]['password'];
			} elseif ($this->host_config[$host]['auth_type'] === 'oauth2') {
				$this->AddNewHost->AuthBasic->Checked = false;
				$this->AddNewHost->AuthOAuth2->Checked = true;
				$this->AddNewHost->APIOAuth2ClientId->Text = $this->host_config[$host]['client_id'];
				$this->AddNewHost->APIOAuth2ClientSecret->Text = $this->host_config[$host]['client_secret'];
				$this->AddNewHost->APIOAuth2RedirectURI->Text = $this->host_config[$host]['redirect_uri'];
				$this->AddNewHost->APIOAuth2Scope->Text = $this->host_config[$host]['scope'];
			}
		} else {
			$this->AddNewHost->APIProtocol->SelectedValue = 'http';
			$this->AddNewHost->APIAddress->Text = 'localhost';
			$this->AddNewHost->APIPort->Text = 9097;
			$this->AddNewHost->APIBasicLogin->Text = 'admin';
		}
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->Lang->SelectedValue = $this->getModule('web_config')->getLanguage();
	}

	public function nextStep($sender, $param)
	{
		if ($param->CurrentStepIndex === 1 && !$this->first_run) {
			$this->InstallWizard->ActiveStepIndex = 3;
		}
	}

	public function previousStep($sender, $param)
	{
	}

	public function wizardStop($sender, $param)
	{
		$this->goToDefaultPage();
	}

	public function wizardCompleted()
	{
		$host = HostConfig::MAIN_CATALOG_HOST;
		$cfg_host = [
			'auth_type' => '',
			'login' => '',
			'password' => '',
			'client_id' => '',
			'client_secret' => '',
			'redirect_uri' => '',
			'scope' => ''
		];
		$cfg_host['protocol'] = $this->AddNewHost->APIProtocol->Text;
		$cfg_host['address'] = $this->AddNewHost->APIAddress->Text;
		$cfg_host['port'] = $this->AddNewHost->APIPort->Text;
		$cfg_host['url_prefix'] = '';
		if ($this->AddNewHost->AuthBasic->Checked == true) {
			$cfg_host['auth_type'] = 'basic';
			$cfg_host['login'] = $this->AddNewHost->APIBasicLogin->Text;
			$cfg_host['password'] = $this->AddNewHost->APIBasicPassword->Text;
		} elseif ($this->AddNewHost->AuthOAuth2->Checked == true) {
			$cfg_host['auth_type'] = 'oauth2';
			$cfg_host['client_id'] = $this->AddNewHost->APIOAuth2ClientId->Text;
			$cfg_host['client_secret'] = $this->AddNewHost->APIOAuth2ClientSecret->Text;
			$cfg_host['redirect_uri'] = $this->AddNewHost->APIOAuth2RedirectURI->Text;
			$cfg_host['scope'] = $this->AddNewHost->APIOAuth2Scope->Text;
		}
		$host_config = $this->getModule('host_config')->getConfig();
		$host_config[$host] = $cfg_host;
		$ret = $this->getModule('host_config')->setConfig($host_config);
		if ($ret === true) {
			// complete new Baculum main settings
			$web_config = $this->getModule('web_config');
			$ret = $web_config->setDefConfigOpts([
				'baculum' => [
					'lang' => $this->Lang->SelectedValue
				]
			]);

			$basic_webuser = $this->getModule('basic_webuser');
			if ($this->first_run && $ret && $web_config->isAuthMethodLocal()) {
				// set new user on first wizard run
				$previous_user = parent::DEFAULT_AUTH_USER;
				$ret = $basic_webuser->setUsersConfig(
					$this->WebLogin->Text,
					$this->WebPassword->Text,
					false,
					$previous_user
				);
			} else {
				$emsg = 'Error while saving basic user config.';
				Logging::log(
					Logging::CATEGORY_APPLICATION,
					$emsg
				);
			}

			if ($this->first_run && $ret) {
				// create new Baculum user on first wizard run
				$user_config = $this->getModule('user_config');
				$new_user_prop = $user_config->getUserConfigProps([
					'username' => $this->WebLogin->Text,
					'roles' => WebUserRoles::ADMIN,
					'enabled' => 1
				]);
				$ret = $user_config->setUserConfig($this->WebLogin->Text, $new_user_prop);
				if (!$ret) {
					$emsg = 'Error while saving user config.';
					Logging::log(
						Logging::CATEGORY_APPLICATION,
						$emsg
					);
				}

				// Login user with new parameters
				$this->getModule('auth')->login($this->WebLogin->Text, $this->WebPassword->Text);
			}

			// Go to default user page
			$this->goToDefaultPage();
		} else {
			$emsg = 'Error while saving auth host config.';
			Logging::log(
				Logging::CATEGORY_APPLICATION,
				$emsg
			);
		}
	}

	public function setLogin($db)
	{
		$this->Login->Enabled = ($this->isSQLiteType($db) === false);
	}

	public function setPassword($db)
	{
		$this->Password->Enabled = ($this->isSQLiteType($db) === false);
	}

	public function setLang($sender, $param)
	{
		$this->getModule('web_config')->setLanguage($sender->SelectedValue);
	}

	public function validateAdministratorPassword($sender, $param)
	{
		if ($this->RetypeWebPasswordRequireValidator->IsValid && $this->RetypeWebPasswordRegexpValidator->IsValid) {
			$sender->Display = 'Dynamic';
		} else {
			$sender->Display = 'None';
		}
		$param->IsValid = ($param->Value === $this->WebPassword->Text);
	}
}
