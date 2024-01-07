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

use Prado\Prado;
use Prado\Web\UI\WebControls\TItemDataRenderer;
use Bacularis\Web\Portlets\DirectiveListTemplate;
use Bacularis\Web\Portlets\DirectiveCheckBox;
use Bacularis\Web\Portlets\DirectiveCheckBoxSimple;
use Bacularis\Web\Portlets\DirectiveComboBox;
use Bacularis\Web\Portlets\DirectiveComboBoxReload;
use Bacularis\Web\Portlets\DirectiveInteger;
use Bacularis\Web\Portlets\DirectiveListBox;
use Bacularis\Web\Portlets\DirectiveOrderedListBox;
use Bacularis\Web\Portlets\DirectivePassword;
use Bacularis\Web\Portlets\DirectiveSize;
use Bacularis\Web\Portlets\DirectiveSpeed;
use Bacularis\Web\Portlets\DirectiveTextBox;
use Bacularis\Web\Portlets\DirectiveMultiComboBox;
use Bacularis\Web\Portlets\DirectiveMultiTextBox;
use Bacularis\Web\Portlets\DirectiveTimePeriod;
use Bacularis\Web\Portlets\DirectiveRunscript;
use Bacularis\Web\Portlets\DirectiveMessages;

/**
 * Directive renderer control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveRenderer extends TItemDataRenderer
{
	public const DATA = 'Data';
	public const IS_DATA_BOUND = 'IsDataBound';

	private $directive_types = [
		'DirectiveCheckBox',
		'DirectiveComboBox',
		'DirectiveComboBoxReload',
		'DirectiveInteger',
		'DirectiveListBox',
		'DirectiveOrderedListBox',
		'DirectivePassword',
		'DirectiveTextBox',
		'DirectiveSize',
		'DirectiveSpeed',
		'DirectiveTimePeriod'
	];

	private $directive_list_types = [
		'DirectiveFileSet',
		'DirectiveSchedule',
		'DirectiveMessages',
		'DirectiveRunscript',
		'DirectiveMultiComboBox',
		'DirectiveMultiTextBox'
	];

	private static $current_section = '';

	public function loadState()
	{
		parent::loadState();
		$this->createItemInternal();
	}

	public function createItemInternal()
	{
		$data = $this->getData();

		$this->createItem($data);
		$this->setIsDataBound(true);
	}

	public function render($writer)
	{
		$data = $this->getData();
		if (key_exists('section', $data)) {
			if ($data['section'] !== self::$current_section) {
				self::$current_section = $data['section'];
				$writer->write('<h3 class="directive_section_header w3-border-bottom" data-section="' . $data['section'] . '" style="display:none;">' . $data['section'] . '</h3>');
			}
		}
		parent::render($writer);
	}

	public function createItem($data)
	{
		$field = $this->getField($data['field_type']);
		$control = Prado::createComponent($field);
		$type = 'Directive' . $data['field_type'];
		if (in_array($type, $this->directive_types)) {
			$control->setHost($data['host']);
			$control->setComponentType($data['component_type']);
			$control->setComponentName($data['component_name']);
			$control->setResourceType($data['resource_type']);
			$control->setResourceName($data['resource_name']);
			$control->setDirectiveName($data['directive_name']);
			$control->setDirectiveValue($data['directive_value']);
			$control->setDefaultValue($data['default_value']);
			$control->setRequired($data['required']);
			$control->setData($data['data']);
			$control->setResource($data['resource']);
			$control->setLabel($data['label']);
			$control->setInConfig($data['in_config']);
			$control->setShow($data['show']);
			$control->setGroupName($data['group_name']);
			$control->setParentName($data['parent_name']);
			$control->setResourceNames($this->SourceTemplateControl->getResourceNames());
			if ($data['directive_name'] === 'Name') {
				$control->setDisabled($this->SourceTemplateControl->getDisableRename());
			}
			$this->addParsedObject($control);
			$control->createDirective();
		} elseif (in_array($type, $this->directive_list_types)) {
			$control->setHost($data['host']);
			$control->setComponentType($data['component_type']);
			$control->setComponentName($data['component_name']);
			$control->setResourceType($data['resource_type']);
			$control->setResourceName($data['resource_name']);
			$control->setDirectiveName($data['directive_name']);
			$control->setData($data['directive_value']);
			$control->setParentName($data['parent_name']);
			$control->setLoadValues($this->SourceTemplateControl->getLoadValues());
			$control->setResourceNames($this->SourceTemplateControl->getResourceNames());
			$control->setShow($data['show']);
			$control->setGroupName($data['group_name']);
			$control->setResource($data['resource']);
			$this->addParsedObject($control);
			if (!$this->getIsDataBound()) {
				$control->raiseEvent('OnDirectiveListLoad', $this, null);
			}
		}
		return $control;
	}

	public function getData()
	{
		return $this->getViewState(self::DATA);
	}

	public function setData($data)
	{
		$this->setViewState(self::DATA, $data);
	}

	public function getIsDataBound()
	{
		return $this->getViewState(self::IS_DATA_BOUND);
	}

	public function setIsDataBound($is_data_bound)
	{
		$this->setViewState(self::IS_DATA_BOUND, $is_data_bound);
	}

	private function getField($field_type)
	{
		return 'Bacularis\Web\Portlets\Directive' . $field_type;
	}
}
