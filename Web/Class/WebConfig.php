<?php
/*
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

Prado::using('Application.Common.Class.ConfigFileModule');
Prado::using('Application.Common.Class.Crypto');

/**
 * Manage webGUI configuration.
 * Module is responsible for get/set webGUI config data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum Web
 */
class WebConfig extends ConfigFileModule {

	/**
	 * Web config file path
	 */
	const CONFIG_FILE_PATH = 'Application.Web.Config.settings';

	/**
	 * Web config file format
	 */
	const CONFIG_FILE_FORMAT = 'ini';

	/**
	 * Default application language
	 */
	const DEF_LANG = 'en';

	/**
	 * Default number of jobs visible in tables/
	 */
	const DEF_MAX_JOBS = 15000;

	/**
	 * Default size values unit.
	 */
	const DEF_SIZE_VAL_UNIT = 'decimal';

	/**
	 * Default value for showing time in job log.
	 */
	const DEF_TIME_IN_JOB_LOG = 0;

	/**
	 * Default value for enabling messages log.
	 */
	const DEF_ENABLE_MESSAGES_LOG = 1;

	/**
	 * Default date and time format.
	 */
	const DEF_DATE_TIME_FORMAT = 'Y-M-D R';

	/**
	 * Supported authentication methods.
	 */
	const AUTH_METHOD_LOCAL = 'local';
	const AUTH_METHOD_BASIC = 'basic';
	const AUTH_METHOD_LDAP = 'ldap';

	/**
	 * Default access options.
	 */
	const DEF_ACCESS_NO_ACCESS = 'no_access';
	const DEF_ACCESS_DEFAULT_SETTINGS = 'default_settings';

	/**
	 * Stores web config content.
	 */
	private $config = null;

	/**
	 * These options are obligatory for web config.
	 */
	private $required_options = array(
		'baculum' => array('debug', 'lang')
	);

	/**
	 * Initialize module configuration.
	 *
	 * @param TXmlElement $config module configuration
	 * @return none
	 */
	public function init($config) {
		// add event handler to set page language
		$this->Application->attachEventHandler(
			'onPreRunService',
			[$this, 'setCulture']
		);
	}

	/**
	 * Get web config.
	 *
	 * @access public
	 * @param string $section config section name
	 * @return array config
	 */
	public function getConfig($section = null) {
		$config = [];
		$valid = true;
		if (is_null($this->config)) {
			$this->config = $this->readConfig(self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			$valid = $this->validateConfig($this->config);
		}
		if ($valid === true) {
			if (is_string($section)) {
				$config = key_exists($section, $this->config) ? $this->config[$section] : array();
			} else {
				$config = $this->config;
			}
		}
		return $config;
	}

	/**
	 * Set web config.
	 *
	 * @access public
	 * @param array $config config
	 * @return boolean true if config saved successfully, otherwise false
	 */
	public function setConfig(array $config) {
		$result = false;
		if ($this->validateConfig($config) === true) {
			$result = $this->writeConfig($config, self::CONFIG_FILE_PATH, self::CONFIG_FILE_FORMAT);
			if ($result === true) {
				$this->config = null;
			}
		}
		return $result;
	}
	
	/**
	 * Validate web config.
	 * Config validation should be used as early as config data is available.
	 * Validation is done in read/write config methods.
	 *
	 * @access private
	 * @param array $config config
	 * @return boolean true if config valid, otherwise false
	 */
	private function validateConfig(array $config = array()) {
		$is_valid = $this->isConfigValid($this->required_options, $config, self::CONFIG_FILE_FORMAT, self::CONFIG_FILE_PATH);
		return $is_valid;
	}

	/**
	 * Set default options for new config.
	 *
	 * @param array $opts custom options to add to default options
	 * @return true on success, otherwise false
	 */
	public function setDefConfigOpts($opts = []) {
		$config = $this->getConfig();

		$baculum = [
			'debug' => 0,
			'lang' => self::DEF_LANG,
			'max_jobs' => self::DEF_MAX_JOBS,
			'size_values_unit' => self::DEF_SIZE_VAL_UNIT,
			'time_in_job_log' => self::DEF_TIME_IN_JOB_LOG,
			'date_time_format' => self::DEF_DATE_TIME_FORMAT,
			'enable_messages_log' => self::DEF_ENABLE_MESSAGES_LOG
		];
		if (key_exists('baculum', $config)) {
			$config['baculum'] = array_merge($baculum, $config['baculum']);
		} else {
			$config['baculum'] = $baculum;
		}

		$user_file = $this->getModule('basic_webuser')->getConfigPath();
		// basic options
		$auth_basic = [
			'allow_manage_users' => 1,
			'user_file' => $user_file,
			'hash_alg' => Crypto::HASH_ALG_APR1_MD5
		];
		if (key_exists('auth_basic', $config)) {
			$config['auth_basic'] = array_merge($auth_basic, $config['auth_basic']);
		} else {
			$config['auth_basic'] = $auth_basic;
		}

		// security options
		$security = [
			'auth_method' => self::AUTH_METHOD_BASIC,
			'def_access' => self::DEF_ACCESS_DEFAULT_SETTINGS,
			'def_role' => WebUserRoles::NORMAL,
			'def_api_host' => HostConfig::MAIN_CATALOG_HOST
		];
		if  (key_exists('security', $config)) {
			$config['security'] = array_merge($security, $config['security']);
		} else {
			$config['security'] = $security;
		}

		if (count($opts) > 0) {
			$config = array_replace_recursive($config, $opts);
		}

		// set default properties
		$ret = $this->setConfig($config);
		if ($ret !== true) {
			$emsg = 'Error while saving auth basic config.';
			$this->getModule('logging')->log(
				__FUNCTION__,
				$emsg,
				Logging::CATEGORY_APPLICATION,
				__FILE__,
				__LINE__
			);
		}
		return $ret;
	}

	/**
	 * Get authentication method.
	 *
	 * @return string authentication method
	 */
	public function getAuthMethod() {
		$config = $this->getConfig();

		$auth_method = self::AUTH_METHOD_BASIC; // Basic is default method
		if (isset($config['security']['auth_method'])) {
			$auth_method = $config['security']['auth_method'];
		}
		return $auth_method;
	}

	/**
	 * Check if current authentication method is set to Basic.
	 *
	 * @return boolean true if is set Basic auth, otherwise false
	 */
	public function isAuthMethodBasic() {
		return ($this->getAuthMethod() === self::AUTH_METHOD_BASIC);
	}

	/**
	 * Check if current authentication method is set to LDAP.
	 *
	 * @return boolean true if is set LDAP auth, otherwise false
	 */
	public function isAuthMethodLdap() {
		return ($this->getAuthMethod() === self::AUTH_METHOD_LDAP);
	}

	/**
	 * Check if current authentication method is set to Local.
	 *
	 * @return boolean true if is set local auth, otherwise false
	 */
	public function isAuthMethodLocal() {
		return ($this->getAuthMethod() === self::AUTH_METHOD_LOCAL);
	}

	/**
	 * Check if current default access method for not existing users
	 * in configuration file is set to no access.
	 *
	 * @return boolean true if is set no access, otherwise false
	 */
	public function isDefAccessNoAccess() {
		$config = $this->getConfig();
		return (isset($config['security']['def_access']) && $config['security']['def_access'] === self::DEF_ACCESS_NO_ACCESS);
	}

	/**
	 * Check if current default access method for not existing users
	 * in configuration file is set to default settings.
	 *
	 * @return boolean true if is set default settings, otherwise false
	 */
	public function isDefAccessDefaultSettings() {
		$config = $this->getConfig();
		return (isset($config['security']['def_access']) && $config['security']['def_access'] === self::DEF_ACCESS_DEFAULT_SETTINGS);
	}

	/**
	 * Check if messages log is enabled.
	 *
	 * @return boolean true if is messages log enabled, otherwise false
	 */
	public function isMessagesLogEnabled() {
		$enabled = self::DEF_ENABLE_MESSAGES_LOG;
		$config = $this->getConfig();
		if (isset($config['baculum']['enable_messages_log'])) {
			$enabled = $config['baculum']['enable_messages_log'];
		}
		return (intval($enabled) == 1);
	}

	/**
	 * Set culture for whole page.
	 * Uses currently set language settings.
	 *
	 * @return none
	 */
	public function setCulture() {
		$this->Application->getGlobalization()->Culture = $this->getLanguage();
	}

	/**
	 * Get curently set language short name (for example: en, pl).
	 * If language short name is not set in session then the language value
	 * is taken from Baculum config file, saved in session and returned.
	 * If the language setting is set in session, then the value from
	 * session is returned.
	 *
	 * @return string currently set language short name
	 */
	public function getLanguage() {
		$language = null;
		if (isset($_SESSION['language']) && !empty($_SESSION['language'])) {
			$language =  $_SESSION['language'];
		} else {
			$config = $this->getConfig();
			if (isset($config['baculum']) && key_exists('lang', $config['baculum'])) {
				$language = $config['baculum']['lang'];
			}
			if (is_null($language)) {
				$language = self::DEF_LANG;
			}
			$_SESSION['language'] = $language;
		}
		return $language;
	}

	/**
	 * Set language for current page.
	 * Note, it is done in session only.
	 *
	 * @return none
	 */
	public function setLanguage($lang) {
		$_SESSION['language'] = $lang;
	}
}
?>
