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

Prado::using('System.Web.UI.ActiveControls.TActiveLinkButton');
Prado::using('Application.Web.Portlets.DirectiveListTemplate');

/**
 * Component actions control responsible for start,
 * stop and restart components.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
class ComponentActionsMenu extends DirectiveListTemplate {

	const BIG_BUTTONS = 'BigButtons';

	/**
	 * Allowed actions to do with components.
	 */
	private $allowed_actions = array('start', 'stop', 'restart');

	/**
	 * Do action on component.
	 *
	 * @param TActiveLinkButton $sender sender object
	 * @param TCommandEventParameter $param command parameter
	 * @return none
	 */
	public function componentAction($sender, $param) {
		$action = $param->getCommandParameter();
		if (in_array($action, $this->allowed_actions)) {
			$host = $this->getHost();
			$component_type = $this->getComponentType();
			$component = $this->getModule('misc')->getComponentUrlName($component_type);
			$result = $this->getModule('api')->get(
				array('actions', $component, $action),
				$host
			);
			$this->getPage()->getCallbackClient()->callClientFunction(
				$this->ClientID . '_component_action_set_result',
				array($action, $result)
			);
		}
	}

	public function setBigButtons($big_buttons) {
		$big_buttons = TPropertyValue::ensureBoolean($big_buttons);
		$this->setViewState(self::BIG_BUTTONS, $big_buttons);
	}

	public function getBigButtons() {
		return $this->getViewState(self::BIG_BUTTONS, false);
	}
}
