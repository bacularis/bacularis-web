<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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
 * TextBox directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveTextBox extends DirectiveTemplate
{
	public function getValue()
	{
		$value = $this->Directive->getText();
		if (empty($value)) {
			$value = null;
		}
		return $value;
	}

	public function createDirective()
	{
		$this->Label->Text = $this->getLabel();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if ($this->getInConfig() === false && empty($directive_value)) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = '';
			}
		}
		$this->Directive->setText($directive_value);
		if ($this->getDisabled()) {
			$this->Directive->setReadOnly(true);
		}
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
		$cssclass = $this->getCssClass();
		if ($cssclass) {
			$cssclass .= ' ' . $this->Directive->getCssClass();
			$this->Directive->setCssClass($cssclass);
		}
	}
}
