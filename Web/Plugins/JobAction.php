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

use Bacularis\Common\Modules\IBacularisRunActionPlugin;
use Bacularis\Web\Modules\BaculaResourceAction;

/**
 * Execute job action plugin module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Plugin
 */
class JobAction extends BaculaResourceAction implements IBacularisRunActionPlugin
{
	/**
	 * Plugin action types.
	 */
	private const ACTION_TYPE_PRE_RUN_MANUALLY = 'pre-run-manually';
	private const ACTION_TYPE_PRE_RUN_MANUALLY_DESC = 'Pre-run manually';
	private const ACTION_TYPE_POST_RUN_MANUALLY = 'post-run-manually';
	private const ACTION_TYPE_POST_RUN_MANUALLY_DESC = 'Post-run manually';

	/**
	 * Get plugin name displayed in web interface.
	 *
	 * @return string plugin name
	 */
	public static function getName(): string
	{
		return 'Job action';
	}

	/**
	 * Get plugin version.
	 *
	 * @return string plugin version
	 */
	public static function getVersion(): string
	{
		return '1.0.0';
	}

	/**
	 * Get plugin type.
	 *
	 * @return string plugin type
	 */
	public static function getType(): string
	{
		return 'run-action';
	}

	/**
	 * Get plugin resource.
	 *
	 * @return string plugin resource
	 */
	public static function getResource(): string
	{
		return 'Job';
	}

	/**
	 * Get plugin configuration parameters.
	 *
	 * return array plugin parameters
	 */
	public static function getParameters(): array
	{
		return [
			[
				'name' => 'action_type',
				'type' => 'array',
				'default' => '',
				'label' => 'Action type',
				'data' => self::getResourceActionTypes()
			],
			[
				'name' => 'action',
				'type' => 'array_multiple_ordered',
				'default' => '',
				'data' => [],
				'resource' => 'job_action',
				'label' => 'Actions'
			]
		];
	}

	/**
	 * Get supported job action types.
	 *
	 * @return array job actions with action id (key) and action description (value)
	 */
	protected static function getResourceActionTypes(): array
	{
		$action_types = parent::getResourceActionTypes();
		$job_action_types = [
			self::ACTION_TYPE_PRE_RUN_MANUALLY => self::ACTION_TYPE_PRE_RUN_MANUALLY_DESC,
			self::ACTION_TYPE_POST_RUN_MANUALLY => self::ACTION_TYPE_POST_RUN_MANUALLY_DESC
		];
		return array_merge($action_types, $job_action_types);
	}
}
