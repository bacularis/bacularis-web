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

namespace Bacularis\Web\Modules;

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\Errors\GenericError;
use Bacularis\Common\Modules\IBacularisVerificationDataPlugin;
use Bacularis\Common\Modules\Miscellaneous;
use Bacularis\Common\Modules\RestoreVerification;

/**
 * Web access restore verification module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebAccessRestoreVerification extends WebModule
{
	/**
	 * Web access restore verification actions.
	 */
	public const ACTION_FINALIZE_NAME = 'finalize';
	public const ACTION_FINALIZE_DESC = 'Finalize';

	/**
	 * Execute web access restore verification command.
	 *
	 * @param array $config web access configuration
	 * @param string $action action name
	 * @param array $params action parameters
	 * @param string $token current web access token
	 * @return null|object command result object or null if action not found
	 */
	public function executeCommand(array $config, string $action, array $params, string $token): ?object
	{
		if ($action !== self::ACTION_FINALIZE_NAME) {
			return null;
		}

		$token_removed = false;
		try {
			$result = $this->finalize($config);
		} finally {
			if (Miscellaneous::isValidWebAccessToken($token)) {
				$web_access_config = $this->getModule('web_access_config');
				$token_removed = $web_access_config->removeWebAccessConfig($token);
			}
		}
		if (!$token_removed) {
			$message = 'Unable to remove one-time Restore Verification Web Access token.';
			return $this->createErrorResult($message);
		}
		return $result;
	}

	/**
	 * Finalize Restore Verification result.
	 *
	 * @param array $config web access configuration
	 * @return object command result
	 */
	private function finalize(array $config): object
	{
		$action_params = [];
		if (key_exists('action_params', $config) && is_array($config['action_params'])) {
			$action_params = $config['action_params'];
		}
		if (!key_exists('test_id', $action_params) || !is_string($action_params['test_id']) || !RestoreVerification::isValidTestId($action_params['test_id'])) {
			$message = 'Invalid Restore Verification test identifier in Web Access configuration.';
			return $this->createErrorResult($message);
		}
		$host_pattern = '/^' . HostConfig::HOST_NAME_PATTERN . '$/D';
		if (!key_exists('api_host', $action_params) || !is_string($action_params['api_host']) || preg_match($host_pattern, $action_params['api_host']) !== 1) {
			$message = 'Invalid API host in Restore Verification Web Access configuration.';
			return $this->createErrorResult($message);
		}

		$test_id = $action_params['test_id'];
		$api_host = $action_params['api_host'];
		$api = $this->getModule('api');
		$endpoint = ['jobs', 'restore', 'verify', 'result', $test_id];
		$result = $api->get($endpoint, $api_host, false);
		if (!is_object($result)) {
			$message = 'Invalid response received while getting the Restore Verification result.';
			return $this->createErrorResult($message);
		}
		if (!property_exists($result, 'error') || $result->error != GenericError::ERROR_NO_ERRORS || !property_exists($result, 'output')) {
			$this->logAPIError($result, 'Unable to get the Restore Verification result.');
			return $result;
		}

		$history_items = $this->prepareHistoryItems($result->output);
		if ($history_items === null) {
			$message = 'Invalid Restore Verification result structure.';
			return $this->createErrorResult($message);
		}
		if (!$this->persistHistory($history_items)) {
			$message = 'Unable to save Restore Verification history.';
			return $this->createErrorResult($message);
		}

		$delete_result = $api->remove($endpoint, $api_host, false);
		if (!is_object($delete_result)) {
			$message = 'Invalid response received while deleting the Restore Verification result.';
			return $this->createErrorResult($message);
		}
		if (!property_exists($delete_result, 'error') || $delete_result->error != GenericError::ERROR_NO_ERRORS) {
			$this->logAPIError($delete_result, 'Unable to delete the Restore Verification result.');
			return $delete_result;
		}
		return $result;
	}

	/**
	 * Prepare Restore Verification history items.
	 *
	 * @param mixed $output Restore Verification result
	 * @return null|array history items or null on invalid structure
	 */
	private function prepareHistoryItems($output): ?array
	{
		if (!is_array($output) && !is_object($output)) {
			return null;
		}
		$misc = $this->getModule('misc');
		$result = $misc->objectToArray($output);
		if (!is_array($result)) {
			return null;
		}

		$items = [];
		foreach ($result as $test_name => $paths) {
			$test_name = (string) $test_name;
			if ($test_name === '' || !is_array($paths)) {
				return null;
			}
			foreach ($paths as $path => $checkers) {
				$path = (string) $path;
				if ($path === '' || !is_array($checkers)) {
					return null;
				}
				foreach ($checkers as $checker_name => $configs) {
					$checker_name = (string) $checker_name;
					if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $checker_name) || !is_array($configs)) {
						return null;
					}
					$checker = sprintf('\\Bacularis\\Common\\Plugins\\%s', $checker_name);
					if (!is_subclass_of($checker, IBacularisVerificationDataPlugin::class)) {
						return null;
					}
					$history_size = $checker::getHistorySize();
					if ($history_size < 1) {
						return null;
					}
					foreach ($configs as $config_hash => $states) {
						$config_hash = (string) $config_hash;
						if (!preg_match('/^[a-f0-9]{64}$/D', $config_hash) || !is_array($states)) {
							return null;
						}
						foreach ($states as $state) {
							if (!is_array($state)) {
								return null;
							}
							$items[] = [
								'test_name' => $test_name,
								'path' => $path,
								'checker' => $checker_name,
								'config_hash' => $config_hash,
								'state' => $state,
								'history_size' => $history_size
							];
						}
					}
				}
			}
		}
		return $items;
	}

	/**
	 * Persist Restore Verification history items.
	 *
	 * @param array $items history items
	 * @return bool true on success, otherwise false
	 */
	private function persistHistory(array $items): bool
	{
		$history = $this->getModule('restore_verification_history_config');
		foreach ($items as $item) {
			$saved = $history->appendHistory(
				$item['test_name'],
				$item['path'],
				$item['checker'],
				$item['config_hash'],
				$item['state'],
				$item['history_size']
			);
			if (!$saved) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Create command error result.
	 *
	 * @param string $message error message
	 * @return object error result
	 */
	private function createErrorResult(string $message): object
	{
		$result = new \stdClass();
		$result->output = $message;
		$result->error = GenericError::ERROR_INTERNAL_ERROR;
		$this->logAPIError($result, $message);
		return $result;
	}

	/**
	 * Log Web Access API error.
	 *
	 * @param mixed $result API result
	 * @param string $message error message
	 * @return void
	 */
	private function logAPIError($result, string $message): void
	{
		$output = '';
		if (is_object($result) && property_exists($result, 'output') && is_scalar($result->output)) {
			$output = (string) $result->output;
		}
		if ($output !== '' && $output !== $message) {
			$message = sprintf('%s %s', $message, $output);
		}
		$audit = $this->getModule('audit');
		$audit->audit(AuditLog::TYPE_ERROR, AuditLog::CATEGORY_APPLICATION, $message);
	}
}
