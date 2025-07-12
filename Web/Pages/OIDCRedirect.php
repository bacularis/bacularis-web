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

use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * OAuth2 redirection callback page for OpenID Connect.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class OIDCRedirect extends BaculumWebPage
{
	public function onInit($param)
	{
		parent::onInit($param);
		Logging::$debug_enabled = true;

		$this->setAccessControl();
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->acquireToken();
	}

	private function setAccessControl()
	{
		$oidc = $this->getModule('oidc');
		$name = $oidc->getClient();
		$org = $this->Request->itemAt('org');
		if ($org !== $name) {
			$emsg = "Invalid organization name. Required: {$org}, Current: {$name}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$oidc->reportError('Invalid organization name.');
			return;
		}

		$idp_config = $this->getModule('idp_config');
		$config = $idp_config->getIdentityProviderConfig($name);
		$origin = $config['oidc_redirect_uri'] ?? '';
		if ($origin) {
			$header = sprintf(
				'Access-Control-Allow-Origin: %s',
				$origin
			);
			$this->Response->appendHeader($header);
		}
	}

	private function acquireToken()
	{
		$code = $_GET['code'] ?? '';
		$state = $_GET['state'] ?? '';
		$oidc = $this->getModule('oidc');
		$st = $oidc->getState();
		$oidc->removeState();
		if ($st !== $state) {
			$emsg = "Invalid OIDC state. Required: {$st}, Current: {$state}.";
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$oidc->reportError('Invalid OIDC state.');
			return;
		}
		if (isset($_GET['error'])) {
			$emsg = "Error while authorization code request. Error: {$_GET['error']}";
			if (isset($_GET['error_description'])) {
				$emsg .= ", Description: {$_GET['error_description']}";
			}
			Logging::log(
				Logging::CATEGORY_SECURITY,
				$emsg
			);
			$oidc->reportError($emsg);
			return;
		}
		$oidc->acquireToken($code);
	}
}
