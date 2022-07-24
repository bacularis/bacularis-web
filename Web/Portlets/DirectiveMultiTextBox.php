<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2022 Marcin Haba
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

use Prado\Web\UI\ActiveControls\TActiveLabel;
use Prado\Web\UI\ActiveControls\TActiveTextBox;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Bacularis\Web\Portlets\DirectiveListTemplate;

/**
 * Multi-textbox directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveMultiTextBox extends DirectiveListTemplate
{
	public function dataBind()
	{
		$this->loadConfig();
	}

	public function getDirectiveValue()
	{
		$values = [];
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

	public function loadConfig()
	{
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
		$values = [];
		for ($i = 0; $i < count($data); $i++) {
			$values[] = [
				'directive_value' => $data[$i],
				'label' => $this->getDirectiveName(),
				'show' => $this->getShow()
			];
		}
		$this->MultiTextBoxRepeater->DataSource = $values;
		$this->MultiTextBoxRepeater->dataBind();
	}

	public function createMultiTextBoxElement($sender, $param)
	{
		$param->Item->Label->Text = $param->Item->Data['label'];
		$param->Item->Directive->Text = $param->Item->Data['directive_value'];
	}

	public function addField($sender, $param)
	{
		$data = $this->getDirectiveValue();
		$data[] = '';
		$this->setData($data);
		$this->loadConfig();
	}
}
