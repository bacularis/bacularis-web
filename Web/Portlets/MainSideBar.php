<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2020 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

namespace Bacularis\Web\Portlets;

/**
 * Main side-bar control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class MainSideBar extends Portlets
{
	/**
	 * Current user organization details.
	 */
	public $organization = [];

	public $user_exists = false;

	/**
	 * Is API configured.
	 */
	public $is_api = false;

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getPage()->IsCallback || $this->getPage()->IsPostBack) {
			return;
		}
		$api_config = $this->getModule('api_config')->getConfig();
		$this->is_api = count($api_config) > 0;
		$org_id = $this->User->getOrganization();
		$org_config = $this->getModule('org_config');
		$this->organization = $org_config->getOrganizationConfig($org_id);
		$user_config = $this->getModule('user_config');
		$user_id = $this->User->getUsername();
		$this->user_exists = $user_config->userExists($org_id, $user_id);

	}

	public function logout($sender, $param)
	{
		// Logout SSO (if any)
		$oidc = $this->getModule('oidc');
		$oidc->rpLogoutUser();

		// do logout
		$users = $this->getModule('users');
		$users->logout($this->Application);
	}
}
