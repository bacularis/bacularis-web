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

use Prado\Prado;
use Bacularis\Common\Modules\BasicUserConfig;
use Bacularis\Common\Modules\IUserConfig;

/**
 * Manage HTTP Basic auth method users.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BasicWebUserConfig extends BasicUserConfig implements IUserConfig
{
	/**
	 * Users login and password file for HTTP Basic auth.
	 */
	public const USERS_FILE_NAME = 'Bacularis.Web.Config.bacularis';
	public const USERS_FILE_EXTENSION = '.users';

	public function getConfigPath()
	{
		// First check if custom config path is set, if not, then use default users file
		return parent::getConfigPath() ?: Prado::getPathOfNamespace(self::USERS_FILE_NAME, self::USERS_FILE_EXTENSION);
	}

	/**
	 * Check if username and password are correct.
	 *
	 * @param string $username user name to log in
	 * @param string $password user password to log in
	 * @param bool $check_conf check if user exists in basic user config
	 * @return bool true if user/pass valid, otherwise false
	 */
	public function validateUsernamePassword($username, $password, $check_conf = true)
	{
		$valid = false;
		if ($username && $password) {
			$user = $this->getModule('user_config')->getConfig($username);
			$web_user = $this->getUserCfg($username);
			if (count($web_user) > 0 && (count($user) > 0 || !$check_conf)) {
				$mod = $this->getModule('crypto')->getModuleByHash($web_user['pwd_hash']);
				if (is_object($mod)) {
					$valid = $mod->verify($password, $web_user['pwd_hash']);
				}
			}
		}
		return $valid;
	}
}
