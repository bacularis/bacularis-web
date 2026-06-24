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

use Bacularis\Common\Modules\AuthBasic;
use Bacularis\Common\Modules\Errors\BconsoleError;
use Bacularis\Common\Modules\Errors\ConnectionError;
use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\Protocol\HTTP\Header as HTTPHeader;
use Bacularis\Common\Modules\Protocol\HTTP\Method as HTTPMethod;

/**
 * Bacularis API client module.
 * It supports HTTP basic authentication and OAuth2 authorization.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BacularisAPIClient extends WebModule
{
	/**
	 * API client version (used in HTTP header)
	 *
	 * 0.2 -
	 * 0.3 - sending config as JSON instead of serialized array
	 * 0.4 - sending POST and PUT requests body parameters as JSON instead of POST form params
	 */
	public const API_CLIENT_VERSION = 0.4;

	/**
	 * API version used by Web
	 */
	public const API_VERSION = 'v3';

	/**
	 * OAuth2 authorization endpoints
	 */
	public const OAUTH2_AUTH_URL = OAuth2Client::AUTHORIZE_ENDPOINT;
	public const OAUTH2_TOKEN_URL = OAuth2Client::TOKEN_ENDPOINT;

	/**
	 * Session params to put in URLs.
	 */
	private const SESSION_URL_PARAMS = ['director'];

	/**
	 * API server version for current request.
	 */
	public $api_server_version = 0;

	/**
	 * Single request response headers.
	 */
	public $response_headers = [];

	/**
	 * OAuth2 client helper.
	 */
	private $oauth2_client;

	/**
	 * Get OAuth2 client helper.
	 *
	 * @return OAuth2Client OAuth2 client helper
	 */
	private function getOAuth2Client(): OAuth2Client
	{
		if (is_null($this->oauth2_client)) {
			$this->oauth2_client = new OAuth2Client();
		}
		return $this->oauth2_client;
	}

	/**
	 * Get connection request handler.
	 * For data requests is used cURL interface.
	 *
	 * @param array $host_cfg API host config parameters
	 * @return resource connection handler on success, false on errors
	 */
	public function getConnection(array $host_cfg)
	{
		$ch = curl_init();
		$this->setBasicAuthOptions($ch, $host_cfg);
		$this->setDefaultRequestOptions($ch);
		return $ch;
	}

	/**
	 * Set cURL Basic Auth options.
	 *
	 * @param resource $ch cURL connection handler
	 * @param array $host_cfg API host config parameters
	 */
	private function setBasicAuthOptions($ch, array $host_cfg): void
	{
		if (count($host_cfg) > 0 && $host_cfg['auth_type'] === AuthBasic::NAME) {
			$userpwd = sprintf('%s:%s', $host_cfg['login'], $host_cfg['password']);
			curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		}
	}

	/**
	 * Set default request options.
	 *
	 * @param resource $ch cURL connection handler
	 */
	private function setDefaultRequestOptions($ch): void
	{
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . md5(session_id()));
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
	}

	/**
	 * Get base API specific headers used in HTTP requests.
	 *
	 * @return array base API specific headers
	 */
	private function getBaseAPIHeaders(): array
	{
		return [
			'X-Baculum-API: ' . (string) (self::API_CLIENT_VERSION),
			'Accept: application/json'
		];
	}

	/**
	 * Get API specific headers used in HTTP requests.
	 *
	 * @param null|string $host API host name
	 * @param array $host_cfg API host configuration
	 * @return array API specific headers
	 */
	private function getAPIHeaders(?string $host = null, array $host_cfg = []): array
	{
		$headers = $this->getBaseAPIHeaders();
		$auth_header = $this->getOAuth2AuthorizationHeader($host, $host_cfg);
		if (!is_null($auth_header)) {
			$headers[] = $auth_header;
		}
		return $headers;
	}

	/**
	 * Get OAuth2 authorization header.
	 *
	 * @param null|string $host host name
	 * @param array $host_cfg API host config parameters
	 * @return null|string OAuth2 authorization header
	 */
	private function getOAuth2AuthorizationHeader(?string $host = null, array $host_cfg = []): ?string
	{
		$oauth2 = $this->getOAuth2Client();
		if (!$oauth2->isOAuth2Host($host, $host_cfg)) {
			return null;
		}
		$auth = $this->getOAuth2Record($host, $host_cfg);
		return $oauth2->createAuthorizationHeader($auth);
	}

	/**
	 * Get URI to use by internal API client's request.
	 *
	 * @param null|string $host host name
	 * @param array $params GET params to send in request
	 * @return null|string URI to internal API server or null if host config does no exist
	 */
	private function getURIResource(?string $host, array $params): ?string
	{
		$uri = null;
		$host_cfg = $this->getHostParams($host);
		if (count($host_cfg) > 0) {
			$base_uri = $this->getBaseURI($host_cfg);
			$api_path = $this->getAPIPath($params);
			$uri = $base_uri . $api_path;
			$uri = $this->addSpecialParams($uri);

			Logging::log(
				Logging::CATEGORY_APPLICATION,
				'API REQUEST ==> ' . $uri
			);
		}
		return $uri;
	}

	/**
	 * Get OAuth2 endpoint with params.
	 *
	 * @param array $host_cfg API host config parameters
	 * @param array $params query parameters
	 * @param string $endpoint endpoint path
	 * @return string URI to internal OAuth2 request
	 */
	private function getURIAuth(array $host_cfg, array $params, string $endpoint): string
	{
		$uri = $this->getBaseURI($host_cfg) . $endpoint;
		foreach ($params as $key => $value) {
			$uri = $this->addQueryParam($uri, $key, $value);
		}
		return $uri;
	}

	/**
	 * Get API resource path.
	 *
	 * @param array $params GET params to send in request
	 * @return string API resource path
	 */
	private function getAPIPath(array $params): string
	{
		$parameters = array_merge(
			['api', self::API_VERSION],
			$params
		);
		return $this->prepareURLParams($parameters);
	}

	/**
	 * Get base API host URI
	 *
	 * @param array $host_cfg API host config parameters
	 */
	private function getBaseURI(array $host_cfg): string
	{
		return sprintf(
			'%s://%s:%d%s/',
			$host_cfg['protocol'],
			$host_cfg['address'],
			$host_cfg['port'],
			$host_cfg['url_prefix']
		);
	}

	/**
	 * Get host specific params to put in URI.
	 *
	 * @param null|string $host API host name
	 * @return array host parameters
	 */
	protected function getHostParams(?string $host): array
	{
		$host_params = HostRecord::findByPk($host);
		if (is_null($host_params)) {
			$host_config = $this->getModule('host_config');
			$host_params = $host_config->getHostConfig($host);
		}
		return $host_params;
	}

	/**
	 * Set/prepare host specific params.
	 *
	 * @param null|string $host host name
	 * @param array $params host parameters
	 */
	public function setHostParams(?string $host, array $params): void
	{
		new HostRecord($host, $params);
	}

	/**
	 * Prepare GET params to put in URI as string.
	 *
	 * @param array $params GET parameters
	 * @param string $separator char used as glue to join params
	 * @return string parameters ready to put in URI
	 */
	private function prepareURLParams(array $params, string $separator = '/'): string
	{
		$callback = function ($p) {
			if (strpos($p, '?') === 0) {
				// query string should be encoded manually
				return $p;
			}
			return rawurlencode($p);
		};
		$params_encoded = array_map($callback, $params);
		$params_url = implode($separator, $params_encoded);
		return $params_url;
	}

	/**
	 * Add special params to URI.
	 *
	 * @param string $uri URI string
	 * @return string URI with special params
	 */
	private function addSpecialParams(string $uri): string
	{
		// add special session params
		$sess = $this->getApplication()->getSession();
		for ($i = 0; $i < count(self::SESSION_URL_PARAMS); $i++) {
			if (!$sess->contains(self::SESSION_URL_PARAMS[$i])) {
				// param value not in session, skip it
				continue;
			}
			$value = $sess->itemAt(self::SESSION_URL_PARAMS[$i]);
			$uri = $this->addQueryParam(
				$uri,
				self::SESSION_URL_PARAMS[$i],
				$value
			);
		}
		return $uri;
	}

	/**
	 * Add query param to URI.
	 *
	 * @param string $uri URI string
	 * @param string $key query param key
	 * @param string $value query param value
	 * @return string URI with query param
	 */
	private function addQueryParam(string $uri, string $key, string $value): string
	{
		$separator = (preg_match('/\?/', $uri) === 1 ? '&' : '?');
		$url_params = $this->prepareURLParams([$key, $value], '=');
		return $uri . $separator . $url_params;
	}

	/**
	 * Internal API GET request.
	 *
	 * @param array $params GET params to send in request
	 * @param null|string $host host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @param bool $use_cache if true then try to use session cache, if false then always use fresh data
	 * @return object stdClass with request result as two properties: 'output' and 'error'
	 */
	public function get(array $params, ?string $host = null, bool $show_error = true, $use_cache = false): object // DO NOT REMOVE $use_cache param. To use in the future
	{
		$ret = $this->request(HTTPMethod::GET, $params, [], $host, $show_error);
		$this->doPostGETRequestAction($ret);
		return $ret;
	}

	/**
	 * Internal API SET request.
	 *
	 * @param array $params GET params to send in request
	 * @param array $options POST params to send in request
	 * @param null|string $host host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object stdClass with request result as two properties: 'output' and 'error'
	 */
	public function set(array $params, array $options = [], ?string $host = null, bool $show_error = true): object
	{
		return $this->request(HTTPMethod::PUT, $params, $options, $host, $show_error);
	}

	/**
	 * Internal API CREATE request.
	 *
	 * @param array $params GET params to send in request
	 * @param array $options POST params to send in request
	 * @param null|string $host host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object stdClass with request result as two properties: 'output' and 'error'
	 */
	public function create(array $params, array $options, ?string $host = null, bool $show_error = true): object
	{
		return $this->request(HTTPMethod::POST, $params, $options, $host, $show_error);
	}

	/**
	 * Internal API REMOVE request.
	 *
	 * @param array $params GET params to send in request
	 * @param null|string $host host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object stdClass with request result as two properties: 'output' and 'error'
	 */
	public function remove(array $params, ?string $host = null, $show_error = true): object
	{
		return $this->request(HTTPMethod::DELETE, $params, [], $host, $show_error);
	}

	/**
	 * Internal API request.
	 *
	 * @param string $method HTTP method
	 * @param array $params GET params to send in request
	 * @param array $options POST params to send in request
	 * @param null|string $host host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object stdClass with request result as two properties: 'output' and 'error'
	 */
	private function request(string $method, array $params, array $options = [], ?string $host = null, bool $show_error = true): object
	{
		$request = $this->prepareAPIRequest($method, $params, $options, $host);
		$response = $this->executeAPIRequest($request);
		return $this->parseAPIResponse(
			$response['body'],
			$response['error'],
			$response['errno'],
			$show_error
		);
	}

	/**
	 * Prepare internal API request data.
	 *
	 * @param string $method HTTP method
	 * @param array $params GET params to send in request
	 * @param array $options POST params to send in request
	 * @param null|string $host host name to send request
	 * @return array cURL request data
	 */
	private function prepareAPIRequest(string $method, array $params, array $options = [], ?string $host = null): array
	{
		if (is_null($host)) {
			$host = $this->User->getDefaultAPIHost();
		}
		$host_cfg = $this->getHostParams($host);
		$uri = $this->getURIResource($host, $params);
		$data = null;
		if ($method === HTTPMethod::PUT || $method === HTTPMethod::POST) {
			$data = json_encode($options);
		}
		$headers = $this->getRequestHeaders($method, $host, $host_cfg, $data);

		return $this->createRequestData(
			$host_cfg,
			$uri,
			$headers,
			$method,
			$data
		);
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
	 * Execute prepared internal API request.
	 *
	 * @param array $request cURL request data
	 * @return array response data with keys: headers, body, error, errno
	 */
	private function executeAPIRequest(array $request): array
	{
		$response = $this->executeRequest($request);
		$this->setResponseHeaders($response);
		return $response;
	}

	/**
	 * Set response headers from response data.
	 *
	 * @param array $response response data with headers key
	 */
	private function setResponseHeaders(array $response): void
	{
		$this->response_headers = $response['headers'];
	}

	/**
	 * Execute prepared cURL request.
	 *
	 * @param array $request cURL request data
	 * @return array response data with keys: headers, body, error, errno
	 */
	private function executeRequest(array $request): array
	{
		$ch = $this->getConnection($request['host_cfg']);
		curl_setopt($ch, CURLOPT_URL, $request['uri']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request['headers']);
		if (isset($request['method'])) {
			$data = (key_exists('data', $request) ? $request['data'] : null);
			$this->setRequestMethod($ch, $request['method'], $data);
		} elseif (key_exists('data', $request)) {
			$this->setRequestBody($ch, $request['data']);
		}
		if (isset($request['options']) && is_array($request['options'])) {
			curl_setopt_array($ch, $request['options']);
		}
		$result = curl_exec($ch);
		$response = $this->createResponseData($ch, $result);
		curl_close($ch);
		return $response;
	}

	/**
	 * Create response data from cURL result.
	 *
	 * @param resource $ch cURL connection handler
	 * @param bool|string $result cURL result
	 * @return array response data with keys: headers, body, error, errno
	 */
	private function createResponseData($ch, $result): array
	{
		$result = ($result === false ? '' : $result);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$response = $this->parseResponse($result, $header_size);

		return [
			'headers' => HTTPHeader::parseAll($response['header']),
			'body' => $response['body'],
			'error' => curl_error($ch),
			'errno' => curl_errno($ch)
		];
	}

	/**
	 * Parse raw cURL response.
	 *
	 * @param string $result raw cURL result
	 * @param int $header_size response header size
	 * @return array response data with keys: header, body
	 */
	private function parseResponse(string $result, int $header_size): array
	{
		return [
			'header' => substr($result, 0, $header_size),
			'body' => substr($result, $header_size)
		];
	}

	/**
	 * Get HTTP headers for internal API request.
	 *
	 * @param string $method HTTP method
	 * @param null|string $host host name to send request
	 * @param array $host_cfg API host config parameters
	 * @param null|string $data request body
	 * @return array HTTP headers
	 */
	private function getRequestHeaders(string $method, ?string $host, array $host_cfg, ?string $data = null): array
	{
		$headers = $this->getAPIHeaders($host, $host_cfg);
		switch ($method) {
			case HTTPMethod::PUT:
				$headers = array_merge($headers, [
					'X-HTTP-Method-Override: PUT',
					'Content-Length: ' . strlen($data),
					'Expect:'
				]);
				break;
			case HTTPMethod::POST:
				$headers = array_merge($headers, ['Expect:']);
				break;
			case HTTPMethod::DELETE:
				$headers = array_merge($headers, [
					'X-HTTP-Method-Override: DELETE'
				]);
				break;
		}
		return $headers;
	}

	/**
	 * Set HTTP method specific cURL options.
	 *
	 * @param resource $ch cURL connection handler
	 * @param string $method HTTP method
	 * @param null|string $data request body
	 */
	private function setRequestMethod($ch, string $method, ?string $data = null): void
	{
		switch ($method) {
			case HTTPMethod::GET:
				curl_setopt($ch, CURLOPT_ENCODING, '');
				break;
			case HTTPMethod::PUT:
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, HTTPMethod::PUT);
				$this->setRequestBody($ch, $data);
				break;
			case HTTPMethod::POST:
				$this->setRequestBody($ch, $data);
				break;
			case HTTPMethod::DELETE:
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, HTTPMethod::DELETE);
				break;
		}
	}

	/**
	 * Set cURL request body options.
	 *
	 * @param resource $ch cURL connection handler
	 * @param null|array|string $data request body
	 */
	private function setRequestBody($ch, $data = null): void
	{
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	}

	/**
	 * Parse and prepare Internal API response.
	 * If a error occurs then redirect to appropriate error page.
	 *
	 * @param string $result response output as JSON string (not object yet)
	 * @param string $error error message from remote host
	 * @param int $errno error number from remote host
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object stdClass parsed response with two top level properties 'output' and 'error'
	 */
	private function parseAPIResponse(string $result, string $error, int $errno, bool $show_error = true): object
	{
		Logging::log(
			Logging::CATEGORY_APPLICATION,
			$result
		);
		$resource = $this->decodeAPIResponse($result, $error, $errno);
		$this->handleAPIResponseError($resource, $show_error);
		Logging::log(
			Logging::CATEGORY_APPLICATION,
			$resource
		);
		return $resource;
	}

	/**
	 * Decode Internal API response.
	 *
	 * @param string $result response output as JSON string
	 * @param string $error error message from remote host
	 * @param int $errno error number from remote host
	 * @return object decoded API response
	 */
	private function decodeAPIResponse(string $result, string $error, int $errno): object
	{
		$resource = json_decode($result);
		if (is_null($resource)) {
			$resource = (object) [
				'error' => ConnectionError::ERROR_CONNECTION_TO_HOST_PROBLEM,
				'output' => ConnectionError::MSG_ERROR_CONNECTION_TO_HOST_PROBLEM . " cURL error $errno: $error. $result"
			];
		}
		return $resource;
	}

	/**
	 * Handle API response error.
	 *
	 * @param object $resource decoded API response
	 * @param bool $show_error if true then it shows error as HTML error page
	 */
	private function handleAPIResponseError(object $resource, bool $show_error): void
	{
		if ($show_error === true && $resource->error != 0 && !$this->isAJAXRequest()) {
			$this->redirectAPIError($resource);
		}
	}

	/**
	 * Check if current request is AJAX request.
	 *
	 * @return bool true if current request is AJAX request
	 */
	private function isAJAXRequest(): bool
	{
		$headers = $this->Request->getHeaders(CASE_UPPER);
		return (isset($headers['X-REQUESTED-WITH']) && $headers['X-REQUESTED-WITH'] === 'XMLHttpRequest');
	}

	/**
	 * Redirect API error to error page.
	 *
	 * @param object $resource decoded API response
	 */
	private function redirectAPIError(object $resource): void
	{
		$url = $this->Service->constructUrl('BacularisError', (array) $resource, false);
		header("Location: $url");
		// write all logs before exiting, otherwise they will be lost
		$log = $this->getModule('log');
		$log->collectLogs(null);
		exit();
	}

	/**
	 * Do post-request action.
	 * Currently used only to GET requests.
	 *
	 * @param StdClass $resource response output object
	 */
	private function doPostGETRequestAction(object $resource): void
	{
		if (!property_exists($resource, 'error')) {
			// Do nothing
		} elseif ($resource->error == BconsoleError::ERROR_INVALID_DIRECTOR) {
			/**
			 * Reset user session values. It is specially required if
			 * user changes director to different. This way session variables
			 * will be reseted for new director.
			 */
			$sess = $this->getApplication()->getSession();
			$sess->open();
			$sess->add('is_user_vars', false);
		}
	}

	/**
	 * Get OAuth2 record.
	 *
	 * @param null|string $host host name
	 * @param array $host_cfg API host config parameters
	 * @return null|array OAuth2 record
	 */
	private function getOAuth2Record(?string $host, array $host_cfg): ?array
	{
		$oauth2 = $this->getOAuth2Client();
		$state = $oauth2->prepareRecordState($host, $host_cfg);
		$auth = $state['record'];
		if ($state['authorize'] && !is_null($host)) {
			$auth = $this->authorizeOAuth2Host($host, $host_cfg);
		}
		return $auth;
	}

	/**
	 * Authorize OAuth2 host.
	 *
	 * @param string $host host name to send request
	 * @param array $host_cfg API host config parameters
	 * @return null|array OAuth2 record
	 */
	private function authorizeOAuth2Host(string $host, array $host_cfg): ?array
	{
		$oauth2 = $this->getOAuth2Client();
		$params = $oauth2->startAuthorization($host, $host_cfg);
		$uri = $this->getURIAuth(
			$host_cfg,
			$params,
			self::OAUTH2_AUTH_URL
		);
		$this->executeOAuth2AuthorizeRequest($uri, $host_cfg);
		return $oauth2->completeAuthorization($host);
	}

	/**
	 * Prepare OAuth2 authorize request data.
	 *
	 * @param string $uri OAuth2 authorize URI
	 * @param array $host_cfg API host config parameters
	 * @return array cURL request data
	 */
	private function prepareOAuth2AuthorizeRequest(string $uri, array $host_cfg): array
	{
		$oauth2 = $this->getOAuth2Client();
		$headers = $this->getBaseAPIHeaders();
		return $oauth2->prepareAuthorizeRequestData(
			$host_cfg,
			$uri,
			$headers
		);
	}

	/**
	 * Execute OAuth2 authorize request.
	 *
	 * @param string $uri OAuth2 authorize URI
	 * @param array $host_cfg API host config parameters
	 */
	private function executeOAuth2AuthorizeRequest(string $uri, array $host_cfg): void
	{
		$request = $this->prepareOAuth2AuthorizeRequest($uri, $host_cfg);
		$this->executeRequest($request);
	}

	public function getTokens($auth_id, $state)
	{
		$this->processOAuth2TokenCallback($auth_id, $state);
	}

	/**
	 * Process OAuth2 token callback.
	 *
	 * @param string $auth_id authorization ID
	 * @param string $state OAuth2 state
	 */
	private function processOAuth2TokenCallback($auth_id, $state): void
	{
		$oauth2 = $this->getOAuth2Client();
		$host = $oauth2->getStateHost($state);
		if (!is_null($host)) {
			$host_cfg = $this->getHostParams($host);
			$uri = $this->getURIAuth(
				$host_cfg,
				[],
				self::OAUTH2_TOKEN_URL
			);
			$data = $oauth2->getTokenParams($auth_id, $host_cfg);
			$tokens = $this->executeOAuth2TokenRequest($uri, $host_cfg, $data);
			$oauth2->completeTokenCallback($host, $tokens);
		}
	}

	/**
	 * Prepare OAuth2 token request data.
	 *
	 * @param string $uri OAuth2 token URI
	 * @param array $host_cfg API host config parameters
	 * @param array $data OAuth2 token request data
	 * @return array cURL request data
	 */
	private function prepareOAuth2TokenRequest(string $uri, array $host_cfg, array $data): array
	{
		$oauth2 = $this->getOAuth2Client();
		$headers = $this->getBaseAPIHeaders();
		return $oauth2->prepareTokenRequestData(
			$host_cfg,
			$uri,
			$headers,
			$data
		);
	}

	/**
	 * Execute OAuth2 token request.
	 *
	 * @param string $uri OAuth2 token URI
	 * @param array $host_cfg API host config parameters
	 * @param array $data OAuth2 token request data
	 * @return null|object OAuth2 tokens
	 */
	private function executeOAuth2TokenRequest(string $uri, array $host_cfg, array $data): ?object
	{
		$oauth2 = $this->getOAuth2Client();
		$request = $this->prepareOAuth2TokenRequest($uri, $host_cfg, $data);
		$response = $this->executeRequest($request);
		$this->setResponseHeaders($response);
		return $oauth2->decodeTokenResponse($response);
	}

	/**
	 * Get Baculum web server version.
	 * Value available after receiving response.
	 *
	 * @return float server version
	 */
	public function getServerVersion()
	{
		$version = 0;
		if (key_exists('baculum-api-version', $this->response_headers)) {
			$version = (float) ($this->response_headers['baculum-api-version']);
		}
		return $version;
	}
}
