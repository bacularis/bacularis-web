<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

		$this->getCallbackClient()->callClientFunction(
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
		$component = $this->ConfigComponentName->getSelectedValue();
		if (empty($component)) {
			return;
		}
		$resource = $this->ConfigResourceName->getSelectedValue();
		if (empty($resource)) {
			return;
		}
		$misc = $this->getModule('misc');
		$resource = $misc->setResourceToAPIForm($resource);
		$this->ConfigDirectives->setComponentType($component);
		$this->ConfigDirectives->setResourceType($resource);
		$this->ConfigDirectives->setLoadValues(false);
		$this->ConfigDirectives->setCopyMode(true);
		$this->ConfigDirectives->raiseEvent('OnDirectiveListLoad', $this, null);
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
		$config = $this->filterConfig($config);
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

	private function filterConfig(array $config): array
	{
		$new_config = [];
		foreach ($config as $name => $value) {
			if (is_array($value) && count($value) == 1 && $value[0] === '') {
				// filter empty arrays provided by not used multitextboxes
				continue;
			}
			$new_config[$name] = $value;
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
}
