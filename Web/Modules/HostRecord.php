<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2019 Kern Sibbald
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
use Bacularis\Common\Modules\ISessionItem;
use Bacularis\Common\Modules\SessionRecord;
use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\AuthOAuth2;

/**
 * Host session record class.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Database
 */
class HostRecord extends SessionRecord implements ISessionItem
{
	/**
	 * Host record session file name.
	 */
	private const SESSION_FILE_NAME = 'Bacularis.Web.Config.session';

	/**
	 * Host record sesion file extension.
	 */
	private const SESSION_FILE_EXT = '.dump';

	/**
	 * Host record properties.
	 */
	public $host;
	public $protocol;
	public $address;
	public $port;
	public $url_prefix;
	public $auth_type;
	public $login;
	public $password;
	public $client_id;
	public $client_secret;
	public $redirect_uri;
	public $scope;

	/**
	 * Get session record identifier.
	 *
	 * @return string record identifier
	 */
	public static function getRecordId(): string
	{
		return 'host_params';
	}

	/**
	 * Get session record primary key.
	 *
	 * @return string primary key name
	 */
	public static function getPrimaryKey(): string
	{
		return 'host';
	}

	/**
	 * Get full session file path.
	 *
	 * @return string session file path
	 */
	public static function getSessionFile(): string
	{
		return Prado::getPathOfNamespace(
			self::SESSION_FILE_NAME,
			self::SESSION_FILE_EXT
		);
	}

	/**
	 * Set host in session.
	 *
	 * @param string $host host name in config
	 * @param array $params host parameters in associative array
	 * @param bool true if host record has been saved in session
	 */
	public function setHost(string $host, array $params): bool
	{
		// General properties
		$this->host = $host;
		$this->protocol = $params['protocol'] ?? 'https';
		$this->address = $params['address'] ?? '';
		$this->port = $params['port'] ?? null;
		$this->url_prefix = $params['url_prefix'] ?? '';

		// Authentication and authorization properties
		if (key_exists('auth_type', $params)) {
			$this->auth_type = $params['auth_type'];

			if ($params['auth_type'] === AuthBasic::NAME) {
				// Basic authentication
				$this->login = $params['login'] ?? '';
				$this->password = $params['password'] ?? '';
			} elseif ($params['auth_type'] === AuthOAuth2::NAME) {
				// OAuth2 authorization
				$this->client_id = $params['client_id'] ?? '';
				$this->client_secret = $params['client_secret'] ?? '';
				$this->redirect_uri = $params['redirect_uri'] ?? '';
				$this->scope = $params['scope'] ?? '';
			}
		}
		return $this->save();
	}
}
