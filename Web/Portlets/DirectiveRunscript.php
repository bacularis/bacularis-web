<?php
/*
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

Prado::using('Application.Web.Portlets.DirectiveListTemplate');
Prado::using('Application.Web.Portlets.DirectiveCheckBox');
Prado::using('Application.Web.Portlets.DirectiveTextBox');
Prado::using('Application.Web.Portlets.DirectiveComboBox');

/**
 * Runscript directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class DirectiveRunscript extends DirectiveListTemplate {

	private $directive_types = array(
		'DirectiveCheckBox',
		'DirectiveComboBox',
		'DirectiveTextBox'
	);

	public function loadConfig() {
		$load_values = $this->getLoadValues();
		$host = $this->getHost();
		$component_type = $this->getComponentType();
		$component_name = $this->getComponentName();
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();

		$directives = $this->getData();
		$options = array();
		if (!is_array($directives)) {
			return;
		}
		$resource_desc = $this->Application->getModule('data_desc')->getDescription($component_type, $resource_type, 'Runscript');
		foreach ($directives as $index => $config) {
			for ($i = 0; $i < count($config); $i++) {
				if (!is_object($config[$i])) {
					continue;
				}
				foreach ($resource_desc->SubSections as $directive_name => $directive_desc) {
					$in_config = property_exists($config[$i], $directive_name);

					$directive_value = null;
					if ($in_config === true) {
						$directive_value = $config[$i]->{$directive_name};
					}

					/**
					 * Because of bug in bdirjson: http://bugs.bacula.org/view.php?id=2464
					 * Here is workaround for bdirjson from Bacula versions without fix for it.
					 */
					if ($directive_name === 'RunsWhen' && $directive_value === 'Any') {
						$directive_value = 'Always';
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
					if (!is_array($directive_value)) {
						$directive_value = array($directive_value);
					}
					for ($j = 0; $j < count($directive_value); $j++) {
						$options[] = array(
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
							'label' => $directive_name,
							'field_type' => $field_type,
							'in_config' => $in_config,
							'show' => ($in_config || !$load_values || $this->SourceTemplateControl->getShowAllDirectives()),
							'parent_name' => __CLASS__,
							'group_name' => $i
						);
					}
				}
			}
		}
		$this->RepeaterRunscriptOptions->dataSource = $options;
		$this->RepeaterRunscriptOptions->dataBind();
	}

	public function getDirectiveValue() {
		$directive_values = null;
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$resource_desc = $this->Application->getModule('data_desc')->getDescription($component_type, $resource_type);

		for ($i = 0; $i < count($this->directive_types); $i++) {
			$controls = $this->RepeaterRunscriptOptions->findControlsByType($this->directive_types[$i]);
			for ($j = 0; $j < count($controls); $j++) {
				$controls[$j]->setValue();
				$directive_name = $controls[$j]->getDirectiveName();
				$directive_value = $controls[$j]->getDirectiveValue();
				$default_value = null;
				if (property_exists($resource_desc['Runscript']->SubSections, $directive_name)) {
					$default_value = $resource_desc['Runscript']->SubSections->{$directive_name}->DefaultValue;
				}
				$in_config = $controls[$j]->getInConfig();
				$index = $controls[$j]->getGroupName();

				if (is_null($directive_value)) {
					// skip not changed values that don't exist in config
					continue;
				}
				if ($this->directive_types[$i] === 'DirectiveCheckBox') {
					settype($default_value, 'bool');
				}
				if ($directive_value === $default_value) {
					// value the same as default value, skip it
					continue;
				}

				if (!isset($directive_values['Runscript'])) {
					$directive_values = array('Runscript' => array());
				}
				if (!isset($directive_values['Runscript'][$index])) {
					$directive_values['Runscript'][$index] = new stdClass;
				}

				$directive_values['Runscript'][$index]->{$directive_name} = $directive_value;
			}
		}
		return $directive_values;
	}

	public function removeRunscript($sender, $param) {
		if ($param instanceof Prado\Web\UI\TCommandEventParameter) {
			$idx = $param->getCommandName();
			$data = $this->getDirectiveValue();
			if (is_array($data)) {
				array_splice($data['Runscript'], $idx, 1);
				$this->setData($data);
				$this->loadConfig();
			}
		}
	}

	public function newRunscriptDirective() {
		$data = $this->getDirectiveValue();
		if (is_array($data) && key_exists('Runscript', $data) && is_array($data['Runscript'])) {
			$data['Runscript'][] = new stdClass;
		} else {
			$data = array('Runscript' => array(new stdClass));
		}
		$this->setData($data);
		$this->SourceTemplateControl->setShowAllDirectives(true);
		$this->loadConfig();
	}
}
