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

Prado::using('System.Web.UI.TTemplateControl');

/**
 * Abstraction to directive control template.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 * @package Baculum Web
 */
abstract class DirectiveControlTemplate extends TTemplateControl {

	public function getCmdParam() {
		$command_param = null;
		if ($this->getPage()->IsCallBack) {
			if (method_exists($this->getPage()->CallBackEventTarget, 'getCommandParameter')) {
				$command_param = $this->getPage()->CallBackEventTarget->getCommandParameter();
			} else {
				$command_param = $this->getPage()->getCallbackEventParameter();
			}
		} elseif ($this->getPage()->IsPostBack) {
			if (method_exists($this->getPage()->PostBackEventTarget, 'getCommandParameter')) {
				$command_param = $this->getPage()->PostBackEventTarget->getCommandParameter();
			}
		}
		if (is_array($command_param) && count($command_param) > 0) {
			$command_param = $command_param[0];
		}
		return $command_param;
	}

}
?>
