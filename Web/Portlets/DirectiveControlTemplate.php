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

use Prado\Web\UI\TTemplateControl;

/**
 * Abstraction to directive control template.
 *
 * @author Marcin Haba <marcin.haba@bacula.pl>
 * @category Control
 */
abstract class DirectiveControlTemplate extends TTemplateControl
{
	public function getCmdParam()
	{
		$command_param = null;
		if ($this->getPage()->IsCallBack) {
			$cbet = $this->getPage()->CallBackEventTarget;
			if (is_object($cbet) && method_exists($cbet, 'getCommandParameter')) {
				$command_param = $cbet->getCommandParameter();
			} else {
				$command_param = $this->getPage()->getCallbackEventParameter();
			}
		} elseif ($this->getPage()->IsPostBack) {
			$pbet = $this->getPage()->PostBackEventTarget;
			if (is_object($pbet) && method_exists($pbet, 'getCommandParameter')) {
				$command_param = $pbet->getCommandParameter();
			}
		}
		if (is_array($command_param) && count($command_param) > 0) {
			$command_param = $command_param[0];
		}
		return $command_param;
	}
}
