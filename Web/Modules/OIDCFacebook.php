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

/**
 * The is module to Facebook social login.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class OIDCFacebook extends OIDC
{
	public const CFG_NAME_PREFIX = 'oidc_facebook_';

	public const DEF_AUTHORIZATION_ENDPOINT = 'https://www.facebook.com/v23.0/dialog/oauth';
	public const DEF_TOKEN_ENDPOINT = 'https://graph.facebook.com/v23.0/oauth/access_token';
	public const DEF_USE_JWKS_ENDPOINT = '1';
	public const DEF_JWKS_ENDPOINT = 'https://www.facebook.com/.well-known/oauth/openid/jwks/';
	public const DEF_ISSUER = 'https://www.facebook.com';
	public const DEF_SCOPE = 'openid email';
	public const DEF_USER_ATTR = 'email';
	public const DEF_LONG_NAME_ATTR = 'name';
	public const DEF_EMAIL_ATTR = 'email';
	public const DEF_DESC_ATTR = '';

	private static $params = [
	];

	public function authorize(string $name, $extra_params = []): void
	{
		$extra_params = $this->getParams(
			self::CFG_NAME_PREFIX,
			$name,
			self::$params
		);
		parent::authorize($name, $extra_params);
	}
}
