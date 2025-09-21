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

	/**
	 * Execute web access resource command.
	 *
	 * @param array $config web access configuration
	 * @param string $action action name
	 * @param array $params action parameters
	 * @return null|object command result object or null if action not found
	 */
	public function executeCommand(array $config, string $action, array $params)
	{
		$result = null;
		$api = $this->getModule('api');
		switch ($action) {
			case self::ACTION_RUN_NAME: {
				$api_host = $config['api_hosts'][0] ?? null;
				$resource_name = $config['resource_name'] ?? '';
				$params['name'] = $resource_name;
				$cmd = ['jobs', 'run'];
				$result = $api->create($cmd, $params, $api_host, false);
				if ($result->error != 0) {
					$this->getModule('audit')->audit(
						AuditLog::TYPE_ERROR,
						AuditLog::CATEGORY_APPLICATION,
						sprintf(
							'Web access resource acction failed: Error: %d, Message: %s',
							$result->error,
							$result->output
						)
					);
				}
				break;
			}
		}
		return $result;
	}
}
