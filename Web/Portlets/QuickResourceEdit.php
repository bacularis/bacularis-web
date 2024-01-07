<?php
/*
 * Bacularis - Bacula web interface
 *
 * Copyright (C) 2021-2024 Marcin Haba
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

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;

/**
 * Quick resource edit control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class QuickResourceEdit extends Portlets implements IActiveControl
{
	public const COMPONENT_TYPE = 'ComponentType';
	public const RESOURCE_TYPE = 'ResourceType';
	public const RESOURCE_NAME = 'ResourceName';

	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	public function openQuickResourceEdit($sender, $param)
	{
		[$component_type, $resource_type, $resource_name] = $param->getCallbackParameter();
		$this->setComponentType($component_type);
		$this->setResourceType($resource_type);
		$this->setResourceName($resource_name);
		$this->open();
	}

	public function open()
	{
		$component_type = $this->getComponentType();
		if (!empty($_SESSION[$component_type])) {
			$resource_type = $this->getResourceType();
			$resource_name = $this->getResourceName();
			$this->QuickResourceEditDirectives->setComponentType($component_type);
			$this->QuickResourceEditDirectives->setComponentName($_SESSION[$component_type]);
			$this->QuickResourceEditDirectives->setResourceType($resource_type);
			$this->QuickResourceEditDirectives->setResourceName($resource_name);
			$this->QuickResourceEditDirectives->setLoadValues(true);
			$this->QuickResourceEditDirectives->raiseEvent('OnDirectiveListLoad', $this, null);
		}
	}

	public function getComponentType()
	{
		return $this->getViewState(self::COMPONENT_TYPE);
	}

	public function setComponentType($type)
	{
		$this->setViewState(self::COMPONENT_TYPE, $type);
	}

	public function getResourceType()
	{
		return $this->getViewState(self::RESOURCE_TYPE);
	}

	public function setResourceType($type)
	{
		$this->setViewState(self::RESOURCE_TYPE, $type);
	}

	public function getResourceName()
	{
		return $this->getViewState(self::RESOURCE_NAME);
	}

	public function setResourceName($name)
	{
		$this->setViewState(self::RESOURCE_NAME, $name);
	}
}
