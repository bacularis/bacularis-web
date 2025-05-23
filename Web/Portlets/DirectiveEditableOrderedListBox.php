<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2025 Marcin Haba
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

use Prado\Prado;

/**
 * EditableOrderedListBox directive template.
 * It enables to save editable list box items in given order in combobox or text fields.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveEditableOrderedListBox extends DirectiveTemplate
{
	public $text_mode = false;

	public function getValue()
	{
		$text = $this->DirectiveText->getText();
		if (!empty($text) || $text == '0') {
			$values = explode(',', $text);
		} else {
			$values = explode('!', $this->DirectiveHidden->getValue());
		}
		$vals = array_filter($values);
		return count($vals) > 0 ? $vals : null;
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

		$directive_value = $this->getDirectiveValue() ?: [];
		$labels = [];
		for ($i = 0; $i < count($items); $i++) {
			$control = Prado::createComponent('\Prado\Web\UI\WebControls\TListItem');
			$control->setValue($items[$i]);
			$idx = array_search($items[$i], $directive_value);
			if ($idx !== false) {
				// element selected
				$pos = $idx + 1;
				$labels[$i] = sprintf(
					'[%d] %s',
					$pos,
					$items[$i]
				);
				$control->setAttribute('data-pos', $pos);
				$control->setSelected(true);
			} else {
				// element not selected
				$labels[$i] = $items[$i];
			}
			$control->setText($labels[$i]);
			$this->DirectiveCombo->addParsedObject($control);
		}

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
			if (is_array($directive_value) && in_array($items[$i], $directive_value)) {
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
		$this->setIsDirectiveCreated(true);
	}
}
