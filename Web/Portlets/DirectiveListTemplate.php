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

Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');
Prado::using('Application.Web.Portlets.ConfigListTemplate');

/**
 * Directive list template.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class DirectiveListTemplate extends ConfigListTemplate implements IActiveControl, ICallbackEventHandler {

	const HOST = 'Host';
	const COMPONENT_TYPE = 'ComponentType';
	const COMPONENT_NAME = 'ComponentName';
	const RESOURCE_TYPE = 'ResourceType';
	const RESOURCE_NAME = 'ResourceName';
	const RESOURCE_NAMES = 'ResourceNames';
	const RESOURCE = 'Resource';
	const DIRECTIVE_NAME = 'DirectiveName';
	const DATA = 'Data';
	const LOAD_VALUES = 'LoadValues';
	const SHOW = 'Show';
	const PARENT_NAME = 'ParentName';
	const GROUP_NAME = 'GroupName';
	const IS_DIRECTIVE_CREATED = 'IsDirectiveCreated';
	const COPY_MODE = 'CopyMode';

	public $doc;

	public $display_directive;

	public function __construct() {
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
		$this->onDirectiveListLoad(array($this, 'loadConfig'));
	}

	public function getActiveControl() {
		return $this->getAdapter()->getBaseActiveControl();
	}

	public function raiseCallbackEvent($param) {
		$this->raisePostBackEvent($param);
		$this->onCallback($param);
	}

	public function onDirectiveListLoad($handler) {
		$this->attachEventHandler('OnDirectiveListLoad', $handler);
	}

	public function onPreRender($param) {
		parent::onPreRender($param);
		if (!$this->getPage()->IsCallBack && !$this->getPage()->IsPostBack) {
			$this->display_directive = $this->getShow();
		}
		$this->setDoc();
	}

	public function onLoad($param) {
		parent::onLoad($param);
		if (!$this->getPage()->IsCallBack && !$this->getPage()->IsPostBack && method_exists($this, 'loadConfig')) {
			$this->loadConfig();
		}
	}

	public function getHost() {
		return $this->getViewState(self::HOST);
	}

	public function setHost($host) {
		$this->setViewState(self::HOST, $host);
	}

	public function getComponentType() {
		return $this->getViewState(self::COMPONENT_TYPE);
	}

	public function setComponentType($type) {
		$this->setViewState(self::COMPONENT_TYPE, $type);
	}

	public function getComponentName() {
		return $this->getViewState(self::COMPONENT_NAME);
	}

	public function setComponentName($name) {
		$this->setViewState(self::COMPONENT_NAME, $name);
	}

	public function getResourceType() {
		return $this->getViewState(self::RESOURCE_TYPE);
	}

	public function setResourceType($type) {
		$this->setViewState(self::RESOURCE_TYPE, $type);
	}

	public function getResourceName() {
		return $this->getViewState(self::RESOURCE_NAME);
	}

	public function setResourceName($name) {
		$this->setViewState(self::RESOURCE_NAME, $name);
	}

	public function getResourceNames() {
		return $this->getViewState(self::RESOURCE_NAMES);
	}

	public function setResourceNames($resource_names) {
		$this->setViewState(self::RESOURCE_NAMES, $resource_names);
	}

	public function getResource() {
		return $this->getViewState(self::RESOURCE);
	}

	public function setResource($resource) {
		$this->setViewState(self::RESOURCE, $resource);
	}

	public function getDirectiveName() {
		return $this->getViewState(self::DIRECTIVE_NAME);
	}

	public function setDirectiveName($name) {
		$this->setViewState(self::DIRECTIVE_NAME, $name);
	}

	public function getData() {
		return $this->getViewState(self::DATA);
	}

	public function setData($data) {
		$this->setViewState(self::DATA, $data);
	}

	public function getLoadValues() {
		return $this->getViewState(self::LOAD_VALUES, true);
	}

	public function setLoadValues($load_values) {
		settype($load_values, 'bool');
		$this->setViewState(self::LOAD_VALUES, $load_values);
	}

	public function getShow() {
		return $this->getViewState(self::SHOW);
	}

	public function setShow($show) {
		$this->setViewState(self::SHOW, $show);
	}

	public function getParentName() {
		return $this->getViewState(self::PARENT_NAME);
	}

	public function setParentName($parent_name) {
		$this->setViewState(self::PARENT_NAME, $parent_name);
	}

	public function getGroupName() {
		return $this->getViewState(self::GROUP_NAME);
	}

	public function setGroupName($group_name) {
		$this->setViewState(self::GROUP_NAME, $group_name);
	}

	public function getIsDirectiveCreated() {
		return $this->getViewState(self::IS_DIRECTIVE_CREATED);
	}

	public function setIsDirectiveCreated($is_created) {
		$this->setViewState(self::IS_DIRECTIVE_CREATED, $is_created);
	}

	public function getCopyMode() {
		return $this->getViewState(self::COPY_MODE, false);
	}

	public function setCopyMode($copy_mode) {
		$copy_mode = TPropertyValue::ensureBoolean($copy_mode);
		$this->setViewState(self::COPY_MODE, $copy_mode, false);
	}

	public function setDoc() {
		$component_type = $this->getComponentType();
		$resource_type = $this->getResourceType();
		$directive_name = $this->getDirectiveName();
		$this->doc = $this->Application->getModule('doc_dir')->getDoc(
			$component_type,
			$resource_type,
			$directive_name
		);
	}
}
?>
