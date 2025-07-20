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

use Bacularis\Common\Modules\Logging;
use Bacularis\Common\Modules\JWT;
use Bacularis\Common\Modules\Miscellaneous;
use Bacularis\Common\Modules\PKCE;
use Bacularis\Common\Modules\Protocol\HTTP\Client as HTTPClient;
use Bacularis\Common\Modules\Protocol\HTTP\Headers as HTTPHeaders;
use Bacularis\Common\Modules\RSAKey;
use Bacularis\Common\Modules\SSLCertificate;
use Prado\Prado;

/**
 * The module supports operations on OpenID Connect server.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class OIDC extends WebModule
{
	public const ID_TOKEN_NAME = 'BACULARIS_OIDC_TOKEN';

	/**
	 * Default OpenID Connect scope.
	 */
	public const DEF_SCOPE = 'openid email profile';

	/**
	 * Source of the OpenID Connect user attributes
	 */
	public const USER_ATTR_SOURCE_ID_TOKEN = 'id_token';
	public const USER_ATTR_SOURCE_USERINFO_ENDPOINT = 'userinfo';

	/**
	 * Redirect URI pattern for OpenID connect.
	 */
	public const REDIRECT_URI_PATTERN = '%protocol://%host/web/oidc/%name/redirect';

	/**
	 * Attribute synchronization policies.
	 */
	public const ATTR_SYNC_POLICY_NO_SYNC = 'no_sync';
	public const ATTR_SYNC_POLICY_EACH_LOGIN = 'each_login';

	private $params;

	public function init($param)
	{
		parent::init($param);
		$this->params = $this->getModule('oidc_session');
	}

	/**
	 * Cached information taken from discovery URI
	 */
	private static $discovery_info = [];

	/**
	 * Cached information taken from JWKS URL
	 */
	private static $jwks = [];

	public function authorize(string $name, array $extra_params = []): void
	{
		if (!$this->isIdPEnabled($name)) {
			$this->reportError('Identity provider is disabled in configuration.');
			return;
		}

		$sess = $this->getApplication()->getSession();
		$sess->open();

		$crypto = $this->getModule('crypto');
		$state = $crypto->getRandomString(24);

		$idp_config = $this->getModule('idp_config');
		if (!$idp_config->identityProviderConfigExists($name)) {
			$emsg = "Requested identity provider config does not exist: {$name}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$this->reportError($emsg);
			return;
		}

		$this->params->setClient($name);
		$this->params->setState($state);
		$config = $idp_config->getIdentityProviderConfig($name);
		$oidc_idp_config = $this->getOIDCIdPConfig($name);

		// Prepare authorization request
		$params = [
			'redirect_uri' => ($config['oidc_redirect_uri'] ?? ''),
			'client_id' => ($config['oidc_client_id'] ?? ''),
			'scope' => ($config['oidc_scope'] ?? ''),
			'state' => $state,
			'response_type' => 'code'
		];
		$params = array_merge($params, $extra_params);

		if ($config['oidc_use_pkce'] == 1) {
			$this->params->setPKCE($config['oidc_pkce_method']);
			$pkce = $this->params->getPKCE();
			$params['code_challenge'] = $pkce['code_challenge'];
			$params['code_challenge_method'] = $config['oidc_pkce_method'];
		}
		if ($config['oidc_prompt'] != '') {
			$params['prompt'] = $config['oidc_prompt'];
		}

		$query = http_build_query($params);
		$url = $oidc_idp_config['authorization_endpoint'] ?? '';
		$url .= '?' . $query;
		$this->Response->redirect($url);
	}

	public function acquireToken(string $code)
	{
		$name = $this->params->getClient();
		if (!$this->isIdPEnabled($name)) {
			$this->reportError('Identity provider is disabled in configuration.');
			return;
		}

		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$oidc_idp_config = $this->getOIDCIdPConfig($name);
		$params = [
			'code' => $code,
			'client_id' => $config['oidc_client_id'],
			'client_secret' => $config['oidc_client_secret'],
			'redirect_uri' => $config['oidc_redirect_uri'],
			'grant_type' => 'authorization_code'
		];

		if ($config['oidc_use_pkce'] == 1) {
			$pkce = $this->params->getPKCE();
			$params['code_verifier'] = $pkce['code_verifier'];
			$this->params->removePKCE();
		}

		$body = http_build_query($params);
		$result = HTTPClient::post(
			$oidc_idp_config['token_endpoint'],
			$body
		);
		if ($result['error'] !== 0) {
			$error = 'Error while sending token request.';
			$emsg = $error . " URL: {$oidc_idp_config['token_endpoint']}, Body: {$body}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$this->reportError($error);
			return;
		}
		$data = json_decode($result['output'], true);
		if (isset($data['error']) || !$data) {
			$error = $data['error'] ?? '-';
			$emsg = "Error while getting token request. Error: {$error}";
			if (isset($data['error_description'])) {
				$emsg .= ", Description: {$data['error_description']}";
			}
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$this->reportError($emsg);
			return;
		}
		$valid_id = $this->validateIDToken($data['id_token']);
		$valid_at = $this->validateAccessToken($data['access_token'], $data['id_token']);
		if ($valid_id && $valid_at) {
			$this->loginUser(
				$data['id_token'],
				$data['access_token'],
				$data['expires_in'] ?? -1,
				$data['refresh_token'] ?? ''
			);
		}
	}

	/**
	 * Log in user with ID token.
	 *
	 * @param string $id_token ID token
	 * @param string $access_token access token
	 * @param int $expires_in token expiry time
	 * @param string $refresh_token refresh token (if used)
	 * @return bool true on success, false otherwise
	 */
	private function loginUser(string $id_token, string $access_token, int $expires_in = 0, string $refresh_token = ''): bool
	{
		// Get decoded ID token properties
		$id_token_dec = JWT::decodeToken($id_token);

		// Get IDP config name
		$name = $this->params->getClient();

		// User attributes
		$attrs = $this->getUserAttributes($id_token_dec, $access_token);

		$user_id = $attrs['username'];
		if (!$user_id) {
			// user attribute not found
			$emsg = 'Username attribute has not been found in ID token';
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$this->reportError($emsg);
			return false;
		}
		$sess = $this->getApplication()->getSession();
		$org_id = $sess->itemAt('login_org');
		$org_user = WebUserConfig::getOrgUser($org_id, $user_id);

		$sid = $id_token_dec['body']['sid'] ?? null;
		$users = $this->getModule('users');
		$success = $users->switchUser($org_user, $sid);
		if ($success) {
			$this->params->setIDToken($id_token);
			$this->params->setAccessToken($access_token);
			$this->params->setRefreshToken($refresh_token);
			$this->params->setTokenExpiresIn($expires_in);
			$this->params->setClient($name);
			$this->syncAttributes($attrs);
			$this->getService()->getRequestedPage()->goToDefaultPage();
		}
		return $success;
	}

	/**
	 * One-time get OAuth2 state parameter and clear the value.
	 *
	 * @return null|string OAuth2 state value
	 */
	public function getStateClear()
	{
		$state = $this->params->getState();
		$this->params->removeState();
		return $state;
	}

	/**
	 * Get OIDC configuration identifier.
	 *
	 * @return null|string OIDC config id
	 */
	public function getClient()
	{
		$client = $this->params->getClient();
		return $client;
	}

	/**
	 * Synchronize user attributes.
	 *
	 * @param array $attrs attributes to sync
	 */
	private function syncAttributes(array $attrs): void
	{
		$name = $this->params->getClient();
		$idp_config = $this->getModule('idp_config');
		$idp = $idp_config->getIdentityProviderConfig($name);
		$sync = false;
		if ($idp['oidc_attr_sync_policy'] === self::ATTR_SYNC_POLICY_EACH_LOGIN) {
			$sync = true;
		}

		if ($sync) {
			// attributes provided, so update them
			$user_id = $this->User->getUsername();
			$org_id = $this->User->getOrganization();
			$user_config = $this->getModule('user_config');
			$config = $user_config->getUserConfig($org_id, $user_id);
			if (count($config) > 0) {
				$config['long_name'] = $attrs['long_name'];
				$config['description'] = $attrs['description'];
				$config['email'] = $attrs['email'];
				$user_config->setUserConfig($org_id, $user_id, $config);
			}
		}
	}

	/**
	 * Get user attributes.
	 *
	 * @param array $id_token_dec decoded ID token
	 * @param string $access_token access token
	 * @return array user attributes
	 */
	private function getUserAttributes(array $id_token_dec, string $access_token): array
	{
		$name = $this->params->getClient();
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);

		$attrs = [];
		if ($config['oidc_user_attr_source'] === self::USER_ATTR_SOURCE_ID_TOKEN) {
			// Get user information from ID token
			$attrs = $this->getUserAttributesFromIDToken($id_token_dec);
		} elseif ($config['oidc_user_attr_source'] === self::USER_ATTR_SOURCE_USERINFO_ENDPOINT) {
			// Get user information from user info endpoint
			$attrs = $this->getUserAttributesFromUserInfo($config, $access_token);
		}


		// Configured user attributes
		$user_attr = $config['oidc_user_attr'];
		$long_name_attr = $config['oidc_long_name_attr'] ?? '';
		$desc_attr = $config['oidc_desc_attr'] ?? '';
		$email_attr = $config['oidc_email_attr'] ?? '';

		// User attribute is special and it has to be taken from ID token anyway
		$username = $id_token_dec['body'][$user_attr] ?? '';

		// Get attributes
		$long_name = $attrs[$long_name_attr] ?? '';
		$description = $attrs[$desc_attr] ?? '';
		$email = $attrs[$email_attr] ?? '';

		return [
			'username' => $username,
			'long_name' => $long_name,
			'description' => $description,
			'email' => $email
		];
	}

	/**
	 * Get user attributes from ID token.
	 *
	 * @param array $config OIDC configuration
	 * @param array $id_token_dec decoded ID token
	 * @return array user attributes
	 */
	private function getUserAttributesFromIDToken(array $id_token_dec): array
	{
		// not too much to do
		$attrs = $id_token_dec['body'] ?? [];
		return $attrs;
	}

	/**
	 * Get user attributes from user info endpoint.
	 *
	 * @param array $config OIDC configuration
	 * @param string $access_token access token
	 * @return array user attributes or empty array if attributes could not be taken
	 */
	private function getUserAttributesFromUserInfo(array $config, string $access_token): array
	{
		// Prepare and send userinfo request
		$user_info_url = $config['oidc_userinfo_endpoint'];
		$auth = "Authorization: Bearer {$access_token}";
		$headers = [$auth];
		$result = HTTPClient::get($user_info_url, $headers);

		$attrs = [];
		if ($result['error'] == 0) {
			$attrs = json_decode($result['output'], true) ?? [];
		}
		return $attrs;
	}

	/**
	 * RP-initiated log out user.
	 */
	public function rpLogoutUser(): void
	{
		$id_token = $this->params->getIDToken();
		if (!$id_token) {
			// no token, no logout
			return;
		}
		$name = $this->params->getClient();
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$oidc_idp_config = $this->getOIDCIdPConfig($name);
		if (!isset($oidc_idp_config['end_session_endpoint'])) {
			// no logout url endpoint defined
			return;
		}

		$logout_url = $oidc_idp_config['end_session_endpoint'];
		$query = [
			'id_token_hint' => $id_token
		];
		if (isset($config['oidc_post_logout_redirect_uri'])) {
			$query['post_logout_redirect_uri'] = $config['oidc_post_logout_redirect_uri'];
		}
		$logout_url .= (strpos($logout_url, '?') === false) ? '?' : '&';
		$logout_url .= http_build_query($query);

		// Remove token (no longer needed)
		$this->params->removeIDToken();

		// Logout request
		$this->Response->redirect($logout_url);
	}

	/**
	 * IdP-initiated log out user.
	 *
	 * @param string $logout_token ID token
	 * @param string $name OIDC config name
	 */
	public function idpLogoutUser(string $logout_token, string $name): void
	{
		if ($this->validateLogoutToken($logout_token, $name)) {
			// Get decoded logout token properties
			$logout_token_dec = JWT::decodeToken($logout_token);
			$val = $logout_token_dec['body']['sid'] ?? '';

			// Logout user
			$users = $this->getModule('users');
			$users->logoutUserByBaseValue($val);
		}
	}

	/**
	 * Validate ID token.
	 *
	 * @param string $id_token ID token
	 * @return bool true on success, otherwise false
	 */
	private function validateIDToken(string $id_token): bool
	{
		$name = $this->params->getClient();
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);

		// Get decoded ID token properties
		$id_token_dec = JWT::decodeToken($id_token);

		if ($config['oidc_use_discovery_endpoint'] == 1 || $config['oidc_validate_sig'] == 1) {
			// Verify token signature

			// Get public key to verify signature
			$pubkey = $this->getVerifyKey($id_token_dec);

			// Validate key
			if (count($pubkey) === 0) {
				// Without public key there is not possible to check the singature
				$this->reportError('Public key has not been found.');
				return false;
			}

			// Check key validity (algorithm...)
			if (!self::isKeySupported($pubkey)) {
				// Invalid key
				$this->reportError('Invalid public key.');
				return false;
			}

			// Verify the ID token signature
			if (!$this->isSignatureValid($pubkey, $id_token)) {
				// Verification error - invalid singnature
				$this->reportError('ID token signature verification error.');
				return false;
			}
		}

		// Verify ID token issuer
		if (!$this->isIssuerValid($id_token_dec)) {
			// ID token issuer is invalid
			$this->reportError('ID token issuer verification error.');
			return false;
		}

		// Verify ID token audience
		if (!$this->isAudienceValid($id_token_dec)) {
			// ID token audience is invalid
			$this->reportError('ID token client identifier (client_id) verification error.');
			return false;
		}

		// Verify ID token create time
		if (!$this->isCreateTimeValid($id_token_dec)) {
			// Token created too long time ago
			$this->reportError('ID token create time is older than allowed maximum value..');
			return false;
		}

		// Verify ID token expiration time
		if (!$this->isExpirationTimeValid($id_token_dec)) {
			// Token expired and is no longer valid
			$this->reportError('ID token is expired.');
			return false;
		}
		return true;
	}

	/**
	 * Validate logout token.
	 *
	 * @param string $logout_token ID token
	 * @param string $name OIDC config name
	 * @return bool true on success, otherwise false
	 */
	private function validateLogoutToken(string $logout_token, string $name): bool
	{
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$this->params->setClient($name);

		// Get decoded logout token properties
		$logout_token_dec = JWT::decodeToken($logout_token);

		if ($config['oidc_use_discovery_endpoint'] == 1 || $config['oidc_validate_sig'] == 1) {
			// Verify token signature

			// Get public key to verify signature
			$pubkey = $this->getVerifyKey($logout_token_dec);

			// Validate key
			if (count($pubkey) === 0) {
				// Without public key there is not possible to check the singature
				return false;
			}

			// Check key validity (algorithm...)
			if (!self::isKeySupported($pubkey)) {
				// Invalid key
				return false;
			}

			// Verify the ID token signature
			if (!$this->isSignatureValid($pubkey, $logout_token)) {
				// Verification error - invalid singnature
				return false;
			}
		}

		// Verify ID token issuer
		if (!$this->isIssuerValid($logout_token_dec)) {
			// ID token issuer is invalid
			return false;
		}

		// Verify ID token audience
		if (!$this->isAudienceValid($logout_token_dec)) {
			// ID token audience is invalid
			return false;
		}

		// Verify ID token create time
		if (!$this->isCreateTimeValid($logout_token_dec)) {
			// Token created too long time ago
			return false;
		}

		// Verify ID token expiration time
		if (!$this->isExpirationTimeValid($logout_token_dec)) {
			// Token expired and is no longer valid
			return false;
		}
		return true;
	}

	/**
	 * Validate access token using ID token.
	 *
	 * @param string $access_token access token
	 * @param string $id_token ID token
	 * @return bool true if token is valid, false otherwise
	 */
	public function validateAccessToken(string $access_token, string $id_token): bool
	{
		// Get decoded ID token properties
		$id_token_dec = JWT::decodeToken($id_token);
		$at_hash = $id_token_dec['body']['at_hash'];

		// Compute at hash
		$digit = hash('sha256', $access_token, true);
		$digit_small = substr($digit, 0, 16);
		$hash = Miscellaneous::encodeBase64URL($digit_small);

		// validate
		$valid = ($at_hash === $hash);
		if (!$valid) {
			$this->reportError('Validation error. Access token is invalid.');
			return false;
		}
		return true;
	}

	/**
	 * Token sanity check.
	 * General check tokens validity and refresh them if needed.
	 *
	 * @return bool true on success, otherwise false
	 */
	public function checkTokens(): void
	{
		$refresh = $logout = false;
		$id_token = $this->params->getIDToken();
		$org_id = $this->User->getOrganization();
		$org_config = $this->getModule('org_config');
		$org = $org_config->getOrganizationConfig($org_id);
		$web_config = $this->getModule('web_config');
		if ($id_token) {
			// Get decoded ID token properties
			$id_token_dec = JWT::decodeToken($id_token);

			if (!$this->isIssuerValid($id_token_dec)) { // Verify ID token issuer
				// ID token issuer is invalid, token tampered with - do logout
				$logout = true;
			} elseif (!$this->isAudienceValid($id_token_dec)) { // Verify ID token audience
				// ID token audience is invalid, token tampered with - do logout
				$logout = true;
			} elseif (!$this->isExpirationTimeValid($id_token_dec)) { // Verify ID token expiration time
				if ($this->params->getRefreshToken()) {
					// Token expired and is no longer valid - refresh
					$refresh = true;
				} else {
					// Refresh token does not exist - logout
					$logout = true;
				}
			}
		} elseif ($org && $org['auth_type'] == OrganizationConfig::AUTH_TYPE_IDP && !$web_config->isAuthMethodBasic()) {
			// user is in org that requires having token - logout
			$logout = true;
		}

		if ($logout) {
			$users = $this->getModule('users');
			$users->logout($this->Application);
		} elseif ($refresh) {
			$this->refreshTokens();
		}
	}

	/**
	 * Try to acquire new tokens using existing refresh token.
	 */
	private function refreshTokens()
	{
		$refresh_token = $this->params->getRefreshToken();
		if (!$refresh_token) {
			return;
		}
		$name = $this->params->getClient();
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$oidc_idp_config = $this->getOIDCIdPConfig($name);
		$params = [
			'grant_type' => 'refresh_token',
			'refresh_token' => $refresh_token,
			'client_id' => $config['oidc_client_id'],
			'client_secret' => $config['oidc_client_secret']
		];

		$body = http_build_query($params);
		$result = HTTPClient::post(
			$oidc_idp_config['token_endpoint'],
			$body
		);

		// remove tokens, they should not be used longer
		$this->params->removeAccessToken();
		$this->params->removeRefreshToken();
		$this->params->removeIDToken();

		if ($result['error'] === 0) {
			$data = json_decode($result['output'], true);
			if (key_exists('access_token', $data)) {
				$this->params->setAccessToken($data['access_token']);
			}
			if (key_exists('refresh_token', $data)) {
				$this->params->setRefreshToken($data['refresh_token']);
			}
			if (key_exists('id_token', $data)) {
				$this->params->setIDToken($data['id_token']);
			}
		}
	}

	/**
	 * Get verification public key from JWKS keys or from user provided key.
	 *
	 * @param string $id_token_dec decoded ID token properties
	 * @return array key properties or empty array if key not found.
	 */
	private function getVerifyKey(array $id_token_dec): array
	{
		$key_id = $id_token_dec['header']['kid'] ?? '';
		$name = $this->params->getClient();
		$oidc_idp_config = $this->getOIDCIdPConfig($name);
		$keys = key_exists('keys', $oidc_idp_config) && is_array($oidc_idp_config['keys']) ? $oidc_idp_config['keys'] : [];

		$key = [];
		for ($i = 0; $i < count($keys); $i++) {
			if (key_exists('kid', $keys[$i]) && $keys[$i]['kid'] === $key_id) {
				$key = $keys[$i];
				break;
			}
		}
		if (count($key) == 0) {
			$emsg = "Unable to find public key to verify ID token. KeyId: {$key_id}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
		}
		return $key;
	}

	/**
	 * Get public key.
	 *
	 * @param string $pubkey decoded public key in JWT form
	 * @return string public key in PEM form
	 */
	private function getPubKey(array $pubkey): string
	{
		$modulus = $pubkey['n'] ?? '';
		$exponent = $pubkey['e'] ?? '';
		$algorithm = $pubkey['alg'] ?? '';

		// NOTE: For now we support only RSA keys
		$key_type = $algorithm == JWT::ALG_RS256 ? RSAKey::KEY_TYPE : 'unknown';

		$tmpdir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$crypto_keys = $this->getModule('crypto_keys');
		$pubkey = $crypto_keys->getPublicKeyPEMFromModulusExponent(
			$key_type,
			$modulus,
			$exponent,
			$tmpdir
		);
		return $pubkey;
	}

	/**
	 * Get public key from certificate.
	 *
	 * @param string $cert certificate
	 * @return string public key in PEM form
	 */
	private function getPubKeyFromCert(string $cert): string
	{
		$ssl_cert = $this->getModule('ssl_cert');
		$cert = SSLCertificate::addCertBeginEnd($cert);

		// Get public key from certificate
		$result = $ssl_cert->getPublicKeyFromCert($cert);
		if ($result['error'] !== 0) {
			$errmsg = var_export($cert, true);
			$emsg = "Error while getting public key from certificate. Cert: {$errmsg}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			return '';
		}
		$pubkey = implode(PHP_EOL, $result['output']);
		return $pubkey;
	}

	/**
	 * Check if key is supported.
	 *
	 * @param array $key key in JWT form
	 * @return bool true on success, otherwise false
	 */
	private static function isKeySupported(array $key): bool
	{
		$valid = true;
		if (!isset($key['alg'])) {
			$errmsg = var_export($key, true);
			$emsg = "Unknown key algorithm. Key: {$errmsg}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$valid = false;
		} elseif (!in_array($key['alg'], [JWT::ALG_RS256])) {
			$emsg = "Unsupported key algorithm: {$key['alg']}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$valid = false;
		}
		return $valid;
	}

	/**
	 * Validate ID token signature.
	 *
	 * @param array $pubkey public key to verify signature
	 * @param string $id_token ID token
	 * @return bool true on success, otherwise false
	 */
	private function isSignatureValid(array $pubkey, string $id_token): bool
	{
		// Get public key
		if (key_exists('n', $pubkey) && key_exists('e', $pubkey)) {
			// Key in JWT format
			$pubkey = $this->getPubKey($pubkey);
		} elseif (key_exists('key', $pubkey)) {
			// Key provided by user in PEM format
			$pubkey = $pubkey['key'];
		}

		[$header, $body, $signature] = JWT::extractTokenParts($id_token);
		$signature_bin = Miscellaneous::decodeBase64URL($signature);
		$data = "{$header}.{$body}";
		$crypto_keys = $this->getModule('crypto_keys');
		$tmpdir = Prado::getPathOfNamespace('Bacularis.Web.Config');
		$valid = $crypto_keys->verifySignatureString(
			$pubkey,
			$signature_bin,
			$data,
			$tmpdir
		);
		if (!$valid) {
			$emsg = "Invalid ID token signature. ID token: {$id_token}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
		}
		return $valid;
	}

	/**
	 * Check if the ID token issuer is valid.
	 *
	 * @param string $id_token_dec decoded ID token properties
	 * @return bool true on success, otherwise false
	 */
	private function isIssuerValid(array $id_token_dec): bool
	{
		$name = $this->params->getClient();
		$idp_config = $this->getOIDCIdPConfig($name);
		$issuer_jwks = $idp_config['issuer'] ?? '';
		$issuer_token = $id_token_dec['body']['iss'] ?? '';
		$valid = (!empty($issuer_jwks) && !empty($issuer_token) && $issuer_jwks === $issuer_token);
		if (!$valid) {
			$emsg = "Invalid ID token issuer. JWKS issuer: {$issuer_jwks}, ID token issuer: {$issuer_token}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
		}
		return $valid;
	}

	/**
	 * Check if the ID token audience is valid.
	 *
	 * @param string $id_token_dec decoded ID token properties
	 * @return bool true on success, otherwise false
	 */
	private function isAudienceValid(array $id_token_dec): bool
	{
		$name = $this->params->getClient();
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$audience_config = $config['oidc_client_id'] ?? '';
		$audience_token = $id_token_dec['body']['aud'] ?? '';
		$valid = (!empty($audience_config) && !empty($audience_token) && $audience_config === $audience_token);
		if (!$valid) {
			$emsg = "Invalid ID token audience. Config audience: {$audience_config}, ID token audience: {$audience_token}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
		}
		return $valid;
	}

	/**
	 * Check if the ID token create time is valid.
	 *
	 * @param string $id_token_dec decoded ID token properties
	 * @return bool true on success, otherwise false
	 */
	private function isCreateTimeValid(array $id_token_dec): bool
	{
		// Maximum number of seconds from issuing
		$max_life_time = 60;

		$create_time = $id_token_dec['body']['iat'] ?? 0;
		$now = time();
		$diff = $now - $create_time;
		$valid = ($diff < $max_life_time);
		if (!$valid) {
			$emsg = "The ID token has been issued longer ($diff seconds) than $max_life_time seconds.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
		}
		return $valid;
	}

	/**
	 * Check if the ID token expiration time is valid.
	 *
	 * @param string $id_token_dec decoded ID token properties
	 * @return bool true on success, otherwise false
	 */
	private function isExpirationTimeValid(array $id_token_dec): bool
	{
		$expiration_time = $id_token_dec['body']['exp'] ?? 0;
		$now = time();
		$diff = $now - $expiration_time;
		$valid = ($diff < 0);
		if (!$valid) {
			$emsg = "The ID token is expired. Diff ({$diff} seconds).";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
		}
		return $valid;
	}

	/**
	 * Report error on login page.
	 *
	 * @param string $emsg error message
	 */
	public function reportError(string $emsg): void
	{
		$emsg .= ' Please check Bacularis logs or contact the administrator.';
		$page = $this->getService()->constructUrl(
			'LoginPage',
			['error' => $emsg]
		);
		$this->Response->redirect($page);
	}

	/**
	 * Get OIDC identity provider configuration.
	 *
	 * @param string $name OIDC name
	 */
	public function getOIDCIdPConfig(string $name): array
	{
		$oidc_idp_config = [];
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$idp_params = [
			'authorization_endpoint',
			'token_endpoint',
			'end_session_endpoint',
			'userinfo_endpoint',
			'issuer'
		];
		if (key_exists('oidc_use_discovery_endpoint', $config) && $config['oidc_use_discovery_endpoint'] == 1) {
			// Get discovery endpoint properties
			$oidc_idp_config = $this->getDiscoveryInfo($name);

			// Get JWKS keys from discovery endpoint
			$jwks_uri = $oidc_idp_config['jwks_uri'] ?? '';
			$jwks = $this->getJWKSKeys($jwks_uri);
			$oidc_idp_config['keys'] = $jwks['keys'] ?? [];
		} else {
			// Get user-defined properties
			for ($i = 0; $i < count($idp_params); $i++) {
				$oidc_idp_config[$idp_params[$i]] = $config["oidc_{$idp_params[$i]}"] ?? '';
			}
			if (key_exists('oidc_use_jwks_endpoint', $config) && $config['oidc_use_jwks_endpoint'] == 1) {
				// Get JWKS keys from user-defined JWKS endpoint
				$jwks = $this->getJWKSKeys($config['oidc_jwks_uri']);
				$oidc_idp_config['keys'] = $jwks['keys'] ?? [];
			} else {
				// Get keys provided by user in PEM format
				$key = ['key' => '', 'kid' => '', 'alg' => JWT::ALG_RS256];
				if (key_exists('oidc_public_key_string', $config)) {
					$key['key'] = implode(PHP_EOL, explode("\\n", $config['oidc_public_key_string']));
				}
				if (key_exists('oidc_public_key_id', $config)) {
					$key['kid'] = $config['oidc_public_key_id'];
				}
				$oidc_idp_config['keys'] = [$key];
			}
		}
		return $oidc_idp_config;
	}

	/**
	 * Get discovery URL information.
	 *
	 * @param string $name OIDC config name
	 * @return array remote discovery URL configuration
	 */
	private function getDiscoveryInfo(string $name): array
	{
		if (!self::$discovery_info) {
			$name = $this->params->getClient();
			$cache = $this->params->getConfigCache($name);
			if (is_null($cache) || (is_array($cache) && $cache['expiry_ts'] <= time())) {
				// cache empty or expired, refresh it
				$idp_config = $this->getModule('idp_config');
				$config = $idp_config->getIdentityProviderConfig($name);

				$discovery_url = $config['oidc_discovery_endpoint'] ?? '';
				if ($discovery_url) {
					$result = HTTPClient::get($discovery_url);
					if ($result['error'] === 0) {
						self::$discovery_info = json_decode($result['output'], true);
						$max_age = HTTPHeaders::getCacheControlMaxAge(
							$result['headers']['cache-control'] ?? ''
						);
						$this->params->setConfigCache($name, $max_age, self::$discovery_info);
					}
				}
			} else {
				// cache is ready, use it
				self::$discovery_info = $cache['cache'];
			}
		}
		return self::$discovery_info;
	}

	/**
	 * Get verification public keys from JWKS URI.
	 *
	 * @param string $jwks_uri JWKS URI
	 * @return array public keys
	 */
	private function getJWKSKeys(string $jwks_uri): array
	{
		if (!self::$jwks) {
			$name = $this->params->getClient();
			$cache = $this->params->getJWKSCache($name);
			if (is_null($cache) || (is_array($cache) && $cache['expiry_ts'] <= time())) {
				// cache is empty or expired
				$result = HTTPClient::get($jwks_uri);
				if ($result['error'] === 0) {
					$jwks = json_decode($result['output'], true);
					self::$jwks = $jwks;
					$max_age = HTTPHeaders::getCacheControlMaxAge(
						$result['headers']['cache-control'] ?? ''
					);
					$this->params->setJWKSCache($name, $max_age, self::$jwks);
				}
			} else {
				// cache is ready, use it
				self::$jwks = $cache['cache'];
			}
		}
		return self::$jwks;
	}

	/**
	 * Is identity provider enabled.
	 *
	 * @param string $name OIDC config name
	 * @return bool true if identity provider is enabled, otherwise false
	 */
	private function isIdPEnabled(string $name): bool
	{
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$enabled = (isset($config['enabled']) && $config['enabled'] == 1);
		if (!$enabled) {
			$emsg = "The identity provider '{$name}' cannot be used because it is disabled in configuration.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
		}
		return $enabled;
	}

	/**
	 * Get extra vendor-specific params to authorization flow.
	 *
	 * @param string $prefix parameter key prefix
	 * @param string $name identity provider configuration name
	 * @param array $params_def extra params definition
	 * @return array additional parameters
	 */
	protected function getParams(string $prefix, string $name, array $params_def): array
	{
		$params = [];
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		for ($i = 0; $i < count($params_def); $i++) {
			$key = $prefix . $params_def[$i];
			if (!key_exists($key, $config) || $config[$key] == '') {
				continue;
			}
			$params[$params_def[$i]] = $config[$key];
		}
		return $params;
	}

	public static function getDefaultOptions(): array
	{
		$config = [];
		$config['oidc_redirect_uri'] = '';
		$config['oidc_use_discovery_endpoint'] = '1';
		$config['oidc_discovery_endpoint'] = '';
		$config['oidc_authorization_endpoint'] = '';
		$config['oidc_token_endpoint'] = '';
		$config['oidc_end_session_endpoint'] = '';
		$config['oidc_userinfo_endpoint'] = '';
		$config['oidc_issuer'] = '';
		$config['oidc_validate_sig'] = '1';
		$config['oidc_public_key_string'] = '';
		$config['oidc_public_key_id'] = '';
		$config['oidc_use_jwks_endpoint'] = '1';
		$config['oidc_jwks_uri'] = '';
		$config['oidc_use_pkce'] = '1';
		$config['oidc_pkce_method'] = PKCE::CODE_CHALLENGE_METHOD_S256;
		$config['oidc_client_id'] = '';
		$config['oidc_client_secret'] = '';
		$config['oidc_scope'] = self::DEF_SCOPE;
		$config['oidc_post_logout_redirect_uri'] = '';
		$config['oidc_prompt'] = '';
		$config['oidc_user_attr_source'] = self::USER_ATTR_SOURCE_ID_TOKEN;
		$config['oidc_user_attr'] = '';
		$config['oidc_long_name_attr'] = '';
		$config['oidc_email_attr'] = '';
		$config['oidc_desc_attr'] = '';
		$config['oidc_attr_sync_policy'] = self::ATTR_SYNC_POLICY_NO_SYNC;
		return $config;
	}
}
