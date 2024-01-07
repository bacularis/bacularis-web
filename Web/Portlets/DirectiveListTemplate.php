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

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TActiveControlAdapter;
use Prado\TPropertyValue;

/**
 * Directive list template.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class DirectiveListTemplate extends ConfigListTemplate implements IActiveControl, ICallbackEventHandler
{
	public const HOST = 'Host';
	public const COMPONENT_TYPE = 'ComponentType';
	public const COMPONENT_NAME = 'ComponentName';
	public const RESOURCE_TYPE = 'ResourceType';
	public const RESOURCE_NAME = 'ResourceName';
	public const RESOURCE_NAMES = 'ResourceNames';
	public const RESOURCE = 'Resource';
	public const DIRECTIVE_NAME = 'DirectiveName';
	public const DATA = 'Data';
	public const LOAD_VALUES = 'LoadValues';
	public const SHOW = 'Show';
	public const PARENT_NAME = 'ParentName';
	public const GROUP_NAME = 'GroupName';
	public const IS_DIRECTIVE_CREATED = 'IsDirectiveCreated';
	public const COPY_MODE = 'CopyMode';
	public const DOC = 'Doc';

	public $doc;

	public $display_directive;

	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
		$this->onDirectiveListLoad([$this, 'loadConfig']);
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

	public function onDirectiveListLoad($handler)
	{
		$this->attachEventHandler('OnDirectiveListLoad', $handler);
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if (!$this->getPage()->IsCallBack && !$this->getPage()->IsPostBack) {
			$this->display_directive = $this->getShow();
		}
		$this->createDoc();
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

	public function getResourceNames()
	{
		return $this->getViewState(self::RESOURCE_NAMES);
	}

	public function setResourceNames($resource_names)
	{
		$this->setViewState(self::RESOURCE_NAMES, $resource_names);
	}

	public function getResource()
	{
		return $this->getViewState(self::RESOURCE);
	}

	public function setResource($resource)
	{
		$this->setViewState(self::RESOURCE, $resource);
	}

	public function getDirectiveName()
	{
		return $this->getViewState(self::DIRECTIVE_NAME);
	}

	public function setDirectiveName($name)
	{
		$this->setViewState(self::DIRECTIVE_NAME, $name);
	}

	public function getData()
	{
		return $this->getViewState(self::DATA);
	}

	public function setData($data)
	{
		$this->setViewState(self::DATA, $data);
	}

	public function getLoadValues()
	{
		return $this->getViewState(self::LOAD_VALUES, true);
	}

	public function setLoadValues($load_values)
	{
		settype($load_values, 'bool');
		$this->setViewState(self::LOAD_VALUES, $load_values);
	}

	public function getShow()
	{
		return $this->getViewState(self::SHOW);
	}

	public function setShow($show)
	{
		$this->setViewState(self::SHOW, $show);
	}

	public function getParentName()
	{
		return $this->getViewState(self::PARENT_NAME);
	}

	public function setParentName($parent_name)
	{
		$this->setViewState(self::PARENT_NAME, $parent_name);
	}

	public function getGroupName()
	{
		return $this->getViewState(self::GROUP_NAME);
	}

	public function setGroupName($group_name)
	{
		$this->setViewState(self::GROUP_NAME, $group_name);
	}

	public function getIsDirectiveCreated()
	{
		return $this->getViewState(self::IS_DIRECTIVE_CREATED);
	}

	public function setIsDirectiveCreated($is_created)
	{
		$this->setViewState(self::IS_DIRECTIVE_CREATED, $is_created);
	}

	public function getCopyMode()
	{
		return $this->getViewState(self::COPY_MODE, false);
	}

	public function setCopyMode($copy_mode)
	{
		$copy_mode = TPropertyValue::ensureBoolean($copy_mode);
		$this->setViewState(self::COPY_MODE, $copy_mode, false);
	}

	public function createDoc()
	{
		$doc = $this->getDoc();
		if (!empty($doc)) {
			$this->doc = $doc;
		} else {
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

	public function setDoc($doc)
	{
		$this->setViewState(self::DOC, $doc);
	}

	public function getDoc()
	{
		return $this->getViewState(self::DOC, '');
	}
}
