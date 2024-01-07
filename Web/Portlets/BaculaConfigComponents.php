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

/**
 * Bacula config components control.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
class BaculaConfigComponents extends ComponentListTemplate
{
	public const CHILD_CONTROL = 'BaculaConfigResources';

	public const MENU_CONTROL = 'NewResourceMenu';

	public const ACTIONS_CONTROL = 'ComponentActionsMenu';

	private function getConfigData($host)
	{
		$params = ['config'];
		$result = $this->Application->getModule('api')->get($params, $host, false);
		$config = [];
		$this->ErrorMsg->Display = 'None';
		if (is_object($result) && $result->error === 0 && is_array($result->output)) {
			$config = $result->output;
		} else {
			$this->ErrorMsg->Text = print_r($result, true);
			$this->ErrorMsg->Display = 'Dynamic';
		}
		return $config;
	}

	public function loadConfig()
	{
		$components = [];
		$host = $this->getHost();
		$config = $this->getConfigData($host);
		for ($i = 0; $i < count($config); $i++) {
			$component = (array) $config[$i];
			if (array_key_exists('component_type', $component) && array_key_exists('component_name', $component)) {
				$component['host'] = $host;
				$component['label'] = $this->getModule('misc')->getComponentFullName($component['component_type']);
				array_push($components, $component);
			}
		}
		$this->RepeaterComponents->DataSource = $components;
		$this->RepeaterComponents->dataBind();
	}

	public function createComponentListElement($sender, $param)
	{
		if (!is_array($param->Item->Data)) {
			// skip parent repeater items
			return;
		}
		$conts = [self::MENU_CONTROL, self::ACTIONS_CONTROL];
		for ($i = 0; $i < count($conts); $i++) {
			$controls = [self::CHILD_CONTROL, $conts[$i]];
			for ($j = 0; $j < count($controls); $j++) {
				$control = $this->getChildControl($param->Item, $controls[$j]);
				if (is_object($control)) {
					$control->setHost($param->Item->Data['host']);
					$control->setComponentType($param->Item->Data['component_type']);
					$control->setComponentName($param->Item->Data['component_name']);
				}
			}
		}
	}

	public function getResources($sender, $param)
	{
		$control = $this->getChildControl($sender->getParent(), self::CHILD_CONTROL);
		if (is_object($control)) {
			$control->raiseEvent('OnResourceListLoad', $this, null);
		}
	}

	public function newResource($sender, $param)
	{
		[$resource_type, $host, $component_type, $component_name] = explode('|', $param->getCommandParameter(), 4);
		$this->NewResource->setHost($host);
		$this->NewResource->setComponentType($component_type);
		$this->NewResource->setComponentName($component_name);
		$this->NewResource->setResourceType($resource_type);
		$this->NewResource->setLoadValues(false);
		$this->NewResource->raiseEvent('OnDirectiveListLoad', $this, null);
	}
}
