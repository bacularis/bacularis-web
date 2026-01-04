<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
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

use Prado\Prado;
use Bacularis\Common\Modules\PKCE;

/**
 * The module manages OpenID Connect session data.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class OIDCSession extends WebModule
{
	/**
	 * Cache files for OIDC endpoints.
	 */
	private const DISCOVERY_INFO_CACHE_DIR = 'Bacularis.Web.Config';
	private const DISCOVERY_INFO_FILE_EXT = '_oidc-config.cache';
	private const JWKS_CACHE_DIR = 'Bacularis.Web.Config';
	private const JWKS_FILE_EXT = '_jwks.cache';

	/**
	 * Session property keys.
	 */
	private const PROP_STATE = 'OIDC_State';
	private const PROP_CLIENT = 'OIDC_Client';
	private const PROP_PKCE = 'OIDC_PKCE';
	private const PROP_ID_TOKEN = 'OIDC_ID_Token';
	private const PROP_ACCESS_TOKEN = 'OIDC_Access_Token';
	private const PROP_REFRESH_TOKEN = 'OIDC_Refresh_Token';
	private const PROP_TOKEN_EXPIRES_IN = 'OIDC_Token_Expires_In';

	/**
	 * Get OIDC authentication state value.
	 *
	 * @return null|string OIDC state value
	 */
	public function getState()
	{
		$sess = $this->getSession();
		return $sess->itemAt(self::PROP_STATE);
	}

	/**
	 * Set OIDC authentication state value.
	 *
	 * @param string $state OIDC state value
	 */
	public function setState(string $state): void
	{
		$sess = $this->getSession();
		$sess->add(self::PROP_STATE, $state);
	}

	/**
	 * Remove OIDC authentication state.
	 */
	public function removeState(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_STATE);
	}

	/**
	 * Get identity provider name from session.
	 *
	 * @return null|string identity provider name
	 */
	public function getClient()
	{
		$sess = $this->getSession();
		return $sess->itemAt(self::PROP_CLIENT);
	}

	/**
	 * Set identity name in session.
	 *
	 * @param string $name identity provider name
	 */
	public function setClient(string $name): void
	{
		$sess = $this->getSession();
		$sess->add(self::PROP_CLIENT, $name);
	}

	/**
	 * Remove identity provider name from session.
	 */
	public function removeClient(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_CLIENT);
	}

	/**
	 * Get OIDC authentication PKCE value.
	 *
	 * @return null|string OIDC PKCE value
	 */
	public function getPKCE()
	{
		$sess = $this->getSession();
		return $sess->itemAt(self::PROP_PKCE);
	}

	/**
	 * Set OIDC authentication PKCE value.
	 *
	 * @param string $method PKCE method (plain or S256)
	 */
	public function setPKCE($method): void
	{
		$keys = PKCE::getKeys($method);
		$sess = $this->getSession();
		$sess->add(self::PROP_PKCE, $keys);
	}

	/**
	 * Remove OIDC authentication PKCE.
	 */
	public function removePKCE(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_PKCE);
	}

	/**
	 * Get OIDC authentication ID token.
	 *
	 * @return null|string OIDC ID token
	 */
	public function getIDToken()
	{
		$sess = $this->getSession();
		return $sess->itemAt(self::PROP_ID_TOKEN);
	}

	/**
	 * Set OIDC authentication ID token.
	 *
	 * @param string $id_token ID token
	 */
	public function setIDToken(string $id_token): void
	{
		$sess = $this->getSession();
		$sess->add(self::PROP_ID_TOKEN, $id_token);
	}

	/**
	 * Remove OIDC authentication ID token.
	 */
	public function removeIDToken(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_ID_TOKEN);
	}

	/**
	 * Get OAuth2 authorization access token.
	 *
	 * @return null|string access token
	 */
	public function getAccessToken()
	{
		$sess = $this->getSession();
		return $sess->itemAt(self::PROP_ACCESS_TOKEN);
	}

	/**
	 * Set OAuth2 authorization access token.
	 *
	 * @param string $access_token access token
	 */
	public function setAccessToken(string $access_token): void
	{
		$sess = $this->getSession();
		$sess->add(self::PROP_ACCESS_TOKEN, $access_token);
	}

	/**
	 * Remove OAuth2 authorization access token.
	 */
	public function removeAccessToken(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_ACCESS_TOKEN);
	}

	/**
	 * Get OAuth2 authorization refresh token.
	 *
	 * @return null|string refresh token
	 */
	public function getRefreshToken()
	{
		$sess = $this->getSession();
		return $sess->itemAt(self::PROP_REFRESH_TOKEN);
	}

	/**
	 * Set OAuth2 authorization refresh token.
	 *
	 * @param string $refresh_token refresh token
	 */
	public function setRefreshToken(string $refresh_token): void
	{
		$sess = $this->getSession();
		$sess->add(self::PROP_REFRESH_TOKEN, $refresh_token);
	}

	/**
	 * Remove OAuth2 authorization refresh token.
	 */
	public function removeRefreshToken(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_REFRESH_TOKEN);
	}

	/**
	 * Get OAuth2 authorization token expiry time.
	 *
	 * @return null|string token expiry time
	 */
	public function getTokenExpiresIn()
	{
		$sess = $this->getSession();
		return $sess->itemAt(self::PROP_TOKEN_EXPIRES_IN, -1);
	}

	/**
	 * Set OAuth2 authorization token expiry time.
	 *
	 * @param int $expires_in token expiry time
	 */
	public function setTokenExpiresIn(int $expires_in): void
	{
		$sess = $this->getSession();
		$sess->add(self::PROP_TOKEN_EXPIRES_IN, $expires_in);
	}

	/**
	 * Remove OAuth2 authorization token expiry time.
	 */
	public function removeTokenExpiresIn(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_TOKEN_EXPIRES_IN);
	}

	/**
	 * Get OIDC configuration cache
	 *
	 * @param string $id cache identifier
	 * @return null|array OIDC configuration cache
	 */
	public function getConfigCache(string $id): ?array
	{
		$config = null;
		if (empty($id)) {
			return $config;
		}
		$dir = Prado::getPathOfNamespace(self::DISCOVERY_INFO_CACHE_DIR);
		$file = $id . self::DISCOVERY_INFO_FILE_EXT;
		$path = implode(DIRECTORY_SEPARATOR, [$dir, $file]);
		if (file_exists($path)) {
			$config_raw = file_get_contents($path);
			if (is_string($config_raw)) {
				$config = json_decode($config_raw, true);
			}
		}
		return $config;
	}

	/**
	 * Set OIDC configuration cache.
	 *
	 * @param string $id cache identifier
	 * @param int $expiry_time cache expiry time in seconds
	 * @param array $cache OIDC configuration cache
	 */
	public function setConfigCache(string $id, int $expiry_time, array $cache): bool
	{
		if ($expiry_time <= 0) {
			$expiry_time = 3600; // default 1 hour
		}
		$expiry_ts = time() + $expiry_time;
		$val = ['expiry_ts' => $expiry_ts, 'cache' => $cache];
		$json = json_encode($val);
		$dir = Prado::getPathOfNamespace(self::DISCOVERY_INFO_CACHE_DIR);
		$file = $id . self::DISCOVERY_INFO_FILE_EXT;
		$path = implode(DIRECTORY_SEPARATOR, [$dir, $file]);
		return (file_put_contents($path, $json, LOCK_EX) !== false);
	}

	/**
	 * Get JWKS configuration cache.
	 *
	 * @param string $id cache identifier
	 * @return null|array JWKS configuration cache
	 */
	public function getJWKSCache(string $id): ?array
	{
		$jwks = null;
		if (empty($id)) {
			return $jwks;
		}
		$dir = Prado::getPathOfNamespace(self::JWKS_CACHE_DIR);
		$file = $id . self::JWKS_FILE_EXT;
		$path = implode(DIRECTORY_SEPARATOR, [$dir, $file]);
		if (file_exists($path)) {
			$jwks_raw = file_get_contents($path);
			if (is_string($jwks_raw)) {
				$jwks = json_decode($jwks_raw, true);
			}
		}
		return $jwks;
	}

	/**
	 * Set JWKS configuration cache.
	 *
	 * @param string $id cache identifier
	 * @param int $expiry_time cache expiry time
	 * @param array $cache JWKS configuration cache
	 * @return bool true on success, false otherwise
	 */
	public function setJWKSCache(string $id, int $expiry_time, array $cache): bool
	{
		if (empty($id)) {
			return false;
		}
		if ($expiry_time <= 0) {
			$expiry_time = 3600; // default 1 hour
		}
		$expiry_ts = time() + $expiry_time;
		$val = ['expiry_ts' => $expiry_ts, 'cache' => $cache];
		$json = json_encode($val);
		$dir = Prado::getPathOfNamespace(self::JWKS_CACHE_DIR);
		$file = $id . self::JWKS_FILE_EXT;
		$path = implode(DIRECTORY_SEPARATOR, [$dir, $file]);
		return (file_put_contents($path, $json, LOCK_EX) !== false);
	}
}
