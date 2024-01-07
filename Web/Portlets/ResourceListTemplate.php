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

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;

/**
 * Resource list template control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class ResourceListTemplate extends ConfigListTemplate implements IActiveControl, ICallbackEventHandler
{
	public const HOST = 'Host';
	public const COMPONENT_TYPE = 'ComponentType';
	public const COMPONENT_NAME = 'ComponentName';

	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
		$this->onResourceListLoad([$this, 'loadConfig']);
	}

	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	public function raiseCallbackEvent($param)
	{
		$this->raisePostBackEvent($param);
		$this->onCallback($param);
	}

	public function onResourceListLoad($handler)
	{
		$this->attachEventHandler('OnResourceListLoad', $handler);
	}

	public function getHost()
	{
		return $this->getViewState(self::HOST);
	}

	public function setHost($host)
	{
		$this->setViewState(self::HOST, $host);
	}

	public function getComponentType()
	{
		return $this->getViewState(self::COMPONENT_TYPE);
	}

	public function setComponentType($type)
	{
		$this->setViewState(self::COMPONENT_TYPE, $type);
	}

	public function getComponentName()
	{
		return $this->getViewState(self::COMPONENT_NAME);
	}

	public function setComponentName($name)
	{
		$this->setViewState(self::COMPONENT_NAME, $name);
	}
}
