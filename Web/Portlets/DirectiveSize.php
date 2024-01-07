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

/**
 * Size directive control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveSize extends DirectiveTemplate
{
	public const SIZE_FORMAT = 'SizeFormat';
	public const DEFAULT_SIZE_FORMAT = '';

	private $size_formats = [
		['format' => '', 'value' => 1, 'label' => 'B'],
		['format' => 'kb', 'value' => 1000, 'label' => 'kB'],
		['format' => 'k', 'value' => 1024, 'label' => 'KiB'],
		['format' => 'mb', 'value' => 1000000, 'label' => 'MB'],
		['format' => 'm', 'value' => 1048576, 'label' => 'MiB'],
		['format' => 'gb', 'value' => 1000000000, 'label' => 'GB'],
		['format' => 'g', 'value' => 1073741824, 'label' => 'GiB'],
		['format' => 'tb', 'value' => 1000000000000, 'label' => 'TB'],
		['format' => 't', 'value' => 1099511627776, 'label' => 'TiB']
	];

	public function getValue()
	{
		$value = $this->Directive->Text;
		if (is_numeric($value)) {
			settype($value, 'integer');
			$size_format = $this->SizeFormat->SelectedValue;
			$value = $this->getValueBytes($value, $size_format);
		} else {
			$value = null;
		}
		return $value;
	}

	public function getSizeFormat()
	{
		return $this->getViewState(self::SIZE_FORMAT, self::DEFAULT_SIZE_FORMAT);
	}

	public function setSizeFormat($format)
	{
		$this->setViewState(self::SIZE_FORMAT, $format);
	}

	public function getSizeFormats()
	{
		$size_formats = [];
		for ($i = 0; $i < count($this->size_formats); $i++) {
			$size_formats[$this->size_formats[$i]['format']] = $this->size_formats[$i]['label'];
		}
		return $size_formats;
	}

	public function createDirective()
	{
		$size_format = $this->getSizeFormat();
		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if ($this->getInConfig() === false && empty($directive_value)) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = 0;
			}
		}
		$formatted_value = $this->formatSize($directive_value, $size_format);
		$this->Directive->Text = $formatted_value['value'];
		$this->SizeFormat->DataSource = $this->getSizeFormats();
		$this->SizeFormat->dataBind();
		$this->SizeFormat->SelectedValue = $formatted_value['format'];
		$this->Label->Text = $this->getLabel();
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
	}

	/**
	 * Convert original size in bytes into given size format.
	 *
	 * Note, if there is not possible to convert size value into given format
	 * then there will be returned value converted by using as close format as possible.
	 * Example:
	 *  size_value: 121000
	 *  given format: b
	 *  returned value: 121
	 *  returned format: kb
	 * @param mixed $size_bytes
	 * @param mixed $format
	 */
	private function formatSize($size_bytes, $format)
	{
		$value = $size_bytes;
		if ($value > 0) {
			for ($i = (count($this->size_formats) - 1); $i >= 0; $i--) {
				if ($this->size_formats[$i]['format'] != $format) {
					$remainder = $value % $this->size_formats[$i]['value'];
					if ($remainder == 0) {
						$value /= $this->size_formats[$i]['value'];
						$format = $this->size_formats[$i]['format'];
						break;
					}
				}
			}
		}
		return ['value' => $value, 'format' => $format];
	}

	private function getValueBytes($value, $size_format)
	{
		for ($i = 0; $i < count($this->size_formats); $i++) {
			if ($this->size_formats[$i]['format'] === $size_format) {
				$value *= $this->size_formats[$i]['value'];
				break;
			}
		}
		return $value;
	}
}
