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
 * Copyright (C) 2013-2021 Kern Sibbald
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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\BaculumPage;
use Bacularis\Common\Modules\Logging;

/**
 * Baculum Web page module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BaculumWebPage extends BaculumPage
{
	/**
	 * It is first application user pre-defined for first login.
	 * It is removed just after setup application.
	 */
	public const DEFAULT_AUTH_USER = 'admin';

	/*
	 * It is security delay that tells how many seconds user needs to wait
	 * after log in failed error to be able to do next log in try.
	 * The value is in seconds.
	 */
	public const LOGIN_FAILED_DELAY = 5;

	protected $web_config = [];

	public function onPreInit($param)
	{
		parent::onPreInit($param);
		$this->web_config = $this->getModule('web_config')->getConfig();

		if ($this->authenticate() === false) {
			sleep(self::LOGIN_FAILED_DELAY);
			exit();
		}

		if (count($this->web_config) === 0 && $this->User->getIsGuest() === false) {
			if ($this->Service->getRequestedPagePath() != 'WebConfigWizard') {
				$this->goToPage('WebConfigWizard');
			}
			// without config there is no way to call api below
			return;
		}

		if (!$this->IsCallBack && !$this->IsPostBack && !$this->isDefaultAPIHost()) {
			$this->goToPage('SelectAPIHost');
			// without API host selected we can't continue
			return;
		}

		Logging::$debug_enabled = (isset($this->web_config['baculum']['debug']) && $this->web_config['baculum']['debug'] == 1);
		if (!$this->IsPostBack && !$this->IsCallBack) {
			$this->postInitActions();
			$this->getModule('api')->initSessionCache(true);
			if ($this->User->getIsGuest() === false && (!key_exists('is_user_vars', $_SESSION) || $_SESSION['is_user_vars'] === false)) {
				$this->resetSessionUserVars(); // reset is required for init session vars
				$this->setSessionUserVars();
			}
		}
	}

	/**
	 * Generic authentication method.
	 * Basic users has to be authenticated for each request.
	 * It is done here.
	 *
	 * @return @boolean true if user has been authenticated successfully, otherwise false
	 */
	protected function authenticate()
	{
		$is_auth = true;
		if (isset($this->web_config['security']['auth_method']) && $this->web_config['security']['auth_method'] === WebConfig::AUTH_METHOD_BASIC) {
			$auth_mod = $this->getModule('basic_webuser');
			$is_auth = ($this->getModule('auth_basic')->authenticate($auth_mod, AuthBasic::REALM_WEB) === true);
			if (!$is_auth) {
				/**
				 * Basic auth is specific because log in takes place for each request.
				 * From this reason audit reports only log in failed.
				 */
				$username = $_SERVER['PHP_AUTH_USER'] ?? '';
				$this->getModule('audit')->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_SECURITY,
					"Log in failed (Basic auth). User: $username"
				);
			}
		}
		return $is_auth;
	}

	/**
	 * Check if default API host is set.
	 * If it isn't direct to API host selection page.
	 *
	 */
	private function isDefaultAPIHost()
	{
		$def_api_host = $this->User->getDefaultAPIHost();
		$auth = $this->getModule('auth');
		$page = $this->Service->getRequestedPagePath();
		$pages_no_host = [$auth->getLoginPage(), 'SelectAPIHost'];
		return (!is_null($def_api_host) || in_array($page, $pages_no_host));
	}

	/**
	 * Set page session values.
	 *
	 */
	private function setSessionUserVars()
	{
		// Set director
		$directors = $this->getModule('api')->get(['directors'], null, false);
		if ($directors->error === 0 && count($directors->output) > 0 &&
			   (!key_exists('director', $_SESSION) || $directors->output[0] != $_SESSION['director'])) {
			$_SESSION['director'] = $directors->output[0];
		}
		// Set config main component names
		$config = $this->getModule('api')->get(['config'], null, false);
		if ($config->error === 0) {
			for ($i = 0; $i < count($config->output); $i++) {
				$component = (array) $config->output[$i];
				if (key_exists('component_type', $component) && key_exists('component_name', $component)) {
					$_SESSION[$component['component_type']] = $component['component_name'];
				}
			}
		}
		$_SESSION['is_user_vars'] = true;
	}

	public function resetSessionUserVars()
	{
		$_SESSION['is_user_vars'] = false;
		$_SESSION['director'] = $_SESSION['dir'] = $_SESSION['sd'] = $_SESSION['fd'] = $_SESSION['bcons'] = '';
	}

	/**
	 * Get default page for currently logged in user.
	 *
	 * @return string page name
	 */
	public function getDefaultPage()
	{
		$def_page = $this->Service->DefaultPage;
		$manager = $this->getModule('users');
		if (!$manager->isPageAllowed($this->User, $this->Service->DefaultPage)) {
			// User hasn't access to default service page. Get first allowed page.
			$def_page = $this->findDefaultPageForUser();
		}
		if (!is_string($def_page)) {
			$def_page = $this->getModule('auth')->getLoginPage();
		}
		return $def_page;
	}

	/**
	 * Redirection to default page defined in application config.
	 *
	 * @access public
	 * @param array $params HTTP GET method parameters in associative array
	 */
	public function goToDefaultPage($params = null)
	{
		$def_page = $this->getDefaultPage();
		if ($def_page !== $this->Service->DefaultPage) {
			/**
			 * If page different than default for service, reset params because
			 * they will not work with different page.
			 */
			$params = null;
		}
		$this->goToPage($def_page, $params);
	}

	/**
	 * Find default page for an user.
	 * Useful to determine on which page direct user. It takes first one found
	 * that can be accessible by the user.
	 *
	 * @return mixed page path or null if no page for user found
	 */
	private function findDefaultPageForUser()
	{
		$manager = $this->getModule('users');
		$user_role = $this->getModule('user_role');
		$roles = $this->User->getRoles();
		$pages = [];
		for ($i = 0; $i < count($roles); $i++) {
			$rpages = $user_role->getPagesByRole($roles[$i]);
			for ($j = 0; $j < count($rpages); $j++) {
				if (!in_array($rpages[$j], $pages) && $manager->isPageAllowed($this->User, $rpages[$j])) {
					$pages[] = $rpages[$j];
				}
			}
		}
		return array_shift($pages);
	}

	/**
	 * Common actions which has to be done for each web page just after
	 * page pre-loading.
	 *
	 */
	private function postInitActions()
	{
	}
}
