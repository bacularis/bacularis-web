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
use Bacularis\Common\Modules\Params;

/**
 * Days of week directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveDaysOfWeek extends DirectiveTemplate
{
	public const SHOW_OPTIONS = 'ShowOptions';

	public function getValue()
	{
		$value = [];
		$wdays = array_values(Params::$wdays);
		for ($i = 0; $i < count($wdays); $i++) {
			if ($this->{$wdays[$i]}->Checked || $this->AllDaysOfWeek->Checked) {
				$value[] = $i;
			}
		}
		if (count($value) == 0) {
			$value = range(0, 6);
		}
		return $value;
	}

	public function createDirective()
	{
		$this->Label->Text = $this->getLabel();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		$wdays = array_values(Params::$wdays);
		if (!is_array($directive_value)) {
			$directive_value = $wdays;
		}
		$wd_len = count($wdays);
		$dv_len = count($directive_value);
		if ($this->getInConfig() === false && $dv_len == $wd_len) {
			if (is_array($default_value) && count($default_value) > 0) {
				$directive_value = $default_value;
			}
		}

		for ($i = 0; $i < $wd_len; $i++) {
			if ($dv_len < $wd_len) {
				// selected days
				$this->{$wdays[$i]}->Checked = in_array($i, $directive_value);
			}
			if ($this->Disabled) {
				$this->{$wdays[$i]}->Enabled = false;
			}
			if ($this->CssClass) {
				$cssclass = $this->CssClass . ' ' . $this->{$wdays[$i]}->getCssClass();
				$this->{$wdays[$i]}->setCssClass($cssclass);
			}
		}
		if ($this->Disabled) {
			$this->AllDaysOfWeek->Enabled = false;
		} elseif ($dv_len == $wd_len && $this->ShowOptions) {
			// all days
			$this->AllDaysOfWeek->Checked = true;
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
