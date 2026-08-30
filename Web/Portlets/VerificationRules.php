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
use Bacularis\Common\Modules\PluginConfigBase;

/**
 * Verification rules control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class VerificationRules extends RestoreTestVerification
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load verification rule list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setVerificationRuleList($sender, $param)
	{
		$verification_rule_config = $this->getModule('verification_rule_config');
		$verification_rules = $verification_rule_config->getConfig();

		$vals = array_values($verification_rules);
		$this->addVerificationRuleRelationInfo($vals);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oVerificationRules.load_verification_rule_list_cb',
			[$vals]
		);
	}

	/**
	 * Add verification rule relations.
	 *
	 * @param array $vals verification rule configuration
	 */
	private function addVerificationRuleRelationInfo(&$vals)
	{
		$restore_test_config = $this->getModule('restore_test_config');
		$restore_tests = $restore_test_config->getConfig();
		$usage = $this->getVerificationRuleUsage($restore_tests);

		for ($i = 0; $i < count($vals); $i++) {
			$vals[$i]['checkers'] = $checkers = [];
			if (key_exists('rules', $vals[$i]) && is_array($vals[$i]['rules'])) {
				foreach ($vals[$i]['rules'] as $path => $props) {
					for ($j = 0; $j < count($props); $j++) {
						$checkers[$props[$j]['checker']] = 1;
					}
				}
			}
			$vals[$i]['checkers'] = array_keys($checkers);
			$vals[$i]['enabled'] = key_exists('enabled', $vals[$i]) ? $vals[$i]['enabled'] : '0';
			$name = key_exists('name', $vals[$i]) ? $vals[$i]['name'] : '';
			$used_by = key_exists($name, $usage) ? $usage[$name] : [];
			$vals[$i]['used_by'] = implode(', ', $used_by);
			$vals[$i]['used_by_restore_tests'] = $used_by;
		}
	}

	/**
	 * Get verification rule usage in restore tests.
	 *
	 * @param array $restore_tests restore tests configuration
	 * @return array verification rule usage indexed by verification rule name
	 */
	private function getVerificationRuleUsage(array $restore_tests): array
	{
		$usage = [];
		foreach ($restore_tests as $restore_test_name => $restore_test) {
			if (!key_exists('verification_rule_sets', $restore_test) || !is_array($restore_test['verification_rule_sets'])) {
				continue;
			}
			$rule_sets = $restore_test['verification_rule_sets'];
			for ($i = 0; $i < count($rule_sets); $i++) {
				if (!key_exists($rule_sets[$i], $usage)) {
					$usage[$rule_sets[$i]] = [];
				}
				$usage[$rule_sets[$i]][] = $restore_test_name;
			}
		}
		return $usage;
	}

	/**
	 * Load data in verification rule modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadVerificationRuleWindow($sender, $param)
	{
		$this->loadCheckers();
		$name = $param->getCallbackParameter() ?? '';
		$verification_rule_config = $this->getModule('verification_rule_config');
		$config = $verification_rule_config->getVerificationRuleConfig($name);

		$rules = [];
		if (count($config) > 0) {
			$this->VerificationRuleFullName->Text = $name;
			$this->VerificationRuleDescription->Text = $config['description'] ?? '';
			$enabled = $config['enabled'] ?? '1';
			$this->VerificationRuleEnabled->Checked = ($enabled == '1');
			$rules = $config['rules'] ?? [];
		}
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oVerificationRules.load_verification_rule_window_cb',
			[$rules]
		);
	}

	/**
	 * Load checkers.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	private function loadCheckers()
	{
		$page = $this->getPage();
		if (!$page->IsCallBack) {
			return;
		}
		$plugin_config = $this->getModule('plugin_config');
		$plugins = $plugin_config->getPlugins(
			PluginConfigBase::PLUGIN_TYPE_VERIFICATION
		);

		$checkers = [];
		foreach ($plugins as $cls => $props) {
			$ops = $cls::getOperators();
			$attr = $cls::getAttribute();
			$vals = $cls::getValues();
			$checkers[$cls] = ['operators' => $ops, 'values' => $vals, 'attr' => $attr];
		}

		$cb = $page->getCallbackClient();
		$cb->callClientFunction(
			'oVerificationRulePathList.set_checkers',
			[$checkers]
		);
	}

	/**
	 * Load list of Bacula clients.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadClientList($sender, $param): void
	{
		$api = $this->getModule('api');
		$result = $api->get(['clients']);
		if ($result->error != 0) {
			return;
		}
		usort($result->output, fn ($a, $b) => strcasecmp($a->name, $b->name));
		$this->VerificationPathsClient->DataSource = $result->output;
		$this->VerificationPathsClient->dataBind();
	}

	/**
	 * Load list of Bacula jobs for selected client.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadJobList($sender, $param): void
	{
		$api = $this->getModule('api');
		$clientid = $this->VerificationPathsClient->getSelectedValue();
		$query = [
			'clientid' => $clientid,
			'limit' => 500,
			'type' => 'B'
		];
		$qs = '?' . http_build_query($query);
		$result = $api->get(['jobs', $qs]);
		if ($result->error != 0) {
			return;
		}
		$misc = $this->getModule('misc');
		$labels = $values = [];
		for ($i = 0; $i < count($result->output); $i++) {
			$labels[] = sprintf(
				'[%s] [%s] %s',
				$result->output[$i]->jobid,
				$misc->getJobLevelLong($result->output[$i]->level) ?? '',
				$result->output[$i]->name
			);
			$values[] = $result->output[$i]->jobid;
		}
		$this->VerificationPathsJob->DataSource = array_combine($values, $labels);
		$this->VerificationPathsJob->dataBind();
	}

	/**
	 * Save rule form data.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveVerificationRule($sender, $param): void
	{
		$rule_config = [];
		$misc = $this->getModule('misc');
		$parameter = $param->getCallbackParameter() ?? [];
		$rules = $parameter->rules ?? [];
		$name = trim($this->VerificationRuleFullName->Text);
		$rule_config = $this->getModule('verification_rule_config');
		$rule_exists = $rule_config->verificationRuleConfigExists($name);

		$cfg_rule = [];
		$cfg_rule['description'] = str_replace(["\r", "\n"], ['', ' '], $this->VerificationRuleDescription->Text);
		$cfg_rule['enabled'] = $this->VerificationRuleEnabled->Checked ? '1' : '0';
		$cfg_rule['rules'] = array_filter($misc->objectToArray($rules), function($item) {
			$ret = true;
			foreach ($item as $rule) {
				if (!key_exists('checker', $rule) ||
				    !key_exists('operator', $rule) ||
				    !key_exists('value', $rule)) {
				    	$ret = false;
					break;
				}
			}
			return $ret;
		});

		$rule_win_type = $this->VerificationRuleWindowType->Value;
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->VerificationRuleWindowError);
		if ($rule_win_type === self::TYPE_ADD_WINDOW && $rule_exists) {
			$msg = 'Verification rule with name \'%s\' already exists.';
			$emsg = sprintf($msg, $name);
			$cb->update($this->VerificationRuleWindowError, $emsg);
			$cb->show($this->VerificationRuleWindowError);
			return;
		}

		$result = $rule_config->setVerificationRuleConfig($name, $cfg_rule);
		if ($result === true) {
			$cb->callClientFunction('oVerificationRules.save_verification_rule_cb');
			$action = $rule_exists ? 'Save' : 'Create';
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"$action verification rule. Name: $name"
			);
			$cb->hide('verification_rule_window');
		} else {
			$msg = 'Error while saving verification rule.';
			$cb->update($this->VerificationRuleWindowError, $msg);
			$cb->show($this->VerificationRuleWindowError);
			return;
		}

		// Refresh verification rules
		$this->setVerificationRuleList($sender, $param);

		$this->onSaveVerificationRules(null);
	}

	/**
	 * On save verification rules event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveVerificationRules($param)
	{
		$this->raiseEvent('OnSaveVerificationRules', $this, $param);
	}

	/**
	 * Load backup job file list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadJobFiles($sender, $param)
	{
		$this->BackupFiles->setJobId($this->VerificationPathsJob->SelectedValue);
		$this->BackupFiles->loadFileList($sender, $param);
	}

	/**
	 * Clear backup job file list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function clearJobFiles($sender, $param)
	{
		$this->BackupFiles->setJobId(0);
		$this->BackupFiles->clearFileList($sender, $param);
	}

	/**
	 * Remove verification rules action.
	 * Here is possible to remove one verification rule or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeVerificationRules($sender, $param): void
	{
		$rm_verification_rules = $param->getCallbackParameter();
		$misc = $this->getModule('misc');
		$rm_verification_rules = $misc->objectToArray($rm_verification_rules);
		$names = [];
		for ($i = 0; $i < count($rm_verification_rules); $i++) {
			$names[] = $rm_verification_rules[$i]['name'];
		}

		$restore_test_config = $this->getModule('restore_test_config');
		$restore_tests = $restore_test_config->getConfig();
		$usage = $this->getVerificationRuleUsage($restore_tests);
		$used_rules = $this->getSelectedVerificationRuleUsage($usage, $names);
		if (count($used_rules) > 0) {
			$msg = $this->getUsedVerificationRulesMessage($used_rules);
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oBulkActionsModal.set_error',
				[$msg]
			);
			return;
		}

		$verification_rule_config = $this->getModule('verification_rule_config');
		$result = true;
		for ($i = 0; $i < count($names); $i++) {
			$ret = $verification_rule_config->removeVerificationRuleConfig($names[$i]);
			$result = ($result && $ret);
		}
		if ($result === true) {
			$audit = $this->getModule('audit');
			for ($i = 0; $i < count($names); $i++) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove verification rule. Name: {$names[$i]}"
				);
			}
		}

		// Refresh verification rules
		$this->setVerificationRuleList($sender, $param);

		$this->onRemoveVerificationRules(null);
	}

	/**
	 * On remove verification rules event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveVerificationRules($param)
	{
		$this->raiseEvent('OnRemoveVerificationRules', $this, $param);
	}

	/**
	 * Get selected verification rules usage grouped by verification rule name.
	 *
	 * @param array $usage verification rule usage
	 * @param array $names verification rule names
	 * @return array selected verification rules usage
	 */
	private function getSelectedVerificationRuleUsage(array $usage, array $names): array
	{
		$used_rules = [];
		for ($i = 0; $i < count($names); $i++) {
			if (!key_exists($names[$i], $usage)) {
				continue;
			}
			$used_rules[$names[$i]] = $usage[$names[$i]];
		}
		return $used_rules;
	}

	/**
	 * Get used verification rules message.
	 *
	 * @param array $used_rules selected verification rules usage
	 * @return string message with verification rules and restore tests
	 */
	private function getUsedVerificationRulesMessage(array $used_rules): string
	{
		$msg = 'The following verification rules are used by restore tests and cannot be removed:';
		$lines = [];
		foreach ($used_rules as $verification_rule => $restore_tests) {
			$line = '<strong>' . htmlspecialchars($verification_rule, ENT_QUOTES, 'UTF-8') . ':</strong>';
			for ($i = 0; $i < count($restore_tests); $i++) {
				$restore_test = htmlspecialchars($restore_tests[$i], ENT_QUOTES, 'UTF-8');
				$line .= '<br /> - ' . $restore_test;
			}
			$lines[] = $line;
		}
		return $msg . '<hr />' . implode('<br /><br />', $lines) . '<hr />';
	}
}
