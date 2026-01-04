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

use Bacularis\Common\Modules\Logging;
use Bacularis\Web\Modules\BaculumWebPage;

/**
 * OpenID Connect logout function.
 * This is backchannel host-to-host logout type.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class OIDCLogout extends BaculumWebPage
{
	public function onInit($param)
	{
		parent::onInit($param);
		$this->logout();
	}

	private function logout()
	{
		$org = $this->Request->itemAt('org');
		$logout_token = $_POST['logout_token'] ?? '';
		if ($logout_token) {
			$oidc = $this->getModule('oidc');
			$oidc->idpLogoutUser($logout_token, $org);
		}
	}
}
