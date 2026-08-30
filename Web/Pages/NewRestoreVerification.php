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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\PluginConfigBase;
use Bacularis\Common\Modules\RestoreDestinationCapability;
use Bacularis\Common\Modules\RestoreVerification;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\JobAction;
use Bacularis\Web\Modules\RestoreDestinationConfig;
use Bacularis\Web\Modules\RestorePolicyConfig;
use Bacularis\Web\Modules\RestoreTestConfig;
use Bacularis\Web\Modules\TRestoreVerification;
use Bacularis\Web\Modules\VerificationRuleConfig;
use Bacularis\Web\Modules\WebAccessBaculaResource;
use Prado\Prado;

/**
 * New restore verification wizard page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class NewRestoreVerification extends BaculumWebPage
{
	use TRestoreVerification;

	/**
	 * Load page.
	 *
	 * @param mixed $param event parameter
	 */
	public function onLoad($param): void
	{
		parent::onLoad($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->loadBackupJobs();
		$this->loadVerificationRules();
		$this->loadRestoreJobs();
		$this->loadRestoreDestinations();
		$this->loadRestoreClients();
		$this->loadRestorePolicies();
	}

	/**
	 * Pre-render page.
	 *
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param): void
	{
		parent::onPreRender($param);
		if ($this->IsCallBack) {
			return;
		}
		if ($this->NewRestoreVerificationWizard->getActiveStepIndex() === 1) {
			if (count($this->VerificationRules->Items) === 0) {
				$this->loadVerificationRules();
			}
			if (count($this->RestoreJob->Items) === 0) {
				$this->loadRestoreJobs();
			}
		} elseif ($this->NewRestoreVerificationWizard->getActiveStepIndex() === 2) {
			if (count($this->RestoreDestination->Items) === 0) {
				$this->loadRestoreDestinations();
			}
			if (count($this->RestoreClient->Items) === 0) {
				$this->loadRestoreClients();
			}
		} elseif ($this->NewRestoreVerificationWizard->getActiveStepIndex() === 3 && count($this->RestorePolicy->Items) === 0) {
			$this->loadRestorePolicies();
		}
	}

	/**
	 * Wizard next button callback actions.
	 *
	 * @param TWizard $sender sender object
	 * @param TWizardNavigationEventParameter $param sender parameters
	 */
	public function wizardNext($sender, $param): void
	{
		if ($param->getCurrentStepIndex() === 0) {
			if (!$this->hasSelectedPaths()) {
				$param->setCancelNavigation(true);
				$this->SelectedPathsValidator->setIsValid(false);
			}
		} elseif ($param->getCurrentStepIndex() === 2) {
			if ($this->isCreateDestinationVisible() && $this->isRootRestorePath($this->RestorePath->Text)) {
				$param->setCancelNavigation(true);
				$this->showRestorePathWarning(true);
			}
		}
	}

	/**
	 * Cancel wizard.
	 *
	 * @param mixed $sender sender object
	 * @param mixed $param event parameter
	 */
	public function wizardStop($sender, $param): void
	{
		$this->goToPage('JobList');
	}

	/**
	 * Complete wizard.
	 *
	 * @param mixed $sender sender object
	 * @param mixed $param event parameter
	 */
	public function wizardCompleted($sender, $param): void
	{
		$this->CreateRestoreTestError->Display = 'None';
		$result = $this->createRestoreTest();
		if (!$result) {
			return;
		}
		$this->goToPage('JobList');
	}

	/**
	 * Get restore test name summary.
	 *
	 * @return string restore test name
	 */
	public function getRestoreTestNameSummary(): string
	{
		$name = trim($this->RestoreTestName->Text);
		return $this->html($name);
	}

	/**
	 * Get backup job summary.
	 *
	 * @return string backup job name
	 */
	public function getBackupJobSummary(): string
	{
		$backup_job = $this->BackupJob->SelectedValue;
		return $this->html($backup_job);
	}

	/**
	 * Get selected files summary.
	 *
	 * @return string selected files summary
	 */
	public function getFilesSummary(): string
	{
		$paths = $this->getSelectedPaths();
		$msg = Prado::localize('%d selected files');
		$count = count($paths);
		return sprintf($msg, $count);
	}

	/**
	 * Get verification method summary.
	 *
	 * @return string verification method summary
	 */
	public function getVerificationSummary(): string
	{
		$verification = 'Basic verification';
		if ($this->VerificationMethodAdvanced->Checked) {
			$verification = 'Advanced verification';
		} elseif ($this->VerificationMethodExistingRules->Checked) {
			$verification_rules = $this->getSelectedVerificationRules();
			$rule_sets = implode(', ', $verification_rules);
			$verification = sprintf(
				'Existing verification rules: %s',
				$rule_sets
			);
		}
		$verification = Prado::localize($verification);
		return $this->html($verification);
	}

	/**
	 * Get restore job summary.
	 *
	 * @return string restore job name
	 */
	public function getRestoreJobSummary(): string
	{
		$restore_job = $this->RestoreJob->SelectedValue;
		return $this->html($restore_job);
	}

	/**
	 * Get restore destination summary.
	 *
	 * @return array restore destination summary
	 */
	public function getDestinationSummary(): array
	{
		$name = $this->RestoreDestination->SelectedValue;
		$restore_client = '-';
		$restore_path = '-';
		$restore_destination_config = $this->getModule('restore_destination_config');
		$rd_config = $restore_destination_config->getRestoreDestinationConfig($name);
		if ($rd_config) {
			$restore_client = $rd_config['restore_client'] ?? '-';
			$restore_path = $rd_config['restore_path_prefixed'] ?? ($rd_config['restore_path_original'] ?? '-');
		}
		return [
			'name' => $this->html($name),
			'restore_client' => $this->html($restore_client),
			'restore_path' => $this->html($restore_path)
		];
	}

	/**
	 * Get restore destination name summary.
	 *
	 * @return string restore destination name
	 */
	public function getDestinationNameSummary(): string
	{
		$summary = $this->getDestinationSummary();
		return $summary['name'];
	}

	/**
	 * Get restore destination client summary.
	 *
	 * @return string restore destination client
	 */
	public function getDestinationClientSummary(): string
	{
		$summary = $this->getDestinationSummary();
		return $summary['restore_client'];
	}

	/**
	 * Get restore destination path summary.
	 *
	 * @return string restore destination path
	 */
	public function getDestinationPathSummary(): string
	{
		$summary = $this->getDestinationSummary();
		return $summary['restore_path'];
	}

	/**
	 * Get run mode summary.
	 *
	 * @return string run mode summary
	 */
	public function getRunModeSummary(): string
	{
		$run_mode = 'Manually only';
		if ($this->RunMethodExistingPolicy->Checked) {
			$run_mode = sprintf(
				'Use existing restore policy: %s',
				$this->RestorePolicy->SelectedValue
			);
		} elseif ($this->RunMethodAfterBackup->Checked) {
			$run_mode = 'After each successful backup';
		}
		$run_mode = Prado::localize($run_mode);
		return $this->html($run_mode);
	}

	/**
	 * Load backup job list.
	 */
	private function loadBackupJobs(): void
	{
		$jobs = [];
		$api = $this->getModule('api');
		$result = $api->get(['jobs', 'resnames', '?type=B']);
		$sess = $this->getPage()->getSession();
		$dir = $sess->itemAt('director');
		if ($result->error == 0) {
			foreach ($result->output as $director => $jlist) {
				if ($director != $dir) {
					continue;
				}
				$jobs = $jlist;
			}
			sort($jobs, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$data_source = array_combine($jobs, $jobs);
		$this->BackupJob->DataSource = $data_source;
		$this->BackupJob->dataBind();
	}

	/**
	 * Load verification rule list.
	 */
	private function loadVerificationRules(): void
	{
		$verification_rule_config = $this->getModule('verification_rule_config');
		$verification_rules = $verification_rule_config->getConfig();
		$names = array_keys($verification_rules);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$data_source = array_combine($names, $names);
		$this->VerificationRules->DataSource = $data_source;
		$this->VerificationRules->dataBind();
	}

	/**
	 * Load restore job list.
	 */
	private function loadRestoreJobs(): void
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
		$data_source = [];
		if (count($jobs) > 0) {
			$data_source = array_combine($jobs, $jobs);
		}
		$this->RestoreJob->DataSource = $data_source;
		if (count($jobs) > 0) {
			$this->RestoreJob->SelectedValue = $jobs[0];
		}
		$this->RestoreJob->dataBind();
	}

	/**
	 * Load restore destination list.
	 *
	 * @param string $selected_value selected restore destination name
	 */
	private function loadRestoreDestinations(string $selected_value = ''): void
	{
		$restore_destination_config = $this->getModule('restore_destination_config');
		$restore_destinations = $restore_destination_config->getConfig();
		$names = array_keys($restore_destinations);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$data_source = array_combine($names, $names);
		$this->RestoreDestination->DataSource = $data_source;
		if (!empty($selected_value)) {
			$this->RestoreDestination->SelectedValue = $selected_value;
		}
		$this->RestoreDestination->dataBind();
	}

	/**
	 * Load restore client list.
	 */
	private function loadRestoreClients(): void
	{
		$client_list = [];
		$clients_mod = $this->getModule('api');
		$clients = $clients_mod->get(['clients', 'resnames']);
		if ($clients->error === 0) {
			$client_list = $clients->output;
			natcasesort($client_list);
		}
		array_unshift($client_list, '');
		$data_source = array_combine($client_list, $client_list);
		$this->RestoreClient->DataSource = $data_source;
		$this->RestoreClient->dataBind();
	}

	/**
	 * Load restore policy list.
	 */
	private function loadRestorePolicies(): void
	{
		$restore_policy_config = $this->getModule('restore_policy_config');
		$restore_policies = $restore_policy_config->getConfig();
		$names = array_keys($restore_policies);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);
		$data_source = array_combine($names, $names);
		$this->RestorePolicy->DataSource = $data_source;
		$this->RestorePolicy->dataBind();
	}

	/**
	 * Load backup version list for selected backup job.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadBackupVersions($sender, $param): void
	{
		$this->BackupVersion->DataSource = [];
		$this->BackupVersion->dataBind();
		$this->clearJobFiles($sender, $param);

		$backup_job = $this->BackupJob->getSelectedValue();
		if (empty($backup_job)) {
			return;
		}

		$query = [
			'name' => $backup_job,
			'type' => 'B',
			'jobstatus' => 'T',
			'level' => 'FID',
			'limit' => 100,
			'order_by' => 'starttime',
			'order_type' => 'desc'
		];
		$qs = '?' . http_build_query($query);
		$api = $this->getModule('api');
		$result = $api->get(['jobs', $qs]);
		if ($result->error != 0) {
			return;
		}

		$misc = $this->getModule('misc');
		$labels = $values = [];
		for ($i = 0; $i < count($result->output); $i++) {
			$job = $result->output[$i];
			$level = $misc->getJobLevelLong($job->level ?? '');
			$status = $misc->getJobState($job->jobstatus ?? '');
			$status_value = is_array($status) ? ($status['value'] ?? '') : '';
			$labels[] = sprintf(
				'[%s] %s - %s - %s',
				$job->jobid,
				$level,
				$job->starttime,
				$status_value
			);
			$values[] = $job->jobid;
		}
		$this->BackupVersion->DataSource = array_combine($values, $labels);
		$this->BackupVersion->dataBind();
	}

	/**
	 * Load backup version file list.
	 *
	 * @param TActiveDropDownList $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadJobFiles($sender, $param): void
	{
		$jobid = $this->BackupVersion->getSelectedValue();
		$this->BackupFiles->setJobId($jobid);
		$this->BackupFiles->loadFileList($sender, $param);
	}

	/**
	 * Clear backup version file list.
	 *
	 * @param mixed $sender sender object
	 * @param mixed $param event parameter
	 */
	public function clearJobFiles($sender, $param): void
	{
		$this->BackupFiles->setJobId(0);
		$this->BackupFiles->clearFileList($sender, $param);
	}

	/**
	 * Save restore destination from wizard.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function saveRestoreDestination($sender, $param): void
	{
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->RestoreDestinationError);
		$this->showRestorePathWarning(false);

		if ($this->isRootRestorePath($this->RestorePath->Text)) {
			$this->showRestorePathWarning(true);
			return;
		}

		$restore_client = $this->RestoreClient->SelectedValue;
		$restore_path = trim($this->RestorePath->Text);
		$name = trim($this->DestinationName->Text);
		if (empty($name) || empty($restore_client) || empty($restore_path)) {
			return;
		}

		$restore_destination_config = $this->getModule('restore_destination_config');
		if ($restore_destination_config->restoreDestinationConfigExists($name)) {
			$msg = 'Restore destination with name \'%s\' already exists.';
			$emsg = sprintf($msg, $name);
			$cb->update($this->RestoreDestinationError, $emsg);
			$cb->show($this->RestoreDestinationError);
			return;
		}
		$cfg_restore_destination = [
			'description' => '',
			'enabled' => '1',
			'restore_client' => $restore_client,
			'restore_mode' => RestoreDestinationConfig::RESTORE_MODE_PREFIXED_PATH,
			'restore_path_prefixed' => $restore_path,
			'restore_path_original' => '/',
			'isolated_test_environment_confirmation' => '0',
			'capabilities' => [
				RestoreDestinationCapability::FILE_CHECK
			]
		];

		$result = $restore_destination_config->setRestoreDestinationConfig(
			$name,
			$cfg_restore_destination
		);
		if ($result !== true) {
			$msg = 'Error while saving restore destination.';
			$cb->update($this->RestoreDestinationError, $msg);
			$cb->show($this->RestoreDestinationError);
			return;
		}

		$this->loadRestoreDestinations($name);
		$audit = $this->getModule('audit');
		$audit->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_APPLICATION,
			"Create restore destination. Name: $name"
		);
		$cb->callClientFunction('oRestoreVerificationDestination.save_destination_cb');
	}

	/**
	 * Validate selected paths.
	 *
	 * @param TCustomValidator $sender sender object
	 * @param TServerValidateEventParameter $param event parameter
	 */
	public function validateSelectedPaths($sender, $param): void
	{
		$param->IsValid = $this->hasSelectedPaths();
	}

	/**
	 * Validate selected verification rules.
	 *
	 * @param TCustomValidator $sender sender object
	 * @param TServerValidateEventParameter $param event parameter
	 */
	public function validateVerificationRules($sender, $param): void
	{
		if (!$this->VerificationMethodExistingRules->Checked) {
			$param->IsValid = true;
			return;
		}
		$verification_rules = $this->getSelectedVerificationRules();
		$param->IsValid = (count($verification_rules) > 0);
	}

	/**
	 * Validate restore path for a new restore destination.
	 *
	 * @param TCustomValidator $sender sender object
	 * @param TServerValidateEventParameter $param event parameter
	 */
	public function validateRestorePath($sender, $param): void
	{
		if (!$this->isCreateDestinationVisible()) {
			$param->IsValid = true;
			$this->showRestorePathWarning(false);
			return;
		}
		$is_valid = !$this->isRootRestorePath($this->RestorePath->Text);
		$param->IsValid = $is_valid;
		$this->showRestorePathWarning(!$is_valid);
	}

	/**
	 * Validate selected restore policy.
	 *
	 * @param TCustomValidator $sender sender object
	 * @param TServerValidateEventParameter $param event parameter
	 */
	public function validateRestorePolicy($sender, $param): void
	{
		if (!$this->RunMethodExistingPolicy->Checked) {
			$param->IsValid = true;
			return;
		}
		$param->IsValid = !empty($this->RestorePolicy->SelectedValue);
	}

	/**
	 * Create restore test with all wizard settings.
	 *
	 * @return bool true if restore test created successfully, otherwise false
	 */
	private function createRestoreTest(): bool
	{
		$name = trim($this->RestoreTestName->Text);
		$rtest_config = $this->getModule('restore_test_config');
		if ($rtest_config->restoreTestConfigExists($name)) {
			$msg = sprintf('Restore test with name \'%s\' already exists.', $name);
			$this->showCreateRestoreTestError($msg);
			return false;
		}

		$verification_rule_sets = $this->getRestoreVerificationRuleSets($name);
		if (!$verification_rule_sets) {
			return false;
		}

		$restore_policy = $this->getRestorePolicyName($name);
		if (empty($restore_policy)) {
			return false;
		}

		$rt_config = $this->getRestoreTestConfig(
			$name,
			$verification_rule_sets,
			$restore_policy
		);
		if (!$rt_config) {
			return false;
		}

		$action_params = [
			'restore_test' => $name
		];
		$web_access = $this->createWebAccess(
			$rt_config['restore_job'],
			WebAccessBaculaResource::ACTION_RUN_RESTORE_TEST_NAME,
			$action_params,
			$rt_config['web_allowed_ips']
		);
		if (!$web_access['state']) {
			$this->showCreateRestoreTestError('Error while creating web access for restore test.');
			return false;
		}
		$rt_config['web_admin_token'] = $web_access['token'];

		$rp_config = $this->getRestorePolicyConfig($restore_policy);
		if ($rp_config && $rp_config['run_method'] === RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP) {
			$token = $this->createRunWebAccessToken($rt_config, $rp_config);
			if (!$token) {
				$this->showCreateRestoreTestError('Error while creating web access for running restore test.');
				return false;
			}
			$rt_config['web_run_token'] = $token;
			$backup_job_updated = $this->updateBackupJob($rt_config, 'create');
			if (!$backup_job_updated) {
				$this->showCreateRestoreTestError('Error while updating backup job for restore test run.');
				return false;
			}
		}

		$result = $rtest_config->setRestoreTestConfig($name, $rt_config);
		if ($result !== true) {
			$this->showCreateRestoreTestError('Error while saving restore test.');
			return false;
		}

		$audit = $this->getModule('audit');
		$audit->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_APPLICATION,
			"Create restore test. Name: $name"
		);

		$admin_job_created = $this->createAdminJob($rt_config);
		if (!$admin_job_created) {
			$this->showCreateRestoreTestError('Restore test configuration has been saved, but the admin job could not be created.');
			return false;
		}

		if ($this->RunRestoreTestNow->Checked) {
			$started = $this->runRestoreTestNow($name);
			if (!$started) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get or create restore verification rule sets.
	 *
	 * @param string $restore_test_name restore test name
	 * @return array verification rule set names
	 */
	private function getRestoreVerificationRuleSets(string $restore_test_name): array
	{
		if ($this->VerificationMethodExistingRules->Checked) {
			$verification_rules = $this->getSelectedVerificationRules();
			if (!$verification_rules) {
				$this->showCreateRestoreTestError('Select at least one verification rule.');
			}
			return $verification_rules;
		}

		$name = $this->getUniqueVerificationRuleName($restore_test_name);
		$rules = [];
		$paths = $this->getSelectedPaths();
		if (!$paths) {
			$this->showCreateRestoreTestError('Select at least one path to verify.');
			return [];
		}
		$checksum_checker = '';
		if ($this->VerificationMethodAdvanced->Checked) {
			$checksum_checker = $this->getBackupJobChecksumChecker();
			if (empty($checksum_checker)) {
				return [];
			}
		}
		for ($i = 0; $i < count($paths); $i++) {
			$rules[$paths[$i]] = $this->getVerificationChecks($paths[$i], $checksum_checker);
		}

		$cfg_verification_rule = [
			'description' => '',
			'enabled' => '1',
			'rules' => $rules
		];
		$verification_rule_config = $this->getModule('verification_rule_config');
		$result = $verification_rule_config->setVerificationRuleConfig(
			$name,
			$cfg_verification_rule
		);
		if ($result !== true) {
			$this->showCreateRestoreTestError('Error while saving verification rules.');
			return [];
		}
		$audit = $this->getModule('audit');
		$audit->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_APPLICATION,
			"Create verification rule. Name: $name"
		);
		return [$name];
	}

	/**
	 * Get verification checks for selected wizard verification method.
	 *
	 * @param string $path selected restore path
	 * @param string $checksum_checker checksum checker plugin name
	 * @return array verification checks
	 */
	private function getVerificationChecks(string $path, string $checksum_checker = ''): array
	{
		$checks = [
			[
				'checker' => 'FileExistsCheck',
				'operator' => '==',
				'value' => 'true'
			]
		];
		if ($this->isDirectoryPath($path)) {
			return $checks;
		}
		if ($this->VerificationMethodAdvanced->Checked) {
			$checks[] = [
				'checker' => 'FileSizeCheck',
				'operator' => VerificationRuleConfig::EQUAL_CATALOG_VALUE,
				'value' => ''
			];
			$checks[] = [
				'checker' => $checksum_checker,
				'operator' => VerificationRuleConfig::EQUAL_CATALOG_VALUE,
				'value' => ''
			];
		} else {
			$checks[] = [
				'checker' => 'FileSizeCheck',
				'operator' => '>',
				'value' => '0'
			];
		}
		return $checks;
	}

	/**
	 * Get checksum checker plugin name for selected backup job.
	 *
	 * @return string checksum checker plugin name or empty string
	 */
	private function getBackupJobChecksumChecker(): string
	{
		$signature = $this->getBackupJobFileSetSignature();
		if (empty($signature)) {
			$msg = 'The selected backup job does not use FileSet signatures. To use Advanced verification, enable Signature in the job FileSet options and run a new backup.';
			$this->showCreateRestoreTestError($msg);
			return '';
		}

		$checker = $this->getChecksumCheckerBySignature($signature);
		if (empty($checker)) {
			$msg = sprintf(
				'The selected backup job uses unsupported FileSet signature algorithm "%s" for Advanced verification.',
				$signature
			);
			$this->showCreateRestoreTestError($msg);
		}
		return $checker;
	}

	/**
	 * Get first FileSet signature configured for selected backup job.
	 *
	 * @return string signature algorithm or empty string
	 */
	private function getBackupJobFileSetSignature(): string
	{
		$fileset = $this->getBackupJobFileSetName();
		if (empty($fileset)) {
			return '';
		}

		$params = [
			'config',
			'dir',
			'Fileset',
			$fileset
		];
		$api = $this->getModule('api');
		$result = $api->get($params);
		if ($result->error != 0) {
			return '';
		}

		$fileset_config = (array) $result->output;
		if (!key_exists('Include', $fileset_config) || !is_array($fileset_config['Include'])) {
			return '';
		}

		$signature = '';
		for ($i = 0; $i < count($fileset_config['Include']); $i++) {
			$signature = $this->getIncludeSignature($fileset_config['Include'][$i]);
			if (!empty($signature)) {
				break;
			}
		}
		return $signature;
	}

	/**
	 * Get FileSet name from selected backup job configuration.
	 *
	 * @return string FileSet name or empty string
	 */
	private function getBackupJobFileSetName(): string
	{
		$fileset = '';
		$params = [
			'config',
			'dir',
			'Job',
			$this->BackupJob->SelectedValue
		];
		$api = $this->getModule('api');
		$result = $api->get($params);
		if ($result->error == 0) {
			$job_config = (array) $result->output;
			if (key_exists('Fileset', $job_config)) {
				$fileset = $job_config['Fileset'];
			}
		}
		return $fileset;
	}

	/**
	 * Get first Signature value from FileSet Include options.
	 *
	 * @param mixed $include FileSet Include section
	 * @return string Signature value or empty string
	 */
	private function getIncludeSignature($include): string
	{
		$include = (array) $include;
		if (!key_exists('Options', $include) || !is_array($include['Options'])) {
			return '';
		}

		$signature = '';
		for ($i = 0; $i < count($include['Options']); $i++) {
			$options = (array) $include['Options'][$i];
			if (key_exists('Signature', $options) && !empty($options['Signature'])) {
				$signature = $options['Signature'];
				break;
			}
		}
		return $signature;
	}

	/**
	 * Get checksum checker plugin name by Bacula Signature value.
	 *
	 * @param string $signature Signature directive value
	 * @return string checksum checker plugin name or empty string
	 */
	private function getChecksumCheckerBySignature(string $signature): string
	{
		$signature = strtolower($signature);
		$checkers = [
			'md5' => 'MD5ChecksumBase64Check',
			'sha1' => 'SHA1ChecksumBase64Check',
			'sha256' => 'SHA256ChecksumBase64Check',
			'sha512' => 'SHA512ChecksumBase64Check'
		];
		$checker = '';
		if (key_exists($signature, $checkers)) {
			$checker = $checkers[$signature];
		}
		return $checker;
	}

	/**
	 * Check if selected path is a directory path.
	 *
	 * @param string $path selected restore path
	 * @return bool true if path points to directory, otherwise false
	 */
	private function isDirectoryPath(string $path): bool
	{
		return (substr($path, -1) === '/');
	}

	/**
	 * Get restore policy name selected or created by the wizard.
	 *
	 * @param string $restore_test_name restore test name
	 * @return string restore policy name
	 */
	private function getRestorePolicyName(string $restore_test_name): string
	{
		if ($this->RunMethodExistingPolicy->Checked) {
			return $this->RestorePolicy->SelectedValue;
		}

		$name = $this->getUniqueRestorePolicyName($restore_test_name);
		$run_method = RestorePolicyConfig::RUN_METHOD_MANUALLY;
		if ($this->RunMethodAfterBackup->Checked) {
			$run_method = RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP;
		}
		$cfg_restore_policy = [
			'description' => '',
			'enabled' => '1',
			'run_method' => $run_method,
			'schedule' => '',
			'job_levels' => ['F', 'D', 'I'],
			'run_most_once_every' => '0',
			'delay_after_backup_mins' => '0'
		];

		$restore_policy_config = $this->getModule('restore_policy_config');
		$result = $restore_policy_config->setRestorePolicyConfig(
			$name,
			$cfg_restore_policy
		);
		if ($result !== true) {
			$this->showCreateRestoreTestError('Error while saving restore policy.');
			return '';
		}
		$audit = $this->getModule('audit');
		$audit->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_APPLICATION,
			"Create restore policy. Name: $name"
		);
		return $name;
	}

	/**
	 * Get restore test configuration.
	 *
	 * @param string $name restore test name
	 * @param array $verification_rule_sets verification rule set names
	 * @param string $restore_policy restore policy name
	 * @return array restore test configuration or empty array on error
	 */
	private function getRestoreTestConfig(string $name, array $verification_rule_sets, string $restore_policy): array
	{
		// Get default web access parameters from current host config
		$host = $this->User->getDefaultAPIHost();
		$host_config = $this->getModule('host_config');
		$hconfig = $host_config->getHostConfig($host);
		$def_protocol = $hconfig['protocol'] ?? RestoreVerification::DEFAULT_WEB_ACCESS_PROTOCOL;
		$def_address = $hconfig['address'] ?? RestoreVerification::DEFAULT_WEB_ACCESS_ADDRESS;
		$def_port = $hconfig['port'] ?? RestoreVerification::DEFAULT_WEB_ACCESS_PORT;
		$def_allowed_ips = RestoreVerification::DEFAULT_WEB_ALLOWED_IPS;

		return [
			'name' => $name,
			'description' => '',
			'enabled' => '1',
			'source_type' => RestoreTestConfig::SOURCE_TYPE_BACKUP_JOB,
			'source_backup_job' => $this->BackupJob->SelectedValue,
			'source_backup_jobid' => '',
			'source_selection' => RestoreTestConfig::SOURCE_SELECTION_LATEST_SUCCESSFUL,
			'latest_successful_date_from' => '0',
			'latest_successful_date_to' => '0',
			'job_levels' => ['F', 'D', 'I'],
			'restore_scope' => RestoreTestConfig::RESTORE_SCOPE_VERIFICATION_RULES_PATHS,
			'restore_destination' => $this->RestoreDestination->SelectedValue,
			'restore_job' => $this->RestoreJob->SelectedValue,
			'restore_policy' => $restore_policy,
			'web_protocol' => $def_protocol,
			'web_address' => $def_address,
			'web_port' => $def_port,
			'web_allowed_ips' => $def_allowed_ips,
			'verification_method' => RestoreTestConfig::RESTORE_VERIFICATION_METHOD_RULES,
			'verification_rule_sets' => $verification_rule_sets,
			'verification_verify_job' => ''
		];
	}

	/**
	 * Get first resource from Director-specific API result.
	 *
	 * @param array $params API endpoint params
	 * @return string resource name or empty string
	 */
	private function getDefaultDirectorResource(array $params): string
	{
		$resource = '';
		$resources = [];
		$api = $this->getModule('api');
		$result = $api->get($params);
		$sess = $this->getPage()->getSession();
		$dir = $sess->itemAt('director');
		if ($result->error == 0) {
			if (is_array($result->output)) {
				foreach ($result->output as $director => $items) {
					if ($director != $dir) {
						continue;
					}
					$resources = $items;
					break;
				}
			} elseif (is_object($result->output)) {
				$resources = $result->output->{$dir} ?? [];
			}
		}
		if ($resources) {
			sort($resources, SORT_NATURAL | SORT_FLAG_CASE);
			$resource = $resources[0];
		}
		return $resource;
	}

	/**
	 * Get first resource from simple API result.
	 *
	 * @param array $params API endpoint params
	 * @return string resource name or empty string
	 */
	private function getDefaultResource(array $params): string
	{
		$resource = '';
		$api = $this->getModule('api');
		$result = $api->get($params);
		if ($result->error == 0 && is_array($result->output)) {
			$resources = $result->output;
			sort($resources, SORT_NATURAL | SORT_FLAG_CASE);
			if ($resources) {
				$resource = $resources[0];
			}
		}
		return $resource;
	}

	/**
	 * Get default Messages resource.
	 *
	 * @return string Messages resource name or empty string
	 */
	private function getDefaultMessagesResource(): string
	{
		$messages = [];
		$api = $this->getModule('api');
		$result = $api->get(['config', 'dir', 'messages']);
		if ($result->error == 0) {
			for ($i = 0; $i < count($result->output); $i++) {
				$messages[] = $result->output[$i]->Messages->Name;
			}
			sort($messages, SORT_NATURAL | SORT_FLAG_CASE);
		}
		$message = '';
		if ($messages) {
			$message = in_array('Standard', $messages) ? 'Standard' : $messages[0];
		}
		return $message;
	}

	/**
	 * Get restore policy config by name.
	 *
	 * @param string $restore_policy restore policy name
	 * @return array restore policy config
	 */
	private function getRestorePolicyConfig(string $restore_policy): array
	{
		$restore_policy_config = $this->getModule('restore_policy_config');
		return $restore_policy_config->getRestorePolicyConfig($restore_policy);
	}

	/**
	 * Get unique verification rule name.
	 *
	 * @param string $restore_test_name restore test name
	 * @return string unique verification rule name
	 */
	private function getUniqueVerificationRuleName(string $restore_test_name): string
	{
		$verification_rule_config = $this->getModule('verification_rule_config');
		return $this->getUniqueConfigName(
			$verification_rule_config,
			$restore_test_name . ' rules',
			'verificationRuleConfigExists'
		);
	}

	/**
	 * Get unique restore policy name.
	 *
	 * @param string $restore_test_name restore test name
	 * @return string unique restore policy name
	 */
	private function getUniqueRestorePolicyName(string $restore_test_name): string
	{
		$restore_policy_config = $this->getModule('restore_policy_config');
		return $this->getUniqueConfigName(
			$restore_policy_config,
			$restore_test_name . ' policy',
			'restorePolicyConfigExists'
		);
	}

	/**
	 * Get unique configuration name.
	 *
	 * @param object $module config module
	 * @param string $base_name base config name
	 * @param string $exists_method exists method name
	 * @return string unique config name
	 */
	private function getUniqueConfigName(object $module, string $base_name, string $exists_method): string
	{
		$name = $base_name;
		$num = 2;
		while ($module->{$exists_method}($name)) {
			$name = sprintf('%s %d', $base_name, $num);
			$num++;
		}
		return $name;
	}

	/**
	 * Create admin job.
	 *
	 * @param array $rt_config restore test config
	 * @return bool true if admin job created successfully, otherwise false
	 */
	private function createAdminJob(array $rt_config): bool
	{
		$resource = [
			'Client' => $this->getDefaultResource(['clients', 'resnames']),
			'Fileset' => $this->getDefaultDirectorResource(['filesets', 'resnames']),
			'Pool' => $this->getDefaultResource(['pools', 'resnames']),
			'Storage' => $this->getDefaultResource(['storages', 'resnames']),
			'Messages' => $this->getDefaultMessagesResource()
		];
		$job = $this->getAdminJobConfig($rt_config, $resource);
		$plugin_manager = $this->getModule('plugin_manager');
		$plugin_manager->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
			'run',
			'pre-create',
			'Job',
			$job['Name']
		);

		$api = $this->getModule('api');
		$job_config = json_encode($job);
		$result = $api->create(
			['config', 'dir', 'Job', $job['Name']],
			['config' => $job_config]
		);
		$success = ($result->error == 0);
		if ($success) {
			$api->set(['console'], ['reload']);
			$plugin_manager->callPluginActionByType(
				PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
				'run',
				'post-create',
				'Job',
				$job['Name']
			);
		}
		return $success;
	}

	/**
	 * Run restore test admin job now.
	 *
	 * @param string $name restore test name
	 * @return bool true if restore test started successfully, otherwise false
	 */
	private function runRestoreTestNow(string $name): bool
	{
		$result = JobAction::runJobByName($name);
		if ($result->error !== 0) {
			$this->showCreateRestoreTestError('Restore test has been created, but it could not be started.');
			return false;
		}
		return true;
	}

	/**
	 * Show create restore test error.
	 *
	 * @param string $msg error message
	 */
	private function showCreateRestoreTestError(string $msg): void
	{
		$msg = Prado::localize($msg);
		$this->CreateRestoreTestError->Text = $msg;
		$this->CreateRestoreTestError->Display = 'Dynamic';
	}

	/**
	 * Convert text for safe HTML output.
	 *
	 * @param string $text text to escape
	 * @return string safe HTML text
	 */
	private function html(string $text): string
	{
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Check if create destination form is visible.
	 *
	 * @return bool true if create destination form is visible, otherwise false
	 */
	private function isCreateDestinationVisible(): bool
	{
		return ($this->CreateDestinationVisible->Value === '1');
	}

	/**
	 * Check if restore path points to filesystem root.
	 *
	 * @param string $restore_path restore path
	 * @return bool true if restore path is root path, otherwise false
	 */
	private function isRootRestorePath(string $restore_path): bool
	{
		return (trim($restore_path) === '/');
	}

	/**
	 * Show or hide restore path warning.
	 *
	 * @param bool $show true to show warning, false to hide
	 */
	private function showRestorePathWarning(bool $show): void
	{
		$display = $show ? 'Dynamic' : 'None';
		$this->RestorePathWarning->Display = $display;
	}

	/**
	 * Get selected verification rule names.
	 *
	 * @return array selected verification rule names
	 */
	private function getSelectedVerificationRules(): array
	{
		$verification_rules = [];
		for ($i = 0; $i < count($this->VerificationRules->Items); $i++) {
			if ($this->VerificationRules->Items[$i]->Selected) {
				$verification_rules[] = $this->VerificationRules->Items[$i]->Value;
			}
		}
		return $verification_rules;
	}

	/**
	 * Get selected restore verification paths.
	 *
	 * @return array selected paths
	 */
	private function getSelectedPaths(): array
	{
		$paths = json_decode($this->SelectedPaths->Text, true);
		if (!is_array($paths)) {
			$paths = [];
		}
		return $paths;
	}

	/**
	 * Check if any restore verification path is selected.
	 *
	 * @return bool true if at least one path is selected, otherwise false
	 */
	private function hasSelectedPaths(): bool
	{
		$paths = $this->getSelectedPaths();
		return (count($paths) > 0);
	}
}
