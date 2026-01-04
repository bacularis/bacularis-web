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
 * Editable list box directive template.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveEditableListBox extends DirectiveTemplate
{
	public $text_mode = false;

	public function getValue()
	{
		$value = [];
		$text = $this->DirectiveText->getText();
		if (!empty($text) || $text == '0') {
			$value = explode(',', $text);
		} else {
			$values = $this->DirectiveCombo->getSelectedIndices();
			$items = $this->DirectiveCombo->getItems();
			for ($i = 0; $i < count($values); $i++) {
				$val = $items[$values[$i]]->getValue();
				if (!empty($val)) {
					$value[] = $val;
				}
			}
		}
		return $value;
	}

	public function createDirective()
	{
		$this->Label->Text = $this->getLabel();
		$data = $this->getData();
		$resource_names = $this->getResourceNames();
		$directive_name = $this->getDirectiveName();
		$resource = $this->getResource();
		$in_config = $this->getInConfig();
		$items = [];
		if (is_array($data)) {
			$items = $data;
		} elseif (is_array($resource_names)) {
			if (array_key_exists($directive_name, $resource_names)) {
				$items = $resource_names[$directive_name];
			} elseif (array_key_exists($resource, $resource_names)) {
				$items = $resource_names[$resource];
			}
		}
		$this->DirectiveCombo->DataSource = array_combine($items, $items);

		$directive_value = $this->getDirectiveValue();
		$default_value = $this->getDefaultValue();
		if ($in_config === false) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = [];
			}
		}
		$selected_indices = [];
		for ($i = 0; $i < count($items); $i++) {
			if (in_array($items[$i], $directive_value)) {
				$selected_indices[] = $i;
			}
		}

		$this->text_mode = (count($directive_value) > 0 && count($selected_indices) != count($directive_value));
		$this->DirectiveText->Text = implode(',', $directive_value);

		if (!empty($directive_value)) {
			$this->DirectiveCombo->setSelectedIndices($selected_indices);
		}
		$this->DirectiveCombo->dataBind();
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
	}
}
