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

use Prado\Prado;
use Bacularis\Common\Modules\AuditLog;
use Bacularis\Web\Modules\BaculumWebPage;
use Bacularis\Web\Modules\ConfigConfig;
use Bacularis\Web\Modules\PatternConfig;
use Bacularis\Web\Modules\VariableConfig;

class Patterns extends BaculumWebPage
{
	public const CONFIG_WINDOW_MODE_ADD = 'add';
	public const CONFIG_WINDOW_MODE_EDIT = 'edit';

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->IsPostBack || $this->IsCallBack) {
			return;
		}
		$this->setComponents();
	}

	public function setComponents()
	{
		$misc = $this->getModule('misc');
		$components = $misc->getComponents();
		$items = [['', '']];
		for ($i = 0; $i < count($components); $i++) {
			$value = $components[$i];
			$name = $misc->getComponentFullName($value);
			$items[$value] = $name;
		}
		$this->ConfigComponentName->DataSource = $items;
		$this->ConfigComponentName->dataBind();

		$this->PatternComponentName->DataSource = $items;
		$this->PatternComponentName->dataBind();
	}

	public function loadConfigList($sender, $param)
	{
		$config = $this->getModule('conf_config')->getConfig();
		$pattern = $this->getModule('pattern_config')->getConfig();
		$config_vals = array_values($config);
		$config_in_pattern_func = function ($item) use ($pattern) {
			$patterns = [];
			foreach ($pattern as $name => $props) {
				if (in_array($item['name'], $props['configs'])) {
					$patterns[] = $name;
				}
			}
			$item['in_pattern'] = $patterns;
			return $item;
		};
		$config_vals = array_map($config_in_pattern_func, $config_vals);
		$this->getCallbackClient()->callClientFunction(
			'oConfigList.update_cb',
			[$config_vals]
		);

		// Update configs in pattern window
		$this->loadPatternConfigList(null, null);
	}

	public function loadConfigs($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if (empty($name)) {
			return;
		}
		$config = $this->getModule('conf_config')->getConfConfig($name);
		if (count($config) == 0) {
			return;
		}

		$cb = $this->getCallbackClient();
		$cb->callClientFunction(
			'oConfig.show_config_window',
			[true]
		);

		$this->ConfigName->Text = $config['name'];
		$this->ConfigDescription->Text = $config['description'];
		$this->ConfigComponentName->setSelectedValue($config['component']);
		$this->loadResourceList(null, null);
		$this->ConfigResourceName->setSelectedValue($config['resource']);


		$this->ConfigDirectives->setComponentType($config['component']);
		$this->ConfigDirectives->setResourceType($config['resource']);
		$this->ConfigDirectives->setLoadValues(false);
		$this->ConfigDirectives->setCopyMode(true);
		$this->ConfigDirectives->loadConfig($sender, $param, 'ondirectivelistload', $config['config']);

		$this->getCallbackClient()->callClientFunction(
			'oConfig.show_directives',
			[true]
		);
	}

	public function loadResourceList($sender, $param)
	{
		$component = $this->ConfigComponentName->getSelectedValue();
		$resource_list = [];
		if (!empty($component)) {
			$misc = $this->getModule('misc');
			$resources = $misc->getResources($component);
			array_unshift($resources, '');
			$misc = $this->getModule('misc');
			$resources = array_map(
				fn ($res) => $misc->setResourceToAPIForm($res),
				$resources
			);
			$resource_list = array_combine($resources, $resources);
		}
		$this->ConfigResourceName->DataSource = $resource_list;
		$this->ConfigResourceName->dataBind();

		$this->ConfigDirectives->unloadDirectives();
	}

	public function loadDirectiveList($sender, $param)
	{
		$this->ConfigDirectives->unloadDirectives();
		$component_type = $this->ConfigComponentName->getSelectedValue();
		if (empty($component_type)) {
			return;
		}
		$sess = $this->getApplication()->getSession();
		$component_name = $sess->itemAt($component_type);

		$resource_type = $this->ConfigResourceName->getSelectedValue();
		if (empty($resource_type)) {
			return;
		}

		$this->loadResourcesToCopy();

		$misc = $this->getModule('misc');
		$resource_type = $misc->setResourceToAPIForm($resource_type);
		$this->ConfigDirectives->setComponentType($component_type);
		$this->ConfigDirectives->setComponentName($component_name);
		$this->ConfigDirectives->setResourceType($resource_type);
		$this->ConfigDirectives->setLoadValues(false);
		$this->ConfigDirectives->setCopyMode(true);
		$this->ConfigDirectives->raiseEvent('OnDirectiveListLoad', $this, null);
	}

	/**
	 * Load resource names to copy resource configuration feature.
	 */
	private function loadResourcesToCopy(): void
	{
		$component_type = $this->ConfigComponentName->getSelectedValue();
		$resource_type = $this->ConfigResourceName->getSelectedValue();
		$resources_start = ['' => ''];
		$params = [
			'config',
			$component_type,
			$resource_type
		];
		$resources = [];
		$res = $this->getModule('api')->get($params);
		if ($res->error === 0) {
			for ($i = 0; $i < count($res->output); $i++) {
				$r = $res->output[$i]->{$resource_type}->Name;
				$resources[$r] = $r;
			}
			natcasesort($resources);
		}
		$resources = array_merge($resources_start, $resources);
		$this->ResourcesToCopy->DataSource = $resources;
		$this->ResourcesToCopy->dataBind();
	}

	/**
	 * Copy configuration from existing resource.
	 *
	 * @param TActiveDropDownList $sender, sender object
	 * @param TCallbackEventParameter $param sender parameter
	 */
	public function copyConfig($sender, $param)
	{
		$resource_name = $this->ResourcesToCopy->SelectedValue;
		if (!empty($resource_name)) {
			$this->ConfigDirectives->setResourceName($resource_name);
			$this->ConfigDirectives->setLoadValues(true);
			$this->ConfigDirectives->setCopyMode(true);
			$this->ConfigDirectives->raiseEvent('OnDirectiveListLoad', $this, null);
			$this->ConfigDirectives->setResourceName(null);
		}
	}

	public function saveConfigs($sender, $param)
	{
		$conf_config = $this->getModule('conf_config');
		$cb = $this->getCallbackClient();

		$name = $this->ConfigName->Text;
		if ($this->ConfigWindowMode->Value == self::CONFIG_WINDOW_MODE_ADD) {
			if ($conf_config->confConfigExists($name)) {
				$cb->callClientFunction(
					'oConfig.show_error',
					[
						true,
						Prado::localize('Config with given name already exists.')
					]
				);
				return;
			}
		}
		$description = $this->ConfigDescription->Text;
		$component = $this->ConfigComponentName->getSelectedValue();
		$resource = $this->ConfigResourceName->getSelectedValue();
		$config = $this->ConfigDirectives->getDirectiveValues(true);
		if (empty($name) || empty($component) || empty($resource) || empty($config)) {
			return;
		}
		$config = self::filterConfig($config);
		$setting = [
			'description' => $description,
			'component' => $component,
			'resource' => $resource,
			'config' => $config
		];
		$result = $conf_config->setConfConfig(
			$name,
			$setting
		);
		if ($result === true) {
			$cb->callClientFunction(
				'oConfig.show_config_window',
				[false]
			);

			// update config list
			$this->loadConfigList(null, null);

			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				"Save config. Name: {$name}"
			);
		} else {
			$cb->callClientFunction(
				'oConfig.show_error',
				[
					true,
					Prado::localize('Error while writing config.')
				]
			);
			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				"Error while saving config. Name: {$name}"
			);
		}
	}

	/**
	 * Prepare configuration values to save in config.
	 *
	 * @param array $config configuration to save
	 * @return array configuration ready to save
	 */
	private static function filterConfig(array $config): array
	{
		$new_config = [];
		foreach ($config as $name => $value) {
			if (is_array($value)) {
				$val = self::filterConfig($value);
				if (count($val) > 0) {
					$new_config[$name] = $val;
				}
			} elseif ($value === null) {
				// filter empty arrays provided for example by not used multitextboxes
				continue;
			} else {
				$new_config[$name] = $value;
			}
		}
		return $new_config;
	}

	public function loadPatternConfigList($sender, $param)
	{
		$component = $this->PatternComponentName->getSelectedValue();
		$conf_config = $this->getModule('conf_config');
		$config = $conf_config->getConfig();
		$config_vals = array_values($config);

		// Update configs in pattern window. Add only configs with defined Name directive
		$configs = array_filter($config_vals, fn ($item) => ($item['component'] == $component && key_exists('Name', $item['config'])));
		$names = array_map(fn ($item) => $item['name'], $configs);
		$this->PatternConfigs->DataSource = array_combine($names, $names);
		$this->PatternConfigs->dataBind();
	}

	public function removeConfigs($sender, $param)
	{
		$names = $param->getCallbackParameter();
		if (empty($names)) {
			return;
		}
		$configs = explode('|', $names);
		$conf_configs = $this->getModule('conf_config');
		for ($i = 0; $i < count($configs); $i++) {
			$conf_configs->removeConfConfig($configs[$i]);
		}

		// update config list
		$this->loadConfigList(null, null);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_CONFIG,
			"Remove configs. Name: {$names}"
		);
	}

	public function loadPattern($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if (empty($name)) {
			return;
		}
		$config = $this->getModule('pattern_config')->getPatternConfig($name);
		if (count($config) == 0) {
			return;
		}

		$this->PatternName->Text = $config['name'];
		$this->PatternDescription->Text = $config['description'];
		$this->PatternComponentName->setSelectedValue($config['component']);

		$this->loadPatternConfigList(null, null);

		// Set configs
		$selected_indices = [];
		$configs = $config['configs'];
		for ($i = 0; $i < $this->PatternConfigs->getItemCount(); $i++) {
			if (in_array($this->PatternConfigs->Items[$i]->Value, $configs)) {
				$selected_indices[] = $i;
			}
		}
		$this->PatternConfigs->setSelectedIndices($selected_indices);
	}

	public function loadPatternList($sender, $param)
	{
		$config = $this->getModule('pattern_config')->getConfig();
		$this->getCallbackClient()->callClientFunction(
			'oPatternList.update_cb',
			[array_values($config)]
		);
	}

	public function savePattern($sender, $param)
	{
		$pattern_config = $this->getModule('pattern_config');
		$cb = $this->getCallbackClient();

		$name = $this->PatternName->Text;
		if ($this->PatternWindowMode->Value == self::CONFIG_WINDOW_MODE_ADD) {
			if ($pattern_config->patternConfigExists($name)) {
				$cb->callClientFunction(
					'oPattern.show_error',
					[
						true,
						Prado::localize('Pattern with given name already exists.')
					]
				);
				return;
			}
		}

		$description = $this->PatternDescription->Text;
		$component = $this->PatternComponentName->getSelectedValue();
		$pattern_configs = $this->PatternConfigs->getSelectedIndices();
		$configs = [];
		foreach ($pattern_configs as $indice) {
			for ($i = 0; $i < $this->PatternConfigs->getItemCount(); $i++) {
				if ($i === $indice) {
					$configs[] = $this->PatternConfigs->Items[$i]->Value;
				}
			}
		}
		if (empty($name) || count($configs) == 0) {
			return;
		}
		$setting = [
			'description' => $description,
			'component' => $component,
			'configs' => $configs
		];
		$result = $pattern_config->setPatternConfig(
			$name,
			$setting
		);
		if ($result === true) {
			$cb->callClientFunction(
				'oPattern.show_pattern_window',
				[false]
			);

			// update pattern list
			$this->loadPatternList(null, null);

			// update config list
			$this->loadConfigList(null, null);

			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				"Save pattern. Name: {$name}"
			);
		} else {
			$cb->callClientFunction(
				'oPattern.show_error',
				[
					true,
					Prado::localize('Error while writing pattern.')
				]
			);

			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				"Error while saving pattern. Name: {$name}"
			);
		}
	}

	public function removePatterns($sender, $param)
	{
		$names = $param->getCallbackParameter();
		if (empty($names)) {
			return;
		}
		$patterns = explode('|', $names);
		$pattern_config = $this->getModule('pattern_config');
		for ($i = 0; $i < count($patterns); $i++) {
			$pattern_config->removePatternConfig($patterns[$i]);
		}

		// update config list
		$this->loadPatternList(null, null);

		// update config list
		$this->loadConfigList(null, null);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_CONFIG,
			"Remove patterns. Name: {$names}"
		);
	}

	public function loadVariable($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if (empty($name)) {
			return;
		}
		$variable_config = $this->getModule('variable_config');
		$config = $variable_config->getVariableConfig($name);
		if (count($config) == 0) {
			return;
		}

		$this->VariableName->Text = $config['name'];
		$this->VariableDescription->Text = $config['description'];
		$this->VariableDefaultValue->Text = $config['default_value'];
	}

	public function loadVariableList($sender, $param)
	{
		$variable_config = $this->getModule('variable_config');
		$config = $variable_config->getConfig();
		$this->getCallbackClient()->callClientFunction(
			'oVariableList.update_cb',
			[array_values($config)]
		);
	}

	public function saveVariable($sender, $param)
	{
		$variable_config = $this->getModule('variable_config');
		$cb = $this->getCallbackClient();

		$name = $this->VariableName->Text;
		if ($this->VariableWindowMode->Value == self::CONFIG_WINDOW_MODE_ADD) {
			if ($variable_config->variableConfigExists($name)) {
				$cb->callClientFunction(
					'oVariable.show_error',
					[
						true,
						Prado::localize('Variable with given name already exists.')
					]
				);
				return;
			}
		}

		$description = $this->VariableDescription->Text;
		$default_value = $this->VariableDefaultValue->Text;
		if (empty($name)) {
			return;
		}
		$setting = [
			'description' => $description,
			'default_value' => $default_value
		];
		$result = $variable_config->setVariableConfig(
			$name,
			$setting
		);
		if ($result === true) {
			$cb->callClientFunction(
				'oVariable.show_variable_window',
				[false]
			);

			// update variable list
			$this->loadVariableList(null, null);

			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				"Save variable. Name: {$name}"
			);
		} else {
			$cb->callClientFunction(
				'oVariable.show_error',
				[
					true,
					Prado::localize('Error while writing variable.')
				]
			);

			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				"Error while saving variable. Name: {$name}"
			);
		}
	}

	public function removeVariables($sender, $param)
	{
		$names = $param->getCallbackParameter();
		if (empty($names)) {
			return;
		}
		$variables = explode('|', $names);
		$variable_config = $this->getModule('variable_config');
		for ($i = 0; $i < count($variables); $i++) {
			$variable_config->removeVariableConfig($variables[$i]);
		}

		// update config list
		$this->loadVariableList(null, null);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_CONFIG,
			"Remove variables. Name: {$names}"
		);
	}

	public function loadConstant($sender, $param)
	{
		$name = $param->getCallbackParameter();
		if (empty($name)) {
			return;
		}
		$constant_config = $this->getModule('constant_config');
		$config = $constant_config->getConstantConfig($name);
		if (count($config) == 0) {
			return;
		}

		$this->ConstantName->Text = $config['name'];
		$this->ConstantDescription->Text = $config['description'];
		$this->ConstantValue->Text = $config['value'];
	}

	public function loadConstantList($sender, $param)
	{
		$constant_config = $this->getModule('constant_config');
		$config = $constant_config->getConfig();
		$this->getCallbackClient()->callClientFunction(
			'oConstantList.update_cb',
			[array_values($config)]
		);
	}

	public function saveConstant($sender, $param)
	{
		$constant_config = $this->getModule('constant_config');
		$cb = $this->getCallbackClient();

		$name = $this->ConstantName->Text;
		if ($this->ConstantWindowMode->Value == self::CONFIG_WINDOW_MODE_ADD) {
			if ($constant_config->constantConfigExists($name)) {
				$cb->callClientFunction(
					'oConstant.show_error',
					[
						true,
						Prado::localize('Constant with given name already exists.')
					]
				);
				return;
			}
		}

		$description = $this->ConstantDescription->Text;
		$value = $this->ConstantValue->Text;
		if (empty($name)) {
			return;
		}
		$setting = [
			'description' => $description,
			'value' => $value
		];
		$result = $constant_config->setConstantConfig(
			$name,
			$setting
		);
		if ($result === true) {
			$cb->callClientFunction(
				'oConstant.show_constant_window',
				[false]
			);

			// update constant list
			$this->loadConstantList(null, null);

			$this->getModule('audit')->audit(
				AuditLog::TYPE_INFO,
				AuditLog::CATEGORY_CONFIG,
				"Save constant. Name: {$name}"
			);
		} else {
			$cb->callClientFunction(
				'oConstant.show_error',
				[
					true,
					Prado::localize('Error while writing constant.')
				]
			);

			$this->getModule('audit')->audit(
				AuditLog::TYPE_ERROR,
				AuditLog::CATEGORY_CONFIG,
				"Error while saving constant. Name: {$name}"
			);
		}
	}

	public function removeConstants($sender, $param)
	{
		$names = $param->getCallbackParameter();
		if (empty($names)) {
			return;
		}
		$constants = explode('|', $names);
		$constant_config = $this->getModule('constant_config');
		for ($i = 0; $i < count($constants); $i++) {
			$constant_config->removeConstantConfig($constants[$i]);
		}

		// update config list
		$this->loadConstantList(null, null);

		$this->getModule('audit')->audit(
			AuditLog::TYPE_INFO,
			AuditLog::CATEGORY_CONFIG,
			"Remove constants. Name: {$names}"
		);
	}
}
