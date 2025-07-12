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

use Bacularis\Common\Modules\AuditLog;

/**
 * OAuth2 client list module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class OAuth2Clients extends Security
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load OAuth2 client list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setOAuth2ClientList($sender, $param)
	{
		$oauth2_clients = $this->getModule('api')->get(['oauth2', 'clients']);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oOAuth2Clients.load_oauth2_client_list_cb', [
			$oauth2_clients->output,
			$oauth2_clients->error
		]);
	}

	public function loadOAuth2ClientConsole($sender, $param)
	{
		$cons = $this->getModule('api')->get(['config', 'dir', 'Console']);
		$console = ['' => ''];
		if ($cons->error == 0) {
			for ($i = 0; $i < count($cons->output); $i++) {
				$console[$cons->output[$i]->Console->Name] = $cons->output[$i]->Console->Name;
			}
		}
		$this->OAuth2ClientConsole->DataSource = $console;
		$this->OAuth2ClientConsole->dataBind();
	}

	/**
	 * Load data in OAuth2 client modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadOAuth2ClientWindow($sender, $param)
	{
		$client_id = $param->getCallbackParameter();
		if (!empty($client_id)) {
			$oauth2_cfg = $this->getModule('api')->get(['oauth2', 'clients', $client_id]);
			if ($oauth2_cfg->error === 0 && is_object($oauth2_cfg->output)) {
				// It is done only for existing OAuth2 client accounts
				$this->OAuth2ClientClientId->Text = $oauth2_cfg->output->client_id;
				$this->OAuth2ClientClientSecret->Text = $oauth2_cfg->output->client_secret;
				$this->OAuth2ClientRedirectURI->Text = $oauth2_cfg->output->redirect_uri;
				$this->OAuth2ClientScope->Text = $oauth2_cfg->output->scope;
				$this->OAuth2ClientBconsoleCfgPath->Text = $oauth2_cfg->output->bconsole_cfg_path;
				$this->OAuth2ClientName->Text = $oauth2_cfg->output->name;
			}
		}
		$this->loadOAuth2ClientConsole(null, null);

		$dirs = $this->getModule('api')->get(['config', 'bcons', 'Director']);
		$dir_names = [];
		if ($dirs->error == 0) {
			for ($i = 0; $i < count($dirs->output); $i++) {
				$dir_names[$dirs->output[$i]->Director->Name] = $dirs->output[$i]->Director->Name;
			}
		}
		$this->OAuth2ClientDirector->DataSource = $dir_names;
		$this->OAuth2ClientDirector->dataBind();
	}

	/**
	 * Save OAuth2 client.
	 * It works both for new OAuth2 client and for edited OAuth2 client.
	 * Saves values from modal popup.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveOAuth2Client($sender, $param)
	{
		$client_id = $this->OAuth2ClientClientId->Text;
		$cfg = [];
		$cfg['client_id'] = $client_id;
		$cfg['client_secret'] = $this->OAuth2ClientClientSecret->Text;
		$cfg['redirect_uri'] = $this->OAuth2ClientRedirectURI->Text;
		$cfg['scope'] = $this->OAuth2ClientScope->Text;
		$cfg['bconsole_cfg_path'] = $this->OAuth2ClientBconsoleCfgPath->Text;
		if ($this->OAuth2ClientBconsoleCreate->Checked) {
			$cfg['console'] = $this->OAuth2ClientConsole->SelectedValue;
			$cfg['director'] = $this->OAuth2ClientDirector->SelectedValue;
		}
		$cfg['name'] = $this->OAuth2ClientName->Text;

		$win_type = $this->OAuth2ClientWindowType->Value;
		$result = (object) ['error' => -1];
		if ($win_type === self::TYPE_ADD_WINDOW) {
			$result = $this->getModule('api')->create(['oauth2', 'clients', $client_id], $cfg);
		} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
			$result = $this->getModule('api')->set(['oauth2', 'clients', $client_id], $cfg);
		}

		if ($result->error === 0) {
			// Refresh OAuth2 client list
			$this->setOAuth2ClientList(null, null);

			$amsg = '';
			if ($win_type === self::TYPE_ADD_WINDOW) {
				$amsg = "Create API OAuth2 client. ClientId: $client_id";
			} elseif ($win_type === self::TYPE_EDIT_WINDOW) {
				$amsg = "Save API OAuth2 client. ClientId: $client_id";
			}
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$amsg
			);
		}
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oOAuth2Clients.save_oauth2_client_cb', [
			$result
		]);

		$this->onSaveOAuth2Client(null);
	}

	/**
	 * On save OAuth2 client event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveOAuth2Client($param)
	{
		$this->raiseEvent('OnSaveOAuth2Client', $this, $param);
	}

	/**
	 * Remove OAuth2 client action.
	 * Here is possible to remove one OAuth2 client or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeOAuth2Clients($sender, $param)
	{
		$client_ids = explode('|', $param->getCallbackParameter());
		for ($i = 0; $i < count($client_ids); $i++) {
			$result = $this->getModule('api')->remove(['oauth2', 'clients', $client_ids[$i]]);
			if ($result->error !== 0) {
				break;
			}
		}

		if (count($client_ids) > 0) {
			// Refresh OAuth2 client list
			$this->setOAuth2ClientList(null, null);

			for ($i = 0; $i < count($client_ids); $i++) {
				$this->getModule('audit')->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove API OAuth2 client. ClientId: {$client_ids[$i]}"
				);
			}
		}
		$this->onRemoveOAuth2Client(null);
	}

	/**
	 * On remove OAuth2 client event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveOAuth2Client($param)
	{
		$this->raiseEvent('OnRemoveOAuth2Client', $this, $param);
	}
}
