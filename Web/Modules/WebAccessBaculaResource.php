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

/**
 * Web access Bacula resource module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebAccessBaculaResource extends WebModule
{
	/**
	 * Web access resource actions.
	 */
	public const ACTION_RUN_NAME = 'run';
	public const ACTION_RUN_DESC = 'Run';
	public const ACTION_CANCEL_NAME = 'cancel';
	public const ACTION_CANCEL_DESC = 'Cancel';
	public const ACTION_RUN_RESTORE_TEST_NAME = 'restore-test';
	public const ACTION_RUN_RESTORE_TEST_DESC = 'Verify restore';

	/**
	 * Execute web access resource command.
	 *
	 * @param array $config web access configuration
	 * @param string $action action name
	 * @param array $params action parameters
	 * @return null|object command result object or null if action not found
	 */
	public function executeCommand(array $config, string $action, array $params): bool
	{
		$success = false;
		$api = $this->getModule('api');
		$audit = $this->getModule('audit');
		switch ($action) {
			case self::ACTION_RUN_NAME: {
				$api_host = $config['api_hosts'][0] ?? null;
				$resource_name = $config['resource_name'] ?? '';
				$params['name'] = $resource_name;
				if (!$this->areRunParamsValid($params)) {
					$success = true; // this is not critical, so do not throw error
					break;
				}
				$cmd = ['jobs', 'run'];
				$result = $api->create($cmd, $params, $api_host, false);
				$success = ($result->error == 0);
				if (!$success) {
					$audit->audit(
						AuditLog::TYPE_ERROR,
						AuditLog::CATEGORY_APPLICATION,
						sprintf(
							'Web access resource action failed: Error: %d, Message: %s',
							$result->error,
							$result->output
						)
					);
				}
				break;
			}
			case self::ACTION_RUN_RESTORE_TEST_NAME: {
				$api_host = $config['api_hosts'][0] ?? null;
				$restore_test = $params['restore_test'] ?? '';
				$rt_manager = $this->getModule('restore_test_manager');
				$success = $rt_manager->prepareTest($api_host, $restore_test);
				if (!$success) {
					$audit->audit(
						AuditLog::TYPE_ERROR,
						AuditLog::CATEGORY_APPLICATION,
						sprintf(
							'Web access restore test action failed: Restore Test: "%s".',
							$restore_test
						)
					);
				}
				break;
			}
		}
		return $success;
	}

	/**
	 * Validate web access parameters.
	 * If parameters are valid, web access action can be running.
	 *
	 * @param array $params action parameters
	 * @return bool true if params are valid, otherwise false
	 */
	private function areRunParamsValid(array $params): bool
	{
		$valid = true;
		$audit = $this->getModule('audit');
		if (isset($params['allowed_levels']) && !empty($params['allowed_levels'])) {
			$misc = $this->getModule('misc');
			$allowed_levels = explode(',', $params['allowed_levels']);
			$level = $this->Request->contains('level') ? substr($this->Request['level'], 0, 1) : '';
			$level_long = $misc->getJobLevelLong($level);
			$valid = (!empty($level) && in_array($level, $allowed_levels));
			if (!$valid) {
				$audit->audit(
					AuditLog::TYPE_WARNING,
					AuditLog::CATEGORY_APPLICATION,
					sprintf(
						"Web access resource action is not running. Current job level '{$level_long}' is not intended to run this action. Params: %s",
						json_encode($params)
					)
				);
			}
		}
		return $valid;
	}
}
