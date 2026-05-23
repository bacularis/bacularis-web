<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2026 Marcin Haba
 *
 * The main author of Bacularis is Marcin Haba, with contributors, whose
 * full list can be found in the AUTHORS file.
 *
 * You may use this file and others of this release according to the
 * license defined in the LICENSE file, which includes the Affero General
 * Public License, v3.0 ("AGPLv3") and some additional permissions and
 * terms pursuant to its AGPLv3 Section 7.
 */

namespace Bacularis\Web\Portlets;

/**
 * Combobox directive control with reload on change capability.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveComboBoxReload extends DirectiveComboBox
{
	public function saveValue($sender, $param, $name = '')
	{
		parent::saveValue($sender, $param);
		$control = $this->getSourceTemplateControl();
		if ($this->getCmdParam() === 'save') {
			// reset control data to not remember it longer after saving
			$control->setData([]);
		}
	}

	public function reloadValue($sender, $param)
	{
		if ($this->getCmdParam() === 'save') {
			/**
			 * When directive is saved, we cannot loadConfig
			 * because it resets all saved values and saves nothing.
			 */
			return;
		} elseif (!$this->getDirectiveValue() && !$this->getValue()) {
			/**
			 * Case for empty selected value.
			 * Do not reload it if selected index has not been really changed.
			 */
			return;
		}
		$this->setValue();
		$control = $this->getSourceTemplateControl();
		$control->setShowAllDirectives(true);
		$control->loadConfig($sender, $param, 'oncallback');
	}
}
