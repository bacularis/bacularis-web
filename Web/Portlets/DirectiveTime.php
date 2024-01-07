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
 * Time directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveTime extends DirectiveTemplate
{
	public const SHOW_HOUR = 'ShowHour';
	public const SHOW_MINUTE = 'ShowMinute';

	public function getValue()
	{
		$hour = (int) $this->Hour->getSelectedValue();
		if (!$this->ShowHour) {
			$hour = null;
		}
		$minute = (int) $this->Minute->getSelectedValue();
		if (!$this->ShowMinute) {
			$minute = null;
		}
		return $this->getTimeValue($hour, $minute);
	}

	private function getTimeValue($hour = null, $minute = null)
	{
		return ['hour' => $hour, 'minute' => $minute];
	}

	public function createDirective()
	{
		$this->Label->Text = $this->getLabel();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if (!is_array($directive_value)) {
			$directive_value = $this->getTimeValue();
		}
		if ($this->getInConfig() === false && is_null($directive_value['hour']) && is_null($directive_value['minute'])) {
			if (is_array($default_value) && !is_null($default_value['hour']) && !is_null($default_value['minute'])) {
				$directive_value = $default_value;
			} else {
				$directive_value = $this->getTimeValue(0, 0);
			}
		}
		$hours = range(0, 23);
		$this->Hour->DataSource = array_map(function ($h) {
			return sprintf('%02d', $h);
		}, $hours);
		$this->Hour->setSelectedValue($directive_value['hour']);
		$this->Hour->dataBind();

		$minutes = range(0, 59);
		$this->Minute->DataSource = array_map(function ($m) {
			return sprintf('%02d', $m);
		}, $minutes);
		$this->Minute->setSelectedValue($directive_value['minute']);
		$this->Minute->dataBind();
		if ($this->getDisabled()) {
			$this->Hour->setReadOnly(true);
			$this->Minute->setReadOnly(true);
		}
		$validate = $this->getRequired();
		$this->TimeValidator->setVisible($validate);
		$cssclass = $this->getCssClass();
		if ($cssclass) {
			$hcssclass = $cssclass . ' ' . $this->Hour->getCssClass();
			$this->Hour->setCssClass($hcssclass);
			$mcssclass = $cssclass . ' ' . $this->Minute->getCssClass();
			$this->Minute->setCssClass($mcssclass);
		}
	}

	public function getShowHour()
	{
		return $this->getViewState(self::SHOW_HOUR, true);
	}

	public function setShowHour($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_HOUR, $show);
	}

	public function getShowMinute()
	{
		return $this->getViewState(self::SHOW_MINUTE, true);
	}

	public function setShowMinute($show)
	{
		$show = TPropertyValue::ensureBoolean($show);
		$this->setViewState(self::SHOW_MINUTE, $show);
	}
}
