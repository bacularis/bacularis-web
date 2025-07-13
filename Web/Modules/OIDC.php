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

	private const PROP_STATE = 'OIDC_State';
	private const PROP_CLIENT = 'OIDC_Client';
	private const PROP_PKCE = 'OIDC_PKCE';
	private const PROP_ID_TOKEN = 'OIDC_ID_Token';
	private const PROP_ACCESS_TOKEN = 'OIDC_Access_Token';

	/**
	 * Cached information taken from discovery URI
	 */
	private static $discovery_info = [];

	public function authorize(string $name): void
	{
		Logging::$debug_enabled = true;

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

		$config = $idp_config->getIdentityProviderConfig($name);
		$oidc_idp_config = $this->getOIDCIdPConfig($name);
		$this->setClient($name);
		$this->setState($state);

		// Prepare authorization request
		$params = [
			'redirect_uri' => ($config['oidc_redirect_uri'] ?? ''),
			'client_id' => ($config['oidc_client_id'] ?? ''),
			'scope' => ($config['oidc_scope'] ?? ''),
			'state' => $state,
			'response_type' => 'code'
		];

		if ($config['oidc_use_pkce'] == 1) {
			$this->setPKCE($config['oidc_pkce_method']);
			$pkce = $this->getPKCE();
			$params['code_challenge'] = $pkce['code_challenge'];
			$params['code_challenge_method'] = $config['oidc_pkce_method'];
		}

		$query = http_build_query($params);
		$url = $oidc_idp_config['authorization_endpoint'] ?? '';
		$url .= '?' . $query;
		$this->Response->redirect($url);
	}

	public function acquireToken(string $code)
	{
		Logging::$debug_enabled = true;

		$name = $this->getClient();
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
			$pkce = $this->getPKCE();
			$params['code_verifier'] = $pkce['code_verifier'];
			$this->removePKCE();
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
				$data['access_token']
			);
		}
	}

	/**
	 * Log in user with ID token.
	 *
	 * @param string $id_token ID token
	 * @param string $access_token access token
	 */
	private function loginUser(string $id_token, string $access_token)
	{
		// Get decoded ID token properties
		$id_token_dec = JWT::decodeToken($id_token);

		// Get IDP config name
		$name = $this->getClient();

		// User attributes
		$attrs = $this->getUserAttributes($id_token_dec, $access_token);

		$user_id = $attrs['username'];
		if (!$user_id) {
			// user not found
			return;
		}
		$sess = $this->getApplication()->getSession();
		$org_id = $sess->itemAt('login_org');
		$org_user = WebUserConfig::getOrgUser($org_id, $user_id);

		$sid = $id_token_dec['body']['sid'] ?? null;
		$users = $this->getModule('users');
		$success = $users->switchUser($org_user, $sid);
		if ($success) {
			$this->setIDToken($id_token);
			$this->setAccessToken($access_token);
			$this->setClient($name);
			$this->syncAttributes($attrs);
			$this->getService()->getRequestedPage()->goToDefaultPage();
		}
	}

	/**
	 * Synchronize user attributes.
	 *
	 * @param array $attrs attributes to sync
	 */
	private function syncAttributes(array $attrs): void
	{
		$name = $this->getClient();
		$idp_config = $this->getModule('idp_config');
		$idp = $idp_config->getIdentityProviderConfig($name);
		$sync = false;
		if ($idp['oidc_attr_sync_policy'] === IdentityProviderConfig::ATTR_SYNC_POLICY_EACH_LOGIN) {
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
		$name = $this->getClient();
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);

		$attrs = [];
		if ($config['oidc_user_attr_source'] === IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_ID_TOKEN) {
			// Get user information from ID token
			$attrs = $this->getUserAttributesFromIDToken($id_token_dec);
		} elseif ($config['oidc_user_attr_source'] === IdentityProviderConfig::OIDC_USER_ATTR_SOURCE_USERINFO_ENDPOINT) {
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
		$id_token = $this->getIDToken();
		if (!$id_token) {
			// no token, no logout
			return;
		}
		$name = $this->getClient();
		$oidc_idp_config = $this->getOIDCIdPConfig($name);
		if (!isset($oidc_idp_config['end_session_endpoint'])) {
			// no logout url endpoint defined
			return;
		}

		$logout_url = $oidc_idp_config['end_session_endpoint'];
		$query = [
			'id_token_hint' => $id_token
		];
		$logout_url .=  (strpos($logout_url, '?') === false) ? '?' : '&';
		$logout_url .= http_build_query($query);

		// Logout request
		HTTPClient::get($logout_url);

		// Remove token (no longer needed)
		$this->removeIDToken();
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
		$name = $this->getClient();
		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);

		// Get decoded ID token properties
		$id_token_dec = JWT::decodeToken($id_token);

		if ($config['oidc_use_discovery_endpoint'] == 1 || $config['oidc_validate_sig'] == 1) {
			// Verify token signature

			// Get public key to verify signature
			$pubkey = $this->getVerifyKey($id_token_dec);

			// Validate key
			if (count($pubkey) ===  0) {
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
		$this->setClient($name);

		// Get decoded logout token properties
		$logout_token_dec = JWT::decodeToken($logout_token);

		if ($config['oidc_use_discovery_endpoint'] == 1 || $config['oidc_validate_sig'] == 1) {
			// Verify token signature

			// Get public key to verify signature
			$pubkey = $this->getVerifyKey($logout_token_dec);

			// Validate key
			if (count($pubkey) ===  0) {
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
	 * Get verification public key from JWKS keys or from user provided key.
	 *
	 * @param string $id_token_dec decoded ID token properties
	 * @return array key properties or empty array if key not found.
	 */
	private function getVerifyKey(array $id_token_dec): array
	{
		$key_id = $id_token_dec['header']['kid'] ?? '';
		$name = $this->getClient();
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
		$pubkey  = $crypto_keys->getPublicKeyPEMFromModulusExponent(
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
	 * @param string $pubkey decoded public key in JWT form
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
	 * @return boolean true on success, otherwise false
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
		$name = $this->getClient();
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
		$name = $this->getClient();
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
		$emsg .=  ' Please check Bacularis logs or contact the administrator.';
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
			$oidc_idp_config['keys'] = $this->getJWKSKeys($jwks_uri);
		} else {
			// Get user-defined properties
			for ($i = 0; $i < count($idp_params); $i++) {
				$oidc_idp_config[$idp_params[$i]] = $config["oidc_{$idp_params[$i]}"] ?? '';
			}
			if (key_exists('oidc_use_jwks_endpoint', $config) && $config['oidc_use_jwks_endpoint'] == 1) {
				// Get JWKS keys from user-defined JWKS endpoint
				$oidc_idp_config['keys'] = $this->getJWKSKeys($config['oidc_jwks_uri']);
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
	 * @param string OIDC config name
	 * @return array remote discovery URL configuration
	 */
	private function getDiscoveryInfo(string $name): array
	{
		if (!self::$discovery_info) {
			$idp_config = $this->getModule('idp_config');
			$config = $idp_config->getIdentityProviderConfig($name);

			$discovery_url = $config['oidc_discovery_endpoint'] ?? '';
			if ($discovery_url) {
				$result = HTTPClient::get($discovery_url);
				if ($result['error'] === 0) {
					self::$discovery_info = json_decode($result['output'], true);
				}
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
		$keys = [];
		$result = HTTPClient::get($jwks_uri);
		if ($result['error'] === 0) {
			$jwks = json_decode($result['output'], true);
			$keys = $jwks['keys'] ?? [];
		}
		return $keys;
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
	 * @param string ID token
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
	 * @param string access token
	 */
	public function setAccessToken(string $id_token): void
	{
		$sess = $this->getSession();
		$sess->add(self::PROP_ACCESS_TOKEN, $id_token);
	}

	/**
	 * Remove OAuth2 authorization access token.
	 */
	public function removeAccessToken(): void
	{
		$sess = $this->getSession();
		$sess->remove(self::PROP_ACCESS_TOKEN);
	}
}
