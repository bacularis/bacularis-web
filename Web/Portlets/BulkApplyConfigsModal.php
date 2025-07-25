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

namespace Bacularis\Web\Portlets;

use Bacularis\Common\Modules\PluginConfigBase;
use Bacularis\Web\Modules\WebUserRoles;
use Bacularis\Web\Modules\VariableConfig;

/**
 * Bulk apply configs modal control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BulkApplyConfigsModal extends Portlets
{
	public const HOST = 'Host';
	public const COMPONENT_TYPE = 'ComponentType';
	public const RESOURCE_TYPE = 'ResourceType';

	public function onInit($param)
	{
		parent::onInit($param);
		$this->Visible = $this->User->isInRole(WebUserRoles::ADMIN);
	}

	public function loadConfigs($sender, $param)
	{
		$options = (array) ($param->getCallbackParameter() ?? []);
		$configs = $this->getModule('conf_config')->getConfig();
		$pattern_config = $this->getModule('pattern_config');
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$names = [];
		foreach ($configs as $name => $props) {
			if ($props['component'] !== $component_type || $props['resource'] !== $resource_type) {
				continue;
			}
			if ((!key_exists('in_pattern', $options) || !$options['in_pattern']) && $pattern_config->isConfigInPattern($name)) {
				continue;
			}
			$names[] = $name;
		}
		$this->Configs->DataSource = array_combine($names, $names);
		$this->Configs->dataBind();

		$cb = $this->getPage()->getCallbackClient();
		$component_type_full = $this->getModule('misc')->getComponentFullName($component_type);
		$cb->callClientFunction(
			'oBulkApplyConfigsModal.update_component',
			[$component_type_full]
		);
		$cb->callClientFunction(
			'oBulkApplyConfigsModal.update_resource',
			[$resource_type]
		);
	}

	public function applyConfigs($sender, $param)
	{
		$param = $param->getCallbackParameter();
		if (empty($param) || !is_object($param) || !isset($param->name) || !isset($param->simulate) || !isset($param->vars)) {
			return;
		}
		$selected = $param->name;
		$simulate = $param->simulate;
		$variables = (array) $param->vars;

		// Get selected configs setting
		$conf_config = $this->getModule('conf_config');
		$variable_config = $this->getModule('variable_config');
		$config_list = $this->Configs->getSelectedIndices();
		$configs = [];
		foreach ($config_list as $indice) {
			for ($i = 0; $i < $this->Configs->getItemCount(); $i++) {
				if ($i === $indice) {
					$config = $this->Configs->Items[$i]->Value;
					$config_raw = $conf_config->getConfConfig($config);
					$configs[] = $variable_config->addVariables($config_raw, $variables);
					break;
				}
			}
		}

		// Get selected resource configuration
		$api = $this->getModule('api');
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$params = [
			'config',
			$component_type,
			$resource_type,
			$selected
		];
		$result = $api->get($params, $host);
		$cb = $this->getPage()->getCallbackClient();
		if ($result->error !== 0) {
			$cb->callClientFunction(
				'oBulkApplyConfigsModal.update_log_status',
				[$selected, false, $result->output, '', $simulate]
			);
			return;
		}

		// Apply configs to selected resource
		$name_warning = false;
		$new_config = (array) $result->output;
		for ($i = 0; $i < count($configs); $i++) {
			foreach ($configs[$i]['config'] as $directive => $value) {
				if ($directive == 'Name' && $this->OverwritePolicyExisting->Checked) {
					$name_warning = true;
				}
				if (key_exists($directive, $new_config)) {
					// Directive exists in original config
					if ($this->OverwritePolicyExisting->Checked) {
						$new_config[$directive] = $value;
					} elseif ($this->OverwritePolicyAddNew->Checked) {
						if (is_array($value)) {
							if (is_object($new_config[$directive])) {
								$new_config[$directive] = (array) $new_config[$directive];
							}
							$new_config[$directive] = array_merge($new_config[$directive], $value);
						}
					}
				} else {
					// Directive does not exist in original config
					$new_config[$directive] = $value;
				}
			}
		}

		// Show or hide name warning
		$cb->callClientFunction(
			'oBulkApplyConfigsModal.show_name_warning',
			[$name_warning]
		);

		if (!$simulate) {
			// Run pre type plugin action
			$this->runPluginAction(
				'pre',
				'update',
				$resource_type,
				$selected
			);
		}

		$query = [];
		if ($simulate) {
			$query['mode'] = 'simulate';
		}
		$query_string = '';
		if (count($query) > 0) {
			$query_string = '?' . http_build_query($query);
		}
		$params = [
			'config',
			$component_type,
			$resource_type,
			$selected,
			$query_string
		];
		$conf = [
			'config' => json_encode($new_config)
		];
		$result = $api->set(
			$params,
			$conf,
			$host
		);
		if ($result->error === 0) {
			if (!$simulate) {
				// Reload settings
				$api->set(['console'], ['reload']);

				// Run post type plugin action
				$this->runPluginAction(
					'post',
					'update',
					$resource_type,
					$selected
				);
			}
			$cb->callClientFunction(
				'oBulkApplyConfigsModal.update_log_status',
				[$selected, true, '', $result->output, $simulate]
			);
		} else {
			$cb->callClientFunction(
				'oBulkApplyConfigsModal.update_log_status',
				[$selected, false, $result->output, '', $simulate]
			);
			return;
		}
	}

	public function prepareVariables($sender, $param)
	{
		// Get selected configs
		$configs = $this->getSelectedConfigs();
		$variable_config = $this->getModule('variable_config');
		$variables = $variable_config->findVariables($configs);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oBulkApplyConfigsModal.prepare_variables_cb',
			[$variables]
		);
	}

	/**
	 * Execute plugin action for given resources.
	 *
	 * @param string $action_type action type (pre or post)
	 * @param string $action_name action name (update)
	 * @param string $resource_type resource type
	 * @param string $resource_name resource name to use in actions
	 */
	private function runPluginAction(string $action_type, string $action_name, string $resource_type, string $resource_name)
	{
		$plugin_manager = $this->getModule('plugin_manager');
		$action = sprintf(
			'%s-%s',
			$action_type,
			$action_name
		);
		$plugin_manager->callPluginActionByType(
			PluginConfigBase::PLUGIN_TYPE_RUN_ACTION,
			'run',
			$action,
			$resource_type,
			$resource_name
		);
	}

	/**
	 * Get selected configs in apply configs window.
	 *
	 * @return array selected configs
	 */
	private function getSelectedConfigs(): array
	{
		$conf_config = $this->getModule('conf_config');
		$config_list = $this->Configs->getSelectedIndices();
		$configs = [];
		foreach ($config_list as $indice) {
			for ($i = 0; $i < $this->Configs->getItemCount(); $i++) {
				if ($i === $indice) {
					$config = $this->Configs->Items[$i]->Value;
					$configs[] = $conf_config->getConfConfig($config);
					break;
				}
			}
		}
		return $configs;
	}

	/**
	 * Get host.
	 *
	 * @return string host
	 */
	public function getHost(): ?string
	{
		return $this->getViewState(self::HOST);
	}

	/**
	 * Set host.
	 *
	 * @param string $host host
	 */
	public function setHost(?string $host): void
	{
		$this->setViewState(self::HOST, $host);
	}

	/**
	 * Get component type.
	 *
	 * @return string component type
	 */
	public function getComponentType(): string
	{
		return $this->getViewState(self::COMPONENT_TYPE, '');
	}

	/**
	 * Set component type.
	 *
	 * @param string $type component type
	 */
	public function setComponentType(string $type): void
	{
		$this->setViewState(self::COMPONENT_TYPE, $type);
	}

	/**
	 * Get resource type.
	 *
	 * @return string resource type
	 */
	public function getResourceType(): string
	{
		return $this->getViewState(self::RESOURCE_TYPE, '');
	}

	/**
	 * Set resource type.
	 *
	 * @param string $type resource type
	 */
	public function setResourceType(string $type): void
	{
		$this->setViewState(self::RESOURCE_TYPE, $type);
	}
}
