<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\AuthBasic;
use Prado\Prado;
use Prado\Security\IUserManager;
use Prado\Security\TAuthorizationRule;
use Prado\Web\Services\TPageService;

/**
 * Web user manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebUserManager extends WebModule implements IUserManager
{
	/**
	 * Authorized flag key.
	 * This flag is used to set authorization state.
	 */
	public const SET_AUTHROIZED_FLAG = 'AuthorizedFlag';

	/**
	 * User class to represent single user instance
	 */
	private $user_class = '';

	/**
	 * Guest name.
	 */
	private $guest_name = 'guest';

	/**
	 * Stores object used to create users (factory)
	 */
	private $user_factory;

	/**
	 * Initialize module configuration.
	 *
	 * @param TXmlElement $config module configuration
	 */
	public function init($config)
	{
		$this->user_factory = Prado::createComponent($this->user_class, $this);
		$this->setAuthorizedFlag(null);
		$this->getModule('auth')->attachEventHandler(
			'OnAuthenticate',
			[$this, 'doAuthentication']
		);
		$this->Application->attachEventHandler(
			'onAuthorization',
			[$this, 'doAuthorization']
		);
		$this->Application->attachEventHandler(
			'onAuthorizationComplete',
			[$this, 'doAuthorizationComplete']
		);
	}

	/**
	 * Create and get new user object.
	 *
	 * @param mixed $org_user user data or null for guests
	 */
	public function getUser($org_user = null)
	{
		$user = null;
		if (is_null($org_user)) {
			$user = Prado::createComponent($this->user_class, $this);
			$user->setIsGuest(true);
		} else {
			$user = $this->user_factory->createUser($org_user);
			$user->setIsGuest(false);
		}
		return $user;
	}

	/**
	 * Used for authentication.
	 * It does login try.
	 *
	 * @param array $org_user user data
	 * @param string $password password
	 * @return bool true if user and password are valid, false otherwise
	 */
	public function validateUser($org_user, $password)
	{
		$valid = false;
		$manager_cls = $this->getUserManagerClass();
		if (!empty($manager_cls) && isset($org_user['user_id'])) {
			$manager = Prado::createComponent($manager_cls);
			$manager->init(null);
			$valid = $manager->validateUser($org_user['user_id'], $password);
		}
		return $valid;
	}

	/**
	 * User class getter.
	 *
	 * @return string user class name
	 */
	public function getUserClass()
	{
		return $this->user_class;
	}

	/**
	 * User class setter.
	 *
	 * @param string $cls user class name
	 */
	public function setUserClass($cls)
	{
		$this->user_class = $cls;
	}

	/**
	 * Guest name getter.
	 *
	 * @return guest name
	 */
	public function getGuestName()
	{
		return $this->guest_name;
	}

	/**
	 * Guest name setter.
	 *
	 * @param string guest name
	 * @param mixed $name
	 */
	public function setGuestName($name)
	{
		$this->guest_name = $name;
	}

	/**
	 * Used for getting stored in cookie information about user.
	 * Useful to keep login between sessions.
	 *
	 * @param THttpCookie $cookie cookie object
	 */
	public function getUserFromCookie($cookie)
	{
		// not implemented
		return null;
	}

	/**
	 * Used for setting in cookie information about user.
	 * Useful to keep login between sessions.
	 *
	 * @param THttpCookie $cookie cookie object
	 */
	public function saveUserToCookie($cookie)
	{
		// not implemented
	}

	/**
	 * Check if currently loading page is allowed for current user.
	 *
	 * @param WebUser $user user object
	 * @param string $page_path page path
	 */
	public function isPageAllowed($user, $page_path)
	{
		$allowed = false;
		$page_roles = $this->getModule('user_role')->getRolesByPagePath($page_path);
		$user_roles = $user->getRoles();
		for ($i = 0; $i < count($user_roles); $i++) {
			if (in_array($user_roles[$i], $page_roles)) {
				$allowed = true;
				break;
			}
		}
		return $allowed;
	}

	/**
	 * Get user manager class.
	 * It is switcher between different authentication backends.
	 *
	 * @return string user manager class path
	 */
	private function getUserManagerClass()
	{
		$cls = null;
		$auth_method = $this->getModule('web_config')->getAuthMethod();

		switch ($auth_method) {
			case WebConfig::AUTH_METHOD_LOCAL:
				$cls = 'Bacularis.Web.Modules.WebLocalUserManager';
				break;
			case WebConfig::AUTH_METHOD_BASIC:
				$cls = 'Bacularis.Web.Modules.WebBasicUserManager';
				break;
			case WebConfig::AUTH_METHOD_LDAP:
				$cls = 'Bacularis.Web.Modules.WebLdapUserManager';
				break;
			default:
				$cls = 'Bacularis.Web.Modules.WebLocalUserManager';
		}
		return $cls;
	}

	/**
	 * Event handler attached to the application authentication event.
	 * It does auto-login for Basic authentication users and applies
	 * authorization rules before they are used in authorization phase.
	 *
	 * @param TApplication $application application object
	 */
	public function doAuthentication($application)
	{
		$web_config = $this->getModule('web_config');
		if ($application->getUser()->IsGuest) {
			if ($web_config->isAuthMethodBasic()) {
				/**
				 * Open session to be able to log in.
				 */
				$sess = $this->getApplication()->getSession();
				$sess->open();

				// If basic user is not logged it, try to log in here
				$user_id = $_SERVER['PHP_AUTH_USER'] ?? '';
				$password = $_SERVER['PHP_AUTH_PW'] ?? '';
				$org_user = WebUserConfig::getOrgUser('', $user_id);
				$auth = $this->getModule('auth');
				$auth->login($org_user, $password);
			}
		}

		$this->applyAuthorizationRules($application);
	}

	/**
	 * Apply authorization rules.
	 * It is important place because here are defined authorization rules for currently
	 * logged in user and currently visited page.
	 *
	 * @param TApplication $application application object
	 */
	private function applyAuthorizationRules($application)
	{
		$page_path = $this->getService()->getRequestedPagePath();
		$page_roles = $this->getModule('user_role')->getRolesByPagePath($page_path, false);
		$auth_rules = $this->getApplication()->getAuthorizationRules();
		$web_config = $this->getModule('web_config');
		$roles = implode(',', $page_roles);
		$users = '';
		$ips = $application->User->getIps();
		$pc = $this->getModule('page_category');
		$add_role = false;
		if ($pc->isCategorySystem($page_path) || $pc->isCategoryConditional($page_path)) {
			// allow system pages for all logged in users
			$roles = '';
			$users = '@';
			$add_role = true;
		}

		if ($pc->isCategoryPublic($page_path)) {
			// public pages are available for everybody
			$roles = $ips = '';
			$users = '*';
			$add_role = true;
		}

		// remove authorization rules if any
		$auth_rules->clear();

		/**
		 * Rules for current page.
		 * NOTE: items order has meaning.
		 */
		$allow_rule = [
			'action' => 'allow',
			'roles' => $roles,
			'users' => $users,
			'verb' => '',
			'ips' => $ips
		];

		$deny_rule = [
			'action' => 'deny',
			'roles' => '',
			'users' => '*',
			'verb' => '',
			'ips' => ''
		];
		$rules = [];

		/**
		 * Add allow rule for enabled users, for special pages (system and public)
		 * and if user doesn't exist in user config and access by default setting
		 * is enabled.
		 */
		if ($application->User->Enabled === true || $add_role === true || (!$application->User->InConfig && $web_config->isDefAccessDefaultSettings())) {
			// Add allow rules for user with set enabled flag
			$rules[] = $allow_rule;
		}
		// Deny everything else
		$rules[] = $deny_rule;

		// Add authorization rules
		for ($i = 0; $i < count($rules); $i++) {
			$rule = new TAuthorizationRule(
				$rules[$i]['action'],
				$rules[$i]['users'],
				$rules[$i]['roles'],
				$rules[$i]['verb'],
				$rules[$i]['ips']
			);
			$auth_rules->insertAt($i, $rule);
		}
	}

	/**
	 * Set authorization flag.
	 * This flag is to know if user finished authorization process successfully or not.
	 * The flag is set in temporary on authorization process and it is set to null
	 * just after the authorization finishes with success. If authorization fails
	 * then onAuthorizationComplete event isn't fired because all process page loading
	 * is stopped on onAuthorization event.
	 * NOTE: This flag has only informational character. All authorization work is
	 * done by the framework.
	 *
	 * @param null|string $state flag state
	 */
	public function setAuthorizedFlag($state)
	{
		$this->Application->getSession()->add(self::SET_AUTHROIZED_FLAG, $state);
	}

	/**
	 * Get authorization flag.
	 *
	 * @return null|string flag state
	 */
	public function getAuthorizedFlag()
	{
		return $this->Application->getSession()->itemAt(self::SET_AUTHROIZED_FLAG);
	}

	/**
	 * Event handler attached to the application authorization event.
	 * It sets the authorization flag.
	 *
	 * @param TApplication $param application object
	 * @param mixed $application
	 */
	public function doAuthorization($application)
	{
		$service = $this->Application->getService();
		$auth = $this->getModule('auth');
		$page = $service->getRequestedPagePath();
		if (($service instanceof TPageService) && $page !== $auth->getLoginPage()) {
			$this->setAuthorizedFlag($page);
		}
	}

	/**
	 * Event handler attached to the application authorization event.
	 * It sets the authorization flag.
	 *
	 * @param TApplication $param application object
	 * @param mixed $application
	 */
	public function doAuthorizationComplete($application)
	{
		$service = $this->Application->getService();
		$page = $service->getRequestedPagePath();
		if (($service instanceof TPageService) && $page === $this->getAuthorizedFlag()) {
			$this->setAuthorizedFlag(null);
		}
	}

	/**
	 * Returns authorization state for current page request.
	 * Because the framework doesn't provide any public method to check
	 * whether authorization finished successfully, this method can
	 * be used for that purpose.
	 * NOTE: Use it after onAuthorizationComplete application event,
	 * not before.
	 *
	 * @return bool true if authorization finished successfully, otherwise false
	 */
	public function isAuthorized()
	{
		$web_config = $this->getModule('web_config');
		$user = $this->Application->getUser();
		return ($this->getAuthorizedFlag() === null &&
			(($user->InConfig && !$web_config->isDefAccessNoAccess()) ||
			(!$user->InConfig && $web_config->isDefAccessDefaultSettings()))
		);
	}

	/**
	 * Switch user to a new user.
	 *
	 * @param string $org_user user data
	 * @param null|string $val base session value
	 */
	public function switchUser(array $org_user, ?string $val = null): bool
	{
		$user = $this->getUser($org_user);
		if (is_null($user)) {
			return false;
		}
		$web_session = $this->getModule('web_session');
		$web_session->updateSessionUser($user, $val);
		$this->getApplication()->setUser($user);
		return true;
	}

	/**
	 * Do logout with current session.
	 *
	 * @param null|TApplication $application application object
	 */
	public function logout($application = null)
	{
		// Open session first, to be able to logout
		$sess = $this->getApplication()->getSession();
		$sess->open();

		// Do log out
		$auth = $this->getModule('auth');
		$auth->logout();

		// post-logout actions
		if ($application) {
			$web_config = $this->getModule('web_config');
			if ($web_config->isAuthMethodBasic()) {
				/**
				 * This status code 401 is necessary to stop comming AJAX requests
				 * and to bring the login prompt on.
				 */
				$application->Response->setStatusCode(401);
				AuthBasic::setAuthenticateHeader(
					AuthBasic::REALM_WEB
				);
				$application->completeRequest();
				exit();
			} else {
				$application->getService()->getRequestedPage()->goToDefaultPage();
			}
		}
	}

	/**
	 * Logout user using base session value.
	 *
	 * @param string $val base session value
	 */
	public function logoutUserByBaseValue(string $val): void
	{
		$web_session = $this->getModule('web_session');
		$web_session->destroySession($val);
	}
}
