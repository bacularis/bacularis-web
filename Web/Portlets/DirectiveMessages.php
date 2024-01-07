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

use Prado\Web\UI\TCommandEventParameter;

/**
 * Messages directive.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveMessages extends DirectiveListTemplate
{
	private $directive_types = [
		'Bacularis\Web\Portlets\DirectiveTextBox'
	];

	public $destination_simple = [
		'Console',
		'Stdout',
		'Stderr',
		'Syslog',
		'Catalog'
	];

	public $destination_address = [
		'Director',
		'File',
		'Append',
		'Mail',
		'MailOnError',
		'MailOnSuccess',
		'Operator'
	];

	private $messages_types = [
		'All',
		'Debug',
		'Info',
		'Warning',
		'Error',
		'Fatal',
		'Terminate',
		'Saved',
		'Skipped',
		'Mount',
		'Restored',
		'Security',
		'Alert',
		'VolMgmt'
	];

	public function loadConfig()
	{
		$dests = $this->getData();
		if (key_exists('Destinations', $dests)) {
			$dests = array_filter($dests['Destinations']);
		}
		$directives = [];
		for ($i = 0; $i < count($dests); $i++) {
			$dest = (array) $dests[$i];
			$is_address_type = in_array($dest['Type'], $this->destination_address);
			$directive_value = null;
			if ($is_address_type && key_exists('Where', $dest)) {
				$directive_value = implode(',', $dest['Where']);
			}
			$this->setDirectiveName($dest['Type']);
			$directives[$i] = [
				'host' => $this->getHost(),
				'component_type' => $this->getComponentType(),
				'component_name' => $this->getComponentName(),
				'resource_type' => $this->getResourceType(),
				'resource_name' => $this->getResourceName(),
				'directive_name' => $dest['Type'],
				'directive_value' => $directive_value,
				'default_value' => false,
				'required' => false,
				'label' => $dest['Type'],
				'field_type' => 'TextBox',
				'in_config' => true,
				'show' => true,
				'parent_name' => __CLASS__,
				'is_address_type' => $is_address_type,
				'messages_types' => []
			];
			$value_all = $value_not = null;
			for ($j = 0; $j < count($this->messages_types); $j++) {
				$value_all = in_array('!' . $this->messages_types[$j], $dest['MsgTypes']);
				$value_not = in_array($this->messages_types[$j], $dest['MsgTypes']);
				$directives[$i]['messages_types'][] = [
					'host' => $this->getHost(),
					'component_type' => $this->getComponentType(),
					'component_name' => $this->getComponentName(),
					'resource_type' => $this->getResourceType(),
					'resource_name' => $this->getResourceName(),
					'directive_name' => $this->messages_types[$j],
					'directive_value' => ($value_all || $value_not),
					'default_value' => false,
					'required' => false,
					'label' => $this->messages_types[$j],
					'field_type' => 'Messages',
					'data' => $dest['Type'],
					'in_config' => true,
					'show' => true,
					'parent_name' => __CLASS__
				];
			}
		}
		$this->RepeaterMessages->dataSource = $directives;
		$this->RepeaterMessages->dataBind();
	}

	public function getDirectiveValue()
	{
		$values = [];
		$controls = $this->RepeaterMessages->getControls();
		for ($i = 0; $i < $controls->count(); $i++) {
			$directive_values = [];
			$where_control = $controls->itemAt($i)->findControlsByType('Bacularis\Web\Portlets\DirectiveTextBox');
			if (count($where_control) === 1 && $where_control[0]->getShow() === true) {
				$directive_values = [$where_control[0]->getDirectiveValue()];
			}
			$types_control = $controls->itemAt($i)->Types;
			$types = $types_control->getDirectiveValues();
			$directive_name = $types_control->getDirectiveName();
			if (count($types) > 0 && count($directive_values) > 0) {
				array_push($directive_values, '=');
			}
			array_push($directive_values, implode(', ', $types));
			$directive_values = array_filter($directive_values);
			if (count($directive_values) === 0) {
				continue;
			}
			if (!key_exists($directive_name, $values)) {
				$values[$directive_name] = [];
			}
			$values[$directive_name][] = implode(' ', $directive_values);
		}
		return $values;
	}

	public function getDirectiveData()
	{
		$values = [];
		$controls = $this->RepeaterMessages->getItems();
		foreach ($controls as $control) {
			$directive_values = [];
			$where_control = $control->findControlsByType('Bacularis\Web\Portlets\DirectiveTextBox');
			if (count($where_control) === 1 && $where_control[0]->getShow() === true) {
				$where_control[0]->setValue();
				$directive_values['Where'] = [$where_control[0]->getDirectiveValue()];
			}
			$types_control = $control->Types;
			$directive_values['MsgTypes'] = $types_control->getDirectiveValues();
			$directive_values['Type'] = $types_control->getDirectiveName();
			array_push($values, $directive_values);
		}
		return $values;
	}

	public function createDirectiveListElement($sender, $param)
	{
		if (!is_array($param->Item->Data)) {
			// skip parent repeater items
			return;
		}
		for ($i = 0; $i < count($this->directive_types); $i++) {
			$control = $this->getChildControl($param->Item, $this->directive_types[$i]);
			if (is_object($control)) {
				$control->setHost($param->Item->Data['host']);
				$control->setComponentType($param->Item->Data['component_type']);
				$control->setComponentName($param->Item->Data['component_name']);
				$control->setResourceType($param->Item->Data['resource_type']);
				$control->setResourceName($param->Item->Data['resource_name']);
				$control->setDirectiveName($param->Item->Data['directive_name']);
				$control->setDirectiveValue($param->Item->Data['directive_value']);
				$control->setDefaultValue($param->Item->Data['default_value']);
				$control->setRequired($param->Item->Data['required']);
				$control->setLabel($param->Item->Data['label']);
				$control->setInConfig($param->Item->Data['in_config']);
				$control->setShow($param->Item->Data['is_address_type']);
				$control->setParentName($param->Item->Data['parent_name']);
				break;
			}
		}
		$param->Item->Types->setData($param->Item->Data['messages_types']);
		$param->Item->Types->setDirectiveName($param->Item->Data['directive_name']);
	}

	public function loadMessageTypes($sender, $param)
	{
		$param->Item->Types->loadConfig();
	}

	public function newMessagesDirective($sender, $param)
	{
		$data = $this->getDirectiveData();
		$msg_type = $sender->getID();
		array_push($data, ['Type' => $msg_type, 'MsgTypes' => []]);
		$this->SourceTemplateControl->setShowAllDirectives(true);
		$this->setData($data);
		$this->loadConfig();
	}

	public function removeMessages($sender, $param)
	{
		if ($param instanceof TCommandEventParameter) {
			$idx = $param->getCommandName();
			$data = $this->getDirectiveData();
			array_splice($data, $idx, 1);
			$this->setData($data);
			$this->loadConfig();
		}
	}
}
