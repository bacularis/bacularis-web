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

use Bacularis\Web\Modules\BacularisWebPluginBase;

/**
 * Base class for Bacula resource action plugin module.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Plugin
 */
abstract class BaculaResourceAction extends BacularisWebPluginBase
{
	/**
	 * Plugin action types.
	 */
	private const ACTION_TYPE_PRE_CREATE = 'pre-create';
	private const ACTION_TYPE_PRE_CREATE_DESC = 'Pre-create';
	private const ACTION_TYPE_POST_CREATE = 'post-create';
	private const ACTION_TYPE_POST_CREATE_DESC = 'Post-create';
	private const ACTION_TYPE_PRE_UPDATE = 'pre-update';
	private const ACTION_TYPE_PRE_UPDATE_DESC = 'Pre-update';
	private const ACTION_TYPE_POST_UPDATE = 'post-update';
	private const ACTION_TYPE_POST_UPDATE_DESC = 'Post-update';
	private const ACTION_TYPE_PRE_REMOVE = 'pre-remove';
	private const ACTION_TYPE_PRE_REMOVE_DESC = 'Pre-remove';
	private const ACTION_TYPE_POST_REMOVE = 'post-remove';
	private const ACTION_TYPE_POST_REMOVE_DESC = 'Post-remove';

	/**
	 * Main run action command.
	 *
	 * @param string $action_type action name
	 * @param null|string $type resource type
	 * @param null|string $name resource name
	 */
	public function run(string $action_type, ?string $type = null, ?string $name = null)
	{
		$config = $this->getConfig();
		if ($config['parameters']['action_type'] !== $action_type) {
			// different action types
			return true;
		}
		if ($type !== static::getResource()) {
			// different resource types
			return true;
		}
		if (is_array($config['parameters']['action'])) {
			for ($i = 0; $i < count($config['parameters']['action']); $i++) {
				$this->runAction($config['parameters']['action'][$i], $type, $name);
			}
		}
	}

	/**
	 * Run single resource type action.
	 *
	 * @param string $action action name
	 * @param null|string $type resource type
	 * @param null|string $name resource name
	 */
	private function runAction(string $action, ?string $type, ?string $name)
	{
		$web_plugin = $this->getModule('plugin_manager');
		$web_plugin->callPluginActionBySettingName(
			$action,
			'run',
			$type,
			$name,
		);
	}

	/**
	 * Get supported resource action types.
	 *
	 * @return array resource actions with action id (key) and action description (value)
	 */
	protected static function getResourceActionTypes(): array
	{
		return [
			self::ACTION_TYPE_PRE_CREATE => self::ACTION_TYPE_PRE_CREATE_DESC,
			self::ACTION_TYPE_POST_CREATE => self::ACTION_TYPE_POST_CREATE_DESC,
			self::ACTION_TYPE_PRE_UPDATE => self::ACTION_TYPE_PRE_UPDATE_DESC,
			self::ACTION_TYPE_POST_UPDATE => self::ACTION_TYPE_POST_UPDATE_DESC,
			self::ACTION_TYPE_PRE_REMOVE => self::ACTION_TYPE_PRE_REMOVE_DESC,
			self::ACTION_TYPE_POST_REMOVE => self::ACTION_TYPE_POST_REMOVE_DESC
		];
	}

	/**
	 * Get resource type.
	 *
	 * @return string resource type
	 */
	abstract public static function getResource(): string;
}
