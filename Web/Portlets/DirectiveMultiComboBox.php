<?php
/*
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

Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveDropDownList');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('Application.Web.Portlets.DirectiveListTemplate');

/**
 * Multi-combobox directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class DirectiveMultiComboBox extends DirectiveListTemplate {

	public function dataBind() {
		$this->loadConfig();
	}

	public function getDirectiveValue() {
		$values = array();
		$controls = $this->MultiComboBoxRepeater->getItems();
		foreach ($controls as $control) {
			$val = $control->Directive->getSelectedValue();
			if (!empty($val)) {
				$values[] = $val;
			}
		}
		return $values;
	}

	public function loadConfig() {
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$directive_name = $this->getDirectiveName();
		$resource = $this->getResource();
		$resource_names = $this->getResourceNames();
		$data = $this->getData();
		$items = array();

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
		$values = array();
		for ($i = 0; $i < count($data); $i++) {
			$values[] = array(
				'items' => $items,
				'directive_value' => $data[$i],
				'label' => $this->getDirectiveName(),
				'show' => $this->getShow()
			);
		}
		$this->MultiComboBoxRepeater->DataSource = $values;
		$this->MultiComboBoxRepeater->dataBind();
	}

	public function createMultiComboBoxElement($sender, $param) {
		$param->Item->Label->Text = $param->Item->Data['label'];
		$param->Item->Directive->DataSource = array_combine($param->Item->Data['items'], $param->Item->Data['items']);
		$param->Item->Directive->setSelectedValue($param->Item->Data['directive_value']);
		$param->Item->Directive->dataBind();
	}

	public function addField($sender, $param) {
		$data = $this->getDirectiveValue();
		$data[] = '';
		$this->setData($data);
		$this->loadConfig();
	}
}
?>
