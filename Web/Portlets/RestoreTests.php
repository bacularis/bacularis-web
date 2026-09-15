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
use Bacularis\Common\Modules\Miscellaneous;
use Bacularis\Common\Modules\RestoreVerification;
use Bacularis\Web\Modules\BaculaConfigAction;
use Bacularis\Web\Modules\RestorePolicyConfig;
use Bacularis\Web\Modules\RestoreTestConfig;
use Bacularis\Web\Modules\TRestoreVerification;
use Bacularis\Web\Modules\WebAccessAction;
use Bacularis\Web\Modules\WebAccessBaculaResource;
use Bacularis\Web\Modules\WebAccessConfig;
use Prado\Prado;
use Prado\Web\UI\ActiveControls\TActiveDropDownList;

/**
 * Verification restore test control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class RestoreTests extends RestoreTestVerification
{
	use TRestoreVerification;

	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load restore test list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setRestoreTestList($sender, $param)
	{
		$rtest_config = $this->getModule('restore_test_config');
		$restore_tests = $rtest_config->getConfig();

		$vals = array_values($restore_tests);
		$this->addRestoreTestRelationInfo($vals);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oRestoreTests.load_restore_test_list_cb',
			[$vals]
		);

		// Refresh job list
		$cb->callClientFunction('oJobList.load_job_list');
	}

	/**
	 * Add restore test relations.
	 *
	 * @param array $vals RestoreTest configuration
	 */
	private function addRestoreTestRelationInfo(&$vals)
	{
		$rpolicy_config = $this->getModule('restore_policy_config');
		for ($i = 0; $i < count($vals); $i++) {
			$vals[$i]['enabled'] = $vals[$i]['enabled'] ?? '0';
			$vals[$i]['source_type'] = $vals[$i]['source_type'] ?? RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB;
			$vals[$i]['source_selection'] = $vals[$i]['source_selection'] ?? RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL;
			$vals[$i]['backup_source'] = $this->getBackupSource($vals[$i]);
			$vals[$i]['restore_policy'] = $vals[$i]['restore_policy'] ?? '';
			$vals[$i]['restore_destination'] = $vals[$i]['restore_destination'] ?? '';
			$vals[$i]['last_result'] = $vals[$i]['last_result'] ?? '';
			$vals[$i]['last_run'] = $vals[$i]['last_run'] ?? '';
			$vals[$i]['next_run'] = $vals[$i]['next_run'] ?? '';
			$vals[$i]['rp_config'] = $rpolicy_config->getRestorePolicyConfig($vals[$i]['restore_policy']);
		}
	}

	/**
	 * Load data in restore test modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadRestoreTestWindow($sender, $param)
	{
		$name = $param->getCallbackParameter() ?? '';
		$rtest_config = $this->getModule('restore_test_config');
		$config = $rtest_config->getRestoreTestConfig($name);
		if (count($config) === 0) {
			$this->setResourceLists();
			return;
		}

		$this->setResourceLists();

		$this->RestoreTestFullName->Text = $name;
		$this->RestoreTestDescription->Text = $config['description'] ?? '';
		$enabled = $config['enabled'] ?? '1';
		$this->RestoreTestEnabled->Checked = ($enabled == '1');

		$source_type = $config['source_type'] ?? RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB;
		$cb = $this->getPage()->getCallbackClient();
		if ($source_type === RestoreTestConfig::SOURCE_TYPE_BACKUP_JOBID) {
			$this->RestoreTestBackupJobSingleJobIdRadio->Checked = true;
			$this->RestoreTestBackupJobRadio->Checked = false;
			$cb->hide('restore_test_window_backup_job_job_name');
			$cb->show('restore_test_window_backup_job_jobid');
		} else {
			$this->RestoreTestBackupJobRadio->Checked = true;
			$this->RestoreTestBackupJobSingleJobIdRadio->Checked = false;
			$cb->show('restore_test_window_backup_job_job_name');
			$cb->hide('restore_test_window_backup_job_jobid');
		}

		$this->RestoreTestBackupJobList->SelectedValue = $config['source_backup_job'] ?? '';
		$this->RestoreTestBackupJobJobId->Text = $config['source_backup_jobid'] ?? '';

		$source_selection = $config['source_selection'] ?? RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL;
		$this->setSourceSelection($source_selection, $cb);

		$date_from = (int) ($config['latest_successful_date_from'] ?? 0);
		$date_to = (int) ($config['latest_successful_date_to'] ?? 0);
		$date_from_str = $date_from > 0 ? date('Y-m-d', $date_from) : '';
		$date_to_str = $date_to > 0 ? date('Y-m-d', $date_to) : '';
		$this->RestoreTestBackupSourceTimeRangeFrom->setDate($date_from_str);
		$this->RestoreTestBackupSourceTimeRangeTo->setDate($date_to_str);

		$job_levels = $config['job_levels'] ?? [];
		if (!is_array($job_levels)) {
			$job_levels = [$job_levels];
		}
		$this->RestoreTestBackupSourceJobLevels->setSelectedValues($job_levels);

		$restore_scope = $config['restore_scope'] ?? RestoreTestConfig::RESTORE_SCOPE_ENTIRE_BACKUP;
		$this->setRestoreScope($restore_scope);

		$this->RestoreTestRestoreDestinationDestination->SelectedValue = $config['restore_destination'] ?? '';
		$this->RestoreTestRestoreJob->SelectedValue = $config['restore_job'] ?? '';
		$this->RestoreTestRestorePolicyPolicy->SelectedValue = $config['restore_policy'] ?? '';
		$this->RestoreTestWebProtocol->SelectedValue = $config['web_protocol'] ?? '';
		$this->RestoreTestWebAddress->Text = $config['web_address'] ?? '';
		$this->RestoreTestWebPort->Text = $config['web_port'] ?? '';
		$this->RestoreTestWebAllowedIPs->Text = $config['web_allowed_ips'] ?? '';
		$this->RestoreTestClient->SelectedValue = $config['test_client'] ?? '';
		$this->RestoreTestFileset->SelectedValue = $config['test_fileset'] ?? '';
		$this->RestoreTestPool->SelectedValue = $config['test_pool'] ?? '';
		$this->RestoreTestStorage->SelectedValue = $config['test_storage'] ?? '';
		$this->RestoreTestMessages->SelectedValue = $config['test_messages'] ?? '';

		$verification_method = $config['verification_method'] ?? RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES;
		$verification_method_js = 'rules';
		if ($verification_method == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES) {
			$this->RestoreTestVerificationMethodRulesRadio->Checked = true;
			$this->RestoreTestVerificationMethodVerifyJobRadio->Checked = false;
			$cb->show('restore_test_window_verification_rule_sets');
			$cb->hide('restore_test_window_verification_verify_job');
		} elseif ($verification_method == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB) {
			$this->RestoreTestVerificationMethodRulesRadio->Checked = false;
			$this->RestoreTestVerificationMethodVerifyJobRadio->Checked = true;
			$verification_method_js = 'verify_job';
			$cb->hide('restore_test_window_verification_rule_sets');
			$cb->show('restore_test_window_verification_verify_job');
		}
		$cb->callClientFunction('oRestoreTests.set_verification_method', [$verification_method_js, false]);

		$verification_rule_sets = $config['verification_rule_sets'] ?? [];
		if (!is_array($verification_rule_sets)) {
			$verification_rule_sets = [$verification_rule_sets];
		}
		$this->RestoreTestVerificationRules->setSelectedValues($verification_rule_sets);
		$this->RestoreTestVerificationVerifyJob->SelectedValue = $config['verification_verify_job'];
	}

	/**
	 * Check backup job by JobId and show job details.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function checkBackupJobId($sender, $param): void
	{
		$jobid = $this->RestoreTestBackupJobJobId->Text;
		$cb = $this->getPage()->getCallbackClient();
		$misc = $this->getModule('misc');

		// Prepare job info container
		$this->clearBackupJobIdInfo();
		$cb->show($this->RestoreTestBackupJobJobIdInfo);

		if (!$misc->isValidInteger($jobid)) {
			$this->showBackupJobIdError();
			return;
		}

		// Get job info by jobid
		$api = $this->getModule('api');
		$result = $api->get(['jobs', (int) $jobid], null, false);
		if ($result->error != 0 || !is_object($result->output)) {
			$this->showBackupJobIdError();
			return;
		}
		$job = $result->output;

		// Display job info properties

		// Job type
		$job_type = $misc->getJobTypeName($job->type ?? '');

		// Job level
		$job_level = $misc->getJobLevelLong($job->level ?? '');
		if (empty($job_level)) {
			$job_level = $job->level ?? '';
		}

		// Job status
		$job_status = $misc->getJobState($job->jobstatus)['value'] ?? '';

		$this->RestoreTestBackupJobJobIdName->Text = Miscellaneous::html_value($job->name ?? '');
		$this->RestoreTestBackupJobJobIdType->Text = Miscellaneous::html_value($job_type);
		$this->RestoreTestBackupJobJobIdStatus->Text = Miscellaneous::html_value($job_status);
		$this->RestoreTestBackupJobJobIdClient->Text = Miscellaneous::html_value($job->client ?? '');
		$this->RestoreTestBackupJobJobIdLevel->Text = Miscellaneous::html_value($job_level);
		$this->RestoreTestBackupJobJobIdStartTime->Text = Miscellaneous::html_value($job->starttime ?? '');

		if (($job->type ?? '') !== 'B') {
			$emsg = Prado::localize('This jobid cannot be used for restore tests.');
			$this->RestoreTestBackupJobJobIdWarning->Text = $emsg;
			$cb->show($this->RestoreTestBackupJobJobIdWarning);
		}
		$cb->slideDown('restore_test_backup_jobid_details');
	}

	/**
	 * Clear backup JobId information fields.
	 */
	private function clearBackupJobIdInfo(): void
	{
		$this->RestoreTestBackupJobJobIdError->Text = '';
		$this->RestoreTestBackupJobJobIdName->Text = '';
		$this->RestoreTestBackupJobJobIdType->Text = '';
		$this->RestoreTestBackupJobJobIdStatus->Text = '';
		$this->RestoreTestBackupJobJobIdClient->Text = '';
		$this->RestoreTestBackupJobJobIdLevel->Text = '';
		$this->RestoreTestBackupJobJobIdStartTime->Text = '';
		$this->RestoreTestBackupJobJobIdWarning->Text = '';

		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->RestoreTestBackupJobJobIdError);
		$cb->hide($this->RestoreTestBackupJobJobIdWarning);
		$cb->hide('restore_test_backup_jobid_details');
	}

	/**
	 * Show backup JobId fetch error.
	 */
	private function showBackupJobIdError(): void
	{
		$emsg = Prado::localize('Unable to fetch jobid information.');
		$this->RestoreTestBackupJobJobIdError->Text = $emsg;
		$cb = $this->getPage()->getCallbackClient();
		$cb->show($this->RestoreTestBackupJobJobIdError);
	}

	/**
	 * Save restore test.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRestoreTest($sender, $param): void
	{
		$rtest_config = $this->getModule('restore_test_config');
		$name = trim($this->RestoreTestFullName->Text);
		$restore_test_exists = $rtest_config->restoreTestConfigExists($name);

		$source_type = $this->getSourceType();
		$source_selection = $this->getSourceSelection();
		$restore_test_win_type = $this->RestoreTestWindowType->Value;
		$rt_config = [];
		if ($restore_test_win_type === self::TYPE_EDIT_WINDOW) {
			$rt_config = $rtest_config->getRestoreTestConfig($name);
		}
		$rt_config['name'] = $name;
		$description = $this->RestoreTestDescription->Text;
		// INI scalar values are stored on a single line.
		$description = str_replace(["\r\n", "\r", "\n"], ' ', $description);
		$rt_config['description'] = $description;
		$rt_config['enabled'] = $this->RestoreTestEnabled->Checked ? '1' : '0';
		$rt_config['source_type'] = $source_type;
		$rt_config['source_backup_job'] = $this->RestoreTestBackupJobList->SelectedValue;
		$rt_config['source_backup_jobid'] = trim($this->RestoreTestBackupJobJobId->Text);
		$rt_config['source_selection'] = $source_selection;
		$rt_config['latest_successful_date_from'] = (string) $this->getTimestampFromDate($this->RestoreTestBackupSourceTimeRangeFrom->Text, false);
		$rt_config['latest_successful_date_to'] = (string) $this->getTimestampFromDate($this->RestoreTestBackupSourceTimeRangeTo->Text, true);
		$rt_config['job_levels'] = $this->getSelectedJobLevels();
		$rt_config['restore_destination'] = $this->RestoreTestRestoreDestinationDestination->SelectedValue;
		$rt_config['restore_job'] = $this->RestoreTestRestoreJob->SelectedValue;
		$rt_config['restore_policy'] = $this->RestoreTestRestorePolicyPolicy->SelectedValue;
		$rt_config['web_protocol'] = $this->RestoreTestWebProtocol->SelectedValue;
		$rt_config['web_address'] = trim($this->RestoreTestWebAddress->Text);
		$rt_config['web_port'] = trim($this->RestoreTestWebPort->Text);
		$rt_config['web_allowed_ips'] = trim($this->RestoreTestWebAllowedIPs->Text);
		$rt_config['test_client'] = $this->RestoreTestClient->Text;
		$rt_config['test_fileset'] = $this->RestoreTestFileset->Text;
		$rt_config['test_pool'] = $this->RestoreTestPool->Text;
		$rt_config['test_storage'] = $this->RestoreTestStorage->Text;
		$rt_config['test_messages'] = $this->RestoreTestMessages->Text;
		$verification_method = $this->getRestoreVerificationMethod();
		if ($verification_method == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB) {
			$rt_config['restore_scope'] = RestoreTestConfig::RESTORE_SCOPE_SINGLE_BACKUP;
		} else {
			$rt_config['restore_scope'] = $this->getRestoreScope();
		}
		$rt_config['verification_method'] = $verification_method;
		$rt_config['verification_rule_sets'] = $this->getSelectedVerificationRules();
		$rt_config['verification_verify_job'] = $this->RestoreTestVerificationVerifyJob->getSelectedValue();

		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->RestoreTestWindowError);
		if ($restore_test_win_type === self::TYPE_ADD_WINDOW && $restore_test_exists) {
			$msg = 'Restore test with name \'%s\' already exists.';
			$emsg = sprintf($msg, $name);
			$this->showError($emsg);
			return;
		}

		// Remove old access tokens
		if ($restore_test_win_type === self::TYPE_EDIT_WINDOW) {
			$this->removeWebAccessTokens($rt_config);
		}

		// Create web access tokens
		// Create token to run restore job by admin job
		$token = $this->createAdminWebAccessToken($rt_config);
		if (!$token) {
			$this->showError('Error while creating web access for restore test.');
			return;
		}
		$rt_config['web_admin_token'] = $token;

		// Create token to run verify job by restore job
		if ($verification_method == RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB) {
			// Create token to run verify job by restore job
			$token = $this->createVerifyWebAccessToken($rt_config);
			if (!$token) {
				$this->showError('Error while creating web access for verify job.');
				return;
			}
			$rt_config['web_verify_token'] = $token;
		}

		if ($source_type == RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB) {
			$rpolicy_config = $this->getModule('restore_policy_config');
			$rp_config = $rpolicy_config->getRestorePolicyConfig($rt_config['restore_policy']);
			$action = '';
			if ($rp_config['run_method'] == RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP) {
				// Create token to run admin job by backup job
				$token = $this->createRunWebAccessToken($rt_config, $rp_config);
				if (!$token) {
					$this->showError('Error while creating web access for running restore test.');
					return;
				}
				$rt_config['web_run_token'] = $token;
				$action = $restore_test_win_type === self::TYPE_ADD_WINDOW ? 'create' : 'update';
			} else {
				$action = 'remove';
			}
			$result = $this->updateBackupJob($rt_config, $action);
			if (!$result) {
				$this->showError('Error while updating backup job configuration.');
				return;
			}
		}

		// Update IP restrictions for web access tokens
		$this->updateWebAllowedIPs($rt_config);

		// Save restore test config
		$result = false;
		if ($restore_test_win_type === self::TYPE_ADD_WINDOW) {
			$result = $rtest_config->setRestoreTestConfig($name, $rt_config);
		} else {
			$result = $rtest_config->updateRestoreTestConfig($name, $rt_config);
		}

		// Prepare result
		if ($result === true) {
			$cb->callClientFunction('oRestoreTests.save_restore_test_cb');
			$action = $restore_test_exists ? 'Save' : 'Create';
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"$action restore test. Name: $name"
			);

			// Create/update admin job
			$action = '';
			$ares = ['state' => false, 'output' => ''];
			if ($restore_test_win_type === self::TYPE_ADD_WINDOW) {
				$ares = $this->createAdminJob($rt_config);
				$action = 'create';
			} elseif ($restore_test_win_type === self::TYPE_EDIT_WINDOW) {
				$ares = $this->updateAdminJob($rt_config);
				$action = 'update';
			}

			if ($ares['state']) {
				$cb->hide('restore_test_window');
			} else {
				if ($action == 'create') {
					// Roll-back action
					$this->removeRestoreTestsInternal([$rt_config]);
				}
				$emsg = sprintf('Error: %s restore test admin job failed. %s', $action, $ares['output']);
				$this->showError($emsg);
			}
		} else {
			$this->showError('Error while saving restore test.');
			return;
		}

		// Update restore test list
		$this->setRestoreTestList($sender, $param);

		$this->onSaveRestoreTest(null);
	}

	/**
	 * On save restore test event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveRestoreTest($param)
	{
		$this->raiseEvent('OnSaveRestoreTest', $this, $param);
	}

	private function showError(string $emsg): void
	{
		$cb = $this->getPage()->getCallbackClient();
		$emsg_html = Miscellaneous::html_value($emsg);
		$cb->update($this->RestoreTestWindowError, $emsg_html);
		$cb->show($this->RestoreTestWindowError);
	}

	/**
	 * Remove all web access tokens related to this restore test.
	 *
	 * @param array $rt_config restore test configuration
	 */
	private function removeWebAccessTokens(array $rt_config): void
	{
		if (isset($rt_config['web_run_token'])) {
			// Remove old web job token
			WebAccessAction::remove($rt_config['web_run_token']);
		}
		if (isset($rt_config['web_admin_token'])) {
			// Remove old web admin token
			WebAccessAction::remove($rt_config['web_admin_token']);
		}
		if (isset($rt_config['web_verify_token'])) {
			// previous token exists, remove it first
			WebAccessAction::remove($rt_config['web_verify_token']);
		}
	}

	/**
	 * Create admin token.
	 * This is token to run restore job.
	 *
	 * @param array $rt_config restore test configuration
	 * @return null|string token value or null on error
	 */
	private function createAdminWebAccessToken(array $rt_config): ?string
	{
		// Main web token to run restore
		$action_params = [
			'restore_test' => $rt_config['name']
		];
		[
			'state' => $state,
			'token' => $token
		] = $this->createWebAccess(
			$this->RestoreTestRestoreJob->SelectedValue,
			WebAccessBaculaResource::ACTION_RUN_RESTORE_TEST_NAME,
			$action_params,
			$rt_config['web_allowed_ips']
		);
		if (!$state) {
			return null;
		}
		return $token;
	}


	/**
	 * Create verify token.
	 * This is token to run verify job.
	 * Used in Native Bacula verification only.
	 *
	 * @param array $rt_config restore test configuration
	 * @return null|string token value or null on error
	 */
	private function createVerifyWebAccessToken(array $rt_config): ?string
	{
		$restore_dest = $this->getModule('restore_destination_config');
		$rd_config = $restore_dest->getRestoreDestinationConfig(
			$rt_config['restore_destination']
		);

		// Token to run Bacula verify job checking
		$action_params = [
			'level' => 'd', // DiskToCatalog
			'client' => $rd_config['restore_client']
		];
		[
			'state' => $state,
			'token' => $token
		] = $this->createWebAccess(
			$this->RestoreTestVerificationVerifyJob->getSelectedValue(),
			WebAccessBaculaResource::ACTION_RUN_NAME,
			$action_params,
			$rt_config['web_allowed_ips']
		);

		if (!$state) {
			return null;
		}
		return $token;
	}

	/**
	 * Create admin job corresponding the restore test.
	 *
	 * @param array $rt_config restore test configuration
	 * @return array output and state with true on success, otherwise false
	 */
	private function createAdminJob(array $rt_config): array
	{
		$resource = [
			'Client' => $this->RestoreTestClient->getSelectedValue(),
			'Fileset' => $this->RestoreTestFileset->getSelectedValue(),
			'Storage' => $this->RestoreTestStorage->getSelectedValue(),
			'Pool' => $this->RestoreTestPool->getSelectedValue(),
			'Messages' => $this->RestoreTestMessages->getSelectedValue()
		];
		$job = $this->getAdminJobConfig($rt_config, $resource);
		$result = BaculaConfigAction::createResource(
			'dir',
			'Job',
			$job['Name'],
			$job
		);
		return ['state' => ($result->error == 0), 'output' => $result->output];
	}

	/**
	 * Update admin job corresponding the restore test.
	 *
	 * @param array $rt_config restore test configuration
	 * @return array output and state with true on success, otherwise false
	 */
	private function updateAdminJob(array $rt_config): array
	{
		$resource = [
			'Client' => $this->RestoreTestClient->getSelectedValue(),
			'Fileset' => $this->RestoreTestFileset->getSelectedValue(),
			'Storage' => $this->RestoreTestStorage->getSelectedValue(),
			'Pool' => $this->RestoreTestPool->getSelectedValue(),
			'Messages' => $this->RestoreTestMessages->getSelectedValue()
		];
		$job = $this->getAdminJobConfig($rt_config, $resource);
		$result = BaculaConfigAction::updateResource(
			'dir',
			'Job',
			$job['Name'],
			$job
		);
		return ['state' => ($result->error == 0), 'output' => $result->output];
	}

	/**
	 * Remove admin job corresponding the restore test.
	 *
	 * @param string $name restore test name
	 * @return bool true on success, otherwise false
	 */
	private function removeAdminJob($name): bool
	{
		$job = $this->getJobConfig($name);
		if (!key_exists('Type', $job) || $job['Type'] != 'Admin') {
			return false;
		}
		$result = BaculaConfigAction::removeResource(
			'dir',
			'Job',
			$name
		);
		return ($result->error == 0);
	}

	/**
	 * Update allowed IP addresses in restore test web access tokens.
	 *
	 * @param array $rt_config restore test configuration
	 */
	private function updateWebAllowedIPs(array $rt_config): void
	{
		$allowed_ips_config = key_exists('web_allowed_ips', $rt_config) ? $rt_config['web_allowed_ips'] : '';
		$allowed_ips = $this->getAllowedIPs($allowed_ips_config);
		$tokens = [
			'web_admin_token',
			'web_verify_token',
			'web_run_token'
		];
		$web_access_config = $this->getModule('web_access_config');
		for ($i = 0; $i < count($tokens); $i++) {
			$token_name = $tokens[$i];
			if (!key_exists($token_name, $rt_config)) {
				continue;
			}
			$token = $rt_config[$token_name];
			$config = $web_access_config->getWebAccessConfig($token);
			if (!$config) {
				continue;
			}

			// Source access methods
			$source_access = WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_NO_RESTRICTION;
			if ($allowed_ips) {
				$source_access = WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_IP_RESTRICTION;
			}
			$config['source_access'] = $source_access;

			$config['source_ips_allowed'] = $allowed_ips;
			$web_access_config->setWebAccessConfig($token, $config);
		}
	}

	/**
	 * Remove restore tests action.
	 * Here is possible to remove one restore test or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeRestoreTests($sender, $param)
	{
		$rm_restore_tests = $param->getCallbackParameter();
		$misc = $this->getModule('misc');
		$rm_restore_tests = $misc->objectToArray($rm_restore_tests);
		$this->removeRestoreTestsInternal($rm_restore_tests);
	}

	/**
	 * Remove restore tests internal action.
	 * Here is possible to remove one restore test or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param array $restore_tests restore test names to remove
	 */
	private function removeRestoreTestsInternal($restore_tests): void
	{
		$rtest_config = $this->getModule('restore_test_config');
		$names = [];
		$configs = [];
		for ($i = 0; $i < count($restore_tests); $i++) {
			$rt = $restore_tests[$i]['name'];
			$names[] = $rt;
			$rt_config = $rtest_config->getRestoreTestConfig($rt);
			$configs[$rt] = $rt_config;
		}

		$result = $rtest_config->removeRestoreTestsConfig($names);
		if ($result === true) {
			$audit = $this->getModule('audit');
			for ($i = 0; $i < count($names); $i++) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove restore test. Name: {$names[$i]}"
				);
				$this->removeAdminJob($names[$i]);
				if ($configs[$names[$i]]['source_type'] == RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB) {
					// If job was using backup job, here remove this dependency as no longer needed
					$this->updateBackupJob($configs[$names[$i]], 'remove');
				}
			}
		}

		$this->setRestoreTestList(null, null);

		$this->onRemoveRestoreTest(null);
	}

	/**
	 * On remove restore test event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveRestoreTest($param)
	{
		$this->raiseEvent('OnRemoveRestoreTest', $this, $param);
	}

	/**
	 * Set all restore test resource lists.
	 */
	private function setResourceLists(): void
	{
		$this->setBackupJobList();
		$this->setRestoreDestinationList();
		$this->setRestoreJobList();
		$this->setRestorePolicyList();
		$this->setVerificationRuleList();
		$this->setBaculaResourceLists();
		$this->setVerifyJobList();
		$this->setWebAccessList();
	}

	/**
	 * Set backup job list.
	 */
	private function setBackupJobList(): void
	{
		$jobs = [];
		$api = $this->getModule('api');
		$result = $api->get(['jobs', 'resnames', '?type=B']);
		$sess = $this->getPage()->getSession();
		$dir = $sess->itemAt('director');
		if ($result->error == 0) {
			foreach ($result->output as $director => $tasks) {
				if ($director != $dir) {
					continue;
				}
				$jobs = $tasks;
			}
			sort($jobs, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$data_source = array_combine($jobs, $jobs);
		$this->RestoreTestBackupJobList->DataSource = $data_source;
		$this->RestoreTestBackupJobList->dataBind();
	}

	/**
	 * Set restore destination list.
	 */
	public function setRestoreDestinationList(): void
	{
		$restore_destination_config = $this->getModule('restore_destination_config');
		$restore_destinations = $restore_destination_config->getConfig();
		$names = array_keys($restore_destinations);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$data_source = array_combine($names, $names);
		$this->RestoreTestRestoreDestinationDestination->DataSource = $data_source;
		$this->RestoreTestRestoreDestinationDestination->dataBind();
	}

	/**
	 * Set restore job list.
	 */
	private function setRestoreJobList(): void
	{
		$jobs = [];
		$api = $this->getModule('api');
		$result = $api->get(['jobs', 'resnames', '?type=R']);
		$sess = $this->getPage()->getSession();
		$dir = $sess->itemAt('director');
		if ($result->error == 0) {
			foreach ($result->output as $director => $restore_jobs) {
				if ($director != $dir) {
					continue;
				}
				$jobs = $restore_jobs;
				break;
			}
			sort($jobs, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$data_source = array_combine($jobs, $jobs);
		$this->RestoreTestRestoreJob->DataSource = $data_source;
		$this->RestoreTestRestoreJob->dataBind();
	}

	/**
	 * Set restore policy list.
	 */
	public function setRestorePolicyList(): void
	{
		$rpolicy_config = $this->getModule('restore_policy_config');
		$restore_policies = $rpolicy_config->getConfig();
		$names = array_keys($restore_policies);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$data_source = array_combine($names, $names);
		$this->RestoreTestRestorePolicyPolicy->DataSource = $data_source;
		$this->RestoreTestRestorePolicyPolicy->dataBind();
	}

	/**
	 * Set verification check list.
	 */
	public function setVerificationRuleList(): void
	{
		$verification_check_config = $this->getModule('verification_rule_config');
		$verification_checks = $verification_check_config->getConfig();
		$names = array_keys($verification_checks);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$data_source = array_combine($names, $names);
		$this->RestoreTestVerificationRules->DataSource = $data_source;
		$this->RestoreTestVerificationRules->dataBind();
	}

	/**
	 * Set Bacula resource lists used in restore test advanced options.
	 */
	private function setBaculaResourceLists(): void
	{
		$this->setClientList();
		$this->setFilesetList();
		$this->setPoolList();
		$this->setStorageList();
		$this->setMessagesList();
	}

	/**
	 * Set client list.
	 */
	private function setClientList(): void
	{
		$clients = [];
		$api = $this->getModule('api');
		$result = $api->get(['clients', 'resnames']);
		if ($result->error == 0 && is_array($result->output)) {
			$clients = $result->output;
			sort($clients, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$this->setBaculaResourceList($this->RestoreTestClient, $clients);
	}

	/**
	 * Set verify job list.
	 */
	private function setVerifyJobList(): void
	{
		$jobs = [];
		$api = $this->getModule('api');
		$result = $api->get(['jobs', 'resnames', '?type=V']);
		$sess = $this->getPage()->getSession();
		$dir = $sess->itemAt('director');
		if ($result->error == 0) {
			foreach ($result->output as $director => $restore_jobs) {
				if ($director != $dir) {
					continue;
				}
				$jobs = $restore_jobs;
				break;
			}
			sort($jobs, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$data_source = array_combine($jobs, $jobs);
		$this->RestoreTestVerificationVerifyJob->DataSource = $data_source;
		$this->RestoreTestVerificationVerifyJob->dataBind();
	}

	/**
	 * Set web access field values.
	 */
	private function setWebAccessList(): void
	{
		// Get default web access parameters from current host config
		$host = $this->User->getDefaultAPIHost();
		$host_config = $this->getModule('host_config');
		$hconfig = $host_config->getHostConfig($host);
		$def_protocol = $hconfig['protocol'] ?? RestoreVerification::DEFAULT_WEB_ACCESS_PROTOCOL;
		$def_address = $hconfig['address'] ?? RestoreVerification::DEFAULT_WEB_ACCESS_ADDRESS;
		$def_port = $hconfig['port'] ?? RestoreVerification::DEFAULT_WEB_ACCESS_PORT;
		$def_allowed_ips = RestoreVerification::DEFAULT_WEB_ALLOWED_IPS;
		$this->RestoreTestWebProtocol->SelectedValue = $def_protocol;
		$this->RestoreTestWebAddress->Text = $def_address;
		$this->RestoreTestWebPort->Text = $def_port;
		$this->RestoreTestWebAllowedIPs->Text = $def_allowed_ips;
	}


	/**
	 * Set fileset list.
	 */
	private function setFilesetList(): void
	{
		$filesets = [];
		$api = $this->getModule('api');
		$result = $api->get(['filesets', 'resnames']);
		if ($result->error == 0 && is_object($result->output)) {
			$sess = $this->getSession();
			$director = $sess->itemAt('director');
			$filesets = $result->output->{$director} ?? [];
			sort($filesets, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$this->setBaculaResourceList($this->RestoreTestFileset, $filesets);
	}

	/**
	 * Set pool list.
	 */
	private function setPoolList(): void
	{
		$pools = [];
		$api = $this->getModule('api');
		$result = $api->get(['pools', 'resnames']);
		if ($result->error == 0 && is_array($result->output)) {
			$pools = $result->output;
			sort($pools, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$this->setBaculaResourceList($this->RestoreTestPool, $pools);
	}

	/**
	 * Set storage list.
	 */
	private function setStorageList(): void
	{
		$storages = [];
		$api = $this->getModule('api');
		$result = $api->get(['storages', 'resnames']);
		if ($result->error == 0 && is_array($result->output)) {
			$storages = $result->output;
			sort($storages, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$this->setBaculaResourceList($this->RestoreTestStorage, $storages);
	}

	/**
	 * Set messages list.
	 */
	private function setMessagesList(): void
	{
		$messages = [];
		$result = BaculaConfigAction::readResources('dir', 'Messages');
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$messages[] = $result->output[$i]->Messages->Name;
			}
			sort($messages, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$selected_value = '';
		if ($messages) {
			$selected_value = in_array('Standard', $messages) ? 'Standard' : $messages[0];
		}
		$this->setBaculaResourceList($this->RestoreTestMessages, $messages, $selected_value);
	}

	/**
	 * Set Bacula resource dropdown list.
	 *
	 * @param TActiveDropDownList $control dropdown control
	 * @param array $resources Bacula resource names
	 * @param string $selected_value selected value
	 */
	private function setBaculaResourceList(TActiveDropDownList $control, array $resources, string $selected_value = ''): void
	{
		$data_source = array_combine($resources, $resources);
		if ($data_source === false) {
			$data_source = [];
		}
		$control->DataSource = $data_source;
		if (empty($selected_value) && count($resources) > 0) {
			$selected_value = $resources[0];
		}
		$control->SelectedValue = $selected_value;
		$control->dataBind();
	}

	/**
	 * Set backup source selection controls.
	 *
	 * @param string $source_selection source selection
	 * @param TCallbackClientScript $cb callback client object
	 */
	private function setSourceSelection(string $source_selection, $cb): void
	{
		$this->RestoreTestBackupSourceLatestSuccessfulRadio->Checked = false;
		$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->Checked = false;
		$this->RestoreTestBackupSourceLatestJobLevelsRadio->Checked = false;
		if ($source_selection === RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL_TIME_RANGE) {
			$this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->Checked = true;
			$cb->show('restore_test_window_backup_source_time_range');
			$cb->hide('restore_test_window_backup_source_job_levels');
		} elseif ($source_selection === RestoreTestConfig::SOURCE_SELECTION_JOB_LEVELS) {
			$this->RestoreTestBackupSourceLatestJobLevelsRadio->Checked = true;
			$cb->hide('restore_test_window_backup_source_time_range');
			$cb->show('restore_test_window_backup_source_job_levels');
		} else {
			$this->RestoreTestBackupSourceLatestSuccessfulRadio->Checked = true;
			$cb->hide('restore_test_window_backup_source_time_range');
			$cb->hide('restore_test_window_backup_source_job_levels');
		}
	}

	/**
	 * Set restore scope controls.
	 *
	 * @param string $restore_scope restore scope
	 */
	private function setRestoreScope(string $restore_scope): void
	{
		$this->RestoreTestRestoreScopeEntireBackup->Checked = false;
		$this->RestoreTestRestoreScopeFilesRequiredByVerificationRules->Checked = false;
		$this->RestoreTestRestoreScopeRandomSample->Checked = false;
		if ($restore_scope === RestoreTestConfig::RESTORE_SCOPE_VERIFICATION_RULES_PATHS) {
			$this->RestoreTestRestoreScopeFilesRequiredByVerificationRules->Checked = true;
		} elseif ($restore_scope === RestoreTestConfig::RESTORE_SCOPE_RANDOM_SAMPLE) {
			$this->RestoreTestRestoreScopeRandomSample->Checked = true;
		} else {
			$this->RestoreTestRestoreScopeEntireBackup->Checked = true;
		}
	}

	/**
	 * Get selected source type.
	 *
	 * @return string source type
	 */
	private function getSourceType(): string
	{
		$source_type = RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB;
		if ($this->RestoreTestBackupJobSingleJobIdRadio->Checked) {
			$source_type = RestoreTestConfig::SOURCE_TYPE_BACKUP_JOBID;
		}
		return $source_type;
	}

	/**
	 * Get selected source selection.
	 *
	 * @return string source selection
	 */
	private function getSourceSelection(): string
	{
		$source_selection = RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL;
		if ($this->RestoreTestBackupSourceLatestBackupTimeRangeRadio->Checked) {
			$source_selection = RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL_TIME_RANGE;
		} elseif ($this->RestoreTestBackupSourceLatestJobLevelsRadio->Checked) {
			$source_selection = RestoreTestConfig::SOURCE_SELECTION_JOB_LEVELS;
		}
		return $source_selection;
	}

	/**
	 * Get selected restore scope.
	 *
	 * @return string restore scope
	 */
	private function getRestoreScope(): string
	{
		$restore_scope = RestoreTestConfig::RESTORE_SCOPE_ENTIRE_BACKUP;
		if ($this->RestoreTestRestoreScopeFilesRequiredByVerificationRules->Checked) {
			$restore_scope = RestoreTestConfig::RESTORE_SCOPE_VERIFICATION_RULES_PATHS;
		} elseif ($this->RestoreTestRestoreScopeRandomSample->Checked) {
			$restore_scope = RestoreTestConfig::RESTORE_SCOPE_RANDOM_SAMPLE;
		}
		return $restore_scope;
	}

	/**
	 * Get timestamp from date.
	 *
	 * @param string $date date in YYYY-MM-DD format
	 * @param bool $end_day use end of day
	 * @return int Unix timestamp or zero if date empty or invalid
	 */
	private function getTimestampFromDate(string $date, bool $end_day): int
	{
		$timestamp = 0;
		if ($date !== '') {
			$time = $end_day ? ' 23:59:59' : ' 00:00:00';
			$date_time = $date . $time;
			$parsed_timestamp = strtotime($date_time);
			if (is_int($parsed_timestamp)) {
				$timestamp = $parsed_timestamp;
			}
		}
		return $timestamp;
	}

	/**
	 * Get selected job levels.
	 *
	 * @return array selected job levels
	 */
	private function getSelectedJobLevels(): array
	{
		$job_levels = [];
		for ($i = 0; $i < count($this->RestoreTestBackupSourceJobLevels->Items); $i++) {
			if ($this->RestoreTestBackupSourceJobLevels->Items[$i]->Selected) {
				$job_levels[] = $this->RestoreTestBackupSourceJobLevels->Items[$i]->Value;
			}
		}
		return $job_levels;
	}

	/**
	 * Get selected restore verification method.
	 *
	 * @return string restore verification method
	 */
	private function getRestoreVerificationMethod(): string
	{
		$verification_method = RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES;
		if ($this->RestoreTestVerificationMethodRulesRadio->Checked) {
			$verification_method = RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES;
		} elseif ($this->RestoreTestVerificationMethodVerifyJobRadio->Checked) {
			$verification_method = RestoreTestConfig::RESTORE_VERIFICATION_METHOD_VERIFY_JOB;
		}
		return $verification_method;
	}

	/**
	 * Get selected verification checks.
	 *
	 * @return array selected verification check names
	 */
	private function getSelectedVerificationRules(): array
	{
		$verification_checks = [];
		for ($i = 0; $i < count($this->RestoreTestVerificationRules->Items); $i++) {
			if ($this->RestoreTestVerificationRules->Items[$i]->Selected) {
				$verification_checks[] = $this->RestoreTestVerificationRules->Items[$i]->Value;
			}
		}
		return $verification_checks;
	}

	/**
	 * Get backup source text for restore test list.
	 *
	 * @param array $config restore test configuration
	 * @return string backup source text
	 */
	private function getBackupSource(array $config): string
	{
		$backup_source = $config['source_backup_job'] ?? '';
		if ($config['source_type'] === RestoreTestConfig::SOURCE_TYPE_BACKUP_JOBID) {
			$backup_source = $config['source_backup_jobid'] ?? '';
		}
		return $backup_source;
	}
}
