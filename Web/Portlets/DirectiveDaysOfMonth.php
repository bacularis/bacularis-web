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

use Prado\TPropertyValue;

/**
 * Days of month directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveDaysOfMonth extends DirectiveTemplate
{
	public const SHOW_OPTIONS = 'ShowOptions';
	public const DAY_CONTROL_PREFIX = 'day';

	public function getValue()
	{
		$value = [];
		$days = range(1, 31);
		for ($i = 0; $i < count($days); $i++) {
			if ($this->{self::DAY_CONTROL_PREFIX . $days[$i]}->Checked || $this->AllDaysOfMonth->Checked) {
				$value[] = $i;
			}
		}
		if (count($value) == 0) {
			$value = $days;
		}
		if ($this->lastday->Checked) {
			$value = [];
		}
		return $value;
	}

	public function createDirective()
	{
		$this->Label->Text = $this->getLabel();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if (!is_array($directive_value)) {
			$directive_value = range(0, 30);
		}

		$days = range(1, 31);
		$d_len = count($days);
		$dv_len = count($directive_value);
		if ($this->getInConfig() === false && $dv_len == $d_len) {
			if (is_array($default_value) && count($default_value) > 0) {
				$directive_value = $default_value;
			}
		}

		for ($i = 0; $i < count($days); $i++) {
			$key = self::DAY_CONTROL_PREFIX . $days[$i];
			if ($dv_len > 0 && $dv_len < $d_len) {
				// selected days
				$this->{$key}->Checked = in_array($i, $directive_value);
			}

			if ($this->Disabled) {
				$this->{$key}->Enabled = false;
			}
			if ($this->CssClass) {
				$cssclass = $this->CssClass . ' ' . $this->{$key}->getCssClass();
				$this->{$key}->setCssClass($cssclass);
			}
		}

		// set 'select all' checkbox
		if ($this->Disabled) {
			$this->AllDaysOfMonth->Enabled = false;
		} elseif ($dv_len == $d_len && $this->ShowOptions) {
			// all days
			$this->AllDaysOfMonth->Checked = true;
		}

		// set 'last day of the month' checkbox
		if ($dv_len == 0) {
			$this->lastday->Checked = true;
		} else {
			$this->lastday->Checked = false;
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
