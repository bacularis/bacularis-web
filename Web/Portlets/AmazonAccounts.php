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

namespace Bacularis\Web\Portlets;

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Cloud\Amazon\Region as AmazonRegion;
use Bacularis\Common\Modules\Cloud\Amazon\Account as AmazonAccount;
use Bacularis\Common\Modules\Errors\CloudAmazonError;
use Prado\Prado;

/**
 * Amazon account settings control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class AmazonAccounts extends AmazonBase
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	public function onInit($param)
	{
		parent::onInit($param);
		if ($this->getPage()->IsPostBack || $this->getPage()->IsCallBack) {
			return;
		}
		$this->setRegionList();
	}

	public function initialize()
	{
		$this->setAccountList(null, null);
		$this->setRegionList();
	}

	/**
	 * Set and load Amazon account list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setAccountList($sender, $param)
	{
		$accounts = [];
		$api = $this->getModule('api');
		$host = $this->getFDAPIHost();
		$result = $api->get(
			['cloud', 'amazon', 'accounts'],
			$host
		);
		if ($result->error == 0) {
			$accounts = $result->output;
		}

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction('oAmazonAccounts.load_account_list_cb', [
			$accounts
		]);
	}

	/**
	 * Prepare AWS region list.
	 */
	private function setRegionList()
	{
		$this->AmazonAccountRegion->DataSource = AmazonRegion::getRegions();
		$this->AmazonAccountRegion->dataBind();
	}

	/**
	 * Load data in amazon account modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadAccountWindow($sender, $param)
	{
		$name = $param->getCallbackParameter() ?? '';

		if (!empty($name)) {
			$api = $this->getModule('api');
			$host = $this->getFDAPIHost();
			$result = $api->get(
				['cloud', 'amazon', 'accounts', $name],
				$host
			);
			if ($result->error == 0) {
				$account = (array) $result->output;
				$is_static_creds = ($account['access_method'] == AmazonAccount::ACCOUNT_ACCESS_METHOD_STATIC_CREDENTIALS);
				$is_assume_role = ($account['access_method'] == AmazonAccount::ACCOUNT_ACCESS_METHOD_ASSUME_ROLE);
				$this->AmazonAccountName->Text = $name;
				$this->AmazonAccountDescription->Text = $account['description'];
				$this->AmazonAccountAccessKey->Text = $account['access_key'] ?? '';
				$this->AmazonAccountSecretKey->Text = $account['secret_key'] ?? '';
				$this->AmazonAccountRoleARN->Text = $account['role_arn'] ?? '';
				$this->AmazonAccountAssumeRoleAccessKey->Text = $account['role_access_key'] ?? '';
				$this->AmazonAccountAssumeRoleSecretKey->Text = $account['role_secret_key'] ?? '';
				$this->AmazonAccountAssumeRoleService->SelectedValue = $account['role_service'] ?? '';
				$this->AmazonAccountRegion->SelectedValue = $account['region'];
				$this->AmazonAccountEnabled->Checked = ($account['enabled'] == 1);
				$cb = $this->getPage()->getCallbackClient();
				if ($is_static_creds) {
					$cb->jQuery($this->AmazonAccountAccessMethodStaticCredentials, 'click');
					$is_ar_role = ($account['role_access_type'] == AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_ROLE);
					$is_ar_service = ($account['role_access_type'] == AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_SERVICE);
					if ($is_ar_role) {
						$cb->jQuery($this->AmazonAccountAccessMethodStaticCredentialsOutAWS, 'click');
					} elseif ($is_ar_service) {
						$cb->jQuery($this->AmazonAccountAccessMethodStaticCredentialsInAWS, 'click');
					}
				} elseif ($is_assume_role) {
					$cb->jQuery($this->AmazonAccountAccessMethodAssumeRole, 'click');
					$is_ar_role = ($account['role_access_type'] == AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_ROLE);
					$is_ar_service = ($account['role_access_type'] == AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_SERVICE);
					if ($is_ar_role) {
						$cb->jQuery($this->AmazonAccountAccessMethodAssumeRoleOutAWS, 'click');
					} elseif ($is_ar_service) {
						$cb->jQuery($this->AmazonAccountAccessMethodAssumeRoleInAWS, 'click');
					}
				}
			}
		}
	}

	/**
	 * Save Amazona account.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveAccount($sender, $param)
	{
		$account_name = trim($this->AmazonAccountName->Text);
		if (empty($account_name)) {
			return;
		}

		$cfg_account = [];
		$cfg_account['name'] = $account_name;
		$cfg_account['description'] = $this->AmazonAccountDescription->Text;
		if ($this->AmazonAccountAccessMethodStaticCredentials->Checked) {
			$cfg_account['access_method'] = AmazonAccount::ACCOUNT_ACCESS_METHOD_STATIC_CREDENTIALS;
			if ($this->AmazonAccountAccessMethodStaticCredentialsOutAWS->Checked) {
				$cfg_account['role_access_type'] = AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_ROLE;
				$cfg_account['access_key'] = $this->AmazonAccountAccessKey->Text;
				$cfg_account['secret_key'] = $this->AmazonAccountSecretKey->Text;
			} else {
				$cfg_account['role_access_type'] = AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_SERVICE;
			}
		} elseif ($this->AmazonAccountAccessMethodAssumeRole->Checked) {
			$cfg_account['access_method'] = AmazonAccount::ACCOUNT_ACCESS_METHOD_ASSUME_ROLE;
			$cfg_account['role_arn'] = $this->AmazonAccountRoleARN->Text;
			if ($this->AmazonAccountAccessMethodAssumeRoleOutAWS->Checked) {
				$cfg_account['role_access_type'] = AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_ROLE;
				$cfg_account['role_access_key'] = $this->AmazonAccountAssumeRoleAccessKey->Text;
				$cfg_account['role_secret_key'] = $this->AmazonAccountAssumeRoleSecretKey->Text;
			} elseif ($this->AmazonAccountAccessMethodAssumeRoleInAWS->Checked) {
				$cfg_account['role_access_type'] = AmazonAccount::ACCOUNT_CREDENTIAL_SOURCE_SERVICE;
				$cfg_account['role_service'] = $this->AmazonAccountAssumeRoleService->SelectedValue;
			}
		}
		$cfg_account['region'] = $this->AmazonAccountRegion->SelectedValue;
		$cfg_account['enabled'] = $this->AmazonAccountEnabled->Checked ? '1' : '0';

		$account_win_type = $this->AmazonAccountWindowType->Value;
		$api = $this->getModule('api');
		$host = $this->getFDAPIHost();
		$result = (object) ['error' => -1, 'output' => ''];
		if ($account_win_type === self::TYPE_ADD_WINDOW) {
			$result = $api->create(
				['cloud', 'amazon', 'accounts', $account_name],
				$cfg_account,
				$host
			);
		} elseif ($account_win_type === self::TYPE_EDIT_WINDOW) {
			$result = $api->set(
				['cloud', 'amazon', 'accounts', $account_name],
				$cfg_account,
				$host
			);
		}


		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->AmazonAccountWindowError);
		if ($account_win_type === self::TYPE_ADD_WINDOW) {
			if ($result->error == CloudAmazonError::ERROR_ACCOUNT_ALREADY_EXISTS) {
				$emsg = Prado::localize('Amazon account \'%s\' already exists. Please type different account name.');
				$emsg = sprintf($emsg, $account_name);
				$cb->update($this->AmazonAccountWindowError, $emsg);
				$cb->show($this->AmazonAccountWindowError);
				return;
			}
		}
		$cb->hide('amazon_account_window');

		$message = '';
		$success = ($result->error == CloudAmazonError::ERROR_NO_ERRORS);
		if ($account_win_type === self::TYPE_ADD_WINDOW) {
			if ($success) {
				$message = "Create Amazon AWS account. Name: {$account_name}";
			} else {
				$message = 'Error while creating Amazon AWS account. ';
				$message .= "Name: {$account_name}, Error: {$result->error}, Message: {$result->output}";
			}
		} elseif ($account_win_type === self::TYPE_EDIT_WINDOW) {
			if ($success) {
				$message = "Save Amazon AWS account. Name: {$account_name}";
			} else {
				$message = 'Error while saving Amazon AWS account. ';
				$message .= "Name: {$account_name}, Error: {$result->error}, Message: {$result->output}";
			}
		}

		if ($success) {
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				$message
			);
		} else {
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				$message
			);
		}

		$this->onSaveAccount(null);

		// Refresh account list
		$this->setAccountList($sender, $param);
	}

	/**
	 * On save Amazon account event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveAccount($param)
	{
		$this->raiseEvent('OnSaveAccount', $this, $param);
	}

	/**
	 * Remove Amazon account action.
	 * Here is possible to remove one account or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeAccounts($sender, $param)
	{
		$accounts = $param->getCallbackParameter();
		$account_list = json_decode(json_encode($accounts), true);
		$host = $this->getFDAPIHost();
		$api = $this->getModule('api');
		$audit = $this->getModule('audit');

		for ($i = 0; $i < count($account_list); $i++) {
			$result = $api->remove(
				['cloud', 'amazon', 'accounts', $account_list[$i]['name']],
				$host
			);
			if ($result->error == 0) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove Amazon AWS account. Name: {$account_list[$i]['name']}"
				);
			} else {
				$audit->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_APPLICATION,
					"Error while removing Amazon AWS account. Name: {$account_list[$i]['name']}"
				);
				break;
			}
		}

		$this->onRemoveAccount(null);

		// Refresh account list
		$this->setAccountList($sender, $param);
	}

	/**
	 * On remove Account event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveAccount($param)
	{
		$this->raiseEvent('OnRemoveAccount', $this, $param);
	}
}
