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
 * Message types control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class MessageTypes extends DirectiveListTemplate
{
	public function loadConfig()
	{
		$this->RepeaterMessageTypes->dataSource = $this->getData();
		$this->RepeaterMessageTypes->dataBind();
	}

	public function getDirectiveValues()
	{
		$type_controls = $this->RepeaterMessageTypes->findControlsByType('Bacularis\Web\Portlets\DirectiveCheckBoxSimple');
		$is_all = false;
		$types = [];
		for ($i = 0; $i < count($type_controls); $i++) {
			$type_controls[$i]->setValue();
			$directive_name = $type_controls[$i]->getDirectiveName();
			$directive_value = $type_controls[$i]->getDirectiveValue();
			if (is_null($directive_value) || $directive_value === false) {
				continue;
			}
			if ($directive_name === 'All' && $directive_value === true) {
				$is_all = true;
			}
			$neg = $directive_name != 'All' && $directive_value === true && $is_all === true ? '!' : '';
			array_push($types, "{$neg}{$directive_name}");
		}
		return $types;
	}

	public function createTypeListElement($sender, $param)
	{
		if (!is_array($param->Item->Data)) {
			// skip parent repeater items
			return;
		}
		$control = $this->getChildControl($param->Item, 'Bacularis\Web\Portlets\DirectiveCheckBoxSimple');
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
			$control->setData($param->Item->Data['directive_value']);
			$control->setInConfig($param->Item->Data['in_config']);
			$control->setShow($param->Item->Data['show']);
			$control->setParentName($param->Item->Data['parent_name']);
		}
	}
}
