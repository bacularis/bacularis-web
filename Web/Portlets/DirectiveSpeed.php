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
Prado::using('System.Web.UI.ActiveControls.TActiveDropDownList');
Prado::using('Application.Web.Portlets.DirectiveTemplate');

/**
 * Speed directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class DirectiveSpeed extends DirectiveTemplate {

	const ALLOW_REMOVE = 'AllowRemove';
	const SPEED_FORMAT = 'SpeedFormat';
	const UNIT_TYPE = 'UnitType';
	const DEFAULT_SPEED_FORMAT = '';

	const DECIMAL_UNIT_TYPE = 'decimal';
	const BINARY_UNIT_TYPE = 'binary';

	private $units = array(
		array('format' => '', 'value' => 1, 'label' => 'B/s'),
		array('format' => 'kb/s', 'value' => 1000, 'label' => 'kB/s'),
		array('format' => 'k/s', 'value' => 1024, 'label' => 'KiB/s'),
		array('format' => 'mb/s', 'value' => 1000000, 'label' => 'MB/s'),
		array('format' => 'm/s', 'value' => 1048576, 'label' => 'MiB/s')
	);

	public function getValue() {
		$value = $this->Directive->Text;
		if (is_numeric($value)) {
			settype($value, 'integer');
			$speed_format = $this->SpeedFormat->SelectedValue;
			$value = $this->getValueBytes($value, $speed_format);
		} else {
			$value = null;
		}
		return $value;
	}

	public function getSpeedFormat() {
		return $this->getViewState(self::SPEED_FORMAT, self::DEFAULT_SPEED_FORMAT);
	}

	public function setSpeedFormat($format) {
		$this->setViewState(self::SPEED_FORMAT, $format);
	}

	public function getSpeedFormats() {
		$speed_formats = array();
		$units = $this->getUnits();
		for ($i = 0; $i < count($units); $i++) {
			$speed_formats[$units[$i]['format']] = $units[$i]['label'];
		}
		return $speed_formats;
	}

	/**
	 * Get units basing on passed unit type.
	 * If no unit type, all defined unit types are supported.
	 * Use this method to get allowed units.
	 *
	 * @return array allowed units to use in control
	 */
	public function getUnits() {
		$units = array();
		$unit_type = $this->getUnitType();
		if (empty($unit_type)) {
			$units = $this->units;
		} else {
			for ($i = 0; $i < count($this->units); $i++) {
				if ($i > 0) {
					if ($unit_type === self::DECIMAL_UNIT_TYPE && $this->units[$i]['value'] % 1000 != 0) {
						continue;
					} elseif ($unit_type === self::BINARY_UNIT_TYPE && $this->units[$i]['value'] % 1024 != 0) {
						continue;
					}
				}
				$units[] = $this->units[$i];
			}
		}
		return $units;
	}

	public function createDirective() {
		$speed_format = $this->getSpeedFormat();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if ($this->getInConfig() === false && empty($directive_value)) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = 0;
			}
		}
		$formatted_value = $this->formatSpeed($directive_value, $speed_format);
		$this->Directive->Text = $formatted_value['value'];
		$this->SpeedFormat->DataSource = $this->getSpeedFormats();
		$this->SpeedFormat->dataBind();
		$this->SpeedFormat->SelectedValue = $formatted_value['format'];
		$this->Label->Text = $this->getLabel();
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
	}

	/**
	 * Convert original speed in bytes into given speed format.
	 *
	 * Note, if there is not possible to convert speed value into given format
	 * then there will be returned value converted by using as close format as possible.
	 * Example:
	 *  speed value: 121000
	 *  given format: ''
	 *  returned value: 121
	 *  returned format: 'kb/s'
	 */
	private function formatSpeed($speed_bytes, $format) {
		$value = $speed_bytes;
		if ($value > 0) {
			$units = $this->getUnits();
			for ($i = (count($units) - 1); $i >= 0; $i--) {
				if ($units[$i]['format'] != $format) {
					$remainder = $value % $units[$i]['value'];
					if ($remainder == 0) {
						$value /= $units[$i]['value'];
						$format = $units[$i]['format'];
						break;
					}
				}
			}
		}
		return array('value' => $value, 'format' => $format);
	}

	private function getValueBytes($value, $speed_format) {
		$units = $this->getUnits();
		for ($i = 0; $i < count($units); $i++) {
			if ($units[$i]['format'] === $speed_format) {
				$value *= $units[$i]['value'];
				break;
			}
		}
		return $value;
	}

	/**
	 * Set unit type to show in control.
	 * Allowed values are: decemial or binary.
	 * If not set, there supported are both decimal and binary.
	 *
	 * @param string $unit_type unit type value (decimal or binary)
	 * @return none
	 * @throw Exception if provided invalid unit type value
	 */
	public function setUnitType($unit_type) {
		if ($unit_type === self::DECIMAL_UNIT_TYPE || $unit_type === self::BINARY_UNIT_TYPE) {
			$this->setViewState(self::UNIT_TYPE, $unit_type);
		} else {
			$emsg = sprintf(
				'Invalid control syntax: %s has no unit type "%s".',
				__CLASS__,
				$unit_type
			);
			throw new Exception($emsg);
		}
	}

	/**
	 * Get unit type to show in control.
	 * If unit type is not set, returned is empty string that means both unit types are supported.
	 *
	 * @return none
	 */
	public function getUnitType() {
		return $this->getViewState(self::UNIT_TYPE, '');
	}
}
?>
