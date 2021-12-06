<?php
/*
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

Prado::using('Application.Web.Pages.Requirements');
Prado::using('Application.Common.Class.BaculumPage');
Prado::using('Application.Web.Init');
Prado::using('Application.Web.Class.WebConfig');
Prado::using('Application.Web.Class.PageCategory');

/**
 * Baculum Web page module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 * @package Baculum Web
 */
class BaculumWebPage extends BaculumPage {

	/**
	 * It is first application user pre-defined for first login.
	 * It is removed just after setup application.
	 */
	const DEFAULT_AUTH_USER = 'admin';

	protected $web_config = array();

	public function onPreInit($param) {
		parent::onPreInit($param);
		$this->web_config = $this->getModule('web_config')->getConfig();
		if (count($this->web_config) === 0) {
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
			if (!key_exists('is_user_vars', $_SESSION) || $_SESSION['is_user_vars'] === false) {
				$this->resetSessionUserVars(); // reset is required for init session vars
				$this->setSessionUserVars();
			}
		}
	}

	/**
	 * Check if default API host is set.
	 * If it isn't direct to API host selection page.
	 *
	 * @return none
	 */
	private function isDefaultAPIHost() {
		$def_api_host = $this->User->getDefaultAPIHost();
		$auth = $this->getModule('auth');
		$page = $this->Service->getRequestedPagePath();
		$pages_no_host = [$auth->getLoginPage(), 'SelectAPIHost'];
		return (!is_null($def_api_host) || in_array($page, $pages_no_host));
	}

	/**
	 * Set page session values.
	 *
	 * @return none
	 */
	private function setSessionUserVars() {
		// Set director
		$directors = $this->getModule('api')->get(array('directors'), null, false);
		if ($directors->error === 0 && count($directors->output) > 0 &&
		       (!key_exists('director', $_SESSION) || $directors->output[0] != $_SESSION['director'])) {
			$_SESSION['director'] = $directors->output[0];
		}
		// Set config main component names
		$config = $this->getModule('api')->get(array('config'), null, false);
		if ($config->error === 0) {
			for ($i = 0; $i < count($config->output); $i++) {
				$component = (array)$config->output[$i];
				if (key_exists('component_type', $component) && key_exists('component_name', $component)) {
					$_SESSION[$component['component_type']] = $component['component_name'];
				}
			}
		}
		$_SESSION['is_user_vars'] = true;

	}

	public function resetSessionUserVars() {
		$_SESSION['is_user_vars'] = false;
		$_SESSION['director'] = $_SESSION['dir'] = $_SESSION['sd'] = $_SESSION['fd'] = $_SESSION['bcons'] = '';
	}

	/**
	 * Redirection to default page defined in application config.
	 *
	 * @access public
	 * @param array $params HTTP GET method parameters in associative array
	 * @return none
	 */
	public function goToDefaultPage($params = null) {
		$def_page = $this->Service->DefaultPage;
		$manager = $this->getModule('users');
		if (!$manager->isPageAllowed($this->User, $this->Service->DefaultPage)) {
			// User hasn't access to default service page. Get first allowed page.
			$def_page = $this->findDefaultPageForUser();

			/**
			 * If page different than default for service, reset params because
			 * they will not work with different page.
			 */
			$params = null;
		}
		if (!is_string($def_page)) {
			$def_page = $this->getModule('auth')->getLoginPage();
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
	private function findDefaultPageForUser() {
		$manager = $this->getModule('users');
		$user_role = $this->getModule('user_role');
		$roles = $this->User->getRoles();
		$pages = [];
		for ($i = 0; $i < count($roles); $i++) {
			$rpages = $user_role->getPagesByRole($roles[$i]);
			for ($j = 0; $j < count($rpages); $j++) {
				if (!in_array($rpages[$i], $pages) && $manager->isPageAllowed($this->User, $rpages[$i])) {
					$pages[] = $rpages[$i];
				}
			}
		}
		return array_shift($pages);
	}

	/**
	 * Common actions which has to be done for each web page just after
	 * page pre-loading.
	 *
	 * @return none
	 */
	private function postInitActions() {
		/**
		 * If users config file doesn't exist, create it and populate
		 * using basic users file.
		 * Basic auth method is the main Baculum Web auth method. Before introducing
		 * users.conf file, it was the only one supported method.
		 */
		$result = $this->getModule('user_config')->importUsers();
		if ($result) {
			/**
			 * User must be logged out because after upgrade to first version
			 * which supports new users management and first page load
			 * roles are not saved in config yet. Hence they are not set for the user.
			 */
			$this->getModule('auth')->logout();
			$this->goToDefaultPage();
		}
	}
}
?>
