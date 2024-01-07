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
 * Copyright (C) 2013-2019 Kern Sibbald
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
 * Multi-combobox directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveMultiComboBox extends DirectiveListTemplate
{
	public function dataBind()
	{
		$this->loadConfig();
	}

	public function getDirectiveValue($data_mode = false)
	{
		$values = [];
		$controls = $this->MultiComboBoxRepeater->getItems();
		foreach ($controls as $control) {
			$val = $control->Directive->getSelectedValue();
			if (!empty($val)) {
				$values[] = $val;
			}
		}
		if ($data_mode && count($values) === 0) {
			$values[] = '';
		}
		return $values;
	}

	public function getDirectiveData()
	{
		return $this->getDirectiveValue(true);
	}

	public function loadConfig()
	{
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$directive_name = $this->getDirectiveName();
		$resource = $this->getResource();
		$resource_names = $this->getResourceNames();
		$data = $this->getData();
		$items = [];

		if (!is_array($data)) {
			if ($this->getShow()) {
				$data = [$data];
			} else {
				$data = [];
			}
		}
		if (is_array($resource_names)) {
			if (key_exists($directive_name, $resource_names)) {
				$items = $resource_names[$directive_name];
			} elseif (key_exists($resource, $resource_names)) {
				$items = $resource_names[$resource];
			}
		}

		/**
		 * Dirty hack to support *all* keyword in resource name list
		 * @TODO: Add an control property to support this type cases
		 */
		if ($resource_type == 'Console' && preg_match('/Acl$/i', $directive_name) == 1) {
			array_unshift($items, '*all*');
		}

		array_unshift($items, '');
		$values = [];
		for ($i = 0; $i < count($data); $i++) {
			$values[] = [
				'items' => $items,
				'directive_value' => $data[$i],
				'label' => $this->getDirectiveName(),
				'show' => $this->getShow()
			];
		}
		$this->MultiComboBoxRepeater->DataSource = $values;
		$this->MultiComboBoxRepeater->dataBind();
	}

	public function createMultiComboBoxElement($sender, $param)
	{
		$param->Item->Label->Text = $param->Item->Data['label'];
		$param->Item->Directive->DataSource = array_combine($param->Item->Data['items'], $param->Item->Data['items']);
		$param->Item->Directive->setSelectedValue($param->Item->Data['directive_value']);
		$param->Item->Directive->dataBind();
	}

	public function addField($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$data[] = '';
		$this->setData($data);
		$this->loadConfig();
	}
}
