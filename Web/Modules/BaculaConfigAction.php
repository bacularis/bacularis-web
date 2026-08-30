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
use Bacularis\Common\Modules\PluginConfigBase;
use Prado\Prado;

/**
 * Various Bacula config actions.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Module
 */
class BaculaConfigAction
{
	/**
	 * Read Bacula configuration resources given type.
	 *
	 * @param string $component_type component type
	 * @param string $resource_type resource type
	 * @param null|string $host API host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object command result with output and error
	 */
	public static function readResources(string $component_type, string $resource_type, ?string $host = null, bool $show_error = true): object
	{
		$app = Prado::getApplication();
		$misc = $app->getModule('misc');
		$api = $app->getModule('api');
		$params = [
			'config',
			$component_type,
			$resource_type
		];
		$result = $api->get($params, $host, $show_error);
		$component_full_type = $misc->getComponentFullName($component_type);
		if ($result->error != 0) {
			$amsg = "%s Host: {$host}, Component: {$component_full_type}, Resource: {$resource_type}";
			$action = sprintf(
				'Problem with getting Bacula config resources: Error: %d, Output: %s',
				$result->error,
				$result->output
			);
			$audit = $app->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
			return false;
		}
		return $result;
	}

	/**
	 * Read Bacula resource configuration.
	 *
	 * @param string $component_type component type
	 * @param string $resource_type resource type
	 * @param string $resource_name resource name
	 * @param null|string $host API host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object command result with output and error
	 */
	public static function readResource(string $component_type, string $resource_type, string $resource_name, ?string $host = null, bool $show_error = true): object
	{
		$app = Prado::getApplication();
		$misc = $app->getModule('misc');
		$api = $app->getModule('api');
		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$result = $api->get($params, $host, $show_error);
		$component_full_type = $misc->getComponentFullName($component_type);
		if ($result->error != 0) {
			$amsg = "%s Host: {$host}, Component: {$component_full_type}, Resource: {$resource_type}, Name: {$resource_name}";
			$action = sprintf(
				'Problem with getting Bacula config resource: Error: %d, Output: %s',
				$result->error,
				$result->output
			);
			$audit = $app->getModule('audit');
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
			return false;
		}
		return $result;
	}

	/**
	 * Create Bacula resource configuration.
	 *
	 * @param string $component_type component type
	 * @param string $resource_type resource type
	 * @param string $resource_name resource name
	 * @param array $config new Bacula resource configuration
	 * @param null|string $host API host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object command result with output and error
	 */
	public static function createResource(string $component_type, string $resource_type, string $resource_name, array $config, ?string $host = null, bool $show_error = true): object
	{
		$app = Prado::getApplication();
		$misc = $app->getModule('misc');
		$component_full_type = $misc->getComponentFullName($component_type);

		$plugin_manager = $app->getModule('plugin_manager');

		// Pre-create job actions
		$plugin_manager->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
			'run',
			'pre-create',
			$resource_type,
			$resource_name
		);

		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$api = $app->getModule('api');
		$result = $api->create(
			$params,
			['config' => json_encode($config)],
			$host,
			$show_error
		);
		$success = ($result->error == 0);
		$host = is_null($host) ? HostConfig::MAIN_CATALOG_HOST : $host;
		$amsg = "%s Host: {$host}, Component: {$component_full_type}, Resource: {$resource_type}, Name: {$resource_name}";
		$audit = $app->getModule('audit');
		if ($success) {
			if ($component_type == 'dir') {
				$api->set(['console'], ['reload']);
			}

			// Post-create job actions
			$plugin_manager->callPluginActionByType(
				PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
				'run',
				'post-create',
				$resource_type,
				$resource_name
			);

			$action = 'Create Bacula config resource.';
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		} else {
			$action = sprintf(
				'Problem with creating Bacula config resource: Error: %d, Output: %s',
				$result->error,
				$result->output
			);
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		}
		return $result;
	}

	/**
	 * Update Bacula resource configuration.
	 *
	 * @param string $component_type component type
	 * @param string $resource_type resource type
	 * @param string $resource_name resource name
	 * @param array $config new Bacula resource configuration
	 * @param null|string $host API host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object command result with output and error
	 */
	public static function updateResource(string $component_type, string $resource_type, string $resource_name, array $config, ?string $host = null, bool $show_error = true): object
	{
		$app = Prado::getApplication();
		$misc = $app->getModule('misc');
		$component_full_type = $misc->getComponentFullName($component_type);

		$plugin_manager = $app->getModule('plugin_manager');

		// Pre-create job actions
		$plugin_manager->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
			'run',
			'pre-update',
			$resource_type,
			$resource_name
		);

		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$api = $app->getModule('api');
		$result = $api->set(
			$params,
			['config' => json_encode($config)],
			$host,
			$show_error
		);
		$success = ($result->error == 0);
		$host = is_null($host) ? HostConfig::MAIN_CATALOG_HOST : $host;
		$amsg = "%s Host: {$host}, Component: {$component_full_type}, Resource: {$resource_type}, Name: {$resource_name}";
		$audit = $app->getModule('audit');
		if ($success) {
			if ($component_type == 'dir') {
				$api->set(['console'], ['reload']);
			}

			// Post-create job actions
			$plugin_manager->callPluginActionByType(
				PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
				'run',
				'post-update',
				$resource_type,
				$resource_name
			);

			$action = 'Save Bacula config resource.';
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		} else {
			$action = sprintf(
				'Problem with saving Bacula config resource: Error: %d, Output: %s',
				$result->error,
				$result->output
			);
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		}
		if ($resource_name != $config['Name']) {
			$amsg =  "%s Host: {$host}, Component: {$component_full_type}, Resource: {$resource_type}, Name: {$resource_name} => {$config['Name']}";
			if ($success) {
				$action = "Rename Bacula config resource.";
				$audit->audit(
					AuditLog::TYPE_INFO,
					AuditLog::CATEGORY_CONFIG,
					sprintf($amsg, $action)
				);
			} else {
				$action = sprintf(
					'Problem while renaming Bacula config resource: Error: %d, Output: %s',
					$result->error,
					$result->output
				);
				$audit->audit(
					AuditLog::TYPE_ERROR,
					AuditLog::CATEGORY_CONFIG,
					sprintf($amsg, $action)
				);
			}
		}
		return $result;
	}

	/**
	 * Remove Bacula resource configuration.
	 *
	 * @param string $component_type component type
	 * @param string $resource_type resource type
	 * @param string $resource_name resource name
	 * @param null|string $host API host name to send request
	 * @param bool $show_error if true then it shows error as HTML error page
	 * @return object command result with output and error
	 */
	public static function removeResource(string $component_type, string $resource_type, string $resource_name, ?string $host = null, bool $show_error = true): object
	{
		$app = Prado::getApplication();
		$misc = $app->getModule('misc');
		$component_full_type = $misc->getComponentFullName($component_type);

		$plugin_manager = $app->getModule('plugin_manager');

		// Pre-remove job actions
		$plugin_manager->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
			'run',
			'pre-remove',
			$resource_type,
			$resource_name
		);

		$params = [
			'config',
			$component_type,
			$resource_type,
			$resource_name
		];
		$api = $app->getModule('api');
		$result = $api->remove(
			$params,
			$host,
			$show_error
		);

		$success = ($result->error == 0);
		$host = is_null($host) ? HostConfig::MAIN_CATALOG_HOST : $host;
		$amsg = "%s Host: {$host}, Component: {$component_full_type}, Resource: {$resource_type}, Name: {$resource_name}";
		$audit = $app->getModule('audit');
		if ($success) {
			// Post-remove job actions
			$plugin_manager->callPluginActionByType(
				PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
				'run',
				'post-remove',
				$resource_type,
				$resource_name
			);

			$action = 'Remove Bacula config resource.';
			$audit->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		} else {
			$action = sprintf(
				'Problem with removing Bacula config resource: Error: %d, Output: %s',
				$result->error,
				$result->output
			);
			$audit->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				sprintf($amsg, $action)
			);
		}
		return $result;
	}
}
