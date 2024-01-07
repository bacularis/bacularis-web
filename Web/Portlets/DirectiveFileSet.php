<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * Bacula(R) - The Network Backup Solution
 * Baculum   - Bacula web interface
 *
 * Copyright (C) 2013-2021 Kern Sibbald
 *
 * The main author of Baculum is Marcin Haba.
 * The original author of Bacula is Kern Sibbald, with contributions
 * from many others, a complete list can be found in the file AUTHORS.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 *
 * This notice must be preserved when any source code is
 * conveyed and/or propagated.
 *
 * Bacula(R) is a registered trademark of Kern Sibbald.
 */

namespace Bacularis\Web\Portlets;

/**
 * FileSet directive template.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveFileSet extends DirectiveListTemplate
{
	private $directive_types = [
		'Bacularis\Web\Portlets\DirectiveCheckBox',
		'Bacularis\Web\Portlets\DirectiveTextBox',
		'Bacularis\Web\Portlets\DirectiveComboBox',
		'Bacularis\Web\Portlets\DirectiveListBox',
		'Bacularis\Web\Portlets\DirectiveInteger'
	];

	private $directive_list_types = [
		'Bacularis\Web\Portlets\DirectiveMultiTextBox'
	];

	private $directive_inc_exc_types = [
		'Bacularis\Web\Portlets\DirectiveTextBox'
	];

	public function loadConfig()
	{
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$directive_name = $this->getDirectiveName();
		$directives = $this->getData();
		$includes = [];
		$file = [];
		$plugin = [];
		$exclude = [];
		$options = [];
		if (!is_array($directives) || $directive_name === 'Exclude') {
			return;
		}
		foreach ($directives as $index => $subres) {
			if ($index === 'Include') {
				for ($i = 0; $i < count($subres); $i++) {
					if (is_null($subres[$i])) {
						// load page with new fileset to create
						continue;
					}
					foreach ($subres[$i] as $name => $values) {
						switch ($name) {
							case 'File': {
								$this->setFile($file, $name, $values);
								break;
							}
							case 'Plugin': {
								$this->setPlugin($plugin, $name, $values);
								break;
							}
							case 'Options': {
								$this->setOption($options, $name, $values);
								break;
							}
						}
					}
					$includes[] = [
						'file' => $file,
						'plugin' => $plugin,
						'options' => $options
					];
					$file = $plugin = $options = [];
				}
			} elseif ($index === 'Exclude') {
				if (!key_exists('File', $subres)) {
					// empty exclude
					continue;
				}
				// this is exclude
				$this->setFile($exclude, 'File', $subres['File']);
			}
		}

		$this->RepeaterFileSetIncludes->DataSource = $includes;
		$this->RepeaterFileSetIncludes->dataBind();
		$this->RepeaterFileSetExclude->DataSource = $exclude;
		$this->RepeaterFileSetExclude->dataBind();
		$this->FSBrowser->loadClients(null, null);
	}

	private function setFile(&$files, $name, $config)
	{
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$directive_name = 'File';
		$field_type = 'TextBox';
		$default_value = '';
		$required = false;

		for ($i = 0; $i < count($config); $i++) {
			$files[] = [
				'host' => $host,
				'component_type' => $component_type,
				'component_name' => $component_name,
				'resource_type' => $resource_type,
				'resource_name' => $resource_name,
				'directive_name' => $name,
				'directive_value' => $config[$i],
				'parent_name' => $name,
				'field_type' => $field_type,
				'default_value' => $default_value,
				'required' => $required,
				'data' => null,
				'resource' => null,
				'in_config' => true,
				'label' => $directive_name,
				'show' => true,
				'group_name' => $i
			];
		}
	}

	private function setPlugin(&$plugins, $name, $config)
	{
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$directive_name = 'Plugin';
		$field_type = 'TextBox';
		$default_value = '';
		$required = false;

		for ($i = 0; $i < count($config); $i++) {
			$plugins[] = [
				'host' => $host,
				'component_type' => $component_type,
				'component_name' => $component_name,
				'resource_type' => $resource_type,
				'resource_name' => $resource_name,
				'directive_name' => $name,
				'directive_value' => $config[$i],
				'parent_name' => $name,
				'field_type' => $field_type,
				'default_value' => $default_value,
				'required' => $required,
				'data' => null,
				'resource' => null,
				'in_config' => true,
				'label' => $directive_name,
				'show' => true,
				'group_name' => $i
			];
		}
	}

	private function setOption(&$options, $name, $config)
	{
		$misc = $this->getModule('misc');
		$load_values = $this->getLoadValues();
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();

		$resource_desc = $this->Application->getModule('data_desc')->getDescription($component_type, $resource_type, 'Include');

		for ($i = 0; $i < count($config); $i++) {
			foreach ($resource_desc->SubSections as $directive_name => $directive_desc) {
				if ($directive_name == 'File') {
					// In options block File cannot be defined
					continue;
				}
				if (is_object($config[$i])) {
					$config[$i] = (array) $config[$i];
				}
				$in_config = key_exists($directive_name, $config[$i]);
				$directive_value = null;
				if ($in_config === true) {
					$directive_value = $config[$i][$directive_name];
				}

				$default_value = null;
				$data = null;
				$field_type = 'TextBox';
				$required = false;
				if (is_object($directive_desc)) {
					if (property_exists($directive_desc, 'Required')) {
						$required = $directive_desc->Required;
					}
					if (property_exists($directive_desc, 'DefaultValue')) {
						$default_value = $directive_desc->DefaultValue;
					}
					if (property_exists($directive_desc, 'Data')) {
						$data = $directive_desc->Data;
					}
					if (property_exists($directive_desc, 'FieldType')) {
						$field_type = $directive_desc->FieldType;
					}
				}
				if ($field_type === 'CheckBox') {
					/**
					 * It is because bdirjson returns FileSet options boolean values
					 * as Yes/No instead of returning true/false as it does for the rest.
					 */
					if ($misc->isValidBooleanTrue($directive_value)) {
						$directive_value = true;
					} elseif ($misc->isValidBooleanFalse($directive_value)) {
						$directive_value = false;
					}
				}

				if ($field_type === 'MultiTextBox') {
					$directive_value = [$directive_value];
				}

				if (!is_array($directive_value)) {
					$directive_value = [$directive_value];
				}
				for ($j = 0; $j < count($directive_value); $j++) {
					$options[] = [
						'host' => $host,
						'component_type' => $component_type,
						'component_name' => $component_name,
						'resource_type' => $resource_type,
						'resource_name' => $resource_name,
						'directive_name' => $directive_name,
						'directive_value' => $directive_value[$j],
						'default_value' => $default_value,
						'required' => $required,
						'resource' => null,
						'data' => $data,
						'field_type' => $field_type,
						'in_config' => $in_config,
						'label' => $directive_name,
						'show' => ($in_config || !$load_values || $this->SourceTemplateControl->getShowAllDirectives()),
						'parent_name' => $name,
						'group_name' => $i
					];
				}
			}
		}
	}

	public function getDirectiveValue()
	{
		$directive_values = ['Include' => [], 'Exclude' => []];
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$resource_desc = $this->Application->getModule('data_desc')->getDescription($component_type, $resource_type);

		$counter = 0;
		$ctrls = $this->RepeaterFileSetIncludes->getItems();
		foreach ($ctrls as $value) {
			for ($i = 0; $i < count($this->directive_types); $i++) {
				$controls = $value->RepeaterFileSetOptions->findControlsByType($this->directive_types[$i]);
				for ($j = 0; $j < count($controls); $j++) {
					$controls[$j]->setValue();
					$directive_name = $controls[$j]->getDirectiveName();
					$directive_value = $controls[$j]->getDirectiveValue();
					$index = $controls[$j]->getGroupName();
					$default_value = $resource_desc['Include']->SubSections->{$directive_name}->DefaultValue;
					$in_config = $controls[$j]->getInConfig();
					if (is_null($directive_value)) {
						// option not set or removed
						continue;
					}
					if ($this->directive_types[$i] === 'Bacularis\Web\Portlets\DirectiveCheckBox') {
						settype($default_value, 'bool');
					}
					if ($directive_value === $default_value) {
						// value the same as default value, skip it
						continue;
					}
					if (!key_exists($counter, $directive_values['Include'])) {
						$directive_values['Include'][$counter] = [];
					}
					if (!key_exists('Options', $directive_values['Include'][$counter])) {
						$directive_values['Include'][$counter]['Options'] = [];
					}
					if (!key_exists($index, $directive_values['Include'][$counter]['Options'])) {
						$directive_values['Include'][$counter]['Options'][$index] = [];
					}
					$directive_values['Include'][$counter]['Options'][$index][$directive_name] = $directive_value;
				}

				$controls = $value->RepeaterFileSetInclude->findControlsByType($this->directive_types[$i]);
				for ($j = 0; $j < count($controls); $j++) {
					$controls[$j]->setValue();
					$directive_name = $controls[$j]->getDirectiveName();
					$directive_value = $controls[$j]->getDirectiveValue();
					if (empty($directive_value)) {
						// Include file directive removed
						continue;
					}
					if (!key_exists($counter, $directive_values['Include'])) {
						$directive_values['Include'][$counter] = [];
					}
					if (!key_exists($directive_name, $directive_values['Include'][$counter])) {
						$directive_values['Include'][$counter][$directive_name] = [];
					}
					$directive_values['Include'][$counter][$directive_name][] = $directive_value;
				}
				$controls = $value->RepeaterFileSetPlugin->findControlsByType($this->directive_types[$i]);
				for ($j = 0; $j < count($controls); $j++) {
					$controls[$j]->setValue();
					$directive_name = $controls[$j]->getDirectiveName();
					$directive_value = $controls[$j]->getDirectiveValue();
					if (empty($directive_value)) {
						// Include plugin directive removed
						continue;
					}
					if (!key_exists($counter, $directive_values['Include'])) {
						$directive_values['Include'][$counter] = [];
					}
					if (!key_exists($directive_name, $directive_values['Include'][$counter])) {
						$directive_values['Include'][$counter][$directive_name] = [];
					}
					$directive_values['Include'][$counter][$directive_name][] = $directive_value;
				}
			}
			for ($i = 0; $i < count($this->directive_list_types); $i++) {
				$controls = $value->RepeaterFileSetOptions->findControlsByType($this->directive_list_types[$i]);
				for ($j = 0; $j < count($controls); $j++) {
					$directive_name = $controls[$j]->getDirectiveName();
					$directive_value = array_filter($controls[$j]->getDirectiveValue());
					sort($directive_value);
					$index = $controls[$j]->getGroupName();
					if (count($directive_value) == 0) {
						// option not set or removed
						continue;
					}
					if (!key_exists($counter, $directive_values['Include'])) {
						$directive_values['Include'][$counter] = [];
					}
					if (!key_exists('Options', $directive_values['Include'][$counter])) {
						$directive_values['Include'][$counter]['Options'] = [];
					}
					if (!key_exists($index, $directive_values['Include'][$counter]['Options'])) {
						$directive_values['Include'][$counter]['Options'][$index] = [];
					}
					$directive_values['Include'][$counter]['Options'][$index][$directive_name] = $directive_value;
				}
			}
			$counter++;
		}
		for ($i = 0; $i < count($this->directive_types); $i++) {
			$controls = $this->RepeaterFileSetExclude->findControlsByType($this->directive_types[$i]);
			for ($j = 0; $j < count($controls); $j++) {
				$controls[$j]->setValue();
				$directive_name = $controls[$j]->getDirectiveName();
				$directive_value = $controls[$j]->getDirectiveValue();
				if (is_null($directive_value)) {
					// Exclude file directive removed
					continue;
				}
				if (!key_exists('File', $directive_values['Exclude'])) {
					$directive_values['Exclude']['File'] = [];
				}
				array_push($directive_values['Exclude']['File'], $directive_value);
			}
		}
		for ($i = 0; $i < count($directive_values['Include']); $i++) {
			if (!is_array($directive_values['Include'][$i]) || !key_exists('Options', $directive_values['Include'][$i])) {
				continue;
			}

			// First sort options by key to keep original order
			ksort($directive_values['Include'][$i]['Options']);

			/**
			 * Options $index can start from value greater than 0, so here reset indexes
			 * to avoid undefined offset error.
			 */
			$directive_values['Include'][$i]['Options'] = array_values($directive_values['Include'][$i]['Options']);
		}

		return $directive_values;
	}

	public function createFileSetIncludes($sender, $param)
	{
		$param->Item->RepeaterFileSetOptions->DataSource = $param->Item->Data['options'];
		$param->Item->RepeaterFileSetOptions->dataBind();
		$param->Item->RepeaterFileSetInclude->DataSource = $param->Item->Data['file'];
		$param->Item->RepeaterFileSetInclude->dataBind();
		$param->Item->RepeaterFileSetPlugin->DataSource = $param->Item->Data['plugin'];
		$param->Item->RepeaterFileSetPlugin->dataBind();
		$param->Item->FileSetFileOptMenu->setItemIndex($param->Item->getItemIndex());
	}

	public function createFileSetIncExcElement($sender, $param)
	{
		if (!is_array($param->Item->Data)) {
			// skip parent repeater items
			return;
		}
		for ($i = 0; $i < count($this->directive_inc_exc_types); $i++) {
			$control = $this->getChildControl($param->Item, $this->directive_inc_exc_types[$i]);
			if (is_object($control)) {
				$control->setHost($param->Item->Data['host']);
				$control->setComponentType($param->Item->Data['component_type']);
				$control->setComponentName($param->Item->Data['component_name']);
				$control->setResourceType($param->Item->Data['resource_type']);
				$control->setResourceName($param->Item->Data['resource_name']);
				$control->setDirectiveName($param->Item->Data['directive_name']);
				$control->setDirectiveValue($param->Item->Data['directive_value']);
				$control->setLabel($param->Item->Data['directive_name']);
				$control->setData($param->Item->Data['directive_value']);
				$control->setInConfig(true);
				$control->setShow(true);
				$control->setParentName($param->Item->Data['parent_name']);
			}
		}
	}

	public function newIncludeBlock($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$data['Include'][] = [];
		$this->setData($data);
		$this->loadConfig();
	}

	public function newIncludeFile($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$inc_index = $sender->Parent->getItemIndex();
		$file_index = 0;
		if (key_exists($inc_index, $data['Include']) && key_exists('File', $data['Include'][$inc_index])) {
			$file_index = count($data['Include'][$inc_index]['File']);
		}
		$data['Include'][$inc_index]['File'][$file_index] = '';
		$this->setData($data);
		$this->loadConfig();
	}

	public function newIncludePlugin($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$inc_index = $sender->Parent->getItemIndex();
		$plugin_index = 0;
		if (key_exists($inc_index, $data['Include']) && key_exists('Plugin', $data['Include'][$inc_index])) {
			$plugin_index = count($data['Include'][$inc_index]['Plugin']);
		}
		$data['Include'][$inc_index]['Plugin'][$plugin_index] = '';
		$this->setData($data);
		$this->loadConfig();
	}

	public function newExcludeFile($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$file_index = 0;
		if (key_exists('Exclude', $data) && is_array($data['Exclude']) && key_exists('File', $data['Exclude'])) {
			$file_index = count($data['Exclude']['File']);
		} else {
			$data['Exclude'] = ['File' => []];
		}
		$data['Exclude']['File'][$file_index] = '';
		$this->setData($data);
		$this->loadConfig();
	}

	public function newIncludeOptions($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$inc_index = $sender->Parent->getItemIndex();
		$opt_index = 0;
		if (key_exists($inc_index, $data['Include']) && key_exists('Options', $data['Include'][$inc_index])) {
			$opt_index = count($data['Include'][$inc_index]['Options']);
		}
		$data['Include'][$inc_index]['Options'][$opt_index] = [];
		$this->SourceTemplateControl->setShowAllDirectives(true);
		$this->setData($data);
		$this->loadConfig();
	}

	public function newIncludeExcludeFile($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$inc_index = $this->RepeaterFileSetIncludes->getItems()->getCount() - 1;
		$inc_exc = $param->getCallbackParameter();
		if (property_exists($inc_exc, 'Include') && is_array($inc_exc->Include)) {
			if (!key_exists($inc_index, $data['Include'])) {
				$data['Include'][$inc_index] = [];
			}
			if (!key_exists('File', $data['Include'][$inc_index])) {
				$data['Include'][$inc_index]['File'] = [];
			}
			for ($i = 0; $i < count($inc_exc->Include); $i++) {
				if (in_array($inc_exc->Include[$i], $data['Include'][$inc_index]['File'])) {
					// path already in includes, skip it to not double it
					continue;
				}
				$data['Include'][$inc_index]['File'][] = $inc_exc->Include[$i];
			}
		}
		if (property_exists($inc_exc, 'Exclude') && is_array($inc_exc->Exclude)) {
			if (!key_exists('File', $data['Exclude'])) {
				$data['Exclude']['File'] = [];
			}
			for ($i = 0; $i < count($inc_exc->Exclude); $i++) {
				if (in_array($inc_exc->Exclude[$i], $data['Exclude']['File'])) {
					// path already in includes, skip it to not double it
					continue;
				}
				$data['Exclude']['File'][] = $inc_exc->Exclude[$i];
			}
		}
		$this->setData($data);
		$this->loadConfig();
	}
}
