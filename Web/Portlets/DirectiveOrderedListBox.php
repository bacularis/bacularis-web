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
 * OrderedListBox directive template.
 * It enables to save list box items in given order.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveOrderedListBox extends DirectiveTemplate
{
	public function getValue()
	{
		$values = explode('!', $this->DirectiveHidden->getValue());
		$vals = array_filter($values);
		return count($vals) > 0 ? $vals : null;
	}

	public function dataBind()
	{
		// this must be empty to keep data item list
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
		$this->Directive->DataSource = array_combine($items, $items);
		$this->Directive->dataBind();

		$data_items = $this->Directive->getItems();
		foreach ($data_items as $item) {
			$value = $item->getValue();
			$idx = array_search($value, $directive_value);
			if ($idx !== false) {
				// element selected
				$pos = $idx + 1;
				$label = sprintf(
					'[%d] %s',
					$pos,
					$value
				);
				$item->setAttribute('data-pos', $pos);
				$item->setSelected(true);
				$item->setText($label);
			}
		}

		$default_value = $this->getDefaultValue();
		if ($in_config === false) {
			if ($default_value !== 0) {
				$directive_value = $default_value;
			} else {
				$directive_value = [];
			}
		}
		$validate = $this->getRequired();
		$this->DirectiveValidator->setVisible($validate);
	}
}
