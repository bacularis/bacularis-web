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
use Bacularis\Web\Modules\BaculaConfigAction;
use Bacularis\Web\Modules\RestorePolicyConfig;
use Bacularis\Web\Modules\TRestoreVerification;

/**
 * Verification restore policies control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class RestorePolicies extends RestoreTestVerification
{
	use TRestoreVerification;

	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load restore policies list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setRestorePolicyList($sender, $param)
	{
		$restore_policy_config = $this->getModule('restore_policy_config');
		$restore_policies = $restore_policy_config->getConfig();

		$vals = array_values($restore_policies);
		$this->addRestorePolicyRelationInfo($vals);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oRestorePolicies.load_restore_policy_list_cb',
			[$vals]
		);

		// Prepare resource lists
		$this->setScheduleList();
	}

	/**
	 * Add restore policy relations.
	 *
	 * @param array $vals RestorePolicy configuration
	 */
	private function addRestorePolicyRelationInfo(&$vals)
	{
		$usage = $this->getUsageByPolicy();
		for ($i = 0; $i < count($vals); $i++) {
			$name = key_exists('name', $vals[$i]) ? $vals[$i]['name'] : '';
			$used_by = key_exists($name, $usage) ? $usage[$name] : [];
			$vals[$i]['used_by'] = implode(', ', $used_by);
			$vals[$i]['used_by_restore_tests'] = $used_by;
			$vals[$i]['enabled'] = $vals[$i]['enabled'] ?? '0';
		}
	}

	/**
	 * Get restore policy usage grouped by restore policy name.
	 *
	 * @return array restore policy usage indexed by restore policy name
	 */
	private function getUsageByPolicy(): array
	{
		$rtest_config = $this->getModule('restore_test_config');
		$restore_tests = $rtest_config->getConfig();
		return $this->getRestorePolicyUsage($restore_tests);
	}

	/**
	 * Get restore policy usage in restore tests.
	 *
	 * @param array $restore_tests restore tests configuration
	 * @return array restore policy usage indexed by restore policy name
	 */
	private function getRestorePolicyUsage(array $restore_tests): array
	{
		$usage = [];
		foreach ($restore_tests as $restore_test_name => $restore_test) {
			if (!key_exists('restore_policy', $restore_test)) {
				continue;
			}
			$restore_policy = $restore_test['restore_policy'];
			if ($restore_policy === '') {
				continue;
			}
			if (!key_exists($restore_policy, $usage)) {
				$usage[$restore_policy] = [];
			}
			$usage[$restore_policy][] = $restore_test_name;
		}
		return $usage;
	}

	/**
	 * Load data in restore policy modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadRestorePolicyWindow($sender, $param)
	{
		$name = $param->getCallbackParameter() ?? '';
		$restore_policy_config = $this->getModule('restore_policy_config');
		$config = $restore_policy_config->getRestorePolicyConfig($name);
		if (count($config) === 0) {
			$this->setScheduleList();
			return;
		}

		$schedule = $config['schedule'] ?? '';
		$job_levels = $config['job_levels'] ?? [];
		$this->setScheduleList();

		$this->RestorePolicyFullName->Text = $name;
		$this->RestorePolicyDescription->Text = $config['description'] ?? '';
		$enabled = $config['enabled'] ?? '1';
		$this->RestorePolicyEnabled->Checked = ($enabled == '1');

		$run_method = $config['run_method'] ?? RestorePolicyConfig::RUN_METHOD_SCHEDULE;
		$cb = $this->getPage()->getCallbackClient();
		if ($run_method === RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP) {
			$this->RestorePolicyHowToRunAfterSuccessfulBackupRadio->Checked = true;
			$cb->hide('restore_policy_window_how_to_run_schedule');
			$cb->show('restore_policy_window_how_to_run_successful_backup');
		} elseif ($run_method === RestorePolicyConfig::RUN_METHOD_MANUALLY) {
			$this->RestorePolicyHowToRunManuallyRadio->Checked = true;
			$cb->hide('restore_policy_window_how_to_run_schedule');
			$cb->hide('restore_policy_window_how_to_run_successful_backup');
		} else {
			$this->RestorePolicyHowToRunScheduleRadio->Checked = true;
			$cb->show('restore_policy_window_how_to_run_schedule');
			$cb->hide('restore_policy_window_how_to_run_successful_backup');
		}

		if ($schedule !== '') {
			$this->RestorePolicyHowToRunSchedule->SelectedValue = $schedule;
		}

		$this->RestorePolicyHowToRunSuccessfulBackupJobLevels->setSelectedValues($job_levels);
	}

	/**
	 * Save restore policy.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRestorePolicy($sender, $param)
	{
		$rpolicy_config = $this->getModule('restore_policy_config');
		$name = trim($this->RestorePolicyFullName->Text);
		$restore_policy_exists = $rpolicy_config->restorePolicyConfigExists($name);

		$rp_config = [];
		$rp_config['name'] = $name;
		$description = $this->RestorePolicyDescription->Text;
		// INI scalar values are stored on a single line.
		$description = str_replace(["\r\n", "\r", "\n"], ' ', $description);
		$rp_config['description'] = $description;
		$rp_config['enabled'] = $this->RestorePolicyEnabled->Checked ? '1' : '0';
		$rp_config['run_method'] = $this->getRunMethod();
		$rp_config['schedule'] = $this->RestorePolicyHowToRunSchedule->SelectedValue;
		$rp_config['job_levels'] = $this->RestorePolicyHowToRunSuccessfulBackupJobLevels->getSelectedValues();

		$restore_policy_win_type = $this->RestorePolicyWindowType->Value;
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->RestorePolicyWindowError);
		if ($restore_policy_win_type === self::TYPE_ADD_WINDOW && $restore_policy_exists) {
			$msg = 'Restore policy with name \'%s\' already exists.';
			$emsg = sprintf($msg, $name);
			$emsg_html = Miscellaneous::html_value($emsg);
			$cb->update($this->RestorePolicyWindowError, $emsg_html);
			$cb->show($this->RestorePolicyWindowError);
			return;
		}

		$result = $rpolicy_config->setRestorePolicyConfig($name, $rp_config);
		if ($result === true) {
			$cb->callClientFunction('oRestorePolicies.save_restore_policy_cb');
			$action = $restore_policy_exists ? 'Save' : 'Create';
			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"$action restore policy. Name: $name"
			);
			$cb->hide('restore_policy_window');

			$result = $this->updateRestorePolicyAdminJobs($rp_config);

			// Update all backup jobs using restore tests with this policy
			if ($restore_policy_win_type === self::TYPE_EDIT_WINDOW) {
				$result = $this->updateRestorePolicyBackupJobs($rp_config);
				if (!$result) {
					$msg = 'Error while updating jobs related to restore policy.';
					$cb->update($this->RestorePolicyWindowError, $msg);
					$cb->show($this->RestorePolicyWindowError);
				}
				return;
			}
		} else {
			$msg = 'Error while saving restore policy.';
			$cb->update($this->RestorePolicyWindowError, $msg);
			$cb->show($this->RestorePolicyWindowError);
			return;
		}

		// Refresh restore policy
		$this->setRestorePolicyList($sender, $param);

		$this->onSaveRestorePolicy(null);
	}

	/**
	 * On save restore policy event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveRestorePolicy($param)
	{
		$this->raiseEvent('OnSaveRestorePolicy', $this, $param);
	}

	/**
	 * Update backup jobs using restore tests with given restore policy.
	 *
	 * @param array $rp_config restore policy configuration
	 * @return bool true on success, otherwise false
	 */
	private function updateRestorePolicyBackupJobs(array $rp_config): bool
	{
		$ret = true;
		$usage = $this->getUsageByPolicy();
		$restore_tests = $usage[$rp_config['name']] ?? [];
		$rtest_config = $this->getModule('restore_test_config');
		$action = ($rp_config['run_method'] === RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP) ? 'update' : 'remove';
		if ($rp_config['enabled'] != 1) {
			$action = 'remove';
		}
		for ($i = 0; $i < count($restore_tests); $i++) {
			$rt_config = $rtest_config->getRestoreTestConfig($restore_tests[$i]);
			$token = '';
			if ($action == 'update') {
				$token = $this->createRunWebAccessToken($rt_config, $rp_config);
				if (!$token) {
					$ret = false;
					break;
				}
				$rt_config['web_run_token'] = $token;
			}
			$result = $rtest_config->updateRestoreTestConfig(
				$restore_tests[$i],
				['web_run_token' => $token]
			);
			if (!$result) {
				$ret = false;
				break;
			}

			$result = $this->updateBackupJob($rt_config, $action);
			if (!$result) {
				$ret = false;
				break;
			}
		}
		return $ret;
	}

	/**
	 * Update admin jobs using restore tests with given restore policy.
	 *
	 * @param array $rp_config restore policy configuration
	 * @return bool true on success, otherwise false
	 */
	private function updateRestorePolicyAdminJobs(array $rp_config): bool
	{
		$ret = true;
		$usage = $this->getUsageByPolicy();
		$restore_tests = $usage[$rp_config['name']] ?? [];
		$rtest_config = $this->getModule('restore_test_config');
		for ($i = 0; $i < count($restore_tests); $i++) {
			$rt_config = $rtest_config->getRestoreTestConfig($restore_tests[$i]);
			$ret = $this->updateAdminJob($rt_config);
			if (!$ret) {
				break;
			}
		}
		return $ret;
	}

	/**
	 * Update admin job corresponding the restore test.
	 *
	 * @param array $rt_config restore test configuration
	 * @return bool true on success, otherwise false
	 */
	private function updateAdminJob(array $rt_config): bool
	{
		$result = BaculaConfigAction::readResource(
			'dir',
			'Job',
			$rt_config['name']
		);
		if ($result->error != 0) {
			return false;
		}
		$misc = $this->getModule('misc');
		$config = $misc->objectToArray($result->output);
		$resource = [
			'Client' => $config['Client'] ?? '',
			'Fileset' => $config['Fileset'] ?? '',
			'Storage' => $config['Storage'] ?? '',
			'Pool' => $config['Pool'] ?? '',
			'Messages' => $config['Messages'] ?? ''
		];
		$job = $this->getAdminJobConfig($rt_config, $resource);
		$result = BaculaConfigAction::updateResource(
			'dir',
			'Job',
			$job['Name'],
			$job
		);
		return ($result->error == 0);
	}


	/**
	 * Remove restore policies action.
	 * Here is possible to remove one restore policy or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeRestorePolicies($sender, $param)
	{
		$rm_restore_policies = $param->getCallbackParameter();
		$rm_restore_policies = json_decode(json_encode($rm_restore_policies), true);
		$names = [];
		for ($i = 0; $i < count($rm_restore_policies); $i++) {
			$names[] = $rm_restore_policies[$i]['name'];
		}

		$rtest_config = $this->getModule('restore_test_config');
		$restore_tests = $rtest_config->getConfig();
		$usage = $this->getRestorePolicyUsage($restore_tests);
		$used_restore_policies = $this->getSelectedRestorePolicyUsage($usage, $names);
		if (count($used_restore_policies) > 0) {
			$msg = $this->getUsedRestorePoliciesMessage($used_restore_policies);
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oBulkActionsModal.set_error',
				[$msg]
			);
			return;
		}

		$rpolicy_config = $this->getModule('restore_policy_config');
		$result = $rpolicy_config->removeRestorePoliciesConfig($names);
		if ($result === true) {
			$audit = $this->getModule('audit');
			for ($i = 0; $i < count($names); $i++) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove restore policy. Name: {$names[$i]}"
				);
			}
		}

		// Refresh policy list
		$this->setRestorePolicyList($sender, $param);

		$this->onRemoveRestorePolicy(null);
	}

	/**
	 * On remove restore policy event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveRestorePolicy($param)
	{
		$this->raiseEvent('OnRemoveRestorePolicy', $this, $param);
	}

	/**
	 * Get selected restore policies usage grouped by restore policy name.
	 *
	 * @param array $usage restore policy usage
	 * @param array $names restore policy names
	 * @return array selected restore policies usage
	 */
	private function getSelectedRestorePolicyUsage(array $usage, array $names): array
	{
		$used_restore_policies = [];
		for ($i = 0; $i < count($names); $i++) {
			if (!key_exists($names[$i], $usage)) {
				continue;
			}
			$used_restore_policies[$names[$i]] = $usage[$names[$i]];
		}
		return $used_restore_policies;
	}

	/**
	 * Get used restore policies message.
	 *
	 * @param array $used_restore_policies selected restore policies usage
	 * @return string message with restore policies and restore tests
	 */
	private function getUsedRestorePoliciesMessage(array $used_restore_policies): string
	{
		$msg = 'The following restore policies are used by restore tests and cannot be removed:';
		$lines = [];
		foreach ($used_restore_policies as $restore_policy => $restore_tests) {
			$line = $restore_policy . ':';
			for ($i = 0; $i < count($restore_tests); $i++) {
				$line .= "\n - " . $restore_tests[$i];
			}
			$lines[] = $line;
		}
		$msg .= ' Please unassign these restore policies from the restore tests and try removing them again.';
		return $msg . "\n\n" . implode("\n\n", $lines) . "\n\n";
	}

	/**
	 * Set schedule list.
	 */
	private function setScheduleList(): void
	{
		$schedule_list = [];
		$api = $this->getModule('api');
		$schedules = $api->get(['schedules', 'resnames']);
		if ($schedules->error === 0) {
			$schedule_list = $schedules->output;
			sort($schedule_list, SORT_NATURAL | SORT_FLAG_CASE);
		}
		array_unshift($schedule_list, '');
		$data_source = array_combine($schedule_list, $schedule_list);
		$this->RestorePolicyHowToRunSchedule->DataSource = $data_source;
		$this->RestorePolicyHowToRunSchedule->dataBind();
	}

	/**
	 * Get selected run method.
	 *
	 * @return string restore policy run method
	 */
	private function getRunMethod(): string
	{
		$run_method = RestorePolicyConfig::RUN_METHOD_SCHEDULE;
		if ($this->RestorePolicyHowToRunAfterSuccessfulBackupRadio->Checked) {
			$run_method = RestorePolicyConfig::RUN_METHOD_AFTER_BACKUP;
		} elseif ($this->RestorePolicyHowToRunManuallyRadio->Checked) {
			$run_method = RestorePolicyConfig::RUN_METHOD_MANUALLY;
		}
		return $run_method;
	}
}
