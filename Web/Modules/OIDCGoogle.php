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

/**
 * The is module to Google social login.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class OIDCGoogle extends OIDC
{
	public const CFG_NAME_PREFIX = 'oidc_google_';

	public const DEF_DISCOVERY_INFO_URI = 'https://accounts.google.com/.well-known/openid-configuration';
	public const DEF_USER_ATTR = 'email';
	public const DEF_LONG_NAME_ATTR = 'name';
	public const DEF_EMAIL_ATTR = 'email';
	public const DEF_DESC_ATTR = '';
	public const DEF_SCOPE = 'openid email';

	private static $params = [
		'access_type'
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
