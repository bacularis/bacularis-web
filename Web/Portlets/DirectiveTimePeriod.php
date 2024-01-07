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

use Prado\Prado;

/**
 * Time period directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveTimePeriod extends DirectiveTemplate
{
	public const TIME_FORMAT = 'TimeFormat';
	public const DEFAULT_TIME_FORMAT = 'day';

	private $time_formats = [
		['format' => 'second', 'value' => 1, 'label' => 'Seconds'],
		['format' => 'minute', 'value' => 60, 'label' => 'Minutes'],
		['format' => 'hour', 'value' => 60, 'label' => 'Hours'],
		['format' => 'day', 'value' => 24, 'label' => 'Days']
	];

	public function getValue()
	{
		$value = $this->Directive->Text;
		if (is_numeric($value)) {
			settype($value, 'integer');
			$time_format = $this->TimeFormat->SelectedValue;
			$value = $this->getValueSeconds($value, $time_format);
		} else {
			$value = null;
		}
		return $value;
	}

	public function getTimeFormat()
	{
		return $this->getViewState(self::TIME_FORMAT, self::DEFAULT_TIME_FORMAT);
	}

	public function setTimeFormat($format)
	{
		$this->setViewState(self::TIME_FORMAT, $format);
	}

	public function getTimeFormats()
	{
		$time_formats = [];
		for ($i = 0; $i < count($this->time_formats); $i++) {
			$format = [
				'label' => Prado::localize($this->time_formats[$i]['label']),
				'format' => $this->time_formats[$i]['format']
			];
			array_push($time_formats, $format);
		}
		return $time_formats;
	}

	public function createDirective()
	{
		$time_format = $this->getTimeFormat();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if ($this->getInConfig() === false && empty($directive_value)) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = 0;
			}
		}
		$formatted_value = $this->formatTimePeriod($directive_value, $time_format);
		$this->Directive->Text = $formatted_value['value'];
		$this->TimeFormat->DataSource = $this->getTimeFormats();
		$this->TimeFormat->SelectedValue = $formatted_value['format'];
		$this->TimeFormat->dataBind();
		$this->Label->Text = $this->getLabel();
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
	}

	/**
	 * Convert original time period in seconds into given time format.
	 *
	 * Note, if there is not possible to convert time period into given format
	 * then there will be returned value converted by using as close format as possible.
	 * Example:
	 *  time_seconds: 5184060
	 *  given format: day
	 *  returned value: 86401
	 *  returned format: minute
	 * @param mixed $time_seconds
	 * @param mixed $format
	 */
	private function formatTimePeriod($time_seconds, $format)
	{
		$value = $time_seconds;
		for ($i = 0; $i < count($this->time_formats); $i++) {
			if ($this->time_formats[$i]['format'] != $format) {
				$remainder = $value % $this->time_formats[$i]['value'];
				if ($remainder === 0) {
					$value /= $this->time_formats[$i]['value'];
					$format = $this->time_formats[$i]['format'];
					continue;
				}
				break;
			}
			break;
		}
		$result = ['value' => $value, 'format' => $format];
		return $result;
	}

	private function getValueSeconds($value, $time_format)
	{
		for ($i = 0; $i < count($this->time_formats); $i++) {
			$value *= $this->time_formats[$i]['value'];
			if ($this->time_formats[$i]['format'] === $time_format) {
				break;
			}
		}
		return $value;
	}
}
