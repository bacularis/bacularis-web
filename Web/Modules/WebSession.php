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

namespace Bacularis\Web\Modules;

use Prado\Security\TUser;

/**
 * This is Bacularis web session module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebSession extends WebModule
{
	/**
	 * Update user session properties.
	 *
	 * @param object $user user instnace
	 * @param null|string $val base session value
	 */
	public function updateSessionUser(TUser $user, ?string $val = null): bool
	{
		if (php_sapi_name() === 'cli' || $user->getIsGuest()) {
			// cli mode and guests users do not support updating session
			return false;
		}

		$sess = $this->getApplication()->getSession();
		if (is_null($sess)) {
			// session is not started
			return false;
		}

		$auth = $this->getModule('auth');
		$sess->close();

		if (is_string($val)) {
			$session_id = $this->prepareSessionID($val);
			$sess->setSessionID($session_id);
		}

		$sess->open();
		$user_key = $auth->getUserKey();
		$value = $user->saveToString();
		$sess->add($user_key, $value);
		return true;
	}

	/**
	 * Prepare and get session identifier.
	 *
	 * @param string $val base session value
	 * @return string new session identifier
	 */
	public function prepareSessionID(string $val): string
	{
		$secman = $this->getApplication()->getSecurityManager();
		$hmac = $secman->hashData($val);
		return substr($hmac, 0, -strlen($val));
	}

	/**
	 * Destroy session.
	 *
	 * @param string $val base session value
	 */
	public function destroySession(string $val)
	{
		$session_id = $this->prepareSessionID($val);
		$sess = $this->getApplication()->getSession();
		$sess->close();
		$sess->setSessionID($session_id);
		$sess->open();
		$sess->destroy();
	}
}
