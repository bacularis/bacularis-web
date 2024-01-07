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

namespace Bacularis\Web\Portlets;

/**
 * Main side-bar control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class MainSideBar extends Portlets
{
	/**
	 * Reload URL is used to refresh page after logout with Basic auth.
	 */
	public $reload_url = '';

	/**
	 * Is API configured.
	 */
	public $is_api = false;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getModule('web_config')->isAuthMethodBasic()) {
			$fake_pwd = $this->getModule('crypto')->getRandomString();
			// must be different than currently logged in Basic user
			$user = ($_SERVER['PHP_AUTH_USER'] ?? '') . '1';

			// do a login try with different user and password to logout current user
			$this->reload_url = $this->getPage()->getFullLoginUrl($user, $fake_pwd);
		}
		$api_config = $this->getModule('api_config')->getConfig();
		$this->is_api = count($api_config) > 0;
	}

	public function logout($sender, $param)
	{
		$this->getModule('auth')->logout();
		if ($this->getModule('web_config')->isAuthMethodBasic()) {
			/**
			 * This status code 401 is necessary to stop comming AJAX requests
			 * and to bring the login prompt on.
			 */
			$this->Response->setStatusCode(401);
		} else {
			$this->getPage()->goToDefaultPage();
		}
	}
}
