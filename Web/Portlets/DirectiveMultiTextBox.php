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
Prado::using('System.Web.UI.ActiveControls.TActiveTextBox');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('Application.Web.Portlets.DirectiveListTemplate');

/**
 * Multi-textbox directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class DirectiveMultiTextBox extends DirectiveListTemplate {

	public function dataBind() {
		$this->loadConfig();
	}

	public function getDirectiveValue() {
		$values = array();
		$controls = $this->MultiTextBoxRepeater->getItems();
		foreach ($controls as $control) {
			$val = $control->Directive->getText();
			if (!empty($val)) {
				$values[] = $val;
			} else {
				$values[] = null;
			}
		}
		return $values;
	}

	public function loadConfig() {
		$resource_type = $this->getResourceType();
		$resource_name = $this->getResourceName();
		$directive_name = $this->getDirectiveName();

		$data = $this->getData();
		if (!is_array($data)) {
			if ($this->getShow()) {
				$data = [$data];
			} else {
				$data = [];
			}
		}
		$values = array();
		for ($i = 0; $i < count($data); $i++) {
			$values[] = array(
				'directive_value' => $data[$i],
				'label' => $this->getDirectiveName(),
				'show' => $this->getShow()
			);
		}
		$this->MultiTextBoxRepeater->DataSource = $values;
		$this->MultiTextBoxRepeater->dataBind();
	}

	public function createMultiTextBoxElement($sender, $param) {
		$param->Item->Label->Text = $param->Item->Data['label'];
		$param->Item->Directive->Text = $param->Item->Data['directive_value'];
	}

	public function addField($sender, $param) {
		$data = $this->getDirectiveValue();
		$data[] = '';
		$this->setData($data);
		$this->loadConfig();
	}
}
?>
