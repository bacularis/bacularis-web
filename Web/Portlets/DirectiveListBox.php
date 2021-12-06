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

Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveListBox');
Prado::using('Application.Web.Portlets.DirectiveTemplate');

/**
 * ListBox directive template.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class DirectiveListBox extends DirectiveTemplate {

	public function getValue() {
		$value = array();
		$values = $this->Directive->getSelectedIndices();
		$items = $this->Directive->getItems();
		for ($i = 0; $i < count($values); $i++) {
			$value[] = $items[$values[$i]]->getValue();
		}
		return $value;
	}

	public function createDirective() {
		$this->Label->Text = $this->getLabel();
		$data = $this->getData();
		$resource_names = $this->getResourceNames();
		$directive_name = $this->getDirectiveName();
		$resource = $this->getResource();
		$in_config = $this->getInConfig();
		$items = array();
		if (is_array($data)) {
			$items = $data;
		} elseif (is_array($resource_names)) {
			if (array_key_exists($directive_name, $resource_names)) {
				$items = $resource_names[$directive_name];
			} elseif (array_key_exists($resource, $resource_names)) {
				$items = $resource_names[$resource];
			}
		}
		$this->Directive->DataSource = array_combine($items, $items);

		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if ($in_config === false) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = array();
			}
		}
		$selected_indices = array();
		for ($i = 0; $i < count($items); $i++) {
			if (in_array($items[$i], $directive_value)) {
				$selected_indices[] = $i;
			}
		}
		if (!empty($directive_value)) {
			$this->Directive->setSelectedIndices($selected_indices);
		}
		$this->Directive->dataBind();
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
	}
}
?>
