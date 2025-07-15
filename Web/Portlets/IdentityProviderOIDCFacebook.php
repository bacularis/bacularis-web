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

namespace Bacularis\Web\Portlets;

use Bacularis\Web\Modules\IdentityProviderConfig;
use Bacularis\Web\Modules\IIdentityProviderForm;
use Bacularis\Web\Modules\OIDC;
use Bacularis\Web\Modules\OIDCFacebook;

/**
 * Social login with Facebook.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class IdentityProviderOIDCFacebook extends Portlets implements IIdentityProviderForm
{
	private $idp_type;

	public function setIdPType($control)
	{
		$this->idp_type = $control;
	}

	public function getIdPType()
	{
		return $this->idp_type;
	}

	/**
	 * Load settings from configuration to form.
	 *
	 * @param array $config identity provider config
	 */
	public function loadSettings(array $config): void
	{
		$this->IdPOIDCFacebookRedirectUri->Text = $config['oidc_redirect_uri'] ?? '';
		$this->IdPOIDCFacebookClientID->Text = $config['oidc_client_id'] ?? '';
		$this->IdPOIDCFacebookClientSecret->Text = $config['oidc_client_secret'] ?? '';
	}

	/**
	 * Load default settings to form.
	 */
	public function loadDefaultSettings(): void
	{
		// so far nothing to do
	}

	/**
	 * Get settings from form to save in config.
	 *
	 * @return array configuration to save
	 */
	public function getSettings(): array
	{
		$config = OIDC::getDefaultOptions();
		$config['oidc_use_discovery_endpoint'] = '0';
		$config['oidc_authorization_endpoint'] = OIDCFacebook::DEF_AUTHORIZATION_ENDPOINT;
		$config['oidc_token_endpoint'] = OIDCFacebook::DEF_TOKEN_ENDPOINT;
		$config['oidc_redirect_uri'] = $this->IdPOIDCFacebookRedirectUri->Text;
		$config['oidc_client_id'] = $this->IdPOIDCFacebookClientID->Text;
		$config['oidc_client_secret'] = $this->IdPOIDCFacebookClientSecret->Text;
		$config['oidc_scope'] = OIDCFacebook::DEF_SCOPE;
		$config['oidc_issuer'] = OIDCFacebook::DEF_ISSUER;
		$config['oidc_use_jwks_endpoint'] = OIDCFacebook::DEF_USE_JWKS_ENDPOINT;
		$config['oidc_jwks_uri'] = OIDCFacebook::DEF_JWKS_ENDPOINT;
		$config['oidc_user_attr'] = OIDCFacebook::DEF_USER_ATTR;
		$config['oidc_long_name_attr'] = OIDCFacebook::DEF_LONG_NAME_ATTR;
		$config['oidc_email_attr'] = OIDCFacebook::DEF_EMAIL_ATTR;
		$config['oidc_desc_attr'] = OIDCFacebook::DEF_DESC_ATTR;
		return $config;
	}
}
