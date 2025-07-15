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
use Bacularis\Web\Modules\OIDCGoogle;

/**
 * Social login with Google.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class IdentityProviderOIDCGoogle extends Portlets implements IIdentityProviderForm
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
		$this->IdPOIDCGoogleRedirectUri->Text = $config['oidc_redirect_uri'] ?? '';
		$this->IdPOIDCGoogleClientID->Text = $config['oidc_client_id'] ?? '';
		$this->IdPOIDCGoogleClientSecret->Text = $config['oidc_client_secret'] ?? '';
		$this->IdPOIDCGooglePrompt->Text = $config['oidc_prompt'] ?? '';
		$this->IdPOIDCGoogleRefreshToken->Checked = ($config[OIDCGoogle::CFG_NAME_PREFIX . 'access_type'] == 'offline');
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
		$config['oidc_discovery_endpoint'] = OIDCGoogle::DEF_DISCOVERY_INFO_URI;
		$config['oidc_redirect_uri'] = $this->IdPOIDCGoogleRedirectUri->Text;
		$config['oidc_client_id'] = $this->IdPOIDCGoogleClientID->Text;
		$config['oidc_client_secret'] = $this->IdPOIDCGoogleClientSecret->Text;
		$config['oidc_prompt'] = $this->IdPOIDCGooglePrompt->Text;
		$config['oidc_user_attr'] = OIDCGoogle::DEF_USER_ATTR;
		$config['oidc_long_name_attr'] = OIDCGoogle::DEF_LONG_NAME_ATTR;
		$config['oidc_email_attr'] = OIDCGoogle::DEF_EMAIL_ATTR;
		$config['oidc_desc_attr'] = OIDCGoogle::DEF_DESC_ATTR;
		$config[OIDCGoogle::CFG_NAME_PREFIX . 'access_type'] = ($this->IdPOIDCGoogleRefreshToken->Checked ? 'offline' : 'online');
		return $config;
	}
}
