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

use Bacularis\Common\Modules\AuditLog;
use Bacularis\Common\Modules\BaculumPage;
use Bacularis\Web\Modules\WebAccessConfig;

/**
 * Web access response page.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Page
 */
class WebAccess extends BaculumPage
{
	/**
	 * Web access error codes.
	 */
	private const ERROR_UNKNOWN = -1;
	private const ERROR_NO_ERROR = 0;
	private const ERROR_INVALID_LINK = 1;
	private const ERROR_LINK_EXPIRED = 2;
	private const ERROR_NO_USE_LEFT = 3;
	private const ERROR_ACCESS_DENIED = 4;
	private const ERROR_ACTION_FAILED = 5;

	/**
	 * Web access error messages.
	 */
	private const MSG_ERROR_UNKNOWN = 'Unknown error';
	private const MSG_NO_ERROR = 'OK';
	private const MSG_INVALID_LINK = 'Invalid web access link';
	private const MSG_LINK_EXPIRED = 'Link access is expired';
	private const MSG_NO_USE_LEFT = 'There is not any execution left';
	private const MSG_ACCESS_DENIED = 'Access denied';
	private const MSG_ACTION_FAILED = 'Action failed. See logs.';

	public function onInit($param)
	{
		parent::onInit($param);
		$token = $this->Request->contains('token') ? $this->Request->itemAt('token') : '';
		$this->runAction($token);
	}

	/**
	 * Main run web access action command.
	 * It executes command connected with given token and sends response
	 * to the web client.
	 *
	 * @param string $token token value
	 */
	private function runAction(string $token)
	{
		$web_access_config = $this->getModule('web_access_config');
		$config = $web_access_config->getWebAccessConfig($token);
		$result = $this->verifyConfig($config);
		if ($result['error'] === self::ERROR_NO_ERROR) {
			$state = $this->executeAction($config);
			if (!$state) {
				$result = [
					'error' => self::ERROR_ACTION_FAILED,
					'message' => self::MSG_ACTION_FAILED
				];
			}
			$this->postAction($token, $config);
		}
		$audit = $this->getModule('audit');
		if ($result['error'] === self::ERROR_NO_ERROR) {
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				sprintf(
					'Web access action started successfully: Action: %s, Params: %s',
					$config['action'],
					json_encode($config['action_params'])
				)
			);
		} else {
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_SECURITY,
				sprintf(
					'Starting web access action failed. Error: %d, Message: %s, Action: %s, Params: %s',
					$result['error'],
					$result['message'],
					$config['action'] ?? '-',
					json_encode($config['action_params'] ?? '{}')
				)
			);
		}
		$this->sendResponse($result);
	}

	/**
	 * Send response with the action result to web client.
	 * NOTE: This method exists and ends application execution.
	 *
	 * @param array $result action result
	 */
	private function sendResponse(array $result): void
	{
		$this->Response->appendHeader('Content-Type: application/json');
		echo json_encode($result);
		$this->Application->completeRequest();
		exit();
	}

	/**
	 * Validate the request by verifying with the web access configuration.
	 *
	 * @param array $config web access config
	 * @return array validation result (error number and message)
	 */
	private function verifyConfig(array $config): array
	{
		$result = ['message' => self::MSG_ERROR_UNKNOWN, 'error' => self::ERROR_UNKNOWN];
		if (count($config) == 0) {
			// Link is invalid
			$result['message'] = self::MSG_INVALID_LINK;
			$result['error'] = self::ERROR_INVALID_LINK;
		} elseif (!$this->verifyTimeMethod($config)) {
			// Link is valid but expired
			$result['message'] = self::MSG_LINK_EXPIRED;
			$result['error'] = self::ERROR_LINK_EXPIRED;
		} elseif (!$this->verifyUsageMethod($config)) {
			// Link is valid but there is not any execution left
			$result['message'] = self::MSG_NO_USE_LEFT;
			$result['error'] = self::ERROR_NO_USE_LEFT;
		} elseif (!$this->verifySourceMethod($config)) {
			// Link is valid but source is not allowed to execute it
			$result['message'] = self::MSG_ACCESS_DENIED;
			$result['error'] = self::ERROR_ACCESS_DENIED;
		} else {
			// Link is valid. Everything is fine.
			$result['message'] = self::MSG_NO_ERROR;
			$result['error'] = self::ERROR_NO_ERROR;
		}
		return $result;
	}

	/**
	 * Validate the time access criterias.
	 *
	 * @param array $config web access config
	 * @return bool true on success, otherwise false
	 */
	private function verifyTimeMethod(array $config): bool
	{
		$valid = false;
		if (key_exists('time_method', $config)) {
			switch ($config['time_method']) {
				case WebAccessConfig::WEB_ACCESS_TIME_METHOD_UNLIMITED: {
					$valid = true;
					break;
				}
				case WebAccessConfig::WEB_ACCESS_TIME_METHOD_FOR_DAYS:
				case WebAccessConfig::WEB_ACCESS_TIME_METHOD_DATE_RANGE: {
					$now = time();
					$ts_from = key_exists('time_from', $config) ? (int) $config['time_from'] : 0;
					$ts_to = key_exists('time_to', $config) ? (int) $config['time_to'] : 0;
					$valid = ($ts_from > 0 && $ts_to > 0 && $ts_from <= $now && $now <= $ts_to);
					break;
				}
			}
		}
		return $valid;
	}

	/**
	 * Validate the usage access criterias.
	 *
	 * @param array $config web access config
	 * @return bool true on success, otherwise false
	 */
	private function verifyUsageMethod(array $config): bool
	{
		$valid = false;
		if (key_exists('usage_method', $config)) {
			switch ($config['usage_method']) {
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_UNLIMITED: {
					$valid = true;
					break;
				}
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_ONE_USE:
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_NUMBER_USES: {
					$usage_left = key_exists('usage_left', $config) ? (int) $config['usage_left'] : 0;
					$valid = ($usage_left > 0);
					break;
				}
			}
		}
		return $valid;
	}

	/**
	 * Validate the source access criterias.
	 *
	 * @param array $config web access config
	 * @return bool true on success, otherwise false
	 */
	private function verifySourceMethod(array $config): bool
	{
		$valid = false;
		if (key_exists('source_access', $config)) {
			switch ($config['source_access']) {
				case WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_NO_RESTRICTION: {
					$valid = true;
					break;
				}
				case WebAccessConfig::WEB_ACCESS_SOURCE_METHOD_IP_RESTRICTION: {
					if (key_exists('source_ips_allowed', $config) && is_array($config['source_ips_allowed'])) {
						$ip = $_SERVER['REMOTE_ADDR'] ?? null;
						$valid = in_array($ip, $config['source_ips_allowed']);
					}
				}
			}
		}
		return $valid;
	}

	/**
	 * Execute web access action.
	 *
	 * @param array $config web access config
	 * @return bool true on success, otherwise false
	 */
	private function executeAction(array $config): bool
	{
		$status = false;
		$action = $config['action'] ?? null;
		$params = [];
		if (key_exists('action_params', $config) && is_array($config['action_params'])) {
			$params = $config['action_params'];
		}
		if (key_exists('access_type', $config)) {
			switch ($config['access_type']) {
				case WebAccessConfig::WEB_ACCESS_TYPE_RESOURCE: {
					$web_access_resource = $this->getModule('web_access_resource');
					$result = $web_access_resource->executeCommand(
						$config,
						$action,
						$params
					);
					$status = ($result->error === 0);
					break;
				}
			}
		}
		return $status;
	}

	/**
	 * Run post actions executed after the main action.
	 * Post-actions are executed only for fully valid requests.
	 *
	 * @param array $token token value
	 * @param array $config web access config
	 */
	private function postAction(string $token, array $config)
	{
		// Update access time
		$config['access_time'] = time();

		if (key_exists('usage_method', $config)) {
			switch ($config['usage_method']) {
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_ONE_USE:
				case WebAccessConfig::WEB_ACCESS_USAGE_METHOD_NUMBER_USES: {
					$usage_left = key_exists('usage_left', $config) ? (int) $config['usage_left'] : 0;
					if ($usage_left > 0) {
						// Decrement the usage left
						$config['usage_left'] = --$usage_left;
					}
					break;
				}
			}
		}

		// Save config
		$web_access_config = $this->getModule('web_access_config');
		$web_access_config->setWebAccessConfig($token, $config);
	}
}
