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
use Prado\Prado;

/**
 * Various web action access actions.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class WebAccessAction
{
	/**
	 * Create web access setting.
	 *
	 * @param array $config web access configuration
	 * @return array create web access result and token
	 */
	public static function create(array $config): array
	{
		$app = Prado::getApplication();
		$web_access_config = $app->getModule('web_access_config');
		$token = $web_access_config->generateWebConfigToken();
		$result = $web_access_config->setWebAccessConfig($token, $config);
		$audit = $app->getModule('audit');
		if ($result) {
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				sprintf(
					"Web access has been created: Action: %s, Params: %s",
					$config['action'],
					json_encode($config['action_params'])
				)
			);
		} else {
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				sprintf(
					"Error while creating web access: Action: %s, Params: %s",
					$config['action'],
					json_encode($config['action_params'])
				)
			);
		}
		return ['state' => $result, 'token' => $token];
	}

	/**
	 * Update web access setting.
	 *
	 * @param string $token token name
	 * @param array $config web access configuration
	 * @return array update web access result and token
	 */
	public static function update(string $token, array $config): array
	{
		$app = Prado::getApplication();
		$web_access_config = $app->getModule('web_access_config');
		$result = $web_access_config->updateWebAccessConfig($token, $config);
		$audit = $app->getModule('audit');
		$params = $web_access_config->getWebAccessConfig($token);
		if ($result) {
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_APPLICATION,
				sprintf(
					"Web access has been updated: Action: %s, Params: %s",
					$params['action'],
					json_encode($config['action_params'])
				)
			);
		} else {
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_APPLICATION,
				sprintf(
					"Error while updating web access: Action: %s, Params: %s",
					$params['action'],
					json_encode($config['action_params'])
				)
			);
		}
		return ['state' => $result, 'token' => $token];
	}

	/**
	 * Remove web access setting.
	 *
	 * @param string $token token value
	 * @return bool true on success, otherwise false
	 */
	public static function remove($token): bool
	{
		$app = Prado::getApplication();
		$web_access_config = $app->getModule('web_access_config');
		$result = $web_access_config->removeWebAccessConfig($token);
		return $result;
	}
}
