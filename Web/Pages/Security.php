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
 * Copyright (C) 2013-2021 Kern Sibbald
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

use Bacularis\Web\Modules\BaculumWebPage;

/**
 * Security page (auth methods, users, roles...).
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class Security extends BaculumWebPage
{
	public function postSaveOrganization($sender, $param)
	{
		// Refresh organization list in general form
		$this->AuthGeneral->initDefAccessForm();

		// refresh organization list
		$this->Users->initUserWindow();

		// refresh IdP list
		$this->AuthIdentityProviders->setIdPList($sender, $param);
	}

	public function postRemoveOrganization($sender, $param)
	{
		// Refresh organization list in general form
		$this->AuthGeneral->initDefAccessForm();

		// refresh organization list
		$this->Users->initUserWindow();

		// refresh IdP list
		$this->AuthIdentityProviders->setIdPList($sender, $param);
	}

	public function postImportUsers($sender, $param)
	{
		// refresh user list
		$this->Users->setUserList($sender, $param);

		// refresh role list
		$this->Roles->setRoleList($sender, $param);
	}

	public function postSaveIdP($sender, $param)
	{
		// refresh organization list
		$this->Organizations->setOrganizationList($sender, $param);
	}

	public function postRemoveIdP($sender, $param)
	{
		// so far nothing to do
	}

	public function postSaveUser($sender, $param)
	{
		// refresh role list
		$this->Roles->setRoleList($sender, $param);

		// refresh organization list
		$this->Organizations->setOrganizationList($sender, $param);
	}

	public function postRemoveUser($sender, $param)
	{
		// refresh role list
		$this->Roles->setRoleList($sender, $param);

		// refresh organization list
		$this->Organizations->setOrganizationList($sender, $param);
	}

	public function postSaveRole($sender, $param)
	{
		// reload user window
		$this->Users->initUserWindow();
	}

	public function postRemoveRole($sender, $param)
	{
		// reload user window
		$this->Users->initUserWindow();
	}

	public function postSaveConsole($sender, $param)
	{
		// reload console list in basic user settings
		$this->BasicUsers->loadAPIBasicUserConsole($sender, $param);

		// reload console list in oauth2 client settings
		$this->OAuth2Clients->loadOAuth2ClientConsole($sender, $param);
	}

	public function postRemoveConsole($sender, $param)
	{
		// reload console list in basic user settings
		$this->BasicUsers->loadAPIBasicUserConsole($sender, $param);

		// reload console list in oauth2 client settings
		$this->OAuth2Clients->loadOAuth2ClientConsole($sender, $param);
	}

	public function postSaveBasicUser($sender, $param)
	{
		// reload basic user list in API host window
		$this->APIHosts->loadAPIBasicUsers($sender, $param);
	}

	public function postRemoveBasicUser($sender, $param)
	{
		// reload basic user list in API host window
		$this->APIHosts->loadAPIBasicUsers($sender, $param);
	}

	public function postSaveOAuth2Client($sender, $param)
	{
		// reload basic user list in API host window
		$this->APIHosts->loadAPIOAuth2Clients($sender, $param);
	}

	public function postRemoveOAuth2Client($sender, $param)
	{
		// reload basic user list in API host window
		$this->APIHosts->loadAPIOAuth2Clients($sender, $param);
	}

	public function postSaveAPIHost($sender, $param)
	{
		// reload user window
		$this->Users->initUserWindow();

		// reload API host group list
		$this->APIHostGroups->setAPIHostGroupList($sender, $param);

		// reload API host group window
		$this->APIHostGroups->initAPIHostGroupWindow();
	}

	public function postRemoveAPIHost($sender, $param)
	{
		// reload user window
		$this->Users->initUserWindow();

		// reload API host group list
		$this->APIHostGroups->setAPIHostGroupList($sender, $param);

		// reload API host group window
		$this->APIHostGroups->initAPIHostGroupWindow();
	}

	public function postSaveAPIHostGroup($sender, $param)
	{
		// reload user window
		$this->Users->initUserWindow();

		// reload API host window
		$this->APIHosts->initAPIHostWindow();
	}

	public function postRemoveAPIHostGroup($sender, $param)
	{
		// reload user window
		$this->Users->initUserWindow();

		// reload API host window
		$this->APIHosts->initAPIHostWindow();
	}
}
