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

use Bacularis\Common\Modules\AuthOAuth2;
use Prado\Prado;

/**
 * OAuth2 client module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class OAuth2Client
{
	/**
	 * OAuth2 authorization endpoints.
	 */
	public const AUTHORIZE_ENDPOINT = 'oauth/authorize/';
	public const TOKEN_ENDPOINT = 'oauth/token/';

	/**
	 * Check if host uses OAuth2 authentication.
	 *
	 * @param null|string $host host name
	 * @param array $host_cfg API host config parameters
	 * @return bool true if host uses OAuth2 authentication
	 */
	public function isOAuth2Host(?string $host = null, array $host_cfg = []): bool
	{
		return (count($host_cfg) > 0 && !is_null($host) && $host_cfg['auth_type'] === AuthOAuth2::NAME);
	}

	/**
	 * Prepare OAuth2 record state.
	 *
	 * @param null|string $host host name
	 * @param array $host_cfg API host config parameters
	 * @return array OAuth2 record state with keys: record, authorize
	 */
	public function prepareRecordState(?string $host, array $host_cfg): array
	{
		$auth = OAuth2Record::findByPk($host);
		$authorize = false;
		if (is_null($auth) || time() >= $auth['refresh_time']) {
			if (is_array($auth)) {
				OAuth2Record::deleteByPk($host);
			}
			$auth = null;
			$authorize = $this->isOAuth2Host($host, $host_cfg);
		}
		return [
			'record' => $auth,
			'authorize' => $authorize
		];
	}

	/**
	 * Create OAuth2 authorization header.
	 *
	 * @param null|array $auth OAuth2 record
	 * @return null|string OAuth2 authorization header
	 */
	public function createAuthorizationHeader(?array $auth): ?string
	{
		$ret = null;
		if (is_array($auth) && key_exists('tokens', $auth) && is_array($auth['tokens'])) {
			$ret = "Authorization: {$auth['tokens']['token_type']} {$auth['tokens']['access_token']}";
		}
		return $ret;
	}

	/**
	 * Start OAuth2 authorization.
	 *
	 * @param string $host host name
	 * @param array $host_cfg API host config parameters
	 * @return array OAuth2 authorize request params with keys: response_type, client_id, redirect_uri, scope, state
	 */
	public function startAuthorization(string $host, array $host_cfg): array
	{
		$params = $this->getAuthorizeParams($host_cfg);
		$this->saveState($host, $params['state']);
		return $params;
	}

	/**
	 * Complete OAuth2 authorization.
	 *
	 * @param string $host host name
	 * @return null|array OAuth2 record
	 */
	public function completeAuthorization(string $host): ?array
	{
		OAuth2Record::forceRefresh();
		return OAuth2Record::findByPk($host);
	}

	/**
	 * Get OAuth2 authorize request params.
	 *
	 * @param array $host_cfg API host config parameters
	 * @return array OAuth2 authorize request params with keys: response_type, client_id, redirect_uri, scope, state
	 */
	private function getAuthorizeParams(array $host_cfg): array
	{
		return [
			'response_type' => 'code',
			'client_id' => $host_cfg['client_id'],
			'redirect_uri' => $host_cfg['redirect_uri'],
			'scope' => $host_cfg['scope'],
			'state' => $this->getState()
		];
	}

	/**
	 * Prepare OAuth2 authorize request data.
	 *
	 * @param array $host_cfg API host config parameters
	 * @param null|string $uri OAuth2 authorize URI
	 * @param array $base_headers base request headers
	 * @return array cURL request data with keys: host_cfg, uri, headers, options
	 */
	public function prepareAuthorizeRequestData(array $host_cfg, ?string $uri, array $base_headers): array
	{
		$headers = $this->getAuthorizeHeaders($base_headers);
		return $this->createAuthorizeRequestData($host_cfg, $uri, $headers);
	}

	/**
	 * Prepare OAuth2 token request data.
	 *
	 * @param array $host_cfg API host config parameters
	 * @param null|string $uri OAuth2 token URI
	 * @param array $base_headers base request headers
	 * @param array $data OAuth2 token request data
	 * @return array cURL request data with keys: host_cfg, uri, headers, data
	 */
	public function prepareTokenRequestData(array $host_cfg, ?string $uri, array $base_headers, array $data): array
	{
		$headers = $this->getTokenHeaders($base_headers);
		return $this->createTokenRequestData($host_cfg, $uri, $headers, $data);
	}

	/**
	 * Get OAuth2 authorize request headers.
	 *
	 * @param array $headers base request headers
	 * @return array OAuth2 authorize request headers
	 */
	private function getAuthorizeHeaders(array $headers): array
	{
		return $headers;
	}

	/**
	 * Get OAuth2 token request headers.
	 *
	 * @param array $headers base request headers
	 * @return array OAuth2 token request headers
	 */
	private function getTokenHeaders(array $headers): array
	{
		return array_merge($headers, ['Expect:']);
	}

	/**
	 * Create OAuth2 authorize request data.
	 *
	 * @param array $host_cfg API host config parameters
	 * @param null|string $uri OAuth2 authorize URI
	 * @param array $headers request headers
	 * @return array cURL request data with keys:
	 *               host_cfg, uri, headers, options
	 */
	private function createAuthorizeRequestData(array $host_cfg, ?string $uri, array $headers): array
	{
		$options = [
			CURLINFO_HEADER_OUT => true,
			CURLOPT_FOLLOWLOCATION => true
		];
		return $this->createRequestData(
			$host_cfg,
			$uri,
			$headers,
			null,
			null,
			$options
		);
	}

	/**
	 * Create OAuth2 token request data.
	 *
	 * @param array $host_cfg API host config parameters
	 * @param null|string $uri OAuth2 token URI
	 * @param array $headers request headers
	 * @param array $data OAuth2 token request data
	 * @return array cURL request data with keys: host_cfg, uri, headers, data
	 */
	private function createTokenRequestData(array $host_cfg, ?string $uri, array $headers, array $data): array
	{
		return $this->createRequestData($host_cfg, $uri, $headers, null, $data);
	}

	/**
	 * Create cURL request data.
	 *
	 * @param array $host_cfg API host config parameters
	 * @param null|string $uri request URI
	 * @param array $headers request headers
	 * @param null|string $method HTTP method
	 * @param null|array|string $data request body
	 * @param array $options cURL options
	 * @return array cURL request data with keys: host_cfg, uri, headers, method, data, options
	 */
	private function createRequestData(array $host_cfg, ?string $uri, array $headers, ?string $method = null, $data = null, array $options = []): array
	{
		$request = [
			'host_cfg' => $host_cfg,
			'uri' => $uri,
			'headers' => $headers
		];
		if (!is_null($method)) {
			$request['method'] = $method;
		}
		if (!is_null($data)) {
			$request['data'] = $data;
		}
		if (count($options) > 0) {
			$request['options'] = $options;
		}
		return $request;
	}

	/**
	 * Decode OAuth2 token response.
	 *
	 * @param array $response response data with keys: headers, body, error, errno
	 * @return null|object OAuth2 tokens
	 */
	public function decodeTokenResponse(array $response): ?object
	{
		return json_decode($response['body']);
	}

	/**
	 * Get OAuth2 state.
	 *
	 * @return string OAuth2 state
	 */
	private function getState(): string
	{
		$app = Prado::getApplication();
		$crypto = $app->getModule('crypto');
		return $crypto->getRandomString(16);
	}

	/**
	 * Save OAuth2 state.
	 *
	 * @param null|string $host host name
	 * @param string $state OAuth2 state
	 */
	private function saveState(?string $host, string $state): void
	{
		$auth = $this->createStateRecord($host, $state);
		$auth->save();
	}

	/**
	 * Create OAuth2 state record.
	 *
	 * @param null|string $host host name
	 * @param string $state OAuth2 state
	 * @return OAuth2Record OAuth2 state record
	 */
	private function createStateRecord(?string $host, string $state): OAuth2Record
	{
		$auth = new OAuth2Record();
		$auth->host = $host;
		$auth->state = $state;
		return $auth;
	}

	/**
	 * Get OAuth2 state host.
	 *
	 * @param string $state OAuth2 state
	 * @return null|string OAuth2 state host
	 */
	public function getStateHost($state): ?string
	{
		$st = OAuth2Record::findBy('state', $state);
		return (is_array($st) ? $st['host'] : null);
	}

	/**
	 * Complete OAuth2 token callback.
	 *
	 * @param string $host host name
	 * @param null|object $tokens OAuth2 tokens
	 */
	public function completeTokenCallback(string $host, ?object $tokens): void
	{
		$this->saveTokens($host, $tokens);
		HostRecord::deleteByPk($host);
	}

	/**
	 * Get OAuth2 token request params.
	 *
	 * @param string $auth_id authorization ID
	 * @param array $host_cfg API host config parameters
	 * @return array OAuth2 token request params with keys:
	 *               grant_type, code, redirect_uri, client_id, client_secret
	 */
	public function getTokenParams($auth_id, array $host_cfg): array
	{
		return [
			'grant_type' => 'authorization_code',
			'code' => $auth_id,
			'redirect_uri' => $host_cfg['redirect_uri'],
			'client_id' => $host_cfg['client_id'],
			'client_secret' => $host_cfg['client_secret']
		];
	}

	/**
	 * Save OAuth2 tokens.
	 *
	 * @param string $host host name
	 * @param null|object $tokens OAuth2 tokens
	 */
	private function saveTokens(string $host, ?object $tokens): void
	{
		if (is_object($tokens) && isset($tokens->access_token) && isset($tokens->refresh_token)) {
			$auth = $this->createTokenRecord($host, $tokens);
			$auth->save();
		}
	}

	/**
	 * Create OAuth2 token record.
	 *
	 * @param string $host host name
	 * @param object $tokens OAuth2 tokens
	 * @return OAuth2Record OAuth2 token record
	 */
	private function createTokenRecord(string $host, object $tokens): OAuth2Record
	{
		$auth = new OAuth2Record();
		$auth->host = $host;
		$auth->tokens = (array) $tokens;
		$auth->refresh_time = $this->getRefreshTime($tokens);
		return $auth;
	}

	/**
	 * Get OAuth2 refresh time.
	 *
	 * @param object $tokens OAuth2 tokens
	 * @return int OAuth2 refresh time
	 */
	private function getRefreshTime(object $tokens): int
	{
		// refresh token 5 seconds before average expires time
		return time() + $tokens->expires_in - 5;
	}
}
