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

Prado::using('System.Web.UI.ActiveControls.TActiveControlAdapter');
Prado::using('Application.Web.Portlets.ConfigListTemplate');

/**
 * Component list template control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class ComponentListTemplate extends ConfigListTemplate implements IActiveControl, ICallbackEventHandler {

	const HOST = 'Host';
	const COMPONENT_TYPE = 'ComponentType';
	const COMPONENT_NAME = 'ComponentName';

	public function __construct() {
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
		$this->onComponentListLoad(array($this, 'loadConfig'));
	}

	public function getActiveControl() {
		return $this->getAdapter()->getBaseActiveControl();
	}

	public function raiseCallbackEvent($param) {
		$this->raisePostBackEvent($param);
		$this->onCallback($param);
	}

	public function onComponentListLoad($handler) {
		$this->attachEventHandler('OnComponentListLoad', $handler);
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
}
?>
