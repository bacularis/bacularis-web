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
 * Combobox directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveComboBox extends DirectiveTemplate
{
	public function getValue()
	{
		$value = $this->Directive->getSelectedValue();
		if (!is_string($value) || empty($value)) {
			$value = null;
		}
		return $value;
	}

	public function createDirective()
	{
		$this->Label->Text = $this->getLabel();
		$data = $this->getData();
		$resource_names = $this->getResourceNames();
		$directive_name = $this->getDirectiveName();
		$resource = $this->getResource();
		$in_config = $this->getInConfig();
		$items = [];
		if (is_array($data)) {
			$items = $data;
		}
		if (is_array($resource_names)) {
			$ritems = [];
			if (key_exists($directive_name, $resource_names)) {
				$ritems = $resource_names[$directive_name];
			} elseif (key_exists($resource, $resource_names)) {
				$ritems = $resource_names[$resource];
			}
			if (count($items) > 0) {
				$items = array_merge($items, $ritems);
			} else {
				$items = $ritems;
			}
		}

		reset($items);
		if (key($items) === 0) {
			// indexed array as data source
			array_unshift($items, '');
			$items = array_combine($items, $items);
		} elseif (is_string(key($items))) {
			// associative array as data source
			$items = array_merge(['' => ''], $items);
		}
		$this->Directive->DataSource = $items;

		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if ($in_config === false && empty($directive_value)) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = '';
			}
		}
		if (!empty($directive_value)) {
			$this->Directive->setSelectedValue($directive_value);
		}
		$this->Directive->dataBind();
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
		$cssclass = $this->getCssClass();
		if ($cssclass) {
			$cssclass .= ' ' . $this->Directive->getCssClass();
			$this->Directive->setCssClass($cssclass);
		}
	}
}
