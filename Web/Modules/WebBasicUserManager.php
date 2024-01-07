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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\IUserManager;

/**
 * Web HTTP Basic user manager module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebBasicUserManager extends WebModule implements IUserManager
{
	/**
	 * Module initialization.
	 *
	 * @param TXmlElement $config module configuration
	 */
	public function init($config)
	{
	}

	/**
	 * Validate username and password.
	 * Used during logging in process.
	 *
	 * @param string $username username
	 * @param string $password password
	 * @return bool true if user and password valid, otherwise false
	 */
	public function validateUser($username, $password)
	{
		/**
		 * Basic auth is realized by web server, so validating
		 * user/pass is always true here.
		 */
		$auth_mod = $this->getModule('basic_webuser');
		$is_auth = ($this->getModule('auth_basic')->authenticate($auth_mod, AuthBasic::REALM_WEB) === true);
		return $is_auth;
	}
}
