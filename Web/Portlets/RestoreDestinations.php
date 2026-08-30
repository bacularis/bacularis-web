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
use Bacularis\Common\Modules\RestoreDestinationCapability;
use Bacularis\Web\Modules\RestoreDestinationConfig;

/**
 * Verification restore destinations control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class RestoreDestinations extends RestoreTestVerification
{
	/**
	 * Modal window types.
	 */
	public const TYPE_ADD_WINDOW = 'add';
	public const TYPE_EDIT_WINDOW = 'edit';

	/**
	 * Set and load restore destinations list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function setRestoreDestinationList($sender, $param)
	{
		$restore_destination_config = $this->getModule('restore_destination_config');
		$restore_destinations = $restore_destination_config->getConfig();

		$vals = array_values($restore_destinations);
		$this->addRestoreDestinationRelationInfo($vals);

		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oRestoreDestinations.load_restore_destination_list_cb',
			[$vals]
		);

		$this->setRestoreClientList();
	}

	/**
	 * Add restore destination relations.
	 *
	 * @param array $vals RestoreDestination configuration
	 */
	private function addRestoreDestinationRelationInfo(&$vals)
	{
		$restore_test_config = $this->getModule('restore_test_config');
		$restore_tests = $restore_test_config->getConfig();
		$usage = $this->getRestoreDestinationUsage($restore_tests);

		for ($i = 0; $i < count($vals); $i++) {
			$capabilities = $vals[$i]['capabilities'] ?? [];
			if (!is_array($capabilities)) {
				$capabilities = [$capabilities];
			}
			$capability_sets = array_map(
				fn ($item) => RestoreDestinationCapability::getDescription($item),
				$capabilities
			);
			$vals[$i]['capability_sets'] = implode(', ', $capability_sets);
			$name = key_exists('name', $vals[$i]) ? $vals[$i]['name'] : '';
			$used_by = key_exists($name, $usage) ? $usage[$name] : [];
			$vals[$i]['used_by'] = implode(', ', $used_by);
			$vals[$i]['used_by_restore_tests'] = $used_by;
			$vals[$i]['enabled'] = $vals[$i]['enabled'] ?? '0';
			$vals[$i]['restore_mode'] = $vals[$i]['restore_mode'] ?? RestoreDestinationConfig::RESTORE_MODE_PREFIXED_PATH;
			$vals[$i]['restore_client'] = $vals[$i]['restore_client'] ?? '';
			$vals[$i]['restore_path_prefixed'] = $vals[$i]['restore_path_prefixed'] ?? '';
			$vals[$i]['restore_path_original'] = $vals[$i]['restore_path_original'] ?? '/';
			$vals[$i]['restore_path'] = $this->getRestorePath($vals[$i]);
		}
	}

	/**
	 * Get restore destination usage in restore tests.
	 *
	 * @param array $restore_tests restore tests configuration
	 * @return array restore destination usage indexed by restore destination name
	 */
	private function getRestoreDestinationUsage(array $restore_tests): array
	{
		$usage = [];
		foreach ($restore_tests as $restore_test_name => $restore_test) {
			if (!key_exists('restore_destination', $restore_test)) {
				continue;
			}
			$restore_destination = $restore_test['restore_destination'];
			if ($restore_destination === '') {
				continue;
			}
			if (!key_exists($restore_destination, $usage)) {
				$usage[$restore_destination] = [];
			}
			$usage[$restore_destination][] = $restore_test_name;
		}
		return $usage;
	}

	/**
	 * Load data in restore destination modal window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function loadRestoreDestinationWindow($sender, $param)
	{
		$name = $param->getCallbackParameter() ?? '';
		$restore_destination_config = $this->getModule('restore_destination_config');
		$config = $restore_destination_config->getRestoreDestinationConfig($name);
		if (count($config) === 0) {
			$this->setRestoreClientList();
			return;
		}

		$this->setRestoreClientList();

		$this->RestoreDestinationFullName->Text = $name;
		$this->RestoreDestinationDescription->Text = $config['description'] ?? '';
		$enabled = $config['enabled'] ?? '1';
		$this->RestoreDestinationEnabled->Checked = ($enabled == '1');
		$this->RestoreDestinationRestoreTargetClient->SelectedValue = $config['restore_client'] ?? '';

		$restore_mode = $config['restore_mode'] ?? RestoreDestinationConfig::RESTORE_MODE_PREFIXED_PATH;
		$cb = $this->getPage()->getCallbackClient();
		if ($restore_mode === RestoreDestinationConfig::RESTORE_MODE_ORIGINAL_PATH) {
			$this->RestoreDestinationRestoreModeOriginalPathRadio->Checked = true;
			$this->RestoreDestinationRestoreModeSafePrefixedRadio->Checked = false;
			$cb->hide('restore_destination_window_restore_mode_safe_prefixed');
			$cb->show('restore_destination_window_restore_mode_original_path');
		} else {
			$this->RestoreDestinationRestoreModeSafePrefixedRadio->Checked = true;
			$this->RestoreDestinationRestoreModeOriginalPathRadio->Checked = false;
			$cb->show('restore_destination_window_restore_mode_safe_prefixed');
			$cb->hide('restore_destination_window_restore_mode_original_path');
		}

		$this->RestoreDestinationRestoreModeSafePrefixed->Text = $config['restore_path_prefixed'] ?? '';
		$this->RestoreDestinationRestoreModeOriginalPath->Text = $config['restore_path_original'] ?? '/';
		$isolated_confirm = $config['isolated_test_environment_confirmation'] ?? '0';
		$this->RestoreDestinationRestoreModeOriginalPathConfirm->Checked = ($isolated_confirm == '1');

		$capabilities = $config['capabilities'] ?? [];
		if (!is_array($capabilities)) {
			$capabilities = [$capabilities];
		}
		$this->RestoreDestinationCapabilitiesFile->Checked = in_array(
			RestoreDestinationCapability::FILE_CHECK,
			$capabilities
		);
		$this->RestoreDestinationCapabilitiesBaculaVerify->Checked = in_array(
			RestoreDestinationCapability::NATIVE_VERIFY,
			$capabilities
		);
		$this->RestoreDestinationCapabilitiesPlugin->Checked = in_array(
			RestoreDestinationCapability::PLUGIN_CHECK,
			$capabilities
		);
		$this->RestoreDestinationCapabilitiesCustom->Checked = in_array(
			RestoreDestinationCapability::CUSTOM_CHECK,
			$capabilities
		);
	}

	/**
	 * Save restore destination.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function saveRestoreDestination($sender, $param)
	{
		$restore_destination_config = $this->getModule('restore_destination_config');
		$name = trim($this->RestoreDestinationFullName->Text);
		$restore_destination_exists = $restore_destination_config->restoreDestinationConfigExists($name);

		$cfg_restore_destination = [];
		$cfg_restore_destination['description'] = str_replace(["\r", "\n"], ['', ' '], $this->RestoreDestinationDescription->Text);
		$cfg_restore_destination['enabled'] = $this->RestoreDestinationEnabled->Checked ? '1' : '0';
		$cfg_restore_destination['restore_client'] = $this->RestoreDestinationRestoreTargetClient->SelectedValue;
		$restore_mode = $this->getRestoreMode();
		$cfg_restore_destination['restore_mode'] = $restore_mode;
		$cfg_restore_destination['restore_path_prefixed'] = $this->RestoreDestinationRestoreModeSafePrefixed->Text;
		$cfg_restore_destination['restore_path_original'] = $this->RestoreDestinationRestoreModeOriginalPath->Text;
		$original_path_confirmed = (
			$restore_mode === RestoreDestinationConfig::RESTORE_MODE_ORIGINAL_PATH &&
			$this->RestoreDestinationRestoreModeOriginalPathConfirm->Checked
		);
		$cfg_restore_destination['isolated_test_environment_confirmation'] = $original_path_confirmed ? '1' : '0';
		$cfg_restore_destination['capabilities'] = $this->getSelectedCapabilities();

		$restore_destination_win_type = $this->RestoreDestinationWindowType->Value;
		$cb = $this->getPage()->getCallbackClient();
		$cb->hide($this->RestoreDestinationWindowError);
		if ($restore_destination_win_type === self::TYPE_ADD_WINDOW && $restore_destination_exists) {
			$msg = 'Restore destination with name \'%s\' already exists.';
			$emsg = sprintf($msg, $name);
			$cb->update($this->RestoreDestinationWindowError, $emsg);
			$cb->show($this->RestoreDestinationWindowError);
			return;
		}

		$result = $restore_destination_config->setRestoreDestinationConfig(
			$name,
			$cfg_restore_destination
		);
		if ($result === true) {
			$cb->callClientFunction(
				'oRestoreDestinations.save_restore_destination_cb'
			);
			$action = $restore_destination_exists ? 'Save' : 'Create';
			$audit = $this->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				"$action restore destination. Name: $name"
			);
			$cb->hide('restore_destination_window');
		} else {
			$msg = 'Error while saving restore destination.';
			$cb->update($this->RestoreDestinationWindowError, $msg);
			$cb->show($this->RestoreDestinationWindowError);
			return;
		}

		// Refresh restore destinations
		$this->setRestoreDestinationList($sender, $param);

		$this->onSaveRestoreDestination(null);
	}

	/**
	 * On save restore destination event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onSaveRestoreDestination($param)
	{
		$this->raiseEvent('OnSaveRestoreDestination', $this, $param);
	}

	/**
	 * Remove restore destinations action.
	 * Here is possible to remove one restore destination or many.
	 * This action is linked with table bulk actions.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param callback parameter
	 */
	public function removeRestoreDestinations($sender, $param)
	{
		$misc = $this->getModule('misc');
		$rm_restore_destinations = $param->getCallbackParameter();
		$rm_restore_destinations = $misc->objectToArray($rm_restore_destinations);
		$names = [];
		for ($i = 0; $i < count($rm_restore_destinations); $i++) {
			$names[] = $rm_restore_destinations[$i]['name'];
		}

		$restore_test_config = $this->getModule('restore_test_config');
		$restore_tests = $restore_test_config->getConfig();
		$usage = $this->getRestoreDestinationUsage($restore_tests);
		$used_restore_destinations = $this->getSelectedRestoreDestinationUsage($usage, $names);
		if (count($used_restore_destinations) > 0) {
			$msg = $this->getUsedRestoreDestinationsMessage($used_restore_destinations);
			$cb = $this->getPage()->getCallbackClient();
			$cb->callClientFunction(
				'oBulkActionsModal.set_error',
				[$msg]
			);
			return;
		}

		$restore_destination_config = $this->getModule('restore_destination_config');
		$result = $restore_destination_config->removeRestoreDestinationsConfig($names);
		if ($result === true) {
			$audit = $this->getModule('audit');
			for ($i = 0; $i < count($names); $i++) {
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_APPLICATION,
					"Remove restore destination. Name: {$names[$i]}"
				);
			}
		}

		// Refresh restore destinations
		$this->setRestoreDestinationList($sender, $param);

		$this->onRemoveRestoreDestination(null);
	}

	/**
	 * On remove restore destination event.
	 *
	 * @param mixed $param event parameter
	 */
	public function onRemoveRestoreDestination($param)
	{
		$this->raiseEvent('OnRemoveRestoreDestination', $this, $param);
	}

	/**
	 * Get selected restore destinations usage grouped by restore destination name.
	 *
	 * @param array $usage restore destination usage
	 * @param array $names restore destination names
	 * @return array selected restore destinations usage
	 */
	private function getSelectedRestoreDestinationUsage(array $usage, array $names): array
	{
		$used_restore_destinations = [];
		for ($i = 0; $i < count($names); $i++) {
			if (!key_exists($names[$i], $usage)) {
				continue;
			}
			$used_restore_destinations[$names[$i]] = $usage[$names[$i]];
		}
		return $used_restore_destinations;
	}

	/**
	 * Get used restore destinations message.
	 *
	 * @param array $used_restore_destinations selected restore destinations usage
	 * @return string message with restore destinations and restore tests
	 */
	private function getUsedRestoreDestinationsMessage(array $used_restore_destinations): string
	{
		$msg = 'The following restore destinations are used by restore tests and cannot be removed:';
		$lines = [];
		foreach ($used_restore_destinations as $restore_destination => $restore_tests) {
			$line = '<strong>' . htmlspecialchars($restore_destination, ENT_QUOTES, 'UTF-8') . ':</strong>';
			for ($i = 0; $i < count($restore_tests); $i++) {
				$restore_test = htmlspecialchars($restore_tests[$i], ENT_QUOTES, 'UTF-8');
				$line .= '<br /> - ' . $restore_test;
			}
			$lines[] = $line;
		}
		$msg .= ' Please unassign these restore destinations from the restore tests and try removing them again.';
		return $msg . '<hr />' . implode('<br /><br />', $lines) . '<hr />';
	}

	/**
	 * Set restore client list.
	 */
	private function setRestoreClientList(): void
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
		$this->RestoreDestinationRestoreTargetClient->DataSource = $data_source;
		$this->RestoreDestinationRestoreTargetClient->dataBind();
	}

	/**
	 * Get restore mode.
	 *
	 * @return string restore destination mode
	 */
	private function getRestoreMode(): string
	{
		$restore_mode = RestoreDestinationConfig::RESTORE_MODE_PREFIXED_PATH;
		if ($this->RestoreDestinationRestoreModeOriginalPathRadio->Checked) {
			$restore_mode = RestoreDestinationConfig::RESTORE_MODE_ORIGINAL_PATH;
		}
		return $restore_mode;
	}

	/**
	 * Get selected capabilities.
	 *
	 * @return array selected capability names
	 */
	private function getSelectedCapabilities(): array
	{
		$capabilities = [];
		if ($this->RestoreDestinationCapabilitiesFile->Checked) {
			$capabilities[] = RestoreDestinationCapability::FILE_CHECK;
		}
		if ($this->RestoreDestinationCapabilitiesBaculaVerify->Checked) {
			$capabilities[] = RestoreDestinationCapability::NATIVE_VERIFY;
		}
		if ($this->RestoreDestinationCapabilitiesPlugin->Checked) {
			$capabilities[] = RestoreDestinationCapability::PLUGIN_CHECK;
		}
		if ($this->RestoreDestinationCapabilitiesCustom->Checked) {
			$capabilities[] = RestoreDestinationCapability::CUSTOM_CHECK;
		}
		return $capabilities;
	}

	/**
	 * Get restore path for destination list.
	 *
	 * @param array $config restore destination configuration
	 * @return string restore path
	 */
	private function getRestorePath(array $config): string
	{
		$restore_path = $config['restore_path_prefixed'] ?? '';
		if ($config['restore_mode'] === RestoreDestinationConfig::RESTORE_MODE_ORIGINAL_PATH) {
			$restore_path = $config['restore_path_original'] ?? '/';
		}
		return $restore_path;
	}
}
