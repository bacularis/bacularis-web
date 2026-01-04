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

use Bacularis\Web\Modules\ConfigConfig;
use Bacularis\Web\Modules\PatternConfig;
use Bacularis\Web\Modules\WebUserRoles;

/**
 * Save Bacula component configuration to configs and pattern.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class SaveComponentToPatternModal extends Portlets
{
	public const HOST = 'Host';
	public const COMPONENT_TYPE = 'ComponentType';

	public function onInit($param)
	{
		parent::onInit($param);
		$this->Visible = $this->User->isInRole(WebUserRoles::ADMIN);
	}

	/**
	 * Prepare create pattern window.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function setConfigsWindow($sender, $param)
	{
		$param = $param->getCallbackParameter();
		[$host, $component_type] = $param;
		$this->setHost($host);
		$this->setComponentType($component_type);
	}

	/**
	 * Create and load tables with component resource list.
	 *
	 * @param TCallback $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function loadResourceList($sender, $param)
	{
		$config = $this->getResourceConfig();
		$this->setPatternName($config);
		$resources = $this->prepareResourceTableData($config);
		$cb = $this->getPage()->getCallbackClient();
		$cb->callClientFunction(
			'oSaveComponentToPattern.create_resource_lists_cb',
			[$resources]
		);
	}

	/**
	 * Main save component to pattern action.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCallbackEventParameter $param event parameter
	 */
	public function saveComponentToPattern($sender, $param)
	{
		$policy_name = $this->SaveComponentToPatternPatternName->Text;
		$component_type = $this->getComponentType();
		$resources = json_decode($this->SavePatternValues->Value, true);
		$configs = [];
		$config = $this->getResourceConfig();
		$component_name = $this->getComponentName($config);
		$error = '';
		$warnings = [];
		for ($i = 0; $i < count($config); $i++) {
			for ($j = 0; $j < count($resources); $j++) {
				if (!property_exists($config[$i], $resources[$j]['type'])) {
					continue;
				}
				if ($config[$i]->{$resources[$j]['type']}->Name !== $resources[$j]['resource']) {
					continue;
				}
				$result = $this->saveConfig(
					$component_type,
					$component_name,
					$resources[$j]['type'],
					$resources[$j]['resource'],
					$config[$i]->{$resources[$j]['type']}
				);
				if ($result['warning']) {
					$warnings[] = $result['warning'];
				}
				if ($result['error']) {
					$error = $result['error'];
					break 2;
				}
				if ($result['config_name']) {
					$configs[] = $result['config_name'];
				}
			}
		}

		if (!$error) {
			if (count($configs) > 0) {
				$result = $this->savePattern(
					$component_type,
					$policy_name,
					$configs
				);
				if ($result['error']) {
					$error = $result['error'];
				}
			} elseif (count($warnings) == 0) {
				$error = 'No config to save. Please select at least one resource.';
			}
		}
		$cb = $this->getPage()->getCallbackClient();
		if ($error) {
			$cb->callClientFunction(
				'oSaveComponentToPattern.set_error',
				$error
			);
		} else {
			if (count($warnings) > 0) {
				$warns = implode('<br />', $warnings);
				$cb->callClientFunction(
					'oSaveComponentToPattern.set_warning',
					$warns
				);
			} else {
				$cb->callClientFunction(
					'oSaveComponentToPattern.show_window',
					false
				);
			}
		}
	}

	/**
	 * Save single config settings.
	 *
	 * @param string $component_type component type
	 * @param string $component_name component name
	 * @param string $resource_type resource type
	 * @param string $resource_name resource name
	 * @param object $config single resource config
	 * @return array results with new config name, error and warnings
	 */
	private function saveConfig(string $component_type, string $component_name, string $resource_type, string $resource_name, object $config): array
	{
		$ret = [
			'config_name' => '',
			'error' => '',
			'warning' => ''
		];
		$conf_config = $this->getModule('conf_config');
		$config_arr = json_encode($config);
		$config_arr = json_decode($config_arr, true);
		$config_name = $this->SaveComponentToPatternConfigName->Text;
		$from = [
			'%component_type',
			'%component_name',
			'%resource_type',
			'%resource_name'
		];
		$to = [
			$component_type,
			$component_name,
			$resource_type,
			$resource_name
		];
		$config_name = str_replace($from, $to, $config_name);

		if (!$this->SaveComponentToPatternOverwriteConfig->Checked && $conf_config->confConfigExists($config_name)) {
			$ret['warning'] = sprintf('Config <strong>"%s"</strong> already exists. This config has not been saved. To save it, you can change the config name or use overwrite config option.', $config_name);
		} else {
			$description = 'Config created by saving existing component configuration';
			$setting = [
				'description' => $description,
				'component' => $component_type,
				'resource' => $resource_type,
				'config' => $config_arr
			];
			$result = $conf_config->setConfConfig(
				$config_name,
				$setting
			);
			if ($result) {
				$ret['config_name'] = $config_name;
			} else {
				$ret['error'] = sprintf('Error while saving config "%s".', $config_name);
			}
		}
		return $ret;
	}

	/**
	 * Main save pattern action.
	 *
	 * @param string $component_type component type
	 * @param string $pattern_name pattern name
	 * @param array $configs all configs names assigned to this new pattern
	 * @return array result with new pattern name and error message
	 */
	private function savePattern(string $component_type, string $pattern_name, array $configs): array
	{
		$ret = [
			'pattern_name' => '',
			'error' => ''
		];
		$pattern_config = $this->getModule('pattern_config');
		if ($pattern_config->patternConfigExists($pattern_name)) {
			$ret['error'] = sprintf('Pattern with name "%s" already exists. Please use different name.', $pattern_name);
		} else {
			$description = 'Pattern created by saving existing component configuration';
			$setting = [
				'description' => $description,
				'component' => $component_type,
				'configs' => $configs
			];
			$result = $pattern_config->setPatternConfig(
				$pattern_name,
				$setting
			);
			if ($result) {
				$ret['pattern_name'] = $pattern_name;
			} else {
				$ret['error'] = sprintf('Error while saving pattern "%s".', $pattern_name);

			}
		}
		return $ret;
	}

	/**
	 * Get all component resource configuration.
	 *
	 * @return array current component resource configuration
	 */
	private function getResourceConfig(): array
	{
		$api = $this->getModule('api');
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$result = $api->get(
			[
				'config',
				$component_type
			],
			$host
		);
		$config = [];
		if ($result->error == 0) {
			$config = $result->output;
		}
		return $config;
	}

	/**
	 * Prepare resouce config for table data.
	 *
	 * @param array $rconfig resource configuration
	 * @return array table data
	 */
	private function prepareResourceTableData(array $rconfig): array
	{
		$config = [];
		for ($i = 0; $i < count($rconfig); $i++) {
			$resource_type = key($rconfig[$i]);
			$resource_name = $rconfig[$i]->{$resource_type}->Name;
			$description = $rconfig[$i]->{$resource_type}->Description ?? '';
			if (!key_exists($resource_type, $config)) {
				$config[$resource_type] = [];
			}
			$config[$resource_type][] = [
				'resource_name' => $resource_name,
				'description' => $description
			];
		}
		return $config;
	}

	/**
	 * Set proposed new pattern name in pattern name field.
	 *
	 * @param array $config component configuration
	 */
	private function setPatternName(array $config): void
	{
		$misc = $this->getModule('misc');
		$component_type = $this->getComponentType();
		$component_type_full = $misc->getComponentFullName($component_type);
		$component_name = $this->getComponentName($config);
		$policy_name = sprintf('Pattern - %s - %s', $component_type_full, $component_name);
		$this->SaveComponentToPatternPatternName->Text = $policy_name;
	}

	/**
	 * Get current component name.
	 *
	 * @param array $config component configuration
	 * @return string component name or empty string if the name not found in config
	 */
	private function getComponentName(array $config): string
	{
		$misc = $this->getModule('misc');
		$component_type = $this->getComponentType();
		$component_type_full = $misc->getMainComponentResource($component_type);
		$component_name = '';
		for ($i = 0; $i < count($config); $i++) {
			if (property_exists($config[$i], $component_type_full)) {
				$component_name = $config[$i]->{$component_type_full}->Name;
				break;
			}
		}
		return $component_name;
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
}
