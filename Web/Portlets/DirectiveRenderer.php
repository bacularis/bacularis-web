<?php
/*
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

Prado::using('System.Web.UI.ActiveControls.TActiveLabel');
Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('System.Web.UI.ActiveControls.TActivePanel');
Prado::using('System.Web.UI.ActiveControls.TActiveRepeater');
Prado::using('System.Web.UI.WebControls.TItemDataRenderer');
Prado::using('System.Web.UI.WebControls.THeader3');
Prado::using('System.Web.UI.WebControls.TLiteral');
Prado::using('Application.Web.Portlets.DirectiveListTemplate');
Prado::using('Application.Web.Portlets.DirectiveCheckBox');
Prado::using('Application.Web.Portlets.DirectiveComboBox');
Prado::using('Application.Web.Portlets.DirectiveInteger');
Prado::using('Application.Web.Portlets.DirectiveListBox');
Prado::using('Application.Web.Portlets.DirectivePassword');
Prado::using('Application.Web.Portlets.DirectiveSize');
Prado::using('Application.Web.Portlets.DirectiveSpeed');
Prado::using('Application.Web.Portlets.DirectiveTextBox');
Prado::using('Application.Web.Portlets.DirectiveMultiComboBox');
Prado::using('Application.Web.Portlets.DirectiveMultiTextBox');
Prado::using('Application.Web.Portlets.DirectiveTimePeriod');
Prado::using('Application.Web.Portlets.DirectiveRunscript');
Prado::using('Application.Web.Portlets.DirectiveMessages');

/**
 * Directive renderer control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class DirectiveRenderer extends TItemDataRenderer {

	const DATA = 'Data';
	const IS_DATA_BOUND = 'IsDataBound';

	private $directive_types = array(
		'DirectiveCheckBox',
		'DirectiveComboBox',
		'DirectiveInteger',
		'DirectiveListBox',
		'DirectivePassword',
		'DirectiveTextBox',
		'DirectiveSize',
		'DirectiveSpeed',
		'DirectiveTimePeriod'
	);

	private $directive_list_types = array(
		'DirectiveFileSet',
		'DirectiveSchedule',
		'DirectiveMessages',
		'DirectiveRunscript',
		'DirectiveMultiComboBox',
		'DirectiveMultiTextBox'
	);

	private static $current_section = '';

	public function loadState() {
		parent::loadState();
		$this->createItemInternal();
	}

	public function createItemInternal() {
		$data = $this->getData();

		if (key_exists('section', $data)) {
			$this->addSection($data['section']);
		}

		$this->createItem($data);
		$this->setIsDataBound(true);
	}

	public function createItem($data) {
		$load_values = $this->SourceTemplateControl->getLoadValues();
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

	public function addSection($section) {
		if ($section !== self::$current_section) {
			self::$current_section = $section;
			$h3 = new THeader3();
			$h3->setCssClass('directive_section_header w3-border-bottom');
			$h3->setStyle('display: none');
			$h3->setAttribute('data-section', $section);
			$text = new TLiteral();
			$text->setText(Prado::localize($section));
			$h3->addParsedObject($text);
			$this->addParsedObject($h3);
		}

	}

	public function getData() {
		return $this->getViewState(self::DATA);
	}

	public function setData($data) {
		$this->setViewState(self::DATA, $data);
	}

	public function getIsDataBound() {
		return $this->getViewState(self::IS_DATA_BOUND);
	}

	public function setIsDataBound($is_data_bound) {
		$this->setViewState(self::IS_DATA_BOUND, $is_data_bound);
	}

	private function getField($field_type) {
		return 'Application.Web.Portlets.Directive' . $field_type;
	}
}
?>
