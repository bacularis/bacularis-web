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

use Prado\TPropertyValue;
use Bacularis\Common\Modules\Params;

/**
 * Weeks of month directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveWeeksOfMonth extends DirectiveTemplate
{
	public const SHOW_OPTIONS = 'ShowOptions';

	public function getValue()
	{
		$value = [];
		$weeks = array_values(Params::$weeks);
		for ($i = 0; $i < count($weeks); $i++) {
			if ($this->{$weeks[$i]}->Checked || $this->AllWeeksOfMonth->Checked) {
				$value[] = $i;
			}
		}
		return $value;
	}

	public function createDirective()
	{
		$this->Label->Text = $this->getLabel();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		$weeks = array_values(Params::$weeks);
		if (!is_array($directive_value)) {
			$directive_value = $weeks;
		}

		$w_len = count($weeks);
		$dv_len = count($directive_value);
		if ($this->getInConfig() === false && $dv_len == $w_len) {
			if (is_array($default_value) && count($default_value) > 0) {
				$directive_value = $default_value;
			}
		}

		for ($i = 0; $i < $w_len; $i++) {
			if ($dv_len < $w_len) {
				// selected weeks
				$this->{$weeks[$i]}->Checked = in_array($i, $directive_value);
			}
			if ($this->Disabled) {
				$this->{$weeks[$i]}->Enabled = false;
			}
			if ($this->CssClass) {
				$cssclass = $this->CssClass . ' ' . $this->{$weeks[$i]}->getCssClass();
				$this->{$weeks[$i]}->setCssClass($cssclass);
			}
		}
		if ($this->Disabled) {
			$this->AllWeeksOfMonth->Enabled = false;
		} elseif ($dv_len == $w_len && $this->ShowOptions) {
			// all weeks
			$this->AllWeeksOfMonth->Checked = true;
		}
	}

	public function setShowOptions($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_OPTIONS, $show);
	}

	public function getShowOptions()
	{
		return $this->getViewState(self::SHOW_OPTIONS, false);
	}
}
